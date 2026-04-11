document.addEventListener('show.bs.modal', function (event) {
    if (event.target.id === 'category-modal') {
        const triggerButton = event.relatedTarget;
        const categoryId = triggerButton?.getAttribute('data-id') || null;
        const categoryBaseUrl = `${base_url}/${panel}/categories`;
        const url = categoryId ? `${categoryBaseUrl}/${categoryId}/edit` : null;

        const form = document.querySelector('.form-submit');
        const imageUpload = document.querySelector('#image-upload');
        const bannerUpload = document.querySelector('#banner-upload');
        const iconUpload = document.querySelector('#icon-upload');
        const activeIconUpload = document.querySelector('#active-icon-upload');
        const backgroundImageUpload = document.querySelector('#background-image-upload');
        const ogImageUpload = document.querySelector('#category-og-image-upload');
        const twitterImageUpload = document.querySelector('#category-twitter-image-upload');
        const backgroundTypeSelect = document.querySelector('#background-type-select');
        const modalTitle = document.querySelector('#category-modal .modal-title');
        const submitButton = document.querySelector('#category-modal button[type="submit"]');
        const parentSelect = document.getElementById("select-parent-category");
        const tomSelectInstance = parentSelect && parentSelect.tomselect; // TomSelect instance

        const normalizeLocalhostOrigin = (url) => {
            if (!url) return '';

            try {
                const parsed = new URL(url, window.location.origin);
                if (parsed.hostname === 'localhost' && window.location.hostname !== 'localhost') {
                    parsed.hostname = window.location.hostname;
                }
                if (parsed.port === '8000' && window.location.port) {
                    parsed.port = window.location.port;
                }

                return parsed.toString();
            } catch (e) {
                return url;
            }
        };

        const ensureFilePondInstance = (input) => {
            if (!input || typeof FilePond === 'undefined') {
                return null;
            }

            let pond = FilePond.find(input);
            if (pond) {
                return pond;
            }

            pond = FilePond.create(input, {
                allowImagePreview: true,
                credits: false,
                storeAsFile: true,
                acceptedFileTypes: ['image/*'],
            });

            return pond;
        };

        const resetFilePondField = (input) => {
            const pond = ensureFilePondInstance(input);
            if (pond) {
                pond.removeFiles();
            }
        };

        const preloadFilePondField = (input, source) => {
            const normalizedSource = normalizeLocalhostOrigin(source || '');
            if (!normalizedSource) {
                return;
            }

            const pond = ensureFilePondInstance(input);
            if (!pond) {
                return;
            }

            pond.removeFiles();
            pond.addFile(normalizedSource).catch((error) => {
                console.error('Failed to preload FilePond file:', normalizedSource, error);
            });
        };

        const setFormFieldValue = (selector, value) => {
            const element = form?.querySelector(selector);
            if (!element) return;
            element.value = value;
        };

        const setFormCheckboxValue = (selector, checked) => {
            const element = form?.querySelector(selector);
            if (!element) return;
            element.checked = checked;
        };

// Remove files from FilePond if available
        resetFilePondField(imageUpload);
        resetFilePondField(bannerUpload);
        resetFilePondField(iconUpload);
        resetFilePondField(activeIconUpload);
        resetFilePondField(backgroundImageUpload);
        resetFilePondField(ogImageUpload);
        resetFilePondField(twitterImageUpload);
        if (categoryId) {
            // Fetch category data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(async responseData => {
                    const data = responseData.data;
                    // Fill form fields
                    setFormFieldValue('input[name="title"]', data.title || '');
                    setFormFieldValue('input[id="category-id"]', categoryId);
                    setFormFieldValue('textarea[name="description"]', data.description || '');
                    setFormCheckboxValue('input[name="status"]', data.status === 'active');

                    // Set background fields only if the controls still exist
                    if (backgroundTypeSelect) {
                        backgroundTypeSelect.value = data.background_type || '';
                        if (typeof toggleBackgroundFields === 'function') {
                            toggleBackgroundFields(data.background_type);
                        }
                    }
                    setFormFieldValue('input[name="background_color"]', data.background_color || '');
                    setFormFieldValue('input[name="font_color"]', data.font_color || '');

                    // is_indexable toggle
                    const isIndexableSwitch = form.querySelector('input[name="is_indexable"]');
                    if (isIndexableSwitch) isIndexableSwitch.checked = data.is_indexable !== false;

                    // SEO fields
                    form.querySelector('input[name="seo_title"]').value = data.metadata?.seo_title || '';
                    form.querySelector('textarea[name="seo_description"]').value = data.metadata?.seo_description || '';
                    form.querySelector('input[name="seo_keywords"]').value = data.metadata?.seo_keywords || '';
                    form.querySelector('input[name="og_title"]').value = data.metadata?.og_title || '';
                    form.querySelector('textarea[name="og_description"]').value = data.metadata?.og_description || '';
                    form.querySelector('input[name="twitter_title"]').value = data.metadata?.twitter_title || '';
                    form.querySelector('textarea[name="twitter_description"]').value = data.metadata?.twitter_description || '';
                    form.querySelector('select[name="twitter_card"]').value = data.metadata?.twitter_card || '';
                    form.querySelector('select[name="schema_mode"]').value = data.metadata?.schema_mode || 'auto';
                    form.querySelector('textarea[name="schema_json_ld"]').value = data.metadata?.schema_json_ld || '';

                    // Set parent_id in TomSelect (auto-select)
                    if (tomSelectInstance) {
                        // If the parent is not in the options yet, load it
                        if (data.parent) {
                            let parentOption = tomSelectInstance.options[data.parent.id];
                            if (!parentOption) {
                                // Fetch the parent (if not already loaded)
                                await fetch(`${categoryBaseUrl}/search?q=${encodeURIComponent(data.parent.title)}`)
                                    .then(res => res.json())
                                    .then(json => {
                                        if (json && json.length) {
                                            tomSelectInstance.addOption(json[0]);
                                        }
                                    });
                            }
                            tomSelectInstance.setValue(data.parent_id);
                        } else {
                            tomSelectInstance.clear();
                        }
                    }

                    // Image upload via FilePond
                    preloadFilePondField(imageUpload, data.image);
                    preloadFilePondField(bannerUpload, data.banner);
                    preloadFilePondField(iconUpload, data.icon);
                    preloadFilePondField(activeIconUpload, data.active_icon);
                    preloadFilePondField(backgroundImageUpload, data.background_image);
                    preloadFilePondField(ogImageUpload, data.og_image || data.metadata?.og_image);
                    preloadFilePondField(twitterImageUpload, data.twitter_image || data.metadata?.twitter_image);

                    // Change form action to update route
                    form.setAttribute('action', `${categoryBaseUrl}/${categoryId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Category';
                    submitButton.textContent = 'Update Category';

                    document.dispatchEvent(new CustomEvent('category-modal:state-applied', {
                        detail: {
                            mode: 'edit',
                            form: form,
                            data: data,
                        }
                    }));
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New category mode
            if (form) form.reset();
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);

            // Reset TomSelect
            if (tomSelectInstance) tomSelectInstance.clear();

            // Reset background fields if present
            if (backgroundTypeSelect) {
                backgroundTypeSelect.value = '';
                if (typeof toggleBackgroundFields === 'function') {
                    toggleBackgroundFields('');
                }
            }
            setFormFieldValue('input[name="background_color"]', '');
            setFormFieldValue('input[name="font_color"]', '');
            const isIndexableSwitchNew = form.querySelector('input[name="is_indexable"]');
            if (isIndexableSwitchNew) isIndexableSwitchNew.checked = true;
            setFormFieldValue('input[name="seo_title"]', '');
            setFormFieldValue('textarea[name="seo_description"]', '');
            setFormFieldValue('input[name="seo_keywords"]', '');
            setFormFieldValue('input[name="og_title"]', '');
            setFormFieldValue('textarea[name="og_description"]', '');
            setFormFieldValue('input[name="twitter_title"]', '');
            setFormFieldValue('textarea[name="twitter_description"]', '');
            setFormFieldValue('select[name="twitter_card"]', '');
            setFormFieldValue('select[name="schema_mode"]', 'auto');
            setFormFieldValue('textarea[name="schema_json_ld"]', '');

            // Set action for create
            form.querySelector('input[id="category-id"]').value = "";
            form.setAttribute('action', categoryBaseUrl);
            modalTitle.textContent = 'Create Category';
            submitButton.textContent = 'Create new Category';

            document.dispatchEvent(new CustomEvent('category-modal:state-applied', {
                detail: {
                    mode: 'create',
                    form: form,
                }
            }));
        }
    }
    if (event.target.id === 'faq-modal') {
        const triggerButton = event.relatedTarget;
        const conditionId = triggerButton ? triggerButton.getAttribute('data-id') : null;
        let url = `${base_url}/${panel}/faqs/${conditionId}/edit`;

        const form = document.querySelector('#faq-modal .form-submit');
        const modalTitle = document.querySelector('#faq-modal .modal-title');
        const submitButton = document.querySelector('#faq-modal button[type="submit"]');

        if (conditionId) {
            // Edit mode: Fetch and populate data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(async responseData => {
                    const data = responseData.data;

                    // Fill form fields
                    form.querySelector('textarea[name="question"]').value = data.question || '';
                    form.querySelector('textarea[name="answer"]').value = data.answer || '';
                    form.querySelector('select[name="status"]').value = data.status || '';

                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/faqs/${conditionId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Faq';
                    submitButton.innerHTML = '<i class="ti ti-edit me-1"></i> Update';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New condition mode: Reset fields
            if (form) form.reset();
            form.querySelector('textarea[name="question"]').value = '';
            form.querySelector('textarea[name="answer"]').value = '';
            form.querySelector('select[name="status"]').value = 'active';
            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/faqs`);
            modalTitle.textContent = 'Add Faq';
            submitButton.innerHTML = '<i class="ti ti-plus me-1"></i> Add';
        }
    }
});

