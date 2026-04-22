@extends('layouts.admin.app', ['page' => 'CMS Pages'])

@section('title', 'CMS Pages')

@section('header_data')
    @php
        $page_title = 'CMS Pages';
        $page_pretitle = 'Manage';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'CMS Pages', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Manage Other Pages</h3>
                        <p class="card-subtitle">System pages stay fixed. Create custom pages with flexible text, image, video, and SEO controls.</p>
                    </div>
                    <div class="card-actions">
                        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
                            Create Page
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Page Title</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th class="w-1">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pages as $page)
                                <tr>
                                    <td>{{ $page->id }}</td>
                                    <td>{{ $page->title }}</td>
                                    <td class="text-muted">{{ $page->slug }}</td>
                                    <td>
                                        @if($page->status === 'active')
                                            <span class="badge bg-success me-1"></span> Active
                                        @else
                                            <span class="badge bg-danger me-1"></span> Inactive
                                        @endif
                                        @if($page->system_page)
                                            <div class="text-muted small mt-1">Fixed system page</div>
                                        @else
                                            <div class="text-muted small mt-1">Custom page</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-primary">
                                                Edit
                                            </a>
                                            @unless($page->system_page)
                                                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Delete this custom page?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No CMS Pages found. Check seeder.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
