// Logo Upload Module
export function initLogoUpload() {
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logoPreview');
    const logoSubmitBtn = document.getElementById('logoSubmitBtn');

    if (!logoInput || !logoPreview || !logoSubmitBtn) return;

    logoInput.addEventListener('change', previewLogo);

    function previewLogo(event) {
        if (event.target.files && event.target.files[0]) {
            const file = event.target.files[0];

            // Validate file size (2MB max)
            if (file.size > 2048 * 1024) {
                alert('File size must be less than 2MB');
                event.target.value = '';
                logoSubmitBtn.disabled = true;
                logoSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG or PNG)');
                event.target.value = '';
                logoSubmitBtn.disabled = true;
                logoSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            // Preview image
            logoPreview.src = URL.createObjectURL(file);

            // Enable submit button
            logoSubmitBtn.disabled = false;
            logoSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}
