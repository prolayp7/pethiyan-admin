@extends('layouts.admin.app', ['page' => $menuAdmin['menus']['active'] ?? "", 'sub_page' => $menuAdmin['menus']['route']['all_menus']['sub_active'] ?? ""])

@section('title', 'Menu Items — ' . $menu->name)

@section('header_data')
    @php
        $page_title    = 'Menu Items';
        $page_pretitle = $menu->name;
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),    'url' => route('admin.dashboard')],
        ['title' => 'Menus',              'url' => route('admin.menus.index')],
        ['title' => $menu->name,          'url' => null],
    ];
@endphp

@section('admin-content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        Items in <span class="text-primary">{{ $menu->name }}</span>
                        <code class="ms-2 small">{{ $menu->slug }}</code>
                    </h3>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
                <div class="card-actions">
                    <div class="row g-2">
                        <div class="col-auto">
                            <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">
                                ← Back to Menus
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-outline-primary"
                               data-bs-toggle="modal" data-bs-target="#item-modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-2">
                                    <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                </svg>
                                Add Item
                            </a>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" id="refresh">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
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

            {{-- Type legend ------------------------------------------------}}
            <div class="card-body border-bottom py-2">
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($types as $t)
                        <span class="badge {{ $t->badgeClass() }}">{{ $t->label() }}</span>
                    @endforeach
                    <span class="text-muted small ms-2">
                        Items with type <span class="badge bg-orange-lt">Mega Menu</span>
                        get a <strong>builder</strong> button to manage panels, columns and links.
                    </span>
                </div>
            </div>

            <div class="card-table">
                <div class="row w-full p-3">
                    <x-datatable id="items-table" :columns="$columns"
                                 route="{{ route('admin.menus.items.datatable', $menu->id) }}"
                                 :options="['ordering' => false, 'paging' => false, 'info' => false, 'pageLength' => 500]"/>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- CREATE / EDIT ITEM MODAL --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="item-modal" tabindex="-1" role="dialog"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="item-modal-title">Add Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label required">Label</label>
                        <input type="text" class="form-control" id="item-label" placeholder="e.g. Shop"/>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Type</label>
                        <select class="form-select" id="item-type">
                            @foreach($types as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">URL / href</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="item-href" placeholder="e.g. /shop" autocomplete="off"/>
                            <div id="item-href-suggestions" class="item-href-suggestions"></div>
                        </div>
                        <small class="text-muted">Choose a suggested system URL or enter any custom relative path or full URL.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Target</label>
                        <select class="form-select" id="item-target">
                            <option value="_self">Same tab (_self)</option>
                            <option value="_blank">New tab (_blank)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent Item</label>
                        <select class="form-select" id="item-parent-id">
                            <option value="">— None (top-level) —</option>
                            @foreach($parentItems as $p)
                                <option value="{{ $p->id }}">{{ $p->label }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Use for Shop dropdown children.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="item-sort-order" value="0" min="0"/>
                    </div>

                    {{-- Fields for shop_dropdown items --}}
                    <div class="col-md-6">
                        <label class="form-label">Icon <span class="text-muted">(lucide name)</span></label>
                        <input type="text" class="form-control" id="item-icon" placeholder="e.g. Star, Truck"/>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Badge</label>
                        <input type="text" class="form-control" id="item-badge" placeholder="e.g. Best Seller"/>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" id="item-description"
                               placeholder="Short subtitle for dropdown cards"/>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Accent Colour</label>
                        <input type="color" class="form-control form-control-color w-100" id="item-accent-color"
                               value="#1f4f8a"/>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="item-is-active" checked>
                            <label class="form-check-label" for="item-is-active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="item-save-btn">Save Item</button>
            </div>
        </div>
    </div>
</div>

{{-- DELETE CONFIRM --}}
<div class="modal modal-blur fade" id="delete-item-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Delete this item?</div>
                <div class="text-muted mt-1">Children of this item will also be deleted.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-item-btn">Delete</button>
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

    const menuId    = {{ $menu->id }};
    const baseUrl   = '{{ route("admin.menus.items.index", $menu->id) }}';
    const reorderUrl = '{{ route("admin.menus.items.reorder", $menu->id) }}';
    const table     = $('#items-table');
    const urlSuggestions = @json($urlSuggestions ?? []);
    let editingId   = null;
    let deleteId    = null;
    let itemSortable = null;
    let draggedChildRows = [];

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

                .item-sort-cell {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }

                .item-sort-handle {
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

                .item-sort-handle:hover,
                .item-sort-handle:focus-visible {
                    color: #182433;
                    background: rgba(32, 107, 196, 0.06);
                    border-color: rgba(32, 107, 196, 0.16);
                    box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.07);
                    outline: none;
                }

                .item-sort-handle:active {
                    cursor: grabbing;
                    background: rgba(32, 107, 196, 0.12);
                    border-color: rgba(32, 107, 196, 0.24);
                    box-shadow: none;
                }

                .item-sort-handle svg {
                    opacity: 0.78;
                }

                .item-sort-handle.is-disabled {
                    cursor: not-allowed;
                    opacity: 0.45;
                    pointer-events: none;
                }

                #items-table tbody tr.sortable-ghost {
                    opacity: 0.45;
                }

                #items-table tbody tr.sortable-chosen {
                    box-shadow: inset 0 0 0 9999px rgba(32, 107, 196, 0.05);
                }
            </style>
        `);
    }

    // ── Refresh ───────────────────────────────────────────────────────────
    $('#refresh').on('click', () => table.DataTable().ajax.reload());

    function normalizeParentId(value) {
        return value === undefined || value === null || value === '' ? '' : String(value);
    }

    function dataTableInstance() {
        return $.fn.DataTable.isDataTable('#items-table') ? table.DataTable() : null;
    }

    function rowsForParent(parentId) {
        return table.find('tbody tr.menu-item-row').filter(function () {
            return normalizeParentId($(this).data('parent-id')) === normalizeParentId(parentId);
        });
    }

    function orderForParent(parentId) {
        return rowsForParent(parentId).map(function () {
            return Number($(this).data('item-id'));
        }).get();
    }

    function refreshSortBadges(parentId) {
        rowsForParent(parentId).each(function (index) {
            $(this).find('.item-sort-badge').text(index + 1);
        });
    }

    function setSortingAvailability(enabled) {
        table.find('.item-sort-handle').toggleClass('is-disabled', !enabled);
    }

    function persistItemOrder(parentId) {
        return $.ajax({
            url: reorderUrl,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                parent_id: normalizeParentId(parentId) || null,
                order: orderForParent(parentId),
            },
        }).done((res) => {
            if (!res.success) {
                toastError(res.message || 'Unable to update menu item order.');
                table.DataTable().ajax.reload(null, false);
                return;
            }

            toastSuccess(res.message || 'Menu item order updated.');
        }).fail(() => {
            toastError('Unable to update menu item order.');
            table.DataTable().ajax.reload(null, false);
        });
    }

    function initItemSorting() {
        const dt = dataTableInstance();
        const tbody = table.find('tbody').get(0);

        if (itemSortable) {
            itemSortable.destroy();
            itemSortable = null;
        }

        if (!dt || !tbody) {
            return;
        }

        const isFiltered = !!dt.search();
        setSortingAvailability(!isFiltered);

        if (isFiltered || typeof Sortable === 'undefined') {
            return;
        }

        itemSortable = Sortable.create(tbody, {
            animation: 150,
            handle: '.item-sort-handle',
            draggable: 'tr.menu-item-row',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onStart: function (event) {
                draggedChildRows = [];

                const $row = $(event.item);
                const parentId = normalizeParentId($row.data('parent-id'));

                if (parentId !== '') {
                    return;
                }

                let $next = $row.next();
                while ($next.length && normalizeParentId($next.data('parent-id')) === String($row.data('item-id'))) {
                    const nextNode = $next.get(0);
                    $next = $next.next();
                    draggedChildRows.push(nextNode);
                    nextNode.parentNode.removeChild(nextNode);
                }
            },
            onMove: function (event) {
                return normalizeParentId($(event.dragged).data('parent-id')) === normalizeParentId($(event.related).data('parent-id'));
            },
            onEnd: function (event) {
                const $row = $(event.item);
                const parentId = normalizeParentId($row.data('parent-id'));

                if (parentId === '' && draggedChildRows.length) {
                    let $cursor = $row;
                    draggedChildRows.forEach((childNode) => {
                        $cursor.after(childNode);
                        $cursor = $(childNode);
                    });
                    draggedChildRows = [];
                }

                refreshSortBadges(parentId);
                persistItemOrder(parentId || null);
            },
        });
    }

    table.on('draw.dt', function () {
        refreshSortBadges('');
        table.find('tbody tr.menu-item-root').each(function () {
            refreshSortBadges($(this).data('item-id'));
        });
        initItemSorting();
    });

    // ── Reset modal on open ───────────────────────────────────────────────
    $('#item-modal').on('show.bs.modal', function () {
        if (!editingId) {
            $('#item-modal-title').text('Add Menu Item');
            resetItemForm();
        }
    }).on('hidden.bs.modal', () => {
        editingId = null;
        closeHrefSuggestions();
    });

    function resetItemForm() {
        $('#item-label').val('');
        $('#item-type').val('link');
        $('#item-href').val('');
        closeHrefSuggestions();
        $('#item-target').val('_self');
        $('#item-parent-id').val('');
        $('#item-sort-order').val(0);
        $('#item-icon').val('');
        $('#item-badge').val('');
        $('#item-description').val('');
        $('#item-accent-color').val('#1f4f8a');
        $('#item-is-active').prop('checked', true);
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    $(document).on('click', '.item-edit-btn', function () {
        editingId      = $(this).data('id');
        const url      = $(this).data('url');
        $.get(url, function (res) {
            if (!res.success) { toastError(res.message); return; }
            const d = res.data;
            $('#item-modal-title').text('Edit Menu Item');
            $('#item-label').val(d.label);
            $('#item-type').val(d.type);
            $('#item-href').val(d.href ?? '');
            closeHrefSuggestions();
            $('#item-target').val(d.target ?? '_self');
            $('#item-parent-id').val(d.parent_id ?? '');
            $('#item-sort-order').val(d.sort_order ?? 0);
            $('#item-icon').val(d.icon ?? '');
            $('#item-badge').val(d.badge ?? '');
            $('#item-description').val(d.description ?? '');
            $('#item-accent-color').val(d.accent_color ?? '#1f4f8a');
            $('#item-is-active').prop('checked', !!d.is_active);
            new bootstrap.Modal(document.getElementById('item-modal')).show();
        });
    });

    // ── Save ──────────────────────────────────────────────────────────────
    $('#item-save-btn').on('click', function () {
        const payload = {
            label:        $('#item-label').val().trim(),
            type:         $('#item-type').val(),
            href:         $('#item-href').val().trim(),
            target:       $('#item-target').val(),
            parent_id:    $('#item-parent-id').val() || null,
            sort_order:   $('#item-sort-order').val(),
            icon:         $('#item-icon').val().trim(),
            badge:        $('#item-badge').val().trim(),
            description:  $('#item-description').val().trim(),
            accent_color: $('#item-accent-color').val(),
            is_active:    $('#item-is-active').is(':checked') ? 1 : 0,
            _token:       '{{ csrf_token() }}',
        };
        if (!payload.label) { toastError('Label is required.'); return; }
        const url = editingId ? baseUrl + '/' + editingId : baseUrl;
        $.ajax({ url, method: 'POST', data: payload })
            .done(res => {
                if (!res.success) { toastError(res.message); return; }
                toastSuccess(res.message);
                bootstrap.Modal.getInstance(document.getElementById('item-modal')).hide();
                table.DataTable().ajax.reload();
            })
            .fail(() => toastError('Request failed.'));
    });

    // ── Toggle active ─────────────────────────────────────────────────────
    $(document).on('change', '.item-toggle-active', function () {
        const url = $(this).data('url');
        $.ajax({ url, method: 'PATCH', data: { _token: '{{ csrf_token() }}' } })
            .done(res => { if (!res.success) toastError(res.message); })
            .fail(() => { this.checked = !this.checked; toastError('Request failed.'); });
    });

    // ── Delete ────────────────────────────────────────────────────────────
    $(document).on('click', '.item-delete-btn', function () {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('delete-item-modal')).show();
    });

    $('#confirm-delete-item-btn').on('click', function () {
        if (!deleteId) return;
        $.ajax({
            url:    baseUrl + '/' + deleteId,
            method: 'DELETE',
            data:   { _token: '{{ csrf_token() }}' },
        }).done(res => {
            bootstrap.Modal.getInstance(document.getElementById('delete-item-modal')).hide();
            if (!res.success) { toastError(res.message); return; }
            toastSuccess(res.message);
            table.DataTable().ajax.reload();
        }).fail(() => toastError('Request failed.'));
    });

    const $hrefInput = $('#item-href');
    const $hrefSuggestions = $('#item-href-suggestions');

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

    function renderHrefSuggestions(query) {
        const matches = filterHrefSuggestions(query);

        if (!matches.length) {
            $hrefSuggestions.html('<div class="item-href-empty">No matching system URL. You can still enter a custom URL.</div>');
            $hrefSuggestions.addClass('is-open');
            return;
        }

        const html = matches.map((item, index) => `
            <div class="item-href-option${index === 0 ? ' is-active' : ''}" data-value="${escapeHtml(item.value)}">
                <span class="item-href-option-value">${escapeHtml(item.value)}</span>
                <span class="item-href-option-label">${escapeHtml(item.label || '')}</span>
            </div>
        `).join('');

        $hrefSuggestions.html(html).addClass('is-open');
    }

    function closeHrefSuggestions() {
        $hrefSuggestions.removeClass('is-open').empty();
    }

    function applyHrefSuggestion(value) {
        $hrefInput.val(value).trigger('change').focus();
        closeHrefSuggestions();
    }

    $hrefInput.on('focus input', function () {
        renderHrefSuggestions($(this).val());
    });

    $hrefInput.on('keydown', function (event) {
        if (!$hrefSuggestions.hasClass('is-open')) {
            return;
        }

        const $items = $hrefSuggestions.find('.item-href-option');
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
                applyHrefSuggestion($active.data('value'));
            }
            return;
        } else if (event.key === 'Escape') {
            closeHrefSuggestions();
            return;
        } else {
            return;
        }

        $items.removeClass('is-active');
        $active = $items.eq(index).addClass('is-active');

        const dropdown = $hrefSuggestions.get(0);
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

    $(document).on('mousedown', '.item-href-option', function (event) {
        event.preventDefault();
        applyHrefSuggestion($(this).data('value'));
    });

    $(document).on('mousedown', function (event) {
        if (!$(event.target).closest('#item-href, #item-href-suggestions').length) {
            closeHrefSuggestions();
        }
    });

    function toastSuccess(msg) {
        if (typeof Toastify !== 'undefined')
            Toastify({ text: msg, backgroundColor: '#2fb344', duration: 3000, gravity: 'top', position: 'right' }).showToast();
    }
    function toastError(msg) {
        if (typeof Toastify !== 'undefined')
            Toastify({ text: msg, backgroundColor: '#d63939', duration: 4000, gravity: 'top', position: 'right' }).showToast();
    }
})();
</script>
@endpush
