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
use App\Models\Wallet;
use App\Models\WalletTransaction;
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
        $orderId = (int)($payload['udf2'] ?? 0);
        $order   = $orderId ? Order::find($orderId) : null;

        if ($status === 'success') {
            // Idempotency guard: webhook, client-side verify-payment, and the
            // reconciliation job can all land for the same transaction. Skip
            // re-processing an already captured order to avoid duplicate
            // status-change side effects.
            if ($order && $order->payment_status === PaymentStatusEnum::COMPLETED()) {
                Log::info('[Easepay] Order already captured, skipping duplicate processing', ['order_id' => $orderId, 'txnid' => $txnid]);
                return;
            }

            // Record or update the transaction
            $transaction = OrderPaymentTransaction::updateOrCreate(
                ['transaction_id' => $easepayTxnId],
                [
                    // uuid has a unique DB constraint and no default/auto-generation
                    // on the model — omitting it defaults to '', which collides with
                    // whichever row claimed '' first and silently rolls back this
                    // entire webhook (including the Order::capturePayment() below).
                    'uuid'           => Str::uuid()->toString(),
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

            $webhookLog?->update([
                'order_payment_transaction_id' => $transaction->id,
                'order_id' => $orderId ?: null,
            ]);

            // createOrder() records a placeholder row at initiation time (keyed by
            // our internal txnid) so a stuck payment has something to reconcile
            // against. Once the real row above (keyed by Easebuzz's own id) exists,
            // that placeholder is superseded — remove it so the Payment Monitor
            // doesn't show two rows for one payment.
            if ($txnid !== $easepayTxnId) {
                OrderPaymentTransaction::where('transaction_id', $txnid)
                    ->where('id', '!=', $transaction->id)
                    ->delete();
            }

            if ($orderId) {
                Order::capturePayment($orderId);
                OrderItem::capturePayment($orderId);
            }

            Log::info('[Easepay] Order payment captured', ['order_id' => $orderId, 'txnid' => $txnid]);

        } elseif ($status === 'failure' || $status === 'usercancel') {
            // Same idempotency guard as above — paymentFailed() refunds any wallet
            // balance used, so re-running it on a duplicate delivery would double-refund.
            if ($order && $order->payment_status === PaymentStatusEnum::FAILED()) {
                Log::info('[Easepay] Order already marked failed, skipping duplicate processing', ['order_id' => $orderId, 'txnid' => $txnid]);
                return;
            }

            $transaction = OrderPaymentTransaction::where('transaction_id', $txnid)->first();
            if ($transaction) {
                $transaction->update([
                    'payment_status' => PaymentStatusEnum::FAILED(),
                    'message'        => 'Payment ' . ucfirst($status),
                    'payment_details' => $payload,
                ]);
            }

            $webhookLog?->update([
                'order_payment_transaction_id' => $transaction?->id,
                'order_id' => $orderId ?: $transaction?->order_id,
            ]);

            if ($orderId) {
                Order::paymentFailed($orderId);
                OrderItem::paymentFailed($orderId);
            }
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
