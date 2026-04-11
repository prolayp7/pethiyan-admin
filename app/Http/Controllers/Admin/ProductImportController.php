<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProductImportJob;
use App\Services\FrontendRevalidateService;
use App\Services\ProductCsvImportService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    public function downloadTemplate(ProductCsvImportService $importService): StreamedResponse
    {
        $fileName = 'product-import-template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        return response()->stream(function () use ($importService) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $importService->templateHeaders());
            fputcsv($output, $importService->templateExampleRow());
            fclose($output);
        }, 200, $headers);
    }

    public function import(Request $request, ProductCsvImportService $importService, ProductService $productService): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:20480',
            'async' => 'nullable|boolean',
        ]);

        $adminUser = auth()->guard('admin')->user();
        $sellerId = 1;
        if (method_exists($adminUser, 'seller') && $adminUser?->seller()) {
            $sellerId = (int) $adminUser->seller()->id;
        }

        $isAsync = (bool) $request->boolean('async');
        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();

        if ($isAsync) {
            $storedPath = $uploadedFile->store('product-imports', 'local');
            $jobId = (string) Str::uuid();

            Cache::put('product_import_job:' . $jobId, [
                'status' => 'queued',
                'job_id' => $jobId,
                'created' => 0,
                'failed' => 0,
                'errors' => [],
                'failed_report_id' => null,
            ], now()->addHours(24));

            ProductImportJob::dispatch($jobId, $storedPath, $originalName, $sellerId);

            return response()->json([
                'success' => true,
                'message' => 'Import queued successfully.',
                'data' => [
                    'async' => true,
                    'job_id' => $jobId,
                    'status' => 'queued',
                ],
            ]);
        }

        $result = $importService->import(
            $uploadedFile->getRealPath(),
            $originalName,
            $sellerId,
            $productService
        );

        $failedReportUrl = null;
        if (!empty($result['failed_report_id'])) {
            $failedReportUrl = route('admin.products.import.failed-report', ['reportId' => $result['failed_report_id']]);
        }

        if ($result['created'] > 0 || $result['updated'] > 0) {
            FrontendRevalidateService::revalidateProducts();
        }

        return response()->json([
            'success' => $result['failed'] === 0,
            'message' => $result['failed'] === 0
                ? 'Products imported successfully.'
                : 'Import completed with some errors.',
            'data' => array_merge($result, [
                'async' => false,
                'failed_report_url' => $failedReportUrl,
            ]),
        ]);
    }

    public function status(string $jobId): JsonResponse
    {
        $data = Cache::get('product_import_job:' . $jobId);
        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found or expired.',
                'data' => null,
            ], 404);
        }

        $failedReportUrl = null;
        if (!empty($data['failed_report_id'])) {
            $failedReportUrl = route('admin.products.import.failed-report', ['reportId' => $data['failed_report_id']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Import status fetched successfully.',
            'data' => array_merge($data, [
                'failed_report_url' => $failedReportUrl,
            ]),
        ]);
    }

    public function downloadFailedReport(string $reportId): StreamedResponse
    {
        $path = 'product-import-reports/' . $reportId . '.csv';
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Report not found.');
        }

        return Storage::disk('local')->download($path, 'product-import-failed-rows-' . $reportId . '.csv');
    }
}
