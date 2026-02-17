
import { initImagePreview }       from './edit-resource-modules/image-preview.js';
import { initGradeLevelTabs }     from './edit-resource-modules/grade-level-tabs.js';
import { initAcquisitionManager } from './edit-resource-modules/acquisition.js';
import { initSubmitLoading }      from './edit-resource-modules/submit-loading.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('print-edit');
    if (!form) return;

    // ── Image preview ──────────────────────────────────────────────────────────
    initImagePreview(
        form.querySelector('#imageUpload'),
        form.querySelector('#imagePreview'),
    );

    // ── Subject / grade-level tabs ─────────────────────────────────────────────
    initGradeLevelTabs(form);

    // ── Multi-author tag input ─────────────────────────────────────────────────
    const authorInput = document.getElementById('author-input');
    const wrapper     = document.getElementById('author-wrapper');
    const hiddenInput = document.getElementById('authors-hidden');

    // Authors are injected by Blade: window.__printResourceData.authors
    let authors = (window.__printResourceData?.authors ?? []).slice();

    const refreshHiddenInput = () => {
        hiddenInput.value = JSON.stringify(authors);
    };

    const createTag = (name) => {
        const tag = document.createElement('span');
        tag.className = 'flex items-center bg-blue-100 text-blue-700 px-2 py-1 rounded text-sm';
        tag.innerHTML = `${name}<button type="button" class="ml-1 text-blue-700 hover:text-red-600">&times;</button>`;
        tag.querySelector('button').addEventListener('click', () => {
            authors = authors.filter(a => a !== name);
            refreshHiddenInput();
            tag.remove();
        });
        return tag;
    };

    const addAuthor = (name) => {
        if (!name || authors.includes(name)) return;
        authors.push(name);
        refreshHiddenInput();
        wrapper.insertBefore(createTag(name), authorInput);
        authorInput.value = '';
    };

    // Render pre-existing authors
    authors.forEach(name => wrapper.insertBefore(createTag(name), authorInput));
    refreshHiddenInput();

    authorInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addAuthor(authorInput.value.trim());
        }
    });

    // ── Quantity total calculation ─────────────────────────────────────────────
    const qtyInputs = form.querySelectorAll('.qty');
    const totalField = form.querySelector('#totalQuantity');

    const calculateTotal = () => {
        let total = 0;
        qtyInputs.forEach(input => { total += parseInt(input.value) || 0; });
        totalField.value = total;
    };

    qtyInputs.forEach(input => input.addEventListener('input', calculateTotal));

    // ── Acquisitions manager ───────────────────────────────────────────────────
    // Existing acquisitions injected by Blade: window.__printResourceData.acquisitions
    initAcquisitionManager(form, {
        tableBodyId:        'acquisitionTableBody',
        acquisitionsInputId:'acquisitionsInput',
        totalQuantityId:    'totalQuantity',
        addBtnId:           'addAcquisitionBtn',
        editGlobal:         'editPrintAcquisition',
        deleteGlobal:       'deletePrintAcquisition',
        deleteConfirmMsg:   'Delete this acquisition?',
        initialData:        window.__printResourceData?.acquisitions ?? [],
    });

    // ── Submit loading ─────────────────────────────────────────────────────────
    initSubmitLoading(form, 'updatePrintBtn', 'updatePrintText', 'updatePrintLoading');
});
