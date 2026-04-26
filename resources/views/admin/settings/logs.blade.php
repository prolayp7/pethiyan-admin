@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? ""])

@section('title', 'System Logs')

@section('header_data')
    @php
        $page_title = 'System Logs';
        $page_pretitle = __('labels.admin') . ' ' . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => 'System Logs', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">System Logs</h2>
                <div class="text-secondary">Review recent application log files, including runtime errors and warnings.</div>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Log Files</h3>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($logFiles as $logFile)
                                @php
                                    $isActive = $selectedFileName === $logFile->getFilename();
                                @endphp
                                <a href="{{ route('admin.system-logs.index', ['file' => $logFile->getFilename()]) }}"
                                   class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }}">
                                    <div class="d-flex w-100 justify-content-between align-items-start gap-3">
                                        <div class="min-w-0">
                                            <div class="fw-medium text-truncate">{{ $logFile->getFilename() }}</div>
                                            <div class="small {{ $isActive ? 'text-white-50' : 'text-secondary' }}">
                                                {{ number_format($logFile->getSize()) }} bytes
                                            </div>
                                        </div>
                                        <span class="badge {{ $isActive ? 'bg-white text-primary' : 'bg-primary-lt text-primary' }}">
                                            {{ date('M d H:i', $logFile->getMTime()) }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="list-group-item">
                                    <div class="text-secondary">No `.log` files were found in `storage/logs`.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <h3 class="card-title mb-1">{{ $selectedFileName ?? 'No Log Selected' }}</h3>
                                @if($selectedFileStats)
                                    <div class="text-secondary small">
                                        Updated {{ $selectedFileStats['modified_at'] }} |
                                        Size {{ $selectedFileStats['size_human'] }} |
                                        Preview errors {{ $selectedFileStats['error_count'] }} |
                                        Preview warnings {{ $selectedFileStats['warning_count'] }}
                                    </div>
                                @endif
                            </div>

                            @if($selectedFileName)
                                @can('updateSetting', [\App\Models\Setting::class, 'system'])
                                    <button type="button"
                                            class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#clearLogModal"
                                            data-log-file="{{ $selectedFileName }}">
                                        Clear Log
                                    </button>
                                @endcan
                            @endif
                        </div>

                        <div class="card-body p-0">
                            @if($selectedFileName)
                                @if(empty($logPreview))
                                    <div class="p-4 text-secondary">This log file is empty.</div>
                                @else
                                    <div class="log-preview">
                                        @foreach($logPreview as $line)
                                            @php
                                                $normalized = strtolower($line);
                                                $lineClass = str_contains($normalized, '.error:')
                                                    ? 'log-line log-line-error'
                                                    : (str_contains($normalized, '.warning:') ? 'log-line log-line-warning' : 'log-line');
                                            @endphp
                                            <div class="{{ $lineClass }}">{{ $line }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="p-4 text-secondary">Choose a log file to inspect its recent entries.</div>
                            @endif
                        </div>
                    </div>

                    @if($selectedFileName)
                        <div class="alert alert-warning mt-4 mb-0">
                            Only the most recent {{ count($logPreview) }} log lines are shown here for readability. Older entries remain in the file until cleared or rotated.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Optimize Clear Section --}}
    <div class="page-body pt-0">
        <div class="container-xl">
            <div class="card mt-2">
                <div class="card-header">
                    <h3 class="card-title mb-0">Cache Management</h3>
                </div>
                <div class="card-body">
                    @if(session('optimize_success'))
                        <div class="alert alert-success alert-dismissible mb-3" role="alert">
                            {{ session('optimize_success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('optimize_error'))
                        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                            {{ session('optimize_error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="fw-medium">Run <code>php artisan optimize:clear</code></div>
                            <div class="text-secondary small mt-1">
                                Clears the application cache, config cache, route cache, view cache, and compiled files.
                            </div>
                        </div>
                        @can('updateSetting', [\App\Models\Setting::class, 'system'])
                            <button type="button"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#optimizeClearModal">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon me-1">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 12a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"/>
                                    <path d="M11 12a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"/>
                                    <path d="M18 12a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"/>
                                </svg>
                                Run optimize:clear
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Optimize Clear Confirmation Modal --}}
    @can('updateSetting', [\App\Models\Setting::class, 'system'])
        <div class="modal modal-blur fade" id="optimizeClearModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-warning">
                    <form method="POST" action="{{ route('admin.system-logs.optimize-clear') }}">
                        @csrf
                        <div class="modal-header border-warning bg-warning-lt">
                            <h5 class="modal-title text-warning">Run optimize:clear</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary">
                                This will clear all application caches including config, routes, views, and compiled files.
                                Enter your password to confirm.
                            </p>
                            <div class="mb-0">
                                <label for="optimize-password" class="form-label required">Admin password</label>
                                <input type="password"
                                       id="optimize-password"
                                       name="password"
                                       class="form-control @error('optimize_password') is-invalid @enderror"
                                       autocomplete="current-password"
                                       placeholder="Enter your password">
                                @error('optimize_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Run</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @if($selectedFileName)
        <div class="modal modal-blur fade" id="clearLogModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-danger">
                    <form method="POST" action="{{ route('admin.system-logs.clear') }}">
                        @csrf
                        <div class="modal-header border-danger bg-danger-lt">
                            <h5 class="modal-title text-danger">Clear Log File</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="file" id="clear-log-file" value="{{ old('file', $selectedFileName) }}">
                            <p class="text-secondary">
                                This will permanently remove all entries from <strong id="clear-log-label">{{ $selectedFileName }}</strong>.
                            </p>
                            <div class="mb-0">
                                <label for="clear-log-password" class="form-label required">Admin password</label>
                                <input type="password"
                                       id="clear-log-password"
                                       name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="current-password"
                                       placeholder="Enter your password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Clear Log</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .log-preview {
            background: #101827;
            color: #e5edf8;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 0.8125rem;
            line-height: 1.45;
            max-height: 70vh;
            overflow: auto;
            padding: 1rem;
        }

        .log-line {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding: 0.375rem 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .log-line:last-child {
            border-bottom: none;
        }

        .log-line-error {
            color: #ffd0d0;
        }

        .log-line-warning {
            color: #ffe8ad;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('show.bs.modal', function (event) {
            if (event.target.id !== 'clearLogModal') {
                return;
            }

            const trigger = event.relatedTarget;
            const file = trigger?.dataset?.logFile ?? '{{ $selectedFileName }}';
            const input = document.getElementById('clear-log-file');
            const label = document.getElementById('clear-log-label');

            if (input) input.value = file;
            if (label) label.textContent = file;
        });

        @if($errors->has('password') && $selectedFileName)
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('clearLogModal');
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            });
        @endif

        @if($errors->has('optimize_password'))
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('optimizeClearModal');
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            });
        @endif
    </script>
@endpush
