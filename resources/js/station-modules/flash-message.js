// Flash Message Module
export function initFlashMessage() {
    const alertBox = document.getElementById('alertBox');

    if (!alertBox) return;

    let alertTimeout = null;

    // Make closeAlert globally available for onclick handler
    window.closeAlert = function() {
        clearTimeout(alertTimeout);
        if (alertBox) alertBox.classList.add('hidden');
    };

    // Auto-hide after 6 seconds
    alertTimeout = setTimeout(() => {
        alertBox.classList.add('hidden');
    }, 6000);
}
