/**
 * image-preview.js  (edit resource)
 *
 * Replaces the old simple file-reader preview with the full
 * crop + zoom + compress flow from ImageCropperModal.
 *
 * Drop image-cropper.js into the same folder (edit-resource-modules/)
 * alongside this file — no other changes needed.
 *
 * Cancel behaviour:
 *   • Clears the <input type="file">
 *   • Restores the <img> src to whatever it was when the page loaded
 *     (i.e. the existing cover image, or the default placeholder)
 */
import { ImageCropperModal } from '../add-resource-modules/image-cropper.js';

// One cropper instance shared across both forms on the page
let _cropper = null;
function getCropper() {
    if (!_cropper) _cropper = new ImageCropperModal();
    return _cropper;
}

/**
 * Wire up crop + compress on a file input / preview pair.
 *
 * @param {HTMLInputElement|null} uploadInput  – the <input type="file">
 * @param {HTMLImageElement|null} previewImg   – the <img> preview element
 */
export function initImagePreview(uploadInput, previewImg) {
    if (!uploadInput || !previewImg) return;

    // Remember the original src so we can restore it on cancel
    const defaultSrc = previewImg.src || '';

    uploadInput.addEventListener('change', async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        // Type guard
        const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowed.includes(file.type)) {
            alert('Please select a valid image file (JPEG or PNG).');
            _reset(uploadInput, previewImg, defaultSrc);
            return;
        }

        // Raw size guard — we compress, but reject truly huge files up-front
        if (file.size > 20 * 1024 * 1024) {
            alert('File is too large (max 20 MB before compression).');
            _reset(uploadInput, previewImg, defaultSrc);
            return;
        }

        // Open the crop / zoom / compress modal
        const croppedFile = await getCropper().open(file);

        if (!croppedFile) {
            // User cancelled — restore original cover preview
            _reset(uploadInput, previewImg, defaultSrc);
            return;
        }

        // Inject the processed File back into the <input> so the form
        // submits the compressed JPEG instead of the original file
        try {
            const dt = new DataTransfer();
            dt.items.add(croppedFile);
            uploadInput.files = dt.files;
        } catch {
            // DataTransfer not supported (very old browsers) — the original
            // file will be submitted; the PHP validator accepts it fine.
        }

        // Show preview of the cropped + compressed result
        const reader = new FileReader();
        reader.onload = (e) => { previewImg.src = e.target.result; };
        reader.readAsDataURL(croppedFile);
    });
}

/** Clear input and restore the original cover image */
function _reset(input, img, defaultSrc) {
    input.value = '';
    img.src     = defaultSrc;
}
