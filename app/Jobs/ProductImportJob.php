<?php

namespace App\Jobs;

use App\Services\FrontendRevalidateService;
use App\Services\ProductCsvImportService;
use App\Services\ProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $jobId,
        public string $storedPath,
        public string $originalName,
        public int $sellerId
    ) {
    }

    public function handle(ProductCsvImportService $importService, ProductService $productService): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'processing',
            'job_id' => $this->jobId,
            'created' => 0,
            'failed' => 0,
            'errors' => [],
            'failed_report_id' => null,
        ], now()->addHours(24));

        $absolutePath = Storage::disk('local')->path($this->storedPath);
        $result = $importService->import($absolutePath, $this->originalName, $this->sellerId, $productService);

        Cache::put($this->cacheKey(), [
            'status' => 'completed',
            'job_id' => $this->jobId,
            'created' => $result['created'] ?? 0,
            'failed' => $result['failed'] ?? 0,
            'errors' => $result['errors'] ?? [],
            'failed_report_id' => $result['failed_report_id'] ?? null,
        ], now()->addHours(24));

        if (($result['created'] ?? 0) > 0 || ($result['updated'] ?? 0) > 0) {
            FrontendRevalidateService::revalidateProducts();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'job_id' => $this->jobId,
            'created' => 0,
            'failed' => 1,
            'errors' => [['row' => 0, 'message' => $exception->getMessage()]],
            'failed_report_id' => null,
        ], now()->addHours(24));
    }

    private function cacheKey(): string
    {
        return 'product_import_job:' . $this->jobId;
    }
}

