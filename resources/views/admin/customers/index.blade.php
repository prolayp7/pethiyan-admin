@extends('layouts.admin.app', ['page' => $menuAdmin['customers']['active'] ?? ""])

@section('title', __('labels.customers'))

@section('header_data')
    @php
        $page_title    = __('labels.customers');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),      'url' => route('admin.dashboard')],
        ['title' => __('labels.customers'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.customers') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            @if($exportPermission)
                            <div class="col-auto">
                                <a href="{{ route('admin.customers.export') }}" class="btn btn-outline-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-download">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                        <path d="M7 11l5 5l5 -5"/>
                                        <path d="M12 4l0 12"/>
                                    </svg>
                                    {{ __('labels.export') }}
                                </a>
                            </div>
                            @endif
                            @if($createPermission)
                            <div class="col-auto">
                                <button class="btn btn-primary" id="addCustomerBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 5l0 14"/>
                                        <path d="M5 12l14 0"/>
                                    </svg>
                                    {{ __('labels.add_customer') }}
                                </button>
                            </div>
                            @endif
                            <div class="col-auto">
                                <button class="btn btn-outline-primary" id="refresh">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                    </svg>
                                    {{ __('labels.refresh') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="customers-table" :columns="$columns"
                                     route="{{ route('admin.customers.datatable') }}"
                                     :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Add / Edit Customer Modal ─────────────────────────────────────── --}}
    <div class="modal modal-blur fade" id="customerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalTitle">{{ __('labels.add_customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="customerForm">
                    @csrf
                    <input type="hidden" id="customerId" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.name') }}</label>
                            <input type="text" class="form-control" id="customerName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.email') }}</label>
                            <input type="email" class="form-control" id="customerEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.mobile') }}</label>
                            <input type="text" class="form-control" id="customerMobile" name="mobile">
                        </div>
                        <div class="mb-3" id="passwordGroup">
                            <label class="form-label required" id="passwordLabel">{{ __('labels.password') }}</label>
                            <input type="password" class="form-control" id="customerPassword" name="password" autocomplete="new-password">
                            <small class="text-muted d-none" id="passwordHint">{{ __('labels.leave_blank_to_keep') }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.status') }}</label>
                            <select class="form-select" name="status" id="customerStatus">
                                <option value="1">{{ __('labels.active') }}</option>
                                <option value="0">{{ __('labels.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">
                            {{ __('labels.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary" id="customerSaveBtn">
                            {{ __('labels.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Delete Confirm Modal ──────────────────────────────────────────── --}}
    <div class="modal modal-blur fade" id="deleteCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                    <h3>{{ __('labels.delete_customer') }}</h3>
                    <div class="text-secondary">{{ __('labels.delete_customer_confirm') }}</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn w-100" data-bs-dismiss="modal">{{ __('labels.cancel') }}</button>
                            </div>
                            <div class="col">
                                <button class="btn btn-danger w-100" id="confirmDeleteCustomer">{{ __('labels.delete') }}</button>
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
    const table       = window.LaravelDataTables?.['customers-table'];
    const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let   deleteId    = null;

    // ── Refresh ──────────────────────────────────────────────────────────
    document.getElementById('refresh')?.addEventListener('click', () => {
        window.LaravelDataTables?.['customers-table']?.draw(false);
    });

    // ── Add customer ─────────────────────────────────────────────────────
    document.getElementById('addCustomerBtn')?.addEventListener('click', () => {
        openModal(null);
    });

    // ── Edit customer (delegated) ─────────────────────────────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-customer-edit]');
        if (!btn) return;
        const id = btn.dataset.customerEdit;
        fetch(`{{ url('/admin/customers') }}/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(res => openModal(res.data ?? res));
    });

    // ── Delete customer (delegated) ──────────────────────────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-customer-delete]');
        if (!btn) return;
        deleteId = btn.dataset.customerDelete;
        new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
    });

    document.getElementById('confirmDeleteCustomer')?.addEventListener('click', () => {
        if (!deleteId) return;
        fetch(`{{ url('/admin/customers') }}/${deleteId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(res => {
            bootstrap.Modal.getInstance(document.getElementById('deleteCustomerModal'))?.hide();
            window.LaravelDataTables?.['customers-table']?.draw(false);
        });
    });

    // ── Toggle status (delegated) ─────────────────────────────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-customer-toggle]');
        if (!btn) return;
        const id = btn.dataset.customerToggle;
        fetch(`{{ url('/admin/customers') }}/${id}/toggle-status`, {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(() => window.LaravelDataTables?.['customers-table']?.draw(false));
    });

    // ── Modal helpers ─────────────────────────────────────────────────────
    function openModal(customer) {
        const isEdit = !!customer;
        document.getElementById('customerModalTitle').textContent =
            isEdit ? '{{ __('labels.edit_customer') }}' : '{{ __('labels.add_customer') }}';
        document.getElementById('customerId').value         = customer?.id   ?? '';
        document.getElementById('customerName').value       = customer?.name  ?? '';
        document.getElementById('customerEmail').value      = customer?.email ?? '';
        document.getElementById('customerMobile').value     = customer?.mobile ?? '';
        document.getElementById('customerStatus').value     = customer?.status != null ? (customer.status ? '1' : '0') : '1';
        document.getElementById('customerPassword').value   = '';
        document.getElementById('customerPassword').required = !isEdit;
        document.getElementById('passwordLabel').classList.toggle('required', !isEdit);
        document.getElementById('passwordHint').classList.toggle('d-none', !isEdit);
        new bootstrap.Modal(document.getElementById('customerModal')).show();
    }

    // ── Form submit ───────────────────────────────────────────────────────
    document.getElementById('customerForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const id      = document.getElementById('customerId').value;
        const method  = id ? 'PUT' : 'POST';
        const url     = id
            ? `{{ url('/admin/customers') }}/${id}`
            : `{{ url('/admin/customers') }}`;

        const body = {
            name:     document.getElementById('customerName').value,
            email:    document.getElementById('customerEmail').value,
            mobile:   document.getElementById('customerMobile').value,
            status:   document.getElementById('customerStatus').value,
            password: document.getElementById('customerPassword').value || undefined,
        };

        fetch(url, {
            method,
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success === false) {
                alert(Object.values(res.data ?? {}).flat().join('\n') || res.message);
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('customerModal'))?.hide();
            window.LaravelDataTables?.['customers-table']?.draw(false);
        });
    });
})();
</script>
@endpush
