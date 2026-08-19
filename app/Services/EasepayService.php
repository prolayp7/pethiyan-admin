<?php

namespace App\Services;

use App\Enums\Payment\PaymentModeEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SettingTypeEnum;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhookLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Easebuzz (Easepay) payment gateway service.
 *
 * API reference: https://easebuzz.in/developers
 * Test endpoint : https://testpay.easebuzz.in
 * Live endpoint : https://pay.easebuzz.in
 */
class EasepayService
{
    protected string $merchantKey;
    protected string $merchantSalt;
    protected string $mode;        // 'test' | 'live'
    protected string $baseUrl;
    // Transaction/refund/status APIs live on a separate "dashboard" subdomain
    // from the pay/initiateLink APIs — see Easebuzz's own Node.js reference kit
    // (easebuzz-lib/utils.js fetchBaseUrl()). Mixing them up returns a 404-ish
    // non-JSON response, which verifyTransaction() surfaces as a generic
    // "Verification failed." with no real error_desc from Easebuzz.
    protected string $dashboardBaseUrl;

    public function __construct(SettingService $settingService)
    {
        $setting = $settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
        $value   = $setting?->value ?? [];

        $this->merchantKey  = trim($value['easepayMerchantKey']  ?? '');
        $this->merchantSalt = trim($value['easepayMerchantSalt'] ?? '');
        $this->mode         = trim($value['easepayPaymentMode']  ?? PaymentModeEnum::Test->value);

        $this->baseUrl = $this->mode === PaymentModeEnum::Live->value
            ? 'https://pay.easebuzz.in'
            : 'https://testpay.easebuzz.in';

        $this->dashboardBaseUrl = $this->mode === PaymentModeEnum::Live->value
            ? 'https://dashboard.easebuzz.in'
            : 'https://testdashboard.easebuzz.in';

    }

    // -------------------------------------------------------------------------
    // Hash helpers
    // -------------------------------------------------------------------------

    /**
     * Generate initiate-payment hash.
     *
     * Hash = SHA512 of key|txnid|amount|productinfo|firstname|email|udf1|...|udf10|salt
     */
    public function generateInitiateHash(array $params): string
    {
        $hashStr = implode('|', [
            $this->merchantKey,
            $params['txnid'],
            $params['amount'],
            $params['productinfo'],
            $params['firstname'],
            $params['email'],
            $params['udf1'] ?? '',
            $params['udf2'] ?? '',
            $params['udf3'] ?? '',
            $params['udf4'] ?? '',
            $params['udf5'] ?? '',
            '',  // udf6-10 empty
            '', '', '', '',
            $this->merchantSalt,
        ]);
        return strtolower(hash('sha512', $hashStr));
    }

