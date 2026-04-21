document.addEventListener('show.bs.modal', event => {
    if (event.target.id === 'product-condition-modal') {
        const triggerButton = event.relatedTarget;
        const conditionId = triggerButton ? triggerButton.getAttribute('data-id') : null;
        let url = `${base_url}/${panel}/product-conditions/${conditionId}/edit`;

        const form = document.querySelector('#product-condition-modal .form-submit');
        const modalTitle = document.querySelector('#product-condition-modal .modal-title');
        const submitButton = document.querySelector('#product-condition-modal button[type="submit"]');
        const selectCategory = document.getElementById('select-category');
        let selectCategoryTom = selectCategory && selectCategory.tomselect ? selectCategory.tomselect : null;

        if (conditionId) {
            // Edit mode: Fetch and populate data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(async responseData => {
                    const data = responseData.data;

                    // Fill form fields
                    form.querySelector('input[name="title"]').value = data.title || '';

                    if (selectCategoryTom) {
                        await loadCategoryAndSetValue(selectCategoryTom, data.category_id);
                    } else if (selectCategory) {
                        selectCategory.value = data.category_id;
                    }

                    form.querySelector('select[name="alignment"]').value = data.alignment || '';

                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/product-conditions/${conditionId}`);

                    // Insert/ensure _method=PUT for update, if needed
                    let methodInput = form.querySelector('input[name="_method"]');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.setAttribute('type', 'hidden');
                        methodInput.setAttribute('name', '_method');
                        form.appendChild(methodInput);
                    }
                    methodInput.value = 'POST';

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Product Condition';
                    submitButton.innerHTML = '<i class="ti ti-edit me-1"></i> Update';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New condition mode: Reset fields
            if (form) form.reset();
            if (selectCategoryTom) selectCategoryTom.clear();
            if (selectCategory) selectCategory.value = '';
            // Remove _method input if it exists
            const methodInput = form.querySelector('input[name="_method"]');
            if (methodInput) methodInput.parentNode.removeChild(methodInput);

            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/product-conditions`);
            modalTitle.textContent = 'Add Product Condition';
            submitButton.innerHTML = '<i class="ti ti-plus me-1"></i> Create';
        }
    }
    if (event.target.id === 'faq-modal') {
        const triggerButton = event.relatedTarget;
        const faqId = triggerButton ? triggerButton.getAttribute('data-id') : null;

        const form = document.querySelector('#faq-modal .form-submit');
        const modalTitle = document.querySelector('#faq-modal .modal-title');
        const submitButton = document.querySelector('#faq-modal button[type="submit"]');
        if (!form) return;

        if (faqId) {
            fetch(`${base_url}/${panel}/faqs/${faqId}/edit`, {method: 'GET'})
                .then(response => response.json())
                .then(responseData => {
                    const data = responseData.data;
                    form.querySelector('textarea[name="question"]').value = data.question || '';
                    form.querySelector('textarea[name="answer"]').value = data.answer || '';
                    form.querySelector('select[name="status"]').value = data.status || 'active';
                    form.setAttribute('action', `${base_url}/${panel}/faqs/${faqId}`);
                    modalTitle.textContent = 'Edit FAQ';
                    submitButton.innerHTML = '<i class="ti ti-edit me-1"></i> Update';
                })
                .catch(error => console.error('AJAX Error:', error));
        } else {
            form.reset();
            form.querySelector('textarea[name="question"]').value = '';
            form.querySelector('textarea[name="answer"]').value = '';
            form.querySelector('select[name="status"]').value = 'active';
            form.setAttribute('action', `${base_url}/${panel}/faqs`);
            modalTitle.textContent = 'Add FAQ';
            submitButton.innerHTML = '<i class="ti ti-plus me-1"></i> Create';
        }
    }
    if (event.target.id === 'product-faq-modal') {
        const triggerButton = event.relatedTarget;
        const conditionId = triggerButton ? triggerButton.getAttribute('data-id') : null;
        let url = `${base_url}/${panel}/product-faqs/${conditionId}/edit`;

        const form = document.querySelector('#product-faq-modal .form-submit');
        const modalTitle = document.querySelector('#product-faq-modal .modal-title');
        const submitButton = document.querySelector('#product-faq-modal button[type="submit"]');
        // Prefer modal-specific select to avoid collision with header filter select
        const selectProduct = document.getElementById('select-product-modal') || document.getElementById('select-product');
        let selectProductTom = selectProduct && selectProduct.tomselect ? selectProduct.tomselect : null;

        if (conditionId) {
            // Edit mode: Fetch and populate data
            fetch(url, {method: 'GET'})
                .then(response => response.json())
                .then(async responseData => {
                    const data = responseData.data;

                    // Fill form fields
                    form.querySelector('textarea[name="question"]').value = data.question || '';
                    form.querySelector('textarea[name="answer"]').value = data.answer || '';
                    const productField = form.querySelector('[name="product_id"]');
                    if (productField) productField.value = data.product_id || '';
                    form.querySelector('select[name="status"]').value = data.status || '';

                    if (selectProductTom) {
                        await loadProductAndSetValue(selectProductTom, data.product_id);
                    } else if (selectProduct) {
                        selectProduct.value = data.product_id;
                    }

                    // Change form action to update route
                    form.setAttribute('action', `${base_url}/${panel}/product-faqs/${conditionId}`);

                    // Update modal title and button
                    modalTitle.textContent = 'Edit Product Faq';
                    submitButton.innerHTML = '<i class="ti ti-edit me-1"></i> Update';
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                });
        } else {
            // New condition mode: Reset fields
            if (form) form.reset();
            if (selectProductTom) selectProductTom.clear();
            if (selectProduct) selectProduct.value = '';
            const productIdFromTrigger = triggerButton ? triggerButton.getAttribute('data-product-id') : null;
            form.querySelector('textarea[name="question"]').value = '';
            form.querySelector('textarea[name="answer"]').value = '';
            const productFieldCreate = form.querySelector('[name="product_id"]');
            if (productFieldCreate) productFieldCreate.value = '';
            if (productIdFromTrigger) {
                if (selectProductTom) {
                    // ensure option exists then set
                    try {
                        selectProductTom.addOption && selectProductTom.addOption({value: productIdFromTrigger, text: productIdFromTrigger});
                    } catch (e) {}
                    selectProductTom.setValue(productIdFromTrigger);
                } else if (selectProduct) {
                    selectProduct.value = productIdFromTrigger;
                }
                const hiddenInput = form.querySelector('[name="product_id"]');
                if (hiddenInput) hiddenInput.value = productIdFromTrigger;
            }
            form.querySelector('select[name="status"]').value = 'active';
            // Set action for create
            form.setAttribute('action', `${base_url}/${panel}/product-faqs`);
            modalTitle.textContent = 'Add Product Faq';
            submitButton.innerHTML = '<i class="ti ti-plus me-1"></i> Add';
        }
    }
});
document.addEventListener('click', function (event) {
    // delete soft store
    handleDelete(event, '.delete-product-condition', `/${panel}/product-conditions/`, 'You are about to delete this Product Condition.');
    handleDelete(event, '.delete-product', `/${panel}/products/`, 'You are about to delete this Product.');
    handleDelete(event, '.delete-faq', `/${panel}/faqs/`, 'You are about to delete this FAQ.');
    handleDelete(event, '.delete-product-faq', `/${panel}/product-faqs/`, 'You are about to delete this Product Faq.');
});


async function loadCategoryAndSetValue(tomSelectInstance, categoryId) {
    if (!categoryId) return;

    let parentOption = tomSelectInstance.options[categoryId];
    if (!parentOption) {
        try {
            const res = await fetch(`${base_url}/${panel}/categories/search?find_id=${categoryId}`);
            const json = await res.json();
            // Assuming your endpoint returns an array of categories with id and name
            if (json && json.length) {
                tomSelectInstance.addOption(json[0]);
            }
        } catch (error) {
            console.error(error);
        }
    }
    tomSelectInstance.setValue(categoryId);
}

async function loadProductAndSetValue(tomSelectInstance, productId) {
    if (!productId) return;

    let parentOption = tomSelectInstance.options[productId];
    if (!parentOption) {
        try {
            const res = await fetch(`${base_url}/${panel}/products/search?find_id=${productId}`);
            const json = await res.json();
            // Assuming your endpoint returns an array of categories with id and name
            if (json && json.length) {
                tomSelectInstance.addOption(json[0]);
            }
        } catch (error) {
            console.error(error);
        }
    }
    tomSelectInstance.setValue(productId);
}

document.addEventListener('DOMContentLoaded', function () {
    initHsnCodeSync();

    try {
        const categoriesElement = document.getElementById('categories');
        if (categoriesElement === null) {
            return;
        }
        const categories = JSON.parse(categoriesElement.dataset.categories);
        // Initialize jsTree
        $('#categories-tree').jstree({
            'core': {
                'data': categories, 'themes': {
                    'variant': 'large'
                },
            }, 'checkbox': {
                'keep_selected_style': true
            }, 'plugins': ['wholerow']
        }).on('ready.jstree', function () {
            // Categories ready; you can programmatically select nodes here
            tree = $('#categories-tree').jstree(true);

            // If in edit mode, select the category
            if (window.productData && window.productData.product && window.productData.product.category_id) {
                tree.select_node(window.productData.product.category_id.toString());
            }
        }).on('select_node.jstree', function (_e, data) {
            var selected_node_id = data.node.id;
            $('#selected_category').val(selected_node_id);
        });

    } catch (e) {
        console.error(e)
    }

    const steps = document.querySelectorAll('.wizard-step');
    const tabs = document.querySelectorAll('.nav-segmented .nav-link');
    const totalSteps = steps.length;

    let currentStep = getStepFromURL() || 1;

    function updateWizard() {
        steps.forEach(step => step.classList.add('d-none'));
        tabs.forEach(tab => tab.classList.remove('active'));

        document.querySelector(`.wizard-step[data-step="${currentStep}"]`)?.classList.remove('d-none');
        document.querySelector(`.nav-link[data-step="${currentStep}"]`)?.classList.add('active');

        const nextStepBtn = document.getElementById('nextStep');
        document.getElementById('prevStep') && (document.getElementById('prevStep').disabled = currentStep === 1);
        nextStepBtn.textContent = currentStep === totalSteps ? 'Finish' : 'Next';
        nextStepBtn.type = currentStep === totalSteps ? 'submit' : 'button';

        updateURL(currentStep);

        // Refresh variant labels in the pricing table when navigating to the pricing step
        if (currentStep === totalSteps && typeof refreshVariantPricingLabels === 'function') {
            refreshVariantPricingLabels();
        }
    }

    function getStepFromURL() {
        const params = new URLSearchParams(window.location.search);
        const step = parseInt(params.get('step'));
        return !isNaN(step) && step >= 1 && step <= totalSteps ? step : null;
    }

    function updateURL(step) {
        const params = new URLSearchParams(window.location.search);
        params.set('step', step);
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);
    }

    // Button navigation
    document.getElementById('prevStep')?.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    document.getElementById('nextStep')?.addEventListener('click', (e) => {
        if (currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        } else if (currentStep === totalSteps) {
            // Let the form submit naturally
            return;
        }
        e.preventDefault();
    });

    // Tab (step) navigation
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            currentStep = parseInt(tab.dataset.step);
            updateWizard();
        });
    });

    // Initialize wizard
    updateWizard();
});

// Product FAQ AJAX handlers: submit and delete (updates table without reload)
document.addEventListener('DOMContentLoaded', function () {
    const faqForm = document.getElementById('product-faq-form');
    const faqTbody = document.getElementById('product-faqs-tbody');
    const modalEl = document.getElementById('product-faq-modal');

    // Helper to render a row for a FAQ
    function renderFaqRow(faq) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', faq.id);
        tr.innerHTML = `
            <td>${faq.id}</td>
            <td>${escapeHtml(faq.question)}</td>
            <td>${escapeHtml(faq.answer)}</td>
            <td class="text-capitalize">${escapeHtml(faq.status || '')}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#product-faq-modal" data-id="${faq.id}">Edit</button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-product-faq" data-id="${faq.id}">Delete</button>
            </td>
        `;
        return tr;
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (faqForm) {
        faqForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = faqForm.querySelector('button[type="submit"]');
            const originalText = submitBtn && submitBtn.innerHTML;
            try {
                if (submitBtn) submitBtn.disabled = true;
                if (submitBtn) submitBtn.innerHTML = 'Please wait...';

                const action = faqForm.getAttribute('action');
                const formData = new FormData(faqForm);

                const res = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    const faq = data.data;

                    // Remove any placeholder empty row
                    const emptyRows = faqTbody ? Array.from(faqTbody.querySelectorAll('tr')).filter(r => {
                        const tds = r.querySelectorAll('td');
                        return tds.length === 1 && tds[0].hasAttribute('colspan');
                    }) : [];
                    emptyRows.forEach(r => r.parentNode.removeChild(r));

                    // If row exists, update it, else append
                    const existing = faqTbody ? faqTbody.querySelector(`tr[data-id="${faq.id}"]`) : null;
                    if (existing) {
                        const newRow = renderFaqRow(faq);
                        existing.parentNode.replaceChild(newRow, existing);
                    } else if (faqTbody) {
                        faqTbody.appendChild(renderFaqRow(faq));
                    }

                    // Hide modal
                    if (modalEl) {
                        try {
                            const modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modalObj.hide();
                        } catch (err) {}
                    }

                    Swal.fire('Success', data.message || 'Saved', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Failed', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'There was an error saving the FAQ.', 'error');
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = originalText; }
            }
        });
    }

    // Delegate delete clicks for product-faqs (prevent global handler)
    // Use capture phase so this runs before other bubble-phase handlers.
    document.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.delete-product-faq');
        if (!btn) return;
        // prevent the global handleDelete from running
        ev.stopPropagation();
        ev.preventDefault();

        const id = btn.getAttribute('data-id');
        const explicitUrl = btn.getAttribute('data-url');
        const url = explicitUrl || `${base_url}/${panel}/product-faqs/${id}`;

        Swal.fire({
            title: 'Are you sure?',
            html: 'You are about to delete this Product Faq.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: primaryColor,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    // remove row if present
                    if (faqTbody) {
                        const row = faqTbody.querySelector(`tr[data-id="${id}"]`);
                        if (row) row.parentNode.removeChild(row);

                        // if no rows remain, insert empty placeholder row
                        if (!faqTbody.querySelector('tr')) {
                            const emptyText = faqTbody.getAttribute('data-empty-text') || 'No product FAQs found';
                            const tr = document.createElement('tr');
                            const td = document.createElement('td');
                            td.setAttribute('colspan', '5');
                            td.textContent = emptyText;
                            tr.appendChild(td);
                            faqTbody.appendChild(tr);
                        }
                    }
                    Swal.fire('Deleted!', data.message || 'Deleted', 'success');
                } else {
                    Swal.fire('Error!', data.message || 'Failed to delete', 'error');
                }
            } catch (err) {
                Swal.fire('Error!', 'There was a problem deleting the FAQ.', 'error');
            }
        });
    }, true);
});

