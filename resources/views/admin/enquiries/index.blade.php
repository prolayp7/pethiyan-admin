@extends('layouts.admin.app', ['page' => 'Enquiries'])

@section('title', 'Manage Enquiries')

@section('header_data')
    @php
        $page_title = 'Enquiries & Contact Us';
        $page_pretitle = 'Manage';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Enquiries', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Submissions</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th class="w-1">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($enquiries as $enq)
                                <tr>
                                    <td class="text-nowrap text-muted">{{ $enq->created_at->format('M d, Y H:i') }}</td>
                                    <td><span class="badge bg-secondary-lt">{{ ucfirst($enq->type) }}</span></td>
                                    <td>{{ $enq->name }}</td>
                                    <td class="text-muted">{{ $enq->email }}</td>
                                    <td>
                                        @if($enq->status === 'unread')
                                            <span class="badge bg-danger">Unread</span>
                                        @elseif($enq->status === 'read')
                                            <span class="badge bg-warning">Read</span>
                                        @else
                                            <span class="badge bg-success">Resolved</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.enquiries.show', $enq) }}" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                        <form action="{{ route('admin.enquiries.destroy', $enq) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this enquiry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No enquiries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($enquiries->hasPages())
                    <div class="card-footer d-flex align-items-center">
                        {{ $enquiries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
