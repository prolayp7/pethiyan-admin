@php use App\Enums\Order\OrderItemStatusEnum;use App\Enums\Product\ProductImageFitEnum;use App\Enums\Product\ProductTypeEnum;use Illuminate\Support\Str; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['products']['active'] ?? "", 'sub_page' => $menuAdmin['products']['route']['products']['sub_active']])
@php
    $title = empty($product) ? __('labels.add_product') : __('labels.edit_product');
@endphp
@section('title', $title)

@section('header_data')
    @php
        $page_title = $title;
        $page_pretitle = __('labels.admin') . " " . __('labels.products');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.products'), 'url' => route('admin.products.index')],
        ['title' => $title, 'url' => '']
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">

    <form id="product-form-submit" method="POST"
          action="{{ empty($product) ? route('admin.products.store') : route('admin.products.update', ['id' => $product->id]) }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $title }}</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('labels.back_to_products') }}
                    </a>
                </div>
            </div>

            {{-- Hidden fields: Pethiyan seller + brand defaults --}}
            <input type="hidden" name="seller_id" value="{{ $pethiyanSellerId ?? 1 }}">

            <div class="card-header">
                <nav class="nav nav-segmented nav-2 w-100" role="tablist">
                    <button type="button" class="nav-link active" data-step="1" aria-selected="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-category">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 4h6v6h-6z"/>
                            <path d="M14 4h6v6h-6z"/>
                            <path d="M4 14h6v6h-6z"/>
                            <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/>
                        </svg>
                        Select Category
                    </button>
                    <button type="button" class="nav-link" data-step="2" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-file-info">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                            <path d="M11 14h1v4h1"/>
                            <path d="M12 11h.01"/>
                        </svg>
                        Product Info
                    </button>
                    <button type="button" class="nav-link" data-step="3" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-feather">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 20l10 -10m0 -5v5h5m-9 -1v5h5m-9 -1v5h5m-5 -5l4 -4l4 -4"/>
                            <path d="M19 10c.638 -.636 1 -1.515 1 -2.486a3.515 3.515 0 0 0 -3.517 -3.514c-.97 0 -1.847 .367 -2.483 1m-3 13l4 -4l4 -4"/>
                        </svg>
                        {{ __('labels.policies_and_features') }}
                    </button>
                    <button type="button" class="nav-link" data-step="4" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-versions">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 5m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/>
                            <path d="M7 7l0 10"/>
                            <path d="M4 8l0 8"/>
                        </svg>
                        {{ __('labels.variations') }}
                    </button>
                    <button type="button" class="nav-link" data-step="5" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-layout-collage">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/>
                            <path d="M10 4l4 16"/>
                            <path d="M12 12l-8 2"/>
                        </svg>
                        {{ __('labels.images') }}
                    </button>
                    <button type="button" class="nav-link" data-step="6" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-file-description">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                            <path d="M9 17h6"/>
                            <path d="M9 13h6"/>
                        </svg>
                        {{ __('labels.description') }}
                    </button>
                    <button type="button" class="nav-link" data-step="8" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-help-circle">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12" y2="17.01"/>
                        </svg>
                        {{ __('labels.product_faqs') }}
                    </button>
                    <button type="button" class="nav-link" data-step="7" aria-selected="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-tabler icons-tabler-outline icon-tabler-currency-dollar">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/>
                            <path d="M12 3v3m0 12v3"/>
                        </svg>
                        {{ __('labels.pricing_and_taxes') }}
                    </button>
                </nav>
            </div>

            <div class="card-body">
                <div id="api-error-summary" class="alert alert-danger d-none" role="alert">
                    <div class="fw-semibold mb-1">Please fix the following errors:</div>
                    <ul class="mb-0 ps-3"></ul>
                </div>
                {{-- Step 1: Category --}}
                <div class="wizard-step" data-step="1">
                    <div class="container">
                        <div class="mb-3">
                            <h4>Search Category</h4>
                            <select class="form-select" id="select-category" type="text"></select>
                        </div>
                        <div class="mb-3">
                            <h4>Browse</h4>
                            <div id="categories" data-categories="{{ $categories }}"></div>
                            <input type="hidden" id="selected_category" name="category_id" value="{{ $product->category_id ?? '' }}">
                        </div>
                        <div id="categories-tree"></div>
                    </div>
                </div>

                {{-- Step 2: Product Info --}}
                <div class="wizard-step d-none" data-step="2">
                    <div class="container">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.product_title') }}</label>
                            <input type="text" class="form-control" name="title" value="{{ $product->title ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.brand') }}</label>
                            <input type="hidden" id="selected-brand" value="{{ $product->brand_id ?? ($pethiyanBrandId ?? 1) }}">
                            <select class="form-select" name="brand_id" id="select-brand">
                                @if(!empty($product->brand))
                                    <option value="{{ $product->brand_id }}" selected>{{ $product->brand->title }}</option>
                                @else
                                    <option value="{{ $pethiyanBrandId ?? 1 }}" selected>Pethiyan</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.made_in') }}</label>
                            <input type="text" class="form-control" name="made_in" value="{{ $product->made_in ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.hsn_code') }}</label>
                            <input type="text" class="form-control" id="hsn-code-main" name="hsn_code" value="{{ $product->hsn_code ?? '' }}">
                        </div>
                        {{-- Indicator and Base Prep Time not applicable for Pethiyan products --}}
                        <input type="hidden" name="base_prep_time" value="0">
                        <div class="mb-3">
                            <label class="form-label">Custom Fields</label>
                            <div id="customFieldsContainer" class="vstack gap-2"
                                 data-existing='@json($product->custom_fields ?? new \stdClass())'></div>
                            <button type="button" class="btn btn-outline-primary mt-2" id="addCustomFieldBtn">
                                <i class="ti ti-plus me-1"></i> Add Field
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Policies & Features --}}
                <div class="wizard-step d-none" data-step="3">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.minimum_order_quantity') }}</label>
                                    <input type="number" class="form-control" name="minimum_order_quantity" min="1" value="{{ $product->minimum_order_quantity ?? '' }}">
                                    <small class="form-hint">By Default Minimum Quantity is 1</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.quantity_step_size') }}</label>
                                    <input type="number" class="form-control" name="quantity_step_size" min="1" value="{{ $product->quantity_step_size ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.total_allowed_quantity') }}</label>
                                    <input type="number" class="form-control" name="total_allowed_quantity" min="0" value="{{ $product->total_allowed_quantity ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.is_returnable') }}</label>
                                    <select class="form-select" name="is_returnable">
                                        <option value="">Select Option</option>
                                        <option value="1" {{ (!empty($product->is_returnable) && $product->is_returnable == '1') ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ empty($product->is_returnable) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.is_cancelable') }}</label>
                                    <select class="form-select" name="is_cancelable">
                                        <option value="">Select Option</option>
                                        <option value="1" {{ (!empty($product->is_cancelable) && $product->is_cancelable == '1') ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ empty($product->is_cancelable) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.cancelable_till') }}</label>
                                    <select class="form-select text-capitalize" name="cancelable_till">
                                        <option value="">Select Option</option>
                                        <option value="{{ OrderItemStatusEnum::PENDING() }}" {{ (!empty($product->cancelable_till) && $product->cancelable_till == OrderItemStatusEnum::PENDING()) ? 'selected' : '' }}>{{ Str::replace('_', ' ', OrderItemStatusEnum::PENDING()) }}</option>
                                        <option value="{{ OrderItemStatusEnum::AWAITING_STORE_RESPONSE() }}" {{ (!empty($product->cancelable_till) && $product->cancelable_till == OrderItemStatusEnum::AWAITING_STORE_RESPONSE()) ? 'selected' : '' }}>{{ Str::replace('_', ' ', OrderItemStatusEnum::AWAITING_STORE_RESPONSE()) }}</option>
                                        <option value="{{ OrderItemStatusEnum::ACCEPTED() }}" {{ (!empty($product->cancelable_till) && $product->cancelable_till == OrderItemStatusEnum::ACCEPTED()) ? 'selected' : '' }}>{{ Str::replace('_', ' ', OrderItemStatusEnum::ACCEPTED()) }}</option>
                                        <option value="{{ OrderItemStatusEnum::PREPARING() }}" {{ (!empty($product->cancelable_till) && $product->cancelable_till == OrderItemStatusEnum::PREPARING()) ? 'selected' : '' }}>{{ Str::replace('_', ' ', OrderItemStatusEnum::PREPARING()) }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.is_attachment_required') }}</label>
                                    <select class="form-select" name="is_attachment_required">
                                        <option value="">Select Option</option>
                                        <option value="1" {{ !empty($product->is_attachment_required) && $product->is_attachment_required == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ empty($product->is_attachment_required) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.featured_product') }}</label>
                                    <select class="form-select" name="featured">
                                        <option value="">Select Option</option>
                                        <option value="1" {{ (!empty($product->featured) && $product->featured == '1') ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ empty($product->featured) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Top Product</label>
                                    <select class="form-select" name="is_top_product">
                                        <option value="">Select Option</option>
                                        <option value="1" {{ !empty($product->is_top_product) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ empty($product->is_top_product) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.requires_otp') }}</label>
                                    <select class="form-select" name="requires_otp">
                                        <option value="">Select Option</option>
                                        <option value="1" {{ (!empty($product->requires_otp) && $product->requires_otp == '1') ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ empty($product->requires_otp) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3 returnable-days">
                                    <label class="form-label">{{ __('labels.returnable_days') }}</label>
                                    <input type="number" class="form-control" name="returnable_days" min="0" value="{{ $product->returnable_days ?? '' }}">
                                    <small class="form-hint">Required if Product is returnable</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.warranty_period') }}</label>
                                    <input type="text" class="form-control" name="warranty_period" value="{{ $product->warranty_period ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.guarantee_period') }}</label>
                                    <input type="text" class="form-control" name="guarantee_period" value="{{ $product->guarantee_period ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 4: Variations --}}
                <div class="wizard-step d-none" data-step="4">
                    <div class="container">
                        <div id="attributes" data-attributes="{{ $attributes }}"></div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.product_type') }}</label>
                            <select class="form-select text-capitalize" name="type" id="productType" {{ !empty($product->type) ? 'readonly' : '' }}>
                                <option value="">{{ __('labels.select_type') }}</option>
                                @foreach(ProductTypeEnum::values() as $type)
                                    <option value="{{ $type }}" {{ (!empty($product->type) && $product->type == $type) ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="simpleProductSection" class="d-none"></div>
                        {{-- Generic attribute picker for simple products --}}
                        <div id="variationsSection" class="d-none">
                            <div class="card border-0 bg-light">
                                <div class="card-header bg-transparent border-bottom gap-1">
                                    <h4 class="card-title mb-0">Product Variations</h4>
                                    <p class="text-muted mb-0 small">Add attributes and their values to create product variants</p>
                                </div>
                                <div class="card-body">
                                    <div id="variantsContainer" class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Product Variants</h5>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-primary" id="addCustomVariantBtn" onclick="addCustomVariant()">
                                                    <i class="ti ti-plus me-1"></i>Add Variant
                                                </button>
                                                <button type="button" class="btn btn-outline-success" id="addRemovedVariantBtn" data-bs-toggle="modal" data-bs-target="#addRemovedVariantModal" disabled>
                                                    <i class="ti ti-history me-1"></i>Restore Removed
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" id="removeAllVariantsBtn">
                                                    <i class="ti ti-trash me-1"></i>Remove All
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="variantsList" class="row g-3"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 5: Images --}}
                <div class="wizard-step d-none" data-step="5">
                    <div class="container">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.main_image') }}</label>
                            <x-filepond_image name="main_image" imageUrl="{{ $product->main_image ?? '' }}"/>
                            <small class="form-hint">Recommended: 1200 x 1200 px. Max upload size: 2 MB.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.additional_images') }}</label>
                            <input type="file" name="additional_images[]" class="form-control"
                                   data-images='@json($product->additional_images ?? [])' multiple>
                            <small class="form-hint">Recommended: 1200 x 1200 px each. Max upload size: 2 MB per image. You can select multiple images at once.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.image_fit') }}</label>
                            <select class="form-select text-capitalize" name="image_fit">
                                @foreach(ProductImageFitEnum::values() as $value)
                                    <option value="{{ $value }}" {{ (!empty($product->image_fit) && $product->image_fit == $value) ? 'selected' : '' }}>{{ Str::replace('_', ' ', $value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.video_type') }}</label>
                            <select class="form-select text-capitalize" name="video_type" id="videoType">
                                <option value="">{{ __('labels.select_video_type') }}</option>
                                @foreach(\App\Enums\Product\ProductVideoTypeEnum::values() as $type)
                                    <option value="{{ $type }}" {{ (!empty($product->video_type) && $product->video_type == $type) ? 'selected' : '' }}>{{ Str::replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.video_link') }}</label>
                            <input type="url" class="form-control" name="video_link" value="{{ $product->video_link ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.video_upload') }}</label>
                            <input type="file" name="product_video" class="form-control" data-image-url="{{ $product->product_video ?? '' }}">
                        </div>
                    </div>
                </div>

                {{-- Step 6: Description --}}
                <div class="wizard-step d-none" data-step="6">
                    <div class="container">
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.short_description') }}</label>
                            <textarea class="form-control" name="short_description" rows="3">{{ $product->short_description ?? '' }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">{{ __('labels.description') }}</label>
                            <textarea class="form-control hugerte-mytextarea" name="description" rows="5">{{ $product->description ?? '' }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('labels.tags') }}</label>
                            <select class="form-select product-tags" name="tags[]" multiple>
                                <option value="">{{ __('labels.select_tags') }}</option>
                                @php
                                    $tags = [];
                                    if (!empty($product->tags)) {
                                        $tags = is_string($product->tags) ? json_decode($product->tags, true) : ($product->tags ?? []);
                                    }
                                @endphp
                                @foreach($tags as $tag)
                                    <option value="{{ $tag }}" selected>{{ $tag }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Step 7: Pricing & Taxes --}}
                <div class="wizard-step d-none" data-step="7">
                    <div class="container">
                        <div id="storePricingSection">
                            <div class="card mb-3 border">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">GST / Tax Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">GST Slab Override</label>
                                            @php
                                                $gstSelectedValue = old('gst_rate', $selectedGstRate ?? (!empty($product) ? (string) $product->gst_rate : ''));
                                            @endphp
                                            <select class="form-select" name="gst_rate">
                                                <option value="">— Use Tax Group Default —</option>
                                                @foreach(['0' => '0% — Nil / Exempt', '5' => '5% — Basic Necessities', '12' => '12% — Paper / Paperboard', '18' => '18% — Plastic / Electronics', '28' => '28% — Luxury / Demerit'] as $pct => $label)
                                                    <option value="{{ $pct }}" {{ (string)$gstSelectedValue === (string)$pct ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('labels.tax_group') }}</label>
                                            <select class="form-select" name="tax_group_id" id="select-tax-group">
                                                <option value="">— Select Tax Group —</option>
                                                @php
                                                    $selectedTaxId = old('tax_group_id', $selectedTaxGroupId ?? (!empty($product) ? optional($product->taxClasses->first())->id : null));
                                                @endphp
                                                @foreach($taxClasses as $taxClass)
                                                    @php
                                                        $rateSum = (float) ($taxClass->taxRates->sum('rate') ?? 0);
                                                        $nearest = collect([0, 5, 12, 18, 28])->sortBy(fn($s) => abs($s - $rateSum))->first();
                                                    @endphp
                                                    <option value="{{ $taxClass->id }}" data-gst-rate="{{ $nearest }}" {{ $selectedTaxId == $taxClass->id ? 'selected' : '' }}>{{ $taxClass->title }}</option>
                                                @endforeach
                                            </select>
                                            <small class="form-hint">You can select multiple tags</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header bg-transparent border-bottom gap-1 px-0">
                                    <h4 class="card-title mb-0">Store Pricing</h4>
                                    <p class="text-muted mb-0 small">Set pricing for each store</p>
                                    {{-- Keep this section in code for future use, hidden for now --}}
                                    <div id="customer-state-wrapper" class="w-100 d-none mt-2">
                                        <label class="form-label mb-1">Customer Delivery State (for GST split)</label>
                                        <select class="form-select" id="customer-state-code" name="customer_state_code">
                                            <option value="">— Select Delivery State —</option>
                                            @foreach(($gstStates ?? []) as $state)
                                                <option
                                                    value="{{ $state->gst_code }}"
                                                    data-state-code="{{ $state->state_code }}"
                                                    data-state-name="{{ $state->name }}"
                                                    {{ old('customer_state_code') == $state->gst_code ? 'selected' : '' }}
                                                >
                                                    {{ $state->name }} (GST {{ $state->gst_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-hint">Intra-state: CGST+SGST, Inter-state: IGST</small>
                                    </div>
                                </div>
                                <div class="card-body px-0">
                                    <div id="simplePricingContainer" class="d-none"></div>
                                    <div id="variantPricingContainer">
                                        <div class="accordion accordion-flush border m-2 rounded" id="storePricingAccordion"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Step 8: Product FAQs --}}
                <div class="wizard-step d-none" data-step="8">
                    <div class="container">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">{{ __('labels.product_faqs') }}</h4>
                            @if(!empty($product) && auth()->user()?->can('create', App\Models\ProductFaq::class))
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#product-faq-modal" data-product-id="{{ $product->id }}">
                                    <i class="ti ti-plus me-1"></i> {{ __('labels.add_product_faq') }}
                                </button>
                            @endif
                        </div>

                        @if(empty($product))
                            <div class="alert alert-info">Save the product first to add product-specific FAQs.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('labels.question') }}</th>
                                            <th>{{ __('labels.answer') }}</th>
                                            <th>{{ __('labels.status') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="product-faqs-tbody" data-empty-text="{{ __('labels.no_product_faqs_found') }}">
                                        @forelse($product->faqs ?? [] as $faq)
                                            <tr data-id="{{ $faq->id }}">
                                                <td>{{ $faq->id }}</td>
                                                <td>{{ Str::limit($faq->question, 80) }}</td>
                                                <td>{{ Str::limit($faq->answer, 120) }}</td>
                                                <td class="text-capitalize">{{ $faq->status }}</td>
                                                <td class="text-end">
                                                    @can('update', $faq)
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#product-faq-modal" data-id="{{ $faq->id }}">{{ __('labels.edit') }}</button>
                                                    @endcan
                                                    @can('delete', $faq)
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-product-faq" data-id="{{ $faq->id }}">{{ __('labels.delete') }}</button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5">{{ __('labels.no_product_faqs_found') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between pt-4">
                <button type="button" class="btn btn-secondary" id="prevStep">Previous</button>
                <button class="btn btn-primary" id="nextStep">Next</button>
            </div>
        </div>
    </form>

    <div class="modal fade" id="addRemovedVariantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Removed Variant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="removedVariantsList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product FAQ Modal (included in product form) -->
    <div class="modal modal-blur fade" id="product-faq-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="product-faq-modal-title">{{ __('labels.add_product_faq') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="form-submit" id="product-faq-form" method="POST" action="{{ route('admin.product_faqs.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.product') }}</label>
                                    @if(!empty($product))
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="text" class="form-control" value="{{ $product->title }}" disabled>
                                    @else
                                        <select class="form-select" id="select-product-modal" name="product_id" required>
                                            <option value="">{{ __('labels.select_product') }}</option>
                                        </select>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.question') }}</label>
                                    <textarea class="form-control" id="question" name="question" rows="3" placeholder="{{ __('labels.enter_question') }}" required></textarea>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('labels.answer') }}</label>
                                    <textarea class="form-control" id="answer" name="answer" rows="4" placeholder="{{ __('labels.enter_answer') }}" required></textarea>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('labels.status') }}</label>
                                    <select class="form-select text-capitalize" id="status" name="status">
                                        @foreach(\App\Enums\ActiveInactiveStatusEnum::values() as $status)
                                            <option value="{{$status}}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            {{ __('labels.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ hyperAsset('assets/vendor/js_tree/main.min.css') }}"/>
@endpush
@push('scripts')
    @php
        $taxClassRateMap = $taxClassRateMap ?? (($taxClasses ?? collect())->mapWithKeys(function ($taxClass) {
            $rateSum = (float) ($taxClass->taxRates->sum('rate') ?? 0);
            $nearest = collect([0, 5, 12, 18, 28])->sortBy(function ($s) use ($rateSum) {
                return abs($s - $rateSum);
            })->first();
            return [$taxClass->id => (int) $nearest];
        }));
    @endphp
    <script src="{{ hyperAsset('assets/vendor/js_tree/main.min.js') }}" defer></script>
    <script src="{{ hyperAsset('assets/js/product.js') }}" defer></script>

    <script>
        // Admin product form: always use Pethiyan seller (ID=1) for store pricing
        window._adminSellerId = {{ $pethiyanSellerId ?? 1 }};
        window._preloadedStores = @json($storeList ?? []);
        window._gstStates = @json($gstStates ?? []);
        window._taxClassRateMap = @json($taxClassRateMap);
        window._productGstRate = @json(!empty($product) ? $product->gst_rate : null);
    </script>

    @if(!empty($product) && !empty($productVariants))
        <script>
            window.productData = {
                product: @json($product),
                variants: @json($productVariants),
                type: "{{ $product->type }}"
            };
        </script>
    @elseif(!empty($product) && !empty($singleProductVariant))
        <script>
            window.productData = {
                product: @json($product),
                variant: @json($singleProductVariant),
                type: "{{ $product->type }}"
            };
        </script>
    @endif
@endpush
