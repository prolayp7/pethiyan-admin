@extends('layouts.admin.app', ['page' => $menuAdmin['trash']['active'] ?? ""])

@section('title', __('labels.trash'))

@section('header_data')
    @php
        $page_title = __('labels.trash');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.trash'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.trash') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                </div>

                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                    @if($canViewCategories)
                        <li class="nav-item" role="presentation">
                            <a href="#tab-trashed-categories" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">{{ __('labels.trashed_categories') }}</a>
                        </li>
                    @endif
                    @if($canViewProducts)
                        <li class="nav-item" role="presentation">
                            <a href="#tab-trashed-products" class="nav-link {{ $canViewCategories ? '' : 'active' }}" data-bs-toggle="tab" aria-selected="false" role="tab">{{ __('labels.trashed_products') }}</a>
                        </li>
                    @endif
                </ul>

                <div class="card-table tab-content">
                    @if($canViewCategories)
                        <div class="tab-pane active show p-3" id="tab-trashed-categories" role="tabpanel">
                            <x-datatable id="trashed-categories-table" :columns="$categoryColumns"
                                         route="{{ route('admin.categories.datatable') }}"
                                         :options="['ordering' => false, 'paging' => true]"/>
                        </div>
                    @endif
                    @if($canViewProducts)
                        <div class="tab-pane {{ $canViewCategories ? '' : 'active show' }} p-3" id="tab-trashed-products" role="tabpanel">
                            <x-datatable id="trashed-products-table" :columns="$productColumns"
                                         route="{{ route('admin.products.datatable') }}"
                                         :options="['ordering' => false, 'paging' => true]"/>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        function handleRestore(url) {
            return $.ajax({
                url: url,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
            });
        }

        $(document).on('click', '.restore-category-btn', function () {
            const id = $(this).data('id');
            const url = '{{ route('admin.categories.restore', ':id') }}'.replace(':id', id);
            handleRestore(url).done((response) => {
                if (!response.success) {
                    toastError(response.message || 'Unable to restore category.');
                    return;
                }
                toastSuccess(response.message || 'Category restored.');
                $('#trashed-categories-table').DataTable().ajax.reload(null, false);
            }).fail(() => toastError('Unable to restore category.'));
        });

        $(document).on('click', '.restore-product-btn', function () {
            const id = $(this).data('id');
            const url = '{{ route('admin.products.restore', ':id') }}'.replace(':id', id);
            handleRestore(url).done((response) => {
                if (!response.success) {
                    toastError(response.message || 'Unable to restore product.');
                    return;
                }
                toastSuccess(response.message || 'Product restored.');
                $('#trashed-products-table').DataTable().ajax.reload(null, false);
            }).fail(() => toastError('Unable to restore product.'));
        });
    })();
</script>
@endpush
