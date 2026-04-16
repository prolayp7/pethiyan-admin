<?php

namespace App\Http\Controllers\Payments;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTransaction;
use App\Models\PaymentDispute;
use App\Models\PaymentRefund;
use App\Models\PaymentSettlement;
use App\Models\PaymentWebhookLog;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    private Api $razorpayApi;
    private string $keyId;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct(SettingService $settingService)
    {
        $setting = $settingService->getSettingByVariable(SettingTypeEnum::PAYMENT());

        $this->keyId = $setting->value['razorpayKeyId'] ?? "";
        $this->secretKey = $setting->value['razorpaySecretKey'] ?? "";
        $this->webhookSecret = $setting->value['razorpayWebhookSecret'] ?? "";

        $this->razorpayApi = new Api($this->keyId, $this->secretKey);
    }

    /**
     * Handle Razorpay Webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $headers = $request->headers->all();
        $webhookLog = null;

        Log::info("Razorpay Webhook Payload: " . $payload);

        DB::beginTransaction();
        try {
            $data = json_decode($payload, true) ?: [];
            $event = $data['event'] ?? null;

            $webhookLog = PaymentWebhookLog::create([
                'gateway' => PaymentTypeEnum::RAZORPAY(),
                'event_name' => $event,
                'delivery_id' => $request->header('X-Razorpay-Event-Id'),
                'status' => 'received',
                'signature_valid' => false,
                'http_status' => 102,
                'request_headers' => $headers,
                'raw_payload' => $data,
            ]);

            if (!$this->isValidSignature($payload, $signature)) {
                Log::error("Invalid Razorpay Webhook signature.");
                $webhookLog->update([
                    'status' => 'rejected',
                    'http_status' => 400,
                    'message' => 'Invalid signature',
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $webhookLog->update([
                'signature_valid' => true,
                'status' => 'processing',
            ]);

            // Dispute events have a different payload structure
            if (str_starts_with($event, 'payment.dispute.')) {
                $disputeEntity = $data['payload']['dispute']['entity'] ?? [];
                $this->handleDisputeEvent($event, $disputeEntity, $data['payload'], $webhookLog);
                $webhookLog->update([
                    'status' => 'processed',
                    'http_status' => 200,
                    'processed_at' => now(),
                ]);
                DB::commit();
                return response()->json(['status' => 'success'], 200);
            }

            // Refund lifecycle events
            if (in_array($event, ['refund.created', 'refund.failed'])) {
                $refundEntity = $data['payload']['refund']['entity'] ?? [];
                $this->handleRefundLifecycleEvent($event, $refundEntity, $data['payload'], $webhookLog);
                $webhookLog->update([
                    'status' => 'processed',
                    'http_status' => 200,
                    'processed_at' => now(),
                ]);
                DB::commit();
                return response()->json(['status' => 'success'], 200);
            }

            if (str_starts_with((string) $event, 'settlement.')) {
                $settlementEntity = $data['payload']['settlement']['entity'] ?? [];
                $this->handleSettlementEvent($event, $settlementEntity, $data['payload'], $webhookLog);
                $webhookLog->update([
                    'status' => 'processed',
                    'http_status' => 200,
                    'processed_at' => now(),
                ]);
                DB::commit();
                return response()->json(['status' => 'success'], 200);
            }

            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $paymentType = $paymentEntity['notes']['type'] ?? 'order_payment';

            // refund.processed still goes through the existing payment-entity path
            if ($event === 'refund.processed') {
                $refundEntity = $data['payload']['refund']['entity'] ?? [];
                $this->handleRefundLifecycleEvent($event, $refundEntity, $data['payload'], $webhookLog);
                $transaction = $this->findTransaction($paymentType, $paymentEntity);
                $this->handleRefund($paymentType, $transaction, $paymentEntity);
                $webhookLog->update([
                    'order_payment_transaction_id' => $transaction?->id,
                    'order_id' => $transaction?->order_id,
                    'status' => 'processed',
                    'http_status' => 200,
                    'processed_at' => now(),
                ]);
                DB::commit();
                return response()->json(['status' => 'success'], 200);
            }

            $transaction = $this->findTransaction($paymentType, $paymentEntity);

            $webhookLog->update([
                'order_payment_transaction_id' => $transaction?->id,
                'order_id' => $transaction?->order_id,
            ]);

            $this->processEvent($event, $paymentType, $paymentEntity, $transaction);

            $webhookLog->update([
                'status' => 'processed',
                'http_status' => 200,
                'processed_at' => now(),
            ]);

            DB::commit();
            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Razorpay Webhook Error: " . $e->getMessage());
            $webhookLog?->update([
                'status' => 'failed',
                'http_status' => 500,
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    /**
     * Create a new Razorpay order
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $input = $request->validate([
                'amount'   => 'required|numeric|min:1', // amount in paise
                'currency' => 'nullable|string|in:INR',
                'receipt'  => 'nullable|string',
            ]);

            $order = $this->razorpayApi->order->create([
                'amount'          => (int) $input['amount'], // already in paise
                'currency'        => $input['currency'] ?? 'INR',
                'receipt'         => $input['receipt'] ?? ('rcpt_' . auth()->id() . '_' . time()),
                'payment_capture' => 1,
                'notes'           => ['user_id' => auth()->id()],
            ]);

            $orderData = $order->toArray();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Razorpay Order created successfully',
                data: [
                    'razorpay_order_id' => $orderData['id'],
                    'amount'            => $orderData['amount'],
                    'currency'          => $orderData['currency'],
                    'key'               => $this->keyId,
                ]
            );

        } catch (Exception $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Unable to create order',
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Create a Razorpay order for wallet recharge
     */
    public function createWalletRechargeOrder(array $data): array
    {
        try {
            // Validate input manually
            $validated = validator($data, [
                'amount' => 'required|numeric|min:1',
                'currency' => 'nullable|string|in:INR',
                'description' => 'nullable|string',
                'transaction_id' => 'required|string',
            ])->validate();

            $order = $this->razorpayApi->order->create([
                'amount' => (int)$validated['amount'] * 100, // Convert to paisa
                'currency' => $validated['currency'] ?? 'INR',
                'receipt' => $validated['description'] ?? "Wallet Recharge",
                'payment_capture' => 1,
                'notes' => [
                    'user_id' => auth()->id(),
                    'type' => 'wallet_recharge', // Specify wallet recharge
                    'transaction_id' => $validated['transaction_id'],
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Razorpay Wallet Recharge Order created successfully',
                'data' => $order->toArray()
            ];

        } catch (Exception $e) {
            Log::error('Razorpay wallet recharge order creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to create wallet recharge order',
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * HTTP endpoint: verify Razorpay payment signature
     */
    public function verifyPaymentHttp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $result = $this->verifyPayment([
            'transaction_id'     => $data['razorpay_payment_id'],
            'razorpay_order_id'  => $data['razorpay_order_id'],
            'razorpay_signature' => $data['razorpay_signature'],
        ]);

        return ApiResponseType::sendJsonResponse(
            success: $result['success'],
            message: $result['message'],
            data: $result['data'] ?? []
        );
    }

    /**
     * Verify Razorpay payment signature (internal use)
     */
    public function verifyPayment(array $data): array
    {
        try {
            $razorpayOrderId = $data['razorpay_order_id'];
            $razorpayPaymentId = $data['transaction_id'];
            $razorpaySignature = $data['razorpay_signature'];

            $expectedSignature = hash_hmac(
                'sha256',
                $razorpayOrderId . '|' . $razorpayPaymentId,
                $this->secretKey
            );

            if ($expectedSignature === $razorpaySignature) {
                return [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => []
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid razorpay signature',
                'data' => []
            ];

        } catch (Exception $e) {
            Log::error('Razorpay payment verification failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment verification failed',
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Fetch payment details by payment ID
     */
    public function getPaymentDetails(string $paymentId): JsonResponse
    {
        try {
            $payment = $this->razorpayApi->payment->fetch($paymentId);
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Payment details fetched successfully',
                data: $payment
            );
        } catch (Exception $e) {
            Log::error('Razorpay fetch payment failed: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Unable to fetch payment details',
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment($paymentId, $amount = null): array
    {
        try {

            $payment = $this->razorpayApi->payment->fetch($paymentId);

            $refundData = [];
            if (isset($amount)) {
                $refundData['amount'] = $amount * 100;
            }

            $refund = $payment->refund($refundData);

            return [
                "success" => true,
                "message" => 'Refund processed successfully',
                "data" => $refund,
            ];

        } catch (Exception $e) {
            Log::error('Razorpay refund failed: ' . $e->getMessage());
            return [
                "success" => false,
                "message" => 'Refund failed: ' . $e->getMessage(),
                "data" => ['error' => $e->getMessage()],
            ];
        }
    }


    private function isValidSignature(string $payload, ?string $signature): bool
    {
        if ($signature === null) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }


    private function processEvent(string $event, string $paymentType, array $paymentEntity, $transaction = null): void
    {
        switch ($event) {
            case 'payment.authorized':
                Log::info('Payment authorized', ['payment_id' => $paymentEntity['id']]);
                break;

            case 'payment.captured':
                $this->handlePaymentCaptured(
                    paymentId: $paymentEntity['id'],
                    paymentEntity: $paymentEntity,
                    userId: $paymentEntity['notes']['user_id'] ?? null,
                    paymentType: $paymentType,
                    transaction: $transaction
                );
                Log::info('Payment captured', ['payment_id' => $paymentEntity['id']]);
                break;

            case 'order.paid':
                $this->handleOrderPaid(
                    event: $event,
                    paymentType: $paymentType,
                    transaction: $transaction
                );
                break;

            case 'payment.failed':
                $this->handlePaymentFailed($paymentType, $paymentEntity, $transaction, $event);
                break;

            default:
                Log::warning("Unhandled Razorpay Webhook Event: {$event}");
                break;
        }
    }

    private function findTransaction(string $paymentType, array $paymentEntity)
    {
        if ($paymentType === 'order_payment') {
            $transactionId = $paymentEntity['id'] ?? '';
            return OrderPaymentTransaction::where('transaction_id', $transactionId)->first();
        }

        if ($paymentType === 'wallet_recharge') {
            $transactionId = $paymentEntity['notes']['transaction_id'] ?? '';
            $transaction = WalletTransaction::find($transactionId);

            if (!$transaction) {
                Log::warning("Wallet Transaction not found for ID: {$transactionId}");
                throw new Exception('Wallet Transaction not found');
            }

            return $transaction;
        }

        return null;
    }

    private function handleOrderPaid($event, $paymentType, $transaction): void
    {
        if ($transaction !== null) {
            if ($paymentType === 'wallet_recharge') {
                $result = Wallet::captureRecharge($transaction->id);
                if (!$result['success']) {
                    Log::error("Wallet Recharge Failed: " . $result['message']);
                    return;
                }
                Log::info("Wallet Recharge Completed: {$event}");

            } elseif ($paymentType === 'order_payment') {
                $transaction->update([
                    'payment_status' => PaymentStatusEnum::COMPLETED(),
                    'message' => $event
                ]);
                if ($transaction->order_id === null) {
                    Log::warning("Order ID is null for transaction: {$transaction->id}");
                    return;
                }
                Order::capturePayment($transaction->order_id);
                OrderItem::capturePayment($transaction->order_id);
                Log::info("Order Updated And Ready to Go: {$event}");
            }
        }
    }

    private function handleRefund($paymentType, $transaction, $data = null): void
    {
        if ($paymentType === 'wallet_recharge') {
            Wallet::captureRefund($transaction->id);
            Log::info('event refund.processed Wallet Refund Processed', ['payment_id' => $transaction->transaction_reference ?? null]);
            return;
        }
        $transaction->update([
            'payment_status' => PaymentStatusEnum::REFUNDED(),
            'message' => "Payment Refunded",
            'payment_details' => $data
        ]);
        Log::info('event refund.processed Payment Refunded', ['payment_id' => $transaction->transaction_id ?? null]);
    }

    private function handlePaymentCaptured($paymentId, $paymentEntity, $userId, $paymentType, $transaction = null): void
    {
        if ($paymentType === 'wallet_recharge') {
            $transaction->update([
                'transaction_reference' => $paymentId,
                'amount' => $paymentEntity['amount'] / 100,
                'currency_code' => $paymentEntity['currency'],
                'description' => 'Wallet Recharge Payment Captured'
            ]);
            return;
        }
        $paymentEntity['order_id'] = $transaction->order_id ?? null;
        $paymentEntity['user_id'] = $userId;
        // Save payment transaction with no order yet
        OrderPaymentTransaction::saveTransaction(data: $paymentEntity, paymentId: $paymentId, paymentMethod: PaymentTypeEnum::RAZORPAY(), paymentStatus: PaymentStatusEnum::COMPLETED());
    }

    private function handleDisputeEvent(string $event, array $disputeEntity, array $fullPayload, ?PaymentWebhookLog $webhookLog = null): void
    {
        $disputeId  = $disputeEntity['id'] ?? null;
        $paymentId  = $disputeEntity['payment_id'] ?? ($fullPayload['payment']['entity']['id'] ?? null);

        if (!$disputeId) {
            Log::warning("Razorpay dispute event missing dispute ID", ['event' => $event]);
            return;
        }

        // Map event to status
        $statusMap = [
            'payment.dispute.created'         => 'created',
            'payment.dispute.under_review'    => 'under_review',
            'payment.dispute.won'             => 'won',
            'payment.dispute.lost'            => 'lost',
            'payment.dispute.closed'          => 'closed',
            'payment.dispute.action_required' => 'action_required',
        ];
        $status = $statusMap[$event] ?? 'created';

        // Find linked transaction by payment ID
        $transaction = $paymentId
            ? OrderPaymentTransaction::where('transaction_id', $paymentId)->first()
            : null;

        PaymentDispute::updateOrCreate(
            ['razorpay_dispute_id' => $disputeId],
            [
                'razorpay_payment_id'          => $paymentId,
                'order_payment_transaction_id' => $transaction?->id,
                'order_id'                     => $transaction?->order_id,
                'amount'                       => ($disputeEntity['amount'] ?? 0) / 100,
                'currency'                     => $disputeEntity['currency'] ?? 'INR',
                'status'                       => $status,
                'reason_code'                  => $disputeEntity['reason_code'] ?? null,
                'reason_description'           => $disputeEntity['reason_description'] ?? null,
                'respond_by'                   => isset($disputeEntity['respond_by'])
                    ? \Carbon\Carbon::createFromTimestamp($disputeEntity['respond_by'])
                    : null,
                'raw_payload'                  => $fullPayload,
            ]
        );

        $webhookLog?->update([
            'order_payment_transaction_id' => $transaction?->id,
            'order_id' => $transaction?->order_id,
        ]);

        Log::info("Razorpay dispute [{$status}]", [
            'dispute_id' => $disputeId,
            'payment_id' => $paymentId,
            'order_id'   => $transaction?->order_id,
        ]);
    }

    private function handleRefundLifecycleEvent(string $event, array $refundEntity, array $fullPayload, ?PaymentWebhookLog $webhookLog = null): void
    {
        $refundId  = $refundEntity['id'] ?? null;
        $paymentId = $refundEntity['payment_id'] ?? null;

        if (!$refundId) {
            Log::warning("Razorpay refund event missing refund ID", ['event' => $event]);
            return;
        }

        $statusMap = [
            'refund.created'   => 'created',
            'refund.processed' => 'processed',
            'refund.failed'    => 'failed',
        ];
        $status = $statusMap[$event] ?? 'created';

        $transaction = $paymentId
            ? OrderPaymentTransaction::where('transaction_id', $paymentId)->first()
            : null;

        PaymentRefund::updateOrCreate(
            ['razorpay_refund_id' => $refundId],
            [
                'razorpay_payment_id'          => $paymentId,
                'order_payment_transaction_id' => $transaction?->id,
                'order_id'                     => $transaction?->order_id,
                'amount'                       => ($refundEntity['amount'] ?? 0) / 100,
                'currency'                     => $refundEntity['currency'] ?? 'INR',
                'status'                       => $status,
                'speed'                        => $refundEntity['speed_processed'] ?? $refundEntity['speed_requested'] ?? null,
                'notes'                        => $refundEntity['notes'] ?? null,
                'raw_payload'                  => $fullPayload,
            ]
        );

        $webhookLog?->update([
            'order_payment_transaction_id' => $transaction?->id,
            'order_id' => $transaction?->order_id,
        ]);

        // On failure, log prominently so it can be investigated
        if ($status === 'failed') {
            Log::error("Razorpay refund FAILED", [
                'refund_id'  => $refundId,
                'payment_id' => $paymentId,
                'order_id'   => $transaction?->order_id,
                'amount'     => ($refundEntity['amount'] ?? 0) / 100,
            ]);
        } else {
            Log::info("Razorpay refund [{$status}]", [
                'refund_id'  => $refundId,
                'payment_id' => $paymentId,
            ]);
        }
    }

    private function handleSettlementEvent(string $event, array $settlementEntity, array $fullPayload, ?PaymentWebhookLog $webhookLog = null): void
    {
        $settlementId = $settlementEntity['id'] ?? null;

        if (!$settlementId) {
            Log::warning('Razorpay settlement event missing settlement ID', ['event' => $event]);
            return;
        }

        $paymentId = $this->extractSettlementPaymentId($settlementEntity, $fullPayload);
        $transaction = $paymentId
            ? OrderPaymentTransaction::where('transaction_id', $paymentId)->first()
            : null;

        $status = $this->deriveSettlementStatus($event, $settlementEntity);
        $settledAt = $this->resolveSettlementTimestamp($settlementEntity);

        PaymentSettlement::updateOrCreate(
            ['razorpay_settlement_id' => $settlementId],
            [
                'razorpay_payment_id' => $paymentId,
                'order_payment_transaction_id' => $transaction?->id,
                'order_id' => $transaction?->order_id,
                'amount' => isset($settlementEntity['amount']) ? (($settlementEntity['amount'] ?? 0) / 100) : 0,
                'currency' => $settlementEntity['currency'] ?? 'INR',
                'status' => $status,
                'event_name' => $event,
                'settlement_reference' => $settlementEntity['entity_id']
                    ?? $settlementEntity['settlement_reference']
                    ?? $settlementEntity['reference_id']
                    ?? null,
                'utr' => $settlementEntity['utr'] ?? null,
                'settled_at' => $settledAt,
                'raw_payload' => $fullPayload,
            ]
        );

        $webhookLog?->update([
            'order_payment_transaction_id' => $transaction?->id,
            'order_id' => $transaction?->order_id,
        ]);

        if ($transaction) {
            $paymentDetails = is_array($transaction->payment_details) ? $transaction->payment_details : [];
            $paymentDetails['settlement'] = [
                'event' => $event,
                'status' => $status,
                'settlement_id' => $settlementId,
                'settlement_reference' => $settlementEntity['entity_id']
                    ?? $settlementEntity['settlement_reference']
                    ?? $settlementEntity['reference_id']
                    ?? null,
                'utr' => $settlementEntity['utr'] ?? null,
                'settled_at' => $settledAt?->toDateTimeString(),
            ];
            $transaction->update(['payment_details' => $paymentDetails]);
        }

        Log::info("Razorpay settlement [{$status}]", [
            'settlement_id' => $settlementId,
            'payment_id' => $paymentId,
            'order_id' => $transaction?->order_id,
        ]);
    }

    private function handlePaymentFailed(string $paymentType, array $paymentEntity, $transaction = null, string $event = ''): void
    {
        if ($paymentType === 'wallet_recharge') {
            if ($transaction === null) {
                return;
            }

            $transaction->update([
                'status' => PaymentStatusEnum::FAILED(),
                'message' => $event,
            ]);
            Log::info('Wallet Recharge Failed', ['payment_id' => $transaction->id]);
        } elseif ($paymentType === 'order_payment') {
            $transaction = $this->resolveOrderPaymentTransactionForWebhook($paymentEntity, $transaction);

            if ($transaction === null) {
                Log::warning('Order payment failed webhook could not be linked to a transaction', [
                    'payment_id' => $paymentEntity['id'] ?? null,
                    'event' => $event,
                ]);
                return;
            }

            $paymentDetails = is_array($transaction->payment_details) ? $transaction->payment_details : [];
            $paymentDetails = array_merge($paymentDetails, [
                'event' => $event,
                'last_webhook_received_at' => now()->toDateTimeString(),
                'failure' => [
                    'error_code' => $paymentEntity['error_code'] ?? null,
                    'error_description' => $paymentEntity['error_description'] ?? null,
                    'error_reason' => $paymentEntity['error_reason'] ?? null,
                    'error_source' => $paymentEntity['error_source'] ?? null,
                    'error_step' => $paymentEntity['error_step'] ?? null,
                    'status' => $paymentEntity['status'] ?? null,
                ],
                'payload' => $paymentEntity,
            ]);

            $transaction->update([
                'payment_status' => PaymentStatusEnum::FAILED(),
                'message' => $this->formatPaymentFailureMessage($paymentEntity, $event),
                'payment_details' => $paymentDetails,
            ]);

            Order::paymentFailed($transaction->order_id);
            OrderItem::paymentFailed($transaction->order_id);
            Log::info('Order Payment Failed', [
                'order_id' => $transaction->order_id,
                'payment_id' => $paymentEntity['id'] ?? null,
                'error_code' => $paymentEntity['error_code'] ?? null,
                'error_reason' => $paymentEntity['error_reason'] ?? null,
            ]);
        }
    }

    private function resolveOrderPaymentTransactionForWebhook(array $paymentEntity, $transaction = null): ?OrderPaymentTransaction
    {
        if ($transaction instanceof OrderPaymentTransaction) {
            return $transaction;
        }

        $paymentId = $paymentEntity['id'] ?? null;
        $userId = $paymentEntity['notes']['user_id'] ?? null;

        if (!$paymentId || !$userId) {
            return null;
        }

        return OrderPaymentTransaction::updateOrCreate(
            ['transaction_id' => $paymentId],
            [
                'uuid' => OrderPaymentTransaction::where('transaction_id', $paymentId)->value('uuid') ?? (string) \Illuminate\Support\Str::uuid(),
                'order_id' => $paymentEntity['notes']['order_id'] ?? null,
                'user_id' => $userId,
                'amount' => isset($paymentEntity['amount']) ? ($paymentEntity['amount'] / 100) : 0,
                'currency' => $paymentEntity['currency'] ?? 'INR',
                'payment_method' => PaymentTypeEnum::RAZORPAY(),
                'payment_status' => PaymentStatusEnum::PENDING(),
                'message' => 'Webhook received before order linkage',
                'payment_details' => $paymentEntity,
            ]
        );
    }

    private function formatPaymentFailureMessage(array $paymentEntity, string $event): string
    {
        $parts = array_filter([
            $paymentEntity['error_description'] ?? null,
            $paymentEntity['error_reason'] ?? null,
            $paymentEntity['error_code'] ?? null,
        ]);

        if (!empty($parts)) {
            return implode(' | ', $parts);
        }

        return $event;
    }

    private function extractSettlementPaymentId(array $settlementEntity, array $fullPayload): ?string
    {
        return $settlementEntity['payment_id']
            ?? data_get($fullPayload, 'payment.entity.id')
            ?? data_get($fullPayload, 'payment.id')
            ?? data_get($fullPayload, 'items.0.entity.payment_id')
            ?? null;
    }

    private function deriveSettlementStatus(string $event, array $settlementEntity): string
    {
        return $settlementEntity['status']
            ?? Str::afterLast($event, '.')
            ?: 'processed';
    }

    private function resolveSettlementTimestamp(array $settlementEntity): ?\Carbon\Carbon
    {
        $timestamp = $settlementEntity['settled_at']
            ?? $settlementEntity['processed_at']
            ?? $settlementEntity['created_at']
            ?? null;

        if (!$timestamp) {
            return null;
        }

        return \Carbon\Carbon::createFromTimestamp((int) $timestamp);
    }
}
