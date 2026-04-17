@extends('layouts.admin.app', ['page' => 'home_section', 'sub_page' => 'featured_products_section'])

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

@push('styles')
<style>
    .featured-preview-shell {
        background: #fff;
        padding: 1.25rem 1.25rem 1.5rem;
    }

    .featured-preview-eyebrow {
        color: #4ea85f;
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        line-height: 1;
        text-transform: uppercase;
    }

    .featured-preview-heading {
        margin: 0;
        background: linear-gradient(135deg, #1a4f83 0%, #2b6e92 48%, #2d8b6a 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
        font-size: 2.25rem;
        font-weight: 800;
        line-height: 1.05;
    }

    .featured-preview-subheading {
        color: #4f6281;
        font-size: 1rem;
        margin: 0.75rem 0 0;
    }

    .featured-preview-link {
        color: #6ea8d8;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .featured-preview-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 1.75rem;
    }

    .featured-preview-card {
        background: #fff;
        border: 1px solid #e6edf5;
        border-radius: 1.25rem;
        box-shadow: 0 10px 28px rgba(15, 36, 68, 0.06);
        overflow: hidden;
    }

    .featured-preview-media {
        aspect-ratio: 1 / 1;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .featured-preview-media img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .featured-preview-no-image {
        color: #94a3b8;
        font-size: 0.95rem;
    }

    .featured-preview-body {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.95rem 1rem 1.1rem;
    }

    .featured-preview-category {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .featured-preview-title {
        color: #0f2444;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.35;
        min-height: 2.6em;
    }

    .featured-preview-price {
        color: #0f2444;
        font-size: 1rem;
        font-weight: 700;
    }

    .featured-preview-empty {
        color: #94a3b8;
        padding: 2rem 0 0.5rem;
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .featured-preview-heading {
            font-size: 1.9rem;
        }

        .featured-preview-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

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
                        <div class="featured-preview-shell">
                            <div class="d-flex justify-content-between align-items-end gap-3">
                                <div>
                                    <div class="featured-preview-eyebrow mb-2" id="previewEyebrow">{{ $settings['eyebrow'] }}</div>
                                    <h2 class="featured-preview-heading" id="previewHeading">{{ $settings['heading'] }}</h2>
                                    <p class="featured-preview-subheading" id="previewSubheading">{{ $settings['subheading'] }}</p>
                                </div>
                                <a href="#" class="featured-preview-link">View All →</a>
                            </div>

                            <div class="featured-preview-grid" id="previewGrid">
                                @forelse($products as $product)
                                    <div class="featured-preview-card">
                                        <div class="featured-preview-media">
                                            @if($product['image'])
                                                <img src="{{ $product['image'] }}" alt="{{ $product['title'] }}">
                                            @else
                                                <div class="featured-preview-no-image">No Image</div>
                                            @endif
                                        </div>
                                        <div class="featured-preview-body">
                                            <div class="featured-preview-category">{{ $product['category_name'] }}</div>
                                            <div class="featured-preview-title">{{ $product['title'] }}</div>
                                            <div class="featured-preview-price">₹{{ $product['price'] !== null ? $product['price'] : '' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="featured-preview-empty">
                                        No products available for the current preview.
                                    </div>
                                @endforelse
                            </div>
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
                        <div class="featured-preview-card">
                                <div class="featured-preview-media">
                                    ${product.image 
                                        ? `<img src="${product.image}" alt="${product.title}">` 
                                        : `<div class="featured-preview-no-image">No Image</div>`
                                    }
                                </div>
                                <div class="featured-preview-body">
                                    <div class="featured-preview-category">${product.category_name || ''}</div>
                                    <div class="featured-preview-title">${product.title || ''}</div>
                                    <div class="featured-preview-price">₹${product.price ?? ''}</div>
                                </div>
                            </div>`;
                    });
                } else {
                    gridHtml = `<div class="featured-preview-empty">No products available for the current preview.</div>`;
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