function initHsnCodeSync() {
    const mainInput = document.getElementById('hsn-code-main');
    const gstInput = document.getElementById('hsn-code-gst');

    if (!mainInput || !gstInput) {
        return;
    }

    const sync = (source, target) => {
        target.value = source.value || '';
    };

    // Initialize from whichever currently has value.
    if ((mainInput.value || '').trim() !== '') {
        sync(mainInput, gstInput);
    } else if ((gstInput.value || '').trim() !== '') {
        sync(gstInput, mainInput);
    }

    mainInput.addEventListener('input', () => sync(mainInput, gstInput));
    gstInput.addEventListener('input', () => sync(gstInput, mainInput));
}
// document.addEventListener('DOMContentLoaded', function () {
// Database attributes - Replace with actual AJAX call
let dbAttributes;
const attributesElement = document.getElementById('attributes');
if (attributesElement !== null) {
    dbAttributes = JSON.parse(attributesElement.dataset.attributes);
}


let variants = [], removedVariants = [];
let productPricing = null;

// Function to initialize the form in edit mode
function initializeEditMode() {
    if (!window.productData) return;

    // Set product type
    const productTypeSelect = document.getElementById('productType');
    if (productTypeSelect && window.productData.type) {
        productTypeSelect.value = window.productData.type;
        toggleProductVariantSection();
    }

    // Initialize variants if product type is 'variant'
    if (window.productData.type === 'variant' && window.productData.variants) {
        initializeVariantData();

        // Fetch and initialize store pricing
        if (window.productData.product && window.productData.product.id) {
            fetchProductPricing(window.productData.product.id);
        }
    }
    // Initialize simple product fields if product type is 'simple'
    else if (window.productData.type === 'simple' && window.productData.variant) {
        const sv = window.productData.variant;
        variants = [{
            id: String(sv.id),
            attributes: {},
            title: sv.title || '',
            image: sv.image || '',
            availability: sv.availability === null || sv.availability === undefined ? '' : sv.availability,
            is_default: 'on',
            weight: sv.weight ?? '',
            weight_unit: sv.weight_unit || 'g',
            is_indexable: sv.metadata?.is_indexable ?? sv.is_indexable ?? true,
            seo_title: sv.metadata?.seo_title || sv.seo_title || '',
            seo_description: sv.metadata?.seo_description || sv.seo_description || '',
            seo_keywords: sv.metadata?.seo_keywords || sv.seo_keywords || '',
            og_title: sv.metadata?.og_title || sv.og_title || '',
            og_description: sv.metadata?.og_description || sv.og_description || '',
            og_image: normalizeStorageUrl(sv.og_image || sv.metadata?.og_image || ''),
            twitter_title: sv.metadata?.twitter_title || sv.twitter_title || '',
            twitter_description: sv.metadata?.twitter_description || sv.twitter_description || '',
            twitter_card: sv.metadata?.twitter_card || sv.twitter_card || '',
            twitter_image: normalizeStorageUrl(sv.twitter_image || sv.metadata?.twitter_image || ''),
            schema_mode: sv.metadata?.schema_mode || sv.schema_mode || 'auto',
            schema_json_ld: sv.metadata?.schema_json_ld || sv.schema_json_ld || '',
        }];
        renderSimpleVariant();
        // Fetch and initialize store pricing
        if (window.productData.product && window.productData.product.id) {
            fetchProductPricing(window.productData.product.id);
        }
    }
}


// Build variants[] from server data then render all cards (edit mode)
function initializeVariantData() {
    if (!window.productData || !window.productData.variants) return;

    variants = window.productData.variants.map((serverVariant) => {
        const attrs = {};
        (serverVariant.attributes || []).forEach((attr) => {
            attrs[String(attr.global_attribute_id)] = Number(attr.global_attribute_value_id);
        });

        return {
            id: String(serverVariant.id),
            attributes: attrs,
            title: serverVariant.title || '',
            image: serverVariant.image || '',
            availability: serverVariant.availability === null || serverVariant.availability === undefined ? '' : serverVariant.availability,
            is_default: serverVariant.is_default || '',
            weight: serverVariant.weight ?? '',
            weight_unit: serverVariant.weight_unit || 'g',
            is_indexable: serverVariant.metadata?.is_indexable ?? serverVariant.is_indexable ?? true,
            seo_title: serverVariant.metadata?.seo_title || serverVariant.seo_title || '',
            seo_description: serverVariant.metadata?.seo_description || serverVariant.seo_description || '',
            seo_keywords: serverVariant.metadata?.seo_keywords || serverVariant.seo_keywords || '',
            og_title: serverVariant.metadata?.og_title || serverVariant.og_title || '',
            og_description: serverVariant.metadata?.og_description || serverVariant.og_description || '',
            og_image: normalizeStorageUrl(serverVariant.og_image || serverVariant.metadata?.og_image || ''),
            twitter_title: serverVariant.metadata?.twitter_title || serverVariant.twitter_title || '',
            twitter_description: serverVariant.metadata?.twitter_description || serverVariant.twitter_description || '',
            twitter_card: serverVariant.metadata?.twitter_card || serverVariant.twitter_card || '',
            twitter_image: normalizeStorageUrl(serverVariant.twitter_image || serverVariant.metadata?.twitter_image || ''),
            schema_mode: serverVariant.metadata?.schema_mode || serverVariant.schema_mode || 'auto',
            schema_json_ld: serverVariant.metadata?.schema_json_ld || serverVariant.schema_json_ld || '',
        };
    });

    renderVariants();
    document.getElementById('variantsContainer')?.classList.remove('d-none');
    updateVariantPricing();
}

// Initialize edit mode if needed
document.addEventListener('DOMContentLoaded', function () {
    if (window.productData) {
        initializeEditMode();
    }
});

// Event listeners
const productType = document.getElementById('productType');
productType?.addEventListener('change', function () {
    toggleProductVariantSection();
});
toggleProductVariantSection()

function toggleProductVariantSection() {
    let value = productType?.value
    const isVariant = value === 'variant';
    const isSimple = value === 'simple';

    // Update pricing containers based on a product type
    if (value) {
        document.getElementById('variationsSection').classList.toggle('d-none', !isVariant);
        document.getElementById('simpleProductSection').classList.toggle('d-none', !isSimple);
        // Show/hide the appropriate pricing containers
        document.getElementById('simplePricingContainer').classList.toggle('d-none', isVariant);
        document.getElementById('variantPricingContainer').classList.toggle('d-none', !isVariant);

        // When switching to simple: render the single variant form
        if (isSimple) {
            // Only reset variants if switching interactively (not edit mode init)
            if (!window.productData || window.productData.type !== 'simple') {
                variants = [];
            }
            renderSimpleVariant();
        }

        // When switching to variant: clear the simple variant data
        if (isVariant && !window.productData) {
            variants = [];
            const simpleSection = document.getElementById('simpleProductSection');
            if (simpleSection) simpleSection.innerHTML = '';
        }

        // Only initialize pricing if we're not in edit mode or if pricing data is already loaded
        if (!window.productData || productPricing) {
            // Initialize the appropriate pricing container
            if (isVariant) {
                initializeVariantPricing();
            } else {
                initializeSimplePricing();
            }
        }
    } else {
        // Hide all containers if no product type is selected
        document.getElementById('simplePricingContainer')?.classList.add('d-none');
        document.getElementById('variantPricingContainer')?.classList.add('d-none');
    }
}

document.getElementById('addRemovedVariantBtn')?.addEventListener('click', () => showRemovedVariantsModal());
document.getElementById('removeAllVariantsBtn')?.addEventListener('click', () => removeAllVariants());




function generateSKU(attrs) {
    // attrs is an object like { 1: 101, 2: 201 } (attributeId: valueId)
    return 'PRD-' + Object.entries(attrs).map(([attrId, valueId]) => {
        // Find attribute key by ID
        let attrKey = Object.keys(dbAttributes).find(key => dbAttributes[key].id === attrId);
        let attr = dbAttributes[attrKey];
        let value = attr.values.find(val => val.id === valueId);
        // Use the first 2 letters of name or value as fallback
        return (attr?.name?.substring(0, 2).toUpperCase() || attrId) +
            (value?.name?.substring(0, 2).toUpperCase() || valueId);
    }).join('-');
}

const attrIdMap = {};

function renderVariantSeoSection(v) {
    const twitterCard = v.twitter_card || '';
    const schemaMode = v.schema_mode || 'auto';
    const ogImageLink = v.og_image
        ? `<small class="form-hint d-block mt-1">Current OG image: <a href="${v.og_image}" target="_blank">View uploaded image</a></small>`
        : '<small class="form-hint d-block mt-1">Uses the variant image if left blank.</small>';
    const twitterImageLink = v.twitter_image
        ? `<small class="form-hint d-block mt-1">Current Twitter image: <a href="${v.twitter_image}" target="_blank">View uploaded image</a></small>`
        : '<small class="form-hint d-block mt-1">Uses the variant image or OG image if left blank.</small>';

    return `
        <div class="col-12">
            <details>
                <summary class="text-muted small fw-semibold" style="cursor:pointer;user-select:none;">SEO Settings</summary>
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label class="row">
                            <span class="col">Allow search engines to index this variant</span>
                            <span class="col-auto">
                                <label class="form-check form-check-single form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           data-variant-id="${v.id}"
                                           ${v.is_indexable === false ? '' : 'checked'}
                                           onchange="updateVariant('${v.id}', 'is_indexable', this.checked)">
                                </label>
                            </span>
                        </label>
                        <small class="form-hint">Uncheck to add noindex for this variant.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-1">SEO Title <span class="text-muted">(max 255)</span></label>
                        <input type="text" class="form-control variant-seo-title-input" maxlength="255"
                               data-variant-id="${v.id}"
                               placeholder="Variant SEO title"
                               value="${v.seo_title || ''}"
                               oninput="handleVariantSeoTitleInput('${v.id}', this.value)">
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-1">SEO Description <span class="text-muted">(max 500)</span></label>
                        <textarea class="form-control variant-seo-description-input" maxlength="500" rows="3"
                                  data-variant-id="${v.id}"
                                  placeholder="Variant SEO description"
                                  oninput="handleVariantSeoDescriptionInput('${v.id}', this.value)">${v.seo_description || ''}</textarea>
                    </div>
                    <div class="col-12">
                           <label class="form-label mb-1">SEO Keywords <span class="text-muted">(max 1000)</span></label>
                           <input type="text" class="form-control variant-seo-keywords-input" maxlength="1000"
                               data-variant-id="${v.id}"
                               placeholder="keyword1, keyword2"
                               value="${v.seo_keywords || ''}">
                    </div>
                    <div class="col-md-6">
                           <label class="form-label mb-1">OG Title <span class="text-muted">(max 255)</span></label>
                           <input type="text" class="form-control" maxlength="255" value="${v.og_title || ''}"
                               placeholder="Leave blank to use variant SEO title"
                               oninput="updateVariant('${v.id}', 'og_title', this.value)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">OG Image</label>
                        <input type="file" name="variant_og_image${v.id}" class="form-control variant-og-image-input" data-image-url="${v.og_image || ''}" accept="image/*">
                        ${ogImageLink}
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-1">OG Description <span class="text-muted">(max 500)</span></label>
                        <textarea class="form-control" rows="3" maxlength="500"
                                  placeholder="Leave blank to use variant SEO description"
                                  oninput="updateVariant('${v.id}', 'og_description', this.value)">${v.og_description || ''}</textarea>
                    </div>
                    <div class="col-md-6">
                           <label class="form-label mb-1">Twitter Title <span class="text-muted">(max 250)</span></label>
                           <input type="text" class="form-control" maxlength="250" value="${v.twitter_title || ''}"
                               placeholder="Leave blank to use variant SEO title"
                               oninput="updateVariant('${v.id}', 'twitter_title', this.value)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Twitter Card</label>
                        <select class="form-select" onchange="updateVariant('${v.id}', 'twitter_card', this.value)">
                            <option value="" ${twitterCard === '' ? 'selected' : ''}>Use automatic fallback</option>
                            <option value="summary" ${twitterCard === 'summary' ? 'selected' : ''}>Summary</option>
                            <option value="summary_large_image" ${twitterCard === 'summary_large_image' ? 'selected' : ''}>Summary Large Image</option>
                            <option value="app" ${twitterCard === 'app' ? 'selected' : ''}>App</option>
                            <option value="player" ${twitterCard === 'player' ? 'selected' : ''}>Player</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Twitter Image</label>
                        <input type="file" name="variant_twitter_image${v.id}" class="form-control variant-twitter-image-input" data-image-url="${v.twitter_image || ''}" accept="image/*">
                        ${twitterImageLink}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Twitter Description <span class="text-muted">(max 500)</span></label>
                        <textarea class="form-control" rows="3" maxlength="500"
                                  placeholder="Leave blank to use variant SEO description"
                                  oninput="updateVariant('${v.id}', 'twitter_description', this.value)">${v.twitter_description || ''}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Schema Mode</label>
                        <select class="form-select variant-schema-mode-select" data-variant-id="${v.id}"
                                onchange="updateVariantSchemaMode('${v.id}', this.value)">
                            <option value="auto" ${schemaMode === 'auto' ? 'selected' : ''}>Auto-generate</option>
                            <option value="custom" ${schemaMode === 'custom' ? 'selected' : ''}>Custom JSON-LD</option>
                        </select>
                    </div>
                    <div class="col-12 variant-schema-json-ld-wrap" data-variant-id="${v.id}" style="${schemaMode === 'custom' ? '' : 'display:none;'}">
                        <label class="form-label mb-1">Schema JSON-LD</label>
                        <textarea class="form-control" rows="6"
                                  placeholder='{"@@context":"https://schema.org","@@type":"Product"}'
                                  oninput="updateVariant('${v.id}', 'schema_json_ld', this.value)">${v.schema_json_ld || ''}</textarea>
                        <small class="form-hint">Used only when Schema Mode is set to Custom JSON-LD.</small>
                    </div>
                </div>
            </details>
        </div>
    `;
}

