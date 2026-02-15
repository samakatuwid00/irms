// Date Established Module
export function initDateEstablished() {
    const dateDisplay = document.getElementById('date_display');
    const dateInput = document.getElementById('date_input');

    if (!dateDisplay || !dateInput) return;

    // Make functions globally available for onclick handlers
    window.switchToDate = function() {
        dateDisplay.classList.add('hidden');
        dateInput.classList.remove('hidden');
        dateInput.focus();
    };

    window.switchToText = function() {
        dateInput.classList.add('hidden');
        dateDisplay.classList.remove('hidden');
        updateReadableDate();
    };

    window.updateReadableDate = function() {
        if (dateInput.value) {
            const date = new Date(dateInput.value);
            dateDisplay.value = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } else {
            dateDisplay.value = '';
        }
    };
}
