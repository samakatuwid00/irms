import { initImagePreview }       from './edit-resource-modules/image-preview.js';
import { initGradeLevelTabs }     from './edit-resource-modules/grade-level-tabs.js';
import { initAcquisitionManager } from './edit-resource-modules/acquisition.js';
import { initSubmitLoading }      from './edit-resource-modules/submit-loading.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('nonprint-edit');
    if (!form) return;

    // ── Image preview ──────────────────────────────────────────────────────────
    initImagePreview(
        form.querySelector('#imageUploadNP'),
        form.querySelector('#imagePreviewNP'),
    );

    // ── Subject / grade-level tabs ─────────────────────────────────────────────
    initGradeLevelTabs(form);

    // ── Acquisitions manager ───────────────────────────────────────────────────
    // Existing acquisitions injected by Blade: window.__nonprintResourceData.acquisitions
    initAcquisitionManager(form, {
        tableBodyId:        'nonprintAcquisitionTableBody',
        acquisitionsInputId:'nonprintAcquisitionsInput',
        totalQuantityId:    'nonprintTotalQuantity',
        addBtnId:           'addNonPrintAcquisitionBtn',
        editGlobal:         'editNonPrintAcquisition',
        deleteGlobal:       'deleteNonPrintAcquisition',
        deleteConfirmMsg:   'Delete this acquisition? This will also remove associated masterlist entries.',
        initialData:        window.__nonprintResourceData?.acquisitions ?? [],
    });

    // ── Submit loading ─────────────────────────────────────────────────────────
    initSubmitLoading(form, 'updateNonPrintBtn', 'updateNonPrintText', 'updateNonPrintLoading');
});
