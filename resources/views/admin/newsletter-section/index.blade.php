@extends('layouts.admin.app', ['page' => 'newsletter_section', 'sub_page' => ''])

@section('title', 'Newsletter Section Settings')

@section('header_data')
    @php
        $page_title    = 'Newsletter Section';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Newsletter Section', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Newsletter Section Settings</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row align-items-start g-4">

            {{-- Settings Form --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Section Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf

                            {{-- Visibility Toggle --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Section Visibility</label>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" {{ $settings['is_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label">Show Newsletter section on homepage</span>
                                </label>
                            </div>

                            {{-- Badge --}}
                            <div class="mb-3">
                                <label class="form-label" for="badge_text">Badge Text</label>
                                <input type="text" class="form-control" id="badge_text" name="badge_text"
                                    value="{{ $settings['badge_text'] }}" maxlength="120" placeholder="e.g. Newsletter">
                            </div>

                            {{-- Heading --}}
                            <div class="mb-3">
                                <label class="form-label" for="heading">Heading</label>
                                <input type="text" class="form-control" id="heading" name="heading"
                                    value="{{ $settings['heading'] }}" maxlength="255" placeholder="e.g. Stay Updated with">
                                <small class="form-hint">Plain part of the heading (before the highlighted word).</small>
                            </div>

                            {{-- Heading Highlight --}}
                            <div class="mb-3">
                                <label class="form-label" for="heading_highlight">Heading Highlight</label>
                                <input type="text" class="form-control" id="heading_highlight" name="heading_highlight"
                                    value="{{ $settings['heading_highlight'] }}" maxlength="255" placeholder="e.g. Packaging Trends">
                                <small class="form-hint">Accent-coloured part of the heading.</small>
                            </div>

                            {{-- Subheading --}}
                            <div class="mb-4">
                                <label class="form-label" for="subheading">Subheading</label>
                                <textarea class="form-control" id="subheading" name="subheading" rows="3"
                                    maxlength="500" placeholder="e.g. Join 5,000+ brand owners...">{{ $settings['subheading'] }}</textarea>
                            </div>

                            <hr>
                            <h4 class="mb-3">Perks List</h4>
                            <small class="form-hint d-block mb-3">Leave a field empty to remove that perk.</small>

                            <div id="perksContainer">
                                @foreach($settings['perks'] as $i => $perk)
                                <div class="input-group mb-2 perk-row">
                                    <span class="input-group-text">{{ $i + 1 }}</span>
                                    <input type="text" class="form-control" name="perks[]"
                                        value="{{ $perk }}" maxlength="200" placeholder="Perk description">
                                    <button type="button" class="btn btn-outline-danger btn-remove-perk" title="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                </div>
                                @endforeach
                            </div>

                            <button type="button" id="addPerkBtn" class="btn btn-sm btn-outline-secondary mb-4">
                                + Add Perk
                            </button>

                            <hr>
                            <h4 class="mb-3">Form Box</h4>

                            <div class="mb-3">
                                <label class="form-label" for="form_title">Form Title</label>
                                <input type="text" class="form-control" id="form_title" name="form_title"
                                    value="{{ $settings['form_title'] }}" maxlength="200" placeholder="e.g. Get packaging insights">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="form_subtitle">Form Subtitle</label>
                                <input type="text" class="form-control" id="form_subtitle" name="form_subtitle"
                                    value="{{ $settings['form_subtitle'] }}" maxlength="300" placeholder="e.g. No spam, unsubscribe any time.">
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="saveSettingsBtn">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm text-white"
                    style="background: linear-gradient(135deg, #1c4c7c, #48b868); overflow: hidden; position: relative;">

                    <div style="position: absolute; top: -60px; left: -60px; width: 320px; height: 320px;
                        background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -100px; right: -60px; width: 420px; height: 420px;
                        background: rgba(255,255,255,0.05); border-radius: 50%;"></div>

                    <div class="card-header border-0 d-flex justify-content-between align-items-center"
                        style="position: relative; z-index: 10;">
                        <h3 class="card-title text-white mb-0">Live Preview</h3>
                        <span class="badge bg-success" id="previewStatus">{{ $settings['is_active'] ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <div class="card-body p-4 p-md-5" style="position: relative; z-index: 10;">
                        <div class="row align-items-center g-4">

                            {{-- Left content --}}
                            <div class="col-md-7">
                                <div class="badge px-3 py-2 text-white mb-3"
                                    style="background: rgba(255,255,255,0.15); font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.2); border-radius: 50rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-bell" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;margin-top:-2px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/>
                                        <path d="M9 17v1a3 3 0 0 0 6 0v-1"/>
                                    </svg>
                                    <span id="previewBadge">{{ $settings['badge_text'] }}</span>
                                </div>

                                <h2 class="fw-bold text-white mb-3" id="previewHeadingFull"
                                    style="font-size: 1.8rem; line-height: 1.25;">
                                    <span id="previewHeading">{{ $settings['heading'] }}</span>{" "}
                                    <span id="previewHighlight" style="color: #a3e635;">{{ $settings['heading_highlight'] }}</span>
                                </h2>

                                <p class="mb-3" id="previewSubheading" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                                    {{ $settings['subheading'] }}
                                </p>

                                <ul id="previewPerks" class="list-unstyled mb-0" style="font-size: 0.85rem; color: rgba(255,255,255,0.8);">
                                    @foreach($settings['perks'] as $perk)
                                    <li class="d-flex align-items-center gap-2 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#a3e635" viewBox="0 0 16 16">
                                            <path d="M2 8l4 4L14 4"/>
                                        </svg>
                                        {{ $perk }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- Right form box --}}
                            <div class="col-md-5">
                                <div class="rounded-3 p-4"
                                    style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);">
                                    <p class="fw-semibold text-white mb-1" id="previewFormTitle">{{ $settings['form_title'] }}</p>
                                    <p id="previewFormSubtitle" class="mb-3" style="color: rgba(255,255,255,0.6); font-size: 0.78rem;">
                                        {{ $settings['form_subtitle'] }}
                                    </p>
                                    <div class="input-group mb-2">
                                        <input type="email" class="form-control form-control-sm" placeholder="your@email.com" disabled>
                                    </div>
                                    <button class="btn btn-sm w-100 fw-semibold" style="background:#a3e635; color:#1c4c7c;">
                                        Subscribe
                                    </button>
                                    <p class="mt-2 text-center" style="color: rgba(255,255,255,0.5); font-size: 0.7rem;">
                                        No credit card required
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // ── Real-time preview ──────────────────────────────────────────────────────
    $('#badge_text').on('input', function () { $('#previewBadge').text($(this).val()); });
    $('#heading').on('input', function () { $('#previewHeading').text($(this).val()); });
    $('#heading_highlight').on('input', function () { $('#previewHighlight').text($(this).val()); });
    $('#subheading').on('input', function () { $('#previewSubheading').text($(this).val()); });
    $('#form_title').on('input', function () { $('#previewFormTitle').text($(this).val()); });
    $('#form_subtitle').on('input', function () { $('#previewFormSubtitle').text($(this).val()); });

    $('#isActive').on('change', function () {
        $('#previewStatus')
            .text($(this).is(':checked') ? 'Active' : 'Inactive')
            .toggleClass('bg-success', $(this).is(':checked'))
            .toggleClass('bg-secondary', !$(this).is(':checked'));
    });

    // ── Perks live preview ────────────────────────────────────────────────────
    function rebuildPreviewPerks() {
        const $ul = $('#previewPerks').empty();
        $('#perksContainer .perk-row input').each(function () {
            const val = $(this).val().trim();
            if (!val) return;
            $ul.append(
                `<li class="d-flex align-items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#a3e635" viewBox="0 0 16 16">
                        <path d="M2 8l4 4L14 4"/>
                    </svg>
                    ${$('<span>').text(val).html()}
                </li>`
            );
        });
    }

    $(document).on('input', '#perksContainer input', rebuildPreviewPerks);

    // ── Reindex perk row numbers ──────────────────────────────────────────────
    function reindexPerks() {
        $('#perksContainer .perk-row').each(function (i) {
            $(this).find('.input-group-text').text(i + 1);
        });
    }

    // ── Add perk row ──────────────────────────────────────────────────────────
    $('#addPerkBtn').on('click', function () {
        const count = $('#perksContainer .perk-row').length + 1;
        if (count > 10) { alert('Maximum 10 perks allowed.'); return; }
        const $row = $(
            `<div class="input-group mb-2 perk-row">
                <span class="input-group-text">${count}</span>
                <input type="text" class="form-control" name="perks[]" maxlength="200" placeholder="Perk description">
                <button type="button" class="btn btn-outline-danger btn-remove-perk" title="Remove">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
            </div>`
        );
        $('#perksContainer').append($row);
    });

    // ── Remove perk row ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove-perk', function () {
        $(this).closest('.perk-row').remove();
        reindexPerks();
        rebuildPreviewPerks();
    });

    // ── Save settings ─────────────────────────────────────────────────────────
    $('#settingsForm').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#saveSettingsBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        const formData = new FormData(this);

        // Convert checkbox to 1/0 boolean
        formData.set('is_active', $('#isActive').is(':checked') ? '1' : '0');

        $.ajax({
            url: '{{ route("admin.newsletter-section.settings.update") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                btn.prop('disabled', false).text(origText);
                if (response.success) {
                    if (typeof showToastr === 'function') {
                        showToastr('success', response.message);
                    } else {
                        alert(response.message);
                    }
                } else {
                    alert('Error saving settings.');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).text(origText);
                let msg = 'Error saving settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (typeof showToastr === 'function') {
                    showToastr('error', msg);
                } else {
                    alert(msg);
                }
            },
        });
    });
});
</script>
@endpush
