@extends('layouts.admin.app', ['page' => 'gift_cards'])

@section('title', 'Gift Cards')

@section('header_data')
    @php
        $page_title   = 'Gift Cards';
        $page_pretitle = 'List';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Gift Cards', 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Gift Cards</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <select class="form-select" id="usedFilter">
                                    <option value="">All Status</option>
                                    <option value="0">Active / Unused</option>
                                    <option value="1">Used</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#gc-modal">
                                    <i class="ti ti-plus fs-5 me-1"></i> Add Gift Card
                                </button>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-outline-primary" id="refresh">
                                    <i class="ti ti-refresh fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="gift-cards-table" :columns="$columns"
                                     route="{{ route('admin.gift-cards.datatable') }}"
                                     :options="['order' => [[0, 'desc']], 'pageLength' => 15]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Add / Edit Modal ─────────────────────────────────────────────────────── --}}
<div class="modal modal-blur fade" id="gc-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gc-modal-title">Add Gift Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="gc-form">
                @csrf
                <input type="hidden" id="gc-id" value="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Title</label>
                            <input type="text" class="form-control" id="gc-title" name="title"
                                   placeholder="e.g. Summer Sale Gift Card" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Discount Amount (₹)</label>
                            <input type="number" class="form-control" id="gc-discount" name="discount"
                                   min="0" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Minimum Order Amount (₹)</label>
                            <input type="number" class="form-control" id="gc-min-order" name="minimum_order_amount"
                                   min="0" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" class="form-control" id="gc-start" name="start_time">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid Until</label>
                            <input type="datetime-local" class="form-control" id="gc-end" name="end_time">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="ti ti-device-floppy me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const table = $('#gift-cards-table');
    const csrf  = '{{ csrf_token() }}';

    // ── Filters ───────────────────────────────────────────────────────────────
    table.on('preXhr.dt', function (e, settings, data) {
        data.used = $('#usedFilter').val();
    });
    $('#usedFilter').on('change', () => table.DataTable().ajax.reload());
    $('#refresh').on('click',     () => table.DataTable().ajax.reload());

    // ── Open edit modal ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function () {
        const url = $(this).data('url');
        $.get(url, function (res) {
            if (!res.success) { alert('Failed to load gift card.'); return; }
            const c = res.data;
            $('#gc-id').val(c.id);
            $('#gc-title').val(c.title);
            $('#gc-discount').val(c.discount);
            $('#gc-min-order').val(c.minimum_order_amount);
            $('#gc-start').val(c.start_time ? c.start_time.substring(0,16) : '');
            $('#gc-end').val(c.end_time ? c.end_time.substring(0,16) : '');
            $('#gc-modal-title').text('Edit Gift Card');
            new bootstrap.Modal('#gc-modal').show();
        });
    });

    // ── Reset modal on open ───────────────────────────────────────────────────
    $('#gc-modal').on('show.bs.modal', function (e) {
        if (!$(e.relatedTarget).length) return; // opened by JS edit
        $('#gc-id').val('');
        $('#gc-form')[0].reset();
        $('#gc-modal-title').text('Add Gift Card');
    });

    // ── Submit form ────────────────────────────────────────────────────────────
    $('#gc-form').on('submit', function (e) {
        e.preventDefault();
        const id  = $('#gc-id').val();
        const url = id
            ? '{{ route('admin.gift-cards.update', ['id' => '__ID__']) }}'.replace('__ID__', id)
            : '{{ route('admin.gift-cards.store') }}';

        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: $(this).serialize(),
            success: function (res) {
                if (res.success) {
                    bootstrap.Modal.getInstance('#gc-modal')?.hide();
                    table.DataTable().ajax.reload();
                } else {
                    alert(res.message || 'Failed to save gift card.');
                }
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                alert(errors ? Object.values(errors).flat().join('\n') : 'An error occurred.');
            }
        });
    });

    // ── Delete ─────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        if (!confirm('Delete this gift card?')) return;
        $.ajax({
            url: $(this).data('url'), type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (res) {
                if (res.success) table.DataTable().ajax.reload();
                else alert(res.message);
            }
        });
    });
})();
</script>
@endpush
