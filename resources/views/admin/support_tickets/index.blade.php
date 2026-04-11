@extends('layouts.admin.app', ['page' => 'support_tickets'])

@section('title', 'Support Tickets')

@section('header_data')
    @php
        $page_title   = 'Support Tickets';
        $page_pretitle = 'List';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Support Tickets', 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Support Tickets</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2 align-items-center">
                            {{-- Status filter --}}
                            <div class="col-auto">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $s)
                                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Type filter --}}
                            <div class="col-auto">
                                <select class="form-select" id="typeFilter">
                                    <option value="">All Types</option>
                                    @foreach($ticketTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Manage types --}}
                            <div class="col-auto">
                                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#type-modal">
                                    <i class="ti ti-tags fs-5 me-1"></i> Manage Types
                                </button>
                            </div>
                            {{-- Refresh --}}
                            <div class="col-auto">
                                <button class="btn btn-outline-primary" id="refresh">
                                    <i class="ti ti-refresh fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="tickets-table" :columns="$columns"
                                     route="{{ route('admin.support-tickets.datatable') }}"
                                     :options="['order' => [[0, 'desc']], 'pageLength' => 15]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Manage Ticket Types Modal ─────────────────────────────────────────── --}}
<div class="modal modal-blur fade" id="type-modal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Ticket Types</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Add new type --}}
                <form id="type-form" class="d-flex gap-2 mb-4">
                    @csrf
                    <input type="hidden" id="type-id" value="">
                    <input type="text" class="form-control" id="type-title" placeholder="Type name (e.g. Billing)" required>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                    <button type="button" class="btn btn-ghost-secondary d-none" id="type-cancel">Cancel</button>
                </form>
                {{-- Existing types list --}}
                <ul class="list-group" id="types-list">
                    @foreach($ticketTypes as $type)
                        <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $type->id }}">
                            <span>{{ $type->title }}</span>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-ghost-primary btn-edit-type"
                                        data-id="{{ $type->id }}" data-title="{{ $type->title }}">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-ghost-danger btn-delete-type"
                                        data-url="{{ route('admin.support-tickets.types.destroy', $type->id) }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const table = $('#tickets-table');

    // ── Datatable extra filters ───────────────────────────────────────────────
    $.fn.dataTable.ext.search.push(function () { return true; }); // placeholder

    $('#statusFilter, #typeFilter').on('change', function () {
        table.DataTable().ajax.reload();
    });

    // Attach filters as query params on each AJAX call
    table.on('preXhr.dt', function (e, settings, data) {
        data.status         = $('#statusFilter').val();
        data.ticket_type_id = $('#typeFilter').val();
    });

    $('#refresh').on('click', function () {
        table.DataTable().ajax.reload();
    });

    // ── Delete ticket ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        if (!confirm('Delete this ticket?')) return;
        $.ajax({
            url, type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function () { table.DataTable().ajax.reload(); },
            error:   function () { alert('Failed to delete ticket.'); }
        });
    });

    // ── Ticket Types ─────────────────────────────────────────────────────────
    $('#type-form').on('submit', function (e) {
        e.preventDefault();
        const id    = $('#type-id').val();
        const title = $('#type-title').val().trim();
        const url   = id
            ? '{{ route('admin.support-tickets.types.update', ['type' => '__ID__']) }}'.replace('__ID__', id)
            : '{{ route('admin.support-tickets.types.store') }}';

        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { title },
            success: function (res) {
                if (res.success) location.reload();
                else alert(res.message);
            }
        });
    });

    $(document).on('click', '.btn-edit-type', function () {
        $('#type-id').val($(this).data('id'));
        $('#type-title').val($(this).data('title'));
        $('#type-cancel').removeClass('d-none');
    });

    $('#type-cancel').on('click', function () {
        $('#type-id').val('');
        $('#type-title').val('');
        $(this).addClass('d-none');
    });

    $(document).on('click', '.btn-delete-type', function () {
        if (!confirm('Delete this type?')) return;
        $.ajax({
            url: $(this).data('url'), type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function (res) {
                if (res.success) location.reload();
                else alert(res.message);
            }
        });
    });
})();
</script>
@endpush
