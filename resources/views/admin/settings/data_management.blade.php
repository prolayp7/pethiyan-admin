@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? ""])

@section('title', 'Data Management')

@section('header_data')
    @php
        $page_title = 'Data Management';
        $page_pretitle = __('labels.admin') . ' ' . __('labels.settings');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => 'Data Management', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Data Management</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Warning banner --}}
            <div class="alert alert-danger mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round" class="icon alert-icon me-2">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v4"/>
                        <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z"/>
                        <path d="M12 16h.01"/>
                    </svg>
                    <div>
                        <h4 class="alert-title">Danger Zone — Irreversible Actions</h4>
                        <div class="text-secondary">
                            Truncating a category <strong>permanently deletes all records</strong> from those tables.
                            This action <strong>cannot be undone</strong>. Make sure you have a database backup before proceeding.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach($categories as $key => $category)
                    @php
                        $iconMap = [
                            'orders'       => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/><path d="M9 12l.01 0"/><path d="M13 12l2 0"/><path d="M9 16l.01 0"/><path d="M13 16l2 0"/>',
                            'carts'        => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>',
                            'transactions' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"/><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"/>',
                            'payments'     => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/><path d="M3 10l18 0"/><path d="M7 15l.01 0"/><path d="M11 15l2 0"/>',
                        ];
                    @endphp
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-sm bg-danger-lt text-danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            {!! $iconMap[$key] !!}
                                        </svg>
                                    </span>
                                    <h4 class="card-title mb-0">{{ $category['label'] }}</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-secondary mb-3">{{ $category['description'] }}</p>
                                <p class="mb-2 fw-medium text-muted small">Tables affected:</p>
                                <div class="d-flex flex-wrap gap-1 mb-4">
                                    @foreach($category['tables'] as $table)
                                        <span class="badge bg-secondary-lt text-secondary font-monospace">{{ $table }}</span>
                                    @endforeach
                                </div>
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmTruncateModal"
                                        data-category="{{ $key }}"
                                        data-label="{{ $category['label'] }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         class="icon me-1">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 7l16 0"/>
                                        <path d="M10 11l0 6"/>
                                        <path d="M14 11l0 6"/>
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                    </svg>
                                    Truncate {{ $category['label'] }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div class="modal modal-blur fade" id="confirmTruncateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content border-danger">
                <div class="modal-header border-danger bg-danger-lt">
                    <h5 class="modal-title text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="icon me-1">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 9v4"/>
                            <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z"/>
                            <path d="M12 16h.01"/>
                        </svg>
                        Confirm Truncation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary mb-3">
                        You are about to permanently delete all records from
                        <strong id="modal-category-label"></strong>.
                        This action <strong>cannot be undone</strong>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label required">Enter your admin password to confirm</label>
                        <input type="password" class="form-control" id="truncate-password"
                               placeholder="Your password" autocomplete="current-password"/>
                        <div class="invalid-feedback" id="truncate-password-error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmTruncateBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="truncate-spinner"></span>
                        Yes, truncate permanently
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('confirmTruncateModal');
    const categoryLabel = document.getElementById('modal-category-label');
    const passwordInput = document.getElementById('truncate-password');
    const passwordError = document.getElementById('truncate-password-error');
    const confirmBtn = document.getElementById('confirmTruncateBtn');
    const spinner = document.getElementById('truncate-spinner');
    let selectedCategory = null;

    // Populate modal when triggered
    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        selectedCategory = btn.dataset.category;
        categoryLabel.textContent = btn.dataset.label;
        passwordInput.value = '';
        passwordInput.classList.remove('is-invalid');
        passwordError.textContent = '';
    });

    modal.addEventListener('shown.bs.modal', function () {
        passwordInput.focus();
    });

    // Allow Enter key to submit
    passwordInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') confirmBtn.click();
    });

    confirmBtn.addEventListener('click', async function () {
        const password = passwordInput.value.trim();
        if (!password) {
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = 'Password is required.';
            passwordInput.focus();
            return;
        }

        passwordInput.classList.remove('is-invalid');
        passwordError.textContent = '';
        confirmBtn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const response = await fetch('{{ route('admin.data-management.truncate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    category: selectedCategory,
                    password: password,
                }),
            });

            const data = await response.json();

            if (data.success) {
                bootstrap.Modal.getInstance(modal).hide();
                window.toastSuccess?.(data.message) ?? alert(data.message);
            } else {
                passwordInput.classList.add('is-invalid');
                passwordError.textContent = data.message ?? 'An error occurred.';
                passwordInput.focus();
            }
        } catch (err) {
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = 'Network error. Please try again.';
        } finally {
            confirmBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
})();
</script>
@endpush
