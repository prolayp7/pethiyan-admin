<?php

namespace App\Console\Commands;

use App\Enums\Payment\PaymentTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\OrderPaymentTransaction;
use App\Services\EasepayService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcilePendingEasepayPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easepay:reconcile-pending
        {--minutes=20 : How old a pending payment must be before checking it}
        {--limit=50 : Max payments to check per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-verify stuck/pending Easepay payments against Easebuzz and capture or fail them';

    public function handle(EasepayService $easepayService): int
    {
        $cutoff = Carbon::now()->subMinutes((int) $this->option('minutes'));

        // EasepayController::createOrder() records a placeholder row (transaction_id
        // = our internal txnid, checkout_payload = the validated checkout data) the
        // moment a payment is initiated — no Order exists yet at that point. If the
        // customer's device or network drops before either the webhook or the
        // browser redirect ever arrives, this is the only row that exists — nothing
        // else knows the payment was attempted, so this is the only way to find it
        // later. handleOrderWebhook() (called below via verifyTransaction's result)
        // creates the Order for the first time on a resolved 'success' status.
        $pending = OrderPaymentTransaction::where('payment_method', PaymentTypeEnum::EASEPAY())
            ->where('payment_status', PaymentStatusEnum::PENDING())
            ->where('created_at', '<=', $cutoff)
            ->orderBy('created_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No stuck Easepay payments found.');
            return 0;
        }

        $this->info("Checking {$pending->count()} pending Easepay payment(s) older than {$cutoff->format('Y-m-d H:i:s')}...");

        $resolved = 0;
        $stillPending = 0;
        $errors = 0;

        foreach ($pending as $transaction) {
            try {
                $result = $easepayService->verifyTransaction($transaction->transaction_id);

                if (!$result['success']) {
                    // Easebuzz has no record of this txnid at all (e.g. the payment
                    // page was never even reached) — leave it pending for now, it'll
                    // be checked again next run in case it's just eventually-consistent.
                    $this->line("  ? {$transaction->transaction_id}: {$result['message']}");
                    $stillPending++;
                    continue;
                }

                $payload = $result['data'] ?? [];
                $status  = strtolower((string) ($payload['status'] ?? ''));

                if (!in_array($status, ['success', 'failure', 'usercancel'], true)) {
                    // Unrecognized/in-progress status from Easebuzz — don't guess,
                    // leave it for a human or a future run once the status settles.
                    $this->line("  ? {$transaction->transaction_id}: unrecognized status '{$status}'");
                    $stillPending++;
                    continue;
                }

                $easepayTxnId = $payload['easepayid'] ?? $transaction->transaction_id;
                $easepayService->handleOrderWebhook($status, $payload, $transaction->transaction_id, $easepayTxnId);

                $this->info("  ✓ {$transaction->transaction_id}: reconciled as '{$status}' (order #{$transaction->order_id})");
                $resolved++;
            } catch (\Throwable $e) {
                $this->error("  ✗ {$transaction->transaction_id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("\n=== Easepay Reconciliation Complete ===");
        $this->info("Resolved: {$resolved} | Still pending: {$stillPending} | Errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
