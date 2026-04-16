@extends('layouts.admin.app', ['page' => $menuAdmin['menus']['active'] ?? "", 'sub_page' => $menuAdmin['menus']['route']['all_menus']['sub_active'] ?? ""])

@section('title', 'Mega Menu Builder — ' . $menuItem->label)

@section('header_data')
    @php
        $page_title    = 'Mega Menu Builder';
        $page_pretitle = $menuItem->label . ' › ' . $menu->name;
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),  'url' => route('admin.dashboard')],
        ['title' => 'Menus',            'url' => route('admin.menus.index')],
        ['title' => $menu->name,        'url' => route('admin.menus.items.index', $menu->id)],
        ['title' => $menuItem->label . ' — Mega Menu', 'url' => null],
    ];
    $panelBaseUrl  = route('admin.menus.items.mega-menu.panels.store', [$menu->id, $menuItem->id]);
    $panelReorderUrl = route('admin.menus.items.mega-menu.panels.reorder', [$menu->id, $menuItem->id]);
    $columnBaseUrl = route('admin.menus.items.mega-menu.columns.store', [$menu->id, $menuItem->id, '__PANEL__']);
    $columnReorderUrl = route('admin.menus.items.mega-menu.columns.reorder', [$menu->id, $menuItem->id, '__PANEL__']);
    $linkBaseUrl   = route('admin.menus.items.mega-menu.links.store',   [$menu->id, $menuItem->id, '__PANEL__', '__COL__']);
    $linkReorderUrl = route('admin.menus.items.mega-menu.links.reorder',   [$menu->id, $menuItem->id, '__PANEL__', '__COL__']);
@endphp

