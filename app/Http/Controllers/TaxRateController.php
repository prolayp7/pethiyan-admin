<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Http\Requests\TaxRate\TaxRateRequest;
use App\Models\TaxRate;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    protected bool $taxClassEditPermission = false;
    protected bool $taxClassCreatePermission = false;

    public function __construct()
    {
        if ($this->getPanel() == 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_CREATE());
            $this->taxClassEditPermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_EDIT());
            $this->taxClassCreatePermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_CREATE());
        }
    }

    public function index(): View
    {
        $this->authorize('viewAny', TaxRate::class);
        $columns = [
            ['data' => 'id',          'name' => 'id',          'title' => __('labels.id')],
            ['data' => 'title',       'name' => 'title',       'title' => __('labels.title')],
            ['data' => 'gst_slab',    'name' => 'gst_slab',    'title' => 'GST Slab',        'orderable' => false, 'searchable' => false],
            ['data' => 'cgst_sgst',   'name' => 'cgst_sgst',   'title' => 'CGST / SGST',     'orderable' => false, 'searchable' => false],
            ['data' => 'igst',        'name' => 'igst',        'title' => 'IGST',             'orderable' => false, 'searchable' => false],
            ['data' => 'is_active',   'name' => 'is_active',   'title' => 'Status',           'orderable' => false, 'searchable' => false],
            ['data' => 'created_at',  'name' => 'created_at',  'title' => __('labels.created_at')],
            ['data' => 'action',      'name' => 'action',      'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $classColumns = [
            ['data' => 'id',         'name' => 'id',         'title' => __('labels.id')],
            ['data' => 'title',      'name' => 'title',      'title' => __('labels.title')],
            ['data' => 'rates',      'name' => 'rates',      'title' => 'Applied GST Rates', 'orderable' => false, 'searchable' => false],
            ['data' => 'is_active',  'name' => 'is_active',  'title' => 'Status',            'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action',     'name' => 'action',     'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;
        $taxClassEditPermission = $this->taxClassEditPermission;
        $taxClassCreatePermission = $this->taxClassCreatePermission;

        return view($this->panelView('tax_rates.index'), compact('columns', 'classColumns', 'editPermission', 'deletePermission', 'createPermission',
            'taxClassEditPermission', 'taxClassCreatePermission'));
    }

    public function getTaxRates(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxRate::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'title', 'gst_slab', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = TaxRate::query();

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('gst_slab', 'like', "%$searchValue%");
            });
        }

        $totalRecords = TaxRate::count();
        $filteredRecords = $query->count();

        $editPermission  = $this->editPermission;
        $deletePermission = $this->deletePermission;

        $slabColors = [
            '0'  => 'secondary',
            '5'  => 'success',
            '12' => 'info',
            '18' => 'warning',
            '28' => 'danger',
        ];

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($taxRate) use ($editPermission, $deletePermission, $slabColors) {
                $slab   = $taxRate->gst_slab ?? null;
                $color  = $slabColors[$slab] ?? 'secondary';
                $slabBadge = $slab !== null
                    ? "<span class=\"badge bg-{$color}-lt fw-bold\">{$slab}% GST</span>"
                    : "<span class=\"badge bg-secondary-lt\">—</span>";

                $cgstSgst = ($taxRate->cgst_rate !== null)
                    ? "{$taxRate->cgst_rate}% + {$taxRate->sgst_rate}%"
                    : '—';

                $igst = ($taxRate->igst_rate !== null)
                    ? "{$taxRate->igst_rate}%"
                    : '—';

                $statusBadge = $taxRate->is_active
                    ? '<span class="badge bg-success-lt">Active</span>'
                    : '<span class="badge bg-danger-lt">Inactive</span>';

                return [
                    'id'         => $taxRate->id,
                    'title'      => $taxRate->title,
                    'gst_slab'   => $slabBadge,
                    'cgst_sgst'  => $cgstSgst,
                    'igst'       => $igst,
                    'is_active'  => $statusBadge,
                    'created_at' => $taxRate->created_at->format('Y-m-d'),
                    'action'     => view('partials.actions', [
                        'modelName'        => 'tax-rate',
                        'id'               => $taxRate->id,
                        'title'            => $taxRate->title,
                        'mode'             => 'model_view',
                        'editPermission'   => $editPermission,
                        'deletePermission' => $deletePermission,
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function store(TaxRateRequest $request): JsonResponse
    {

        $validated = $request->validated();
        try {
            $this->authorize('create', TaxRate::class);
            $taxRate = TaxRate::create($validated);
            return ApiResponseType::sendJsonResponse(
                true,
                'labels.tax_rate_created_successfully',
                $taxRate
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        } catch (Exception) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.tax_rate_create_failed',
                [],
                500
            );
        }
    }

    public function show($id): JsonResponse
    {
        $taxRate = TaxRate::find($id);
        if (!$taxRate) {
            return ApiResponseType::sendJsonResponse(false, 'labels.tax_rate_not_found', [], 404);
        }
        return ApiResponseType::sendJsonResponse(
            true,
            'labels.tax_rate_fetched_successfully',
            $taxRate
        );
    }

    public function update(TaxRateRequest $request, $id): JsonResponse
    {
        $taxRate = TaxRate::find($id);
        if (!$taxRate) {
            return ApiResponseType::sendJsonResponse(false, 'labels.tax_rate_not_found', [], 404);
        }

        $this->authorize('update', $taxRate);

        $validated = $request->validated();

        try {
            $taxRate->update($validated);
            return ApiResponseType::sendJsonResponse(
                true,
                'labels.tax_rate_updated_successfully',
                $taxRate
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        } catch (Exception) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.tax_rate_update_failed',
                [],
                500
            );
        }
    }

    public function destroy($id): JsonResponse
    {
        $taxRate = TaxRate::find($id);
        if (!$taxRate) {
            return ApiResponseType::sendJsonResponse(false, 'labels.tax_rate_not_found', [], 404);
        }

        $this->authorize('delete', $taxRate);

        try {
            $taxRate->delete();
            return ApiResponseType::sendJsonResponse(
                true,
                'labels.tax_rate_deleted_successfully',
                []
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        } catch (Exception) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.tax_rate_delete_failed',
                [],
                500
            );
        }
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');

        $taxRates = TaxRate::query()
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'LIKE', '%' . $query . '%');
            })
            ->select([
                'id as value',
                'title',
            ])
            ->take(10)
            ->get();

        return response()->json($taxRates);
    }
}
