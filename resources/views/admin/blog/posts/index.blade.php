@extends('layouts.admin.app', ['page' => 'blog', 'sub_page' => 'blog_posts'])

@section('title', 'Blog Posts')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Blog Posts', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Blog Posts</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary">Add Post</a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by title or excerpt">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Category</th>
                            <th>Published</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @if($post->featured_image_url)
                                            <img src="{{ $post->featured_image_url }}" alt="" style="width: 72px; height: 48px; object-fit: cover; border-radius: 10px;">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $post->title }}</div>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($post->excerpt, 110) }}</div>
                                            @if($post->is_featured)
                                                <span class="badge bg-yellow-lt text-yellow mt-1">Featured</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $post->category?->title ?? 'Uncategorized' }}</td>
                                <td>{{ $post->published_at?->format('d M Y, h:i A') ?? 'Draft' }}</td>
                                <td>
                                    <span class="badge {{ $post->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                        {{ $post->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a href="{{ route('admin.blog.posts.edit', $post) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.blog.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No blog posts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
