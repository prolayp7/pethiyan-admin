@extends('layouts.admin.app', ['page' => 'blog', 'sub_page' => 'blog_posts'])

@section('title', $post->exists ? 'Edit Blog Post' : 'Add Blog Post')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Blog Posts', 'url' => route('admin.blog.posts.index')],
        ['title' => $post->exists ? 'Edit' : 'Add', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">{{ $post->exists ? 'Edit Blog Post' : 'Add Blog Post' }}</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $post->exists ? route('admin.blog.posts.update', $post) : route('admin.blog.posts.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Title</label>
                            <input type="text" class="form-control" name="title" value="{{ old('title', $post->title) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="blog_category_id">
                                <option value="">Uncategorized</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) old('blog_category_id', $post->blog_category_id) === (string) $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Published At</label>
                            <input type="datetime-local" class="form-control" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reading Time (minutes)</label>
                            <input type="number" class="form-control" name="reading_time" min="1" max="120" value="{{ old('reading_time', $post->reading_time ?: '') }}" placeholder="Auto">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Excerpt</label>
                            <textarea class="form-control" name="excerpt" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Content</label>
                            <textarea class="hugerte-mytextarea form-control" name="content" rows="12">{{ old('content', $post->content) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Featured Image</label>
                            <x-filepond_image name="featured_image" imageUrl="{{ $post->featured_image_url ?? '' }}"/>
                            <div class="form-hint mt-2">Recommended: 1600 x 900 px. Max upload size: 5 MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Author Avatar</label>
                            <x-filepond_image name="author_avatar" imageUrl="{{ $post->author_avatar_url ?? '' }}"/>
                            <div class="form-hint mt-2">Recommended: 256 x 256 px. Max upload size: 3 MB.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Author Name</label>
                            <input type="text" class="form-control" name="author_name" value="{{ old('author_name', $post->author_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Author Role</label>
                            <input type="text" class="form-control" name="author_role" value="{{ old('author_role', $post->author_role) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tags</label>
                            <input type="text" class="form-control" name="tags_input" value="{{ old('tags_input', $tagString) }}" placeholder="packaging, logistics, branding">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Author Bio</label>
                            <textarea class="form-control" name="author_bio" rows="3">{{ old('author_bio', $post->author_bio) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" name="meta_title" maxlength="255" value="{{ old('meta_title', $post->metadata['meta_title'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" name="meta_description" rows="2" maxlength="500">{{ old('meta_description', $post->metadata['meta_description'] ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" name="meta_keywords" maxlength="1000" value="{{ old('meta_keywords', $post->metadata['meta_keywords'] ?? '') }}" placeholder="packaging, courier bags, custom printing">
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" {{ old('is_featured', $post->exists ? $post->is_featured : false) ? 'checked' : '' }}>
                                <span class="form-check-label">Mark as featured</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $post->exists ? $post->is_active : true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-link link-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">{{ $post->exists ? 'Update Post' : 'Create Post' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
