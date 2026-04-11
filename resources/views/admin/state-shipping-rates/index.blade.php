@extends('layouts.admin.app', ['page' => $menuAdmin['state_shipping_rates']['active'] ?? '', 'sub_page' => ''])

@section('title', 'Shipping Tariffs')

@section('header_data')
    @php $page_title = 'Shipping Tariffs'; $page_pretitle = 'Logistics'; @endphp
@endsection

@php
$breadcrumbs = [
    ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
    ['title' => 'Shipping Tariffs', 'url' => null],
];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Shipping Tariffs</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRateModal">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Shipping Tariff
            </button>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        {{-- Info card --}}
        <div class="alert alert-info mb-4">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                </div>
                <div>
                    Configure shipping tariffs by <strong>delivery partner + pin zone</strong>. These values can be used for courier-cost estimation and checkout shipping logic.
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Delivery Partners</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th>Status</th>
                                <th class="text-end">Toggle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($partners as $partner)
                                <tr>
                                    <td>{{ $partner->name }}</td>
                                    <td>
                                        @if($partner->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <label class="form-check form-switch m-0 d-inline-block">
                                            <input
                                                class="form-check-input js-partner-toggle"
                                                type="checkbox"
                                                data-id="{{ $partner->id }}"
                                                @checked($partner->is_active)
                                            >
                                        </label>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">No delivery partners found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">All Shipping Tariffs</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="ratesTable" class="table table-vcenter card-table datatable">
                        <thead>
                            <tr>
                                @foreach($columns as $col)
                                    <th>{{ $col['title'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ====================== ADD MODAL ====================== --}}
<div class="modal modal-blur fade" id="addRateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Shipping Tariff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRateForm" class="form-submit" action="{{ route('admin.state-shipping-rates.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('admin.state-shipping-rates._form', ['rate' => null, 'partners' => $partners, 'zones' => $zones])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Tariff</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================== EDIT MODAL ====================== --}}
<div class="modal modal-blur fade" id="editRateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Shipping Tariff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRateForm" class="form-submit" method="POST">
                @csrf
                <div class="modal-body" id="editRateBody">
                    @include('admin.state-shipping-rates._form', ['rate' => null, 'partners' => $partners, 'zones' => $zones])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Tariff</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.addEventListener('load', function () {
@php
    $dtColumns = array_map(fn($c) => ['data' => $c['data'], 'name' => $c['name'], 'orderable' => $c['orderable'] ?? true, 'searchable' => $c['searchable'] ?? true], $columns);
@endphp
const dtColumns = @json($dtColumns);

const table = $('#ratesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route('admin.state-shipping-rates.datatable') }}',
    columns: dtColumns,
    order: [[0, 'asc']],
});

// ---- Edit ----
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-rate');
    if (!btn) return;

    const d = btn.dataset;
    const form = document.getElementById('editRateForm');
    form.action = `/admin/state-shipping-rates/${d.id}`;

    Object.entries({
        delivery_partner_id: d.delivery_partner_id,
        zone_id: d.zone_id,
        upto_250: d.upto_250,
        upto_500: d.upto_500,
        every_500: d.every_500,
        per_kg: d.per_kg,
        kg_2: d.kg_2,
        above_5_surface: d.above_5_surface,
        above_5_air: d.above_5_air,
        fuel_surcharge_percent: d.fuel_surcharge_percent,
        gst_percent: d.gst_percent,
        is_active: d.is_active, notes: d.notes,
    }).forEach(([k, v]) => {
        const el = form.querySelector(`[name="${k}"]`);
        if (!el) return;
        if (el.type === 'checkbox') { el.checked = v == '1'; }
        else { el.value = v ?? ''; }
    });

    new bootstrap.Modal(document.getElementById('editRateModal')).show();
});

// ---- Delete ----
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete-rate');
    if (!btn) return;
    if (!confirm(`Delete shipping tariff for "${btn.dataset.name}"?`)) return;

    fetch(`/admin/state-shipping-rates/${btn.dataset.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    }).then(r => r.json()).then(res => {
        if (res.success) { table.ajax.reload(); }
        else { alert(res.message || 'Delete failed.'); }
    });
});

// ---- Partner status toggle ----
document.addEventListener('change', function (e) {
    const toggle = e.target.closest('.js-partner-toggle');
    if (!toggle) return;

    const previous = !toggle.checked;
    fetch(`/admin/state-shipping-rates/partners/${toggle.dataset.id}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    }).then(r => r.json()).then((res) => {
        if (!res.success) {
            toggle.checked = previous;
            alert(res.message || 'Failed to update partner status.');
            return;
        }
        location.reload();
    }).catch(() => {
        toggle.checked = previous;
        alert('Failed to update partner status.');
    });
});

// ---- Form submit (add + edit) ----
document.querySelectorAll('.form-submit').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = new FormData(form);
        fetch(form.action, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
                    table.ajax.reload();
                    form.reset();
                } else {
                    alert(res.message || 'Error saving tariff.');
                }
            });
    });
});
}); // window load
</script>
@endpush
