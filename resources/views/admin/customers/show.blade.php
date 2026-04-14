@extends('layouts.admin.app', ['page' => $menuAdmin['customers']['active'] ?? ""])

@section('title', __('labels.customer') . ' — ' . $customer->name)

@section('header_data')
    @php
        $page_title    = $customer->name;
        $page_pretitle = __('labels.customer_detail');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),      'url' => route('admin.dashboard')],
        ['title' => __('labels.customers'), 'url' => route('admin.customers.index')],
        ['title' => $customer->name,         'url' => null],
    ];
@endphp

@section('admin-content')
<div class="row row-cards">

    {{-- ── Profile card ──────────────────────────────────────────────────── --}}
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    @if($customer->profile_image)
                        <img src="{{ $customer->profile_image }}" alt="{{ $customer->name }}"
                             class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">
                    @else
                        <span class="avatar avatar-xl rounded-circle bg-primary-lt text-primary fs-2 fw-bold">
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        </span>
                    @endif
                </div>
                <h3 class="mb-0">{{ $customer->name }}</h3>
                <div class="text-secondary">{{ $customer->email }}</div>
                @if($customer->mobile)
                    <div class="text-secondary">{{ $customer->mobile }}</div>
                @endif
                <div class="mt-2">
                    @if($customer->status)
                        <span class="badge bg-success-lt text-success">{{ __('labels.active') }}</span>
                    @else
                        <span class="badge bg-danger-lt text-danger">{{ __('labels.inactive') }}</span>
                    @endif
                </div>
            </div>
            @if($editPermission)
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-primary flex-fill" id="editCustomerBtn"
                        data-id="{{ $customer->id }}"
                        data-name="{{ $customer->name }}"
                        data-email="{{ $customer->email }}"
                        data-mobile="{{ $customer->mobile }}"
                        data-status="{{ $customer->status ? 1 : 0 }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" class="icon">
                        <path d="M11 4H4a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2 -2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1 -4 9.5 -9.5z"/>
                    </svg>
                    {{ __('labels.edit') }}
                </button>
                <button class="btn {{ $customer->status ? 'btn-warning' : 'btn-success' }} flex-fill" id="toggleStatusBtn"
                        data-id="{{ $customer->id }}">
                    {{ $customer->status ? __('labels.deactivate') : __('labels.activate') }}
                </button>
            </div>
            @endif
        </div>

        {{-- ── Stats ────────────────────────────────────────────────────── --}}
        <div class="card mt-3">
            <div class="card-body">
                <div class="divide-y">
                    <div class="row py-2">
                        <div class="col text-secondary">{{ __('labels.registered') }}</div>
                        <div class="col-auto fw-medium">{{ $customer->created_at?->format('d M Y') }}</div>
                    </div>
                    <div class="row py-2">
                        <div class="col text-secondary">{{ __('labels.total_orders') }}</div>
                        <div class="col-auto fw-medium" id="orderCountBadge">—</div>
                    </div>
                    <div class="row py-2">
                        <div class="col text-secondary">{{ __('labels.saved_addresses') }}</div>
                        <div class="col-auto fw-medium" id="addressCountBadge">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabs ────────────────────────────────────────────────────────── --}}
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="customerTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-orders">{{ __('labels.orders') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-addresses">{{ __('labels.addresses') }}</a>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content p-0">

                {{-- ── Orders tab ──────────────────────────────────────── --}}
                <div class="tab-pane active" id="tab-orders">
                    <table class="table table-vcenter card-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('labels.order') }}</th>
                                <th>{{ __('labels.payment') }}</th>
                                <th>{{ __('labels.payment_status') }}</th>
                                <th>{{ __('labels.status') }}</th>
                                <th>{{ __('labels.total') }}</th>
                                <th>{{ __('labels.date') }}</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <tr><td colspan="7" class="text-center py-4 text-secondary">{{ __('labels.loading') }}…</td></tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center p-3" id="ordersPagination"></div>
                </div>

                {{-- ── Addresses tab ────────────────────────────────────── --}}
                <div class="tab-pane" id="tab-addresses">
                    <div class="p-3 border-bottom d-flex justify-content-end">
                        @if($editPermission)
                        <button class="btn btn-sm btn-primary" id="addAddressBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                            </svg>
                            {{ __('labels.add_address') }}
                        </button>
                        @endif
                    </div>
                    <table class="table table-vcenter card-table" id="addressesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('labels.type') }}</th>
                                <th>{{ __('labels.address') }}</th>
                                <th>{{ __('labels.mobile') }}</th>
                                @if($editPermission || $deletePermission)
                                <th>{{ __('labels.action') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="addressesBody">
                            <tr><td colspan="5" class="text-center py-4 text-secondary">{{ __('labels.loading') }}…</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>{{-- /.tab-content --}}
        </div>
    </div>

</div>

{{-- ── Edit Customer Modal ───────────────────────────────────────────────── --}}
<div class="modal modal-blur fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('labels.edit_customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('labels.name') }}</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">{{ __('labels.email') }}</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('labels.mobile') }}</label>
                        <input type="text" class="form-control" id="editMobile" name="mobile">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('labels.password') }} <small class="text-muted">({{ __('labels.leave_blank_to_keep') }})</small></label>
                        <input type="password" class="form-control" id="editPassword" name="password" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('labels.status') }}</label>
                        <select class="form-select" name="status" id="editStatus">
                            <option value="1">{{ __('labels.active') }}</option>
                            <option value="0">{{ __('labels.inactive') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('labels.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Address Modal ─────────────────────────────────────────────────────── --}}
<div class="modal modal-blur fade" id="addressModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalTitle">{{ __('labels.add_address') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addressForm">
                @csrf
                <input type="hidden" id="addressId" value="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">{{ __('labels.address_line1') }}</label>
                            <input type="text" class="form-control" name="address_line1" id="addrLine1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('labels.address_line2') }}</label>
                            <input type="text" class="form-control" name="address_line2" id="addrLine2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('labels.city') }}</label>
                            <input type="text" class="form-control" name="city" id="addrCity" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('labels.state') }}</label>
                            <input type="text" class="form-control" name="state" id="addrState">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('labels.zipcode') }}</label>
                            <input type="text" class="form-control" name="zipcode" id="addrZip">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('labels.country') }}</label>
                            <input type="text" class="form-control" name="country" id="addrCountry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('labels.mobile') }}</label>
                            <input type="text" class="form-control" name="mobile" id="addrMobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('labels.address_type') }}</label>
                            <select class="form-select" name="address_type" id="addrType">
                                <option value="home">{{ __('labels.home') }}</option>
                                <option value="work">{{ __('labels.work') }}</option>
                                <option value="other">{{ __('labels.other') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('labels.landmark') }}</label>
                            <input type="text" class="form-control" name="landmark" id="addrLandmark">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('labels.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Delete Address Confirm Modal ─────────────────────────────────────── --}}
<div class="modal modal-blur fade" id="deleteAddressModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round" class="icon mb-2 text-danger icon-lg">
                    <path d="M12 9v4"/>
                    <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>
                    <path d="M12 16h.01"/>
                </svg>
                <h3>{{ __('labels.delete_address') }}</h3>
                <p class="text-secondary">{{ __('labels.delete_address_confirm') }}</p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button class="btn w-100" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                        </div>
                        <div class="col">
                            <button class="btn btn-danger w-100" id="confirmDeleteAddress">{{ __('labels.delete') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const customerId  = {{ $customer->id }};
    const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const baseUrl     = `{{ url('/admin/customers') }}/${customerId}`;

    let deleteAddressId = null;
    let ordersPage      = 0;
    const ordersPerPage = 10;

    // ── Load orders ──────────────────────────────────────────────────────
    function loadOrders() {
        fetch(`${baseUrl}/orders?draw=1&start=${ordersPage * ordersPerPage}&length=${ordersPerPage}`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            const body  = document.getElementById('ordersBody');
            const rows  = res.data ?? [];
            document.getElementById('orderCountBadge').textContent = res.recordsTotal ?? '—';

            if (!rows.length) {
                body.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-secondary">{{ __('labels.no_orders_found') }}</td></tr>`;
                return;
            }

            body.innerHTML = rows.map((r, i) => `
                <tr>
                    <td>${ordersPage * ordersPerPage + i + 1}</td>
                    <td>${r.order_number}</td>
                    <td>${r.payment_method}</td>
                    <td>${r.payment_status}</td>
                    <td>${r.status}</td>
                    <td>{{ config('settings.currency_symbol', '₹') }}${r.total}</td>
                    <td>${r.created_at}</td>
                </tr>
            `).join('');

            // Simple pagination
            const total   = res.recordsTotal ?? 0;
            const pages   = Math.ceil(total / ordersPerPage);
            const pagination = document.getElementById('ordersPagination');
            if (pages <= 1) { pagination.innerHTML = ''; return; }
            pagination.innerHTML = `
                <button class="btn btn-sm btn-outline-secondary" id="ordPrev" ${ordersPage === 0 ? 'disabled' : ''}>‹ {{ __('labels.previous') }}</button>
                <span class="text-secondary">${__('Page')} ${ordersPage + 1} / ${pages}</span>
                <button class="btn btn-sm btn-outline-secondary" id="ordNext" ${ordersPage >= pages - 1 ? 'disabled' : ''}>{{ __('labels.next') }} ›</button>
            `;
            document.getElementById('ordPrev')?.addEventListener('click', () => { ordersPage--; loadOrders(); });
            document.getElementById('ordNext')?.addEventListener('click', () => { ordersPage++; loadOrders(); });
        });
    }

    // ── Load addresses ────────────────────────────────────────────────────
    function loadAddresses() {
        fetch(`${baseUrl}/addresses`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            const body = document.getElementById('addressesBody');
            const rows = res.data ?? [];
            document.getElementById('addressCountBadge').textContent = rows.length;

            if (!rows.length) {
                body.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-secondary">{{ __('labels.no_addresses_found') }}</td></tr>`;
                return;
            }

            body.innerHTML = rows.map((r, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td><span class="badge bg-secondary-lt">${r.address_type}</span></td>
                    <td>${r.full_address}</td>
                    <td>${r.mobile}</td>
                    <td>${r.action}</td>
                </tr>
            `).join('');
        });
    }

    // ── Initial load ───────────────────────────────────────────────────────
    loadOrders();
    loadAddresses();

    // Reload addresses when tab activated
    document.querySelector('a[href="#tab-addresses"]')?.addEventListener('shown.bs.tab', loadAddresses);

    // ── Edit customer ─────────────────────────────────────────────────────
    document.getElementById('editCustomerBtn')?.addEventListener('click', (e) => {
        const btn = e.currentTarget;
        document.getElementById('editName').value   = btn.dataset.name   ?? '';
        document.getElementById('editEmail').value  = btn.dataset.email  ?? '';
        document.getElementById('editMobile').value = btn.dataset.mobile ?? '';
        document.getElementById('editStatus').value = btn.dataset.status ?? '1';
        document.getElementById('editPassword').value = '';
        new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
    });

    document.getElementById('editCustomerForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const body = {
            name:     document.getElementById('editName').value,
            email:    document.getElementById('editEmail').value,
            mobile:   document.getElementById('editMobile').value,
            status:   document.getElementById('editStatus').value,
            password: document.getElementById('editPassword').value || undefined,
        };
        fetch(baseUrl, {
            method: 'PUT',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success === false) {
                alert(Object.values(res.data ?? {}).flat().join('\n') || res.message);
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'))?.hide();
            location.reload();
        });
    });

    // ── Toggle status ─────────────────────────────────────────────────────
    document.getElementById('toggleStatusBtn')?.addEventListener('click', () => {
        fetch(`${baseUrl}/toggle-status`, {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(() => location.reload());
    });

    // ── Add address ───────────────────────────────────────────────────────
    document.getElementById('addAddressBtn')?.addEventListener('click', () => openAddressModal(null));

    // ── Edit / delete address (delegated) ─────────────────────────────────
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-address-btn');
        if (editBtn) {
            const addr = JSON.parse(editBtn.dataset.address ?? '{}');
            openAddressModal(addr);
        }

        const delBtn = e.target.closest('.delete-address-btn');
        if (delBtn) {
            deleteAddressId = delBtn.dataset.addressId;
            new bootstrap.Modal(document.getElementById('deleteAddressModal')).show();
        }
    });

    document.getElementById('confirmDeleteAddress')?.addEventListener('click', () => {
        if (!deleteAddressId) return;
        fetch(`${baseUrl}/addresses/${deleteAddressId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(() => {
            bootstrap.Modal.getInstance(document.getElementById('deleteAddressModal'))?.hide();
            loadAddresses();
        });
    });

    function openAddressModal(addr) {
        const isEdit = !!addr;
        document.getElementById('addressModalTitle').textContent =
            isEdit ? '{{ __('labels.edit_address') }}' : '{{ __('labels.add_address') }}';
        document.getElementById('addressId').value   = addr?.id           ?? '';
        document.getElementById('addrLine1').value   = addr?.address_line1 ?? '';
        document.getElementById('addrLine2').value   = addr?.address_line2 ?? '';
        document.getElementById('addrCity').value    = addr?.city          ?? '';
        document.getElementById('addrState').value   = addr?.state         ?? '';
        document.getElementById('addrZip').value     = addr?.zipcode       ?? '';
        document.getElementById('addrCountry').value = addr?.country       ?? '';
        document.getElementById('addrMobile').value  = addr?.mobile        ?? '';
        document.getElementById('addrType').value    = addr?.address_type  ?? 'home';
        document.getElementById('addrLandmark').value= addr?.landmark      ?? '';
        new bootstrap.Modal(document.getElementById('addressModal')).show();
    }

    document.getElementById('addressForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const id     = document.getElementById('addressId').value;
        const method = id ? 'PUT' : 'POST';
        const url    = id ? `${baseUrl}/addresses/${id}` : `${baseUrl}/addresses`;

        const body = {
            address_line1: document.getElementById('addrLine1').value,
            address_line2: document.getElementById('addrLine2').value,
            city:          document.getElementById('addrCity').value,
            state:         document.getElementById('addrState').value,
            zipcode:       document.getElementById('addrZip').value,
            country:       document.getElementById('addrCountry').value,
            mobile:        document.getElementById('addrMobile').value,
            address_type:  document.getElementById('addrType').value,
            landmark:      document.getElementById('addrLandmark').value,
        };

        fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success === false) {
                alert(Object.values(res.data ?? {}).flat().join('\n') || res.message);
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('addressModal'))?.hide();
            loadAddresses();
        });
    });
})();
</script>
@endpush