@section('admin-content')
<div class="row row-cards">
    <div class="col-12">

        {{-- Header card ------------------------------------------------}}
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Mega Menu Builder</h3>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
                <div class="card-actions">
                    <a href="{{ route('admin.menus.items.index', $menu->id) }}" class="btn btn-outline-secondary me-2">
                        ← Back to Items
                    </a>
                    <button type="button" class="d-none" id="panel-modal-trigger" data-bs-toggle="modal" data-bs-target="#panel-modal"></button>
                    <button type="button" class="d-none" id="column-modal-trigger" data-bs-toggle="modal" data-bs-target="#column-modal"></button>
                    <button type="button" class="d-none" id="link-modal-trigger" data-bs-toggle="modal" data-bs-target="#link-modal"></button>
                    <button type="button" class="d-none" id="delete-confirm-trigger" data-bs-toggle="modal" data-bs-target="#delete-confirm-modal"></button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#panel-modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                        </svg>
                        Add Panel
                    </button>
                </div>
            </div>
            <div class="card-body py-2 border-bottom">
                <small class="text-muted">
                    <strong>Structure:</strong>
                    Each <span class="badge bg-orange-lt">Panel</span> is a sidebar tab (e.g. "Stand-Up Pouches").
                    Each panel has <span class="badge bg-blue-lt">Columns</span> (e.g. "By Closure").
                    Each column has <span class="badge bg-green-lt">Links</span> (individual URLs).
                </small>
            </div>
        </div>

        {{-- Panels accordion ------------------------------------------}}
        @if($panels->isEmpty())
            <div class="empty">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <p class="empty-title">No panels yet</p>
                <p class="empty-subtitle text-muted">Click "Add Panel" to create the first sidebar category.</p>
                <div class="empty-action">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#panel-modal">Add Panel</button>
                </div>
            </div>
        @else
            <div class="accordion" id="panels-accordion">
                @foreach($panels as $panel)
                <div class="card mb-2 panel-sort-item" id="panel-card-{{ $panel->id }}" data-panel-id="{{ $panel->id }}">
                    {{-- Panel header --------------------------------}}
                    <div class="card-header d-flex align-items-center gap-2 py-2"
                         style="border-left: 4px solid {{ $panel->accent_color ?? '#2563eb' }}">
                        <button type="button" class="btn btn-sm p-0 text-muted sortable-handle panel-drag-handle" title="Drag to reorder" aria-label="Drag to reorder panel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <circle cx="9" cy="6" r="1.25"/><circle cx="15" cy="6" r="1.25"/>
                                <circle cx="9" cy="12" r="1.25"/><circle cx="15" cy="12" r="1.25"/>
                                <circle cx="9" cy="18" r="1.25"/><circle cx="15" cy="18" r="1.25"/>
                            </svg>
                        </button>
                        <button class="btn btn-sm p-0 me-2 collapsed" data-bs-toggle="collapse"
                                data-bs-target="#panel-body-{{ $panel->id }}" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="chevron-icon">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>

                        <span class="badge me-2" style="background:{{ $panel->accent_color ?? '#2563eb' }}">
                            {{ $panel->sort_order }}
                        </span>

                        <strong class="flex-fill">{{ $panel->label }}</strong>
                        <code class="text-muted small">{{ $panel->href }}</code>

                        {{-- Active toggle --}}
                        <div class="form-check form-switch mb-0 ms-2">
                            <input class="form-check-input panel-toggle-active" type="checkbox"
                                   {{ $panel->is_active ? 'checked' : '' }}
                                   data-panel-id="{{ $panel->id }}"
                                   data-url="{{ route('admin.menus.items.mega-menu.panels.toggle-active', [$menu->id, $menuItem->id, $panel->id]) }}">
                        </div>

                        {{-- Actions --}}
                        <button type="button" class="btn btn-sm btn-outline-secondary panel-edit-btn ms-1"
                                data-panel-id="{{ $panel->id }}"
                                data-url="{{ route('admin.menus.items.mega-menu.panels.show', [$menu->id, $menuItem->id, $panel->id]) }}">
                            Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-1"
                                data-bs-toggle="modal" data-bs-target="#column-modal"
                                data-panel-id="{{ $panel->id }}">
                            + Column
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger panel-delete-btn ms-1"
                                data-panel-id="{{ $panel->id }}"
                                data-url="{{ route('admin.menus.items.mega-menu.panels.destroy', [$menu->id, $menuItem->id, $panel->id]) }}">
                            Delete
                        </button>
                    </div>

                    {{-- Panel body (columns) ------------------------}}
                    <div class="collapse" id="panel-body-{{ $panel->id }}">
                        <div class="card-body">
                            @if($panel->columns->isEmpty())
                                <p class="text-muted small">No columns yet. Click "+ Column" to add one.</p>
                            @else
                                <div class="row g-3 columns-sortable" id="columns-grid-{{ $panel->id }}" data-panel-id="{{ $panel->id }}">
                                    @foreach($panel->columns as $column)
                                    <div class="col-md-4 column-sort-item" id="column-card-{{ $column->id }}" data-column-id="{{ $column->id }}">
                                        <div class="card card-sm h-100">
                                            <div class="card-header py-2 d-flex align-items-center">
                                                <button type="button" class="btn btn-xs btn-ghost-secondary me-1 sortable-handle column-drag-handle" title="Drag to reorder" aria-label="Drag to reorder column">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <circle cx="9" cy="6" r="1.25"/><circle cx="15" cy="6" r="1.25"/>
                                                        <circle cx="9" cy="12" r="1.25"/><circle cx="15" cy="12" r="1.25"/>
                                                        <circle cx="9" cy="18" r="1.25"/><circle cx="15" cy="18" r="1.25"/>
                                                    </svg>
                                                </button>
                                                <strong class="flex-fill small">{{ $column->heading }}</strong>
                                                <button type="button" class="btn btn-xs btn-outline-secondary column-edit-btn ms-1"
                                                        data-column-id="{{ $column->id }}"
                                                        data-panel-id="{{ $panel->id }}"
                                                        data-url="{{ route('admin.menus.items.mega-menu.columns.show', [$menu->id, $menuItem->id, $panel->id, $column->id]) }}">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-primary link-add-btn ms-1"
                                                        data-column-id="{{ $column->id }}"
                                                        data-panel-id="{{ $panel->id }}"
                                                        data-bs-toggle="modal" data-bs-target="#link-modal">
                                                    + Link
                                                </button>
                                                <button type="button" class="btn btn-xs btn-outline-danger column-delete-btn ms-1"
                                                        data-column-id="{{ $column->id }}"
                                                        data-panel-id="{{ $panel->id }}"
                                                        data-url="{{ route('admin.menus.items.mega-menu.columns.destroy', [$menu->id, $menuItem->id, $panel->id, $column->id]) }}">
                                                    ×
                                                </button>
                                            </div>
                                            <ul class="list-group list-group-flush links-sortable" id="links-list-{{ $column->id }}" data-panel-id="{{ $panel->id }}" data-column-id="{{ $column->id }}">
                                                @foreach($column->links as $link)
                                                <li class="list-group-item py-1 px-2 d-flex align-items-center link-sort-item"
                                                    id="link-row-{{ $link->id }}" data-link-id="{{ $link->id }}">
                                                    <button type="button" class="btn btn-xs btn-ghost-secondary me-1 sortable-handle link-drag-handle" title="Drag to reorder" aria-label="Drag to reorder link">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                            <circle cx="9" cy="6" r="1.25"/><circle cx="15" cy="6" r="1.25"/>
                                                            <circle cx="9" cy="12" r="1.25"/><circle cx="15" cy="12" r="1.25"/>
                                                            <circle cx="9" cy="18" r="1.25"/><circle cx="15" cy="18" r="1.25"/>
                                                        </svg>
                                                    </button>
                                                    <span class="flex-fill small {{ !$link->is_active ? 'text-muted text-decoration-line-through' : '' }}">
                                                        {{ $link->label }}
                                                    </span>
                                                    <div class="d-flex gap-1 ms-2">
                                                        <button type="button" class="btn btn-xs btn-ghost-secondary link-edit-btn"
                                                                data-link-id="{{ $link->id }}"
                                                                data-column-id="{{ $column->id }}"
                                                                data-panel-id="{{ $panel->id }}"
                                                                data-url="{{ route('admin.menus.items.mega-menu.links.show', [$menu->id, $menuItem->id, $panel->id, $column->id, $link->id]) }}">
                                                            ✎
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-ghost-danger link-delete-btn"
                                                                data-link-id="{{ $link->id }}"
                                                                data-column-id="{{ $column->id }}"
                                                                data-panel-id="{{ $panel->id }}"
                                                                data-url="{{ route('admin.menus.items.mega-menu.links.destroy', [$menu->id, $menuItem->id, $panel->id, $column->id, $link->id]) }}">
                                                            ×
                                                        </button>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- PANEL MODAL --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="panel-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="panel-modal-title">Add Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="panel-editing-id"/>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label required">Label</label>
                        <input type="text" class="form-control" id="panel-label" placeholder="e.g. Stand-Up Pouches"/>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="panel-sort-order" value="0" min="0"/>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">URL</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="panel-href" placeholder="/categories/standup-pouches" autocomplete="off"/>
                            <div id="panel-href-suggestions" class="item-href-suggestions"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Accent Colour</label>
                        <input type="color" class="form-control form-control-color w-100" id="panel-accent-color" value="#2563eb"/>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Tagline</label>
                        <input type="text" class="form-control" id="panel-tagline"
                               placeholder="Retail-ready resealable pouches"/>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Banner Image</label>
                        <input type="hidden" id="panel-image-path" value=""/>
                        <input type="file" class="form-control" id="panel-image-upload" name="panel_image"
                               accept="image/jpg,image/jpeg,image/png,image/webp">
                        <div class="form-hint">Recommended: 1200 x 720 px. JPG / PNG / WebP — max 5 MB. Leave empty to keep existing image.</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="panel-is-active" checked>
                            <label class="form-check-label" for="panel-is-active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="panel-save-btn">Save Panel</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- COLUMN MODAL --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="column-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="column-modal-title">Add Column</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="column-editing-id"/>
                <input type="hidden" id="column-panel-id"/>
                <div class="mb-3">
                    <label class="form-label required">Column Heading</label>
                    <input type="text" class="form-control" id="column-heading" placeholder="e.g. By Closure"/>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" class="form-control" id="column-sort-order" value="0" min="0"/>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="column-save-btn">Save Column</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- LINK MODAL --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="link-modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="link-modal-title">Add Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="link-editing-id"/>
                <input type="hidden" id="link-column-id"/>
                <input type="hidden" id="link-panel-id"/>
                <div class="mb-3">
                    <label class="form-label required">Label</label>
                    <input type="text" class="form-control" id="link-label" placeholder="e.g. Ziplock Stand-Up"/>
                </div>
                <div class="mb-3">
                    <label class="form-label required">URL</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="link-href" placeholder="/categories/ziplock-pouches" autocomplete="off"/>
                        <div id="link-href-suggestions" class="item-href-suggestions"></div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="link-sort-order" value="0" min="0"/>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Target</label>
                        <select class="form-select" id="link-target">
                            <option value="_self">Same tab</option>
                            <option value="_blank">New tab</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="link-is-active" checked>
                    <label class="form-check-label" for="link-is-active">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="link-save-btn">Save Link</button>
            </div>
        </div>
    </div>
