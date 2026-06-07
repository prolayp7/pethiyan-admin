@extends('layouts.admin.app', ['page' => 'authors'])

@section('title', 'Authors')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Authors', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Authors</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.authors.create') }}" class="btn btn-primary">Add Author</a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Author</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($authors as $author)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($author->image_url)
                                            <img src="{{ $author->image_url }}" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $author->name }}</div>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($author->bio, 90) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $author->role }}</td>
                                <td>
                                    <span class="badge {{ $author->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                        {{ $author->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a href="{{ route('admin.authors.edit', $author) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.authors.destroy', $author) }}" onsubmit="return confirm('Delete this author?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No authors yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $authors->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
