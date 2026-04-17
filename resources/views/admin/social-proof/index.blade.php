@extends('layouts.admin.app', ['page' => 'home_section', 'sub_page' => 'social_proof'])

@section('title', 'Social Proof – Testimonials')

@section('header_data')
    @php
        $page_title    = 'Social Proof';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Social Proof',    'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Social Proof</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4" id="spTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-testimonials" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 20l1.3 -3.9a9 8 0 1 1 3.4 2.9l-4.7 1z"/></svg>
                    Testimonials <span class="badge bg-blue ms-1">{{ $testimonials->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sp-settings" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94-1.543.826-3.31 2.37-2.37c1 .608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                    Section Settings
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 1 — TESTIMONIALS
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="tab-testimonials">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Drag rows to reorder. Changes save automatically.</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#testimonialModal" onclick="openTestimonialModal()">
                        + Add Testimonial
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table" id="testimonialsTable">
                            <thead>
                                <tr>
                                    <th style="width:32px"></th>
                                    <th style="width:56px">Avatar</th>
                                    <th>Customer</th>
                                    <th>Quote</th>
                                    <th style="width:80px">Stars</th>
                                    <th style="width:90px">Active</th>
                                    <th style="width:100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="testimonialsSortable">
                                @foreach($testimonials as $testimonial)
                                <tr data-id="{{ $testimonial->id }}">
                                    <td class="text-center text-muted" style="cursor:grab">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                    </td>
                                    <td>
                                        @if($testimonial->image_url)
                                            <img src="{{ $testimonial->image_url }}" alt="{{ $testimonial->name }}" class="avatar" style="width:40px;height:40px;object-fit:cover;">
                                        @else
                                            @php
                                                $initials = collect(explode(' ', $testimonial->name))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->join('');
                                                $colors = ['bg-blue','bg-green','bg-purple','bg-orange','bg-teal','bg-pink'];
                                                $color  = $colors[crc32($testimonial->name) % count($colors)];
                                            @endphp
                                                <div class="avatar {{ $color }} text-white fw-bold" style="width:40px;height:40px;">
                                                <span>{{ $initials }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $testimonial->name }}</div>
                                        <small class="text-muted">{{ $testimonial->title }}</small>
                                    </td>
                                    <td>
                                        <div class="text-truncate text-muted" style="max-width:260px">{{ $testimonial->quote }}</div>
                                    </td>
                                    <td>
                                        <div class="text-warning">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $testimonial->stars)★@else☆@endif
                                            @endfor
                                        </div>
                                    </td>
                                    <td>
                                        <label class="form-check form-switch mb-0">
                                            <input class="form-check-input testimonial-toggle" type="checkbox"
                                                data-id="{{ $testimonial->id }}"
                                                {{ $testimonial->is_active ? 'checked' : '' }}>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary px-2 py-1 btn-edit-testimonial"
                                                data-id="{{ $testimonial->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 btn-delete-testimonial"
                                                data-id="{{ $testimonial->id }}" data-name="{{ $testimonial->name }}">
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
                 TAB 2 — SECTION SETTINGS
            ═══════════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-sp-settings">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Section Settings</h3></div>
                            <div class="card-body">
                                <form id="spSettingsForm">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Section Visibility</label>
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="spIsActive" name="is_active"
                                                {{ $settings['is_active'] ? 'checked' : '' }}>
                                            <span class="form-check-label">Show Social Proof section on the homepage</span>
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="spEyebrow">Eyebrow Label</label>
                                        <input type="text" class="form-control" id="spEyebrow" name="eyebrow"
                                            value="{{ $settings['eyebrow'] }}" maxlength="120" placeholder="e.g. SOCIAL PROOF">
                                        <div class="form-hint">Small label shown above the heading (usually uppercase).</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="spHeading">Main Heading</label>
                                        <input type="text" class="form-control" id="spHeading" name="heading"
                                            value="{{ $settings['heading'] }}" maxlength="255" placeholder="e.g. What Our Customers Say">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold" for="spSubheading">Subheading</label>
                                        <input type="text" class="form-control" id="spSubheading" name="subheading"
                                            value="{{ $settings['subheading'] }}" maxlength="255" placeholder="e.g. Trusted by over 10,000 brands worldwide">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold" for="spPlacement">Show Section After</label>
                                        <select class="form-select" id="spPlacement" name="placement">
                                            @foreach([
                                                'after_hero' => 'Hero Section',
                                                'after_categories' => 'Categories',
                                                'after_featured_products' => 'Featured Products',
                                                'after_your_items' => 'Your Items',
                                                'after_recently_viewed' => 'Recently Viewed Products',
                                                'after_video_stories' => 'Video Stories',
                                                'after_why_choose_us' => 'Why Choose Us',
                                                'after_promo_banner' => 'Promo Banner',
                                                'after_newsletter' => 'Newsletter',
                                            ] as $value => $label)
                                                <option value="{{ $value }}" {{ $settings['placement'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" id="saveSpSettingsBtn">
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

{{-- Hidden trigger --}}
<button id="triggerTestimonialModal" data-bs-toggle="modal" data-bs-target="#testimonialModal" style="display:none" aria-hidden="true"></button>

{{-- ═══════════════════════════════════════════════════════════════════════════
     TESTIMONIAL MODAL (Add / Edit)
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal modal-blur fade" id="testimonialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testimonialModalTitle">Add Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testimonialForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="testimonialId" name="_testimonial_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Customer Name</label>
                            <input type="text" class="form-control" name="name" id="t_name" required maxlength="120" placeholder="e.g. Sarah Mitchell">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Star Rating</label>
                            <select class="form-select" name="stars" id="t_stars">
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ str_repeat('★', $i) }} ({{ $i }})</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Title / Role</label>
                            <input type="text" class="form-control" name="title" id="t_title" maxlength="120" placeholder="e.g. Founder, Bloom Organics">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Quote</label>
                            <textarea class="form-control" name="quote" id="t_quote" rows="4" required maxlength="1000" placeholder="Enter the customer's testimonial quote here..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Avatar Image <small class="text-muted">(optional — initials auto-generated if not set)</small></label>
                            <input type="file" class="form-control" name="avatar_image" id="t_avatar" accept="image/jpg,image/jpeg,image/png,image/webp">
                            <div class="form-hint">Max 2 MB. JPG / PNG / WebP.</div>
                            <div id="avatarPreview" class="mt-2" style="display:none">
                                <img id="avatarPreviewImg" src="" alt="" class="avatar avatar-lg rounded">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="t_is_active" checked>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="testimonialSubmitBtn">Save Testimonial</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const allTestimonialsData = {!! json_encode(
        $testimonials->map(fn($t) => $t->append('image_url'))->keyBy('id')
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

    // ── SORTABLE ──────────────────────────────────────────────────────────
    new Sortable(document.getElementById('testimonialsSortable'), {
        animation: 150,
        handle: 'td:first-child',
        onEnd: function () {
            const order = [...document.querySelectorAll('#testimonialsSortable tr')].map(r => r.dataset.id);
            const fd = new FormData();
            order.forEach((id, i) => fd.append('order[]', id));
            fetch('{{ route("admin.social-proof.testimonials.reorder") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: fd,
            }).then(r => r.json()).then(d => { if (d.success) showToast('success', 'Order saved.'); });
        },
    });

    // ── TOGGLE ─────────────────────────────────────────────────────────────
    document.getElementById('testimonialsSortable').addEventListener('change', function (e) {
        if (!e.target.classList.contains('testimonial-toggle')) return;
        const id = e.target.dataset.id;
        fetch(`/admin/social-proof/testimonials/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        }).then(r => r.json()).then(d => {
            if (!d.success) { showToast('error', d.message); e.target.checked = !e.target.checked; }
            else showToast('success', d.message);
        });
    });

    // ── OPEN ADD MODAL ─────────────────────────────────────────────────────
    window.openTestimonialModal = function () {
        document.getElementById('testimonialModalTitle').textContent = 'Add Testimonial';
        document.getElementById('testimonialSubmitBtn').textContent  = 'Save Testimonial';
        document.getElementById('testimonialId').value = '';
        document.getElementById('testimonialForm').reset();
        document.getElementById('avatarPreview').style.display = 'none';
        document.getElementById('t_is_active').checked = true;
    };

    // ── OPEN EDIT MODAL ────────────────────────────────────────────────────
    document.getElementById('testimonialsSortable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-testimonial');
        if (!btn) return;
        const t = allTestimonialsData[btn.dataset.id];
        if (!t) return;

        document.getElementById('testimonialModalTitle').textContent = 'Edit Testimonial';
        document.getElementById('testimonialSubmitBtn').textContent  = 'Update Testimonial';
        document.getElementById('testimonialId').value = t.id;
        document.getElementById('t_name').value        = t.name;
        document.getElementById('t_title').value       = t.title || '';
        document.getElementById('t_quote').value       = t.quote;
        document.getElementById('t_stars').value       = t.stars;
        document.getElementById('t_is_active').checked = !!t.is_active;

        const preview    = document.getElementById('avatarPreview');
        const previewImg = document.getElementById('avatarPreviewImg');
        if (t.image_url) {
            previewImg.src = t.image_url;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        document.getElementById('triggerTestimonialModal').click();
    });

    // Image preview on file select
    document.getElementById('t_avatar').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const previewImg = document.getElementById('avatarPreviewImg');
        previewImg.src = URL.createObjectURL(file);
        document.getElementById('avatarPreview').style.display = 'block';
    });

    // ── SUBMIT FORM ────────────────────────────────────────────────────────
    document.getElementById('testimonialForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('testimonialSubmitBtn');
        const id  = document.getElementById('testimonialId').value;
        const fd  = new FormData(this);
        fd.set('is_active', document.getElementById('t_is_active').checked ? '1' : '0');

        const url = id
            ? `/admin/social-proof/testimonials/${id}`
            : '{{ route("admin.social-proof.testimonials.store") }}';

        ajaxPost(url, fd, btn, function () {
            document.querySelector('#testimonialModal [data-bs-dismiss="modal"]').click();
            location.reload();
        });
    });

    // ── DELETE ─────────────────────────────────────────────────────────────
    document.getElementById('testimonialsSortable').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-testimonial');
        if (!btn) return;
        if (!confirm(`Delete testimonial from "${btn.dataset.name}"?`)) return;
        ajaxDelete(`/admin/social-proof/testimonials/${btn.dataset.id}`, () => btn.closest('tr').remove());
    });

    // ── SECTION SETTINGS ───────────────────────────────────────────────────
    document.getElementById('spSettingsForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('saveSpSettingsBtn');
        const fd  = new FormData();
        fd.append('is_active',  document.getElementById('spIsActive').checked ? '1' : '0');
        fd.append('eyebrow',    document.getElementById('spEyebrow').value);
        fd.append('heading',    document.getElementById('spHeading').value);
        fd.append('subheading', document.getElementById('spSubheading').value);

        ajaxPost('{{ route("admin.social-proof.settings.update") }}', fd, btn, function () {});
    });

}); // end window load
</script>
@endpush
