
import {
    setupImagePreview,
    setupFormSubmit
} from './add-resource-modules/form-utils.js';
import { MultiAuthorInput } from './add-resource-modules/multi-author.js';

export function initPrintResourceForm() {
    const form = document.getElementById('print');
    if (!form) return;

    setupImagePreview('imageUpload', 'imagePreview');

    const authorInput = new MultiAuthorInput(
        'author-input',
        'author-wrapper',
        'authors-hidden'
    );

    const existingAuthors = window.__editAuthors ?? [];
    existingAuthors.forEach(name => authorInput.addAuthor(name));

    setupFormSubmit(form, 'savePrintBtn', 'savePrintText', 'savePrintLoading');

    // Flush unregistered author text before submit
    form.addEventListener('submit', function () {
        authorInput.flushInput();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrintResourceForm);
} else {
    initPrintResourceForm();
}