function buildVariantAttrPickerHTML(variantId, attrs) {
    // Build one row per existing attribute pair, plus an "Add Attribute" button
    const attrKeys = Object.keys(dbAttributes);
    const rows = Object.entries(attrs).map(([attrId, valueId]) => {
        const matchedKey = attrKeys.find(k => String(dbAttributes[k].id) === String(attrId)) || '';
        const attrOpts = attrKeys.map(k =>
            `<option value="${k}" ${k === matchedKey ? 'selected' : ''}>${dbAttributes[k].name}</option>`
        ).join('');
        const valOpts = matchedKey
            ? dbAttributes[matchedKey].values.map(v =>
                `<option value="${v.id}" ${String(v.id) === String(valueId) ? 'selected' : ''}>${v.name}</option>`
              ).join('')
            : '';
        const rowId = `vattr_${variantId}_${attrId}`;
        return `
            <div class="d-flex gap-2 align-items-center mb-2 variant-attr-row" data-row-id="${rowId}" data-attr-key="${matchedKey}">
                <select class="form-select form-select-sm flex-fill vattr-key-select"
                        onchange="onVariantAttrKeyChange('${variantId}','${rowId}',this.value)">
                    <option value="">— Attribute —</option>
                    ${attrOpts}
                </select>
                <select class="form-select form-select-sm flex-fill vattr-val-select"
                        onchange="syncVariantAttrRows('${variantId}')" ${!matchedKey ? 'disabled' : ''}>
                    <option value="">— Value —</option>
                    ${valOpts}
                </select>
                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 px-2"
                        onclick="removeVariantAttrRow('${variantId}','${rowId}')">
                    <i class="ti ti-minus"></i>
                </button>
            </div>`;
    }).join('');

    return `
        <div class="variant-attrs-section mb-3" data-variant-id="${variantId}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0 fw-semibold">Attributes</label>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="addVariantAttrRow('${variantId}')">
                    <i class="ti ti-plus me-1"></i>Add
                </button>
            </div>
            <div class="variant-attr-rows">${rows}</div>
        </div>`;
}

function ensureAttrIdMap() {
    Object.keys(dbAttributes).forEach(attrKey => {
        const attr = dbAttributes[attrKey];
        attrIdMap[attr.id] = {
            name: attr.name,
            values: Object.fromEntries(attr.values.map(v => [v.id, v.name]))
        };
    });
}

function renderVariants() {
    ensureAttrIdMap();
    document.getElementById('variantsList').innerHTML = variants.map(v =>
        `<div class="col-md-6" data-id="${v.id}">
        <div class="card border h-100">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-2 gap-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" title="Copy Variant" onclick="copyVariant('${v.id}')">
                        <i class="ti ti-copy fs-2"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeVariant('${v.id}')">
                        <i class="ti ti-trash fs-2"></i>
                    </button>
                </div>
                ${buildVariantAttrPickerHTML(v.id, v.attributes)}
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control variant-title-input" min="0" data-variant-id="${v.id}" value="${v.title}" oninput="handleVariantTitleInput('${v.id}', this.value)">
                    </div>
                    <div class="col-12">
                            <label class="form-label">Variant Image</label>
                            <input type="file" name="variant_image${v.id}" class="form-control variant-image-input" data-image-url="${v.image || ''}" accept="image/*" onchange="updateVariant('${v.id}', 'variant_image', this.value)">
                            <small class="form-hint">Recommended: 1200 x 1200 px. Max upload size: 2 MB.</small>
                        </div>
                    <div class="col-6">
                        <label class="form-label">Weight</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="0" step="any"
                                   placeholder="e.g. 500"
                                   value="${v.weight ?? ''}"
                                   onchange="updateVariant('${v.id}', 'weight', this.value)">
                            <select class="form-select" style="max-width:90px;" onchange="updateVariant('${v.id}', 'weight_unit', this.value)">
                                <option value="g"  ${(v.weight_unit||'g')==='g'  ? 'selected' : ''}>g</option>
                                <option value="kg" ${(v.weight_unit||'g')==='kg' ? 'selected' : ''}>kg</option>
                                <option value="mg" ${(v.weight_unit||'g')==='mg' ? 'selected' : ''}>mg</option>
                                <option value="lb" ${(v.weight_unit||'g')==='lb' ? 'selected' : ''}>lb</option>
                                <option value="oz" ${(v.weight_unit||'g')==='oz' ? 'selected' : ''}>oz</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Availability</label>
                        <select class="form-select" onchange="updateVariant('${v.id}', 'availability', this.value)">
                            <option value="" ${v.availability === '' ? 'selected' : ''}>Select</option>
                            <option value="yes" ${v.availability == 1 ? 'selected' : ''}>Yes</option>
                            <option value="no" ${v.availability == 0 ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" name='is_defaults' type="radio" id="flexRadioDefault${v.id}" onchange="setDefaultVariant('${v.id}')" ${isDefaultVariant(v.is_default) ? 'checked' : ''}>
                            <label class="form-check-label" for="flexRadioDefault${v.id}">Set as Default</label>
                        </div>
                    </div>
                    ${renderVariantSeoSection(v)}
                </div>
            </div>
        </div>
    </div>
`,
    ).join('');
    // Initialize FilePond for all variant image inputs
    document.querySelectorAll('.variant-image-input').forEach(input => {
        const inputName = input.getAttribute('name');
        initializeFilePond(inputName, ['image/*'], '2MB');
    });
    document.querySelectorAll('.variant-og-image-input').forEach(input => {
        const inputName = input.getAttribute('name');
        initializeFilePond(inputName, ['image/*'], '4MB');
    });
    document.querySelectorAll('.variant-twitter-image-input').forEach(input => {
        const inputName = input.getAttribute('name');
        initializeFilePond(inputName, ['image/*'], '4MB');
    });
    hydrateVariantSeoFields();
    initializeVariantSeoKeywordInputs();
}

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
        server: {
            load: (source, load, error, _progress, _abort) => {
                fetch(source)
                    .then(response => response.blob())
                    .then(blob => load(blob))
                    .catch(err => error(err));
                return { abort: () => {} };
            }
        },
        files: imageUrl ? [{
            source: imageUrl,
            options: {type: 'local'}
        }] : []
    });
}

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
    } catch (_e) {
        return url;
    }
}

function normalizeStorageUrl(path) {
    if (!path || typeof path !== 'string') return '';

    if (/^https?:\/\//i.test(path)) {
        return normalizeLocalhostOrigin(path);
    }

    return normalizeLocalhostOrigin(`${window.location.origin}/storage/${String(path).replace(/^\/+/, '')}`);
}

function getMainProductTitle() {
    const titleInput = document.querySelector('input[name="title"]');
    return (titleInput?.value || '').trim();
}

function normalizeSeoText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
}

function getMainProductSourceDescription() {
    const shortDescriptionInput = document.querySelector('textarea[name="short_description"]');
    const descriptionInput = document.querySelector('textarea[name="description"]');

    return normalizeSeoText(shortDescriptionInput?.value || descriptionInput?.value || '');
}

function generatedMainSeoTitle(title) {
    return normalizeSeoText(title);
}

function generatedMainSeoDescription() {
    return getMainProductSourceDescription();
}

function getSeoFieldState(input) {
    return {
        manual: input?.dataset.seoManual === '1',
        lastAutoValue: normalizeSeoText(input?.dataset.lastAutoValue || ''),
    };
}

function setSeoFieldAutoValue(input, value) {
    if (!input) return;

    const normalizedValue = normalizeSeoText(value);
    input.value = normalizedValue;
    input.dataset.lastAutoValue = normalizedValue;
    input.dataset.seoManual = '0';
    input.dispatchEvent(new Event('input', {bubbles: true}));
}

function syncMainSeoFields(options = {}) {
    const seoTitleInput = document.getElementById('seoTitle');
    const seoDescriptionInput = document.getElementById('seoDescription');
    if (!seoTitleInput || !seoDescriptionInput) return;

    const syncTitle = options.title !== false;
    const syncDescription = options.description !== false;

    if (syncTitle) {
        const nextSeoTitle = generatedMainSeoTitle(getMainProductTitle());
        const currentSeoTitle = normalizeSeoText(seoTitleInput.value);
        const titleState = getSeoFieldState(seoTitleInput);

        if (!titleState.manual || currentSeoTitle === '' || currentSeoTitle === titleState.lastAutoValue) {
            setSeoFieldAutoValue(seoTitleInput, nextSeoTitle);
        }
    }

    if (syncDescription) {
        const nextSeoDescription = generatedMainSeoDescription();
        const currentSeoDescription = normalizeSeoText(seoDescriptionInput.value);
        const descriptionState = getSeoFieldState(seoDescriptionInput);

        if (!descriptionState.manual || currentSeoDescription === '' || currentSeoDescription === descriptionState.lastAutoValue) {
            setSeoFieldAutoValue(seoDescriptionInput, nextSeoDescription);
        }
    }
}

function initializeMainSeoAutofill() {
    const titleInput = document.querySelector('input[name="title"]');
    const shortDescriptionInput = document.querySelector('textarea[name="short_description"]');
    const descriptionInput = document.querySelector('textarea[name="description"]');
    const seoTitleInput = document.getElementById('seoTitle');
    const seoDescriptionInput = document.getElementById('seoDescription');

    titleInput?.addEventListener('input', function () {
        syncMainSeoFields({title: true, description: false});
    });

    const handleDescriptionSync = function () {
        syncMainSeoFields({title: false, description: true});
        hydrateVariantSeoFields();
    };

    seoTitleInput?.addEventListener('input', function () {
        const state = getSeoFieldState(this);
        const currentValue = normalizeSeoText(this.value);
        this.dataset.seoManual = currentValue !== '' && currentValue !== state.lastAutoValue ? '1' : '0';
    });

    seoDescriptionInput?.addEventListener('input', function () {
        const state = getSeoFieldState(this);
        const currentValue = normalizeSeoText(this.value);
        this.dataset.seoManual = currentValue !== '' && currentValue !== state.lastAutoValue ? '1' : '0';
    });

    shortDescriptionInput?.addEventListener('input', handleDescriptionSync);
    descriptionInput?.addEventListener('input', handleDescriptionSync);

    syncMainSeoFields();
}

function getVariantSourceDescription() {
    const shortDescriptionInput = document.querySelector('textarea[name="short_description"]');
    const descriptionInput = document.querySelector('textarea[name="description"]');

    return normalizeSeoText(shortDescriptionInput?.value || descriptionInput?.value || '');
}

function generatedVariantSeoTitle(title) {
    return normalizeSeoText(title);
}

function generatedVariantSeoDescription() {
    return getVariantSourceDescription();
}

function isVariantSeoTitleManual(variant) {
    const currentValue = normalizeSeoText(variant?.seo_title || '');
    return currentValue !== '' && currentValue !== generatedVariantSeoTitle(variant?.title || '');
}

function isVariantSeoDescriptionManual(variant) {
    const currentValue = normalizeSeoText(variant?.seo_description || '');
    return currentValue !== '' && currentValue !== generatedVariantSeoDescription();
}

function setVariantSeoFieldInputValue(selector, variantId, value) {
    const input = document.querySelector(`${selector}[data-variant-id="${variantId}"]`);
    if (!input) return;
    input.value = value;
}

function syncVariantSeoFields(variantId, options = {}) {
    const variant = variants.find(v => v.id === variantId);
    if (!variant) return;

    const syncTitle = options.title !== false;
    const syncDescription = options.description !== false;

    if (syncTitle && !isVariantSeoTitleManual(variant)) {
        const nextSeoTitle = generatedVariantSeoTitle(variant.title);
        variant.seo_title = nextSeoTitle;
        setVariantSeoFieldInputValue('.variant-seo-title-input', variantId, nextSeoTitle);
    }

    if (syncDescription && !isVariantSeoDescriptionManual(variant)) {
        const nextSeoDescription = generatedVariantSeoDescription();
        variant.seo_description = nextSeoDescription;
        setVariantSeoFieldInputValue('.variant-seo-description-input', variantId, nextSeoDescription);
    }
}

function hydrateVariantSeoFields() {
    variants.forEach(variant => {
        syncVariantSeoFields(variant.id);
    });
}

function handleVariantTitleInput(id, value) {
    const variant = variants.find(v => v.id === id);
    if (!variant) return;

    const previousGeneratedSeoTitle = generatedVariantSeoTitle(variant.title || '');
    const currentSeoTitle = normalizeSeoText(variant.seo_title || '');

    updateVariant(id, 'title', value);

    if (currentSeoTitle === '' || currentSeoTitle === previousGeneratedSeoTitle) {
        const nextSeoTitle = generatedVariantSeoTitle(value);
        variant.seo_title = nextSeoTitle;
        setVariantSeoFieldInputValue('.variant-seo-title-input', id, nextSeoTitle);
    }
}

function handleVariantSeoTitleInput(id, value) {
    updateVariant(id, 'seo_title', value);
}

function handleVariantSeoDescriptionInput(id, value) {
    updateVariant(id, 'seo_description', value);
}

function updateVariantSchemaMode(id, value) {
    updateVariant(id, 'schema_mode', value);
    const wrap = document.querySelector(`.variant-schema-json-ld-wrap[data-variant-id="${id}"]`);
    if (wrap) {
        wrap.style.display = value === 'custom' ? '' : 'none';
    }
}

function normalizeSeoKeywordList(value) {
    const seen = new Set();

    return String(value || '')
        .split(',')
        .map(keyword => keyword.replace(/\s+/g, ' ').trim())
        .filter(keyword => {
            if (!keyword) {
                return false;
            }

            const normalizedKeyword = keyword.toLowerCase();
            if (seen.has(normalizedKeyword)) {
                return false;
            }

            seen.add(normalizedKeyword);
            return true;
        });
}

function setTomSelectKeywordValues(input, value) {
    if (!input) return;

    const keywords = normalizeSeoKeywordList(value);
    const control = input.tomselect;

    if (!control) {
        input.value = keywords.join(', ');
        return;
    }

    control.clear(true);
    control.clearOptions();
    keywords.forEach(keyword => {
        control.addOption({value: keyword, text: keyword});
    });
    control.setValue(keywords, true);
    input.value = keywords.join(', ');
}

