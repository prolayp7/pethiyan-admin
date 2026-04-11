@extends('layouts.admin.app', ['page' => 'Enquiries'])

@section('title', 'View Enquiry')

@section('header_data')
    @php
        $page_title = 'View Submission';
        $page_pretitle = 'Enquiries';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Enquiries', 'url' => route('admin.enquiries.index')],
        ['title' => 'View', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Enquiry Details</h3>
                    <span class="badge bg-secondary-lt">{{ ucfirst($enquiry->type) }} form</span>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $enquiry->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Email</div>
                            <div class="datagrid-content"><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Phone</div>
                            <div class="datagrid-content">
                                @if($enquiry->phone)
                                    <a href="tel:{{ $enquiry->phone }}">{{ $enquiry->phone }}</a>
                                @else
                                    <span class="text-muted">Not provided</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Date Submitted</div>
                            <div class="datagrid-content">{{ $enquiry->created_at->format('F d, Y \a\t H:i:s') }}</div>
                        </div>
                        @if($enquiry->subject)
                        <div class="datagrid-item w-100">
                            <div class="datagrid-title">Subject</div>
                            <div class="datagrid-content fw-bold">{{ $enquiry->subject }}</div>
                        </div>
                        @endif
                        <div class="datagrid-item w-100 mt-4">
                            <div class="datagrid-title">Message</div>
                            <div class="datagrid-content p-3 border rounded bg-light">
                                {!! nl2br(e($enquiry->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end mt-4">
                    <a href="{{ route('admin.enquiries.index') }}" class="btn btn-secondary">Back to List</a>
                    <a href="mailto:{{ $enquiry->email }}" class="btn btn-primary">Reply via Email</a>
                </div>
            </div>
        </div>
    </div>
@endsection
