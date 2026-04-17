@extends('layouts.admin.app', ['page' => 'home_section', 'sub_page' => 'video_story_section'])

@section('title', 'Video Stories')

@section('header_data')
    @php
        $page_title = 'Video Stories';
        $page_pretitle = 'Home Page';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Video Stories', 'url' => null],
    ];
@endphp

@section('admin-content')
<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">Video Stories</h2>
            <x-breadcrumb :items="$breadcrumbs"/>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-videos" type="button">
                    Videos <span class="badge bg-blue ms-1">{{ $videos->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-video-story-settings" type="button">
                    Section Settings
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-videos">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">Drag rows to reorder the carousel. Only active videos are sent to the frontend API.</p>
                    <button class="btn btn-primary btn-sm" onclick="openVideoStoryModal()">
                        + Add Video
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th style="width: 32px"></th>
                                    <th style="width: 120px">Preview</th>
                                    <th>Title</th>
                                    <th style="width: 90px">Active</th>
                                    <th style="width: 110px">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="videoStoriesSortable">
                                @foreach($videos as $video)
                                    <tr data-id="{{ $video->id }}">
                                        <td class="text-center text-muted" style="cursor: grab;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                        </td>
                                        <td>
                                            @if($video->video_url)
                                                <video src="{{ $video->video_url }}" muted playsinline preload="metadata" style="width: 96px; height: 64px; object-fit: cover; border-radius: 10px; background: #111827;"></video>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $video->title }}</div>
                                            <small class="text-muted">{{ basename((string) $video->video_path) }}</small>
                                        </td>
                                        <td>
                                            <label class="form-check form-switch mb-0">
                                                <input class="form-check-input video-story-toggle" type="checkbox" data-id="{{ $video->id }}" {{ $video->is_active ? 'checked' : '' }}>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-outline-primary px-2 py-1" onclick="openVideoStoryModal({{ $video->id }})">Edit</button>
                                                <button class="btn btn-sm btn-outline-danger px-2 py-1" onclick="deleteVideoStory({{ $video->id }}, '{{ addslashes($video->title) }}')">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-video-story-settings">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Section Settings</h3>
                            </div>
                            <div class="card-body">
                                <form id="videoStorySettingsForm">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Section Visibility</label>
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="videoStoryIsActive" {{ $settings['is_active'] ? 'checked' : '' }}>
                                            <span class="form-check-label">Show this section on the homepage</span>
                                        </label>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="videoStoryEyebrow">Eyebrow</label>
                                        <input type="text" class="form-control" id="videoStoryEyebrow" name="eyebrow" value="{{ $settings['eyebrow'] }}" maxlength="120" placeholder="e.g. SHOP & DISCOVER">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="videoStoryHeading">Heading</label>
                                        <input type="text" class="form-control" id="videoStoryHeading" name="heading" value="{{ $settings['heading'] }}" maxlength="255" placeholder="Main section heading">
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label" for="videoStorySubheading">Subheading</label>
                                        <textarea class="form-control" id="videoStorySubheading" name="subheading" rows="3" maxlength="255" placeholder="Short supporting copy">{{ $settings['subheading'] }}</textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label" for="videoStoryPlacement">Show Video Stories After</label>
                                        <select class="form-select" id="videoStoryPlacement" name="placement">
                                            @foreach([
                                                'after_hero' => 'Hero Section',
                                                'after_categories' => 'Categories',
                                                'after_featured_products' => 'Featured Products',
                                                'after_your_items' => 'Your Items',
                                                'after_recently_viewed' => 'Recently Viewed Products',
                                                'after_why_choose_us' => 'Why Choose Us',
                                                'after_promo_banner' => 'Promo Banner',
                                                'after_social_proof' => 'Social Proof',
                                                'after_newsletter' => 'Newsletter',
                                            ] as $value => $label)
                                                <option value="{{ $value }}" {{ $settings['placement'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-hint">Choose which homepage section should appear immediately before the Video Stories block.</div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Autoplay</label>
                                            <label class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="autoplay_enabled" id="videoStoryAutoplay" {{ $settings['autoplay_enabled'] ? 'checked' : '' }}>
                                                <span class="form-check-label">Auto-advance videos</span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="videoStoryAnimationStyle">Animation Style</label>
                                            <select class="form-select" id="videoStoryAnimationStyle" name="animation_style">
                                                @foreach(['slide' => 'Slide', 'fade' => 'Fade', 'none' => 'None'] as $value => $label)
                                                    <option value="{{ $value }}" {{ $settings['animation_style'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="videoStoryAutoplayDelay">Autoplay Delay (ms)</label>
                                            <input type="number" class="form-control" id="videoStoryAutoplayDelay" name="autoplay_delay" min="1500" max="20000" step="100" value="{{ $settings['autoplay_delay'] }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="videoStoryTransitionDuration">Transition Duration (ms)</label>
                                            <input type="number" class="form-control" id="videoStoryTransitionDuration" name="transition_duration" min="0" max="2000" step="10" value="{{ $settings['transition_duration'] }}">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-4 w-100" id="saveVideoStorySettingsBtn">Save Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button id="triggerVideoStoryModal" data-bs-toggle="modal" data-bs-target="#videoStoryModal" style="display:none" aria-hidden="true"></button>

<div class="modal modal-blur fade" id="videoStoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoStoryModalTitle">Add Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="videoStoryForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="videoStoryId" name="_video_story_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required" for="videoStoryTitle">Video Title</label>
                            <input type="text" class="form-control" id="videoStoryTitle" name="title" maxlength="120" required placeholder="Internal title for this video">
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="videoStoryVideo">Video File</label>
                            <input type="file" class="filepond" id="videoStoryVideo" name="video" accept="video/mp4,video/webm,video/quicktime">
                            <div class="form-hint">Allowed formats: MP4, WebM, MOV. Max upload size: 5 MB.</div>
                        </div>
                        <div class="col-12">
                            <div id="videoStoryPreviewWrap" style="display: none;">
                                <label class="form-label">Current Video</label>
                                <video id="videoStoryPreview" controls muted playsinline style="width: 100%; max-height: 320px; border-radius: 12px; background: #111827;"></video>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="videoStoryStatus" checked>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="videoStorySubmitBtn">Save Video</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const videoStoryData = {!! json_encode($videos->keyBy('id')->map(fn ($video) => [
        'id' => $video->id,
        'title' => $video->title,
        'video_url' => $video->video_url,
        'is_active' => $video->is_active,
    ])) !!};
</script>
<script>
window.addEventListener('load', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const videoStoryFileInput = document.getElementById('videoStoryVideo');
    let videoStoryFilePond = null;
    let localPreviewUrl = null;

    function clearLocalPreviewUrl() {
        if (localPreviewUrl) {
            URL.revokeObjectURL(localPreviewUrl);
            localPreviewUrl = null;
        }
    }

    function setVideoPreview(url) {
        const previewWrap = document.getElementById('videoStoryPreviewWrap');
        const preview = document.getElementById('videoStoryPreview');
        if (!previewWrap || !preview) {
            return;
        }

        if (url) {
            preview.src = url;
            previewWrap.style.display = 'block';
            return;
        }

        previewWrap.style.display = 'none';
        preview.removeAttribute('src');
        preview.load();
    }

    if (videoStoryFileInput && typeof FilePond !== 'undefined') {
        videoStoryFilePond = FilePond.create(videoStoryFileInput, {
            credits: false,
            storeAsFile: true,
            allowImagePreview: false,
            acceptedFileTypes: ['video/mp4', 'video/webm', 'video/quicktime'],
            maxFileSize: '5MB',
            labelIdle: 'Drag and drop a video or <span class="filepond--label-action">Browse</span>',
            onupdatefiles: (items) => {
                clearLocalPreviewUrl();

                const file = items[0]?.file ?? null;
                if (file) {
                    localPreviewUrl = URL.createObjectURL(file);
                    setVideoPreview(localPreviewUrl);
                    return;
                }

                if (!document.getElementById('videoStoryId')?.value) {
                    setVideoPreview(null);
                }
            },
        });
    }

    function withBoolean(fd, key, value) {
        fd.set(key, value ? '1' : '0');
    }

    async function sendRequest(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
            ...options,
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Something went wrong.');
        }

        return data;
    }

    window.openVideoStoryModal = function (id = null) {
        const form = document.getElementById('videoStoryForm');
        const title = document.getElementById('videoStoryModalTitle');
        const idInput = document.getElementById('videoStoryId');
        const titleInput = document.getElementById('videoStoryTitle');
        const fileInput = document.getElementById('videoStoryVideo');
        const statusInput = document.getElementById('videoStoryStatus');

        form.reset();
        idInput.value = '';
        fileInput.required = true;
        clearLocalPreviewUrl();
        videoStoryFilePond?.removeFiles();
        setVideoPreview(null);

        if (id && videoStoryData[id]) {
            const current = videoStoryData[id];
            title.textContent = 'Edit Video';
            idInput.value = String(current.id);
            titleInput.value = current.title || '';
            statusInput.checked = !!current.is_active;
            fileInput.required = false;

            if (current.video_url) {
                setVideoPreview(current.video_url);
            }
        } else {
            title.textContent = 'Add Video';
            statusInput.checked = true;
        }

        document.getElementById('triggerVideoStoryModal').click();
    };

    window.deleteVideoStory = async function (id, title) {
        if (!confirm(`Delete "${title}"?`)) {
            return;
        }

        try {
            await sendRequest(`/admin/video-stories-section/videos/${id}`, { method: 'DELETE' });
            window.location.reload();
        } catch (error) {
            alert(error.message);
        }
    };

    document.getElementById('videoStoryForm')?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const id = document.getElementById('videoStoryId').value;
        const submitBtn = document.getElementById('videoStorySubmitBtn');
        const fd = new FormData(form);
        const selectedVideoFile = videoStoryFilePond?.getFiles()?.[0]?.file ?? null;
        const originalButtonHtml = submitBtn.innerHTML;

        if (selectedVideoFile) {
            fd.delete('video');
            fd.append('video', selectedVideoFile, selectedVideoFile.name);
        }

        if (!id && !selectedVideoFile) {
            alert('Please select a video file to upload.');
            return;
        }

        withBoolean(fd, 'is_active', document.getElementById('videoStoryStatus').checked);

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Uploading...';
        try {
            await sendRequest(
                id ? `/admin/video-stories-section/videos/${id}` : '{{ route('admin.video-stories-section.videos.store') }}',
                {
                    method: 'POST',
                    body: fd,
                }
            );
            window.location.reload();
        } catch (error) {
            alert(error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalButtonHtml;
        }
    });

    document.getElementById('videoStorySettingsForm')?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const submitBtn = document.getElementById('saveVideoStorySettingsBtn');
        const fd = new FormData(form);

        withBoolean(fd, 'is_active', document.getElementById('videoStoryIsActive').checked);
        withBoolean(fd, 'autoplay_enabled', document.getElementById('videoStoryAutoplay').checked);

        submitBtn.disabled = true;
        try {
            await sendRequest('{{ route('admin.video-stories-section.settings.update') }}', {
                method: 'POST',
                body: fd,
            });
            alert('Section settings saved.');
        } catch (error) {
            alert(error.message);
        } finally {
            submitBtn.disabled = false;
        }
    });

    document.querySelectorAll('.video-story-toggle').forEach((toggle) => {
        toggle.addEventListener('change', async function () {
            try {
                await sendRequest(`/admin/video-stories-section/videos/${this.dataset.id}/toggle`, {
                    method: 'POST',
                });
            } catch (error) {
                this.checked = !this.checked;
                alert(error.message);
            }
        });
    });

    const sortableRoot = document.getElementById('videoStoriesSortable');
    if (sortableRoot) {
        Sortable.create(sortableRoot, {
            animation: 150,
            onEnd: async function () {
                const order = Array.from(sortableRoot.querySelectorAll('tr')).map((row) => Number(row.dataset.id));
                const fd = new FormData();
                order.forEach((id) => fd.append('order[]', String(id)));

                try {
                    await sendRequest('{{ route('admin.video-stories-section.videos.reorder') }}', {
                        method: 'POST',
                        body: fd,
                    });
                } catch (error) {
                    alert(error.message);
                    window.location.reload();
                }
            },
        });
    }
});
</script>
@endpush
