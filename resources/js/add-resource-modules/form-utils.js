/**
 * Shared utility functions for resource forms
 */
import { ImageCropperModal } from './image-cropper.js';

// Singleton cropper shared across all forms on the page
let _cropperInstance = null;
function getCropper() {
    if (!_cropperInstance) _cropperInstance = new ImageCropperModal();
    return _cropperInstance;
}

/**
 * Setup image preview with crop & compress.
 *
 * Flow:
 *  1. User selects a JPEG/PNG file (up to 20 MB raw)
 *  2. Crop + zoom modal opens
 *  3. On "Apply & Compress" → output is always < 1 MB JPEG
 *  4. On "Cancel" → input is cleared + preview restored to its default src
 *
 * To set a default/placeholder image, add data-default-src on the <img>:
 *   <img id="imagePreview" data-default-src="/images/placeholder.png" src="/images/placeholder.png">
 *
 * @param {string} uploadId  - ID of the <input type="file">
 * @param {string} previewId - ID of the <img> preview element
 */
export function setupImagePreview(uploadId, previewId) {
    const imageUpload  = document.getElementById(uploadId);
    const imagePreview = document.getElementById(previewId);

    if (!imageUpload || !imagePreview) return;

    // Store the initial src so we can restore it on cancel
    if (!imagePreview.dataset.defaultSrc) {
        imagePreview.dataset.defaultSrc = imagePreview.src || '';
    }

    imageUpload.addEventListener('change', async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        // Type check
        const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowed.includes(file.type)) {
            alert('Please select a valid image file (JPEG or PNG).');
            _resetInput(imageUpload, imagePreview);
            return;
        }

        // Raw size guard (we'll compress, but reject absurdly large files)
        if (file.size > 20 * 1024 * 1024) {
            alert('File is too large (max 20 MB before compression).');
            _resetInput(imageUpload, imagePreview);
            return;
        }

        // Open crop/zoom modal
        const croppedFile = await getCropper().open(file);

        if (!croppedFile) {
            // User cancelled → clear input + restore default preview
            _resetInput(imageUpload, imagePreview);
            return;
        }

        // Inject processed File back into <input> so form submits it
        try {
            const dt = new DataTransfer();
            dt.items.add(croppedFile);
            imageUpload.files = dt.files;
        } catch {
            // DataTransfer not supported in this browser — form will fall back
            // to the original file; the backend validator handles size/type.
        }

        // Show preview of the cropped + compressed result
        const reader = new FileReader();
        reader.onload = (e) => { imagePreview.src = e.target.result; };
        reader.readAsDataURL(croppedFile);
    });
}

/**
 * Clear the file input and restore the preview image to its default src.
 */
function _resetInput(input, preview) {
    input.value = '';
    preview.src = preview.dataset.defaultSrc || '';
}

/**
 * Setup tab functionality
 * @param {HTMLElement} form - The form element containing tabs
 */
export function setupTabs(form) {
    const tabs     = form.querySelectorAll('.tab-btn');
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
        if (target) target.classList.remove('hidden');
    }

    activateTab(tabs[0]);
    tabs.forEach(tab => tab.addEventListener('click', () => activateTab(tab)));
}

/**
 * Setup quantity calculation
 * @param {HTMLElement} form        - The form element
 * @param {string}      totalFieldId - ID of the total quantity field
 */
export function setupQuantityCalculation(form, totalFieldId) {
    const qtyInputs  = form.querySelectorAll('.qty');
    const totalField = document.getElementById(totalFieldId);

    if (!totalField) return;

    const calculateTotal = () => {
        let total = 0;
        qtyInputs.forEach(input => { total += parseInt(input.value) || 0; });
        totalField.value = total;
    };

    qtyInputs.forEach(input => input.addEventListener('input', calculateTotal));
    return calculateTotal;
}

/**
 * Setup form submit with loading state
 * @param {HTMLElement} form      - The form element
 * @param {string}      btnId     - ID of submit button
 * @param {string}      textId    - ID of button text element
 * @param {string}      loadingId - ID of loading indicator element
 */
export function setupFormSubmit(form, btnId, textId, loadingId) {
    form.addEventListener('submit', () => {
        const saveBtn     = document.getElementById(btnId);
        const saveText    = document.getElementById(textId);
        const saveLoading = document.getElementById(loadingId);

        if (saveBtn && saveText && saveLoading) {
            saveBtn.disabled = true;
            saveText.classList.add('hidden');
            saveLoading.classList.remove('hidden');
        }
    });
}
