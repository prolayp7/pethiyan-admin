@extends('layouts.admin.app', ['page' => 'home_section', 'sub_page' => 'promo_banner'])

@section('title', 'Promo Banner Settings')

@section('header_data')
    @php
        $page_title    = 'Promo Banner';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Promo Banner', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Promo Banner Settings</h2>
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
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Section Visibility</label>
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" {{ $settings['is_active'] ? 'checked' : '' }}>
                                    <span class="form-check-label">Show Promo Banner on homepage</span>
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="badge_text">Badge Text</label>
                                <input type="text" class="form-control" id="badge_text" name="badge_text" value="{{ $settings['badge_text'] }}" maxlength="120" placeholder="e.g. Limited Time Offer">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="heading">Main Heading</label>
                                <input type="text" class="form-control" id="heading" name="heading" value="{{ $settings['heading'] }}" maxlength="255" placeholder="e.g. Custom Packaging Solutions for Your Brand">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="subheading">Subheading</label>
                                <textarea class="form-control" id="subheading" name="subheading" rows="2" maxlength="500" placeholder="e.g. Get premium branded packaging...">{{ $settings['subheading'] }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="placement">Show Section After</label>
                                <select class="form-select" id="placement" name="placement">
                                    @foreach([
                                        'after_hero' => 'Hero Section',
                                        'after_categories' => 'Categories',
                                        'after_featured_products' => 'Featured Products',
                                        'after_your_items' => 'Your Items',
                                        'after_recently_viewed' => 'Recently Viewed Products',
                                        'after_video_stories' => 'Video Stories',
                                        'after_why_choose_us' => 'Why Choose Us',
                                        'after_social_proof' => 'Social Proof',
                                        'after_newsletter' => 'Newsletter',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" {{ $settings['placement'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <hr>
                            <h4 class="mb-3">Offer Box Details</h4>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label" for="offer_primary">Offer Amount</label>
                                    <input type="text" class="form-control" id="offer_primary" name="offer_primary" value="{{ $settings['offer_primary'] }}" maxlength="50" placeholder="e.g. 20%">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="offer_secondary">Offer Text</label>
                                    <input type="text" class="form-control" id="offer_secondary" name="offer_secondary" value="{{ $settings['offer_secondary'] }}" maxlength="120" placeholder="e.g. OFF First Order">
                                </div>
                            </div>

                            <hr>
                            <h4 class="mb-3">Call to Action</h4>

                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="button_label">Button Label</label>
                                    <input type="text" class="form-control" id="button_label" name="button_label" value="{{ $settings['button_label'] }}" maxlength="120" placeholder="e.g. Explore Now">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="button_link">Button Link</label>
                                    <input type="text" class="form-control" id="button_link" name="button_link" value="{{ $settings['button_link'] }}" maxlength="500" placeholder="e.g. /shop">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="saveSettingsBtn">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="col-lg-7">
                <div class="card bg-dark text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #1c4c7c, #48b868); overflow: hidden; position: relative;">
                    <!-- Decorative overlay matching design -->
                    <div style="position: absolute; top: -50px; left: -50px; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -100px; right: -50px; width: 400px; height: 400px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>

                    <div class="card-header border-0 d-flex justify-content-between align-items-center" style="position: relative; z-index: 10;">
                        <h3 class="card-title text-white">Live Preview</h3>
                    </div>

                    <div class="card-body p-4 p-md-5" style="position: relative; z-index: 10;">
                        <div class="row align-items-center">
                            {{-- Left side Content --}}
                            <div class="col-md-8 text-start mb-4 mb-md-0">
                                <div class="badge px-3 py-2 text-white mb-3" style="background: rgba(255,255,255,0.15); font-size: 0.85rem; font-weight: 500; border: 1px solid rgba(255,255,255,0.2); border-radius: 50rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-bolt" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; margin-top: -2px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <polyline points="13 3 13 10 19 10 11 21 11 14 5 14 13 3"></polyline>
                                    </svg>
                                    <span id="previewBadge">{{ $settings['badge_text'] }}</span>
                                </div>
                                <h2 class="display-6 fw-bold text-white mb-3" id="previewHeading" style="font-size: 2.2rem; line-height: 1.2;">
                                    {{ $settings['heading'] }}
                                </h2>
                                <p class="text-white-50 mb-0" id="previewSubheading" style="font-size: 1.05rem;">
                                    {{ $settings['subheading'] }}
                                </p>
                            </div>

                            {{-- Right side Offer Box --}}
                            <div class="col-md-4 text-center text-md-end">
                                <div class="d-inline-block text-center p-3 p-md-4 rounded-3 text-white mb-3" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); min-width: 140px;">
                                    <div class="fw-bold lh-1" id="previewOfferPrimary" style="font-size: 2.5rem;">{{ $settings['offer_primary'] }}</div>
                                    <div class="small opacity-75 mt-1" id="previewOfferSecondary" style="font-size: 0.9rem;">{{ $settings['offer_secondary'] }}</div>
                                </div>
                                <div>
                                    <button class="btn btn-light rounded-pill px-4 py-2 fw-semibold" style="color: #1c4c7c;">
                                        <span id="previewButtonLabel">{{ $settings['button_label'] }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right ms-2" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                                        </svg>
                                    </button>
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
$(document).ready(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Real-time text preview bindings
    $('#badge_text').on('input', function() { $('#previewBadge').text($(this).val()); });
    $('#heading').on('input', function() { $('#previewHeading').text($(this).val()); });
    $('#subheading').on('input', function() { $('#previewSubheading').text($(this).val()); });
    $('#offer_primary').on('input', function() { $('#previewOfferPrimary').text($(this).val()); });
    $('#offer_secondary').on('input', function() { $('#previewOfferSecondary').text($(this).val()); });
    $('#button_label').on('input', function() { $('#previewButtonLabel').text($(this).val()); });

    // Save Settings AJAX
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveSettingsBtn');
        const origText = btn.text();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: '{{ route("admin.promo-banner.settings.update") }}',
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
