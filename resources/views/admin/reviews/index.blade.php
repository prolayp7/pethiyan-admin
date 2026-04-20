@extends('admin.layouts.app')

@section('content')
    <div class="container">
        <h1>Product Reviews</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.reviews.store') }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Product ID</label>
                            <input name="product_id" class="form-control" required />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rating</label>
                            <select name="rating" class="form-select">
                                @for($i=5;$i>=1;$i--)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">Title</label>
                            <input name="title" class="form-control" required />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" class="form-control"></textarea>
                        </div>
                        <div class="col-12 mt-2">
                            <button class="btn btn-primary">Add Anonymous Approved Review</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
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
                @foreach($reviews as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->product->title ?? $r->product_id }}</td>
                        <td>{{ $r->user->name ?? 'Anonymous' }}</td>
                        <td>{{ $r->rating }}</td>
                        <td>{{ $r->title }}</td>
                        <td>{{ Str::limit($r->comment, 100) }}</td>
                        <td>{{ ucfirst($r->status) }}</td>
                        <td>
                            @if($r->status !== 'approved')
                                <form method="POST" action="{{ route('admin.reviews.approve', $r->id) }}" style="display:inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                            @endif
                            @if($r->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.reviews.reject', $r->id) }}" style="display:inline">@csrf<button class="btn btn-sm btn-danger">Reject</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $reviews->links() }}
    </div>
@endsection
