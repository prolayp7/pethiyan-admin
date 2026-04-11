<div>
    <input
        type="file"
        class="form-control"
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        data-image-url="{{ $imageUrl ?? '' }}"
        disabled="{{ $disabled ?? 'false' }}"
        multiple="{{ $multiple ?? 'false' }}"
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
            FilePond.create(input, {
                allowImagePreview: true,
                instantUpload: false,
                acceptedFileTypes: ['image/*'],
                credits: false,
                storeAsFile: true,
                server: {
                    load: (source, load, error, progress, abort) => {
                        fetch(normalizeLocalhostOrigin(source))
                            .then(response => response.blob())
                            .then(blob => load(blob))
                            .catch(err => error(err));
                        return { abort: () => {} };
                    }
                },
                files: imageUrl ? [{
                    source: imageUrl, options: {
                        type: 'local'
                    }
                }] : []
            });
        }
    });
</script>
