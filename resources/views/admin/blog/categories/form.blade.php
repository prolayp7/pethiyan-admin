@extends('layouts.admin.app', ['page' => 'blog', 'sub_page' => 'blog_categories'])

@section('title', $category->exists ? 'Edit Blog Category' : 'Add Blog Category')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Blog Categories', 'url' => route('admin.blog.categories.index')],
        ['title' => $category->exists ? 'Edit' : 'Add', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">{{ $category->exists ? 'Edit Blog Category' : 'Add Blog Category' }}</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $category->exists ? route('admin.blog.categories.update', $category) : route('admin.blog.categories.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Title</label>
                            <input type="text" class="form-control" name="title" value="{{ old('title', $category->title) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cover Image</label>
                            <x-filepond_image name="cover_image" imageUrl="{{ $category->cover_image_url ?? '' }}"/>
                            <div class="form-hint mt-2">Recommended: 1200 x 630 px. Max upload size: 4 MB.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $category->exists ? $category->is_active : true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" name="meta_title" maxlength="255" value="{{ old('meta_title', $category->metadata['meta_title'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" name="meta_description" rows="2" maxlength="500">{{ old('meta_description', $category->metadata['meta_description'] ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" name="meta_keywords" maxlength="1000" value="{{ old('meta_keywords', $category->metadata['meta_keywords'] ?? '') }}" placeholder="packaging news, shipping tips, ecommerce">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('admin.blog.categories.index') }}" class="btn btn-link link-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">{{ $category->exists ? 'Update Category' : 'Create Category' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
