@extends('layouts.admin.app', ['page' => 'why_choose_us', 'sub_page' => ''])

@section('title', 'Why Choose Us Settings')

@section('header_data')
    @php
        $page_title    = 'Why Choose Us';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Why Choose Us', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Why Choose Us Settings</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row align-items-start">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Section Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Section Visibility</label>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" {{ $settings['is_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label">Show "Why Choose Us" section on homepage</span>
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="eyebrow">Eyebrow</label>
                                <input type="text" class="form-control" id="eyebrow" name="eyebrow" value="{{ $settings['eyebrow'] }}" maxlength="120" placeholder="e.g. WHY CHOOSE US">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="heading">Heading</label>
                                <input type="text" class="form-control" id="heading" name="heading" value="{{ $settings['heading'] }}" maxlength="255" placeholder="e.g. Why Buy from Pethiyan?">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="subheading">Subheading</label>
                                <textarea class="form-control" id="subheading" name="subheading" rows="2" maxlength="500" placeholder="From small businesses to large manufacturers...">{{ $settings['subheading'] }}</textarea>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-semibold">Features Block</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFeatureBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                                        Add Feature
                                    </button>
                                </div>
                                <div id="featuresContainer" class="list-group list-group-flush border rounded">
                                    @if(empty($settings['features']))
                                        <div class="list-group-item text-center text-muted" id="noFeaturesFound">No features added yet.</div>
                                    @else
                                        @foreach($settings['features'] as $index => $feature)
                                            <div class="list-group-item feature-item">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto">
                                                        <span class="text-muted feature-handle" style="cursor: grab;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg>
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" class="form-control form-control-sm feature-input" name="features[]" value="{{ $feature }}" placeholder="Enter feature text...">
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-feature-btn" title="Remove">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="saveSettingsBtn">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card bg-dark text-white shadow-sm" style="background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PHBhdGggZD0iTTAgMGgyMHYyMEgwem0xMCAxMGgxMHYxMEgxMHoiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wMykiLz48L3N2Zz4=') repeat, linear-gradient(135deg, #0f1c2b, #153243);">
                    <div class="card-header border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center">
                        <h3 class="card-title text-white">Live Preview</h3>
                        <button type="button" class="btn btn-sm btn-outline-light" id="refreshPreviewBtn">Refresh Features</button>
                    </div>
                    <div class="card-body p-5">
                        
                        {{-- Preview Header --}}
                        <div class="mb-5 text-start">
                            <div class="text-uppercase small fw-bold mb-2" id="previewEyebrow" style="color: #27a768; letter-spacing: 1px;">{{ $settings['eyebrow'] }}</div>
                            <h2 class="mb-3 display-6 fw-bold" style="color: #5ab1d3;" id="previewHeading">{{ $settings['heading'] }}</h2>
                            <p class="text-white-50 mb-0" id="previewSubheading" style="font-size: 1.1rem;">{{ $settings['subheading'] }}</p>
                        </div>

                        {{-- Features Grid Preview --}}
                        <div class="row g-4" id="previewGrid">
                            @foreach($settings['features'] ?? [] as $feature)
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start bg-white bg-opacity-10 p-3 rounded" style="border: 1px solid rgba(255,255,255,0.05)">
                                        <div class="text-success mt-1 me-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </div>
                                        <div class="text-white opacity-75">
                                            {{ $feature }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="featureTemplate">
    <div class="list-group-item feature-item">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="text-muted feature-handle" style="cursor: grab;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg>
                </span>
            </div>
            <div class="col">
                <input type="text" class="form-control form-control-sm feature-input" name="features[]" placeholder="Enter feature text...">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-feature-btn" title="Remove">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                </button>
            </div>
        </div>
    </div>
</template>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Real-time text preview
    $('#eyebrow').on('input', function() { $('#previewEyebrow').text($(this).val()); });
    $('#heading').on('input', function() { $('#previewHeading').text($(this).val()); });
    $('#subheading').on('input', function() { $('#previewSubheading').text($(this).val()); });

    // Features repeater
    function toggleNoFeaturesMsg() {
        if ($('.feature-item').length > 0) {
            $('#noFeaturesFound').hide();
        } else {
            $('#noFeaturesFound').show();
        }
    }

    $("#featuresContainer").on('input', '.feature-input', function(){
        updatePreviewGrid();
    });

    $('#addFeatureBtn').on('click', function() {
        const template = $('#featureTemplate').html();
        $('#featuresContainer').append(template);
        toggleNoFeaturesMsg();
        updatePreviewGrid();
        
        // Focus the newly added input
        $('#featuresContainer .feature-item:last-child .feature-input').focus();
    });

    $('#featuresContainer').on('click', '.remove-feature-btn', function() {
        $(this).closest('.feature-item').remove();
        toggleNoFeaturesMsg();
        updatePreviewGrid();
    });

    // Make repeater sortable
    const featuresSortable = new Sortable(document.getElementById('featuresContainer'), {
        animation: 150,
        handle: '.feature-handle',
        onEnd: function () {
            updatePreviewGrid();
        }
    });

    // Live update preview grid
    function updatePreviewGrid() {
        let gridHtml = '';
        $('.feature-item').each(function() {
            const val = $(this).find('.feature-input').val();
            if(val.trim() === '') return;
            
            gridHtml += `
            <div class="col-md-6">
                <div class="d-flex align-items-start bg-white bg-opacity-10 p-3 rounded" style="border: 1px solid rgba(255,255,255,0.05)">
                    <div class="text-success mt-1 me-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="text-white opacity-75">
                        ${val}
                    </div>
                </div>
            </div>`;
        });
        $('#previewGrid').html(gridHtml);
    }

    $('#refreshPreviewBtn').on('click', function() {
        updatePreviewGrid();
    });

    // Save Settings
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveSettingsBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: '{{ route("admin.why-choose-us.settings.update") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                btn.prop('disabled', false).html(origText);
                if(response.success) {
                    if (typeof showToastr === 'function') {
                        showToastr('success', response.message);
                    } else {
                        alert(response.message);
                    }
                } else {
                    alert('Error saving settings.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(origText);
                let msg = 'Error saving settings.';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (typeof showToastr === 'function') {
                    showToastr('error', msg);
                } else {
                    alert(msg);
                }
            }
        });
    });
});
</script>
@endpush
