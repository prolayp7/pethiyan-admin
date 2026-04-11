@extends('layouts.admin.app', ['page' => $menuAdmin['menus']['active'] ?? "", 'sub_page' => $menuAdmin['menus']['route']['all_menus']['sub_active'] ?? ""])

@section('title', 'Navigation Menus')

@section('header_data')
    @php
        $page_title = 'Navigation Menus';
        $page_pretitle = 'Content Management';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Menus', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Navigation Menus</h3>
                    <x-breadcrumb :items="$breadcrumbs"/>
                </div>
                <div class="card-actions">
                    <div class="row g-2">
                        <div class="col-auto">
                            <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#menu-modal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-2">
                                    <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                </svg>
                                Add Menu
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
            <div class="card-table">
                <div class="row w-full p-3">
                    <x-datatable id="menus-table" :columns="$columns"
                                 route="{{ route('admin.menus.datatable') }}"
                                 :options="['order' => [[0, 'desc']], 'pageLength' => 15]"/>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- CREATE / EDIT MODAL --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="menu-modal" tabindex="-1" role="dialog"
     aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menu-modal-title">Create Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Name</label>
                    <input type="text" class="form-control" id="menu-name" placeholder="e.g. Main Navigation"/>
                    <small class="text-muted">Human-readable label for this menu.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" id="menu-slug" placeholder="e.g. header_main (auto-generated if empty)"/>
                    <small class="text-muted">Machine identifier used by the frontend API. Leave blank to auto-generate.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Location</label>
                    <select class="form-select" id="menu-location">
                        <option value="header">Header (main navigation)</option>
                        <option value="footer">Footer (footer columns)</option>
                    </select>
                    <small class="text-muted">Where this menu appears on the frontend.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="menu-description" rows="2"
                              placeholder="Optional description"></textarea>
                </div>
                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="menu-is-active" checked>
                    <label class="form-check-label" for="menu-is-active">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="menu-save-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="icon icon-2"><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Save Menu
                </button>
            </div>
        </div>
    </div>
</div>

{{-- DELETE CONFIRM MODAL --}}
<div class="modal modal-blur fade" id="delete-menu-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div class="text-muted mt-1">This will delete the menu and all its items permanently.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-menu-btn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const table   = $('#menus-table');
    let editingId = null;
    let deleteId  = null;

    // ── Refresh ───────────────────────────────────────────────────────────
    $('#refresh').on('click', () => table.DataTable().ajax.reload());

    // ── Open CREATE modal ─────────────────────────────────────────────────
    $('#menu-modal').on('show.bs.modal', function (e) {
        if (!editingId) {
            $('#menu-modal-title').text('Create Menu');
            $('#menu-name').val('');
            $('#menu-slug').val('');
            $('#menu-location').val('header');
            $('#menu-description').val('');
            $('#menu-is-active').prop('checked', true);
        }
    }).on('hidden.bs.modal', function () {
        editingId = null;
    });

    // ── Open EDIT modal ───────────────────────────────────────────────────
    $(document).on('click', '.menu-edit-btn', function () {
        const id  = $(this).data('id');
        const url = $(this).data('url');
        editingId = id;
        $.get(url, function (res) {
            if (!res.success) return toastError(res.message);
            const m = res.data;
            $('#menu-modal-title').text('Edit Menu');
            $('#menu-name').val(m.name);
            $('#menu-slug').val(m.slug);
            $('#menu-location').val(m.location ?? 'header');
            $('#menu-description').val(m.description ?? '');
            $('#menu-is-active').prop('checked', m.is_active);
            new bootstrap.Modal(document.getElementById('menu-modal')).show();
        });
    });

    // ── Save (create or update) ───────────────────────────────────────────
    $('#menu-save-btn').on('click', function () {
        const payload = {
            name:        $('#menu-name').val().trim(),
            slug:        $('#menu-slug').val().trim(),
            location:    $('#menu-location').val(),
            description: $('#menu-description').val().trim(),
            is_active:   $('#menu-is-active').is(':checked') ? 1 : 0,
            _token:      '{{ csrf_token() }}',
        };

        if (!payload.name) { toastError('Name is required.'); return; }

        const url    = editingId ? '{{ route("admin.menus.index") }}/' + editingId : '{{ route("admin.menus.store") }}';
        const method = editingId ? 'POST' : 'POST';

        $.ajax({ url, method, data: payload })
            .done(res => {
                if (!res.success) { toastError(res.message); return; }
                toastSuccess(res.message);
                bootstrap.Modal.getInstance(document.getElementById('menu-modal')).hide();
                table.DataTable().ajax.reload();
            })
            .fail(() => toastError('Request failed.'));
    });

    // ── Toggle active ─────────────────────────────────────────────────────
    $(document).on('change', '.menu-toggle-active', function () {
        const url = $(this).data('url');
        $.ajax({ url, method: 'PATCH', data: { _token: '{{ csrf_token() }}' } })
            .done(res => { if (!res.success) toastError(res.message); })
            .fail(() => { this.checked = !this.checked; toastError('Request failed.'); });
    });

    // ── Delete ────────────────────────────────────────────────────────────
    $(document).on('click', '.menu-delete-btn', function () {
        deleteId = $(this).data('id');
        new bootstrap.Modal(document.getElementById('delete-menu-modal')).show();
    });

    $('#confirm-delete-menu-btn').on('click', function () {
        if (!deleteId) return;
        $.ajax({
            url:    '{{ route("admin.menus.index") }}/' + deleteId,
            method: 'DELETE',
            data:   { _token: '{{ csrf_token() }}' },
        }).done(res => {
            bootstrap.Modal.getInstance(document.getElementById('delete-menu-modal')).hide();
            if (!res.success) { toastError(res.message); return; }
            toastSuccess(res.message);
            table.DataTable().ajax.reload();
        }).fail(() => toastError('Request failed.'));
    });

    // ── Helpers ───────────────────────────────────────────────────────────
    function toastSuccess(msg) {
        if (typeof Toastify !== 'undefined') {
            Toastify({ text: msg, backgroundColor: '#2fb344', duration: 3000, gravity: 'top', position: 'right' }).showToast();
        }
    }
    function toastError(msg) {
        if (typeof Toastify !== 'undefined') {
            Toastify({ text: msg, backgroundColor: '#d63939', duration: 4000, gravity: 'top', position: 'right' }).showToast();
        }
    }
})();
</script>
@endpush
