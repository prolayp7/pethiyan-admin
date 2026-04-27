@extends('layouts.admin.app', ['page' => 'products'])

@section('title', 'Product Reviews')

@section('header_data')
    @php
        $page_title = 'Product Reviews';
        $page_pretitle = 'Admin Product Reviews';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Reviews', 'url' => ''],
    ];
@endphp

@section('admin-content')
    <div class="page-wrapper">
        <div class="page-body">
            <div class="container-xl">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible mb-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Add Anonymous Review --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Add Anonymous Approved Review</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.reviews.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Product <span class="text-danger">*</span></label>
                                    <select id="product-search" name="product_id"
                                            class="form-select @error('product_id') is-invalid @enderror"
                                            placeholder="Search product…" required>
                                        @if(old('product_id'))
                                            <option value="{{ old('product_id') }}" selected>ID: {{ old('product_id') }}</option>
                                        @endif
                                    </select>
                                    @error('product_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Rating <span class="text-danger">*</span></label>
                                    <select name="rating" class="form-select">
                                        @for($i = 5; $i >= 1; $i--)
                                            <option value="{{ $i }}" @selected(old('rating', 5) == $i)>
                                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input name="title" class="form-control @error('title') is-invalid @enderror"
                                           value="{{ old('title') }}" placeholder="Review title" required />
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Comment</label>
                                    <textarea name="comment" class="form-control @error('comment') is-invalid @enderror"
                                              rows="3" placeholder="Write review comment…">{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                        </svg>
                                        Add Review
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Reviews Table --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Reviews</h3>
                        <div class="card-actions">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <select class="form-select" id="statusFilter" onchange="filterReviews()">
                                        <option value="">All Statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th class="w-1">ID</th>
                                    <th>Product</th>
                                    <th>User</th>
                                    <th>Rating</th>
                                    <th>Title</th>
                                    <th>Comment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reviews as $r)
                                    <tr data-status="{{ $r->status }}">
                                        <td class="text-muted">{{ $r->id }}</td>
                                        <td>
                                            <span class="text-body">{{ $r->product->title ?? '—' }}</span>
                                            @if(!$r->product)
                                                <small class="text-muted d-block">ID: {{ $r->product_id }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($r->user)
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm me-2 rounded-circle"
                                                          style="background-image: url({{ $r->user->avatar_url ?? '' }})">
                                                        @if(!$r->user->avatar_url)
                                                            {{ strtoupper(substr($r->user->name, 0, 1)) }}
                                                        @endif
                                                    </span>
                                                    {{ $r->user->name }}
                                                </div>
                                            @else
                                                <span class="text-muted">Anonymous</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                         viewBox="0 0 24 24"
                                                         fill="{{ $i <= $r->rating ? '#f59e0b' : 'none' }}"
                                                         stroke="{{ $i <= $r->rating ? '#f59e0b' : '#d1d5db' }}"
                                                         stroke-width="2">
                                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                    </svg>
                                                @endfor
                                                <span class="text-muted ms-1">{{ $r->rating }}/5</span>
                                            </div>
                                        </td>
                                        <td>{{ $r->title ?: '—' }}</td>
                                        <td>
                                            <span title="{{ $r->comment }}">{{ Str::limit($r->comment, 80) ?: '—' }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $badge = match($r->status) {
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    default    => 'bg-warning text-dark',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ ucfirst($r->status) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @if($r->status !== 'approved')
                                                    <form method="POST" action="{{ route('admin.reviews.approve', $r->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            Approve
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($r->status !== 'rejected')
                                                    <form method="POST" action="{{ route('admin.reviews.reject', $r->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Reject
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('admin.reviews.destroy', $r->id) }}"
                                                      onsubmit="return confirm('Delete this review? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16"
                                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                             stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No reviews found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($reviews->hasPages())
                        <div class="card-footer d-flex align-items-center">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function filterReviews() {
    const val = document.getElementById('statusFilter').value.toLowerCase();
    document.querySelectorAll('tbody tr[data-status]').forEach(row => {
        row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('product-search');
    if (el && window.TomSelect) {
        new TomSelect(el, {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            copyClassesToDropdown: false,
            dropdownParent: 'body',
            placeholder: 'Search product by name…',
            render: {
                item:   (data, escape) => `<div>${escape(data.text)}</div>`,
                option: (data, escape) => `<div>${escape(data.text)}</div>`,
                no_results: () => `<div class="no-results">No products found</div>`,
            },
            load: function (query, callback) {
                if (!query.length) return callback();
                fetch('{{ route('admin.products.search') }}?search=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(json => callback(json))
                    .catch(() => callback());
            },
        });
    }
});
</script>
@endpush
