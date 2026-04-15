<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Services\SettingService;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AuthorizesRequests, ChecksPermissions;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    protected function isDemoModeEnabled(): bool
    {
        try {
            $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
            $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
            return (bool)($settings['demoMode'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Base query — web-panel users only (no admin/seller panel access). */
    private function customerQuery()
    {
        return User::query()->where(function ($q) {
            $q->whereNull('access_panel')->orWhere('access_panel', 'web');
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            abort(403, trans('labels.permission_denied'));
        }

        $createPermission = $this->hasPermission(AdminPermissionEnum::CUSTOMER_CREATE());
        $editPermission   = $this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT());
        $deletePermission = $this->hasPermission(AdminPermissionEnum::CUSTOMER_DELETE());
        $exportPermission = $this->hasPermission(AdminPermissionEnum::CUSTOMER_EXPORT());

        $columns = [
            ['data' => 'id',         'name' => 'id',         'title' => __('labels.id')],
            ['data' => 'name',        'name' => 'name',        'title' => __('labels.name')],
            ['data' => 'email',       'name' => 'email',       'title' => __('labels.email')],
            ['data' => 'mobile',      'name' => 'mobile',      'title' => __('labels.mobile')],
            ['data' => 'status',      'name' => 'status',      'title' => __('labels.status'),  'orderable' => false, 'searchable' => false],
            ['data' => 'created_at',  'name' => 'created_at',  'title' => __('labels.created_at')],
            ['data' => 'action',      'name' => 'action',      'title' => __('labels.action'),   'orderable' => false, 'searchable' => false],
        ];

        return view('admin.customers.index', compact(
            'columns',
            'createPermission',
            'editPermission',
            'deletePermission',
            'exportPermission',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Datatable
    // ──────────────────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $draw   = $request->get('draw');
        $start  = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection   = $request->get('order')[0]['dir']    ?? 'asc';

        $columnsMap  = ['id', 'name', 'email', 'mobile', 'status', 'created_at'];
        $orderColumn = $columnsMap[$orderColumnIndex] ?? 'id';

        $query = $this->customerQuery();
        $totalRecords = $query->count();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name',   'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile','like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        $demo            = $this->isDemoModeEnabled();
        $editPermission  = $this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT());
        $deletePermission= $this->hasPermission(AdminPermissionEnum::CUSTOMER_DELETE());

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($user) use ($demo, $editPermission, $deletePermission) {
                $email  = $user->email  ?? '';
                $mobile = $user->mobile ?? '';
                return [
                    'id'         => $user->id,
                    'name'       => '<a href="' . route('admin.customers.show', $user->id) . '" class="text-decoration-none fw-medium">' . e($user->name) . '</a>',
                    'email'      => $demo ? Str::mask($email,  '****', 3, 4) : e($email),
                    'mobile'     => $demo ? Str::mask($mobile, '****', 3, 4) : e($mobile),
                    'status'     => view('partials.status', ['status' => $user->status ? 'active' : 'inactive'])->render(),
                    'created_at' => $user->created_at?->format('Y-m-d'),
                    'action'     => view('admin.customers.partials.actions', [
                        'customer'        => $user,
                        'editPermission'  => $editPermission,
                        'deletePermission'=> $deletePermission,
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw'            => intval($draw),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Show (detail page)
    // ──────────────────────────────────────────────────────────────────────────

    public function show(Request $request, int $id): View|JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            if ($request->expectsJson()) {
                return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
            }

            abort(403, trans('labels.permission_denied'));
        }

        $customer = $this->customerQuery()->findOrFail($id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id'     => $customer->id,
                    'name'   => $customer->name,
                    'email'  => $customer->email,
                    'mobile' => $customer->mobile,
                    'status' => (bool) $customer->status,
                ],
            ]);
        }

        $editPermission   = $this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT());
        $deletePermission = $this->hasPermission(AdminPermissionEnum::CUSTOMER_DELETE());

        return view('admin.customers.show', compact('customer', 'editPermission', 'deletePermission'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Create / Store
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_CREATE())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'mobile'   => 'nullable|string|max:20',
            'password' => ['required', Password::min(8)],
            'status'   => 'nullable|boolean',
        ]);

        $customer = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'mobile'       => $validated['mobile'] ?? null,
            'password'     => Hash::make($validated['password']),
            'status'       => $validated['status'] ?? true,
            'access_panel' => null,
        ]);

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.customer_created_successfully',
            data:    ['id' => $customer->id, 'name' => $customer->name],
            status:  201
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $customer = $this->customerQuery()->findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $id,
            'mobile'   => 'nullable|string|max:20',
            'password' => ['nullable', Password::min(8)],
            'status'   => 'nullable|boolean',
        ]);

        $data = [
            'name'   => $validated['name'],
            'email'  => $validated['email'],
            'mobile' => $validated['mobile'] ?? $customer->mobile,
            'status' => $validated['status'] ?? $customer->status,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $customer->update($data);

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.customer_updated_successfully',
            data:    $customer->fresh()
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Toggle Active / Inactive
    // ──────────────────────────────────────────────────────────────────────────

    public function toggleStatus(int $id): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $customer = $this->customerQuery()->findOrFail($id);
        $customer->update(['status' => !$customer->status]);
        $customer->refresh();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: $customer->status ? 'labels.customer_activated' : 'labels.customer_deactivated',
            data:    ['status' => $customer->status]
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Destroy (soft-delete)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_DELETE())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $customer = $this->customerQuery()->findOrFail($id);
        $customer->delete();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.customer_deleted_successfully'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Addresses (datatable)
    // ──────────────────────────────────────────────────────────────────────────

    public function addresses(Request $request, int $customerId): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $this->customerQuery()->findOrFail($customerId);

        $addresses = Address::where('user_id', $customerId)->orderByDesc('id')->get();

        $editPermission   = $this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT());
        $deletePermission = $this->hasPermission(AdminPermissionEnum::CUSTOMER_DELETE());

        $data = $addresses->map(function ($addr) use ($customerId, $editPermission, $deletePermission) {
            return [
                'id'            => $addr->id,
                'address_type'  => strtolower($addr->address_type ?? 'other'),
                'address_line1' => $addr->address_line1 ?? '',
                'address_line2' => $addr->address_line2 ?? '',
                'city'          => $addr->city          ?? '',
                'state'         => $addr->state         ?? '',
                'zipcode'       => $addr->zipcode       ?? '',
                'country'       => $addr->country       ?? '',
                'mobile'        => $addr->mobile        ?? '',
                'landmark'      => $addr->landmark      ?? '',
                'full_address'  => implode(', ', array_filter([
                    $addr->address_line1,
                    $addr->address_line2,
                    $addr->city,
                    $addr->state,
                    $addr->zipcode,
                    $addr->country,
                ])),
                'action'        => view('admin.customers.partials.address-actions', [
                    'address'         => $addr,
                    'customerId'      => $customerId,
                    'editPermission'  => $editPermission,
                    'deletePermission'=> $deletePermission,
                ])->render(),
            ];
        })->toArray();

        return response()->json(['data' => $data]);
    }

    public function storeAddress(Request $request, int $customerId): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $this->customerQuery()->findOrFail($customerId);

        $validated = $request->validate([
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'required|string|max:100',
            'state'         => 'nullable|string|max:100',
            'zipcode'       => 'nullable|string|max:20',
            'country'       => 'nullable|string|max:100',
            'mobile'        => 'nullable|string|max:20',
            'address_type'  => 'nullable|string|max:50',
            'landmark'      => 'nullable|string|max:255',
        ]);

        $address = Address::create(array_merge($validated, ['user_id' => $customerId]));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.address_created_successfully',
            data:    $address,
            status:  201
        );
    }

    public function updateAddress(Request $request, int $customerId, int $addressId): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_EDIT())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $address = Address::where('user_id', $customerId)->findOrFail($addressId);

        $validated = $request->validate([
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'required|string|max:100',
            'state'         => 'nullable|string|max:100',
            'zipcode'       => 'nullable|string|max:20',
            'country'       => 'nullable|string|max:100',
            'mobile'        => 'nullable|string|max:20',
            'address_type'  => 'nullable|string|max:50',
            'landmark'      => 'nullable|string|max:255',
        ]);

        $address->update($validated);

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.address_updated_successfully',
            data:    $address->fresh()
        );
    }

    public function destroyAddress(int $customerId, int $addressId): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_DELETE())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $address = Address::where('user_id', $customerId)->findOrFail($addressId);
        $address->delete();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.address_deleted_successfully'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Orders (datatable)
    // ──────────────────────────────────────────────────────────────────────────

    public function orders(Request $request, int $customerId): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $this->customerQuery()->findOrFail($customerId);

        $draw   = $request->get('draw');
        $start  = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);

        $query = Order::where('user_id', $customerId);
        $total = $query->count();

        $orders = $query->orderByDesc('id')->skip($start)->take($length)->get();

        $data = $orders->map(function ($order) {
            return [
                'id'             => $order->id,
                'order_number'   => '<a href="' . route('admin.orders.show', $order->id) . '" class="text-decoration-none fw-medium">#' . e($order->slug ?? $order->id) . '</a>',
                'payment_method' => ucfirst(str_replace('_', ' ', $order->payment_method ?? '')),
                'payment_status' => view('partials.status', ['status' => $order->payment_status ?? ''])->render(),
                'status'         => view('partials.status', ['status' => $order->status ?? ''])->render(),
                'total'          => number_format((float)($order->final_total ?? 0), 2),
                'created_at'     => $order->created_at?->format('Y-m-d'),
            ];
        })->toArray();

        return response()->json([
            'draw'            => intval($draw),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Export CSV
    // ──────────────────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_EXPORT())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $filename = 'customers_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Mobile', 'Status', 'Created At']);

            $this->customerQuery()
                ->orderBy('id', 'desc')
                ->chunk(500, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->mobile,
                            $user->status ? 'Active' : 'Inactive',
                            optional($user->created_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
