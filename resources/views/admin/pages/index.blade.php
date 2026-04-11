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
                        <p class="card-subtitle">Edit the content of your static website pages.</p>
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
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-primary">
                                            Edit
                                        </a>
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
