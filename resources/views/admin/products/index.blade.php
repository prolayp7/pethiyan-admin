@php
    use App\Enums\Product\ProductVarificationStatusEnum;
    $isApprovalView = request()->query('verification_status') === ProductVarificationStatusEnum::PENDING();
    $productSubPage = $menuAdmin['products']['route']['products']['sub_active'] ?? 'products';
    if ($isApprovalView && isset($menuAdmin['products']['route']['pending_approval_products']['sub_active'])) {
        $productSubPage = $menuAdmin['products']['route']['pending_approval_products']['sub_active'];
    }
@endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? "", 'sub_page' => $productSubPage])


@section('title', __('labels.products'))

@section('header_data')
    @php
        $page_title = __('labels.products');
        $page_pretitle = __('labels.admin') . " " . __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.products'), 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">{{ __('labels.products') }}</h3>
                                <x-breadcrumb :items="$breadcrumbs"/>
                            </div>
                            <div class="card-actions">
                                <div class="row g-2">
                                    @if($createPermission)
                                    <div class="col-auto">
                                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M12 5l0 14"/>
                                                <path d="M5 12l14 0"/>
                                            </svg>
                                            {{ __('labels.add_product') }}
                                        </a>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#product-import-modal">
                                            {{ __('Import Products') }}
                                        </button>
                                    </div>
                                    @endif
                                    <div class="col-auto">
                                        <select class="form-select" id="productTypeFilter">
                                            <option value="">{{ __('labels.product_type') }}</option>
                                            @foreach(\App\Enums\Product\ProductTypeEnum::values() as $type)
                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productStatusFilter">
                                            <option value="">{{ __('labels.product_status') }}</option>
                                            @foreach(\App\Enums\Product\ProductStatusEnum::values() as $type)
                                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productVerificationStatusFilter">
                                            <option value="">{{ __('labels.verification_status') }}</option>
                                            @foreach(ProductVarificationStatusEnum::values() as $type)
                                                <option
                                                    value="{{ $type }}">{{ ucfirst(\Illuminate\Support\Str::replace("_", " ",$type)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="form-select" id="productCategoryFilter" placeholder="{{ __('labels.category') }}">
                                        </select>
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
                        <div class="card-table">
                            <div class="row w-full p-3">
                                <x-datatable id="products-table" :columns="$columns"
                                             route="{{ route('admin.products.datatable') }}"
                                             :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="view-product-offcanvas" aria-labelledby="offcanvasEndLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="offcanvasEndLabel">Product Details</h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="card card-sm border-0">
                <label class="fw-medium pb-1">Image</label>
                <div class="img-box-200px-h card-img">
                    <img id="product-image" src=""/>
                </div>
                <div class="card-body px-0">
                    <div>
                        <h4 id="product-name" class="fs-3"></h4>
                        <p id="product-description" class="fs-4"></p>
                        <p class="col-md-8 d-flex justify-content-between">Status: <span id="product-status"
                                                                                         class="badge bg-green-lt text-uppercase fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Category: <span id="product-category"
                                                                                           class="fw-medium"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="product-import-modal" tabindex="-1" aria-labelledby="productImportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productImportModalLabel">Import Products (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="product-import-form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="{{ route('admin.products.import.template') }}" class="btn btn-sm btn-outline-secondary">
                                Download Template
                            </a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CSV File</label>
                            <input type="file" name="file" id="product-import-file" class="form-control" accept=".csv,.txt,.xlsx" required>
                            <small class="text-muted">Supported: CSV, TXT, XLSX. Use the template to ensure valid columns and JSON fields.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" id="product-import-async" name="async" value="1">
                                <span class="form-check-label">Run in background (recommended for large files)</span>
                            </label>
                        </div>
                        <div class="alert d-none" id="product-import-result"></div>
                        <div class="d-none" id="product-import-report-wrap">
                            <a href="#" id="product-import-report-link" class="btn btn-sm btn-outline-danger" target="_blank">Download Failed Rows Report</a>
                        </div>
                        <ul class="small text-danger ps-3 d-none" id="product-import-errors"></ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="product-import-submit">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{hyperAsset('assets/js/product.js')}}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('product-import-form');
            if (!form) {
                return;
            }

            const submitButton = document.getElementById('product-import-submit');
            const resultBox = document.getElementById('product-import-result');
            const errorList = document.getElementById('product-import-errors');
            const reportWrap = document.getElementById('product-import-report-wrap');
            const reportLink = document.getElementById('product-import-report-link');
            const asyncCheckbox = document.getElementById('product-import-async');

            let statusPollTimer = null;
            let statusPollAttempts = 0;

            function resetResultUi() {
                resultBox.className = 'alert d-none';
                resultBox.textContent = '';
                errorList.innerHTML = '';
                errorList.classList.add('d-none');
                reportWrap.classList.add('d-none');
                reportLink.href = '#';
            }

            function renderResult(data) {
                const created = data.created ?? 0;
                const failed = data.failed ?? 0;
                resultBox.className = 'alert ' + (failed > 0 ? 'alert-warning' : 'alert-success');
                resultBox.textContent = `Created: ${created}, Failed: ${failed}`;

                if (data.failed_report_url) {
                    reportWrap.classList.remove('d-none');
                    reportLink.href = data.failed_report_url;
                }

                if (Array.isArray(data.errors) && data.errors.length > 0) {
                    errorList.classList.remove('d-none');
                    data.errors.slice(0, 20).forEach(function (item) {
                        const li = document.createElement('li');
                        li.textContent = `Row ${item.row}: ${item.message}`;
                        errorList.appendChild(li);
                    });
                }
            }

            async function pollJobStatus(jobId) {
                if (!jobId) {
                    return;
                }

                statusPollAttempts++;
                try {
                    const response = await fetch(`{{ route('admin.products.import.status', ['jobId' => '__JOB_ID__']) }}`.replace('__JOB_ID__', jobId), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const payload = await response.json();
                    const data = payload.data || {};

                    if (data.status === 'queued' || data.status === 'processing') {
                        resultBox.className = 'alert alert-info';
                        resultBox.textContent = `Import status: ${data.status}...`;
                        if (statusPollAttempts < 120) {
                            statusPollTimer = setTimeout(() => pollJobStatus(jobId), 2500);
                        } else {
                            resultBox.className = 'alert alert-warning';
                            resultBox.textContent = 'Import is still running. Please check again in a moment.';
                        }
                        return;
                    }

                    if (data.status === 'completed' || data.status === 'failed') {
                        renderResult(data);
                        submitButton.disabled = false;
                        submitButton.textContent = 'Import';
                    }
                } catch (_error) {
                    resultBox.className = 'alert alert-danger';
                    resultBox.textContent = 'Unable to fetch background import status.';
                    submitButton.disabled = false;
                    submitButton.textContent = 'Import';
                }
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const formData = new FormData(form);
                if (!asyncCheckbox.checked) {
                    formData.delete('async');
                }
                submitButton.disabled = true;
                submitButton.textContent = 'Importing...';
                resetResultUi();
                if (statusPollTimer) {
                    clearTimeout(statusPollTimer);
                }
                statusPollAttempts = 0;

                try {
                    const response = await fetch("{{ route('admin.products.import.store') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const payload = await response.json();
                    const data = payload.data || {};
                    if (data.async === true && data.job_id) {
                        resultBox.className = 'alert alert-info';
                        resultBox.textContent = 'Import queued. Checking progress...';
                        pollJobStatus(data.job_id);
                    } else {
                        renderResult(data);
                        submitButton.disabled = false;
                        submitButton.textContent = 'Import';
                    }
                } catch (error) {
                    resultBox.className = 'alert alert-danger';
                    resultBox.textContent = 'Import failed. Please try again.';
                    submitButton.disabled = false;
                    submitButton.textContent = 'Import';
                }
            });
        });
    </script>
@endpush
