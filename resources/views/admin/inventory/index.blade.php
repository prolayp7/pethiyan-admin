@extends('layouts.admin.app', ['page' => 'inventory'])

@section('title', 'Inventory Management')

@section('header_data')
    @php
        $page_title   = 'Inventory';
        $page_pretitle = 'Stock Management';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Inventory', 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Stock Levels</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions d-flex gap-2">
                        <select class="form-select" id="stockFilter" style="width:160px">
                            <option value="">All Stock</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock (≤10)</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table" id="inventoryTable">
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
</div>

{{-- Edit Stock Modal --}}
<div class="modal modal-blur fade" id="editStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-1" id="modalProductName"></p>
                <p class="text-muted small mb-3" id="modalVariantName"></p>
                <div class="mb-3">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" class="form-control" id="stockInput" min="0" placeholder="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStockBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.addEventListener('load', function () {
    let currentSpvId = null;

    const table = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.inventory.datatable') }}',
            data: function (d) {
                d.stock_filter = $('#stockFilter').val();
            }
        },
        columns: @json($columns),
        order: [[6, 'asc']],
        pageLength: 25,
    });

    $('#stockFilter').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.btn-edit-stock', function () {
        currentSpvId = $(this).data('id');
        $('#modalProductName').text($(this).data('product'));
        $('#modalVariantName').text($(this).data('variant'));
        $('#stockInput').val($(this).data('stock'));
        $('#saveStockBtn').prop('disabled', false).text('Save');
        $('#editStockModal').modal('show');
    });

    const $saveStockBtn = $('#saveStockBtn');
    const saveStockBtnText = $saveStockBtn.text().trim();

    function setSaveStockLoadingState(isLoading) {
        if (isLoading) {
            $saveStockBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');
        } else {
            $saveStockBtn.prop('disabled', false).text(saveStockBtnText);
        }
    }

    $('#saveStockBtn').on('click', function () {
        if (!currentSpvId) return;
        const stock = parseInt($('#stockInput').val());
        if (isNaN(stock) || stock < 0) {
            showToastr('error', 'Please enter a valid stock quantity.');
            return;
        }

        setSaveStockLoadingState(true);

        $.ajax({
            url: '/admin/inventory/' + currentSpvId + '/stock',
            method: 'POST',
            data: { stock: stock, _token: '{{ csrf_token() }}' },
            success: function (res) {
                const success = typeof res.success !== 'undefined' ? res.success : res.status;
                if (success) {
                    $('#editStockModal').modal('hide');
                    table.ajax.reload(null, false);
                    showToastr('success', res.message || 'Stock updated successfully.');
                } else {
                    showToastr('error', res.message || 'Unable to update stock.');
                }
            },
            error: function () {
                showToastr('error', 'Failed to update stock.');
            },
            complete: function () {
                setSaveStockLoadingState(false);
            }
        });
    });
}); // end window.addEventListener load
</script>
@endpush
