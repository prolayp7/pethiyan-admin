<div>
    <input
        type="file"
        class="form-control"
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        data-image-url="{{ $imageUrl ?? '' }}"
        disabled="{{ $disabled ?? 'false' }}"
        multiple="{{ $multiple ?? 'false' }}"
        {{ $attributes }}
    />
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const normalizeLocalhostOrigin = (url) => {
            if (!url || typeof url !== 'string') return url;

            try {
                const parsed = new URL(url, window.location.origin);
                const isLoopback = ['localhost', '127.0.0.1'].includes(parsed.hostname);
                const currentIsLoopback = ['localhost', '127.0.0.1'].includes(window.location.hostname);

                if (isLoopback && currentIsLoopback) {
                    return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`;
                }

                return parsed.toString();
            } catch (_e) {
                return url;
            }
        };

        FilePond.registerPlugin(FilePondPluginImagePreview);
        const input = document.querySelector('[name="{{ $name }}"]');
        if (input) {
            let imageUrl = normalizeLocalhostOrigin(input.getAttribute('data-image-url'));
            let userAddedNewFile = false;
            let suppressServerRemove = false;

            const pond = FilePond.create(input, {
                allowImagePreview: true,
                instantUpload: false,
                acceptedFileTypes: ['image/*'],
                credits: false,
                storeAsFile: true,
                onaddfile: (error, fileItem) => {
                    if (!error && fileItem.origin === FilePond.FileOrigin.INPUT) {
                        userAddedNewFile = true;
                    }
                },
                onremovefile: (error, fileItem) => {
                    if (fileItem && fileItem.origin === FilePond.FileOrigin.INPUT) {
                        userAddedNewFile = false;
                    }
                },
                server: {
                    load: (source, load, error, progress, abort) => {
                        fetch(normalizeLocalhostOrigin(source))
                            .then(response => {
                                const contentType = response.headers.get('content-type') || 'image/jpeg';
                                return response.blob().then(blob => {
                                    const ext = contentType.split('/').pop() || 'jpg';
                                    return new File([blob], `image.${ext}`, { type: contentType });
                                });
                            })
                            .then(file => load(file))
                            .catch(err => error(err));
                        return { abort: () => {} };
                    },
                    // If the input provides model/collection data attributes, call server to remove the media
                    remove: (source, load, error) => {
                        if (suppressServerRemove) {
                            suppressServerRemove = false;
                            load();
                            return;
                        }
                        try {
                            const modelId = input.dataset.modelId || null;
                            const collection = input.dataset.collection || null;
                            const mediaId = input.dataset.mediaId || null;
                            if (modelId && collection) {
                                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                                const headers = { 'Content-Type': 'application/json' };
                                if (tokenMeta) headers['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');
                                const body = {};
                                if (collection) body.collection = collection;
                                if (mediaId) body.media_id = mediaId;
                                fetch(`/admin/products/${modelId}/media`, {
                                    method: 'DELETE',
                                    headers,
                                    body: JSON.stringify(body)
                                }).then(res => {
                                    if (res.ok) {
                                        load();
                                    } else {
                                        error('Failed to delete media on server');
                                    }
                                }).catch(err => error(err));
                            } else {
                                // no model info — just succeed client-side
                                load();
                            }
                        } catch (e) {
                            error(e);
                        }
                    }
                },
                files: imageUrl ? [{
                    source: imageUrl, options: {
                        type: 'local'
                    }
                }] : []
            });

            // Before form submit: if the user hasn't uploaded a new image, clear the
            // preloaded original so the field arrives empty and the controller keeps
            // the existing stored image (via its hasFile() guard).
            const form = input.closest('form');
            if (form && imageUrl) {
                form.addEventListener('submit', function () {
                    if (!userAddedNewFile && pond.getFiles().length > 0) {
                        suppressServerRemove = true;
                        pond.removeFiles();
                    }
                });
            }
        }
    });
</script>
