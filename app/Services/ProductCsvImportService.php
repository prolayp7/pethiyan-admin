<?php

namespace App\Services;

use App\Enums\Product\ProductTypeEnum;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class ProductCsvImportService
{
    private const REQUIRED_COLUMNS = [
        'title',
        'type',
        'category_id',
        'short_description',
        'description',
        'pricing_json',
    ];

    public function import(string $filePath, string $originalName, int $sellerId, ProductService $productService): array
    {
        $rows = $this->parseRowsFromFile($filePath, $originalName);
        if (!is_array($rows) || empty($rows)) {
            return [
                'created' => 0,
                'failed' => 1,
                'errors' => [['row' => 0, 'message' => 'Unable to parse import file.']],
            ];
        }

        $header = $rows[0] ?? null;
        if (!is_array($header) || empty($header)) {
            return [
                'created' => 0,
                'failed' => 1,
                'errors' => [['row' => 0, 'message' => 'CSV header is missing or invalid.']],
            ];
        }

        $normalizedHeader = array_map([$this, 'normalizeHeader'], $header);
        $missingColumns = array_values(array_diff(self::REQUIRED_COLUMNS, $normalizedHeader));

        if (!empty($missingColumns)) {
            return [
                'created' => 0,
                'failed' => 1,
                'errors' => [[
                    'row' => 0,
                    'message' => 'Missing required columns: ' . implode(', ', $missingColumns),
                ]],
            ];
        }

        return $this->importRows($rows, $normalizedHeader, $sellerId, $productService);
    }

    public function buildFailedRowsReport(array $errors): ?string
    {
        if (empty($errors)) {
            return null;
        }

        $disk = Storage::disk('local');
        $directory = 'product-import-reports';
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $reportId = (string) Str::uuid();
        $reportPath = $directory . '/' . $reportId . '.csv';
        $absolutePath = $disk->path($reportPath);
        $handle = fopen($absolutePath, 'w');
        if ($handle === false) {
            return null;
        }

        fputcsv($handle, ['row', 'message', 'payload_json']);
        foreach ($errors as $error) {
            fputcsv($handle, [
                $error['row'] ?? '',
                $error['message'] ?? '',
                json_encode($error['data'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }
        fclose($handle);

        return $reportId;
    }

    private function importRows(array $rows, array $normalizedHeader, int $sellerId, ProductService $productService): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];
        $rowNumber = 1; // header
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $row) {
            $rowNumber++;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rowData = $this->rowToAssociative($normalizedHeader, $row);
            $validation = $this->validateRow($rowData);

            if ($validation !== true) {
                $failed++;
                $errors[] = ['row' => $rowNumber, 'message' => $validation, 'data' => $rowData];
                continue;
            }

            try {
                $payload = $this->mapToProductPayload($rowData, $sellerId);
                $request = Request::create('/admin/products/import', 'POST', $payload);
                $productService->storeProduct($payload, $request);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage(), 'data' => $rowData];
            }
        }

        $reportId = $this->buildFailedRowsReport($errors);

        return [
            'created' => $created,
            'failed' => $failed,
            'errors' => $errors,
            'failed_report_id' => $reportId,
        ];
    }

    public function templateHeaders(): array
    {
        return [
            'title',
            'type',
            'category_id',
            'sub_category_id',
            'brand_id',
            'tax_group_id',
            'gst_rate',
            'hsn_code',
            'short_description',
            'description',
            'tags',
            'base_prep_time',
            'minimum_order_quantity',
            'quantity_step_size',
            'total_allowed_quantity',
            'is_returnable',
            'returnable_days',
            'is_cancelable',
            'cancelable_till',
            'is_attachment_required',
            'featured',
            'requires_otp',
            'image_fit',
            'indicator',
            'video_type',
            'video_link',
            'made_in',
            'warranty_period',
            'guarantee_period',
            'barcode',
            'capacity',
            'capacity_unit',
            'weight',
            'weight_unit',
            'height',
            'height_unit',
            'breadth',
            'breadth_unit',
            'length',
            'length_unit',
            'color_value_id',
            'pricing_json',
            'variants_json',
        ];
    }

    public function templateExampleRow(): array
    {
        return [
            'Sample Product',
            'simple',
            '1',
            '',
            '',
            '',
            '12',
            '39191010',
            'Short description',
            'Long description',
            'tape|packaging',
            '0',
            '1',
            '1',
            '100',
            '0',
            '',
            '0',
            '',
            '0',
            '0',
            '0',
            'cover',
            '',
            '',
            '',
            'India',
            '',
            '',
            'SAMPLE-001',
            '',
            'ml',
            '1.2',
            'kg',
            '10',
            'cm',
            '4',
            'cm',
            '12',
            'cm',
            '',
            '{"store_pricing":[{"store_id":1,"price":89,"stock":15,"sku":"SKU-001"}]}',
            '',
        ];
    }

    private function validateRow(array $rowData): true|string
    {
        $validator = Validator::make($rowData, [
            'title' => 'required|string|max:255',
            'type' => 'required|in:' . ProductTypeEnum::SIMPLE->value . ',' . ProductTypeEnum::VARIANT->value,
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'tax_group_id' => 'nullable|integer|exists:tax_classes,id',
            'gst_rate' => 'nullable|in:0,5,12,18,28',
            'short_description' => 'required|string|max:255',
            'description' => 'required|string',
            'pricing_json' => 'required|string',
            'variants_json' => 'nullable|string',
            'barcode' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $isVariant = $rowData['type'] === ProductTypeEnum::VARIANT->value;
        if ($isVariant && empty($rowData['variants_json'])) {
            return 'variants_json is required when type is variant.';
        }

        if (!$isVariant && empty($rowData['barcode'])) {
            return 'barcode is required when type is simple.';
        }

        if (!empty($rowData['sub_category_id'])) {
            $parentCategory = Category::query()->where('id', (int) $rowData['category_id'])->first();
            $subCategory = Category::query()->where('id', (int) $rowData['sub_category_id'])->first();
            if ($subCategory && $parentCategory && (int) $subCategory->parent_id !== (int) $parentCategory->id) {
                return 'sub_category_id does not belong to the selected category_id.';
            }
        }

        $pricing = json_decode($rowData['pricing_json'], true);
        if (!is_array($pricing)) {
            return 'pricing_json must be a valid JSON object.';
        }

        if ($isVariant) {
            if (!isset($pricing['variant_pricing']) || !is_array($pricing['variant_pricing'])) {
                return 'pricing_json must include variant_pricing array for variant products.';
            }
        } else {
            if (!isset($pricing['store_pricing']) || !is_array($pricing['store_pricing'])) {
                return 'pricing_json must include store_pricing array for simple products.';
            }
        }

        return true;
    }

    private function mapToProductPayload(array $rowData, int $sellerId): array
    {
        $pricingJson = (string) ($rowData['pricing_json'] ?? '{}');
        $pricingJson = $this->normalizePricingJsonForImport($pricingJson, (string) ($rowData['type'] ?? ''));

        $resolvedCategoryId = !empty($rowData['sub_category_id'])
            ? (int) $rowData['sub_category_id']
            : (int) $rowData['category_id'];

        $tags = [];
        if (!empty($rowData['tags'])) {
            $rawTags = str_contains((string) $rowData['tags'], '|')
                ? explode('|', (string) $rowData['tags'])
                : explode(',', (string) $rowData['tags']);
            $tags = array_values(array_filter(array_map(static fn($tag) => trim((string) $tag), $rawTags)));
        }

        return [
            'title' => (string) $rowData['title'],
            'seller_id' => $sellerId,
            'category_id' => $resolvedCategoryId,
            'brand_id' => $this->nullableInt($rowData['brand_id'] ?? null),
            'tax_group_id' => $this->nullableInt($rowData['tax_group_id'] ?? null),
            'gst_rate' => $this->nullableString($rowData['gst_rate'] ?? null),
            'hsn_code' => $this->nullableString($rowData['hsn_code'] ?? null),
            'type' => (string) $rowData['type'],
            'base_prep_time' => $this->nullableInt($rowData['base_prep_time'] ?? null) ?? 0,
            'short_description' => (string) $rowData['short_description'],
            'description' => (string) $rowData['description'],
            'indicator' => $this->nullableString($rowData['indicator'] ?? null),
            'image_fit' => $this->nullableString($rowData['image_fit'] ?? null) ?? 'cover',
            'minimum_order_quantity' => $this->nullableInt($rowData['minimum_order_quantity'] ?? null) ?? 1,
            'quantity_step_size' => $this->nullableInt($rowData['quantity_step_size'] ?? null) ?? 1,
            'total_allowed_quantity' => $this->nullableInt($rowData['total_allowed_quantity'] ?? null),
            'is_returnable' => $this->normalizeBoolean($rowData['is_returnable'] ?? null),
            'returnable_days' => $this->nullableInt($rowData['returnable_days'] ?? null),
            'is_cancelable' => $this->normalizeBoolean($rowData['is_cancelable'] ?? null),
            'cancelable_till' => $this->nullableString($rowData['cancelable_till'] ?? null),
            'is_attachment_required' => $this->normalizeBoolean($rowData['is_attachment_required'] ?? null),
            'featured' => $this->normalizeBoolean($rowData['featured'] ?? null),
            'requires_otp' => $this->normalizeBoolean($rowData['requires_otp'] ?? null),
            'video_type' => $this->nullableString($rowData['video_type'] ?? null),
            'video_link' => $this->nullableString($rowData['video_link'] ?? null),
            'warranty_period' => $this->nullableString($rowData['warranty_period'] ?? null),
            'guarantee_period' => $this->nullableString($rowData['guarantee_period'] ?? null),
            'made_in' => $this->nullableString($rowData['made_in'] ?? null),
            'pricing' => $pricingJson,
            'tags' => $tags,
            'custom_fields' => [],
            'variants_json' => $this->nullableString($rowData['variants_json'] ?? null),
            'barcode' => $this->nullableString($rowData['barcode'] ?? null),
            'capacity' => $this->nullableFloat($rowData['capacity'] ?? null),
            'capacity_unit' => $this->nullableString($rowData['capacity_unit'] ?? null) ?? 'ml',
            'weight' => $this->nullableFloat($rowData['weight'] ?? null),
            'weight_unit' => $this->nullableString($rowData['weight_unit'] ?? null) ?? 'kg',
            'height' => $this->nullableFloat($rowData['height'] ?? null),
            'height_unit' => $this->nullableString($rowData['height_unit'] ?? null) ?? 'cm',
            'breadth' => $this->nullableFloat($rowData['breadth'] ?? null),
            'breadth_unit' => $this->nullableString($rowData['breadth_unit'] ?? null) ?? 'cm',
            'length' => $this->nullableFloat($rowData['length'] ?? null),
            'length_unit' => $this->nullableString($rowData['length_unit'] ?? null) ?? 'cm',
            'color_value_id' => $this->nullableInt($rowData['color_value_id'] ?? null),
        ];
    }

    private function normalizePricingJsonForImport(string $pricingJson, string $type): string
    {
        $pricing = json_decode($pricingJson, true);
        if (!is_array($pricing)) {
            return $pricingJson;
        }

        if ($type !== ProductTypeEnum::VARIANT->value || empty($pricing['variant_pricing']) || !is_array($pricing['variant_pricing'])) {
            return json_encode($pricing, JSON_UNESCAPED_UNICODE);
        }

        $normalizedVariantPricing = [];
        foreach ($pricing['variant_pricing'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $variantId = $entry['variant_id'] ?? null;
            if ($variantId === null) {
                continue;
            }

            // Accept nested structure:
            // [{"variant_id":"v1","store_pricing":[{"store_id":1,...}]}]
            if (isset($entry['store_pricing']) && is_array($entry['store_pricing'])) {
                foreach ($entry['store_pricing'] as $storePricing) {
                    if (!is_array($storePricing)) {
                        continue;
                    }

                    $normalizedVariantPricing[] = array_merge(
                        ['variant_id' => $variantId],
                        $storePricing
                    );
                }
                continue;
            }

            // Also accept already flat structure:
            // [{"variant_id":"v1","store_id":1,...}]
            $normalizedVariantPricing[] = $entry;
        }

        $pricing['variant_pricing'] = $normalizedVariantPricing;
        return json_encode($pricing, JSON_UNESCAPED_UNICODE);
    }

    private function rowToAssociative(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $index => $column) {
            $assoc[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $assoc;
    }

    private function parseRowsFromFile(string $filePath, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === 'xlsx') {
            return $this->parseXlsxRows($filePath);
        }

        return $this->parseCsvRows($filePath);
    }

    private function parseCsvRows(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function parseXlsxRows(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $sharedXml = @simplexml_load_string($sharedStringsXml);
            if ($sharedXml && isset($sharedXml->si)) {
                foreach ($sharedXml->si as $si) {
                    $value = '';
                    if (isset($si->t)) {
                        $value = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $value .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = $value;
                }
            }
        }

        $sheetXmlRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlRaw === false) {
            $zip->close();
            return [];
        }

        $sheetXml = @simplexml_load_string($sheetXmlRaw);
        $zip->close();
        if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($sheetXml->sheetData->row as $rowNode) {
            $rowData = [];
            foreach ($rowNode->c as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $columnLetters = preg_replace('/\d+/', '', $cellRef);
                $columnIndex = $this->columnLettersToIndex($columnLetters);
                if ($columnIndex < 0) {
                    continue;
                }

                $type = (string) ($cell['t'] ?? '');
                $value = '';
                if ($type === 's') {
                    $sharedIndex = isset($cell->v) ? (int) $cell->v : -1;
                    $value = $sharedStrings[$sharedIndex] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = isset($cell->v) ? (string) $cell->v : '';
                }

                $rowData[$columnIndex] = $value;
            }

            if (!empty($rowData)) {
                ksort($rowData);
                $maxIndex = max(array_keys($rowData));
                $normalized = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $normalized[] = $rowData[$i] ?? '';
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    private function columnLettersToIndex(string $letters): int
    {
        if ($letters === '') {
            return -1;
        }

        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim($header));
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableInt(mixed $value): ?int
    {
        $stringValue = $this->nullableString($value);
        return $stringValue === null ? null : (int) $stringValue;
    }

    private function nullableFloat(mixed $value): ?float
    {
        $stringValue = $this->nullableString($value);
        return $stringValue === null ? null : (float) $stringValue;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'y'], true);
    }
}
