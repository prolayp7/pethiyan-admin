<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use App\Models\PaymentRefund;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\EasepayService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EasepayController extends Controller
{
    public function __construct(protected EasepayService $easepayService) {}

    // -------------------------------------------------------------------------
    // Create Order Payment
    // -------------------------------------------------------------------------

    /**
     * Initiate Easepay payment for an order.
     *
     * POST /api/easepay/create-order
     * Body: { order_id, amount, productinfo?, firstname?, email?, phone? }
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'    => 'required|integer|exists:orders,id',
            'amount'      => 'required|numeric|min:1',
            'productinfo' => 'nullable|string|max:255',
            'firstname'   => 'nullable|string|max:100',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:20',
        ]);

        $user  = Auth::user();
        $txnid = 'ORD-' . $validated['order_id'] . '-' . Str::random(8);

        $result = $this->easepayService->initiatePayment([
            'txnid'       => $txnid,
            'amount'      => $validated['amount'],
            'productinfo' => $validated['productinfo'] ?? 'Order Payment',
            'firstname'   => $validated['firstname']   ?? ($user?->name ?? ''),
            'email'       => $validated['email']       ?? ($user?->email ?? ''),
            'phone'       => $validated['phone']       ?? ($user?->mobile ?? ''),
            'udf1'        => 'order_payment',
            'udf2'        => (string)$validated['order_id'],
            'udf3'        => (string)($user?->id ?? ''),
        ]);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
        }

        return ApiResponseType::sendJsonResponse(true, $result['message'], [
            'txnid'       => $txnid,
            'access_key'  => $result['access_key'],
            'payment_url' => $result['payment_url'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Create Wallet Recharge
    // -------------------------------------------------------------------------

    /**
     * Initiate Easepay payment for a wallet recharge.
     */
    public function createWalletRechargeOrder(array $data): array
    {
        $user  = Auth::user();
        $txnid = 'WAL-' . ($data['transaction_id'] ?? Str::random(10));

        return $this->easepayService->initiatePayment([
            'txnid'       => $txnid,
            'amount'      => $data['amount'],
            'productinfo' => $data['description'] ?? 'Wallet Recharge',
            'firstname'   => $user?->name  ?? '',
            'email'       => $user?->email ?? '',
            'phone'       => $user?->mobile ?? '',
            'udf1'        => 'wallet_recharge',
            'udf2'        => (string)($data['transaction_id'] ?? ''),
            'udf3'        => (string)($user?->id ?? ''),
        ]);
    }

    // -------------------------------------------------------------------------
    // Webhook / Server-Side Callback
    // -------------------------------------------------------------------------

    /**
     * Handle Easepay webhook / server-to-server callback.
     *
     * POST /api/easepay/webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('[Easepay] Webhook received', ['txnid' => $payload['txnid'] ?? null]);

        DB::beginTransaction();
        try {
            // Verify hash
            if (!$this->easepayService->verifyResponseHash($payload)) {
                Log::warning('[Easepay] Webhook hash mismatch', $payload);
                DB::rollBack();
                return response()->json(['error' => 'Invalid hash'], 400);
            }

            $status       = strtolower($payload['status']  ?? '');
            $paymentType  = strtolower($payload['udf1']    ?? 'order_payment');
            $txnid        = $payload['txnid']              ?? '';
            $easepayTxnId = $payload['easepayid']          ?? $txnid;

            // Easebuzz refund webhook — status is 'refund' or 'refund_bounced'
            if (in_array($status, ['refund', 'refund_bounced'])) {
                $this->handleRefundWebhook($status, $payload, $txnid, $easepayTxnId);
            } elseif ($paymentType === 'wallet_recharge') {
                $this->handleWalletWebhook($status, $payload, $easepayTxnId);
            } else {
                $this->handleOrderWebhook($status, $payload, $txnid, $easepayTxnId);
            }

            DB::commit();
            return response()->json(['status' => 'success'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Easepay] Webhook exception: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Client-side payment verification (post-redirect)
    // -------------------------------------------------------------------------

    /**
     * Verify a payment after the user is redirected back to the app.
     *
     * POST /api/easepay/verify-payment
     * Body: { txnid }
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate(['txnid' => 'required|string']);

        $result = $this->easepayService->verifyTransaction($validated['txnid']);

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data']
        );
    }

    // -------------------------------------------------------------------------
    // Refund
    // -------------------------------------------------------------------------

    /**
     * Refund an Easepay transaction.
     *
     * POST /api/easepay/refund
     * Body: { txnid, amount? }
     */
    public function refundPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'txnid'  => 'required|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $result = $this->easepayService->refundPayment(
            $validated['txnid'],
            isset($validated['amount']) ? (float)$validated['amount'] : null
        );

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            $result['data']
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function handleOrderWebhook(string $status, array $payload, string $txnid, string $easepayTxnId): void
    {
        $orderId = (int)($payload['udf2'] ?? 0);

        if ($status === 'success') {
            // Record or update the transaction
            OrderPaymentTransaction::updateOrCreate(
                ['transaction_id' => $easepayTxnId],
                [
                    'order_id'       => $orderId ?: null,
                    'user_id'        => $payload['udf3'] ?? null,
                    'transaction_id' => $easepayTxnId,
                    'amount'         => $payload['amount'] ?? 0,
                    'currency'       => $payload['currency'] ?? 'INR',
                    'payment_method' => PaymentTypeEnum::EASEPAY(),
                    'payment_status' => PaymentStatusEnum::COMPLETED(),
                    'message'        => 'Payment Successful',
                    'payment_details' => $payload,
                ]
            );

            if ($orderId) {
                Order::capturePayment($orderId);
                OrderItem::capturePayment($orderId);
            }

            Log::info('[Easepay] Order payment captured', ['order_id' => $orderId, 'txnid' => $txnid]);

        } elseif ($status === 'failure' || $status === 'usercancel') {
            $transaction = OrderPaymentTransaction::where('transaction_id', $txnid)->first();
            if ($transaction) {
                $transaction->update([
                    'payment_status' => PaymentStatusEnum::FAILED(),
                    'message'        => 'Payment ' . ucfirst($status),
                    'payment_details' => $payload,
                ]);
            }

            if ($orderId) {
                Order::paymentFailed($orderId);
                OrderItem::paymentFailed($orderId);
            }
        }
    }

    private function handleWalletWebhook(string $status, array $payload, string $easepayTxnId): void
    {
        $walletTransactionId = $payload['udf2'] ?? null;

        if (!$walletTransactionId) {
            Log::warning('[Easepay] Wallet webhook missing udf2 (transaction_id)');
            return;
        }

        $transaction = WalletTransaction::find($walletTransactionId);
        if (!$transaction) {
            Log::warning('[Easepay] WalletTransaction not found', ['id' => $walletTransactionId]);
            return;
        }

        if ($status === 'success') {
            $transaction->update(['transaction_reference' => $easepayTxnId]);
            $result = Wallet::captureRecharge($transaction->id);
            if (!$result['success']) {
                Log::error('[Easepay] Wallet capture failed: ' . $result['message']);
            }
        } elseif ($status === 'failure' || $status === 'usercancel') {
            $transaction->update(['status' => PaymentStatusEnum::FAILED(), 'message' => 'Payment ' . ucfirst($status)]);
        }
    }

    /**
     * Handle Easebuzz refund webhook.
     * Status is 'refund' (processed) or 'refund_bounced' (failed).
     * Easebuzz re-sends the original txnid; the easepayid field holds the refund reference.
     */
    private function handleRefundWebhook(string $status, array $payload, string $txnid, string $easepayRefundId): void
    {
        // Find original payment transaction by txnid
        $transaction = OrderPaymentTransaction::where('transaction_id', $txnid)->first();

        $refundStatus = $status === 'refund' ? 'processed' : 'failed';

        PaymentRefund::updateOrCreate(
            ['razorpay_refund_id' => 'ebz-' . $easepayRefundId],  // prefix to avoid collision with Razorpay IDs
            [
                'razorpay_payment_id'          => $txnid,
                'order_payment_transaction_id' => $transaction?->id,
                'order_id'                     => $transaction?->order_id,
                'amount'                       => $payload['amount'] ?? 0,
                'currency'                     => $payload['currency'] ?? 'INR',
                'status'                       => $refundStatus,
                'speed'                        => null,
                'notes'                        => null,
                'raw_payload'                  => $payload,
            ]
        );

        if ($refundStatus === 'processed' && $transaction) {
            $transaction->update([
                'payment_status' => PaymentStatusEnum::REFUNDED(),
                'message'        => 'Refund processed via Easebuzz',
            ]);
            Log::info('[Easepay] Refund processed', ['txnid' => $txnid, 'order_id' => $transaction->order_id]);
        } elseif ($refundStatus === 'failed') {
            Log::error('[Easepay] Refund BOUNCED (failed)', [
                'txnid'    => $txnid,
                'order_id' => $transaction?->order_id,
                'amount'   => $payload['amount'] ?? null,
            ]);
        }
    }
}