function buildKeywordTomSelect(input, onSync) {
    if (!input || !window.TomSelect || input.tomselect) {
        return;
    }

    new TomSelect(input, {
        create: (raw) => {
            const keyword = String(raw || '').replace(/\s+/g, ' ').trim();
            return keyword ? {value: keyword, text: keyword} : false;
        },
        createOnBlur: true,
        persist: false,
        delimiter: ',',
        hideSelected: true,
        duplicates: false,
        maxOptions: 100,
        onChange: onSync,
        onBlur: onSync,
    });
}

function syncMainSeoKeywordsValue() {
    const input = document.getElementById('main-seo-keywords-input');
    const valueInput = document.getElementById('main-seo-keywords-value');
    if (!input || !valueInput) return;

    const control = input.tomselect;
    const keywords = normalizeSeoKeywordList(control ? control.items.join(',') : input.value);
    valueInput.value = keywords.join(', ');
}

function initializeMainSeoKeywordsInput() {
    const input = document.getElementById('main-seo-keywords-input');
    const valueInput = document.getElementById('main-seo-keywords-value');
    if (!input || !valueInput) return;

    buildKeywordTomSelect(input, syncMainSeoKeywordsValue);
    setTomSelectKeywordValues(input, valueInput.value || input.value);
    syncMainSeoKeywordsValue();
}

function syncVariantSeoKeywordsValue(variantId, input) {
    if (!input) return;

    const control = input.tomselect;
    const keywords = normalizeSeoKeywordList(control ? control.items.join(',') : input.value);
    const value = keywords.join(', ');
    input.value = value;
    updateVariant(variantId, 'seo_keywords', value);
}

function initializeVariantSeoKeywordInputs() {
    document.querySelectorAll('.variant-seo-keywords-input').forEach(input => {
        const variantId = input.getAttribute('data-variant-id');
        if (!variantId) return;

        buildKeywordTomSelect(input, () => syncVariantSeoKeywordsValue(variantId, input));
        setTomSelectKeywordValues(input, input.value);
        syncVariantSeoKeywordsValue(variantId, input);
    });
}

function flushTomSelectPendingKeyword(input, onSync) {
    if (!input) return;

    const control = input.tomselect;
    if (!control) {
        onSync?.();
        return;
    }

    const pendingKeyword = String(control.control_input?.value || '').replace(/\s+/g, ' ').trim();
    if (pendingKeyword) {
        control.addItem(pendingKeyword);
        control.control_input.value = '';
    }

    onSync?.();
}

function isDefaultVariant(value) {
    return value === true || value === 1 || value === '1' || value === 'on';
}

function setDefaultVariant(variantId) {
    variants.forEach(v => {
        v.is_default = v.id === variantId;
    });
}

function updateVariant(id, field, value) {
    const variant = variants.find(v => v.id === id);
    if (variant) variant[field] = value;
}

function removeVariant(id) {
    const index = variants.findIndex(v => v.id === id);
    if (index > -1) {
        removedVariants.push(variants.splice(index, 1)[0]);
        document.querySelector(`div[data-id="${id}"]`).remove();
        document.getElementById('addRemovedVariantBtn').disabled = false;
        updateVariantPricing();
    }
}

function copyVariant(id) {
    // Force-sync current DOM attribute rows into the variants array before reading
    syncVariantAttrRowsById(id);

    const source = variants.find(v => String(v.id) === String(id));
    if (!source) return;

    const newId = `v_copy_${Date.now()}`;
    const copy = Object.assign({}, source, {
        id: newId,
        is_default: false
    });
    copy.attributes = JSON.parse(JSON.stringify(source.attributes || {}));

    const sourceIndex = variants.findIndex(v => String(v.id) === String(id));
    variants.splice(sourceIndex + 1, 0, copy);

    renderVariants();
    updateVariantPricing();
}

// Like syncVariantAttrRows but accepts both string and numeric IDs safely
function syncVariantAttrRowsById(variantId) {
    const section = document.querySelector(`.variant-attrs-section[data-variant-id="${variantId}"]`);
    if (!section) return;
    const attrs = {};
    section.querySelectorAll('.variant-attr-row').forEach(row => {
        const attrKey = row.querySelector('.vattr-key-select')?.value;
        const valueId = row.querySelector('.vattr-val-select')?.value;
        if (attrKey && valueId && dbAttributes[attrKey]) {
            attrs[dbAttributes[attrKey].id] = parseInt(valueId);
        }
    });
    const variant = variants.find(v => String(v.id) === String(variantId));
    if (variant) variant.attributes = attrs;
}

function updateAttributeOptions() {
    // Get all currently selected attributes
    const selectedAttributes = Array.from(document.querySelectorAll('.attr-select'))
        .map(select => select.value)
        .filter(value => value);

    // Update all attribute selects to disable already selected options
    document.querySelectorAll('.attr-select').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = `
                    <option value="">Select Attribute</option>
                    ${Object.keys(dbAttributes).map(attr => {
            const isDisabled = selectedAttributes.includes(attr) && attr !== currentValue;
            return `<option value="${attr}" ${isDisabled ? 'disabled' : ''} ${attr === currentValue ? 'selected' : ''}>${dbAttributes[attr].name}</option>`;
        }).join('')}
                `;
    });
}

function removeAllVariants() {
    Swal.fire({
        title: "Are you sure?",
        html: 'You are about to remove all variants. You can add them back from the removed variants section.',
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Remove All!"
    }).then((result) => {
        if (result.isConfirmed) {
            removedVariants.push(...variants);
            variants = [];
            document.getElementById('variantsList').innerHTML = '';
            document.getElementById('addRemovedVariantBtn').disabled = false;

            // Update store pricing UI for variants
            updateVariantPricing();
        }
    });
}

function showRemovedVariantsModal() {
    console.log(removedVariants);
    document.getElementById('removedVariantsList').innerHTML = removedVariants.map(v => `
    <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2">
      <div>
        <strong>
          ${Object.entries(v.attributes).map(([attrId, valueId]) => {
        const attr = attrIdMap[attrId];
        const attrName = attr ? attr.name : attrId;
        const valueName = attr && attr.values[valueId] ? attr.values[valueId] : valueId;
        return `${attrName}: ${valueName}`;
    }).join(', ')}
        </strong><br>
      </div>
      <button type="button" class="btn btn-success btn-sm" onclick="restoreVariant('${v.id}')">
        <i class="fas fa-plus me-1"></i>Add Back
      </button>
    </div>
    `).join('');

    const modalEl = document.getElementById('addRemovedVariantModal');

    if (removedVariants.length === 0) {
        $('#addRemovedVariantModal').modal('hide')
    } else if (!modalEl.classList.contains('show')) {
        $('#addRemovedVariantModal').modal('show')
    }
}

function restoreVariant(id) {
    const index = removedVariants.findIndex(v => v.id === id);
    if (index > -1) {
        variants.push(removedVariants.splice(index, 1)[0]);
        renderVariants();
        document.getElementById('addRemovedVariantBtn').disabled = !removedVariants.length;

        // Update store pricing UI for variants
        updateVariantPricing();
        showRemovedVariantsModal(); // Refresh modal
    }
}

function addCustomVariant() {
    const id = `v_custom_${Date.now()}`;
    const variant = {
        id,
        attributes: {},
        title: '',
        availability: '',
        is_default: '',
        weight: '',
        weight_unit: 'g',
        is_indexable: true,
        seo_title: '', seo_description: '', seo_keywords: '',
        og_title: '', og_description: '', og_image: '',
        twitter_title: '', twitter_description: '', twitter_card: '', twitter_image: '',
        schema_mode: 'auto', schema_json_ld: '',
    };
    variants.push(variant);

    const html = `<div class="col-md-6" data-id="${id}">
        <div class="card border h-100">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-2 gap-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm p-1" title="Copy Variant" onclick="copyVariant('${id}')">
                        <i class="ti ti-copy fs-2"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeVariant('${id}')">
                        <i class="ti ti-trash fs-2"></i>
                    </button>
                </div>
                ${buildVariantAttrPickerHTML(id, {})}
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control variant-title-input" data-variant-id="${id}" placeholder="e.g. Red 500ml" value="" oninput="handleVariantTitleInput('${id}', this.value)">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Variant Image</label>
                        <input type="file" name="variant_image${id}" class="form-control variant-image-input" data-image-url="" accept="image/*" onchange="updateVariant('${id}', 'variant_image', this.value)">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Weight</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="0" step="any"
                                   placeholder="e.g. 500"
                                   value=""
                                   onchange="updateVariant('${id}', 'weight', this.value)">
                            <select class="form-select" style="max-width:90px;" onchange="updateVariant('${id}', 'weight_unit', this.value)">
                                <option value="g"  selected>g</option>
                                <option value="kg">kg</option>
                                <option value="mg">mg</option>
                                <option value="lb">lb</option>
                                <option value="oz">oz</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Availability</label>
                        <select class="form-select" onchange="updateVariant('${id}', 'availability', this.value)">
                            <option value="">Select</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" name="is_defaults" type="radio" id="flexRadioDefault${id}" onchange="setDefaultVariant('${id}')">
                            <label class="form-check-label" for="flexRadioDefault${id}">Set as Default</label>
                        </div>
                    </div>
                    ${renderVariantSeoSection(variant)}
                </div>
            </div>
        </div>
    </div>`;

    document.getElementById('variantsList').insertAdjacentHTML('beforeend', html);
    document.getElementById('variantsContainer').classList.remove('d-none');
    initializeFilePond(`variant_image${id}`, ['image/*'], '2MB');
    initializeVariantSeoKeywordInputs();
    updateVariantPricing();
}

function renderSimpleVariant() {
    const container = document.getElementById('simpleProductSection');
    if (!container) return;

    // Create one variant if none exists yet (create mode)
    if (variants.length === 0) {
        variants.push({
            id: 'v_simple',
            attributes: {},
            title: '',
            image: '',
            availability: '',
            is_default: 'on',
            weight: '',
            weight_unit: 'g',
            is_indexable: true,
            seo_title: '', seo_description: '', seo_keywords: '',
            og_title: '', og_description: '', og_image: '',
            twitter_title: '', twitter_description: '', twitter_card: '', twitter_image: '',
            schema_mode: 'auto', schema_json_ld: '',
        });
    }

    const v = variants[0];
    container.innerHTML = `
        <div class="card border">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Weight</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="0" step="any"
                                   placeholder="e.g. 500"
                                   value="${v.weight ?? ''}"
                                   onchange="updateVariant('${v.id}', 'weight', this.value)">
                            <select class="form-select" style="max-width:90px;" onchange="updateVariant('${v.id}', 'weight_unit', this.value)">
                                <option value="g"  ${(v.weight_unit||'g')==='g'  ? 'selected' : ''}>g</option>
                                <option value="kg" ${(v.weight_unit||'g')==='kg' ? 'selected' : ''}>kg</option>
                                <option value="mg" ${(v.weight_unit||'g')==='mg' ? 'selected' : ''}>mg</option>
                                <option value="lb" ${(v.weight_unit||'g')==='lb' ? 'selected' : ''}>lb</option>
                                <option value="oz" ${(v.weight_unit||'g')==='oz' ? 'selected' : ''}>oz</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Availability</label>
                        <select class="form-select" onchange="updateVariant('${v.id}', 'availability', this.value)">
                            <option value="" ${v.availability === '' ? 'selected' : ''}>Select</option>
                            <option value="yes" ${v.availability == 1 || v.availability === 'yes' ? 'selected' : ''}>Yes</option>
                            <option value="no" ${v.availability == 0 && v.availability !== '' || v.availability === 'no' ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                    ${renderVariantSeoSection(v)}
                </div>
            </div>
        </div>
    `;

    // Initialize FilePond for SEO image inputs
    container.querySelectorAll('.variant-og-image-input').forEach(input => {
        initializeFilePond(input.getAttribute('name'), ['image/*'], '4MB');
    });
    container.querySelectorAll('.variant-twitter-image-input').forEach(input => {
        initializeFilePond(input.getAttribute('name'), ['image/*'], '4MB');
    });
    hydrateVariantSeoFields();
    initializeVariantSeoKeywordInputs();
}

// ── Per-variant attribute picker helpers ─────────────────────────────────────

let variantAttrRowCounter = 0;

function addVariantAttrRow(variantId) {
    const section = document.querySelector(`.variant-attrs-section[data-variant-id="${variantId}"]`);
    if (!section) return;
    const rowId = `vattr_${variantId}_new_${++variantAttrRowCounter}`;
    const attrOpts = Object.keys(dbAttributes)
        .map(k => `<option value="${k}">${dbAttributes[k].name}</option>`)
        .join('');
    const rowHtml = `
        <div class="d-flex gap-2 align-items-center mb-2 variant-attr-row" data-row-id="${rowId}" data-attr-key="">
            <select class="form-select form-select-sm flex-fill vattr-key-select"
                    onchange="onVariantAttrKeyChange('${variantId}','${rowId}',this.value)">
                <option value="">— Attribute —</option>
                ${attrOpts}
            </select>
            <select class="form-select form-select-sm flex-fill vattr-val-select" disabled
                    onchange="syncVariantAttrRows('${variantId}')">
                <option value="">— Value —</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 px-2"
                    onclick="removeVariantAttrRow('${variantId}','${rowId}')">
                <i class="ti ti-minus"></i>
            </button>
        </div>`;
    section.querySelector('.variant-attr-rows').insertAdjacentHTML('beforeend', rowHtml);
}

function removeVariantAttrRow(variantId, rowId) {
    const row = document.querySelector(`.variant-attr-row[data-row-id="${rowId}"]`);
    if (row) row.remove();
    syncVariantAttrRows(variantId);
}

function onVariantAttrKeyChange(variantId, rowId, newAttrKey) {
    const row = document.querySelector(`.variant-attr-row[data-row-id="${rowId}"]`);
    if (!row) return;
    row.dataset.attrKey = newAttrKey;
    const valSelect = row.querySelector('.vattr-val-select');
    if (!newAttrKey || !dbAttributes[newAttrKey]) {
        valSelect.innerHTML = '<option value="">— Value —</option>';
        valSelect.disabled = true;
    } else {
        valSelect.innerHTML = '<option value="">— Value —</option>' +
            dbAttributes[newAttrKey].values.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
        valSelect.disabled = false;
    }
    syncVariantAttrRows(variantId);
}

