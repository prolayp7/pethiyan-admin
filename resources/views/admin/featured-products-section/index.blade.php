@extends('layouts.admin.app', ['page' => 'featured_products_section', 'sub_page' => ''])

@section('title', 'Featured Products Settings')

@section('header_data')
    @php
        $page_title    = 'Featured Products';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Featured Products', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Featured Products Settings</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row align-items-start">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Section Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Section Visibility</label>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" {{ $settings['is_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label">Show Featured Products section on the homepage</span>
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="eyebrow">Eyebrow</label>
                                <input type="text" class="form-control" id="eyebrow" name="eyebrow" value="{{ $settings['eyebrow'] }}" maxlength="120" placeholder="e.g. BESTSELLERS">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="heading">Heading</label>
                                <input type="text" class="form-control" id="heading" name="heading" value="{{ $settings['heading'] }}" maxlength="255" placeholder="e.g. Featured Products">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="subheading">Subheading</label>
                                <input type="text" class="form-control" id="subheading" name="subheading" value="{{ $settings['subheading'] }}" maxlength="255" placeholder="e.g. Handpicked packaging solutions...">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Categories to Feature</label>
                                <select class="form-select" id="categoryIds" name="category_ids[]" multiple="multiple" style="width: 100%;">
                                    @php
                                        // group logic
                                        $groupedCategories = $categories->groupBy('parent_id');
                                    @endphp
                                    @if(isset($groupedCategories['']))
                                        @foreach($groupedCategories[''] as $parent)
                                            <optgroup label="{{ $parent->title }}">
                                                <option value="{{ $parent->id }}" {{ in_array($parent->id, $settings['category_ids'] ?? []) ? 'selected' : '' }}>{{ $parent->title }}</option>
                                                @if(isset($groupedCategories[$parent->id]))
                                                    @foreach($groupedCategories[$parent->id] as $child)
                                                        <option value="{{ $child->id }}" {{ in_array($child->id, $settings['category_ids'] ?? []) ? 'selected' : '' }}>-- {{ $child->title }}</option>
                                                    @endforeach
                                                @endif
                                            </optgroup>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="form-hint mt-2">Products from these categories will be shown in tabs (if multiple).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="productCount">Max Products limit</label>
                                <input type="number" class="form-control" id="productCount" name="product_count" value="{{ $settings['product_count'] }}" min="1" max="50">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="viewAllLink">View All Link</label>
                                <input type="text" class="form-control" id="viewAllLink" name="view_all_link" value="{{ $settings['view_all_link'] }}" placeholder="/shop">
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="saveSettingsBtn">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Live Preview</h3>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshPreviewBtn">Refresh</button>
                    </div>
                    <div class="card-body bg-light">
                        {{-- Preview Header --}}
                        <div class="mb-4">
                            <div class="text-success text-uppercase small fw-bold mb-1" id="previewEyebrow">{{ $settings['eyebrow'] }}</div>
                            <h2 class="mb-2" style="color: #1c4c64;" id="previewHeading">{{ $settings['heading'] }}</h2>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="text-muted mb-0" id="previewSubheading">{{ $settings['subheading'] }}</p>
                                <a href="#" class="text-primary text-decoration-none">View All →</a>
                            </div>
                        </div>

                        {{-- Category Pills Simulation --}}
                        @if(count($settings['category_ids'] ?? []) > 1)
                            <div class="d-flex gap-2 mb-4 overflow-auto pb-2" id="previewPills">
                                @foreach($categories->whereIn('id', $settings['category_ids'] ?? []) as $index => $cat)
                                    <span class="badge @if($index === 0) bg-primary @else bg-white text-dark border @endif px-3 py-2 rounded-pill">{{ $cat->title }}</span>
                                @endforeach
                            </div>
                        @else
                            <div id="previewPills" class="d-flex gap-2 mb-4 overflow-auto pb-2"></div>
                        @endif

                        {{-- Product Grid Preview --}}
                        <div class="row row-cols-2 row-cols-sm-3 g-3" id="previewGrid">
                            @forelse($products as $product)
                                <div class="col">
                                    <div class="card card-sm shadow-sm h-100">
                                        <div class="ratio ratio-1x1 bg-white">
                                            @if($product->getFirstMediaUrl('images'))
                                                <img src="{{ $product->getFirstMediaUrl('images') }}" class="object-fit-contain p-2" alt="{{ $product->name }}">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center bg-light text-muted">No Image</div>
                                            @endif
                                        </div>
                                        <div class="card-body p-2 d-flex flex-column">
                                            <div class="text-muted small text-truncate text-uppercase mb-1" style="font-size: 0.7rem;">{{ $product->category?->title }}</div>
                                            <div class="fw-semibold text-dark text-truncate mb-auto" style="font-size: 0.85rem;">{{ $product->name }}</div>
                                            <div class="mt-2 fw-bold" style="font-size: 0.9rem;">₹{{ $product->price }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 py-5 text-center text-muted">
                                    No products found for the selected categories.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    $('#categoryIds').select2({
        placeholder: "Select categories",
        allowClear: true
    });

    // Real-time text preview
    $('#eyebrow').on('input', function() { $('#previewEyebrow').text($(this).val()); });
    $('#heading').on('input', function() { $('#previewHeading').text($(this).val()); });
    $('#subheading').on('input', function() { $('#previewSubheading').text($(this).val()); });

    // Refresh Preview
    function refreshPreview(buttonElement = null) {
        if (buttonElement) {
            $(buttonElement).html('<span class="spinner-border spinner-border-sm"></span>');
            $(buttonElement).prop('disabled', true);
        }

        const categoryIds = $('#categoryIds').val() || [];
        const productCount = $('#productCount').val() || 8;

        // Render Pills natively based on selected text from select2
        const selectedData = $('#categoryIds').select2('data');
        let pillsHtml = '';
        if (selectedData.length > 1) {
            selectedData.forEach((item, index) => {
                const title = item.text.replace('-- ', '');
                const bgClass = index === 0 ? 'bg-primary' : 'bg-white text-dark border';
                pillsHtml += `<span class="badge ${bgClass} px-3 py-2 rounded-pill">${title}</span>`;
            });
        }
        $('#previewPills').html(pillsHtml);

        $.ajax({
            url: '{{ route("admin.featured-products-section.preview") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
                category_ids: categoryIds,
                product_count: productCount
            },
            success: function(response) {
                let gridHtml = '';
                if (response.success && response.products && response.products.length > 0) {
                    response.products.forEach(product => {
                        gridHtml += `
                        <div class="col">
                            <div class="card card-sm shadow-sm h-100">
                                <div class="ratio ratio-1x1 bg-white">
                                    ${product.image 
                                        ? `<img src="${product.image}" class="object-fit-contain p-2" alt="${product.name}">` 
                                        : `<div class="d-flex align-items-center justify-content-center bg-light text-muted">No Image</div>`
                                    }
                                </div>
                                <div class="card-body p-2 d-flex flex-column">
                                    <div class="text-muted small text-truncate text-uppercase mb-1" style="font-size: 0.7rem;">${product.category_name || ''}</div>
                                    <div class="fw-semibold text-dark text-truncate mb-auto" style="font-size: 0.85rem;">${product.name}</div>
                                    <div class="mt-2 fw-bold" style="font-size: 0.9rem;">₹${product.price}</div>
                                </div>
                            </div>
                        </div>`;
                    });
                } else {
                    gridHtml = `<div class="col-12 py-5 text-center text-muted">No products found for the selected categories.</div>`;
                }
                $('#previewGrid').html(gridHtml);
                
                if (buttonElement) {
                    $(buttonElement).html('Refresh');
                    $(buttonElement).prop('disabled', false);
                }
            },
            error: function() {
                if (buttonElement) {
                    $(buttonElement).html('Refresh');
                    $(buttonElement).prop('disabled', false);
                }
            }
        });
    }

    $('#refreshPreviewBtn').on('click', function() {
        refreshPreview(this);
    });

    // Save Settings
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveSettingsBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: '{{ route("admin.featured-products-section.settings.update") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                btn.prop('disabled', false).html(origText);
                if(response.success) {
                    if (typeof showToastr === 'function') {
                        showToastr('success', response.message);
                    } else {
                        alert(response.message);
                    }
                    refreshPreview();
                } else {
                    alert('Error saving settings.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(origText);
                let msg = 'Error saving settings.';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (typeof showToastr === 'function') {
                    showToastr('error', msg);
                } else {
                    alert(msg);
                }
            }
        });
    });
});
</script>
@endpush
