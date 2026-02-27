// Logo Upload Module
export function initLogoUpload() {
    const logoInput     = document.getElementById('logo');
    const logoPreview   = document.getElementById('logoPreview');
    const logoSubmitBtn = document.getElementById('logoSubmitBtn');

    if (!logoInput || !logoPreview || !logoSubmitBtn) return;

    logoInput.addEventListener('change', previewLogo);

    async function previewLogo(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG or PNG)');
            event.target.value = '';
            setSubmitDisabled(true);
            return;
        }

        // Compress to < 100 KB while preserving original dimensions
        const compressed = await compressTo100KB(file);

        // Swap the input's file with the compressed result
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressed);
        logoInput.files = dataTransfer.files;

        // Preview compressed image
        logoPreview.src = URL.createObjectURL(compressed);

        setSubmitDisabled(false);
    }

    function setSubmitDisabled(disabled) {
        logoSubmitBtn.disabled = disabled;
        logoSubmitBtn.classList.toggle('opacity-50',         disabled);
        logoSubmitBtn.classList.toggle('cursor-not-allowed', disabled);
    }
}

/**
 * Compresses an image File to under 100 KB using JPEG quality iteration.
 * Original pixel dimensions are always preserved — no downscaling.
 *
 * @param {File} file
 * @returns {Promise<File>}
 */
async function compressTo100KB(file) {
    const MAX = 100 * 1024; // 100 KB

    // Decode the file into an HTMLImageElement at full native resolution
    const objectUrl = URL.createObjectURL(file);
    const bitmap = await new Promise((resolve, reject) => {
        const img    = new Image();
        img.onload  = () => resolve(img);
        img.onerror = reject;
        img.src      = objectUrl;
    });
    URL.revokeObjectURL(objectUrl);

    // Draw at native resolution — dimensions never change
    const canvas  = document.createElement('canvas');
    canvas.width  = bitmap.naturalWidth;
    canvas.height = bitmap.naturalHeight;
    canvas.getContext('2d').drawImage(bitmap, 0, 0);

    const baseName = file.name.replace(/\.[^.]+$/, '');

    // Iterate quality downward until the blob fits within 100 KB
    let quality = 0.92;
    let blob;
    while (quality >= 0.01) {
        blob = await toBlob(canvas, 'image/jpeg', quality);
        if (blob.size <= MAX) break;
        quality = parseFloat((quality - 0.05).toFixed(2));
    }

    return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' });
}

function toBlob(canvas, type, quality) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(b => b ? resolve(b) : reject(new Error('toBlob failed')), type, quality);
    });
}