function syncVariantAttrRows(variantId) {
    const section = document.querySelector(`.variant-attrs-section[data-variant-id="${variantId}"]`);
    if (!section) return;
    const attrs = {};
    section.querySelectorAll('.variant-attr-row').forEach(row => {
        const attrKey = row.querySelector('.vattr-key-select')?.value;
        const valueId = row.querySelector('.vattr-val-select')?.value;
        if (attrKey && valueId && dbAttributes[attrKey]) {
            attrs[dbAttributes[attrKey].id] = parseInt(valueId);
        }
    });
    const variant = variants.find(v => v.id === variantId);
    if (variant) variant.attributes = attrs;
}

function loadValues(id, attrName) {
    const select = document.querySelector(`[data-values="${id}"]`);
    if (!attrName) {
        select.innerHTML = '<option disabled>Select attribute first</option>';
        updateGenerateButton();
        updateAttributeOptions();
        return;
    }

    select.innerHTML = dbAttributes[attrName].values.map(val => `<option value="${val.id}">${val.name}</option>`).join('');
    select.onchange = updateGenerateButton;
    updateGenerateButton();
    updateAttributeOptions();
    // Initialize TomSelect only if not already initialized
    if (!select.tomselect) {
        new TomSelect(select, {
            create: false
        });
    } else {
        // If already initialized, refresh options
        select.tomselect.clearOptions();
        dbAttributes[attrName].values.forEach(val => {
            select.tomselect.addOption({value: val.id, text: val.name});
        });
        select.tomselect.refreshOptions(false);
    }
}

// Store pricing functions
let stores = [];

function toNumber(value) {
    const num = parseFloat(value);
    return Number.isFinite(num) ? num : 0;
}

function normalizeStateCode(value) {
    if (value === null || value === undefined) return '';
    return String(value).trim().toUpperCase();
}

function normalizeGstCode(value) {
    if (value === null || value === undefined) return '';
    const raw = String(value).trim();
    if (raw === '') return '';
    if (/^\d+$/.test(raw)) {
        return raw.padStart(2, '0');
    }
    return raw.toUpperCase();
}

function getSelectedCustomerLocation() {
    const stateSelect = document.getElementById('customer-state-code');
    if (!stateSelect) {
        return {gstCode: '', stateCode: '', stateName: ''};
    }

    const selectedOption = stateSelect.options[stateSelect.selectedIndex];
    const gstCode = normalizeGstCode(stateSelect.value || '');
    const stateCode = normalizeStateCode(selectedOption?.dataset?.stateCode || '');
    const stateName = selectedOption?.dataset?.stateName || '';

    return {gstCode, stateCode, stateName};
}

function ensureDefaultCustomerStateFromStores(storeRows) {
    const stateSelect = document.getElementById('customer-state-code');
    if (!stateSelect) return;
    if (stateSelect.value) return;
    if (!Array.isArray(storeRows) || storeRows.length === 0) return;

    const firstStore = storeRows.find(s => (s && (s.gst_code || s.state_code)));
    if (!firstStore) return;

    const gstCode = normalizeGstCode(firstStore.gst_code || '');
    const stateCode = normalizeStateCode(firstStore.state_code || '');

    let targetOption = null;
    if (gstCode) {
        targetOption = Array.from(stateSelect.options).find(opt => normalizeGstCode(opt.value) === gstCode) || null;
    }
    if (!targetOption && stateCode) {
        targetOption = Array.from(stateSelect.options).find(opt => normalizeStateCode(opt.dataset.stateCode || '') === stateCode) || null;
    }

    if (targetOption) {
        stateSelect.value = targetOption.value;
    }
}

function getSelectedGstRatePercent() {
    const validSlabs = [0, 5, 12, 18, 28];
    const gstOverride = document.querySelector('select[name="gst_rate"]');
    const overrideRate = parseInt(gstOverride?.value || '', 10);
    if (Number.isInteger(overrideRate) && validSlabs.includes(overrideRate)) {
        return overrideRate;
    }

    // Fallback to explicit data-gst-rate on selected tax-group option.
    const taxGroupSelect = document.getElementById('select-tax-group');
    const selectedOption = taxGroupSelect?.options?.[taxGroupSelect.selectedIndex] || null;
    const optionRate = parseInt(selectedOption?.dataset?.gstRate || '', 10);
    if (Number.isInteger(optionRate) && validSlabs.includes(optionRate)) {
        return optionRate;
    }

    // Fallback to precomputed tax-group slab map from Blade.
    const selectedTaxGroupId = taxGroupSelect?.value || '';
    if (selectedTaxGroupId && window._taxClassRateMap) {
        const mappedRate = parseInt(window._taxClassRateMap[selectedTaxGroupId], 10);
        if (Number.isInteger(mappedRate) && validSlabs.includes(mappedRate)) {
            return mappedRate;
        }
    }

    // Fallback to current product saved GST override (edit mode).
    const productRate = parseInt(window._productGstRate ?? window.productData?.product?.gst_rate ?? '', 10);
    if (Number.isInteger(productRate) && validSlabs.includes(productRate)) {
        return productRate;
    }

    // Last fallback to rate mentioned in selected tax-group title (if present).
    const selectedText = selectedOption?.text || '';
    const matchedPct = selectedText.match(/(\d{1,2})(?:\.\d+)?\s*%/);
    if (matchedPct) {
        const parsed = parseInt(matchedPct[1], 10);
        if (Number.isInteger(parsed) && validSlabs.includes(parsed)) {
            return parsed;
        }
    }

    // Always return a slab for preview computation.
    return 0;
}

function ensureGstSlabPreselected() {
    const gstOverride = document.querySelector('select[name="gst_rate"]');
    if (!gstOverride) return;

    // If already selected, keep current value.
    if (gstOverride.value !== null && String(gstOverride.value).trim() !== '') {
        return;
    }

    const validSlabs = ['0', '5', '12', '18', '28'];
    const productRateRaw = window._productGstRate ?? window.productData?.product?.gst_rate ?? '';
    const productRate = String(productRateRaw ?? '').trim();

    if (validSlabs.includes(productRate)) {
        gstOverride.value = productRate;
        return;
    }

    // Fallback to selected tax-group slab map.
    const taxGroupSelect = document.getElementById('select-tax-group');
    const selectedTaxGroupId = taxGroupSelect?.value || '';
    if (selectedTaxGroupId && window._taxClassRateMap) {
        const mappedRate = String(window._taxClassRateMap[selectedTaxGroupId] ?? '').trim();
        if (validSlabs.includes(mappedRate)) {
            gstOverride.value = mappedRate;
        }
    }
}

function determineSupplyType(storeStateCode, storeGstCode, customerStateCode, customerGstCode) {
    if (storeGstCode && customerGstCode) {
        return storeGstCode === customerGstCode ? 'intra' : 'inter';
    }
    if (storeStateCode && customerStateCode) {
        return storeStateCode === customerStateCode ? 'intra' : 'inter';
    }
    return 'intra';
}

function calculateVariantGstFromPrice(price, gstRate, supplyType) {
    const taxableAmount = toNumber(price);
    const validRate = Number.isInteger(gstRate) ? gstRate : 0;

    let cgstAmount = 0;
    let sgstAmount = 0;
    let igstAmount = 0;

    if (supplyType === 'inter') {
        igstAmount = taxableAmount * (validRate / 100);
    } else {
        const halfRate = validRate / 2;
        cgstAmount = taxableAmount * (halfRate / 100);
        sgstAmount = taxableAmount * (halfRate / 100);
    }

    const totalTax = cgstAmount + sgstAmount + igstAmount;
    const totalCost = taxableAmount + totalTax;

    return {
        taxableAmount,
        cgstAmount,
        sgstAmount,
        igstAmount,
        totalTax,
        totalCost
    };
}

function formatAmount(value) {
    return `${currencySymbol}${toNumber(value).toFixed(2)}`;
}

function setGstCellText(row, selector, value) {
    const cell = row.querySelector(selector);
    if (cell) {
        cell.textContent = value;
    }
}

function recalculateGstRow(row) {
    if (!row) return;

    const priceInput = row.querySelector('.store-price');
    const gstRate = getSelectedGstRatePercent();
    const customerLocation = getSelectedCustomerLocation();
    const storeStateCode = normalizeStateCode(row.dataset.storeStateCode || '');
    const storeGstCode = normalizeGstCode(row.dataset.storeGstCode || '');

    const supplyType = determineSupplyType(
        storeStateCode,
        storeGstCode,
        customerLocation.stateCode,
        customerLocation.gstCode
    );

    const supplyBadge = row.querySelector('.gst-supply-type');
    if (supplyBadge) {
        const resolvedText = supplyType === 'inter' ? 'IGST (Inter)' : 'CGST+SGST (Intra)';
        const isFallback = (!storeStateCode && !storeGstCode) || (!customerLocation.stateCode && !customerLocation.gstCode);
        supplyBadge.textContent = isFallback ? `${resolvedText} - default` : resolvedText;
        supplyBadge.className = `badge ${supplyType === 'inter' ? 'text-bg-warning' : 'text-bg-success'} gst-supply-type`;
    }

    const hasPrice = priceInput && priceInput.value !== '';
    if (!hasPrice) {
        setGstCellText(row, '.gst-cgst-amount', '-');
        setGstCellText(row, '.gst-sgst-amount', '-');
        setGstCellText(row, '.gst-igst-amount', '-');
        setGstCellText(row, '.gst-tax-amount', '-');
        setGstCellText(row, '.gst-total-cost', '-');
        return;
    }

    const computed = calculateVariantGstFromPrice(priceInput.value, gstRate, supplyType);
    setGstCellText(row, '.gst-cgst-amount', formatAmount(computed.cgstAmount));
    setGstCellText(row, '.gst-sgst-amount', formatAmount(computed.sgstAmount));
    setGstCellText(row, '.gst-igst-amount', formatAmount(computed.igstAmount));
    setGstCellText(row, '.gst-tax-amount', formatAmount(computed.totalTax));
    setGstCellText(row, '.gst-total-cost', formatAmount(computed.totalCost));
}

function recalculateVariantGstRow(row) {
    recalculateGstRow(row);
}

function recalculateSimpleGstRow(row) {
    recalculateGstRow(row);
}

function recalculateAllVariantGstRows() {
    document.querySelectorAll('#storePricingAccordion .variant-pricing-row').forEach(row => {
        recalculateVariantGstRow(row);
    });
}

function recalculateAllSimpleGstRows() {
    document.querySelectorAll('#simplePricingAccordion .simple-pricing-row').forEach(row => {
        recalculateSimpleGstRow(row);
    });
}

function bindVariantGstPreviewEvents() {
    const pricingContainer = document.getElementById('storePricingAccordion');
    if (pricingContainer && !pricingContainer.dataset.gstEventsBound) {
        pricingContainer.addEventListener('input', function (event) {
            const target = event.target;
            if (!target) return;
            const row = target.closest('.variant-pricing-row');
            if (target.classList.contains('store-price')) {
                handlePriceChangeForDiscount(row);
                recalculateVariantGstRow(row);
                recalculateVisiblePanIndiaTables();
            } else if (target.classList.contains('store-disc-pct')) {
                handleDiscPctChange(row);
            } else if (target.classList.contains('store-special-price')) {
                handleSpecialPriceChange(row);
            }
        });
        pricingContainer.dataset.gstEventsBound = '1';
    }

    const gstRateSelect = document.querySelector('select[name="gst_rate"]');
    if (gstRateSelect && !gstRateSelect.dataset.gstEventsBound) {
        gstRateSelect.addEventListener('change', () => {
            recalculateAllVariantGstRows();
            recalculateVisiblePanIndiaTables();
        });
        gstRateSelect.dataset.gstEventsBound = '1';
    }

    const customerStateSelect = document.getElementById('customer-state-code');
    if (customerStateSelect && !customerStateSelect.dataset.gstEventsBound) {
        customerStateSelect.addEventListener('change', () => {
            recalculateAllVariantGstRows();
            recalculateAllSimpleGstRows();
        });
        customerStateSelect.dataset.gstEventsBound = '1';
    }
}

function bindSimpleGstPreviewEvents() {
    const pricingContainer = document.getElementById('simplePricingAccordion');
    if (pricingContainer && !pricingContainer.dataset.gstEventsBound) {
        pricingContainer.addEventListener('input', function (event) {
            const target = event.target;
            if (!target) return;
            const row = target.closest('.simple-pricing-row');
            if (target.classList.contains('store-price')) {
                handlePriceChangeForDiscount(row);
                recalculateSimpleGstRow(row);
                recalculateVisiblePanIndiaTables();
            } else if (target.classList.contains('store-disc-pct')) {
                handleDiscPctChange(row);
            } else if (target.classList.contains('store-special-price')) {
                handleSpecialPriceChange(row);
            }
        });
        pricingContainer.dataset.gstEventsBound = '1';
    }

    const gstRateSelect = document.querySelector('select[name="gst_rate"]');
    if (gstRateSelect && !gstRateSelect.dataset.simpleGstEventsBound) {
        gstRateSelect.addEventListener('change', () => {
            recalculateAllSimpleGstRows();
            recalculateVisiblePanIndiaTables();
        });
        gstRateSelect.dataset.simpleGstEventsBound = '1';
    }

    const customerStateSelect = document.getElementById('customer-state-code');
    if (customerStateSelect && !customerStateSelect.dataset.simpleGstEventsBound) {
        customerStateSelect.addEventListener('change', recalculateAllSimpleGstRows);
        customerStateSelect.dataset.simpleGstEventsBound = '1';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    ensureGstSlabPreselected();
});

// ============================================================
// Discount helpers (Disc % ↔ Special Price)
// ============================================================

function handleDiscPctChange(row) {
    if (!row) return;
    const price = parseFloat(row.querySelector('.store-price')?.value);
    const discInput = row.querySelector('.store-disc-pct');
    const spInput = row.querySelector('.store-special-price');
    if (!discInput || !spInput) return;
    const disc = parseFloat(discInput.value);
    if (!isNaN(price) && price > 0 && !isNaN(disc) && disc >= 0 && disc <= 100) {
        spInput.value = (price * (1 - disc / 100)).toFixed(2);
    } else {
        spInput.value = '0';
    }
}

