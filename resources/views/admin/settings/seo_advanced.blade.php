@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['seo_advanced']['sub_active'] ?? ""])

@section('title', 'SEO Advanced Settings')

@section('header_data')
    @php
        $page_title    = 'SEO Advanced Settings';
        $page_pretitle = __('labels.admin') . ' Settings';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'),     'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => 'SEO Advanced',        'url' => null],
    ];
    $robotsRules     = $settings['robotsDisallowRules'] ?? [];
    $sitemapCustom   = $settings['sitemapCustomUrls']   ?? [];
    $sitemapExclude  = $settings['sitemapExcludeUrls']  ?? [];
    $changeFreqOpts  = ['always','hourly','daily','weekly','monthly','yearly','never'];
@endphp

@section('admin-content')
<div class="page-body">
    <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post" id="seo-advanced-form">
        @csrf
        <input type="hidden" name="type" value="seo_advanced">

        {{-- ── Robots.txt Disallow Rules ────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Extra Robots.txt Disallow Rules</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-disallow-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                    </svg>
                    Add Rule
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Add extra paths (e.g. <code>/my-promo-page/</code>) to disallow for all crawlers.
                    The core transactional paths (<code>/cart/</code>, <code>/checkout/</code>, etc.) are always disallowed automatically.
                </p>
                <div id="disallow-list">
                    @forelse ($robotsRules as $i => $rule)
                        <div class="input-group mb-2 disallow-row">
                            <input type="text" class="form-control" name="robotsDisallowRules[]"
                                   value="{{ $rule }}" placeholder="/my-page/">
                            <button type="button" class="btn btn-outline-danger remove-row-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-muted small" id="disallow-empty-msg">No extra rules. Click "Add Rule" to add one.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Sitemap Custom URLs ─────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Sitemap — Custom URLs</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-sitemap-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                    </svg>
                    Add URL
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Add custom static URLs to the sitemap (e.g. landing pages not in the default sitemap).
                    Dynamic product and category pages are included automatically.
                </p>
                <div id="sitemap-list">
                    @forelse ($sitemapCustom as $i => $entry)
                        <div class="row g-2 mb-2 align-items-center sitemap-row">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="sitemapCustomUrls[{{ $i }}][url]"
                                       value="{{ $entry['url'] ?? '' }}" placeholder="https://pethiyan.com/custom-page">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="sitemapCustomUrls[{{ $i }}][changeFreq]">
                                    @foreach ($changeFreqOpts as $opt)
                                        <option value="{{ $opt }}" {{ ($entry['changeFreq'] ?? 'weekly') === $opt ? 'selected' : '' }}>
                                            {{ ucfirst($opt) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="sitemapCustomUrls[{{ $i }}][priority]"
                                       value="{{ $entry['priority'] ?? '0.5' }}" min="0" max="1" step="0.1"
                                       placeholder="0.5">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger w-100 remove-row-btn">Remove</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small" id="sitemap-empty-msg">No custom URLs yet. Click "Add URL" to add one.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Sitemap Exclude URLs ────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Sitemap — Exclude URLs</h4>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-exclude-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                    </svg>
                    Add URL
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Exact URL paths to remove from the sitemap (e.g. <code>/products/discontinued-item</code>).
                    This overrides the automatic inclusion of product and category pages.
                </p>
                <div id="exclude-list">
                    @forelse ($sitemapExclude as $i => $path)
                        <div class="input-group mb-2 exclude-row">
                            <input type="text" class="form-control" name="sitemapExcludeUrls[]"
                                   value="{{ $path }}" placeholder="/products/old-slug">
                            <button type="button" class="btn btn-outline-danger remove-row-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @empty
                        <p class="text-muted small" id="exclude-empty-msg">No exclusions. Click "Add URL" to exclude a page.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Save SEO Advanced Settings</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ── Disallow rules ─────────────────────────────────────────────────────────
    const disallowList = document.getElementById('disallow-list');
    document.getElementById('add-disallow-btn').addEventListener('click', function () {
        const emptyMsg = document.getElementById('disallow-empty-msg');
        if (emptyMsg) emptyMsg.remove();
        const row = document.createElement('div');
        row.className = 'input-group mb-2 disallow-row';
        row.innerHTML = `
            <input type="text" class="form-control" name="robotsDisallowRules[]" placeholder="/my-page/">
            <button type="button" class="btn btn-outline-danger remove-row-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                </svg>
            </button>`;
        disallowList.appendChild(row);
        bindRemoveBtn(row.querySelector('.remove-row-btn'), row);
    });

    // ── Sitemap custom URLs ────────────────────────────────────────────────────
    const sitemapList = document.getElementById('sitemap-list');
    const changeFreqOpts = ['always','hourly','daily','weekly','monthly','yearly','never'];
    document.getElementById('add-sitemap-btn').addEventListener('click', function () {
        const emptyMsg = document.getElementById('sitemap-empty-msg');
        if (emptyMsg) emptyMsg.remove();
        const idx = sitemapList.querySelectorAll('.sitemap-row').length;
        const selectOpts = changeFreqOpts.map(o =>
            `<option value="${o}"${o === 'weekly' ? ' selected' : ''}>${o.charAt(0).toUpperCase() + o.slice(1)}</option>`
        ).join('');
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-center sitemap-row';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="sitemapCustomUrls[${idx}][url]"
                       placeholder="https://pethiyan.com/custom-page">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="sitemapCustomUrls[${idx}][changeFreq]">${selectOpts}</select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="sitemapCustomUrls[${idx}][priority]"
                       value="0.5" min="0" max="1" step="0.1" placeholder="0.5">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger w-100 remove-row-btn">Remove</button>
            </div>`;
        sitemapList.appendChild(row);
        bindRemoveBtn(row.querySelector('.remove-row-btn'), row);
        reindexSitemapRows();
    });

    // ── Sitemap exclude URLs ───────────────────────────────────────────────────
    const excludeList = document.getElementById('exclude-list');
    document.getElementById('add-exclude-btn').addEventListener('click', function () {
        const emptyMsg = document.getElementById('exclude-empty-msg');
        if (emptyMsg) emptyMsg.remove();
        const row = document.createElement('div');
        row.className = 'input-group mb-2 exclude-row';
        row.innerHTML = `
            <input type="text" class="form-control" name="sitemapExcludeUrls[]" placeholder="/products/old-slug">
            <button type="button" class="btn btn-outline-danger remove-row-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                </svg>
            </button>`;
        excludeList.appendChild(row);
        bindRemoveBtn(row.querySelector('.remove-row-btn'), row);
    });

    // ── Shared helpers ─────────────────────────────────────────────────────────
    function bindRemoveBtn(btn, row) {
        btn.addEventListener('click', function () {
            row.remove();
            reindexSitemapRows();
        });
    }

    function reindexSitemapRows() {
        sitemapList.querySelectorAll('.sitemap-row').forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/sitemapCustomUrls\[\d+\]/, `sitemapCustomUrls[${i}]`);
            });
        });
    }

    // Bind remove buttons on existing rows (loaded from saved settings)
    document.querySelectorAll('.disallow-row .remove-row-btn').forEach(function (btn) {
        bindRemoveBtn(btn, btn.closest('.disallow-row'));
    });
    document.querySelectorAll('.sitemap-row .remove-row-btn').forEach(function (btn) {
        bindRemoveBtn(btn, btn.closest('.sitemap-row'));
    });
    document.querySelectorAll('.exclude-row .remove-row-btn').forEach(function (btn) {
        bindRemoveBtn(btn, btn.closest('.exclude-row'));
    });
})();
</script>
@endpush
