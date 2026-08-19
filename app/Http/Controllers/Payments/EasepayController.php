<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
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
     * Validate a checkout and initiate Easepay payment for it. No Order is
     * created here — the validated checkout payload is stashed on the
     * placeholder transaction row (keyed by our internal txnid) and only
     * turned into a real Order once EasepayService::handleOrderWebhook()
     * confirms payment succeeded. See CLAUDE.md-adjacent plan doc:
     * docs/superpowers/plans/2026-08-18-easepay-defer-order-creation.md
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

        // Amount is computed server-side from the cart — never trust a
        // client-supplied amount, since it is what gets sent to the payment
        // gateway and stored as the payable amount for capture.
        $amount = $orderService->computeCheckoutAmount($user, $checkoutData);

        $txnid = 'ORD-' . Str::random(10) . '-' . $user->id;

        $result = $this->easepayService->initiatePayment([
            'txnid'       => $txnid,
            'amount'      => $amount,
            'productinfo' => 'Order Payment',
            'firstname'   => $user->name ?? '',
            'email'       => $user->email ?? '',
            'phone'       => $user->mobile ?? '',
            'udf1'        => 'order_payment',
            'udf3'        => (string)$user->id,
        ]);

        if (!$result['success']) {
            return ApiResponseType::sendJsonResponse(false, $result['message'], $result['data'] ?? []);
        }

        // Placeholder row: no order_id yet (none exists), checkout_payload is
        // the validated data createOrder() needs once payment is confirmed.
        // See EasepayService::handleOrderWebhook() for the consuming side.
        OrderPaymentTransaction::create([
            'uuid'             => Str::uuid()->toString(),
            'order_id'         => null,
            'user_id'          => $user->id,
            'transaction_id'   => $txnid,
            'amount'           => $amount,
            'currency'         => 'INR',
            'payment_method'   => PaymentTypeEnum::EASEPAY(),
            'payment_status'   => PaymentStatusEnum::PENDING(),
            'message'          => 'Payment initiated, awaiting confirmation',
            'checkout_payload' => $checkoutData,
        ]);

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

        if ($result['success']) {
            $payload = $result['data'] ?? [];
            $status  = strtolower((string) ($payload['status'] ?? ''));

            if (in_array($status, ['success', 'failure', 'usercancel'], true)) {
                $easepayTxnId = $payload['easepayid'] ?? $txnid;
                $this->easepayService->handleOrderWebhook($status, $payload, $txnid, $easepayTxnId);
                // On success, handleOrderWebhook() supersedes the txnid-keyed
                // placeholder with a row keyed by easepayTxnId — look that up
                // instead so the order (if created) is found.
                $lookupTransactionId = $status === 'success' ? $easepayTxnId : $txnid;
            }
        }

        $orderInfo = ['order_id' => null, 'order_number' => null, 'order_slug' => null];
        $transaction = OrderPaymentTransaction::where('transaction_id', $lookupTransactionId)->first();

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

        return ApiResponseType::sendJsonResponse(
            $result['success'],
            $result['message'],
            array_merge($result['data'] ?? [], $orderInfo)
        );
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
