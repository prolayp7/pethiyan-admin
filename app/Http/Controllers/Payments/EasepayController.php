<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Services\EasepayService;
use App\Services\OrderService;
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
     * Create a pending order from the current cart, then initiate Easepay
     * payment for that order. The payment callback only needs to capture or
     * fail the already-persisted order, so it does not depend on the cart
     * still existing after the customer returns from Easebuzz.
     *
     * POST /api/easepay/create-order
     * Body: { address_id, shipping_rate_id?, delivery_charge?, promo_code?, ... }
     */
    public function createOrder(\App\Http\Requests\User\Order\InitiateEasepayPaymentRequest $request, OrderService $orderService): JsonResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, __('labels.user_not_authenticated'), []);
        }

        $checkoutData = array_merge($validated, [
            'payment_type' => PaymentTypeEnum::EASEPAY(),
        ]);

        $validation = $orderService->validateCheckout($user, $checkoutData);
        if (!$validation['success']) {
            return ApiResponseType::sendJsonResponse(false, $validation['message'], $validation['data'] ?? []);
        }

        // Compute the amount server-side; never trust a client-supplied amount.
        $amount = $orderService->computeCheckoutAmount($user, $checkoutData);

        $orderResult = $orderService->createOrder($user, $checkoutData);
        if (!$orderResult['success'] || !(($orderResult['data'] ?? null) instanceof Order)) {
            return ApiResponseType::sendJsonResponse(
                false,
                $orderResult['message'] ?? 'Could not create the order.',
                $orderResult['data'] ?? []
            );
        }
        $order = $orderResult['data'];

        $txnid = 'ORD-' . Str::random(10) . '-' . $user->id;

        $placeholder = OrderPaymentTransaction::create([
            'uuid'           => Str::uuid()->toString(),
            'order_id'       => $order->id,
            'user_id'        => $user->id,
            'transaction_id' => $txnid,
            'amount'         => $amount,
            'currency'       => 'INR',
            'payment_method' => PaymentTypeEnum::EASEPAY(),
            'payment_status' => PaymentStatusEnum::PENDING(),
            'message'        => 'Payment initiated, awaiting confirmation',
        ]);

        $result = $this->easepayService->initiatePayment([
            'txnid'       => $txnid,
            'amount'      => $amount,
            'productinfo' => 'Order Payment',
            'firstname'   => $user->name ?? '',
            'email'       => $user->email ?? '',
            'phone'       => $user->mobile ?? '',
            'udf1'        => 'order_payment',
            'udf2'        => (string) $order->id,
            'udf3'        => (string)$user->id,
        ]);

        if (!$result['success']) {
            $placeholder->update([
                'payment_status' => PaymentStatusEnum::FAILED(),
                'message'        => $result['message'] ?? 'Could not initiate Easepay payment.',
            ]);
            Order::paymentFailed($order->id);
            OrderItem::paymentFailed($order->id);

            return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
        }

        return ApiResponseType::sendJsonResponse(true, $result['message'], [
            'txnid'       => $txnid,
            'order_id'    => $order->id,
            'order_number'=> $order->order_number,
            'order_slug'  => $order->slug,
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
        $webhookLog = PaymentWebhookLog::create([
            'gateway' => PaymentTypeEnum::EASEPAY(),
            'event_name' => strtolower((string) ($payload['status'] ?? 'callback')),
            'delivery_id' => $payload['easepayid'] ?? ($payload['txnid'] ?? null),
            'status' => 'received',
            'signature_valid' => false,
            'http_status' => 102,
            'request_headers' => $request->headers->all(),
            'raw_payload' => $payload,
        ]);

        Log::info('[Easepay] Webhook received', ['txnid' => $payload['txnid'] ?? null]);

        DB::beginTransaction();
        try {
            // Verify hash
            if (!$this->easepayService->verifyResponseHash($payload)) {
                Log::warning('[Easepay] Webhook hash mismatch', $payload);
                $webhookLog->update([
                    'status' => 'rejected',
                    'http_status' => 400,
                    'message' => 'Invalid hash',
                ]);
                DB::rollBack();
                return response()->json(['error' => 'Invalid hash'], 400);
            }

            $webhookLog->update([
                'signature_valid' => true,
                'status' => 'processing',
            ]);

            $status       = strtolower($payload['status']  ?? '');
            $paymentType  = strtolower($payload['udf1']    ?? 'order_payment');
            $txnid        = $payload['txnid']              ?? '';
            $easepayTxnId = $payload['easepayid']          ?? $txnid;

            // Easebuzz refund webhook — status is 'refund' or 'refund_bounced'
            if (in_array($status, ['refund', 'refund_bounced'])) {
                $this->easepayService->handleRefundWebhook($status, $payload, $txnid, $easepayTxnId, $webhookLog);
            } elseif ($paymentType === 'wallet_recharge') {
                $this->easepayService->handleWalletWebhook($status, $payload, $easepayTxnId);
            } else {
                $this->easepayService->handleOrderWebhook($status, $payload, $txnid, $easepayTxnId, $webhookLog);
            }

            $webhookLog->update([
                'status' => 'processed',
                'http_status' => 200,
                'processed_at' => now(),
            ]);

            DB::commit();
            return response()->json(['status' => 'success'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Easepay] Webhook exception: ' . $e->getMessage());
            $webhookLog->update([
                'status' => 'failed',
                'http_status' => 500,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Client-side payment verification (post-redirect)
    // -------------------------------------------------------------------------

    /**
     * Verify a payment after the user is redirected back to the app.
     *
     * The Easepay/Easebuzz webhook is the authoritative confirmation path, but it
     * depends on the merchant dashboard being configured with our webhook URL and
     * on Easebuzz reliably delivering it. Since surl/furl point at the storefront
     * (not a backend endpoint), the frontend calls this after redirect so the order
     * gets captured immediately even if the webhook is delayed, missing, or
     * misconfigured. handleOrderWebhook() is idempotent, so it's safe to also run
     * here if the webhook fires separately for the same transaction.
     *
     * POST /api/easepay/verify-payment
     * Body: { txnid }
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate(['txnid' => 'required|string']);
        $txnid = $validated['txnid'];

        $result = $this->easepayService->verifyTransaction($txnid);

        $lookupTransactionId = $txnid;
        $paymentConfirmed = false;

        if ($result['success']) {
            $payload = $result['data'] ?? [];
            $transactionPayload = $this->findTransactionPayload($payload);
            $status = strtolower((string) ($transactionPayload['status'] ?? ''));

            if (in_array($status, ['success', 'failure', 'usercancel'], true)) {
                $paymentConfirmed = $status === 'success';
                $easepayTxnId = $transactionPayload['easepayid'] ?? $txnid;
                $this->easepayService->handleOrderWebhook($status, $transactionPayload, $txnid, $easepayTxnId);
                // On success, handleOrderWebhook() supersedes the txnid-keyed
                // placeholder with a row keyed by easepayTxnId — look that up
                // instead so the order (if created) is found.
                $lookupTransactionId = $status === 'success' ? $easepayTxnId : $txnid;
            }
        }

        $orderInfo = ['order_id' => null, 'order_number' => null, 'order_slug' => null];
        $transaction = OrderPaymentTransaction::where('transaction_id', $lookupTransactionId)->first();
        if (!$transaction && $lookupTransactionId !== $txnid) {
            $transaction = OrderPaymentTransaction::where('transaction_id', $txnid)->first();
        }

        if ($transaction?->order_id) {
            $order = Order::find($transaction->order_id);
            if ($order) {
                $orderInfo = [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'order_slug'   => $order->slug,
                ];
            }
        }

        $success = $result['success'] && $orderInfo['order_id'] !== null;
        $message = $result['message'];
        if ($result['success'] && !$success) {
            $message = $transaction?->message
                ?: 'Payment was confirmed but the order is not ready yet. Please contact support with reference: ' . $txnid;
        }

        return ApiResponseType::sendJsonResponse(
            $success,
            $message,
            array_merge($result['data'] ?? [], $orderInfo, [
                'payment_confirmed' => $paymentConfirmed,
            ])
        );
    }

    /**
     * Easebuzz's transaction API can return the transaction as a nested record.
     * Find the record whose status is a payment outcome rather than relying on
     * the outer API response's status field.
     *
     * @param array<mixed> $payload
     * @return array<string, mixed>
     */
    private function findTransactionPayload(array $payload): array
    {
        if (in_array(strtolower((string) ($payload['status'] ?? '')), ['success', 'failure', 'usercancel'], true)) {
            return $payload;
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $transactionPayload = $this->findTransactionPayload($value);
                if ($transactionPayload !== []) {
                    return $transactionPayload;
                }
            }
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // Refund
    // -------------------------------------------------------------------------

    /**
     * Refund an Easepay transaction.
     *
     * Admin-only: this moves real money back to the customer, so a plain
     * auth:sanctum check (any logged-in customer) isn't enough — only staff
     * with the admin access panel may call it.
     *
     * POST /api/easepay/refund
     * Body: { txnid, amount? }
     */
    public function refundPayment(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->access_panel?->value !== 'admin') {
            return ApiResponseType::sendJsonResponse(false, __('labels.unauthorized_access'), []);
        }

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
}