function handleSpecialPriceChange(row) {
    if (!row) return;
    const price = parseFloat(row.querySelector('.store-price')?.value);
    const discInput = row.querySelector('.store-disc-pct');
    const spInput = row.querySelector('.store-special-price');
    if (!discInput || !spInput) return;
    const special = parseFloat(spInput.value);
    if (!isNaN(price) && price > 0 && !isNaN(special) && special > 0 && special < price) {
        discInput.value = (((price - special) / price) * 100).toFixed(2);
    } else {
        discInput.value = '';
        if (!isNaN(special) && !isNaN(price) && special >= price) {
            spInput.value = '0';
        }
    }
}

function handlePriceChangeForDiscount(row) {
    if (!row) return;
    const price = parseFloat(row.querySelector('.store-price')?.value);
    const discInput = row.querySelector('.store-disc-pct');
    const spInput = row.querySelector('.store-special-price');
    if (!discInput || !spInput) return;
    const disc = parseFloat(discInput.value);
    if (!isNaN(price) && price > 0 && !isNaN(disc) && disc > 0) {
        spInput.value = (price * (1 - disc / 100)).toFixed(2);
    } else if (!isNaN(price) && price > 0 && spInput.value !== '') {
        const sp = parseFloat(spInput.value);
        if (!isNaN(sp) && sp < price) {
            discInput.value = (((price - sp) / price) * 100).toFixed(2);
        }
    }
}

// ============================================================
// Pan India GST Breakdown
// ============================================================

/**
 * Collect price entries from within a store pricing card.
 * Returns [{label, price}, …] — one entry per .store-price input.
 */
function collectStorePriceEntries(card) {
    const inputs = card ? Array.from(card.querySelectorAll('.store-price')) : [];
    if (!inputs.length) return [{ label: 'Price', price: '' }];
    return inputs.map(inp => {
        const row   = inp.closest('tr');
        const cell  = row ? row.querySelector('.variant-label-cell') : null;
        const label = cell ? (cell.innerText || cell.textContent || '').trim().substring(0, 30) : 'Price';
        return { label: label || 'Variant', price: inp.value };
    });
}

/**
 * Build the full HTML for the Pan India state-wise breakdown table.
 */
function buildPanIndiaTableHtml(wrapper) {
    const storeGstCode   = wrapper.dataset.storeGstCode   || '';
    const storeStateCode = wrapper.dataset.storeStateCode || '';
    const gstRate        = getSelectedGstRatePercent();
    // Filter out states with no gst_code (bad/duplicate data) and deduplicate by gst_code
    const seenGstCodes = new Set();
    const states = (window._gstStates || []).filter(s => {
        const code = normalizeGstCode(String(s.gst_code || ''));
        if (!code) return false;
        if (seenGstCodes.has(code)) return false;
        seenGstCodes.add(code);
        return true;
    });

    const card          = wrapper.closest('.store-pricing-card') || wrapper.closest('.accordion-item');
    const priceEntries  = collectStorePriceEntries(card);
    const normStoreGst  = normalizeGstCode(storeGstCode);
    const normStoreState= normalizeStateCode(storeStateCode);

    // Grouped header: one 4-col span per price entry
    const headerPriceCols = priceEntries.length > 1
        ? priceEntries.map(e =>
            `<th colspan="4" class="text-center border-start small">${e.label}</th>`
          ).join('')
        : `<th class="border-start">CGST</th><th>SGST</th><th>IGST</th><th>Total Cost</th>`;

    const subHeaderCols = priceEntries.length > 1
        ? priceEntries.map(() =>
            `<th class="border-start small">CGST</th><th class="small">SGST</th><th class="small">IGST</th><th class="small">Total Cost</th>`
          ).join('')
        : '';

    const rows = states.map(state => {
        const stateGst  = normalizeGstCode(String(state.gst_code  || ''));
        const stateSt   = normalizeStateCode(String(state.state_code || ''));

        let supplyType;
        if (normStoreGst && stateGst) {
            supplyType = normStoreGst === stateGst ? 'intra' : 'inter';
        } else if (normStoreState && stateSt) {
            supplyType = normStoreState === stateSt ? 'intra' : 'inter';
        } else {
            supplyType = 'inter';
        }

        const isIntra   = supplyType === 'intra';
        const badgeCls  = isIntra ? 'text-bg-success' : 'text-bg-warning';
        const supplyLbl = isIntra ? 'CGST+SGST' : 'IGST';
        const rowCls    = isIntra ? 'table-success' : '';

        const priceCols = priceEntries.map(entry => {
            const price = parseFloat(entry.price) || 0;
            if (!price) {
                return `<td colspan="4" class="text-muted text-center small border-start">No price</td>`;
            }
            const c   = calculateVariantGstFromPrice(price, gstRate, supplyType);
            const fmt = v => `₹${v.toFixed(2)}`;
            return `
                <td class="border-start">${fmt(c.cgstAmount)}</td>
                <td>${fmt(c.sgstAmount)}</td>
                <td>${fmt(c.igstAmount)}</td>
                <td class="fw-medium">${fmt(c.totalCost)}</td>
            `;
        }).join('');

        const stickyBg = isIntra ? '#d1e7dd' : 'var(--tblr-body-bg, #fff)';
        return `
            <tr class="${rowCls}">
                <td class="text-nowrap small" style="position:sticky;left:0;z-index:1;background:${stickyBg};min-width:130px;">${state.name}</td>
                <td class="text-center" style="position:sticky;left:130px;z-index:1;background:${stickyBg};min-width:52px;"><span class="badge bg-blue-lt text-blue">${state.gst_code || ''}</span></td>
                <td style="position:sticky;left:182px;z-index:1;background:${stickyBg};min-width:90px;"><span class="badge ${badgeCls} small">${supplyLbl}</span></td>
                ${priceCols}
            </tr>
        `;
    }).join('');

    const sellerLabel = storeGstCode ? `GST ${storeGstCode}` : (storeStateCode || 'N/A');

    return `
        <div class="d-flex align-items-center gap-2 mb-2 mt-1 px-1">
            <i class="ti ti-world text-blue fs-4"></i>
            <span class="fw-semibold small text-muted">Pan India GST Breakdown</span>
            <span class="badge bg-blue-lt text-blue ms-auto">Seller State: ${sellerLabel}</span>
        </div>
        <div class="table-responsive border rounded" style="max-height:400px;overflow-y:auto;">
            <table class="table table-sm table-bordered table-hover mb-0" style="border-collapse:separate;border-spacing:0;">
                <thead class="sticky-top">
                    <tr class="table-dark">
                        <th rowspan="${priceEntries.length > 1 ? 2 : 1}" style="position:sticky;left:0;z-index:3;min-width:130px;">STATE</th>
                        <th rowspan="${priceEntries.length > 1 ? 2 : 1}" style="position:sticky;left:130px;z-index:3;min-width:52px;">GST</th>
                        <th rowspan="${priceEntries.length > 1 ? 2 : 1}" style="position:sticky;left:182px;z-index:3;min-width:90px;">SUPPLY</th>
                        ${headerPriceCols}
                    </tr>
                    ${priceEntries.length > 1 ? `<tr class="table-secondary">${subHeaderCols}</tr>` : ''}
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

/**
 * Re-render all currently-visible Pan India wrappers (called when price or GST rate changes).
 */
function recalculateVisiblePanIndiaTables() {
    document.querySelectorAll('.pan-india-wrapper:not(.d-none)').forEach(wrapper => {
        wrapper.innerHTML = buildPanIndiaTableHtml(wrapper);
    });
}

/**
 * Wire up toggle buttons inside `container`.
 */
function initPanIndiaToggles(container) {
    container.querySelectorAll('.pan-india-toggle-btn').forEach(btn => {
        if (btn.dataset.panIndiaBound) return;
        btn.dataset.panIndiaBound = '1';
        btn.addEventListener('click', function () {
            const accordionBody = this.closest('.accordion-body');
            if (!accordionBody) return;
            const wrapper = accordionBody.querySelector('.pan-india-wrapper');
            if (!wrapper) return;
            const hidden = wrapper.classList.contains('d-none');
            if (hidden) {
                wrapper.classList.remove('d-none');
                wrapper.innerHTML = buildPanIndiaTableHtml(wrapper);
                this.innerHTML = '<i class="ti ti-world-off me-1"></i>Hide Pan India GST';
                this.classList.remove('btn-outline-info');
                this.classList.add('btn-info');
            } else {
                wrapper.classList.add('d-none');
                this.innerHTML = '<i class="ti ti-world me-1"></i>Pan India GST';
                this.classList.remove('btn-info');
                this.classList.add('btn-outline-info');
            }
        });
    });
}

// Fetch stores from the server
let cachedStores = null; // Store cached result
let storesPromise = null; // Store the fetch promise for concurrent calls

function fetchStores() {
    // Use server-preloaded stores if available (avoids AJAX latency and failures)
    if (cachedStores !== null) {
        return Promise.resolve(cachedStores);
    }
    if (typeof window._preloadedStores !== 'undefined' && window._preloadedStores.length > 0) {
        cachedStores = window._preloadedStores;
        return Promise.resolve(cachedStores);
    }
    // Fallback: fetch from API
    if (storesPromise !== null) {
        return storesPromise;
    }
    const storesListUrl = (typeof window._adminSellerId !== 'undefined' && window._adminSellerId)
        ? `${base_url}/${panel}/stores/list?seller_id=${window._adminSellerId}`
        : `${base_url}/${panel}/stores/list`;
    storesPromise = axios.get(storesListUrl)
        .then(response => {
            cachedStores = response.data.data;
            storesPromise = null;
            return cachedStores;
        })
        .catch(error => {
            console.error('Error fetching stores:', error);
            storesPromise = null;
            return [];
        });
    return storesPromise;
}

// Fetch product pricing data
function fetchProductPricing(productId) {
    return axios.get(`${base_url}/${panel}/products/${productId}/pricing`)
        .then(response => {
            if (response.data.success) {
                productPricing = response.data.data;

                // Initialize pricing UI with the fetched data
                if (document.getElementById('productType').value === 'variant') {
                    updateVariantPricing();
                } else {
                    initializeSimplePricing();
                }

                return productPricing;
            }
            return null;
        })
        .catch(error => {
            console.error('Error fetching product pricing:', error);
            return null;
        });
}

// Initialize pricing for simple products
function initializeSimplePricing() {
    const container = document.getElementById('simplePricingContainer');
    container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading stores...</p></div>';

    // Create accordion container
    const accordionContainer = document.createElement('div');
    accordionContainer.className = 'accordion accordion-flush border m-2 rounded';
    accordionContainer.id = 'simplePricingAccordion';

    fetchStores().then(stores => {
        if (stores === null || stores.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No stores available for pricing.</div>';
            return;
        }
        ensureDefaultCustomerStateFromStores(stores);

        let html = '';
        stores.forEach((store, index) => {
            const storeStateLabel = store.state_name || store.state_code || 'State N/A';
            const storeGstLabel = store.gst_code ? `GST ${store.gst_code}` : 'GST N/A';
            // Get store pricing data if available
            let storePrice = '';
            let storeStock = '';
            let storeSku = '';
            let storeSpecialPrice = '0';
            let storeDiscPct = '';

            // If we're in edit mode and have pricing data
            if (productPricing && productPricing.variant_pricing) {
                // For simple products, we need to find the single variant
                const variantId = window.productData && window.productData.variant ? window.productData.variant.id : null;

                if (variantId && productPricing.variant_pricing[variantId]) {
                    // Find pricing for this store
                    const storePricing = productPricing.variant_pricing[variantId].store_pricing.find(
                        sp => sp.store_id === store.id
                    );

                    if (storePricing) {
                        storePrice = storePricing.price || '';
                        storeStock = storePricing.stock || '';
                        storeSku = storePricing.sku || '';
                        storeSpecialPrice = storePricing.special_price ?? '0';
                    }
                }
            }
            if (storeSpecialPrice && storePrice && parseFloat(storePrice) > 0) {
                const _sp = parseFloat(storeSpecialPrice), _p = parseFloat(storePrice);
                if (_sp < _p) storeDiscPct = (((_p - _sp) / _p) * 100).toFixed(2);
                else storeSpecialPrice = '0';
            }
            html += `
                <div class="accordion-item store-pricing-card" data-store-id="${store.id}">
                    <h2 class="accordion-header bg-body-tertiary">
                        <button class="accordion-button d-flex align-items-center ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#simple-store-${store.id}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="simple-store-${store.id}">
                            <span class="fw-medium text-dark">${store.name}</span>
                            <span class="badge text-bg-light ms-2">${storeStateLabel} (${storeGstLabel})</span>
                            <button type="button" class="btn btn-outline-danger btn-icon btn-sm remove-store-pricing me-3">
                                <i class="ti ti-trash fs-2 p-1"></i>
                            </button>
                        </button>
                    </h2>
                    <div id="simple-store-${store.id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#simplePricingAccordion">
                        <div class="accordion-body p-2">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Price</th>
                                            <th>Disc %</th>
                                            <th>Special Price</th>
                                            <th>Stock</th>
                                            <th>SKU</th>
                                            <th>Supply</th>
                                            <th>CGST</th>
                                            <th>SGST</th>
                                            <th>IGST</th>
                                            <th>Total GST</th>
                                            <th>Total Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="simple-pricing-row" data-store-id="${store.id}" data-store-state-code="${store.state_code || ''}" data-store-gst-code="${store.gst_code || ''}">
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">${currencySymbol}</span>
                                                    <input type="number" class="form-control store-price" name="store_pricing[${store.id}][price]" step="0.01" min="0" value="${storePrice}">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control store-disc-pct" step="0.01" min="0" max="100" placeholder="0" value="${storeDiscPct}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">${currencySymbol}</span>
                                                    <input type="number" class="form-control store-special-price" name="store_pricing[${store.id}][special_price]" step="0.01" min="0" placeholder="0" value="${storeSpecialPrice}">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm store-stock" name="store_pricing[${store.id}][stock]" min="0" value="${storeStock}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm store-sku" name="store_pricing[${store.id}][sku]" value="${storeSku}">
                                            </td>
                                            <td>
                                                <span class="badge text-bg-light gst-supply-type">-</span>
                                            </td>
                                            <td><span class="gst-cgst-amount">-</span></td>
                                            <td><span class="gst-sgst-amount">-</span></td>
                                            <td><span class="gst-igst-amount">-</span></td>
                                            <td><span class="gst-tax-amount">-</span></td>
                                            <td><span class="fw-medium gst-total-cost">-</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-2">
                                <button type="button" class="btn btn-sm btn-outline-info pan-india-toggle-btn">
                                    <i class="ti ti-world me-1"></i>Pan India GST
                                </button>
                            </div>
                            <div class="pan-india-wrapper d-none" data-store-gst-code="${store.gst_code || ''}" data-store-state-code="${store.state_code || ''}"></div>
                        </div>
                    </div>
                </div>
            `;
        });

        accordionContainer.innerHTML = html;
        container.innerHTML = '';
        container.appendChild(accordionContainer);

        // Add event listeners for trash buttons
        const removeStoreCard = document.getElementsByClassName('remove-store-pricing');
        if (removeStoreCard && removeStoreCard.length > 0) {
            Array.from(removeStoreCard).forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation(); // Prevent accordion toggle
                    e.target.closest('.store-pricing-card').remove();
                });
            });
        }

        bindSimpleGstPreviewEvents();
        recalculateAllSimpleGstRows();
        initPanIndiaToggles(accordionContainer);
    });
}

// Initialize pricing for variant products
function initializeVariantPricing() {
    const container = document.getElementById('storePricingAccordion');
    container.innerHTML = '<div class="alert alert-info">Please generate variants first to set store-specific pricing.</div>';
    bindVariantGstPreviewEvents();

    // If variants are already generated, update the pricing UI
    if (variants.length > 0) {
        updateVariantPricing();
    }
}

// Update pricing UI for variants
function updateVariantPricing() {
    ensureAttrIdMap();
    const container = document.getElementById('storePricingAccordion');
    fetchStores().then(stores => {
        if (stores === null || stores.length === 0 || variants.length === 0) {
            container.innerHTML = '<div class="alert alert-info m-3">No stores or variants available for pricing.</div>';
            return;
        }
        ensureDefaultCustomerStateFromStores(stores);

        let html = '';
        stores.forEach((store, index) => {
            const storeStateLabel = store.state_name || store.state_code || 'State N/A';
            const storeGstLabel = store.gst_code ? `GST ${store.gst_code}` : 'GST N/A';
            html += `
                <div class="accordion-item store-pricing-card" data-store-id="${store.id}">
                    <h2 class="accordion-header bg-body-tertiary">
                        <button class="accordion-button d-flex align-items-center ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#store-${store.id}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="store-${store.id}">
                            <span class="fw-medium text-dark">${store.name}</span>
                            <span class="badge text-bg-light ms-2">${storeStateLabel} (${storeGstLabel})</span>
                            <button type="button" class="btn btn-outline-danger btn-icon btn-sm remove-store-pricing me-3">
                                <i class="ti ti-trash fs-2 p-1"></i>
                            </button>
                        </button>
                    </h2>
                    <div id="store-${store.id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#storePricingAccordion">
                        <div class="accordion-body p-2">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Variant</th>
                                            <th>Price</th>
                                            <th>Disc %</th>
                                            <th>Special Price</th>
                                            <th>Stock</th>
                                            <th>SKU</th>
                                            <th>Supply</th>
                                            <th>CGST</th>
                                            <th>SGST</th>
                                            <th>IGST</th>
                                            <th>Total GST</th>
                                            <th>Total Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${variants.map(variant => {
                                        // (variant rows below)
                const variantId = variant.id;
                const storeStateCode = store.state_code || '';
                const storeGstCode = store.gst_code || '';
                let storePrice = '';
                let storeStock = '';
                let storeSku = '';
                let storeSpecialPrice = '0';
                let storeDiscPct = '';

                if (productPricing && productPricing.variant_pricing) {
                    if (productPricing.variant_pricing[variantId]) {
                        const serverVariant = productPricing.variant_pricing[variantId];
                        const storePricing = serverVariant.store_pricing.find(
                            sp => sp.store_id === store.id
                        );
                        if (storePricing) {
                            storePrice = storePricing.price || '';
                            storeStock = storePricing.stock || '';
                            storeSku = storePricing.sku || '';
                            storeSpecialPrice = storePricing.special_price ?? '0';
                        }
                    } else {
                        const serverVariants = window.productData && window.productData.variants ? window.productData.variants : [];
                        const matchingServerVariant = serverVariants.find(sv => {
                            if (!sv.attributes || !variant.attributes) return false;
                            const serverAttrs = {};
                            sv.attributes.forEach(attr => {
                                serverAttrs[attr.global_attribute_id] = attr.global_attribute_value_id;
                            });
                            for (const attrId in variant.attributes) {
                                if (serverAttrs[attrId] !== variant.attributes[attrId]) {
                                    return false;
                                }
                            }
                            return true;
                        });
                        if (matchingServerVariant && matchingServerVariant.id) {
                            const serverVariantId = matchingServerVariant.id;
                            if (productPricing.variant_pricing[serverVariantId]) {
                                const serverVariant = productPricing.variant_pricing[serverVariantId];
                                const storePricing = serverVariant.store_pricing.find(
                                    sp => sp.store_id === store.id
                                );
                                if (storePricing) {
                                    storePrice = storePricing.price || '';
                                    storeStock = storePricing.stock || '';
                                    storeSku = storePricing.sku || '';
                                    storeSpecialPrice = storePricing.special_price ?? '0';
                                }
                            }
                        }
                    }
                }
                if (storeSpecialPrice && storePrice && parseFloat(storePrice) > 0) {
                    const _sp = parseFloat(storeSpecialPrice), _p = parseFloat(storePrice);
                    if (_sp < _p) storeDiscPct = (((_p - _sp) / _p) * 100).toFixed(2);
                    else storeSpecialPrice = '0';
                }

                const variantAttributeBadges = Object.entries(variant.attributes).map(([attrId, valueId]) => {
                    const attr = attrIdMap[attrId];
                    const attrName = attr ? attr.name : attrId;
                    const valueName = attr && attr.values[valueId] ? attr.values[valueId] : valueId;
                    return `<span class="badge bg-primary-subtle text-primary me-1">${attrName}: ${valueName}</span>`;
                }).join('');

                const variantTitleText = (variant.title || '').trim();
                const variantLabelHtml = variantAttributeBadges || (variantTitleText
                    ? `<span class="badge bg-primary-subtle text-primary me-1">${variantTitleText}</span>`
                    : '<span class="text-muted small">Variant</span>');

                const productName = (
    (window.productData?.product?.title) ||
    (document.querySelector('input[name="title"]')?.value) ||
    ''
).trim();
                return `
                                                <tr class="variant-pricing-row" data-store-id="${store.id}" data-store-state-code="${storeStateCode}" data-store-gst-code="${storeGstCode}" data-variant-id="${variantId}">
                                                    <td class="variant-label-cell">
                                                        ${productName ? `<div class="fw-semibold text-dark small mb-1">${productName}</div>` : ''}
                                                        ${variantTitleText ? `<div class="fw-semibold text-dark mb-1">${variantTitleText}</div>` : ''}
                                                        <div>${variantLabelHtml}</div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">${currencySymbol}</span>
                                                            <input type="number" class="form-control store-price" name="variant_pricing[${store.id}][${variantId}][price]" step="0.01" min="0" value="${storePrice}">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" class="form-control store-disc-pct" step="0.01" min="0" max="100" placeholder="0" value="${storeDiscPct}">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">${currencySymbol}</span>
                                                            <input type="number" class="form-control store-special-price" name="variant_pricing[${store.id}][${variantId}][special_price]" step="0.01" min="0" placeholder="0" value="${storeSpecialPrice}">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm store-stock" name="variant_pricing[${store.id}][${variantId}][stock]" min="0" value="${storeStock}">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm store-sku" name="variant_pricing[${store.id}][${variantId}][sku]" value="${storeSku}">
                                                    </td>
                                                    <td>
                                                        <span class="badge text-bg-light gst-supply-type">-</span>
                                                    </td>
                                                    <td><span class="gst-cgst-amount">-</span></td>
                                                    <td><span class="gst-sgst-amount">-</span></td>
                                                    <td><span class="gst-igst-amount">-</span></td>
                                                    <td><span class="gst-tax-amount">-</span></td>
                                                    <td><span class="fw-medium gst-total-cost">-</span></td>
                                                </tr>
                                            `;
            }).join('')}
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-2">
                                <button type="button" class="btn btn-sm btn-outline-info pan-india-toggle-btn">
                                    <i class="ti ti-world me-1"></i>Pan India GST
                                </button>
                            </div>
                            <div class="pan-india-wrapper d-none" data-store-gst-code="${store.gst_code || ''}" data-store-state-code="${store.state_code || ''}"></div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        const removeStoreCard = document.getElementsByClassName('remove-store-pricing');
        if (removeStoreCard && removeStoreCard.length > 0) {
            Array.from(removeStoreCard).forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.stopPropagation(); // Prevent accordion toggle
                    e.target.closest('.store-pricing-card').remove();
                });
            });
        }

        bindVariantGstPreviewEvents();
        recalculateAllVariantGstRows();
        initPanIndiaToggles(container);
    });
}

