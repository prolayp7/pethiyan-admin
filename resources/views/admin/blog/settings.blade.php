@extends('layouts.admin.app', ['page' => 'blog', 'sub_page' => 'blog_settings'])

@section('title', 'Blog Settings')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Blog Settings', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Blog Settings</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.blog.settings.update') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Section Visibility</label>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $settings['is_active'] ? 'checked' : '' }}>
                            <span class="form-check-label">Enable the blog section and API content</span>
                        </label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Eyebrow</label>
                            <input type="text" class="form-control" name="eyebrow" value="{{ old('eyebrow', $settings['eyebrow']) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Heading</label>
                            <input type="text" class="form-control" name="heading" value="{{ old('heading', $settings['heading']) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subheading</label>
                            <textarea class="form-control" name="subheading" rows="3">{{ old('subheading', $settings['subheading']) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Featured Section Title</label>
                            <input type="text" class="form-control" name="featured_section_title" value="{{ old('featured_section_title', $settings['featured_section_title']) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Latest Section Title</label>
                            <input type="text" class="form-control" name="latest_section_title" value="{{ old('latest_section_title', $settings['latest_section_title']) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categories Section Title</label>
                            <input type="text" class="form-control" name="categories_section_title" value="{{ old('categories_section_title', $settings['categories_section_title']) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Newsletter CTA Title</label>
                            <input type="text" class="form-control" name="newsletter_title" value="{{ old('newsletter_title', $settings['newsletter_title']) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Posts Per Page</label>
                            <input type="number" class="form-control" name="posts_per_page" min="3" max="30" value="{{ old('posts_per_page', $settings['posts_per_page']) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Newsletter CTA Subtitle</label>
                            <textarea class="form-control" name="newsletter_subtitle" rows="2">{{ old('newsletter_subtitle', $settings['newsletter_subtitle']) }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">Save Blog Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
