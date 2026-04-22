@extends('layouts.admin.app', ['page' => 'CMS Pages'])

@section('title', 'Edit ' . $page->title)

@section('header_data')
    @php
        $page_title = 'Edit ' . $page->title;
        $page_pretitle = 'CMS Pages';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'CMS Pages', 'url' => route('admin.pages.index')],
        ['title' => 'Edit', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="row">
        <div class="col-lg-9 col-md-11 mx-auto">

            @if($page->slug === 'contact-us')
                {{-- ── CONTACT PAGE DEDICATED FORM ─────────────────────────────── --}}
                @php
                    $cb = is_array($page->content_blocks) ? $page->content_blocks : [];
                    $v  = fn(string $k, string $d = '') => old($k, $cb[$k] ?? $d);
                @endphp

                <div class="alert alert-info mb-4">
                    <div class="d-flex gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="icon mt-1 flex-shrink-0">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 9h.01"/><path d="M11 12h1v4h1"/>
                            <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z"/>
                        </svg>
                        <div>
                            <strong>Sync note:</strong>
                            The <em>Primary Phone</em>, <em>Primary Email</em>, and <em>Office Address</em>
                            fields are kept in sync with
                            <a href="{{ route('admin.settings.show', 'system') }}" class="alert-link">System Settings → Support Information &amp; General</a>.
                            Saving either location updates the other automatically.
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.pages.update', $page) }}" method="POST">
                    @csrf

                    {{-- ── Page title ── --}}
                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title mb-0">Page</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Page Title</label>
                                <input type="text" name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $page->title) }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" value="{{ $page->slug }}" disabled>
                                <small class="form-hint">Slug cannot be changed for core system pages.</small>
                            </div>
                        </div>
                    </div>

                    {{-- ── Hero / Intro ── --}}
                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title mb-0">Hero / Intro Section</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Intro Heading</label>
                                <input type="text" name="introTitle" class="form-control"
                                       value="{{ $v('introTitle', "We'd love to hear from you") }}"
                                       placeholder="We'd love to hear from you">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Intro Description</label>
                                <textarea name="introText" class="form-control" rows="3"
                                          placeholder="Short description shown below the heading…">{{ $v('introText') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ── Phone ── --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                Phone Numbers
                                <span class="badge bg-blue-lt text-blue ms-2" style="font-size:.7rem">Synced with System Settings</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Phone Numbers <small class="text-muted">(one per line — first line syncs to System Settings)</small></label>
                                <textarea name="phoneNumbers" class="form-control font-monospace" rows="3"
                                          placeholder="+91 98765 43210&#10;+91 98765 43211">{{ $v('phoneNumbers') }}</textarea>
                                @if(!empty($systemSettings['sellerSupportNumber']))
                                    <div class="form-hint">System Settings value: <code>{{ $systemSettings['sellerSupportNumber'] }}</code></div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Sub-note <small class="text-muted">(e.g. availability hours)</small></label>
                                <input type="text" name="phoneNote" class="form-control"
                                       value="{{ $v('phoneNote', 'Mon–Sat, 9 AM – 7 PM IST') }}"
                                       placeholder="Mon–Sat, 9 AM – 7 PM IST">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">WhatsApp Number <small class="text-muted">(digits only, with country code — for the Chat on WhatsApp button)</small></label>
                                <input type="text" name="whatsappNumber" class="form-control font-monospace"
                                       value="{{ $v('whatsappNumber') }}"
                                       placeholder="919876543210">
                                <small class="form-hint">Used in wa.me link. Example: <code>919876543210</code></small>
                            </div>
                        </div>
                    </div>

                    {{-- ── Email ── --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                Email Addresses
                                <span class="badge bg-blue-lt text-blue ms-2" style="font-size:.7rem">Synced with System Settings</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Email Addresses <small class="text-muted">(one per line — first line syncs to System Settings)</small></label>
                                <textarea name="emails" class="form-control font-monospace" rows="3"
                                          placeholder="support@example.com&#10;sales@example.com">{{ $v('emails') }}</textarea>
                                @if(!empty($systemSettings['sellerSupportEmail']))
                                    <div class="form-hint">System Settings value: <code>{{ $systemSettings['sellerSupportEmail'] }}</code></div>
                                @endif
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Email Sub-note</label>
                                <input type="text" name="emailNote" class="form-control"
                                       value="{{ $v('emailNote', 'We reply within 24 hours') }}"
                                       placeholder="We reply within 24 hours">
                            </div>
                        </div>
                    </div>

                    {{-- ── Office ── --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                Office / Address
                                <span class="badge bg-blue-lt text-blue ms-2" style="font-size:.7rem">Address synced with System Settings</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Office / Company Name</label>
                                <input type="text" name="officeName" class="form-control"
                                       value="{{ $v('officeName') }}"
                                       placeholder="Pethiyan Packaging Pvt. Ltd.">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Office Address <small class="text-muted">(syncs to/from System Settings → Company Address)</small></label>
                                <textarea name="officeAddress" class="form-control" rows="2"
                                          placeholder="Mumbai, Maharashtra — 400001">{{ $v('officeAddress') }}</textarea>
                                @if(!empty($systemSettings['companyAddress']))
                                    <div class="form-hint">System Settings value: <code>{{ $systemSettings['companyAddress'] }}</code></div>
                                @endif
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Office Sub-note</label>
                                <input type="text" name="officeNote" class="form-control"
                                       value="{{ $v('officeNote', 'Visit by appointment only') }}"
                                       placeholder="Visit by appointment only">
                            </div>
                        </div>
                    </div>

                    {{-- ── Business Hours ── --}}
                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title mb-0">Business Hours</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Days</label>
                                <input type="text" name="businessHoursLine1" class="form-control"
                                       value="{{ $v('businessHoursLine1', 'Monday – Saturday') }}"
                                       placeholder="Monday – Saturday">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Timings</label>
                                <input type="text" name="businessHoursLine2" class="form-control"
                                       value="{{ $v('businessHoursLine2', '9:00 AM – 7:00 PM IST') }}"
                                       placeholder="9:00 AM – 7:00 PM IST">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Sub-note</label>
                                <input type="text" name="businessHoursNote" class="form-control"
                                       value="{{ $v('businessHoursNote', 'Closed on national holidays') }}"
                                       placeholder="Closed on national holidays">
                            </div>
                        </div>
                    </div>

                    {{-- ── SEO ── --}}
                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title mb-0">SEO Settings <small class="text-muted fw-normal">(optional)</small></h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control"
                                       value="{{ old('meta_title', $page->meta_title) }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer text-end mt-2">
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-link">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Contact Page</button>
                    </div>
                </form>

            @else
                {{-- ── RICH TEXT EDITOR (Quill — all non-contact pages) ───────── --}}
                <form action="{{ route('admin.pages.update', $page) }}" method="POST" id="page-edit-form">
                    @csrf

                    {{-- Page title & slug --}}
                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title mb-0">Page</h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Page Title</label>
                                <input type="text" name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $page->title) }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" value="{{ $page->slug }}" disabled>
                                <small class="form-hint">Slug cannot be changed for core system pages.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Rich text content --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Page Content</h4>
                            <div class="card-options">
                                <span class="badge bg-green-lt text-green">Rich Text Editor</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="quill-editor"></div>
                            <input type="hidden" name="content" id="quill-content-input"
                                   value="{{ old('content', $page->content ?? '') }}">
                            @error('content')<div class="invalid-feedback d-block px-3 pb-3">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- SEO --}}
                    <div class="card mb-4">
                        <div class="card-header"><h4 class="card-title mb-0">SEO Settings <small class="text-muted fw-normal">(optional)</small></h4></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control"
                                       value="{{ old('meta_title', $page->meta_title) }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer text-end mt-2">
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-link">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            @endif

        </div>
    </div>
@endsection

@unless($page->slug === 'contact-us')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<style>
    #quill-editor         { min-height: 460px; font-size: 14px; }
    .ql-toolbar.ql-snow   { border-left: none; border-right: none; border-top: none;
                            border-bottom: 1px solid #e6e7e9; border-radius: 0; }
    .ql-container.ql-snow { border: none; }
    .ql-editor            { padding: 1rem 1.25rem; line-height: 1.7; }
    .ql-editor h1         { font-size: 1.75rem; font-weight: 700; margin: 1rem 0 .5rem; }
    .ql-editor h2         { font-size: 1.375rem; font-weight: 700; margin: 1rem 0 .5rem; }
    .ql-editor h3         { font-size: 1.125rem; font-weight: 600; margin: .75rem 0 .4rem; }
    .ql-editor p          { margin-bottom: .6rem; }
    .ql-editor ul,
    .ql-editor ol         { padding-left: 1.5rem; margin-bottom: .6rem; }
    .ql-editor blockquote { border-left: 3px solid #e6e7e9; padding-left: 1rem;
                            color: #6c757d; margin: .75rem 0; }
    .ql-editor a          { color: #0066cc; text-decoration: underline; }
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Start writing page content…',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'blockquote'],
                [{ indent: '-1' }, { indent: '+1' }],
                ['clean'],
            ],
        },
    });

    // Load existing content
    const hiddenInput = document.getElementById('quill-content-input');
    const existing    = hiddenInput.value.trim();
    if (existing) {
        quill.clipboard.dangerouslyPasteHTML(0, existing);
    }

    // Before submit, copy editor HTML to the hidden field
    document.getElementById('page-edit-form').addEventListener('submit', function () {
        hiddenInput.value = quill.getSemanticHTML
            ? quill.getSemanticHTML()
            : quill.root.innerHTML;
    });
})();
</script>
@endpush
@endunless
