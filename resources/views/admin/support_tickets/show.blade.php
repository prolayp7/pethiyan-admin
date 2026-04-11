@extends('layouts.admin.app', ['page' => 'support_tickets'])

@section('title', 'Ticket #' . $ticket->id)

@section('header_data')
    @php
        $page_title   = 'Ticket #' . $ticket->id;
        $page_pretitle = 'Support Tickets';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),    'url' => route('admin.dashboard')],
        ['title' => 'Support Tickets',    'url' => route('admin.support-tickets.index')],
        ['title' => 'Ticket #' . $ticket->id, 'url' => ''],
    ];
@endphp

@section('admin-content')
<div class="page-body">
    <div class="container-xl">
        <div class="row g-4">

            {{-- ── Left: Thread ──────────────────────────────────────────── --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $ticket->subject }}</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <span class="badge bg-blue-lt fs-6">{{ $ticket->ticketType?->title }}</span>
                    </div>

                    {{-- Original message --}}
                    <div class="card-body border-bottom">
                        <div class="d-flex gap-3">
                            <span class="avatar avatar-sm rounded-circle bg-blue-lt text-blue">
                                {{ strtoupper(substr($ticket->user?->name ?? 'U', 0, 1)) }}
                            </span>
                            <div class="flex-fill">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $ticket->user?->name ?? $ticket->email }}</strong>
                                    <small class="text-muted">{{ $ticket->created_at->format('d M Y, H:i') }}</small>
                                </div>
                                <p class="mt-2 mb-0 text-muted" style="white-space:pre-line">{{ $ticket->description }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Thread messages --}}
                    <div class="card-body" id="message-thread">
                        @foreach($ticket->messages as $msg)
                            @php $isAdmin = $msg->send_by === 'admin'; @endphp
                            <div class="d-flex gap-3 mb-4 {{ $isAdmin ? 'flex-row-reverse' : '' }}">
                                <span class="avatar avatar-sm rounded-circle {{ $isAdmin ? 'bg-green-lt text-green' : 'bg-blue-lt text-blue' }}">
                                    {{ $isAdmin ? 'A' : strtoupper(substr($ticket->user?->name ?? 'U', 0, 1)) }}
                                </span>
                                <div class="flex-fill {{ $isAdmin ? 'text-end' : '' }}">
                                    <div class="d-flex justify-content-between {{ $isAdmin ? 'flex-row-reverse' : '' }}">
                                        <strong>{{ $isAdmin ? 'Support Team' : ($ticket->user?->name ?? $ticket->email) }}</strong>
                                        <small class="text-muted">{{ $msg->created_at->format('d M Y, H:i') }}</small>
                                    </div>
                                    <div class="mt-1 p-3 rounded {{ $isAdmin ? 'bg-green-lt' : 'bg-blue-lt' }}"
                                         style="white-space:pre-line;display:inline-block;max-width:90%;text-align:left">
                                        {{ $msg->message }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reply box --}}
                    @if($ticket->isOpen())
                        <div class="card-footer">
                            <form id="reply-form">
                                @csrf
                                <div class="mb-3">
                                    <textarea class="form-control" id="reply-message" name="message" rows="4"
                                              placeholder="Type your reply…" required></textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-send fs-5 me-1"></i> Send Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="card-footer text-center text-muted">
                            <i class="ti ti-lock me-1"></i> This ticket is {{ $ticket->status->label() }}. No further replies.
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Right: Sidebar ─────────────────────────────────────────── --}}
            <div class="col-lg-4">

                {{-- Ticket Info --}}
                <div class="card mb-3">
                    <div class="card-header"><h4 class="card-title mb-0">Ticket Details</h4></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">ID</dt>
                            <dd class="col-sm-7">#{{ $ticket->id }}</dd>

                            <dt class="col-sm-5 text-muted">Customer</dt>
                            <dd class="col-sm-7">{{ $ticket->user?->name ?? '—' }}</dd>

                            <dt class="col-sm-5 text-muted">Email</dt>
                            <dd class="col-sm-7">{{ $ticket->email }}</dd>

                            <dt class="col-sm-5 text-muted">Type</dt>
                            <dd class="col-sm-7">{{ $ticket->ticketType?->title ?? '—' }}</dd>

                            <dt class="col-sm-5 text-muted">Created</dt>
                            <dd class="col-sm-7">{{ $ticket->created_at->format('d M Y') }}</dd>

                            <dt class="col-sm-5 text-muted">Updated</dt>
                            <dd class="col-sm-7">{{ $ticket->updated_at->format('d M Y') }}</dd>
                        </dl>
                    </div>
                </div>

                {{-- Update Status --}}
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Update Status</h4></div>
                    <div class="card-body">
                        <form id="status-form">
                            @csrf
                            <div class="mb-3">
                                <select class="form-select" id="ticket-status" name="status">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s->value }}"
                                            {{ $ticket->status === $s ? 'selected' : '' }}>
                                            {{ $s->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const replyUrl  = '{{ route('admin.support-tickets.reply', $ticket->id) }}';
    const statusUrl = '{{ route('admin.support-tickets.status', $ticket->id) }}';
    const csrf      = '{{ csrf_token() }}';

    // ── Send reply ────────────────────────────────────────────────────────────
    $('#reply-form').on('submit', function (e) {
        e.preventDefault();
        const message = $('#reply-message').val().trim();
        if (!message) return;

        $.ajax({
            url: replyUrl, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: { message },
            success: function (res) {
                if (!res.success) { alert(res.message); return; }
                const m = res.data;
                const bubble = `
                    <div class="d-flex gap-3 mb-4 flex-row-reverse">
                        <span class="avatar avatar-sm rounded-circle bg-green-lt text-green">A</span>
                        <div class="flex-fill text-end">
                            <div class="d-flex justify-content-between flex-row-reverse">
                                <strong>Support Team</strong>
                                <small class="text-muted">${m.created_at}</small>
                            </div>
                            <div class="mt-1 p-3 rounded bg-green-lt" style="white-space:pre-line;display:inline-block;max-width:90%;text-align:left">${m.message}</div>
                        </div>
                    </div>`;
                $('#message-thread').append(bubble);
                $('#reply-message').val('');
                window.scrollTo(0, document.body.scrollHeight);
            },
            error: function () { alert('Failed to send reply.'); }
        });
    });

    // ── Update status ─────────────────────────────────────────────────────────
    $('#status-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: statusUrl, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: { status: $('#ticket-status').val() },
            success: function (res) {
                if (res.success) {
                    toastSuccess('Status updated.');
                } else {
                    alert(res.message);
                }
            },
            error: function () { alert('Failed to update status.'); }
        });
    });

    function toastSuccess(msg) {
        const t = document.createElement('div');
        t.className = 'alert alert-success alert-dismissible position-fixed bottom-0 end-0 m-3';
        t.style.zIndex = 9999;
        t.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }
})();
</script>
@endpush