// Delete category
document.addEventListener('click', function (event) {
    handleDelete(event, '.delete-category', `/${panel}/categories/`, 'You are about to delete this Category.');
    handleDelete(event, '.delete-faq', `/${panel}/faqs/`, 'You are about to delete this Faq.');
});

// Background type toggle function
function toggleBackgroundFields(backgroundType) {
    const backgroundColorField = document.getElementById('background-color-field');
    const backgroundImageField = document.getElementById('background-image-field');

    if (backgroundType === 'color') {
        backgroundColorField.style.display = 'block';
        backgroundImageField.style.display = 'none';
    } else if (backgroundType === 'image') {
        backgroundColorField.style.display = 'none';
        backgroundImageField.style.display = 'block';
    } else {
        backgroundColorField.style.display = 'none';
        backgroundImageField.style.display = 'none';
    }
}

// Background type select event listener
document.addEventListener('change', function (event) {
    if (event.target.id === 'background-type-select') {
        toggleBackgroundFields(event.target.value);
    }
});

let tomSelectInstance;

try {
    tomSelectInstance = new TomSelect('.search-labels', {
        create: true,
        maxItems: 3
    });
} catch (e) {
}
document.querySelector('.generate-search-labels-button')?.addEventListener('click', function () {
    if (!tomSelectInstance) return;

    // Pool of random keywords
    const keywords = [
        'Grocery', 'Electronics', 'Daily Essentials', 'Fashion',
        'Beauty', 'Toys', 'Stationery', 'Books', 'Sports', 'Furniture'
    ];

    // Shuffle and pick 3 random keywords
    const randomKeywords = keywords.sort(() => 0.5 - Math.random()).slice(0, 3);

    // Clear old selections
    tomSelectInstance.clear();

    // Add "Search for ..." items
    randomKeywords.forEach(keyword => {
        const label = `Search for ${keyword}`;
        const value = label.toLowerCase().replace(/\s+/g, '_');
        tomSelectInstance.addOption({value, text: label});
        tomSelectInstance.addItem(value);
    });
});
