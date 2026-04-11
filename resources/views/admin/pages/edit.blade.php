@extends('layouts.admin.app', ['page' => 'CMS Pages'])

@section('title', 'Edit ' . $page->title)

@section('header_data')
    @php
        $page_title = 'Edit ' . $page->title;
        $page_pretitle = 'CMS Pages';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'CMS Pages', 'url' => route('admin.pages.index')],
        ['title' => 'Edit', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Content: {{ $page->title }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.pages.update', $page) }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label required">Page Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="{{ $page->slug }}" disabled>
                            <small class="form-hint">Slug cannot be changed for core system pages.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Page Content</label>
                            <textarea class="form-control hugerte-mytextarea @error('content') is-invalid @enderror" name="content" rows="15">{{ old('content', $page->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <fieldset class="form-fieldset mt-4">
                            <h4 class="mb-3">SEO Settings (Optional)</h4>
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $page->meta_title) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
                            </div>
                        </fieldset>

                        <div class="form-footer text-end mt-4">
                            <a href="{{ route('admin.pages.index') }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
