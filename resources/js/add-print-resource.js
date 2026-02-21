/**
 * Add / Edit Print Resource Form Handler
 *
 * Works for both create (POST) and edit (PUT) modes.
 * In edit mode the blade injects window.__editAuthors before this script runs,
 * and the MultiAuthorInput is seeded from that array on init.
 */
import {
    setupImagePreview,
    setupFormSubmit
} from './add-resource-modules/form-utils.js';
import { MultiAuthorInput } from './add-resource-modules/multi-author.js';

export function initPrintResourceForm() {
    const form = document.getElementById('print');
    if (!form) return;

    // Image preview with crop & compress
    setupImagePreview('imageUpload', 'imagePreview');

    // Multi-author tag input
    const authorInput = new MultiAuthorInput(
        'author-input',
        'author-wrapper',
        'authors-hidden'
    );

    // Seed existing authors when in edit mode
    const existingAuthors = window.__editAuthors ?? [];
    existingAuthors.forEach(name => authorInput.addAuthor(name));

    // Submit button loading state
    setupFormSubmit(form, 'savePrintBtn', 'savePrintText', 'savePrintLoading');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrintResourceForm);
} else {
    initPrintResourceForm();
}
