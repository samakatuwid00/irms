/**
 * Shared utility functions for resource forms
 */

/**
 * Setup image preview functionality
 * @param {string} uploadId - ID of the file input
 * @param {string} previewId - ID of the image preview element
 */
export function setupImagePreview(uploadId, previewId) {
    const imageUpload = document.getElementById(uploadId);
    const imagePreview = document.getElementById(previewId);

    if (!imageUpload || !imagePreview) return;

    imageUpload.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file.');
            event.target.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Image size must be less than 5MB.');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Setup tab functionality
 * @param {HTMLElement} form - The form element containing tabs
 */
export function setupTabs(form) {
    const tabs = form.querySelectorAll('.tab-btn');
    const contents = form.querySelectorAll('.tab-content');

    if (tabs.length === 0) return;

    function activateTab(tab) {
        tabs.forEach(t => {
            t.classList.remove('border-blue-600', 'text-blue-600', 'active');
            t.classList.add('text-gray-600');
        });

        contents.forEach(c => c.classList.add('hidden'));

        tab.classList.add('border-blue-600', 'text-blue-600', 'active');
        tab.classList.remove('text-gray-600');

        const target = form.querySelector(`#${tab.dataset.tab}`);
        if (target) {
            target.classList.remove('hidden');
        }
    }

    // Activate first tab by default
    activateTab(tabs[0]);

    // Setup click handlers
    tabs.forEach(tab => {
        tab.addEventListener('click', () => activateTab(tab));
    });
}

/**
 * Setup quantity calculation
 * @param {HTMLElement} form - The form element
 * @param {string} totalFieldId - ID of the total quantity field
 */
export function setupQuantityCalculation(form, totalFieldId) {
    const qtyInputs = form.querySelectorAll('.qty');
    const totalField = document.getElementById(totalFieldId);

    if (!totalField) return;

    const calculateTotal = () => {
        let total = 0;
        qtyInputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        totalField.value = total;
    };

    qtyInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    return calculateTotal;
}

/**
 * Setup form submit with loading state
 * @param {HTMLElement} form - The form element
 * @param {string} btnId - ID of submit button
 * @param {string} textId - ID of button text element
 * @param {string} loadingId - ID of loading indicator element
 */
export function setupFormSubmit(form, btnId, textId, loadingId) {
    form.addEventListener('submit', () => {
        const saveBtn = document.getElementById(btnId);
        const saveText = document.getElementById(textId);
        const saveLoading = document.getElementById(loadingId);

        if (saveBtn && saveText && saveLoading) {
            saveBtn.disabled = true;
            saveText.classList.add('hidden');
            saveLoading.classList.remove('hidden');
        }
    });
}
