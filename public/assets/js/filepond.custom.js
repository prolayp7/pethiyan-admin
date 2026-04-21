document.addEventListener('DOMContentLoaded', () => {
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
    FilePond.registerPlugin(FilePondPluginFileValidateType);
    FilePond.registerPlugin(FilePondPluginFileValidateSize);
    // Initialize FilePond for serviceAccountFile
    const serviceAccountInput = document.querySelector('[name="serviceAccountFile"]');
    const serviceAccountUrl = serviceAccountInput
        ? normalizeLocalhostOrigin(serviceAccountInput.getAttribute('data-service-url'))
        : '';
    FilePond.create(serviceAccountInput, {
        allowImagePreview: false, // Disable image preview for non-image files
        credits: false, storeAsFile: true, acceptedFileTypes: ['application/json', 'text/plain'], // Adjust based on expected file types
        files: serviceAccountUrl ? [{
            source: serviceAccountUrl,
            options: {
                type: 'remote'
            }
        }] : []
    });

    const systemUpdateInput = document.querySelector('[name="package"]');

    FilePond.create(systemUpdateInput, {
        allowImagePreview: false, // Disable image preview for non-image files
        credits: false,
        storeAsFile: true,
        acceptedFileTypes: [
            'application/zip',
            'application/x-zip-compressed',
            'multipart/x-zip',
        ],
        maxFileSize: '100MB',
        files: []
    })

    const filePondServerLoad = {
        load: (source, load, error, progress, abort) => {
            fetch(normalizeLocalhostOrigin(source))
                .then(response => {
                    // Preserve Content-Type so FilePond type-validation doesn't reject it
                    const contentType = (response.headers.get('content-type') || 'image/jpeg').split(';')[0].trim();
                    return response.blob().then(blob => new Blob([blob], { type: contentType }));
                })
                .then(blob => load(blob))
                .catch(err => error(err));
            return { abort: () => {} };
        }
    };

    function initializeFilePond(inputName, allowFileTypes = ['image/*'], maxFileSize = null) {
        const input = document.querySelector(`[name="${inputName}"]`);
        if (!input) return;

        const imageUrl = normalizeLocalhostOrigin(input.getAttribute('data-image-url') || '');
        FilePond.create(input, {
            allowImagePreview: true,
            credits: false,
            storeAsFile: true,
            maxFileSize: maxFileSize,
            acceptedFileTypes: allowFileTypes,
            server: filePondServerLoad,
            files: imageUrl ? [{
                source: imageUrl,
                options: {type: 'local'}
            }] : []
        });
    }

    initializeFilePond('backgroundImage', ['image/jpeg','image/png','image/jpg','image/webp'], '5MB');
    initializeFilePond('banner', ['image/*'], '10MB');
    initializeFilePond('profile_image', ['image/*'], '5MB');
    initializeFilePond('address_proof', ['image/*'], '5MB');
    initializeFilePond('voided_check', ['image/*'], '5MB');
    initializeFilePond('activeIcon', ['image/*'], '2MB');
    initializeFilePond('image', ['image/*'], '5MB');
    initializeFilePond('active_icon', ['image/*'], '2MB');
    initializeFilePond('icon', ['image/*'], '2MB');
    initializeFilePond('desktop_4k_background_image', ['image/*'], '5MB');
    initializeFilePond('desktop_fdh_background_image', ['image/*'], '5MB');
    initializeFilePond('tablet_background_image', ['image/*'], '5MB');
    initializeFilePond('mobile_background_image', ['image/*'], '5MB');
    initializeFilePond('product_video', ['video/mp4', 'video/mkv', 'video/webm'], '20MB');
    initializeFilePond('siteFavicon', ['image/*'], '2MB');
    initializeFilePond('siteHeaderDarkLogo', ['image/*'], '5MB');
    initializeFilePond('siteHeaderLogo', ['image/*'], '5MB');
    initializeFilePond('siteFooterLogo', ['image/*'], '5MB');
    initializeFilePond('seoOgImage', ['image/*'], '4MB');
    initializeFilePond('seoTwitterImage', ['image/*'], '4MB');
    initializeFilePond('og_image', ['image/*'], '4MB');
    initializeFilePond('twitter_image', ['image/*'], '4MB');
    initializeFilePond('banner_image', ['image/*'], '10MB');
    initializeFilePond('favicon', ['image/png'], '1MB');
    initializeFilePond('logo', ['image/*'], '5MB');
    initializeFilePond('store_logo', ['image/*'], '5MB');
    initializeFilePond('store_banner', ['image/*'], '10MB');
    initializeFilePond('adminSignature', ['image/*'], '2MB')

    const input = document.querySelector(`[name="additional_images[]"]`);
    if (input) {
        const imagesJson = input.getAttribute('data-images');
        let imageFiles = [];
        const mediaMap = {}; // url -> mediaId
        if (imagesJson !== null && imagesJson !== '' && imagesJson !== undefined) {
            try {
                const parsed = JSON.parse(imagesJson);
                if (Array.isArray(parsed)) {
                    parsed.forEach(item => {
                        const url = normalizeLocalhostOrigin(item.url || item);
                        imageFiles.push({ source: url, options: { type: 'local' } });
                        if (item.id) mediaMap[url] = item.id;
                    });
                }
            } catch (e) {
                // fallback: treat as simple array of urls
                try {
                    const arr = JSON.parse(imagesJson);
                    arr.forEach(u => {
                        const url = normalizeLocalhostOrigin(u);
                        imageFiles.push({ source: url, options: { type: 'local' } });
                    });
                } catch (_e) {
                    // ignore
                }
            }
        }

        const serverOptions = Object.assign({}, filePondServerLoad, {
            remove: (source, load, error) => {
                try {
                    const modelId = input.dataset.modelId || null;
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const headers = { 'Content-Type': 'application/json' };
                    if (tokenMeta) headers['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');

                    const body = {};
                    if (modelId) body.model_id = modelId;
                    // lookup media id by URL
                    const mediaId = mediaMap[source] || null;
                    if (mediaId) body.media_id = mediaId;

                    if (modelId && (mediaId || input.dataset.collection)) {
                        // prefer media_id when available
                        if (!body.collection && input.dataset.collection) body.collection = input.dataset.collection;
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
                        // no server info — proceed client-side
                        load();
                    }
                } catch (e) {
                    error(e);
                }
            }
        });

        FilePond.create(input, {
            allowImagePreview: true,
            credits: false,
            storeAsFile: true,
            maxFileSize: '2MB',
            acceptedFileTypes: ['image/*'],
            server: serverOptions,
            files: imageFiles
        });
    }
});
