/**
 * Non-Print Resource Form Handler
 * Handles image preview and form submit loading state only.
 * Acquisitions are added separately via the Search > Add Acquisition flow.
 */
import {
    setupImagePreview,
    setupFormSubmit
} from './add-resource-modules/form-utils.js';

export function initNonPrintResourceForm() {
    const form = document.getElementById('nonprintForm');
    if (!form) return;

    // Setup image preview
    setupImagePreview('nonprintImageUpload', 'nonprintImagePreview');

    // Setup form submit with loading state
    setupFormSubmit(form, 'saveNonPrintBtn', 'saveNonPrintText', 'saveNonPrintLoading');
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNonPrintResourceForm);
} else {
    initNonPrintResourceForm();
}