/**
 * Refresh only the label cells in the variant pricing table to reflect
 * the current attributes/title state — without rebuilding the whole table
 * (which would wipe entered prices).
 */
function refreshVariantPricingLabels() {
    ensureAttrIdMap();
    syncVariantsFromDOM();
    document.querySelectorAll('#storePricingAccordion .variant-pricing-row[data-variant-id]').forEach(row => {
        const variantId = row.dataset.variantId;
        const variant = variants.find(v => String(v.id) === String(variantId));
        if (!variant) return;

        const badgesHtml = Object.entries(variant.attributes).map(([attrId, valueId]) => {
            const attr = attrIdMap[attrId];
            const attrName = attr ? attr.name : attrId;
            const valueName = attr && attr.values[valueId] ? attr.values[valueId] : valueId;
            return `<span class="badge bg-primary-subtle text-primary me-1">${attrName}: ${valueName}</span>`;
        }).join('');

        const titleText = (variant.title || '').trim();
        const labelHtml = badgesHtml || (titleText
            ? `<span class="badge bg-primary-subtle text-primary me-1">${titleText}</span>`
            : '<span class="text-muted small">No attributes set</span>');

        const cell = row.querySelector('.variant-label-cell');
        if (cell) {
            const productName = (
    (window.productData?.product?.title) ||
    (document.querySelector('input[name="title"]')?.value) ||
    ''
).trim();
            cell.innerHTML = (productName ? `<div class="text-muted small mb-1" style="font-size:0.72rem;">${productName}</div>` : '')
                + (titleText ? `<div class="fw-semibold text-dark mb-1">${titleText}</div>` : '')
                + `<div>${labelHtml}</div>`;
        }
    });
}

function syncVariantsFromDOM() {
    // Find the checked default radio first
    let checkedDefaultId = null;
    document.querySelectorAll('input[name="is_defaults"]:checked').forEach(radio => {
        const oc = radio.getAttribute('onchange') || '';
        const m = oc.match(/setDefaultVariant\('([^']+)'\)/);
        if (m) checkedDefaultId = m[1];
    });

    document.querySelectorAll('#variantsList > [data-id]').forEach(card => {
        const id = card.dataset.id;
        const variant = variants.find(v => v.id === id);
        if (!variant) return;

        // Sync all inputs/selects that call updateVariant
        card.querySelectorAll('[onchange]').forEach(el => {
            const oc = el.getAttribute('onchange') || '';
            const m = oc.match(/updateVariant\([^,]+,\s*'([^']+)'/);
            if (m) {
                const field = m[1];
                if (field !== 'variant_image') {
                    variant[field] = el.value;
                }
            }
        });

        // Sync per-variant attribute picker rows into variant.attributes
        syncVariantAttrRows(id);

        // Sync is_default from the checked radio
        if (checkedDefaultId !== null) {
            variant.is_default = (id === checkedDefaultId) ? 'on' : '';
        }
    });
}

function addVariantInputsToForm() {
    document.querySelectorAll('.variant-hidden-input').forEach(el => el.remove());
    const form = document.querySelector('#product-form-submit');
    if (!form) return;

    // Sync latest DOM values into variants[] before serializing
    syncVariantsFromDOM();

    // Create a simplified variants array
    const simplifiedVariants = variants.map(variant => {
        // Create a new variant object with a simpler structure
        const newVariant = {
            id: variant.id,
            title: variant.title || '',
            availability: variant.availability || '',
            is_default: variant.is_default || '',
            weight: variant.weight !== '' && variant.weight !== null && variant.weight !== undefined ? variant.weight : null,
            weight_unit: variant.weight_unit || 'g',
            attributes: [],
            metadata: {
                is_indexable: variant.is_indexable !== false,
                seo_title: variant.seo_title || null,
                seo_description: variant.seo_description || null,
                seo_keywords: variant.seo_keywords || null,
                og_title: variant.og_title || null,
                og_description: variant.og_description || null,
                twitter_title: variant.twitter_title || null,
                twitter_description: variant.twitter_description || null,
                twitter_card: variant.twitter_card || null,
                schema_mode: variant.schema_mode || 'auto',
                schema_json_ld: variant.schema_json_ld || null,
            },
        };

        // Add attributes in a simpler format
        Object.entries(variant.attributes).forEach(([attrId, valueId]) => {
            newVariant.attributes.push({
                attribute_id: attrId,
                value_id: valueId
            });
        });

        return newVariant;
    });

    // Add the simplified variants as a single JSON string
    const input = document.createElement('input');
    input.type = 'hidden';
    input.className = 'variant-hidden-input';
    input.name = 'variants_json';
    input.value = JSON.stringify(simplifiedVariants);
    form.appendChild(input);
}