</div>

{{-- GENERIC DELETE CONFIRM --}}
<div class="modal modal-blur fade" id="delete-confirm-modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title" id="delete-confirm-title">Delete?</div>
                <p class="text-muted small mt-1" id="delete-confirm-body"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    'use strict';

    const CSRF        = '{{ csrf_token() }}';
    const menuId      = {{ $menu->id }};
    const itemId      = {{ $menuItem->id }};
    const panelBase   = '{{ $panelBaseUrl }}';
    const panelReorderUrl = '{{ $panelReorderUrl }}';
    // Replace __PANEL__ / __COL__ at runtime
    const colBaseTPL  = '{{ $columnBaseUrl }}';
    const colReorderTPL = '{{ $columnReorderUrl }}';
    const linkBaseTPL = '{{ $linkBaseUrl }}';
    const linkReorderTPL = '{{ $linkReorderUrl }}';
    const urlSuggestions = @json($urlSuggestions ?? []);
    const panelImageInput = document.getElementById('panel-image-upload');

    let deleteCallback = null;

    if (!document.getElementById('item-href-suggestion-styles')) {
        $('head').append(`
            <style id="item-href-suggestion-styles">
                .item-href-suggestions {
                    position: absolute;
                    top: calc(100% + 4px);
                    left: 0;
                    right: 0;
                    z-index: 1065;
                    display: none;
                    max-height: 220px;
                    overflow-y: auto;
                    background: #fff;
                    border: 1px solid rgba(98, 105, 118, 0.16);
                    border-radius: 8px;
                    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
                    padding: 4px 0;
                }

                .item-href-suggestions.is-open {
                    display: block;
                }

                .item-href-option {
                    padding: 8px 12px;
                    cursor: pointer;
                    line-height: 1.25;
                }

                .item-href-option:hover,
                .item-href-option.is-active {
                    background: rgba(32, 107, 196, 0.08);
                }

                .item-href-option-value {
                    display: block;
                    font-weight: 600;
                    color: #182433;
                    word-break: break-all;
                }

                .item-href-option-label {
                    display: block;
                    margin-top: 2px;
                    font-size: 12px;
                    color: #626976;
                }

                .item-href-empty {
                    padding: 10px 12px;
                    font-size: 12px;
                    color: #626976;
                }

                .sortable-handle {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 26px;
                    height: 26px;
                    padding: 0;
                    border: 1px solid rgba(98, 105, 118, 0.12);
                    border-radius: 7px;
                    color: #7b8794;
                    background: rgba(255, 255, 255, 0.72);
                    cursor: grab;
                    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
                    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
                }

                .sortable-handle:hover,
                .sortable-handle:focus-visible {
                    color: #182433;
                    background: rgba(32, 107, 196, 0.06);
                    border-color: rgba(32, 107, 196, 0.16);
                    box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.07);
                    transform: translateY(-1px);
                    outline: none;
                }

                .sortable-handle:active {
                    cursor: grabbing;
                    background: rgba(32, 107, 196, 0.12);
                    border-color: rgba(32, 107, 196, 0.24);
                    box-shadow: none;
                    transform: none;
                }

                .sortable-handle svg {
                    display: block;
                    opacity: 0.72;
                    transition: opacity 0.15s ease;
                }

                .sortable-handle:hover svg,
                .sortable-handle:focus-visible svg,
                .sortable-handle:active svg {
                    opacity: 0.95;
                }

                .panel-drag-handle {
                    margin-right: 2px;
                }

                .column-drag-handle,
                .link-drag-handle {
                    width: 22px;
                    height: 22px;
                    border-radius: 6px;
                }

                .sortable-ghost {
                    opacity: 0.45;
                }

                .sortable-chosen {
                    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
                }
            </style>
        `);
    }

    function panelUrl(panelId, extra)  { return panelBase.replace('panels', 'panels') + (extra ? '/' + extra : ''); }
    function colBaseUrl(panelId)       { return colBaseTPL.replace('__PANEL__', panelId); }
    function colReorderUrl(panelId)    { return colReorderTPL.replace('__PANEL__', panelId); }
    function linkBaseUrl(panelId, colId) { return linkBaseTPL.replace('__PANEL__', panelId).replace('__COL__', colId); }
    function linkReorderUrl(panelId, colId) { return linkReorderTPL.replace('__PANEL__', panelId).replace('__COL__', colId); }

    /* ── CSRF helper ─────────────────────────────────────────────────── */
    function ajax(url, method, data, done) {
        const isFormData = typeof FormData !== 'undefined' && data instanceof FormData;
        const payload = isFormData ? data : Object.assign({ _token: CSRF }, data);
        if (isFormData && !payload.has('_token')) {
            payload.append('_token', CSRF);
        }

        return $.ajax({
            url,
            method,
            data: payload,
            processData: !isFormData,
            contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8'
        })
            .done(res => {
                if (!res.success) { toastError(res.message); return; }
                done(res);
            })
            .fail((xhr) => toastError(xhr?.responseJSON?.message || 'Request failed.'));
    }

    const normalizeLocalhostOrigin = (url) => {
        if (!url || typeof url !== 'string') return url;

        try {
            const parsed = new URL(url, window.location.origin);
            const isLoopback = ['localhost', '127.0.0.1'].includes(parsed.hostname);
            const currentIsLoopback = ['localhost', '127.0.0.1'].includes(window.location.hostname);

            if (isLoopback && currentIsLoopback) {
                return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`;
            }

            return parsed.toString();
        } catch (_e) {
            return url;
        }
    };

    let panelImagePond = null;

    function ensurePanelImagePond() {
        if (!panelImageInput || typeof FilePond === 'undefined') {
            return null;
        }

        if (panelImagePond) {
            return panelImagePond;
        }

        panelImagePond = FilePond.create(panelImageInput, {
            allowImagePreview: true,
            credits: false,
            storeAsFile: true,
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
            maxFileSize: '5MB',
            server: {
                load: (source, load, error) => {
                    fetch(normalizeLocalhostOrigin(source))
                        .then(response => {
                            const contentType = (response.headers.get('content-type') || 'image/jpeg').split(';')[0].trim();
                            return response.blob().then(blob => new Blob([blob], { type: contentType }));
                        })
                        .then(blob => load(blob))
                        .catch(err => error(err));
                    return { abort: () => {} };
                }
            }
        });

        return panelImagePond;
    }

    function resetPanelImageField() {
        const pond = ensurePanelImagePond();
        $('#panel-image-path').val('');
        if (pond) {
            pond.removeFiles();
        } else if (panelImageInput) {
            panelImageInput.value = '';
        }
    }

    function preloadPanelImage(source) {
        const normalizedSource = normalizeLocalhostOrigin(source);
        const pond = ensurePanelImagePond();

        $('#panel-image-path').val(source || '');

        if (!pond) {
            return;
        }

        pond.removeFiles();

        if (!normalizedSource) {
            return;
        }

        pond.addFile(normalizedSource).catch(() => {
            $('#panel-image-path').val(source || '');
        });
    }

    function setButtonLoadingState($button, loadingLabel) {
        const originalHtml = $button.data('original-html') || $button.html();
        $button.data('original-html', originalHtml);
        $button.prop('disabled', true).html(
            `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingLabel}`
        );
    }

    function resetButtonLoadingState($button) {
        const originalHtml = $button.data('original-html');
        if (originalHtml) {
            $button.html(originalHtml);
        }
        $button.prop('disabled', false);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function filterHrefSuggestions(query) {
        const normalized = query.trim().toLowerCase();

        if (!normalized) {
            return urlSuggestions.slice(0, 40);
        }

        return urlSuggestions.filter((item) => item.value.toLowerCase().includes(normalized)
            || (item.label || '').toLowerCase().includes(normalized)).slice(0, 40);
    }

    function initializeUrlSuggestionField(inputSelector, dropdownSelector) {
        const $input = $(inputSelector);
        const $dropdown = $(dropdownSelector);

        if (!$input.length || !$dropdown.length) {
            return { close: () => {} };
        }

        function render(query) {
            const matches = filterHrefSuggestions(query);

            if (!matches.length) {
                $dropdown.html('<div class="item-href-empty">No matching system URL. You can still enter a custom URL.</div>');
                $dropdown.addClass('is-open');
                return;
            }

            const html = matches.map((item, index) => `
                <div class="item-href-option${index === 0 ? ' is-active' : ''}" data-value="${escapeHtml(item.value)}">
                    <span class="item-href-option-value">${escapeHtml(item.value)}</span>
                    <span class="item-href-option-label">${escapeHtml(item.label || '')}</span>
                </div>
            `).join('');

            $dropdown.html(html).addClass('is-open');
        }

        function close() {
            $dropdown.removeClass('is-open').empty();
        }

        function apply(value) {
            $input.val(value).trigger('change').focus();
            close();
        }

        $input.on('focus input', function () {
            render($(this).val());
        });

        $input.on('keydown', function (event) {
            if (!$dropdown.hasClass('is-open')) {
                return;
            }

            const $items = $dropdown.find('.item-href-option');
            if (!$items.length) {
                return;
            }

            let $active = $items.filter('.is-active');
            let index = $items.index($active);

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                index = Math.min(index + 1, $items.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                index = Math.max(index - 1, 0);
            } else if (event.key === 'Enter') {
                if ($active.length) {
                    event.preventDefault();
                    apply($active.data('value'));
                }
                return;
            } else if (event.key === 'Escape') {
                close();
                return;
            } else {
                return;
            }

            $items.removeClass('is-active');
            $active = $items.eq(index).addClass('is-active');

            const dropdown = $dropdown.get(0);
            const activeEl = $active.get(0);
            if (dropdown && activeEl) {
                const dropdownTop = dropdown.scrollTop;
                const dropdownBottom = dropdownTop + dropdown.clientHeight;
                const activeTop = activeEl.offsetTop;
                const activeBottom = activeTop + activeEl.offsetHeight;

                if (activeBottom > dropdownBottom) {
                    dropdown.scrollTop = activeBottom - dropdown.clientHeight;
                } else if (activeTop < dropdownTop) {
                    dropdown.scrollTop = activeTop;
                }
            }
        });

        $(document).on('mousedown', `${dropdownSelector} .item-href-option`, function (event) {
            event.preventDefault();
            apply($(this).data('value'));
        });

        $(document).on('mousedown', function (event) {
            if (!$(event.target).closest(`${inputSelector}, ${dropdownSelector}`).length) {
                close();
            }
        });

        return { close };
    }

    function reorderRequest(url, order, successMessage) {
        return ajax(url, 'POST', { order }, () => {
            if (successMessage) {
                toastSuccess(successMessage);
            }
        });
    }

    function refreshPanelState() {
        const $panels = $('#panels-accordion').children('.panel-sort-item');

        $panels.each(function (index) {
            $(this).find('.badge').first().text(index + 1);
        });
    }

    function panelOrder() {
        return $('#panels-accordion').children('.panel-sort-item').map(function () {
            return Number($(this).data('panel-id'));
        }).get();
    }

    function columnOrder(panelId) {
        return $(`#columns-grid-${panelId}`).children('.column-sort-item').map(function () {
            return Number($(this).data('column-id'));
        }).get();
    }

    function linkOrder(columnId) {
        return $(`#links-list-${columnId}`).children('.link-sort-item').map(function () {
            return Number($(this).data('link-id'));
        }).get();
    }

    function initSortable() {
        if (typeof Sortable === 'undefined') {
            return;
        }

        const panelsRoot = document.getElementById('panels-accordion');

        if (panelsRoot) {
            Sortable.create(panelsRoot, {
                animation: 150,
                handle: '.panel-drag-handle',
                draggable: '.panel-sort-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function () {
                    refreshPanelState();
                    reorderRequest(panelReorderUrl, panelOrder(), 'Panel order updated.');
                },
            });
        }

        document.querySelectorAll('.columns-sortable').forEach((element) => {
            Sortable.create(element, {
                animation: 150,
                handle: '.column-drag-handle',
                draggable: '.column-sort-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function () {
                    const panelId = element.dataset.panelId;
                    reorderRequest(colReorderUrl(panelId), columnOrder(panelId), 'Column order updated.');
                },
            });
        });

        document.querySelectorAll('.links-sortable').forEach((element) => {
            Sortable.create(element, {
                animation: 150,
                handle: '.link-drag-handle',
                draggable: '.link-sort-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function () {
                    const panelId = element.dataset.panelId;
                    const columnId = element.dataset.columnId;
                    reorderRequest(linkReorderUrl(panelId, columnId), linkOrder(columnId), 'Link order updated.');
                },
            });
        });
    }

    const panelHrefSuggestions = initializeUrlSuggestionField('#panel-href', '#panel-href-suggestions');
    const linkHrefSuggestions = initializeUrlSuggestionField('#link-href', '#link-href-suggestions');

    refreshPanelState();
    initSortable();

    /* ═══════════════════════════════════════════════════════════════
     |  PANELS
     ═══════════════════════════════════════════════════════════════ */
    let editPanelId = null;

    $('#panel-modal').on('show.bs.modal', function () {
        if (!editPanelId) {
            $('#panel-modal-title').text('Add Panel');
            $('#panel-label').val('');
            $('#panel-href').val('');
            $('#panel-tagline').val('');
            $('#panel-accent-color').val('#2563eb');
            $('#panel-sort-order').val(0);
            $('#panel-is-active').prop('checked', true);
            $('#panel-editing-id').val('');
            resetPanelImageField();
        }
    }).on('hidden.bs.modal', () => {
        editPanelId = null;
        panelHrefSuggestions.close();
    });

    $(document).on('click', '.panel-edit-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        editPanelId = $(this).data('panel-id');
        $.get($(this).data('url'), function (res) {
            if (!res.success) { toastError(res.message); return; }
            const p = res.data;
            $('#panel-modal-title').text('Edit Panel');
            $('#panel-editing-id').val(p.id);
            $('#panel-label').val(p.label);
            $('#panel-href').val(p.href);
            panelHrefSuggestions.close();
            $('#panel-tagline').val(p.tagline ?? '');
            $('#panel-accent-color').val(p.accent_color ?? '#2563eb');
            $('#panel-sort-order').val(p.sort_order);
            $('#panel-is-active').prop('checked', !!p.is_active);
            preloadPanelImage(p.image_path ?? '');
            document.getElementById('panel-modal-trigger').click();
        });
    });

    $('#panel-save-btn').on('click', function () {
        const $button = $(this);
        const pid  = $('#panel-editing-id').val();
        const label = $('#panel-label').val().trim();
        const href = $('#panel-href').val().trim();
        if (!label || !href) { toastError('Label and URL are required.'); return; }

        const data = new FormData();
        data.append('label', label);
        data.append('href', href);
        data.append('tagline', $('#panel-tagline').val().trim());
        data.append('image_path', $('#panel-image-path').val().trim());
        data.append('accent_color', $('#panel-accent-color').val());
        data.append('sort_order', $('#panel-sort-order').val());
        data.append('is_active', $('#panel-is-active').is(':checked') ? '1' : '0');

        const pond = ensurePanelImagePond();
        const pondFile = pond?.getFiles?.()[0] ?? null;
        const isLocalPreview =
            typeof FilePond !== 'undefined'
            && FilePond.FileOrigin
            && pondFile
            && pondFile.origin === FilePond.FileOrigin.LOCAL;

        if (pondFile?.file && !isLocalPreview) {
            data.append('panel_image', pondFile.file, pondFile.file.name || 'panel-image');
        } else if (panelImageInput?.files?.[0]) {
            data.append('panel_image', panelImageInput.files[0]);
        }

        const url = pid ? panelBase.replace('/panels', '/panels/' + pid) : panelBase;
        setButtonLoadingState($button, pid ? 'Saving...' : 'Creating...');
        ajax(url, 'POST', data, () => {
            toastSuccess('Panel saved.');
            document.querySelector('#panel-modal [data-bs-dismiss="modal"]').click();
            setTimeout(() => location.reload(), 800);
        }).always(() => {
            resetButtonLoadingState($button);
        });
    });

    $(document).on('click', '.panel-delete-btn', function () {
        const url = $(this).data('url');
        deleteCallback = () => ajax(url, 'DELETE', {}, () => {
            toastSuccess('Panel deleted.');
            setTimeout(() => location.reload(), 800);
        });
        showDeleteConfirm('Delete panel?', 'All columns and links inside will also be deleted.');
    });

    $(document).on('change', '.panel-toggle-active', function () {
        const url = $(this).data('url');
        ajax(url, 'PATCH', {}, res => {});
    });

    /* ═══════════════════════════════════════════════════════════════
     |  COLUMNS
     ═══════════════════════════════════════════════════════════════ */
    let editColumnId = null;
    let editColumnPanelId = null;

    $('#column-modal').on('show.bs.modal', function (e) {
        const btn = $(e.relatedTarget);
        if (btn.hasClass('link-add-btn')) return; // prevent override when link btn triggers
        editColumnId = null;
        if (btn.data('panel-id')) $('#column-panel-id').val(btn.data('panel-id'));
        $('#column-modal-title').text('Add Column');
        $('#column-heading').val('');
        $('#column-sort-order').val(0);
        $('#column-editing-id').val('');
    }).on('hidden.bs.modal', () => { editColumnId = null; });

    $(document).on('click', '.column-edit-btn', function () {
        editColumnId      = $(this).data('column-id');
        editColumnPanelId = $(this).data('panel-id');
        $.get($(this).data('url'), function (res) {
            if (!res.success) { toastError(res.message); return; }
            const c = res.data;
            $('#column-modal-title').text('Edit Column');
            $('#column-editing-id').val(c.id);
            $('#column-panel-id').val(editColumnPanelId);
            $('#column-heading').val(c.heading);
            $('#column-sort-order').val(c.sort_order);
            document.getElementById('column-modal-trigger').click();
        });
    });

    $('#column-save-btn').on('click', function () {
        const cid     = $('#column-editing-id').val();
        const panelId = $('#column-panel-id').val();
        const data    = {
            heading:    $('#column-heading').val().trim(),
            sort_order: $('#column-sort-order').val(),
        };
        if (!data.heading) { toastError('Heading is required.'); return; }
        const base = colBaseUrl(panelId);
        const url  = cid ? base + '/' + cid : base;
        ajax(url, 'POST', data, () => {
            toastSuccess('Column saved.');
            document.querySelector('#column-modal [data-bs-dismiss="modal"]').click();
            setTimeout(() => location.reload(), 800);
        });
    });

    $(document).on('click', '.column-delete-btn', function () {
        const url = $(this).data('url');
        deleteCallback = () => ajax(url, 'DELETE', {}, () => {
            toastSuccess('Column deleted.');
            setTimeout(() => location.reload(), 800);
        });
        showDeleteConfirm('Delete column?', 'All links inside will also be deleted.');
    });

    /* ═══════════════════════════════════════════════════════════════
     |  LINKS
     ═══════════════════════════════════════════════════════════════ */
    let editLinkId = null;

    $('#link-modal').on('show.bs.modal', function (e) {
        const btn = $(e.relatedTarget);
        if (btn.hasClass('link-add-btn')) {
            editLinkId = null;
            $('#link-editing-id').val('');
            $('#link-column-id').val(btn.data('column-id'));
            $('#link-panel-id').val(btn.data('panel-id'));
            $('#link-modal-title').text('Add Link');
            $('#link-label').val('');
            $('#link-href').val('');
            $('#link-sort-order').val(0);
            $('#link-target').val('_self');
            $('#link-is-active').prop('checked', true);
            linkHrefSuggestions.close();
        }
    }).on('hidden.bs.modal', () => {
        editLinkId = null;
        linkHrefSuggestions.close();
    });

    $(document).on('click', '.link-edit-btn', function () {
        editLinkId = $(this).data('link-id');
        const colId   = $(this).data('column-id');
        const panelId = $(this).data('panel-id');
        $.get($(this).data('url'), function (res) {
            if (!res.success) { toastError(res.message); return; }
            const l = res.data;
            $('#link-modal-title').text('Edit Link');
            $('#link-editing-id').val(l.id);
            $('#link-column-id').val(colId);
            $('#link-panel-id').val(panelId);
            $('#link-label').val(l.label);
            $('#link-href').val(l.href);
            linkHrefSuggestions.close();
            $('#link-sort-order').val(l.sort_order);
            $('#link-target').val(l.target ?? '_self');
            $('#link-is-active').prop('checked', !!l.is_active);
            document.getElementById('link-modal-trigger').click();
        });
    });

    $('#link-save-btn').on('click', function () {
        const lid     = $('#link-editing-id').val();
        const colId   = $('#link-column-id').val();
        const panelId = $('#link-panel-id').val();
        const data    = {
            label:      $('#link-label').val().trim(),
            href:       $('#link-href').val().trim(),
            sort_order: $('#link-sort-order').val(),
            target:     $('#link-target').val(),
            is_active:  $('#link-is-active').is(':checked') ? 1 : 0,
        };
        if (!data.label || !data.href) { toastError('Label and URL are required.'); return; }
        const base = linkBaseUrl(panelId, colId);
        const url  = lid ? base + '/' + lid : base;
        ajax(url, 'POST', data, () => {
            toastSuccess('Link saved.');
            document.querySelector('#link-modal [data-bs-dismiss="modal"]').click();
            setTimeout(() => location.reload(), 600);
        });
    });

    $(document).on('click', '.link-delete-btn', function () {
        const url = $(this).data('url');
        deleteCallback = () => ajax(url, 'DELETE', {}, () => {
            toastSuccess('Link deleted.');
            setTimeout(() => location.reload(), 600);
        });
        showDeleteConfirm('Delete link?', '');
    });

    /* ═══════════════════════════════════════════════════════════════
     |  SHARED DELETE CONFIRM
     ═══════════════════════════════════════════════════════════════ */
    function showDeleteConfirm(title, body) {
        $('#delete-confirm-title').text(title);
        $('#delete-confirm-body').text(body);
        document.getElementById('delete-confirm-trigger').click();
    }

    $('#confirm-delete-btn').on('click', function () {
        document.querySelector('#delete-confirm-modal [data-bs-dismiss="modal"]').click();
        if (deleteCallback) { deleteCallback(); deleteCallback = null; }
    });

    /* ═══════════════════════════════════════════════════════════════
     |  TOASTS
     ═══════════════════════════════════════════════════════════════ */
    function toastSuccess(msg) {
        if (typeof Toastify !== 'undefined')
            Toastify({ text: msg, backgroundColor: '#2fb344', duration: 2500, gravity: 'top', position: 'right' }).showToast();
    }
    function toastError(msg) {
        if (typeof Toastify !== 'undefined')
            Toastify({ text: msg, backgroundColor: '#d63939', duration: 4000, gravity: 'top', position: 'right' }).showToast();
    }
})();
</script>
@endpush
