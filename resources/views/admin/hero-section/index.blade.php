@extends('layouts.admin.app', ['page' => 'hero_section', 'sub_page' => ''])

@section('title', 'Hero Section')

@section('header_data')
    @php
        $page_title    = 'Hero Section';
        $page_pretitle = 'Content';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Hero Section',    'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Hero Section</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4" id="heroTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-slides" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9l5 3l-5 3z"/></svg>
                    Slides <span class="badge bg-blue ms-1">{{ $slides->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-badges" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v5c0 3.5-3.4 6.8-8 8-4.6-1.2-8-4.5-8-8v-5z"/></svg>
                    Trust Badges <span class="badge bg-green ms-1">{{ $badges->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94-1.543.826-3.31 2.37-2.37c1 .608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                    Carousel Settings
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 1 — SLIDES
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="tab-slides">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Drag rows to reorder. Changes save automatically.</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#slideModal" onclick="openSlideModal()">
                        + Add Slide
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table" id="slidesTable">
                            <thead>
                                <tr>
                                    <th style="width:32px"></th>
                                    <th style="width:80px">Image</th>
                                    <th>Eyebrow / Heading</th>
                                    <th>Primary CTA</th>
                                    <th>Secondary CTA</th>
                                    <th style="width:90px">Active</th>
                                    <th style="width:100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="slidesSortable">
                                @foreach($slides as $slide)
                                <tr data-id="{{ $slide->id }}">
                                    <td class="text-center text-muted" style="cursor:grab">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                    </td>
                                    <td>
                                        @if($slide->image_url)
                                            <img src="{{ $slide->image_url }}" alt="" class="rounded" style="width:64px;height:44px;object-fit:cover;">
                                        @else
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:64px;height:44px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#aaa" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate fw-semibold" style="max-width:200px">{{ $slide->eyebrow }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width:200px">{{ str_replace("\n", " / ", $slide->heading) }}</small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width:130px">{{ $slide->primary_cta_label }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width:130px">{{ $slide->primary_cta_href }}</small>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width:130px">{{ $slide->secondary_cta_label }}</div>
                                        <small class="text-muted text-truncate d-block" style="max-width:130px">{{ $slide->secondary_cta_href }}</small>
                                    </td>
                                    <td>
                                        <label class="form-check form-switch mb-0">
                                            <input class="form-check-input slide-toggle" type="checkbox"
                                                data-id="{{ $slide->id }}"
                                                {{ $slide->is_active ? 'checked' : '' }}>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary px-2 py-1 btn-edit-slide"
                                                data-id="{{ $slide->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 btn-delete-slide"
                                                data-id="{{ $slide->id }}" data-name="{{ $slide->eyebrow }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 2 — TRUST BADGES
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-badges">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Drag rows to reorder.</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#badgeModal" onclick="openBadgeModal()">
                        + Add Badge
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th style="width:32px"></th>
                                    <th style="width:60px">Icon</th>
                                    <th>Label</th>
                                    <th style="width:90px">Active</th>
                                    <th style="width:100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="badgesSortable">
                                @foreach($badges as $badge)
                                <tr data-id="{{ $badge->id }}">
                                    <td class="text-center text-muted" style="cursor:grab">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                    </td>
                                    <td>
                                        <span class="badge bg-blue-lt px-2 py-1 text-capitalize">{{ str_replace('-', ' ', $badge->icon_name) }}</span>
                                    </td>
                                    <td class="fw-semibold">{{ $badge->label }}</td>
                                    <td>
                                        <label class="form-check form-switch mb-0">
                                            <input class="form-check-input badge-toggle" type="checkbox"
                                                data-id="{{ $badge->id }}"
                                                {{ $badge->is_active ? 'checked' : '' }}>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary px-2 py-1 btn-edit-badge"
                                                data-badge='@json($badge)'>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 btn-delete-badge"
                                                data-id="{{ $badge->id }}" data-name="{{ $badge->label }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 3 — CAROUSEL SETTINGS
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-settings">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Carousel Settings</h3></div>
                            <div class="card-body">
                                <form id="settingsForm">
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Autoplay</label>
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoplayEnabled"
                                                name="autoplay_enabled"
                                                {{ $settings['autoplay_enabled'] ? 'checked' : '' }}>
                                            <span class="form-check-label">Enable autoplay</span>
                                        </label>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold" for="autoplayDelay">
                                            Autoplay Delay: <span id="delayDisplay">{{ $settings['autoplay_delay'] }}</span>ms
                                        </label>
                                        <input type="range" class="form-range" id="autoplayDelay" name="autoplay_delay"
                                            min="1000" max="15000" step="500"
                                            value="{{ $settings['autoplay_delay'] }}">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">1s</small>
                                            <small class="text-muted">15s</small>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold" for="heroHeight">
                                            Hero Height: <span id="heroHeightDisplay">{{ $settings['hero_height'] }}</span>px
                                        </label>
                                        <input type="range" class="form-range" id="heroHeight" name="hero_height"
                                            min="360" max="980" step="10"
                                            value="{{ $settings['hero_height'] }}">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">360px</small>
                                            <small class="text-muted">980px</small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100" id="saveSettingsBtn">
                                        Save Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end tab-content --}}
    </div>
</div>

{{-- Hidden triggers (avoids needing the bootstrap global in JS) --}}
<button id="triggerSlideModal" data-bs-toggle="modal" data-bs-target="#slideModal" style="display:none" aria-hidden="true"></button>
<button id="triggerBadgeModal" data-bs-toggle="modal" data-bs-target="#badgeModal" style="display:none" aria-hidden="true"></button>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SLIDE MODAL (Add / Edit)
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="slideModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="slideModalTitle">Add Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="slideForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="slideId" name="_slide_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Eyebrow Text</label>
                            <input type="text" class="form-control" name="eyebrow" id="s_eyebrow" required maxlength="120" placeholder="e.g. Premium Packaging Excellence">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Heading <small class="text-muted">(use \n for line break)</small></label>
                            <textarea class="form-control" name="heading" id="s_heading" rows="2" required maxlength="300" placeholder="e.g. Packaging That Protects,\nPresents, and Performs"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="s_description" rows="2" maxlength="500"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Primary CTA Label</label>
                            <input type="text" class="form-control" name="primary_cta_label" id="s_primary_label" required maxlength="120" placeholder="Explore Products">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Primary CTA URL</label>
                            <input type="text" class="form-control" name="primary_cta_href" id="s_primary_href" required maxlength="500" placeholder="/shop">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Secondary CTA Label</label>
                            <input type="text" class="form-control" name="secondary_cta_label" id="s_secondary_label" required maxlength="120" placeholder="Request Quote">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Secondary CTA URL</label>
                            <input type="text" class="form-control" name="secondary_cta_href" id="s_secondary_href" required maxlength="500" placeholder="/contact">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Slide Image</label>
                            <input type="file" class="form-control" name="image" id="s_image" accept="image/jpg,image/jpeg,image/png,image/webp">
                            <div class="form-hint">Recommended: 1920 x 800 px. JPG / PNG / WebP — max 5 MB. Leave empty to keep existing image.</div>
                            <div id="slideImagePreview" class="mt-2" style="display:none">
                                <img id="slideImagePreviewImg" src="" alt="" class="rounded" style="height:80px;object-fit:cover;">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="s_is_active" checked>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="slideSubmitBtn">Save Slide</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     BADGE MODAL (Add / Edit)
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="badgeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="badgeModalTitle">Add Trust Badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="badgeForm">
                @csrf
                <input type="hidden" id="badgeId" name="_badge_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Icon</label>
                        <select class="form-select" name="icon_name" id="b_icon_name" required>
                            @foreach($availableIcons as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Label</label>
                        <input type="text" class="form-control" name="label" id="b_label" required maxlength="80" placeholder="e.g. Food Safe">
                    </div>
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="b_is_active" checked>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="badgeSubmitBtn">Save Badge</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    // Slide data injected server-side — avoids HTML-attribute JSON encoding issues
    const allSlidesData = {!! json_encode(
        $slides->map(fn($s) => $s->append('image_url'))->keyBy('id')
    ) !!};
</script>
<script>
window.addEventListener('load', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // ── Helpers ────────────────────────────────────────────────────────────
    function showToast(type, msg) {
        if (typeof showToastr === 'function') { showToastr(type, msg); return; }
        alert(msg);
    }

    function ajaxPost(url, formData, btn, callback) {
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.innerHTML = orig;
            if (data.success) { showToast('success', data.message); callback(data); }
            else              { showToast('error', data.message || 'Error'); }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; showToast('error', 'Request failed'); });
    }

    function ajaxDelete(url, callback) {
        fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => { if (data.success) { showToast('success', data.message); callback(); } else { showToast('error', data.message); } })
        .catch(() => showToast('error', 'Request failed'));
    }

    // ── SLIDES ─────────────────────────────────────────────────────────────

    // Sortable drag-reorder
    const slidesSortable = new Sortable(document.getElementById('slidesSortable'), {
        animation: 150,
        handle: 'td:first-child',
        onEnd: function () {
            const order = [...document.querySelectorAll('#slidesSortable tr')].map(r => r.dataset.id);
            const fd = new FormData();
            order.forEach((id, i) => fd.append('order[]', id));
            fetch('{{ route("admin.hero-section.slides.reorder") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: fd,
            }).then(r => r.json()).then(d => { if (d.success) showToast('success', 'Order saved.'); });
        },
    });

    // Toggle active
    document.getElementById('slidesSortable').addEventListener('change', function (e) {
        if (!e.target.classList.contains('slide-toggle')) return;
        const id = e.target.dataset.id;
        const fd = new FormData(); fd.append('_method', 'POST');
        fetch(`/admin/hero-section/slides/${id}/toggle`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        }).then(r => r.json()).then(d => {
            if (!d.success) { showToast('error', d.message); e.target.checked = !e.target.checked; }
        });
    });

    // Open add modal
    window.openSlideModal = function () {
        document.getElementById('slideModalTitle').textContent = 'Add Slide';
        document.getElementById('slideSubmitBtn').textContent  = 'Save Slide';
        document.getElementById('slideId').value = '';
        document.getElementById('slideForm').reset();
        document.getElementById('slideImagePreview').style.display = 'none';
    };

    // Open edit modal
    document.getElementById('slidesSortable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-slide');
        if (!btn) return;
        const slide = allSlidesData[btn.dataset.id];
        if (!slide) return;

        document.getElementById('slideModalTitle').textContent = 'Edit Slide';
        document.getElementById('slideSubmitBtn').textContent  = 'Update Slide';
        document.getElementById('slideId').value               = slide.id;
        document.getElementById('s_eyebrow').value             = slide.eyebrow;
        document.getElementById('s_heading').value             = slide.heading;
        document.getElementById('s_description').value         = slide.description || '';
        document.getElementById('s_primary_label').value       = slide.primary_cta_label;
        document.getElementById('s_primary_href').value        = slide.primary_cta_href;
        document.getElementById('s_secondary_label').value     = slide.secondary_cta_label;
        document.getElementById('s_secondary_href').value      = slide.secondary_cta_href;
        document.getElementById('s_is_active').checked         = !!slide.is_active;

        const preview    = document.getElementById('slideImagePreview');
        const previewImg = document.getElementById('slideImagePreviewImg');
        if (slide.image_url) {
            previewImg.src = slide.image_url;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        document.getElementById('triggerSlideModal').click();
    });

    // Image preview on file select
    document.getElementById('s_image').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const preview = document.getElementById('slideImagePreview');
        const previewImg = document.getElementById('slideImagePreviewImg');
        previewImg.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    });

    // Submit slide form
    document.getElementById('slideForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('slideSubmitBtn');
        const id  = document.getElementById('slideId').value;
        const fd  = new FormData(this);
        fd.set('is_active', document.getElementById('s_is_active').checked ? '1' : '0');

        const url = id
            ? `/admin/hero-section/slides/${id}`
            : '{{ route("admin.hero-section.slides.store") }}';

        ajaxPost(url, fd, btn, function () {
            document.querySelector('#slideModal [data-bs-dismiss="modal"]').click();
            location.reload();
        });
    });

    // Delete slide
    document.getElementById('slidesSortable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-slide');
        if (!btn) return;
        if (!confirm(`Delete slide "${btn.dataset.name}"?`)) return;
        ajaxDelete(`/admin/hero-section/slides/${btn.dataset.id}`, () => btn.closest('tr').remove());
    });

    // ── TRUST BADGES ───────────────────────────────────────────────────────

    const badgesSortable = new Sortable(document.getElementById('badgesSortable'), {
        animation: 150,
        handle: 'td:first-child',
        onEnd: function () {
            const order = [...document.querySelectorAll('#badgesSortable tr')].map(r => r.dataset.id);
            const fd = new FormData();
            order.forEach(id => fd.append('order[]', id));
            fetch('{{ route("admin.hero-section.badges.reorder") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: fd,
            }).then(r => r.json()).then(d => { if (d.success) showToast('success', 'Order saved.'); });
        },
    });

    document.getElementById('badgesSortable').addEventListener('change', function (e) {
        if (!e.target.classList.contains('badge-toggle')) return;
        const id = e.target.dataset.id;
        fetch(`/admin/hero-section/badges/${id}/toggle`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        }).then(r => r.json()).then(d => {
            if (!d.success) { showToast('error', d.message); e.target.checked = !e.target.checked; }
        });
    });

    window.openBadgeModal = function () {
        document.getElementById('badgeModalTitle').textContent = 'Add Trust Badge';
        document.getElementById('badgeSubmitBtn').textContent  = 'Save Badge';
        document.getElementById('badgeId').value = '';
        document.getElementById('badgeForm').reset();
    };

    document.getElementById('badgesSortable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-badge');
        if (!btn) return;
        const badge = JSON.parse(btn.dataset.badge);
        document.getElementById('badgeModalTitle').textContent = 'Edit Trust Badge';
        document.getElementById('badgeSubmitBtn').textContent  = 'Update Badge';
        document.getElementById('badgeId').value               = badge.id;
        document.getElementById('b_icon_name').value           = badge.icon_name;
        document.getElementById('b_label').value               = badge.label;
        document.getElementById('b_is_active').checked         = !!badge.is_active;
        document.getElementById('triggerBadgeModal').click();
    });

    document.getElementById('badgeForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('badgeSubmitBtn');
        const id  = document.getElementById('badgeId').value;
        const fd  = new FormData(this);
        fd.set('is_active', document.getElementById('b_is_active').checked ? '1' : '0');

        const url = id
            ? `/admin/hero-section/badges/${id}`
            : '{{ route("admin.hero-section.badges.store") }}';

        ajaxPost(url, fd, btn, function () {
            document.querySelector('#badgeModal [data-bs-dismiss="modal"]').click();
            location.reload();
        });
    });

    document.getElementById('badgesSortable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-badge');
        if (!btn) return;
        if (!confirm(`Delete badge "${btn.dataset.name}"?`)) return;
        ajaxDelete(`/admin/hero-section/badges/${btn.dataset.id}`, () => btn.closest('tr').remove());
    });

    // ── CAROUSEL SETTINGS ──────────────────────────────────────────────────

    const delaySlider       = document.getElementById('autoplayDelay');
    const delayDisplay      = document.getElementById('delayDisplay');
    const heroHeightSlider  = document.getElementById('heroHeight');
    const heroHeightDisplay = document.getElementById('heroHeightDisplay');
    delaySlider.addEventListener('input', () => delayDisplay.textContent = delaySlider.value);
    heroHeightSlider.addEventListener('input', () => heroHeightDisplay.textContent = heroHeightSlider.value);

    document.getElementById('settingsForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('saveSettingsBtn');
        const fd  = new FormData();
        fd.append('autoplay_enabled', document.getElementById('autoplayEnabled').checked ? '1' : '0');
        fd.append('autoplay_delay',   delaySlider.value);
        fd.append('hero_height',      heroHeightSlider.value);

        ajaxPost('{{ route("admin.hero-section.settings.update") }}', fd, btn, function () {});
    });

}); // end window load
</script>
@endpush
