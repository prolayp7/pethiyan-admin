@extends('layouts.admin.app', ['page' => 'pin_service', 'sub_page' => ''])

@section('title', 'Pin Service Areas')

@section('header_data')
    @php
        $page_title    = 'Pin Service Areas';
        $page_pretitle = 'Settings';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),     'url' => route('admin.dashboard')],
        ['title' => 'Settings',            'url' => route('admin.settings.index')],
        ['title' => 'Pin Service Areas',   'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Pin Service Areas</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="col-auto ms-auto">
            <div class="d-flex gap-2">
                <a href="{{ route('admin.pin-service.masters.index') }}" class="btn btn-outline-primary">
                    Manage Districts/Cities
                </a>
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                    Import CSV
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Pincode
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Total Pincodes</div>
                        <div class="h1 mb-0">{{ number_format($totalPins) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Serviceable</div>
                        <div class="h1 mb-0 text-green">{{ number_format($serviceable) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Non-Serviceable</div>
                        <div class="h1 mb-0 text-red">{{ number_format($totalPins - $serviceable) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">States Covered</div>
                        <div class="h1 mb-0">{{ $states->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Filter by State</label>
                        <select class="form-select" id="filterState">
                            <option value="">All States</option>
                            @foreach($states as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Filter by Zone</label>
                        <select class="form-select" id="filterZone">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone => $label)
                                <option value="{{ $zone }}">Zone {{ $zone }} ({{ $label }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Serviceability</label>
                        <select class="form-select" id="filterServiceable">
                            <option value="">All</option>
                            <option value="1">Serviceable</option>
                            <option value="0">Non-Serviceable</option>
                        </select>
                    </div>
                    <div class="col-md-auto d-flex align-items-end gap-2">
                        <button class="btn btn-success" id="bulkEnable" disabled>Enable Selected</button>
                        <button class="btn btn-danger"  id="bulkDisable" disabled>Disable Selected</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-body p-0">
                <table id="pinTable" class="table table-vcenter table-hover card-table" style="width:100%">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>Pincode</th>
                            <th>State</th>
                            <th>District</th>
                            <th>City</th>
                            <th>Zone</th>
                            <th>Delivery Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal modal-blur fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalTitle">Add Pincode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label required">Pincode</label>
                        <input type="text" class="form-control" id="fPincode" maxlength="6" placeholder="e.g. 400001">
                    </div>
                    <div class="col-6">
                        <label class="form-label required">Zone</label>
                        <select class="form-select" id="fZone">
                            @foreach($zones as $z => $label)
                                <option value="{{ $z }}">{{ $z }} — {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label required">State</label>
                        <select class="form-select" id="fState">
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label required">District</label>
                        <select class="form-select" id="fDistrict" disabled>
                            <option value="">Select District</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label required">City</label>
                        <select class="form-select" id="fCity" disabled>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Region</label>
                        <select class="form-select" id="fZone1">
                            <option value="">Select Region</option>
                            @foreach($regions as $region)
                                <option value="{{ $region }}">{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Delivery Time</label>
                        <input type="text" class="form-control" id="fDeliveryTime" placeholder="e.g. 3-4 Days">
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" id="fServiceable" checked>
                            <span class="form-check-label">Serviceable</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBtn">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal modal-blur fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    CSV must have columns: <code>pincode, state, district, city, zone, zone1, dtime</code><br>
                    Existing pincodes will be updated. New ones will be added.
                </div>
                <div class="mb-3">
                    <label class="form-label required">CSV File</label>
                    <input type="file" class="form-control" id="importFile" accept=".csv,.txt">
                </div>
                <div id="importProgress" class="d-none">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-indeterminate"></div>
                    </div>
                    <small class="text-muted">Importing, please wait…</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="importBtn">Import</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.addEventListener('load', function () {
(function () {
    const dtUrl      = '{{ route('admin.pin-service.datatable') }}';
    const storeUrl   = '{{ route('admin.pin-service.store') }}';
    const toggleUrl  = (id) => `/admin/pin-service/${id}/toggle`;
    const updateUrl  = (id) => `/admin/pin-service/${id}`;
    const deleteUrl  = (id) => `/admin/pin-service/${id}`;
    const bulkUrl    = '{{ route('admin.pin-service.bulk-toggle') }}';
    const importUrl  = '{{ route('admin.pin-service.import') }}';
    const districtsUrl = '{{ route('admin.pin-service.districts') }}';
    const citiesUrl    = '{{ route('admin.pin-service.cities') }}';
    const csrf       = document.querySelector('meta[name="csrf-token"]').content;

    const zoneBadge = { A: 'bg-green', B: 'bg-teal', C: 'bg-cyan', D: 'bg-yellow', E: 'bg-orange' };

    const table = $('#pinTable').DataTable({
        processing : true,
        serverSide : true,
        dom        : "<'row g-2 align-items-center px-3 pt-3'<'col-md-6'l><'col-md-6 d-flex justify-content-md-end'f>>" +
                     "rt" +
                     "<'row g-2 align-items-center px-3 pb-3'<'col-md-6'i><'col-md-6 d-flex justify-content-md-end'p>>",
        language   : {
            lengthMenu: '_MENU_ entries per page',
            search: 'Search:',
            searchPlaceholder: 'Pincode, state, district, city',
        },
        ajax       : {
            url  : dtUrl,
            data : (d) => {
                d.filter_state       = $('#filterState').val();
                d.filter_zone        = $('#filterZone').val();
                d.filter_serviceable = $('#filterServiceable').val();
            }
        },
        columns: [
            {
                data: 'id', orderable: false, searchable: false,
                render: (d) => `<input type="checkbox" class="form-check-input row-check" value="${d}">`
            },
            { data: 'pincode' },
            { data: 'state' },
            { data: 'district' },
            { data: 'city' },
            {
                data: 'zone', orderable: true,
                render: (d) => `<span class="badge ${zoneBadge[d] ?? 'bg-secondary'}">Zone ${d}</span>`
            },
            { data: 'delivery_time' },
            {
                data: 'is_serviceable', orderable: true,
                render: (d, t, row) =>
                    `<label class="form-check form-switch mb-0">
                        <input class="form-check-input svc-toggle" type="checkbox" data-id="${row.id}" ${d ? 'checked' : ''}>
                    </label>`
            },
            {
                data: null, orderable: false, searchable: false,
                render: (d, t, row) =>
                    `<div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${row.id}">Edit</button>
                        <button class="btn btn-sm btn-outline-danger del-btn" data-id="${row.id}">Delete</button>
                    </div>`
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
    });

    // Re-draw on filter change
    ['#filterState','#filterZone','#filterServiceable'].forEach(sel => {
        document.querySelector(sel).addEventListener('change', () => table.ajax.reload());
    });

    // Serviceable toggle
    $('#pinTable').on('change', '.svc-toggle', function () {
        const id = $(this).data('id');
        fetch(toggleUrl(id), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
            .then(r => r.json())
            .then(res => { if (!res.success) alert('Toggle failed'); });
    });

    // Select all
    document.getElementById('selectAll').addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
        updateBulkButtons();
    });
    $('#pinTable').on('change', '.row-check', updateBulkButtons);

    function updateBulkButtons() {
        const any = !!document.querySelector('.row-check:checked');
        document.getElementById('bulkEnable').disabled  = !any;
        document.getElementById('bulkDisable').disabled = !any;
    }

    function selectedIds() {
        return [...document.querySelectorAll('.row-check:checked')].map(cb => +cb.value);
    }

    document.getElementById('bulkEnable').addEventListener('click',  () => bulkToggle(true));
    document.getElementById('bulkDisable').addEventListener('click', () => bulkToggle(false));

    function bulkToggle(serviceable) {
        const ids = selectedIds();
        if (!ids.length) return;
        fetch(bulkUrl, {
            method : 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body   : JSON.stringify({ ids, is_serviceable: serviceable }),
        }).then(() => table.ajax.reload());
    }

    // Add / Edit
    const addModal = document.getElementById('addModal');
    let isEdit = false;

    const fState = document.getElementById('fState');
    const fDistrict = document.getElementById('fDistrict');
    const fCity = document.getElementById('fCity');

    function setSelectOptions(selectEl, values, placeholder) {
        const safeValues = Array.isArray(values) ? values : [];
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        safeValues.forEach((value) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            selectEl.appendChild(opt);
        });
        selectEl.disabled = safeValues.length === 0;
    }

    async function loadDistricts(state, selectedDistrict = '') {
        setSelectOptions(fDistrict, [], 'Loading districts...');
        setSelectOptions(fCity, [], 'Select City');
        if (!state) {
            setSelectOptions(fDistrict, [], 'Select District');
            setSelectOptions(fCity, [], 'Select City');
            return;
        }

        const url = `${districtsUrl}?state=${encodeURIComponent(state)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } }).then(r => r.json()).catch(() => null);
        const districts = res?.data ?? [];
        setSelectOptions(fDistrict, districts, 'Select District');
        if (selectedDistrict) fDistrict.value = selectedDistrict;
    }

    async function loadCities(state, district, selectedCity = '') {
        setSelectOptions(fCity, [], 'Loading cities...');
        if (!state || !district) {
            setSelectOptions(fCity, [], 'Select City');
            return;
        }

        const url = `${citiesUrl}?state=${encodeURIComponent(state)}&district=${encodeURIComponent(district)}`;
        const res = await fetch(url, { headers: { Accept: 'application/json' } }).then(r => r.json()).catch(() => null);
        const cities = res?.data ?? [];
        setSelectOptions(fCity, cities, 'Select City');
        if (selectedCity) fCity.value = selectedCity;
    }

    fState.addEventListener('change', async () => {
        await loadDistricts(fState.value);
    });

    fDistrict.addEventListener('change', async () => {
        await loadCities(fState.value, fDistrict.value);
    });

    document.getElementById('saveBtn').addEventListener('click', () => {
        const id    = document.getElementById('editId').value;
        const url   = isEdit ? updateUrl(id) : storeUrl;
        const body  = {
            pincode       : document.getElementById('fPincode').value,
            state         : document.getElementById('fState').value,
            district      : document.getElementById('fDistrict').value,
            city          : document.getElementById('fCity').value,
            zone          : document.getElementById('fZone').value,
            zone1         : document.getElementById('fZone1').value,
            delivery_time : document.getElementById('fDeliveryTime').value,
            is_serviceable: document.getElementById('fServiceable').checked ? 1 : 0,
        };

        fetch(url, {
            method : 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body   : JSON.stringify(body),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success || res.pincode) {
                bootstrap.Modal.getInstance(addModal).hide();
                table.ajax.reload();
            } else {
                alert(Object.values(res.errors ?? {}).flat().join('\n'));
            }
        });
    });

    $('#pinTable').on('click', '.edit-btn', function () {
        const id = $(this).data('id');
        fetch(`/admin/pin-service/${id}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(p => {
                isEdit = true;
                document.getElementById('addModalTitle').textContent = 'Edit Pincode';
                document.getElementById('editId').value         = p.id;
                document.getElementById('fPincode').value       = p.pincode;
                document.getElementById('fState').value         = p.state ?? '';
                loadDistricts(p.state ?? '', p.district ?? '').then(() => {
                    loadCities(p.state ?? '', p.district ?? '', p.city ?? '');
                });
                document.getElementById('fZone').value          = p.zone;
                document.getElementById('fZone1').value         = p.zone1 ?? '';
                document.getElementById('fDeliveryTime').value  = p.delivery_time ?? '';
                document.getElementById('fServiceable').checked = !!p.is_serviceable;
                new bootstrap.Modal(addModal).show();
            });
    });

    addModal.addEventListener('hidden.bs.modal', () => {
        isEdit = false;
        document.getElementById('addModalTitle').textContent = 'Add Pincode';
        document.getElementById('editId').value = '';
        ['fPincode','fState','fZone1','fDeliveryTime'].forEach(id => {
            document.getElementById(id).value = '';
        });
        setSelectOptions(fDistrict, [], 'Select District');
        setSelectOptions(fCity, [], 'Select City');
        document.getElementById('fZone').value          = 'A';
        document.getElementById('fServiceable').checked = true;
    });

    // Delete
    $('#pinTable').on('click', '.del-btn', function () {
        if (!confirm('Delete this pincode?')) return;
        const id = $(this).data('id');
        fetch(deleteUrl(id), {
            method : 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(() => table.ajax.reload());
    });

    // Import
    document.getElementById('importBtn').addEventListener('click', () => {
        const file = document.getElementById('importFile').files[0];
        if (!file) { alert('Select a CSV file.'); return; }

        const fd = new FormData();
        fd.append('csv_file', file);
        fd.append('_token', csrf);

        document.getElementById('importProgress').classList.remove('d-none');
        document.getElementById('importBtn').disabled = true;

        fetch(importUrl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                document.getElementById('importProgress').classList.add('d-none');
                document.getElementById('importBtn').disabled = false;
                if (res.success) {
                    alert(`Imported ${res.imported} pincodes successfully.`);
                    bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                    table.ajax.reload();
                } else {
                    alert('Import failed.');
                }
            });
    });
})();
}); // window load
</script>
@endpush
