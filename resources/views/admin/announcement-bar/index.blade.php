@extends('layouts.admin.app', ['page' => 'announcement_bar', 'sub_page' => ''])

@section('title', 'Top Bars / Ticker Settings')

@section('header_data')
    @php
        $page_title    = 'Top Bars / Ticker';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Top Bars / Ticker', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Top Bars & Ticker Settings</h2>
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
                        <h3 class="card-title">Bar Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf
                            
                            {{-- Top Banner Settings --}}
                            <h4 class="mb-3 text-primary">Top Banner Window</h4>
                            
                            <div class="mb-3">
                                <label class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="top_bar_active" name="top_bar_active" {{ $settings['top_bar_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold">Show Top Dark Blue Banner</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="top_bar_text">Banner Text</label>
                                <input type="text" class="form-control" id="top_bar_text" name="top_bar_text" value="{{ $settings['top_bar_text'] }}" maxlength="255" placeholder="e.g. The Power of Perfect Packaging...">
                            </div>

                            <hr>
                            
                            {{-- Ticker/Marquee Settings --}}
                            <h4 class="mb-3 mt-4 text-primary">Features Ticker (Marquee)</h4>
                            
                            <div class="mb-4">
                                <label class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="ticker_active" name="ticker_active" {{ $settings['ticker_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold">Show Features Ticker below Top Banner</span>
                                </label>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-semibold">Ticker Items (Add Emojis if desired!)</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addTickerBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                                        Add Item
                                    </button>
                                </div>
                                <div id="tickerContainer" class="list-group list-group-flush border rounded overflow-hidden">
                                    @if(empty($settings['ticker_items']))
                                        <div class="list-group-item text-center text-muted" id="noTickerFound">No items added yet.</div>
                                    @else
                                        @foreach($settings['ticker_items'] as $index => $item)
                                            <div class="list-group-item ticker-item p-2">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-auto">
                                                        <span class="text-muted ticker-handle d-flex align-items-center" style="cursor: grab;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg>
                                                        </span>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" class="form-control form-control-sm ticker-input" name="ticker_items[]" value="{{ $item }}" placeholder="e.g. 🚚 Free Shipping...">
                                                    </div>
                                                    <div class="col-auto">
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-ticker-btn" title="Remove">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-3" id="saveSettingsBtn">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="col-lg-7">
                <div class="card bg-light border-0 shadow-sm overflow-hidden" style="min-height: 300px;">
                    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                        <h3 class="card-title text-muted">Live Preview</h3>
                    </div>
                    
                    <div class="w-100" style="position: relative;">
                        <!-- TOP BANNER PREVIEW -->
                        <div id="previewTopBarCont" class="{{ !$settings['top_bar_active'] ? 'd-none' : '' }}">
                            <div class="w-100 text-center py-2 text-white" style="background-color: #1a3c61; font-size: 0.85rem; font-weight: 500;">
                                <span id="previewTopBarText">{{ $settings['top_bar_text'] }}</span>
                            </div>
                        </div>

                        <!-- TICKER PREVIEW -->
                        <div id="previewTickerCont" class="{{ !$settings['ticker_active'] ? 'd-none' : '' }}">
                            <div class="w-100 py-2 border-bottom shadow-sm text-muted d-flex align-items-center overflow-hidden" style="background-color: #f8f9fa; font-size: 0.8rem; font-weight: 600; white-space: nowrap;">
                                <div id="previewTickerTrack" class="d-flex align-items-center flex-nowrap" style="animation: scroll 60s linear infinite;">
                                    <!-- Populated via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dummy content below to show context -->
                        <div class="p-5 text-center text-muted" style="opacity: 0.5;">
                            [ Header / Navigation would be here ]<br><br>
                            [ Hero Section would be here ]
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<template id="tickerTemplate">
    <div class="list-group-item ticker-item p-2">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="text-muted ticker-handle d-flex align-items-center" style="cursor: grab;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><circle cx="9" cy="9" r="1"/><circle cx="15" cy="9" r="1"/><circle cx="9" cy="15" r="1"/><circle cx="15" cy="15" r="1"/></svg>
                </span>
            </div>
            <div class="col">
                <input type="text" class="form-control form-control-sm ticker-input" name="ticker_items[]" placeholder="e.g. 🚚 Free Shipping...">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-outline-danger btn-icon remove-ticker-btn" title="Remove">
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
    padding: 0 30px;
    display: flex;
    align-items: center;
    position: relative;
}
.ticker-preview-item:not(:last-child)::after {
    content: "|";
    position: absolute;
    right: 0;
    color: #ccc;
    font-weight: 300;
}
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Toggle Preview Visibility
    $('#top_bar_active').on('change', function() {
        if($(this).is(':checked')) $('#previewTopBarCont').removeClass('d-none');
        else $('#previewTopBarCont').addClass('d-none');
    });

    $('#ticker_active').on('change', function() {
        if($(this).is(':checked')) $('#previewTickerCont').removeClass('d-none');
        else $('#previewTickerCont').addClass('d-none');
    });

    // Top Bar Text Preview
    $('#top_bar_text').on('input', function() { 
        $('#previewTopBarText').text($(this).val()); 
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
            const val = $(this).find('.ticker-input').val();
            if(val.trim() !== '') items.push(val);
        });

        if (items.length > 0) {
            // Duplicate the array to create a seamless infinite scroll effect
            const repeatedItems = [...items, ...items, ...items, ...items];
            repeatedItems.forEach(item => {
                itemsHtml += `<div class="ticker-preview-item">${item}</div>`;
            });
        }
        $('#previewTickerTrack').html(itemsHtml);
    }

    $("#tickerContainer").on('input', '.ticker-input', updateTickerPreview);

    $('#addTickerBtn').on('click', function() {
        const template = $('#tickerTemplate').html();
        $('#tickerContainer').append(template);
        toggleNoTickerMsg();
        updateTickerPreview();
        $('#tickerContainer .ticker-item:last-child .ticker-input').focus();
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
            url: '{{ route("admin.announcement-bar.settings.update") }}',
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