// Function to restructure form data into a simpler format
function restructureFormData(originalFormData) {
    // Create a new FormData object
    const newFormData = new FormData();

    // Extract and restructure pricing data
    const storePricing = [];
    const variantPricing = [];

    // Temporary storage for collecting all fields for each store/variant
    const storePricingTemp = {};
    const variantPricingTemp = {};

    // Process all form fields
    for (let [key, value] of originalFormData.entries()) {
        // Handle store pricing for simple products
        if (key.startsWith('store_pricing[')) {
            // Extract store ID and field name from the key
            // Format: store_pricing[storeId][fieldName]
            const matches = key.match(/store_pricing\[(\d+)\]\[([^\]]+)\]/);
            if (matches) {
                const storeId = matches[1];
                const field = matches[2];

                if (!storePricingTemp[storeId]) {
                    storePricingTemp[storeId] = {store_id: storeId};
                }
                storePricingTemp[storeId][field] = normalizePricingFieldValue(field, value);
            }
        }
        // Handle variant pricing
        else if (key.startsWith('variant_pricing[')) {
            // Extract store ID, variant ID, and field name from the key
            // Format: variant_pricing[storeId][variantId][fieldName]
            const matches = key.match(/variant_pricing\[(\d+)\]\[([^\]]+)\]\[([^\]]+)\]/);
            if (matches) {
                const storeId = matches[1];
                const variantId = matches[2];
                const field = matches[3];

                const key = `${storeId}_${variantId}`;
                if (!variantPricingTemp[key]) {
                    variantPricingTemp[key] = {
                        store_id: storeId,
                        variant_id: variantId
                    };
                }
                variantPricingTemp[key][field] = normalizePricingFieldValue(field, value);
            }
        }
        // Pass through all other fields unchanged
        else {
            newFormData.append(key, value);
        }
    }

    // Convert temporary objects to arrays
    for (const storeId in storePricingTemp) {
        storePricing.push(storePricingTemp[storeId]);
    }

    for (const key in variantPricingTemp) {
        variantPricing.push(variantPricingTemp[key]);
    }

    // Add restructured data to the new FormData
    newFormData.append('pricing', JSON.stringify({
        store_pricing: storePricing,
        variant_pricing: variantPricing
    }));

    return newFormData;
}

function normalizePricingFieldValue(field, value) {
    if (field === 'special_price' && (value === '' || value === null || value === undefined)) {
        return '0';
    }

    return value;
}

function getErrorSummaryContainer(form) {
    return form?.querySelector('#api-error-summary') || null;
}

function hideErrorSummary(form) {
    const summary = getErrorSummaryContainer(form);
    if (!summary) return;
    const list = summary.querySelector('ul');
    if (list) {
        list.innerHTML = '';
    }
    summary.classList.add('d-none');
}

function normalizeErrorMessages(errors, fallbackMessage = '') {
    const messages = [];

    if (errors && typeof errors === 'object' && !Array.isArray(errors)) {
        Object.values(errors).forEach(value => {
            if (Array.isArray(value)) {
                value.forEach(item => {
                    if (item !== null && item !== undefined && String(item).trim() !== '') {
                        messages.push(String(item).trim());
                    }
                });
            } else if (value !== null && value !== undefined && String(value).trim() !== '') {
                messages.push(String(value).trim());
            }
        });
    } else if (Array.isArray(errors)) {
        errors.forEach(item => {
            if (item !== null && item !== undefined && String(item).trim() !== '') {
                messages.push(String(item).trim());
            }
        });
    } else if (errors !== null && errors !== undefined && String(errors).trim() !== '') {
        messages.push(String(errors).trim());
    }

    const fallback = String(fallbackMessage || '').trim();
    if (fallback && messages.length === 0) {
        messages.push(fallback);
    }

    // Remove duplicates while preserving order.
    return [...new Set(messages)];
}

function showErrorSummary(form, errors, fallbackMessage = '') {
    const summary = getErrorSummaryContainer(form);
    if (!summary) return;

    const list = summary.querySelector('ul');
    if (!list) return;

    const messages = normalizeErrorMessages(errors, fallbackMessage);
    if (messages.length === 0) {
        hideErrorSummary(form);
        return;
    }

    list.innerHTML = '';
    messages.forEach((msg) => {
        const li = document.createElement('li');
        li.textContent = String(msg);
        list.appendChild(li);
    });
    summary.classList.remove('d-none');
    summary.scrollIntoView({behavior: 'smooth', block: 'start'});
}

function toSafeSameOriginUrl(url) {
    if (!url || typeof url !== 'string') return null;
    try {
        const resolved = new URL(url, window.location.origin);
        if (resolved.origin !== window.location.origin) return null;
        if (!['http:', 'https:'].includes(resolved.protocol)) return null;
        return resolved.toString();
    } catch (_e) {
        return null;
    }
}

let productForm = document.getElementById('product-form-submit');
productForm?.addEventListener('submit', function (e) {
    e.preventDefault();
    flushTomSelectPendingKeyword(
        document.getElementById('main-seo-keywords-input'),
        syncMainSeoKeywordsValue
    );
    document.querySelectorAll('.variant-seo-keywords-input').forEach(input => {
        const variantId = input.getAttribute('data-variant-id');
        if (!variantId) return;
        flushTomSelectPendingKeyword(input, () => syncVariantSeoKeywordsValue(variantId, input));
    });
    addVariantInputsToForm();
    hideErrorSummary(productForm);
    clearValidationErrors(productForm);

    const action = productForm.getAttribute('action');
    const originalFormData = new FormData(productForm);
    const submitButton = productForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    const originalButtonContent = submitButton.innerHTML;
    submitButton.innerHTML = `<div class="spinner-border text-white me-2" role="status"><span class="visually-hidden">Loading...</span></div> ${originalButtonContent}`;


    // Restructure form data
    const formData = restructureFormData(originalFormData);

    // Prepare headers
    const headers = {
        'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'
    };

    // Prepare axios config
    const config = {
        method: 'POST', url: action, headers: headers
    };
    config.data = formData;

    axios(config)
        .then(function (response) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
            let data = response.data;
            if (data.success === false) {
                showErrorSummary(productForm, [data.message], data.message);
                return Toast.fire({
                    icon: "error", title: data.message
                });
            }
            clearValidationErrors(productForm);
            hideErrorSummary(productForm);
            const redirectUrl = data.data && data.data.redirect_url ? data.data.redirect_url : null;
            setTimeout(function () {
                const safeRedirectUrl = toSafeSameOriginUrl(redirectUrl);
                if (safeRedirectUrl) {
                    window.location.href = safeRedirectUrl;
                } else {
                    location.reload();
                }
            }, 3000);
            return Toast.fire({
                icon: "success", title: data.message
            });
            // Handle success UI update here
        })
        .catch(function (error) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;

            if (error.response && error.response.status === 422) {
                // Handle validation errors
                const validationErrors = error.response.data.data || error.response.data.errors;
                if (validationErrors) {
                    displayValidationErrors(productForm, validationErrors);
                    showErrorSummary(productForm, validationErrors, error.response.data.message);

                    // Show toast with first error or generic message
                    const firstErrorMessage = error.response.data.message ||
                        Object.values(validationErrors).flat()[0] ||
                        "Validation failed";

                    return Toast.fire({
                        icon: "error",
                        title: firstErrorMessage
                    });
                }
            }

            if (error.response && error.response.data && error.response.data.message) {
                const serverErrors = error.response.data.errors || error.response.data.data || null;
                showErrorSummary(productForm, serverErrors, error.response.data.message);
                return Toast.fire({
                    icon: "error", title: error.response.data.message
                });
            } else {
                console.error('Error:', error);
                showErrorSummary(productForm, null, "An error occurred while submitting the form.");
                return Toast.fire({
                    icon: "error", title: "An error occurred while submitting the form."
                });
            }
        });
});

try {
    new TomSelect('.product-tags', {
        create: true
    });
} catch (e) {
    // console.error(e);
}

initializeMainSeoAutofill();
initializeMainSeoKeywordsInput();


const videoTypeSelect = document.getElementById('videoType');
const videoLinkDiv = document.querySelector('input[name="video_link"]')?.closest('.mb-3');
const videoUploadDiv = document.querySelector('input[name="product_video"]')?.closest('.mb-3');


function toggleVideoFields() {
    const selectedType = videoTypeSelect !== null ? videoTypeSelect.value.toLowerCase() : "";
    if (videoLinkDiv !== null && videoUploadDiv !== null && videoLinkDiv !== undefined && videoUploadDiv !== undefined) {
        if (selectedType === 'self_hosted') {
            videoLinkDiv.style.display = 'none';
            videoUploadDiv.style.display = 'block';
        } else if (selectedType) {
            videoLinkDiv.style.display = 'block';
            videoUploadDiv.style.display = 'none';
        } else {
            // If no type is selected, hide both
            videoLinkDiv.style.display = 'none';
            videoUploadDiv.style.display = 'none';
        }
    }
}

// Initial toggle on a load
toggleVideoFields();

// Add event listener on change
videoTypeSelect?.addEventListener('change', toggleVideoFields);
$(document).ready(function () {
    // ----- Custom Fields (dynamic key-value) -----
    (function initCustomFields() {
        const container = document.getElementById('customFieldsContainer');
        const addBtn = document.getElementById('addCustomFieldBtn');
        if (!container || !addBtn) return;

        function createRow(key = '', value = '') {
            const row = document.createElement('div');
            row.className = 'd-flex gap-2 align-items-center';

            const keyInput = document.createElement('input');
            keyInput.type = 'text';
            keyInput.className = 'form-control';
            keyInput.placeholder = 'Field name (e.g., color)';
            keyInput.value = key;

            const valueInput = document.createElement('input');
            valueInput.type = 'text';
            valueInput.className = 'form-control';
            valueInput.placeholder = 'Value (e.g., red)';
            valueInput.value = value;

            // Update name attributes based on current key
            function syncNames() {
                const k = keyInput.value.trim();
                // Default temp name so field is still submitted
                const safe = k || `__custom_${Date.now()}_${Math.floor(Math.random()*9999)}__`;
                valueInput.name = `custom_fields[${safe}]`;
            }

            keyInput.addEventListener('input', syncNames);
            // Initialize name
            syncNames();

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-outline-danger';
            removeBtn.innerHTML = '<i class="ti ti-x"></i>';
            removeBtn.addEventListener('click', () => row.remove());

            row.appendChild(keyInput);
            row.appendChild(valueInput);
            row.appendChild(removeBtn);
            return row;
        }

        // Preload existing fields from data attribute (object map)
        try {
            const existingJson = container.getAttribute('data-existing');
            if (existingJson) {
                const existing = JSON.parse(existingJson || '{}') || {};
                Object.keys(existing).forEach(k => {
                    container.appendChild(createRow(k, existing[k]));
                });
            }
        } catch (e) {
            console.error('Error parsing existing custom fields:', e);
        }

        addBtn.addEventListener('click', function () {
            container.appendChild(createRow());
        });
    })();

    const table = $('#products-table').DataTable();
    const faqTable = $('#product-faqs-table').DataTable();

    // Prefill filters from URL params if present
    try {
        const params = new URLSearchParams(window.location.search);
        const vs = params.get('verification_status');
        if (vs && $('#productVerificationStatusFilter').length) {
            $('#productVerificationStatusFilter').val(vs);
            // Trigger an initial reload with the preselected filter
            setTimeout(function () {
                table.ajax.reload(null, false);
            }, 50);
        }
    } catch (e) {
        console.error(e);
    }

    // Initialize Tom Select for Category Filter (server-side loading)
    try {
        const catEl = document.getElementById('productCategoryFilter');
        if (catEl) {
            /** @type {any} */ (window).TomSelect && new /** @type {any} */ (window).TomSelect(catEl, {
                copyClassesToDropdown: false,
                dropdownParent: 'body',
                controlInput: '<input>',
                valueField: 'value',
                labelField: 'text',
                searchField: 'text',
                placeholder: (typeof labels !== 'undefined' && labels.category) ? labels.category : 'Category',
                load: function (query, callback) {
                    if (!query.length) return callback();
                    const url = `${base_url}/${panel}/categories/search?search=${encodeURIComponent(query)}`;
                    fetch(url)
                        .then(response => response.json())
                        .then(json => callback(json))
                        .catch(() => callback());
                }
            });
        }
    } catch (e) {
        console.error(e);
    }

    // Reload table when filters change
    $('#productVerificationStatusFilter, #productStatusFilter, #productTypeFilter, #productCategoryFilter').on('change', function () {
        table.ajax.reload(null, false);
    });
    $('#faqStatusFilter, [name=\'product_id_filter\']').on('change', function () {
        faqTable.ajax.reload(null, false);
    });

    // Add filter params to AJAX request
    $('#product-faqs-table').on('preXhr.dt', function (_e, _settings, data) {
        data.status = $('#faqStatusFilter').val();
        data.product_id = $('[name=\'product_id_filter\']').val();
    });

    $('#products-table').on('preXhr.dt', function (_e, _settings, data) {
        data.product_type = $('#productTypeFilter').val();
        data.product_status = $('#productStatusFilter').val();
        data.verification_status = $('#productVerificationStatusFilter').val();
        data.category_id = $('#productCategoryFilter').val();
    });
    (function () {
        const select = document.getElementById('verification_status');
        const reasonWrap = document.getElementById('rejection-reason-wrapper');
        const toggleReason = () => {
            const val = (select !== undefined && select != null && select !== "") ? select.value : null;
            if (reasonWrap === undefined || reasonWrap == null) return;
            reasonWrap.style.display = (val === 'rejected') ? 'block' : 'none';
            if (val !== 'rejected') {
                const ta = document.getElementById('rejection_reason');
                if (ta) ta.value = '';
            }
        };
        select?.addEventListener('change', toggleReason);
        toggleReason();
    })();
});

$(document).ready(function () {
    document.addEventListener('click', function (event) {
        const updateProductStatus = event.target.closest('.update-product-status');
        if (!updateProductStatus) return;

        const id = updateProductStatus.getAttribute('data-id');

        // Disable button
        updateProductStatus.disabled = true;

        // Save original text
        let originalText = updateProductStatus.innerHTML;

        // Show spinner
        updateProductStatus.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
    `;

        axios.post(`${base_url}/${panel}/products/${id}/update-status`)
            .then(function (response) {
                let data = response.data;

                if (data.success) {
                    $(`#products-table`).DataTable().ajax.reload(null, false);
                    Toast.fire({
                        icon: "success", title: data.message
                    });
                } else {
                    Toast.fire({
                        icon: "error", title: data.message
                    });
                }

                // Re-enable and restore text
                updateProductStatus.disabled = false;
                updateProductStatus.innerHTML = originalText;
            })
            .catch(function (error) {
                console.error('Error:', error);

                Toast.fire({
                    icon: "error", title: "An error occurred while updating product status."
                });

                // Re-enable and restore text
                updateProductStatus.disabled = false;
                updateProductStatus.innerHTML = originalText;
            });
    });

});
