@extends('layouts.admin.app', ['page' => 'highlight_ticker', 'sub_page' => ''])

@section('title', 'Highlight Ticker Settings')

@section('header_data')
    @php
        $page_title    = 'Highlight Ticker';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Highlight Ticker', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Highlight Ticker (Marquee)</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row align-items-start g-4">
            
            {{-- Settings Form --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Ticker Items</h3>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ $settings['is_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold">Show Highlight Ticker on homepage</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-semibold">Ticker Items (Highlight + Normal Text)</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addTickerBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                                        Add Item
                                    </button>
                                </div>
                                <div id="tickerContainer" class="list-group list-group-flush border rounded">
                                    @if(empty($settings['items']))
                                        <div class="list-group-item text-center text-muted" id="noTickerFound">No items added yet.</div>
                                    @else
                                        @foreach($settings['items'] as $index => $item)
                                            <div class="list-group-item ticker-item p-3">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto">
                                                        <span class="text-muted ticker-handle d-flex align-items-center" style="cursor: grab;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg>
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted mb-1 d-md-none">Highlight Text (Green)</label>
                                                        <input type="text" class="form-control highlight-input" name="highlights[]" value="{{ $item['highlight'] }}" placeholder="+HIGHLIGHT TEXT">
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small text-muted mb-1 d-md-none">Normal Text (Grey)</label>
                                                        <input type="text" class="form-control normal-input" name="texts[]" value="{{ $item['text'] }}" placeholder="NORMAL TEXT GOES HERE">
                                                    </div>
                                                    <div class="col-auto">
                                                        <label class="form-label small text-muted mb-1 d-md-none">&nbsp;</label>
                                                        <button type="button" class="btn btn-outline-danger btn-icon remove-ticker-btn" title="Remove">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" id="saveSettingsBtn">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm overflow-hidden bg-light" style="min-height: 200px;">
                    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                        <h3 class="card-title text-muted">Live Preview</h3>
                    </div>
                    
                    <div class="w-100 d-flex flex-column align-items-center justify-content-center p-0" style="min-height: 150px; background-color: #f8f9fa;">
                        
                        <!-- Dummy content above -->
                        <div class="text-center text-muted mb-4 small" style="opacity: 0.5;">
                            [ Page Content Above ]
                        </div>

                        <!-- HIGHLIGHT TICKER PREVIEW -->
                        <div id="previewTickerCont" class="w-100 {{ !$settings['is_active'] ? 'd-none' : '' }}">
                            <div class="w-100 py-3 border-top border-bottom overflow-hidden shadow-sm" style="background-color: #0b1118; font-family: monospace; font-size: 0.85rem; font-weight: 600; white-space: nowrap; letter-spacing: 0.5px;">
                                <div id="previewTickerTrack" class="d-flex align-items-center flex-nowrap" style="animation: scroll 60s linear infinite;">
                                    <!-- Populated via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dummy content below -->
                        <div class="text-center text-muted mt-4 small" style="opacity: 0.5;">
                            [ Footer Below ]
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<template id="tickerTemplate">
    <div class="list-group-item ticker-item p-3">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="text-muted ticker-handle d-flex align-items-center" style="cursor: grab;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg>
                </span>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 d-md-none">Highlight Text (Green)</label>
                <input type="text" class="form-control highlight-input" name="highlights[]" placeholder="+HIGHLIGHT TEXT">
            </div>
            <div class="col-md-7">
                <label class="form-label small text-muted mb-1 d-md-none">Normal Text (Grey)</label>
                <input type="text" class="form-control normal-input" name="texts[]" placeholder="NORMAL TEXT GOES HERE">
            </div>
            <div class="col-auto">
                <label class="form-label small text-muted mb-1 d-md-none">&nbsp;</label>
                <button type="button" class="btn btn-outline-danger btn-icon remove-ticker-btn" title="Remove">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes scroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); } 
}
.ticker-preview-item {
    margin-right: 40px;
    display: inline-flex;
    align-items: center;
}
.ticker-preview-hl {
    color: #4ade80; /* bright green */
    margin-right: 6px;
}
.ticker-preview-txt {
    color: #a1a1aa; /* dim gray */
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Toggle Preview Visibility
    $('#is_active').on('change', function() {
        if($(this).is(':checked')) $('#previewTickerCont').removeClass('d-none');
        else $('#previewTickerCont').addClass('d-none');
    });

    // Ticker Repeater Logic
    function toggleNoTickerMsg() {
        if ($('.ticker-item').length > 0) {
            $('#noTickerFound').hide();
        } else {
            $('#noTickerFound').show();
        }
    }

    function updateTickerPreview() {
        let itemsHtml = '';
        const items = [];
        $('.ticker-item').each(function() {
            const hl = $(this).find('.highlight-input').val().trim();
            const txt = $(this).find('.normal-input').val().trim();
            if(hl !== '' || txt !== '') {
                items.push({ hl, txt });
            }
        });

        if (items.length > 0) {
            // Duplicate the array multiple times to create a seamless infinite scroll effect across large screens
            const repeatedItems = [...items, ...items, ...items, ...items, ...items, ...items, ...items, ...items];
            repeatedItems.forEach(item => {
                itemsHtml += `
                <div class="ticker-preview-item">
                    <span class="ticker-preview-hl">${item.hl}</span>
                    <span class="ticker-preview-txt">${item.txt}</span>
                </div>`;
            });
        }
        $('#previewTickerTrack').html(itemsHtml);
    }

    $("#tickerContainer").on('input', '.highlight-input, .normal-input', updateTickerPreview);

    $('#addTickerBtn').on('click', function() {
        const template = $('#tickerTemplate').html();
        $('#tickerContainer').append(template);
        toggleNoTickerMsg();
        updateTickerPreview();
        $('#tickerContainer .ticker-item:last-child .highlight-input').focus();
    });

    $('#tickerContainer').on('click', '.remove-ticker-btn', function() {
        $(this).closest('.ticker-item').remove();
        toggleNoTickerMsg();
        updateTickerPreview();
    });

    // Make repeater sortable
    const tickerSortable = new Sortable(document.getElementById('tickerContainer'), {
        animation: 150,
        handle: '.ticker-handle',
        onEnd: updateTickerPreview
    });

    // Init preview on load
    updateTickerPreview();

    // Save Settings AJAX
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveSettingsBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: '{{ route("admin.highlight-ticker.settings.update") }}',
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
                if(xhr.responseJSON && xhr.responseJSON.message) msg = msg = xhr.responseJSON.message;
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
