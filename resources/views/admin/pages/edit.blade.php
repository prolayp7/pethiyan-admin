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

            @elseif($page->slug === 'about-us')
                @php
                    $aboutBlocks = is_array($page->content_blocks) ? $page->content_blocks : [];
                    $oldSections = old('about_sections');
                    $aboutSections = is_string($oldSections)
                        ? (json_decode($oldSections, true) ?: [])
                        : ($aboutBlocks['story_sections'] ?? []);
                    $oldValues = old('about_values');
                    $aboutValues = is_string($oldValues)
                        ? (json_decode($oldValues, true) ?: [])
                        : ($aboutBlocks['core_values'] ?? []);
                @endphp

                <form action="{{ route('admin.pages.update', $page) }}" method="POST" id="about-page-form">
                    @csrf

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

                    <div class="card mb-4">
                        <div class="card-header">
                            <div>
                                <h4 class="card-title mb-1">About Story Sections</h4>
                                <p class="text-muted mb-0">Add multiple image + text blocks for the About page. Each block can place the image on the left or right.</p>
                            </div>
                            <div class="card-options">
                                <button type="button" class="btn btn-primary btn-sm" id="add-about-section">Add Section</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-4">
                                Use the text editor to format content and add links to other pages directly inside the text. Upload an image for each section, then choose whether it should appear on the left or right.
                            </div>

                            <input type="hidden" name="about_sections" id="about-sections-input" value="{{ old('about_sections') }}">
                            @error('about_sections')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                            <div id="about-sections-list" class="d-flex flex-column gap-4"></div>

                            <div id="about-sections-empty" class="border rounded-3 p-4 text-center text-muted">
                                No sections added yet. Click <strong>Add Section</strong> to create the first content block.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <div>
                                <h4 class="card-title mb-1">Core Values Section</h4>
                                <p class="text-muted mb-0">Manage the “What Drives Us / Our Core Values” section shown on the About page frontend.</p>
                            </div>
                            <div class="card-options">
                                <button type="button" class="btn btn-primary btn-sm" id="add-about-value-item">Add Value</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Section Eyebrow</label>
                                    <input type="text" class="form-control" id="about-values-eyebrow" value="{{ $aboutValues['eyebrow'] ?? '' }}" placeholder="WHAT DRIVES US">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Section Heading</label>
                                    <input type="text" class="form-control" id="about-values-heading" value="{{ $aboutValues['heading'] ?? '' }}" placeholder="Our Core Values">
                                </div>
                            </div>

                            <input type="hidden" name="about_values" id="about-values-input" value="{{ old('about_values') }}">
                            @error('about_values')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                            <div id="about-values-list" class="d-flex flex-column gap-4"></div>
                            <div id="about-values-empty" class="border rounded-3 p-4 text-center text-muted">
                                No value cards added yet. Click <strong>Add Value</strong> to create one.
                            </div>
                        </div>
                    </div>

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
                        <button type="submit" class="btn btn-primary">Save About Page</button>
                    </div>
                </form>

                <template id="about-section-template">
                    <div class="card about-section-item">
                        <div class="card-header">
                            <div>
                                <h4 class="card-title mb-1">Section <span class="about-section-number"></span></h4>
                                <p class="text-muted mb-0">Manage the heading, text, image, and image alignment for this block.</p>
                            </div>
                            <div class="card-options">
                                <button type="button" class="btn btn-outline-danger btn-sm about-remove-section">Remove</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Subheading</label>
                                    <input type="text" class="form-control about-field-subheading" placeholder="OUR STORY">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Heading</label>
                                    <input type="text" class="form-control about-field-heading" placeholder="Built for India's Growing Businesses">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Image Position</label>
                                    <select class="form-select about-field-position">
                                        <option value="right">Image Right</option>
                                        <option value="left">Image Left</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Text Content</label>
                                <div class="about-editor border rounded">
                                    <div class="about-editor-toolbar"></div>
                                    <div class="about-editor-body"></div>
                                </div>
                                <small class="form-hint">Use links, formatting, and lists as needed.</small>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Image</label>
                                    <input type="file" class="about-image-input" accept="image/*">
                                    <div class="d-flex justify-content-end mt-2">
                                        <button type="button" class="btn btn-outline-secondary about-remove-image">Clear</button>
                                    </div>
                                    <small class="form-hint">Upload JPG, PNG, GIF, or WEBP up to 5 MB.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Image Alt Text</label>
                                    <input type="text" class="form-control about-field-image-alt" placeholder="About section image">
                                    <div class="mt-3">
                                        <label class="form-label">Uploaded Image URL</label>
                                        <input type="text" class="form-control about-field-image-url" placeholder="Auto-filled after upload">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template id="about-value-template">
                    <div class="card about-value-item">
                        <div class="card-header">
                            <div>
                                <h4 class="card-title mb-1">Value Card <span class="about-value-number"></span></h4>
                                <p class="text-muted mb-0">Choose an icon, title, and description for this core value.</p>
                            </div>
                            <div class="card-options">
                                <button type="button" class="btn btn-outline-danger btn-sm about-remove-value">Remove</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Icon</label>
                                    <select class="form-select about-value-icon">
                                        <option value="leaf">Leaf / Eco</option>
                                        <option value="award">Award / Quality</option>
                                        <option value="users">Users / Customer</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control about-value-title" placeholder="Eco-First">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control about-value-description" rows="3" placeholder="Explain this core value..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

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

