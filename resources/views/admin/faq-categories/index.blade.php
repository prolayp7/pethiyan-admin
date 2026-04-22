@php use App\Enums\ActiveInactiveStatusEnum; @endphp
@extends('layouts.admin.app', ['page' => $menuAdmin['faqs']['active'] ?? ""])

@section('title', 'FAQ Categories')
@section('header_data')
    @php
        $page_title = 'FAQ Categories';
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'FAQs', 'url' => route('admin.faqs.index')],
        ['title' => 'Categories', 'url' => ''],
    ];
@endphp

@section('admin-content')
    <div class="page-body">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">FAQ Categories</h3>
                            <x-breadcrumb :items="$breadcrumbs"/>
                        </div>
                        <div class="card-actions">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#cat-modal">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                             viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        Add Category
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-outline-primary" id="refresh">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round"
                                             class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                        </svg>
                                        {{ __('labels.refresh') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-table">
                        <div class="row w-full p-3">
                            <x-datatable id="cat-table" :columns="$columns"
                                         route="{{ route('admin.faq-categories.datatable') }}"
                                         :options="['order' => [[0, 'asc']],'pageLength' => 15,]"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Category Modal -->
    <div class="modal modal-blur fade" id="cat-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cat-modal-title">Add FAQ Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="form-submit" id="cat-form" method="POST" action="{{ route('admin.faq-categories.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-8 mb-3">
                                <label class="form-label required">Category Name</label>
                                <input type="text" class="form-control" id="cat-name" name="name"
                                       placeholder="e.g. Orders & Payment" required>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <label class="form-label">Icon (Emoji)</label>
                                <input type="text" class="form-control" id="cat-icon" name="icon"
                                       placeholder="🛒" maxlength="10">
                                <small class="form-hint">Paste an emoji e.g. 🛒 🚚 📦 🔄</small>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="cat-sort" name="sort_order"
                                       value="0" min="0">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select text-capitalize" id="cat-status" name="status">
                                    @foreach(ActiveInactiveStatusEnum::values() as $s)
                                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                        <button type="submit" class="btn btn-primary ms-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            {{ __('labels.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const refreshBtn = document.getElementById('refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            const table = window.LaravelDataTables?.['cat-table'];
            if (table) table.ajax.reload(null, false);
        });
    }

    // Handle edit — populate modal from API response
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('[data-action="edit"][data-model="faq-category"]');
        if (!editBtn) return;

        const id = editBtn.dataset.id;
        fetch(`/admin/faq-categories/${id}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;
            document.getElementById('cat-modal-title').textContent = 'Edit FAQ Category';
            document.getElementById('cat-name').value   = d.name ?? '';
            document.getElementById('cat-icon').value   = d.icon ?? '';
            document.getElementById('cat-sort').value   = d.sort_order ?? 0;
            document.getElementById('cat-status').value = d.status ?? 'active';

            const form = document.getElementById('cat-form');
            form.action = `/admin/faq-categories/${id}`;

            // Add _method spoofing for PUT
            let methodInput = form.querySelector('input[name="_method"]');
            if (!methodInput) {
                methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                form.appendChild(methodInput);
            }
            methodInput.value = 'POST';

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('cat-modal'));
            modal.show();
        });
    });

    // Reset modal on hide
    document.getElementById('cat-modal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('cat-modal-title').textContent = 'Add FAQ Category';
        document.getElementById('cat-form').reset();
        document.getElementById('cat-form').action = '{{ route("admin.faq-categories.store") }}';
        const m = document.getElementById('cat-form').querySelector('input[name="_method"]');
        if (m) m.remove();
    });
</script>
@endpush
