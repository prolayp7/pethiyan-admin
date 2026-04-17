@extends('layouts.admin.app', ['page' => $menuAdmin['categories']['active'] ?? ""])

@section('title', __('labels.categories'))

@section('header_data')
    @php
        $page_title = __('labels.categories');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.categories'), 'url' => null],
    ];
    $canManageCategories = ($createPermission ?? false) || ($editPermission ?? false);
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.categories') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                @if($createPermission ?? false)
                                    <div class="col text-end">
                                        <a href="#" class="btn btn-6 btn-outline-primary" data-bs-toggle="modal"
                                           data-bs-target="#category-modal">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="icon icon-2"
                                            >
                                                <path d="M12 5l0 14"/>
                                                <path d="M5 12l14 0"/>
                                            </svg>
                                            {{ __('labels.add_category') }}
                                        </a>
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
                <div class="card-table">
                    <div class="row w-full p-3">
                        <div class="alert alert-info alert-dismissible" role="alert">
                            <div class="alert-icon">
                                <!-- Download SVG icon from http://tabler.io/icons/icon/info-circle -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon alert-icon icon-2"
                                >
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
                                    <path d="M12 9h.01"/>
                                    <path d="M11 12h1v4h1"/>
                                </svg>
                            </div>
                            {{ __('labels.global_scope_config_note') }}
                            <a href="{{route('admin.settings.show', ['home_general_settings'])}}" class="alert-action">
                                Link </a>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                        <x-datatable id="categories-table" :columns="$columns"
                                     route="{{ route('admin.categories.datatable') }}"
                                     :options="['ordering' => false, 'paging' => false, 'info' => false, 'pageLength' => 500]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($canManageCategories)
        <div
            class="modal modal-blur fade"
            id="category-modal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            data-bs-backdrop="static"
        >
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <form class="form-submit" action="{{route('admin.categories.store')}}" method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" id="category-id" value=""/>
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('labels.create_category') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="row g-4">

                                {{-- LEFT COLUMN: text / select / toggle fields --}}
                                <div class="col-md-7">

                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('labels.category_name') }}</label>
                                        <input type="text" class="form-control" name="title" id="category-title-input"
                                               placeholder="{{ __('labels.enter_category_name') }}"
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('labels.description') }}</label>
                                        <textarea class="form-control" name="description" id="category-description-input" rows="3"
                                                  placeholder="{{ __('labels.enter_description') }}"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('labels.parent_category') }}</label>
                                        <select type="text" class="form-select" id="select-parent-category" name="parent_id">
                                            <!-- Options will be dynamically loaded -->
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3 form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="status" id="status-switch"
                                                       value="active" checked>
                                                <label class="form-check-label"
                                                       for="status-switch">{{ __('labels.status') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- RIGHT COLUMN: image upload fields --}}
                                <div class="col-md-5">
                                    <div class="p-3 rounded border h-100" style="background: var(--tblr-bg-surface-secondary, #f8f9fa);">
                                        <h6 class="mb-3 text-muted fw-semibold text-uppercase" style="font-size: .7rem; letter-spacing: .05em;">Media &amp; Images</h6>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.image') }}</label>
                                            <input type="file" class="form-control" id="image-upload" name="image"
                                                   data-image-url=""/>
                                            <small class="form-hint">Recommended: 1024 x 1024 px. Max upload size: 5 MB.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.banner') }}</label>
                                            <input type="file" class="form-control" id="banner-upload" name="banner"
                                                   data-image-url=""/>
                                            <small class="form-hint">Recommended: 1600 x 600 px. Max upload size: 2 MB.</small>
                                        </div>

                                        {{--<div class="mb-3">
                                            <label class="form-label">{{ __('labels.icon') }}</label>
                                            <input type="file" class="form-control" id="icon-upload" name="icon"
                                                   data-image-url=""/>
                                            <small class="form-hint">Recommended: 256 x 256 px. Preferred file size: up to 2 MB.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('labels.active_icon') }}</label>
                                            <input type="file" class="form-control" id="active-icon-upload" name="active_icon"
                                                   data-image-url=""/>
                                            <small class="form-hint">Recommended: 256 x 256 px. Preferred file size: up to 2 MB.</small>
                                        </div>

                                        <div class="mb-0" id="background-image-field" style="display: none;">
                                            <label class="form-label">{{ __('labels.background_image') }}</label>
                                            <input type="file" class="form-control" id="background-image-upload"
                                                   name="background_image"
                                                   data-image-url=""/>
                                            <small class="form-hint">Recommended: 1920 x 1080 px. Max upload size: 2 MB.</small>
                                        </div>--}}
                                    </div>
                                </div>

                            </div>{{-- end .row --}}

                            {{-- SEO Section --}}
                            <hr class="mt-4 mb-3">
                            <div class="mb-1">
                                <h6 class="text-muted fw-semibold text-uppercase mb-3" style="font-size:.7rem;letter-spacing:.05em;">SEO Settings</h6>
                                <div class="mb-3">
                                    <label class="row">
                                        <span class="col">Allow search engines to index this category</span>
                                        <span class="col-auto">
                                            <label class="form-check form-check-single form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_indexable"
                                                       id="is-indexable-switch" value="1" checked/>
                                            </label>
                                        </span>
                                    </label>
                                    <small class="form-hint">Uncheck to add <code>noindex</code> (e.g. draft, unlisted, or duplicate category).</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SEO Title</label>
                                        <input type="text" class="form-control" name="seo_title" id="category-seo-title-input" maxlength="255"
                                               placeholder="e.g. Buy Standup Pouches | Pethiyan"/>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="form-hint">Up to 255 characters.</small>
                                            <small class="text-muted" id="catSeoTitleCount">0 / 255</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SEO Keywords</label>
                                        <input type="hidden" name="seo_keywords" id="category-seo-keywords-value"/>
                                        <input type="text" class="form-control" id="category-seo-keywords-input" maxlength="255"
                                               placeholder="e.g. standup pouch, kraft bag"/>
                                        <small class="form-hint">Comma-separated keywords.</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">SEO Description</label>
                                        <textarea class="form-control" name="seo_description" id="category-seo-description-input" rows="2" maxlength="500"
                                                  placeholder="e.g. Shop premium standup pouches at Pethiyan. GST invoice, bulk pricing."></textarea>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="form-hint">Up to 500 characters.</small>
                                            <small class="text-muted" id="catSeoDescCount">0 / 500</small>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="text-muted fw-semibold text-uppercase mb-3" style="font-size:.7rem;letter-spacing:.05em;">Open Graph</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">OG Title</label>
                                        <input type="text" class="form-control" name="og_title"
                                               placeholder="Leave blank to use SEO title"/>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">OG Image</label>
                                        <input type="file" class="form-control" id="category-og-image-upload" name="og_image"/>
                                        <small class="form-hint">Recommended: 1200 x 630 px. Max upload size: 2 MB.</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">OG Description</label>
                                        <textarea class="form-control" name="og_description" rows="2"
                                                  placeholder="Leave blank to use SEO description"></textarea>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="text-muted fw-semibold text-uppercase mb-3" style="font-size:.7rem;letter-spacing:.05em;">Twitter</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter Title</label>
                                        <input type="text" class="form-control" name="twitter_title"
                                               placeholder="Leave blank to use SEO title"/>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter Card</label>
                                        <select class="form-select" name="twitter_card">
                                            <option value="">Use automatic fallback</option>
                                            <option value="summary">Summary</option>
                                            <option value="summary_large_image">Summary Large Image</option>
                                            <option value="app">App</option>
                                            <option value="player">Player</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter Image</label>
                                        <input type="file" class="form-control" id="category-twitter-image-upload" name="twitter_image"/>
                                        <small class="form-hint">Recommended: 1200 x 675 px. Max upload size: 2 MB.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter Description</label>
                                        <textarea class="form-control" name="twitter_description" rows="2"
                                                  placeholder="Leave blank to use SEO description"></textarea>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="text-muted fw-semibold text-uppercase mb-3" style="font-size:.7rem;letter-spacing:.05em;">Schema</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Schema Mode</label>
                                        <select class="form-select" name="schema_mode" id="category-schema-mode-select">
                                            <option value="auto">Auto-generate</option>
                                            <option value="custom">Custom JSON-LD</option>
                                        </select>
                                    </div>
                                    <div class="col-12" id="category-schema-json-ld-wrap">
                                        <label class="form-label">Schema JSON-LD</label>
                                        <textarea class="form-control" name="schema_json_ld" rows="6"
                                                  placeholder='{"@@context":"https://schema.org","@@type":"CollectionPage"}'></textarea>
                                        <small class="form-hint">Used only when Schema Mode is set to Custom JSON-LD.</small>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer">
                            <a href="#" class="btn"
                               data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon icon-2"
                                >
                                    <path d="M12 5l0 14"/>
                                    <path d="M5 12l14 0"/>
                                </svg>
                                {{ __('labels.create_new_category') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <div class="offcanvas offcanvas-end" tabindex="-1" id="view-category-offcanvas" aria-labelledby="offcanvasEndLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title" id="offcanvasEndLabel">Category Details</h2>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="card card-sm border-0">
                <label class="fw-medium pb-1">Banner</label>
                <div class="img-box-200px-h card-img">
                    <img id="banner-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Image</label>
                <div class="img-box-200px-h card-img">
                    <img id="card-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Icon</label>
                <div class="img-box-200px-h card-img">
                    <img id="icon-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Active Icon</label>
                <div class="img-box-200px-h card-img">
                    <img id="active-icon-image" src=""/>
                </div>
                <label class="fw-medium pb-1 pt-3">Background</label>
                <div id="background-display">
                    <p class="col-md-8 d-flex justify-content-between">Type: <span id="background-type"
                                                                                   class="fw-medium"></span></p>
                    <div id="background-color-display" style="display: none;">
                        <p class="col-md-8 d-flex justify-content-between">Color: <span id="background-color-value"
                                                                                        class="fw-medium"></span></p>
                        <div class="color-preview" id="background-color-preview"
                             style="width: 50px; height: 50px; border: 1px solid #ccc; border-radius: 4px;"></div>
                    </div>
                    <div id="background-image-display" style="display: none;">
                        <div class="img-box-200px-h card-img">
                            <img id="background-image-preview" src=""/>
                        </div>
                    </div>
                </div>
                <label class="fw-medium pb-1 pt-3">Font Color</label>
                <div id="font-color-display">
                    <p class="col-md-8 d-flex justify-content-between">Color: <span id="font-color-value"
                                                                                    class="fw-medium"></span></p>
                    <div class="color-preview form-control form-control-color w-100" id="font-color-preview"></div>
                </div>
                <div class="card-body px-0">
                    <div>
                        <h4 id="category-name" class="fs-3"></h4>
                        <p id="category-description" class="fs-4"></p>
                        <p class="col-md-8 d-flex justify-content-between">Status: <span id="category-status"
                                                                                         class="badge bg-green-lt text-uppercase fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Parent Category: <span id="parent-category"
                                                                                                  class="fw-medium"></span>
                        </p>
                        <p class="col-md-8 d-flex justify-content-between">Commission: <span class="fw-medium"></span>%
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (function () {
        const table = $('#categories-table');
        const reorderUrl = '{{ route('admin.categories.reorder') }}';
        let categorySortable = null;

        if (!document.getElementById('category-sort-styles')) {
            $('head').append(`
                <style id="category-sort-styles">
                    .category-sort-cell {
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                    }

                    .category-sort-handle {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 24px;
                        height: 24px;
                        padding: 0;
                        border: 1px solid rgba(98, 105, 118, 0.12);
                        border-radius: 6px;
                        background: rgba(255, 255, 255, 0.72);
                        color: #7b8794;
                        cursor: grab;
                        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
                        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
                    }

                    .category-sort-handle:hover,
                    .category-sort-handle:focus-visible {
                        color: #182433;
                        background: rgba(32, 107, 196, 0.06);
                        border-color: rgba(32, 107, 196, 0.16);
                        box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.07);
                        outline: none;
                    }

                    .category-sort-handle:active {
                        cursor: grabbing;
                        background: rgba(32, 107, 196, 0.12);
                        border-color: rgba(32, 107, 196, 0.24);
                        box-shadow: none;
                    }

                    .category-sort-handle svg {
                        opacity: 0.78;
                    }

                    .category-sort-handle.is-disabled {
                        cursor: not-allowed;
                        opacity: 0.45;
                        pointer-events: none;
                    }

                    #categories-table tbody tr.sortable-ghost {
                        opacity: 0.45;
                    }

                    #categories-table tbody tr.sortable-chosen {
                        box-shadow: inset 0 0 0 9999px rgba(32, 107, 196, 0.05);
                    }
                </style>
            `);
        }

        function dataTableInstance() {
            return $.fn.DataTable.isDataTable('#categories-table') ? table.DataTable() : null;
        }

        function currentOrder() {
            return table.find('tbody tr.category-row').map(function () {
                return Number($(this).data('category-id'));
            }).get();
        }

        function refreshSortBadges() {
            table.find('tbody tr.category-row').each(function (index) {
                $(this).find('.category-sort-badge').text(index + 1);
            });
        }

        function setSortingAvailability(enabled) {
            table.find('.category-sort-handle').toggleClass('is-disabled', !enabled);
        }

        function persistOrder() {
            return $.ajax({
                url: reorderUrl,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order: currentOrder(),
                },
            }).done((response) => {
                if (!response.success) {
                    toastError(response.message || 'Unable to update category order.');
                    table.DataTable().ajax.reload(null, false);
                    return;
                }

                toastSuccess(response.message || 'Category order updated.');
            }).fail(() => {
                toastError('Unable to update category order.');
                table.DataTable().ajax.reload(null, false);
            });
        }

        function initCategorySorting() {
            const dt = dataTableInstance();
            const tbody = table.find('tbody').get(0);

            if (categorySortable) {
                categorySortable.destroy();
                categorySortable = null;
            }

            if (!dt || !tbody) {
                return;
            }

            const isFiltered = !!dt.search();
            setSortingAvailability(!isFiltered);

            if (isFiltered || typeof Sortable === 'undefined') {
                return;
            }

            categorySortable = Sortable.create(tbody, {
                animation: 150,
                handle: '.category-sort-handle',
                draggable: 'tr.category-row',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function () {
                    refreshSortBadges();
                    persistOrder();
                },
            });
        }

        $('#refresh').on('click', function () {
            const dt = dataTableInstance();
            if (dt) {
                dt.ajax.reload();
            }
        });

        table.on('draw.dt', function () {
            refreshSortBadges();
            initCategorySorting();
        });
    })();

    (function () {
        const modal = document.getElementById('category-modal');
        if (!modal) {
            return;
        }

        const form = modal.querySelector('form');
        const titleInput = modal.querySelector('#category-title-input');
        const descriptionInput = modal.querySelector('#category-description-input');
        const seoTitleInput = modal.querySelector('#category-seo-title-input');
        const seoDescriptionInput = modal.querySelector('#category-seo-description-input');
        const seoKeywordsInput = modal.querySelector('#category-seo-keywords-input');
        const seoKeywordsValueInput = modal.querySelector('#category-seo-keywords-value');
        const seoTitleCount = document.getElementById('catSeoTitleCount');
        const seoDescriptionCount = document.getElementById('catSeoDescCount');
        const schemaModeSelect = modal.querySelector('#category-schema-mode-select');
        const schemaJsonLdWrap = modal.querySelector('#category-schema-json-ld-wrap');

        let seoTitleManuallyEdited = false;
        let seoDescriptionManuallyEdited = false;
        let programmaticSeoUpdate = false;

        function normalizeText(value) {
            return (value || '').replace(/\s+/g, ' ').trim();
        }

        function normalizeKeywords(value) {
            const seen = new Set();

            return (value || '')
                .split(',')
                .map((keyword) => normalizeText(keyword))
                .filter((keyword) => {
                    if (!keyword) {
                        return false;
                    }

                    const normalizedKeyword = keyword.toLowerCase();
                    if (seen.has(normalizedKeyword)) {
                        return false;
                    }

                    seen.add(normalizedKeyword);
                    return true;
                });
        }

        function generatedSeoTitle() {
            return normalizeText(titleInput?.value || '');
        }

        function generatedSeoDescription() {
            return normalizeText(descriptionInput?.value || '');
        }

        function updateCounter(el, counterEl, max) {
            if (!el || !counterEl) {
                return;
            }

            const len = el.value.length;
            counterEl.textContent = len + ' / ' + max;
            counterEl.style.color = len > max ? '#d63939' : (len >= max * 0.9 ? '#f59f00' : '');
        }

        function refreshCounters() {
            updateCounter(seoTitleInput, seoTitleCount, 255);
            updateCounter(seoDescriptionInput, seoDescriptionCount, 500);
        }

        function toggleSchemaJsonLdField() {
            if (!schemaModeSelect || !schemaJsonLdWrap) {
                return;
            }

            schemaJsonLdWrap.style.display = schemaModeSelect.value === 'custom' ? '' : 'none';
        }

        function setSeoFieldValue(field, value) {
            if (!field) {
                return;
            }

            programmaticSeoUpdate = true;
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            programmaticSeoUpdate = false;
        }

        function syncSeoTitleFromCategory() {
            if (!seoTitleInput || seoTitleManuallyEdited) {
                return;
            }

            setSeoFieldValue(seoTitleInput, generatedSeoTitle());
        }

        function syncSeoDescriptionFromCategory() {
            if (!seoDescriptionInput || seoDescriptionManuallyEdited) {
                return;
            }

            setSeoFieldValue(seoDescriptionInput, generatedSeoDescription());
        }

        function syncKeywordInputValue() {
            if (!seoKeywordsInput || !seoKeywordsValueInput) {
                return;
            }

            const control = seoKeywordsInput.tomselect;
            if (!control) {
                seoKeywordsValueInput.value = '';
                return;
            }

            seoKeywordsValueInput.value = normalizeKeywords(control.items.join(',')).join(', ');
        }

        function setKeywordTags(value) {
            if (!seoKeywordsInput || !seoKeywordsValueInput) {
                return;
            }

            const keywords = normalizeKeywords(value);
            const control = seoKeywordsInput.tomselect;

            if (!control) {
                seoKeywordsValueInput.value = keywords.join(', ');
                return;
            }

            control.clear(true);
            control.clearOptions();
            keywords.forEach((keyword) => {
                control.addOption({ value: keyword, text: keyword });
            });
            control.setValue(keywords, true);
            seoKeywordsValueInput.value = keywords.join(', ');
        }

        function ensureKeywordTagsInput() {
            if (!seoKeywordsInput || !window.TomSelect || seoKeywordsInput.tomselect) {
                return;
            }

            new TomSelect(seoKeywordsInput, {
                create: (input) => {
                    const keyword = normalizeText(input);
                    return keyword ? { value: keyword, text: keyword } : false;
                },
                createOnBlur: true,
                persist: false,
                delimiter: ',',
                hideSelected: true,
                duplicates: false,
                maxOptions: 100,
                onChange: syncKeywordInputValue,
                onBlur: syncKeywordInputValue,
            });

            setKeywordTags(seoKeywordsValueInput?.value || '');
        }

        function hydrateSeoState(mode) {
            const currentSeoTitle = normalizeText(seoTitleInput?.value || '');
            const currentSeoDescription = normalizeText(seoDescriptionInput?.value || '');

            seoTitleManuallyEdited = mode === 'edit'
                && currentSeoTitle !== ''
                && currentSeoTitle !== generatedSeoTitle();
            seoDescriptionManuallyEdited = mode === 'edit'
                && currentSeoDescription !== ''
                && currentSeoDescription !== generatedSeoDescription();

            if (mode !== 'edit') {
                seoTitleManuallyEdited = false;
                seoDescriptionManuallyEdited = false;
            }

            syncSeoTitleFromCategory();
            syncSeoDescriptionFromCategory();
            setKeywordTags(seoKeywordsValueInput?.value || '');
            refreshCounters();
        }

        titleInput?.addEventListener('input', syncSeoTitleFromCategory);
        descriptionInput?.addEventListener('input', syncSeoDescriptionFromCategory);

        seoTitleInput?.addEventListener('input', function () {
            if (!programmaticSeoUpdate) {
                seoTitleManuallyEdited = normalizeText(seoTitleInput.value) !== generatedSeoTitle();
            }
            refreshCounters();
        });

        seoDescriptionInput?.addEventListener('input', function () {
            if (!programmaticSeoUpdate) {
                seoDescriptionManuallyEdited = normalizeText(seoDescriptionInput.value) !== generatedSeoDescription();
            }
            refreshCounters();
        });

        modal.addEventListener('shown.bs.modal', function () {
            ensureKeywordTagsInput();
            refreshCounters();
            toggleSchemaJsonLdField();
        });

        document.addEventListener('category-modal:state-applied', function (event) {
            ensureKeywordTagsInput();
            hydrateSeoState(event.detail?.mode || 'create');
            toggleSchemaJsonLdField();
        });

        form?.addEventListener('submit', function () {
            syncKeywordInputValue();
        });

        schemaModeSelect?.addEventListener('change', toggleSchemaJsonLdField);
    })();
</script>
@endpush