@if($page->slug === 'about-us')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<style>
    .about-editor-body .ql-editor { min-height: 220px; font-size: 14px; line-height: 1.7; }
    .about-editor .ql-toolbar.ql-snow { border-left: none; border-right: none; border-top: none; }
    .about-editor .ql-container.ql-snow { border: none; }
    .about-section-item.is-uploading { opacity: .7; pointer-events: none; }
    .about-section-item .filepond--root { margin-bottom: 0; }
</style>
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
    const initialSections = @json($aboutSections ?? []);
    const initialValues = @json($aboutValues ?? []);
    const uploadUrl = @json(route('admin.pages.media.store', $page));
    const csrfToken = @json(csrf_token());
    const list = document.getElementById('about-sections-list');
    const emptyState = document.getElementById('about-sections-empty');
    const addButton = document.getElementById('add-about-section');
    const valuesList = document.getElementById('about-values-list');
    const valuesEmptyState = document.getElementById('about-values-empty');
    const addValueButton = document.getElementById('add-about-value-item');
    const valuesTemplate = document.getElementById('about-value-template');
    const valuesInput = document.getElementById('about-values-input');
    const valuesEyebrowInput = document.getElementById('about-values-eyebrow');
    const valuesHeadingInput = document.getElementById('about-values-heading');
    const form = document.getElementById('about-page-form');
    const hiddenInput = document.getElementById('about-sections-input');
    const template = document.getElementById('about-section-template');
    const quillInstances = [];

    function normalizeLocalhostOrigin(url) {
        if (!url || typeof url !== 'string') return url;

        try {
            const parsed = new URL(url, window.location.origin);
            const isLoopback = ['localhost', '127.0.0.1'].includes(parsed.hostname);
            const currentIsLoopback = ['localhost', '127.0.0.1'].includes(window.location.hostname);

            if (isLoopback && currentIsLoopback) {
                return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`;
            }

            return parsed.toString();
        } catch (_error) {
            return url;
        }
    }

    function toolbarConfig() {
        return [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'blockquote'],
            ['clean'],
        ];
    }

    function syncEmptyState() {
        emptyState.classList.toggle('d-none', list.children.length > 0);
        Array.from(list.children).forEach((item, index) => {
            const number = item.querySelector('.about-section-number');
            if (number) {
                number.textContent = index + 1;
            }
        });
    }

    function syncValuesEmptyState() {
        valuesEmptyState.classList.toggle('d-none', valuesList.children.length > 0);
        Array.from(valuesList.children).forEach((item, index) => {
            const number = item.querySelector('.about-value-number');
            if (number) {
                number.textContent = index + 1;
            }
        });
    }

    function createImagePond(input, imageUrlInput, sectionEl) {
        return FilePond.create(input, {
            allowImagePreview: true,
            credits: false,
            storeAsFile: false,
            instantUpload: true,
            maxFileSize: '5MB',
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'],
            labelIdle: 'Drag & drop image or <span class="filepond--label-action">Browse</span>',
            server: {
                process: (fieldName, file, _metadata, load, error, progress, abort) => {
                    const formData = new FormData();
                    formData.append('file', file, file.name);

                    const request = new XMLHttpRequest();
                    request.open('POST', uploadUrl);
                    request.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    request.setRequestHeader('Accept', 'application/json');

                    request.upload.onprogress = (event) => {
                        progress(event.lengthComputable, event.loaded, event.total);
                    };

                    request.onload = () => {
                        try {
                            const payload = JSON.parse(request.responseText || '{}');
                            if (request.status >= 200 && request.status < 300 && payload.url) {
                                imageUrlInput.value = payload.url;
                                load(payload.url);
                                return;
                            }

                            error(payload.message || 'Image upload failed.');
                        } catch (_parseError) {
                            error('Image upload failed.');
                        }
                    };

                    request.onerror = () => error('Image upload failed.');
                    request.send(formData);

                    return {
                        abort: () => {
                            request.abort();
                            abort();
                        }
                    };
                },
                load: (source, load, error) => {
                    fetch(normalizeLocalhostOrigin(source))
                        .then((response) => {
                            const contentType = (response.headers.get('content-type') || 'image/jpeg').split(';')[0].trim();
                            return response.blob().then((blob) => new Blob([blob], { type: contentType }));
                        })
                        .then((blob) => load(blob))
                        .catch((err) => error(err));

                    return {
                        abort: () => {}
                    };
                },
                revert: (_uniqueFileId, load) => {
                    imageUrlInput.value = '';
                    load();
                },
            },
            files: imageUrlInput.value ? [{
                source: normalizeLocalhostOrigin(imageUrlInput.value),
                options: { type: 'local' },
            }] : [],
        });
    }

    function createSection(section = {}) {
        const fragment = template.content.cloneNode(true);
        const sectionEl = fragment.querySelector('.about-section-item');
        const subheadingInput = sectionEl.querySelector('.about-field-subheading');
        const headingInput = sectionEl.querySelector('.about-field-heading');
        const positionInput = sectionEl.querySelector('.about-field-position');
        const imageAltInput = sectionEl.querySelector('.about-field-image-alt');
        const imageUrlInput = sectionEl.querySelector('.about-field-image-url');
        const imageInput = sectionEl.querySelector('.about-image-input');
        const removeButton = sectionEl.querySelector('.about-remove-section');
        const clearImageButton = sectionEl.querySelector('.about-remove-image');
        const toolbar = sectionEl.querySelector('.about-editor-toolbar');
        const editorBody = sectionEl.querySelector('.about-editor-body');

        subheadingInput.value = section.subheading || '';
        headingInput.value = section.heading || '';
        positionInput.value = section.image_position === 'left' ? 'left' : 'right';
        imageAltInput.value = section.image_alt || '';
        imageUrlInput.value = section.image_url || '';

        const quill = new Quill(editorBody, {
            theme: 'snow',
            placeholder: 'Add text details for this section…',
            modules: { toolbar: toolbarConfig() },
        });

        if (section.body_html) {
            quill.clipboard.dangerouslyPasteHTML(0, section.body_html);
        }

        const pond = createImagePond(imageInput, imageUrlInput, sectionEl);
        quillInstances.push({ sectionEl, quill, pond });

        removeButton.addEventListener('click', function () {
            const index = quillInstances.findIndex((instance) => instance.sectionEl === sectionEl);
            if (index >= 0) {
                quillInstances[index].pond?.destroy();
                quillInstances.splice(index, 1);
            }
            sectionEl.remove();
            syncEmptyState();
        });

        clearImageButton.addEventListener('click', function () {
            imageUrlInput.value = '';
            pond.removeFiles();
        });

        list.appendChild(sectionEl);
        syncEmptyState();
    }

    addButton.addEventListener('click', function () {
        createSection();
    });

    function createValueItem(item = {}) {
        const fragment = valuesTemplate.content.cloneNode(true);
        const itemEl = fragment.querySelector('.about-value-item');
        const iconInput = itemEl.querySelector('.about-value-icon');
        const titleInput = itemEl.querySelector('.about-value-title');
        const descriptionInput = itemEl.querySelector('.about-value-description');
        const removeButton = itemEl.querySelector('.about-remove-value');

        iconInput.value = item.icon || 'leaf';
        titleInput.value = item.title || '';
        descriptionInput.value = item.description || '';

        removeButton.addEventListener('click', function () {
            itemEl.remove();
            syncValuesEmptyState();
        });

        valuesList.appendChild(itemEl);
        syncValuesEmptyState();
    }

    addValueButton.addEventListener('click', function () {
        createValueItem();
    });

    form.addEventListener('submit', function () {
        const payload = quillInstances.map(({ sectionEl, quill }) => ({
            subheading: sectionEl.querySelector('.about-field-subheading').value.trim(),
            heading: sectionEl.querySelector('.about-field-heading').value.trim(),
            body_html: quill.getSemanticHTML ? quill.getSemanticHTML() : quill.root.innerHTML,
            image_url: sectionEl.querySelector('.about-field-image-url').value.trim(),
            image_alt: sectionEl.querySelector('.about-field-image-alt').value.trim(),
            image_position: sectionEl.querySelector('.about-field-position').value === 'left' ? 'left' : 'right',
        })).filter((section) => (
            section.subheading || section.heading || section.body_html.replace(/<(.|\n)*?>/g, '').trim() || section.image_url
        ));

        hiddenInput.value = JSON.stringify(payload);

        const valuePayload = Array.from(valuesList.children).map((itemEl) => ({
            icon: itemEl.querySelector('.about-value-icon').value,
            title: itemEl.querySelector('.about-value-title').value.trim(),
            description: itemEl.querySelector('.about-value-description').value.trim(),
        })).filter((item) => item.title || item.description);

        valuesInput.value = JSON.stringify({
            eyebrow: valuesEyebrowInput.value.trim(),
            heading: valuesHeadingInput.value.trim(),
            items: valuePayload,
        });
    });

    if (Array.isArray(initialSections) && initialSections.length > 0) {
        initialSections.forEach((section) => createSection(section));
    } else {
        syncEmptyState();
    }

    if (Array.isArray(initialValues.items) && initialValues.items.length > 0) {
        initialValues.items.forEach((item) => createValueItem(item));
    } else {
        syncValuesEmptyState();
    }
})();
</script>
@endpush
@elseif($page->slug !== 'contact-us')
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
@endif
