@extends('layouts.admin.app', ['page' => 'authors'])

@section('title', $author->exists ? 'Edit Author' : 'Add Author')

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Authors', 'url' => route('admin.authors.index')],
        ['title' => $author->exists ? 'Edit' : 'Add', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">{{ $author->exists ? 'Edit Author' : 'Add Author' }}</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $author->exists ? route('admin.authors.update', $author) : route('admin.authors.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $author->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" name="role" value="{{ old('role', $author->role) }}" placeholder="e.g. Content Writer">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="4">{{ old('bio', $author->bio) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            @if($author->exists && ($author->image_url ?? ''))
                                <div class="mb-2 d-flex align-items-center gap-3">
                                    <img src="{{ $author->image_url }}" alt="Author image" class="rounded-circle border" style="width: 64px; height: 64px; object-fit: cover;">
                                    <span class="text-muted small">Current image — upload a new file below to replace it.</span>
                                </div>
                            @endif
                            <x-filepond_image name="image" imageUrl=""/>
                            <div class="form-hint mt-2">Recommended: square image, at least 300 x 300 px. Max upload size: 3 MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $author->exists ? $author->is_active : true) ? 'checked' : '' }}>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('admin.authors.index') }}" class="btn btn-link link-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">{{ $author->exists ? 'Update Author' : 'Create Author' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
