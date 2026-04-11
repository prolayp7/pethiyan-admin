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
                                 :options="['order' => [[0, 'asc']], 'pageLength' => 25]"/>
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
                        <input type="text" class="form-control" id="item-href" placeholder="e.g. /shop"/>
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
<script>
(function () {
    'use strict';

    const menuId    = {{ $menu->id }};
    const baseUrl   = '{{ route("admin.menus.items.index", $menu->id) }}';
    const table     = $('#items-table');
    let editingId   = null;
    let deleteId    = null;

    // ── Refresh ───────────────────────────────────────────────────────────
    $('#refresh').on('click', () => table.DataTable().ajax.reload());

    // ── Reset modal on open ───────────────────────────────────────────────
    $('#item-modal').on('show.bs.modal', function () {
        if (!editingId) {
            $('#item-modal-title').text('Add Menu Item');
            resetItemForm();
        }
    }).on('hidden.bs.modal', () => { editingId = null; });

    function resetItemForm() {
        $('#item-label').val('');
        $('#item-type').val('link');
        $('#item-href').val('');
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
