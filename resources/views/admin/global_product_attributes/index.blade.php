@php use App\Enums\Attribute\AttributeTypesEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['attributes']['active'] ?? ""])

@section('title', __('labels.attributes'))

@section('header_data')
    @php
        $page_title    = __('labels.attributes');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),       'url' => route('admin.dashboard')],
        ['title' => __('labels.attributes'), 'url' => ''],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.attributes') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                <select class="form-select text-capitalize" id="typeFilter">
                                    <option value="">{{ __('labels.transaction_type') }}</option>
                                    @foreach(AttributeTypesEnum::values() as $value)
                                        <option value="{{ $value }}">{{ Str::replace('_', ' ', $value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto ms-auto">
                                @if($createPermission)
                                    <div class="btn-list">
                                        <span class="d-flex flex-column flex-md-row gap-1">
                                            <button type="button" class="btn bg-primary-lt" data-bs-toggle="modal"
                                                    data-bs-target="#attribute-create-update-modal">
                                                <i class="ti ti-plus fs-3"></i>
                                                {{ __('labels.create_attribute') }}
                                            </button>
                                            <button type="button" class="btn bg-indigo-lt" data-bs-toggle="modal"
                                                    data-bs-target="#attribute-value-create-update-modal">
                                                <i class="ti ti-plus fs-3"></i>
                                                {{ __('labels.create_attribute_value') }}
                                            </button>
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-outline-primary" id="refresh">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round"
                                         class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
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
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <a href="#tabs-attributes" class="nav-link active" data-bs-toggle="tab">
                                {{ __('labels.attributes') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tabs-attribute-values" class="nav-link" data-bs-toggle="tab">
                                {{ __('labels.attribute_values') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active show" id="tabs-attributes">
                            <x-datatable id="attributes-table" :columns="$columns"
                                         route="{{ route('admin.attributes.datatable') }}"
                                         :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                        </div>
                        <div class="tab-pane" id="tabs-attribute-values">
                            <div class="mb-3 d-flex align-items-center gap-2" id="attr-values-filter-bar" style="display:none!important">
                                <span class="text-muted small">Filtering by attribute:</span>
                                <span class="badge bg-blue text-white" id="attr-values-filter-label"></span>
                                <a href="#" class="text-muted small" id="attr-values-clear-filter">
                                    <i class="ti ti-x"></i> Clear filter
                                </a>
                            </div>
                            <x-datatable id="attribute-values-table" :columns="$valuesColumns"
                                         route="{{ route('admin.attributes.values.datatable') }}"
                                         :options="['order' => [[0, 'desc']], 'pageLength' => 10]"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(($createPermission ?? false) || ($editPermission ?? false))
        {{-- Create / Edit Attribute Modal --}}
        <div class="modal modal-blur fade" id="attribute-create-update-modal" tabindex="-1"
             role="dialog" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit" action="{{ route('admin.attributes.store') }}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_new_attribute') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="title">{{ __('labels.title') }}</label>
                                <input type="text" id="title" name="title" class="form-control"
                                       placeholder="{{ __('labels.attribute_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required" for="label">{{ __('labels.label') }}</label>
                                <input type="text" id="label" name="label" class="form-control"
                                       placeholder="{{ __('labels.label') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required" for="swatche_type">{{ __('labels.swatche_type') }}</label>
                                <select class="form-select" id="swatche_type" name="swatche_type" required>
                                    <option value="" selected>{{ __('labels.select_type') }}</option>
                                    @foreach($attributeTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-plus me-1"></i>{{ __('labels.create_new_attribute') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Create / Edit Attribute Value Modal --}}
        <div class="modal modal-blur fade" id="attribute-value-create-update-modal" tabindex="-1"
             role="dialog" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit attribute-value-form"
                          action="{{ route('admin.attributes.values.store') }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_attribute_value') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="attribute_id" class="form-label required">{{ __('labels.attribute') }}</label>
                                    <select class="form-select" id="attribute_id" name="attribute_id" required>
                                        <option value="">{{ __('form.selectAttribute') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div id="dynamic-fields-container">
                                <div class="field-group mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row align-items-end">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label required">{{ __('form.value') }}</label>
                                                        <input type="text" name="values[]" class="form-control"
                                                               placeholder="{{ __('labels.eg_red') }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3 swatche-value-container">
                                                        <label class="form-label required">{{ __('form.swatcheValue') }}</label>
                                                        <input type="text" name="swatche_value[]"
                                                               class="form-control swatche-value"
                                                               placeholder="{{ __('labels.enter_swatche_value') }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="mb-3 text-end">
                                                        <button type="button" class="btn btn-danger btn-sm remove-field"
                                                                style="display: none;">
                                                            <i class="ti ti-minus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-success w-100" id="add-more-fields">
                                        <i class="ti ti-plus"></i> {{ __('labels.add_more_values') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-plus me-1"></i>{{ __('labels.create_new_attribute_value') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ hyperAsset('assets/js/attribute.js') }}" defer></script>
    <script>
        // Point attribute search to admin endpoint
        window._attributeSearchUrl = "{{ route('admin.attributes.search') }}";
        // Point attribute edit/update/delete actions to admin endpoints
        window._attributeBaseUrl    = "{{ url('admin/attributes') }}";
        window._attributeValueBaseUrl = "{{ url('admin/attributes/values') }}";

        document.getElementById('refresh')?.addEventListener('click', () => {
            window.DatatableUtils?.refreshDatatable('attributes-table');
            window.DatatableUtils?.refreshDatatable('attribute-values-table');
        });

        // Click on values count badge → switch to Attribute Values tab filtered by that attribute
        document.addEventListener('click', function (e) {
            const link = e.target.closest('.view-attr-values');
            if (!link) return;
            e.preventDefault();

            const attrId    = link.dataset.attrId;
            const attrTitle = link.dataset.attrTitle;

            // Switch to Attribute Values tab
            const tabEl = document.querySelector('a[href="#tabs-attribute-values"]');
            if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();

            // Show filter bar
            const filterBar   = document.getElementById('attr-values-filter-bar');
            const filterLabel = document.getElementById('attr-values-filter-label');
            filterBar.style.removeProperty('display');
            filterBar.style.display = 'flex';
            filterLabel.textContent = attrTitle;
            filterBar.dataset.attrId = attrId;

            // Reload datatable with attribute_id param
            const jq = window.jQuery;
            if (jq && jq.fn.DataTable.isDataTable('#attribute-values-table')) {
                const dt = jq('#attribute-values-table').DataTable();
                dt.settings()[0].ajax.data = function (d) { d.attribute_id = attrId; };
                dt.draw();
            }
        });

        // Clear filter
        document.getElementById('attr-values-clear-filter')?.addEventListener('click', function (e) {
            e.preventDefault();
            const filterBar = document.getElementById('attr-values-filter-bar');
            filterBar.style.display = 'none';
            filterBar.dataset.attrId = '';

            const jq = window.jQuery;
            if (jq && jq.fn.DataTable.isDataTable('#attribute-values-table')) {
                const dt = jq('#attribute-values-table').DataTable();
                dt.settings()[0].ajax.data = function (d) { delete d.attribute_id; };
                dt.draw();
            }
        });
    </script>
@endpush
