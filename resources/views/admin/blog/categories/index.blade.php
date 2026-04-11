@extends('layouts.admin.app', ['page' => 'blog', 'sub_page' => 'blog_categories'])

@section('title', 'Blog Categories')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Blog Categories', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Blog Categories</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.blog.categories.create') }}" class="btn btn-primary">Add Category</a>
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
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Posts</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($category->cover_image_url)
                                            <img src="{{ $category->cover_image_url }}" alt="" style="width: 56px; height: 40px; object-fit: cover; border-radius: 8px;">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $category->title }}</div>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($category->description, 90) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td>{{ $category->posts_count }}</td>
                                <td>
                                    <span class="badge {{ $category->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a href="{{ route('admin.blog.categories.edit', $category) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.blog.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No blog categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
