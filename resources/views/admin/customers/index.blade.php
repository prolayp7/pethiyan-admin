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

    {{-- Hidden triggers (avoids needing the bootstrap global in JS) --}}
    <button id="triggerCustomerModal" data-bs-toggle="modal" data-bs-target="#customerModal" style="display:none" aria-hidden="true"></button>
    <button id="triggerDeleteCustomerModal" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal" style="display:none" aria-hidden="true"></button>

    {{-- ── Add / Edit Customer Modal ─────────────────────────────────────── --}}
    <div class="modal modal-blur fade" id="customerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalTitle">{{ __('labels.add_customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="customerForm">
                    @csrf
                    <input type="hidden" id="customerId" value="">
                    <div class="modal-body">

                        {{-- Customer Details --}}
                        <h5 class="mb-3 text-secondary fw-semibold" style="font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;">Customer Details</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('labels.name') }}</label>
                                <input type="text" class="form-control" id="customerName" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('labels.email') }}</label>
                                <input type="email" class="form-control" id="customerEmail" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('labels.mobile') }}</label>
                                <input type="text" class="form-control" id="customerMobile" name="mobile">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="customerCompanyName" name="company_name"
                                       placeholder="Optional business/company name">
                            </div>
                            <div class="col-md-6" id="passwordGroup">
                                <label class="form-label required" id="passwordLabel">{{ __('labels.password') }}</label>
                                <input type="password" class="form-control" id="customerPassword" name="password" autocomplete="new-password">
                                <small class="text-muted d-none" id="passwordHint">{{ __('labels.leave_blank_to_keep') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('labels.status') }}</label>
                                <select class="form-select" name="status" id="customerStatus">
                                    <option value="1">{{ __('labels.active') }}</option>
                                    <option value="0">{{ __('labels.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GSTIN</label>
                                <input type="text" class="form-control text-uppercase" id="customerGstin" name="gstin"
                                       maxlength="15" placeholder="e.g. 07AAAAA0000A1Z5">
                                <small class="form-hint">15-character GST Identification Number (optional)</small>
                            </div>
                        </div>

                        {{-- ── Address Section: NEW customer (collapsed toggle) ── --}}
                        <div id="addressAddSection">
                            <hr class="my-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="mb-0 text-secondary fw-semibold" style="font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;">Address <span class="text-muted fw-normal">(optional)</span></h5>
                                <button type="button" class="btn btn-sm btn-ghost-secondary" id="toggleAddressFields">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="icon me-1" id="toggleAddressIcon">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                    </svg>
                                    <span id="toggleAddressLabel">Add Address</span>
                                </button>
                            </div>
                            <div id="addressFields" class="d-none">
                                @include('admin.customers.partials.address-form-fields', ['prefix' => 'new'])
                            </div>
                        </div>

                        {{-- ── Address Section: EDIT customer (live list + inline form) ── --}}
                        <div id="addressEditSection" class="d-none">
                            <hr class="my-3">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0 text-secondary fw-semibold" style="font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;">
                                    Addresses <span class="badge bg-secondary-lt text-secondary ms-1" id="editAddrCount" style="font-size:.7rem;"></span>
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="editOpenAddrFormBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" class="icon me-1">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                    </svg>
                                    Add Address
                                </button>
                            </div>

                            {{-- Existing addresses list --}}
                            <div id="editAddrList" class="mb-3"></div>

                            {{-- Inline add / edit address form --}}
                            <div id="editAddrFormWrap" class="d-none">
                                <div class="card border shadow-sm mb-2">
                                    <div class="card-body pb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0 fw-semibold" id="editAddrFormTitle">New Address</h6>
                                            <button type="button" class="btn-close btn-sm" id="editCloseAddrFormBtn" aria-label="Close"></button>
                                        </div>
                                        <input type="hidden" id="editAddrId">
                                        @include('admin.customers.partials.address-form-fields', ['prefix' => 'edit'])
                                        <div class="d-flex justify-content-end gap-2 mt-3 pb-1">
                                            <button type="button" class="btn btn-sm btn-secondary" id="editAddrCancelBtn">Cancel</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="editAddrSaveBtn">Save Address</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    const bootstrapModal = globalThis.bootstrap?.Modal;
    let   deleteId    = null;

    function showModal(modalId, triggerId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        if (bootstrapModal) {
            bootstrapModal.getOrCreateInstance(modalElement).show();
            return;
        }

        document.getElementById(triggerId)?.click();
    }

    function hideModal(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        if (bootstrapModal) {
            bootstrapModal.getInstance(modalElement)?.hide();
            return;
        }

        modalElement.querySelector('[data-bs-dismiss="modal"]')?.click();
    }

    function parseJsonResponse(response) {
        return response.json().then((payload) => {
            if (!response.ok) {
                const errorMessage = payload?.message || 'Request failed.';
                throw new Error(errorMessage);
            }

            return payload;
        });
    }

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
        .then(parseJsonResponse)
        .then(res => openModal(res.data ?? res))
        .catch(err => alert(err.message || 'Unable to load customer details.'));
    });

    // ── Delete customer (delegated) ──────────────────────────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-customer-delete]');
        if (!btn) return;
        deleteId = btn.dataset.customerDelete;
        showModal('deleteCustomerModal', 'triggerDeleteCustomerModal');
    });

    document.getElementById('confirmDeleteCustomer')?.addEventListener('click', () => {
        if (!deleteId) return;
        fetch(`{{ url('/admin/customers') }}/${deleteId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(parseJsonResponse)
        .then(() => {
            hideModal('deleteCustomerModal');
            window.LaravelDataTables?.['customers-table']?.draw(false);
        })
        .catch(err => alert(err.message || 'Unable to delete customer.'));
    });

    // ── Toggle status (delegated) ─────────────────────────────────────────
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-customer-toggle]');
        if (!btn) return;
        const id = btn.dataset.customerToggle;
        fetch(`{{ url('/admin/customers') }}/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ _method: 'PATCH' }),
        })
        .then(parseJsonResponse)
        .then(() => {
            if (typeof window.DatatableUtils?.refreshDatatable === 'function') {
                window.DatatableUtils.refreshDatatable('customers-table');
                return;
            }

            if (table?.ajax?.reload) {
                table.ajax.reload(null, false);
                return;
            }

            window.LaravelDataTables?.['customers-table']?.draw(false);
        })
        .catch(err => alert(err.message || 'Unable to update customer status.'));
    });

    // ══════════════════════════════════════════════════════════════════════
    // ADD-mode address helpers (collapsed toggle, new customer only)
    // ══════════════════════════════════════════════════════════════════════
    let addressOpen = false;

    document.getElementById('toggleAddressFields')?.addEventListener('click', () => {
        addressOpen = !addressOpen;
        document.getElementById('addressFields').classList.toggle('d-none', !addressOpen);
        document.getElementById('toggleAddressLabel').textContent = addressOpen ? 'Remove Address' : 'Add Address';
        const icon = document.getElementById('toggleAddressIcon');
        icon.innerHTML = addressOpen
            ? '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0"/>'
            : '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/>';
    });

    function resetNewAddrFields() {
        addressOpen = false;
        ['newAddrLine1','newAddrLine2','newAddrCity','newAddrState','newAddrZip','newAddrMobile','newAddrLandmark']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        const c = document.getElementById('newAddrCountry'); if (c) c.value = 'India';
        const t = document.getElementById('newAddrType');    if (t) t.value = 'home';
        document.getElementById('addressFields')?.classList.add('d-none');
        const lbl = document.getElementById('toggleAddressLabel');
        if (lbl) lbl.textContent = 'Add Address';
        const icon = document.getElementById('toggleAddressIcon');
        if (icon) icon.innerHTML = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/>';
    }

    function getNewAddrPayload() {
        const line1 = document.getElementById('newAddrLine1')?.value?.trim();
        const city  = document.getElementById('newAddrCity')?.value?.trim();
        if (!line1 && !city) return null;
        return {
            address_line1: line1 || '',
            address_line2: document.getElementById('newAddrLine2')?.value?.trim() || '',
            city:          city || '',
            state:         document.getElementById('newAddrState')?.value?.trim()    || '',
            zipcode:       document.getElementById('newAddrZip')?.value?.trim()      || '',
            country:       document.getElementById('newAddrCountry')?.value?.trim()  || 'India',
            mobile:        document.getElementById('newAddrMobile')?.value?.trim()   || '',
            address_type:  document.getElementById('newAddrType')?.value            || 'home',
            landmark:      document.getElementById('newAddrLandmark')?.value?.trim() || '',
        };
    }

    // ══════════════════════════════════════════════════════════════════════
    // EDIT-mode address helpers (live list + inline form)
    // ══════════════════════════════════════════════════════════════════════

    function loadEditAddresses(customerId) {
        const list = document.getElementById('editAddrList');
        list.innerHTML = '<p class="text-secondary small py-2">Loading…</p>';
        fetch(`{{ url('/admin/customers') }}/${customerId}/addresses`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        .then(r => r.json())
        .then(res => {
            const rows = res.data ?? [];
            document.getElementById('editAddrCount').textContent = rows.length || '';
            if (!rows.length) {
                list.innerHTML = '<p class="text-secondary small py-2 mb-0">No addresses saved yet.</p>';
                return;
            }
            list.innerHTML = rows.map(addr => `
                <div class="card border mb-2 shadow-sm" data-addr-id="${addr.id}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1 min-w-0">
                                <span class="badge bg-azure-lt text-azure me-1">${addr.address_type}</span>
                                <span class="text-body small">${addr.full_address || '—'}</span>
                                ${addr.mobile ? `<span class="text-secondary small ms-2">· ${addr.mobile}</span>` : ''}
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-ghost-secondary edit-modal-addr-btn py-0 px-1"
                                        data-addr='${JSON.stringify(addr).replace(/'/g, "&#39;")}' title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost-danger delete-modal-addr-btn py-0 px-1"
                                        data-addr-id="${addr.id}" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/>
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/>
                                        <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => { list.innerHTML = '<p class="text-danger small py-2">Failed to load addresses.</p>'; });
    }

    function resetEditAddrForm() {
        document.getElementById('editAddrId').value = '';
        ['editAddrLine1','editAddrLine2','editAddrCity','editAddrState','editAddrZip','editAddrMobile','editAddrLandmark']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        const c = document.getElementById('editAddrCountry'); if (c) c.value = 'India';
        const t = document.getElementById('editAddrType');    if (t) t.value = 'home';
        document.getElementById('editAddrFormTitle').textContent = 'New Address';
        document.getElementById('editAddrFormWrap').classList.add('d-none');
    }

    function openEditAddrForm(addr) {
        document.getElementById('editAddrId').value        = addr?.id              ?? '';
        document.getElementById('editAddrLine1').value     = addr?.address_line1   ?? '';
        document.getElementById('editAddrLine2').value     = addr?.address_line2   ?? '';
        document.getElementById('editAddrCity').value      = addr?.city            ?? '';
        document.getElementById('editAddrState').value     = addr?.state           ?? '';
        document.getElementById('editAddrZip').value       = addr?.zipcode         ?? '';
        document.getElementById('editAddrCountry').value   = addr?.country         || 'India';
        document.getElementById('editAddrMobile').value    = addr?.mobile          ?? '';
        document.getElementById('editAddrType').value      = addr?.address_type    || 'home';
        document.getElementById('editAddrLandmark').value  = addr?.landmark        ?? '';
        document.getElementById('editAddrFormTitle').textContent = addr?.id ? 'Edit Address' : 'New Address';
        document.getElementById('editAddrFormWrap').classList.remove('d-none');
        document.getElementById('editAddrFormWrap').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Open blank form
    document.getElementById('editOpenAddrFormBtn')?.addEventListener('click', () => openEditAddrForm(null));

    // Close / cancel inline form
    document.getElementById('editCloseAddrFormBtn')?.addEventListener('click', resetEditAddrForm);
    document.getElementById('editAddrCancelBtn')?.addEventListener('click',  resetEditAddrForm);

    // Delegate: edit address row button
    document.getElementById('editAddrList')?.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-modal-addr-btn');
        if (editBtn) {
            try { openEditAddrForm(JSON.parse(editBtn.dataset.addr)); } catch {}
            return;
        }
        const delBtn = e.target.closest('.delete-modal-addr-btn');
        if (delBtn) {
            if (!confirm('Delete this address?')) return;
            const currentId = document.getElementById('customerId').value;
            const addrId    = delBtn.dataset.addrId;
            fetch(`{{ url('/admin/customers') }}/${currentId}/addresses/${addrId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            })
            .then(r => r.json())
            .then(() => { resetEditAddrForm(); loadEditAddresses(currentId); })
            .catch(err => alert(err.message || 'Delete failed.'));
        }
    });

    // Save inline address (add or update)
    document.getElementById('editAddrSaveBtn')?.addEventListener('click', () => {
        const customerId = document.getElementById('customerId').value;
        const addrId     = document.getElementById('editAddrId').value;
        const method     = addrId ? 'PUT' : 'POST';
        const url        = addrId
            ? `{{ url('/admin/customers') }}/${customerId}/addresses/${addrId}`
            : `{{ url('/admin/customers') }}/${customerId}/addresses`;

        const payload = {
            address_line1: document.getElementById('editAddrLine1')?.value?.trim()   || '',
            address_line2: document.getElementById('editAddrLine2')?.value?.trim()   || '',
            city:          document.getElementById('editAddrCity')?.value?.trim()     || '',
            state:         document.getElementById('editAddrState')?.value?.trim()    || '',
            zipcode:       document.getElementById('editAddrZip')?.value?.trim()      || '',
            country:       document.getElementById('editAddrCountry')?.value?.trim()  || 'India',
            mobile:        document.getElementById('editAddrMobile')?.value?.trim()   || '',
            address_type:  document.getElementById('editAddrType')?.value            || 'home',
            landmark:      document.getElementById('editAddrLandmark')?.value?.trim() || '',
        };

        if (!payload.address_line1 && !payload.city) {
            alert('Please fill in at least Address Line 1 or City.');
            return;
        }

        const btn = document.getElementById('editAddrSaveBtn');
        btn.disabled = true; btn.textContent = 'Saving…';

        fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success === false) {
                alert(Object.values(res.data ?? {}).flat().join('\n') || res.message || 'Save failed.');
                return;
            }
            resetEditAddrForm();
            loadEditAddresses(customerId);
        })
        .catch(err => alert(err.message || 'Save failed.'))
        .finally(() => { btn.disabled = false; btn.textContent = 'Save Address'; });
    });

    // ══════════════════════════════════════════════════════════════════════
    // Modal open helper
    // ══════════════════════════════════════════════════════════════════════
    function openModal(customer) {
        const isEdit = !!customer;
        document.getElementById('customerModalTitle').textContent =
            isEdit ? '{{ __('labels.edit_customer') }}' : '{{ __('labels.add_customer') }}';
        document.getElementById('customerId').value          = customer?.id     ?? '';
        document.getElementById('customerName').value        = customer?.name   ?? '';
        document.getElementById('customerEmail').value       = customer?.email  ?? '';
        document.getElementById('customerMobile').value      = customer?.mobile ?? '';
        document.getElementById('customerCompanyName').value = customer?.company_name ?? '';
        document.getElementById('customerStatus').value      = customer?.status != null ? (customer.status ? '1' : '0') : '1';
        document.getElementById('customerGstin').value       = customer?.gstin  ?? '';
        document.getElementById('customerPassword').value    = '';
        document.getElementById('customerPassword').required = !isEdit;
        document.getElementById('passwordLabel').classList.toggle('required', !isEdit);
        document.getElementById('passwordHint').classList.toggle('d-none', !isEdit);

        // Toggle which address UI to show
        document.getElementById('addressAddSection').classList.toggle('d-none',  isEdit);
        document.getElementById('addressEditSection').classList.toggle('d-none', !isEdit);

        if (!isEdit) {
            resetNewAddrFields();
        } else {
            resetEditAddrForm();
            loadEditAddresses(customer.id);
        }

        showModal('customerModal', 'triggerCustomerModal');
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
            company_name: document.getElementById('customerCompanyName').value.trim() || null,
            status:   document.getElementById('customerStatus').value,
            password: document.getElementById('customerPassword').value || undefined,
            gstin:    document.getElementById('customerGstin').value.trim().toUpperCase() || null,
        };

        const saveBtn = document.getElementById('customerSaveBtn');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

        fetch(url, {
            method,
            headers: {
                'Accept':       'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
        })
        .then(parseJsonResponse)
        .then(res => {
            if (res.success === false) {
                alert(Object.values(res.data ?? {}).flat().join('\n') || res.message);
                return;
            }

            // If creating a new customer and address fields were filled, post address too
            const newCustomerId = res.data?.id ?? null;
            const addressPayload = !id && newCustomerId ? getNewAddrPayload() : null;

            if (addressPayload) {
                return fetch(`{{ url('/admin/customers') }}/${newCustomerId}/addresses`, {
                    method: 'POST',
                    headers: {
                        'Accept':       'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(addressPayload),
                })
                .then(r => r.json())
                .then(() => {
                    hideModal('customerModal');
                    window.LaravelDataTables?.['customers-table']?.draw(false);
                });
            }

            hideModal('customerModal');
            window.LaravelDataTables?.['customers-table']?.draw(false);
        })
        .catch(err => alert(err.message || 'Unable to save customer.'))
        .finally(() => {
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '{{ __('labels.save') }}'; }
        });
    });
})();
</script>
@endpush