    /**
     * Verify response hash from Easebuzz callback/webhook.
     *
     * Official reverse hash formula:
     * SHA512( salt|status|udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key )
     */
    public function verifyResponseHash(array $response): bool
    {
        $expectedHash = strtolower(hash('sha512', implode('|', [
            $this->merchantSalt,
            $response['status']      ?? '',
            $response['udf10']       ?? '',
            $response['udf9']        ?? '',
            $response['udf8']        ?? '',
            $response['udf7']        ?? '',
            $response['udf6']        ?? '',
            $response['udf5']        ?? '',
            $response['udf4']        ?? '',
            $response['udf3']        ?? '',
            $response['udf2']        ?? '',
            $response['udf1']        ?? '',
            $response['email']       ?? '',
            $response['firstname']   ?? '',
            $response['productinfo'] ?? '',
            $response['amount']      ?? '',
            $response['txnid']       ?? '',
            $this->merchantKey,
        ])));

        return hash_equals($expectedHash, strtolower($response['hash'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // Initiate payment
    // -------------------------------------------------------------------------

    /**
     * Initiate a payment and return the access key + form URL for redirect.
     *
     * @param  array  $data  {txnid, amount, productinfo, firstname, email, phone, udf1...}
     * @return array  {success, access_key, payment_url, data}
     */
    public function initiatePayment(array $data): array
    {
        if (empty($this->merchantKey) || empty($this->merchantSalt)) {
            return ['success' => false, 'message' => 'Easepay credentials not configured.', 'data' => []];
        }

        $params = array_merge([
            'key'         => $this->merchantKey,
            'txnid'       => $data['txnid'],
            'amount'      => number_format((float)$data['amount'], 2, '.', ''),
            'productinfo' => $data['productinfo'] ?? 'Order Payment',
            'firstname'   => $data['firstname']   ?? '',
            'email'       => $data['email']        ?? '',
            'phone'       => $data['phone']        ?? '',
            // Easebuzz requires both success and failure return URLs, and posts
            // the transaction result (txnid, status, hash, ...) to them as a form
            // body — they must be a POST-capable endpoint, not a plain frontend
            // page route. The storefront's /api/easepay/callback receives that
            // POST, forwards it to our webhook, and redirects the browser to the
            // storefront checkout page with a clean query string.
            'surl'        => $data['surl'] ?? rtrim(config('app.frontend_url'), '/') . '/api/easepay/callback',
            'furl'        => $data['furl'] ?? rtrim(config('app.frontend_url'), '/') . '/api/easepay/callback',
            'udf1'        => $data['udf1']         ?? '',
            'udf2'        => $data['udf2']         ?? '',
            'udf3'        => $data['udf3']         ?? '',
            'udf4'        => $data['udf4']         ?? '',
            'udf5'        => $data['udf5']         ?? '',
        ], $data);

        $params['hash'] = $this->generateInitiateHash($params);

        try {
            $response = Http::asForm()
                ->post("{$this->baseUrl}/payment/initiateLink", $params);

            $json = $response->json();

            if (!$response->successful() || ($json['status'] ?? 0) != 1) {
                Log::error('[Easepay] initiatePayment failed', ['response' => $json]);
                return [
                    'success' => false,
                    'message' => $json['error_desc'] ?? 'Failed to initiate payment.',
                    'data'    => $json,
                ];
            }

            return [
                'success'     => true,
                'message'     => 'Payment initiated successfully.',
                'access_key'  => $json['data'] ?? '',
                'payment_url' => "{$this->baseUrl}/pay/{$json['data']}",
                'data'        => $json,
            ];

        } catch (\Throwable $e) {
            Log::error('[Easepay] initiatePayment exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    // -------------------------------------------------------------------------
    // Verify payment (server-to-server)
    // -------------------------------------------------------------------------

    /**
     * Verify a transaction via Easepay's Transaction API.
     */
    public function verifyTransaction(string $txnid): array
    {
        $hash = strtolower(hash('sha512', "{$this->merchantKey}|{$txnid}|{$this->merchantSalt}"));

        try {
            $response = Http::asForm()->post("{$this->dashboardBaseUrl}/transaction/v2/retrieve", [
                'key'    => $this->merchantKey,
                'txnid'  => $txnid,
                'hash'   => $hash,
            ]);

            $json = $response->json();

            if (!$response->successful() || ($json['status'] ?? 0) != 1) {
                return ['success' => false, 'message' => $json['error_desc'] ?? 'Verification failed.', 'data' => $json];
            }

            return ['success' => true, 'message' => 'Transaction verified.', 'data' => $json['data'] ?? $json];

        } catch (\Throwable $e) {
            Log::error('[Easepay] verifyTransaction exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    // -------------------------------------------------------------------------
    // Refund
    // -------------------------------------------------------------------------

    /**
     * Process a refund for a captured transaction.
     *
     * @param  string      $txnid   Original transaction ID
     * @param  float|null  $amount  Partial refund amount (null = full refund)
     */
    public function refundPayment(string $txnid, ?float $amount = null): array
    {
        $refundAmount = $amount !== null ? number_format($amount, 2, '.', '') : null;
        $hash = strtolower(hash('sha512', "{$this->merchantKey}|{$txnid}|{$this->merchantSalt}"));

        $payload = [
            'merchant_key' => $this->merchantKey,
            'txnid'        => $txnid,
            'hash'         => $hash,
        ];
        if ($refundAmount !== null) {
            $payload['refund_amount'] = $refundAmount;
        }

        try {
            $response = Http::asForm()->post("{$this->baseUrl}/payment/refund/v2", $payload);
            $json     = $response->json();

            if (!$response->successful() || ($json['status'] ?? 0) != 1) {
                return ['success' => false, 'message' => $json['error_desc'] ?? 'Refund failed.', 'data' => $json];
            }

            return ['success' => true, 'message' => 'Refund initiated successfully.', 'data' => $json];

        } catch (\Throwable $e) {
            Log::error('[Easepay] refundPayment exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public function getMode(): string   { return $this->mode; }
    public function getBaseUrl(): string { return $this->baseUrl; }

    // -------------------------------------------------------------------------
    // Shared transaction-outcome handling
    //
    // Used by EasepayController (webhook + client-side verify-payment) and by
    // the ReconcilePendingEasepayPayments command, so a payment gets captured
    // the same way regardless of which of those three paths detects it first.
    // -------------------------------------------------------------------------

    public function handleOrderWebhook(string $status, array $payload, string $txnid, string $easepayTxnId, ?PaymentWebhookLog $webhookLog = null): void
    {
        // The placeholder row created at initiate time (EasepayController::createOrder)
        // is always keyed by OUR txnid — that's the only stable link back to the
        // validated checkout payload before an Order exists. udf2 used to carry a
        // pre-existing order_id; there is no order yet at initiate time anymore,
        // so it's unused for this (order_payment) branch now.
        $placeholder = OrderPaymentTransaction::where('transaction_id', $txnid)->first();

        if ($status === 'success') {
            // Idempotency guard: webhook, client-side verify-payment, and the
            // reconciliation job can all land for the same transaction. The
            // txnid-keyed placeholder gets deleted once superseded by the
            // easepayTxnId-keyed row created further down (see below) — so a
            // second call for the same payment (webhook + verify-payment
            // racing, by design — see EasepayController) won't find it by
            // txnid anymore. Check the superseding row too before concluding
            // this is a genuine "never seen this payment" case.
            $existing = $placeholder ?: OrderPaymentTransaction::where('transaction_id', $easepayTxnId)->first();

            if ($existing && $existing->order_id) {
                $existingOrder = Order::find($existing->order_id);
                if ($existingOrder && $existingOrder->payment_status === PaymentStatusEnum::COMPLETED()) {
                    Log::info('[Easepay] Order already captured, skipping duplicate processing', ['order_id' => $existing->order_id, 'txnid' => $txnid]);
                    return;
                }
            }

            if (!$placeholder || !$placeholder->checkout_payload) {
                if ($existing) {
                    // Found via easepayTxnId but order_id wasn't set/completed
                    // above — already processed or currently being processed by
                    // the other caller in the webhook/verify-payment race, not a
                    // genuine gap.
                    Log::info('[Easepay] Already processed for this transaction, skipping', ['txnid' => $txnid, 'easepayTxnId' => $easepayTxnId]);
                } else {
                    Log::error('[Easepay] Payment succeeded but no checkout payload found — cannot create order', ['txnid' => $txnid]);
                }
                return;
            }

            // Security: verify what Easebuzz actually reports as paid matches what
            // we expected to be paid (computed server-side and stored on the
            // placeholder at initiate time — see EasepayController::createOrder()).
            // This should never mismatch in normal operation (Easebuzz reports
            // exactly what was charged), so treat any mismatch as an anomaly worth
            // alerting on and refuse to create the order rather than trusting a
            // client-influenced payload amount.
            $expectedAmount = (float) $placeholder->amount;
            $actualAmount   = (float) ($payload['amount'] ?? 0);

            if (abs($expectedAmount - $actualAmount) > 1.0) {
                // Easebuzz reported status=success, so money genuinely moved —
                // just not the expected amount. This is COMPLETED (captured),
                // not FAILED, so it stays visible for manual follow-up (the
                // Payment Monitor, and NOT silently excluded by
                // ReconcilePendingEasepayPayments's PENDING-only query) —
                // same reasoning as the "order creation failed" branch below.
                $placeholder->update([
                    'payment_status' => PaymentStatusEnum::COMPLETED(),
                    'message'        => sprintf(
                        'Payment captured but amount does not match expected total (expected %.2f, received %.2f) — needs manual review',
                        $expectedAmount,
                        $actualAmount
                    ),
                    'payment_details' => $payload,
                ]);
                Log::error('[Easepay] Payment amount mismatch — refusing to create order', [
                    'txnid'    => $txnid,
                    'expected' => $expectedAmount,
                    'actual'   => $actualAmount,
                ]);

                $webhookLog?->update([
                    'order_payment_transaction_id' => $placeholder->id,
                ]);

                return;
            }

            $user = User::find($placeholder->user_id);
            if (!$user) {
                Log::error('[Easepay] Payment succeeded but user no longer exists', ['txnid' => $txnid, 'user_id' => $placeholder->user_id]);
                return;
            }

            // OrderService::createOrder() opens its own DB transaction but only
            // rolls it back inside its catch blocks — every early `return` on
            // validation failure leaves that transaction open, and it can also
            // throw outright (e.g. its own catch-block failure handler reading
            // a key our checkout_payload never has). Either way, without this
            // guard the "payment captured but order creation failed" bookkeeping
            // update just below would execute inside that still-open,
            // uncommitted transaction and get silently rolled back at the end
            // of the request — exactly the silent-money-loss this method exists
            // to prevent. Catch any throw and roll back any leaked transaction
            // level before touching the placeholder.
            $transactionLevelBefore = DB::transactionLevel();
            try {
                $orderResult = app(OrderService::class)->createOrder($user, $placeholder->checkout_payload);
            } catch (\Throwable $e) {
                Log::error('[Easepay] OrderService::createOrder() threw', [
                    'txnid' => $txnid,
                    'error' => $e->getMessage(),
                ]);
                $orderResult = ['success' => false, 'message' => $e->getMessage()];
            }
            while (DB::transactionLevel() > $transactionLevelBefore) {
                DB::rollBack();
            }

            if (!$orderResult['success']) {
                // Money has already been captured by Easebuzz at this point, but the
                // order couldn't be created (e.g. cart or address changed between
                // initiation and confirmation). Do NOT lose this — mark the payment
                // itself completed (it was) but leave order_id null, and alert an
                // admin so it gets resolved manually (refund or manual order).
                $placeholder->update([
                    'payment_status' => PaymentStatusEnum::COMPLETED(),
                    'message'        => 'Payment captured but order creation failed: ' . ($orderResult['message'] ?? 'unknown error'),
                    'payment_details' => $payload,
                ]);
                Log::error('[Easepay] Payment succeeded but order creation failed', [
                    'txnid' => $txnid,
                    'user_id' => $user->id,
                    'reason' => $orderResult['message'] ?? null,
                ]);
                return;
            }

            $order = $orderResult['data'];

            // Record or update the transaction, keyed by Easebuzz's own id
            $transaction = OrderPaymentTransaction::updateOrCreate(
                ['transaction_id' => $easepayTxnId],
                [
                    'uuid'           => Str::uuid()->toString(),
                    'order_id'       => $order->id,
                    'user_id'        => $user->id,
                    'transaction_id' => $easepayTxnId,
                    'amount'         => $payload['amount'] ?? 0,
                    'currency'       => $payload['currency'] ?? 'INR',
                    'payment_method' => PaymentTypeEnum::EASEPAY(),
                    'payment_status' => PaymentStatusEnum::COMPLETED(),
                    'message'        => 'Payment Successful',
                    'payment_details' => $payload,
                ]
            );

            $webhookLog?->update([
                'order_payment_transaction_id' => $transaction->id,
                'order_id' => $order->id,
            ]);

            // Supersede the initiate-time placeholder now that the real,
            // order-linked row above exists — same cleanup as before, just
            // keyed by our txnid instead of assuming it differs from easepayTxnId.
            if ($txnid !== $easepayTxnId) {
                OrderPaymentTransaction::where('transaction_id', $txnid)
                    ->where('id', '!=', $transaction->id)
                    ->delete();
            }

            Order::capturePayment($order->id);
            OrderItem::capturePayment($order->id);

            Log::info('[Easepay] Order created and payment captured', ['order_id' => $order->id, 'txnid' => $txnid]);

        } elseif ($status === 'failure' || $status === 'usercancel') {
            // No order exists yet in this flow, so there's nothing to roll back
            // (no stock reserved, no wallet deducted, no promo usage counted) —
            // just record the outcome on the placeholder for the Payment Monitor.
            if (!$placeholder) {
                Log::info('[Easepay] Failure/usercancel webhook for unknown txnid', ['txnid' => $txnid]);
                return;
            }

            if ($placeholder->payment_status === PaymentStatusEnum::FAILED()) {
                Log::info('[Easepay] Already marked failed, skipping duplicate processing', ['txnid' => $txnid]);
                return;
            }

            $placeholder->update([
                'payment_status' => PaymentStatusEnum::FAILED(),
                'message'        => 'Payment ' . ucfirst($status),
                'payment_details' => $payload,
            ]);

            $webhookLog?->update([
                'order_payment_transaction_id' => $placeholder->id,
            ]);
        }
    }

    public function handleWalletWebhook(string $status, array $payload, string $easepayTxnId): void
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
    public function handleRefundWebhook(string $status, array $payload, string $txnid, string $easepayRefundId, ?PaymentWebhookLog $webhookLog = null): void
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

        $webhookLog?->update([
            'order_payment_transaction_id' => $transaction?->id,
            'order_id' => $transaction?->order_id,
        ]);

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
