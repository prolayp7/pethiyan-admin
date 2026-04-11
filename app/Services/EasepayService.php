<?php

namespace App\Services;

use App\Enums\Payment\PaymentModeEnum;
use App\Enums\SettingTypeEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function __construct(SettingService $settingService)
    {
        $setting = $settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());
        $value   = $setting?->value ?? [];

        $this->merchantKey  = $value['easepayMerchantKey']  ?? '';
        $this->merchantSalt = $value['easepayMerchantSalt'] ?? '';
        $this->mode         = $value['easepayPaymentMode']  ?? PaymentModeEnum::Test->value;

        $this->baseUrl = $this->mode === PaymentModeEnum::Live->value
            ? 'https://pay.easebuzz.in'
            : 'https://testpay.easebuzz.in';
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
            // Easebuzz requires both success and failure return URLs.
            'surl'        => $data['surl'] ?? rtrim(config('app.frontendUrl', config('app.url')), '/') . '/checkout?payment_status=success',
            'furl'        => $data['furl'] ?? rtrim(config('app.frontendUrl', config('app.url')), '/') . '/checkout?payment_status=failed',
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
            $response = Http::asForm()->post("{$this->baseUrl}/payment/transaction/v2", [
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
}
