/**
 * Print Resource Form Handler
 */
import {
    setupImagePreview,
    setupTabs,
    setupQuantityCalculation,
    setupFormSubmit
} from './add-resource-modules/form-utils.js';
import { AcquisitionManager } from './add-resource-modules/acquisition-manager.js';
import { MultiAuthorInput } from './add-resource-modules/multi-author.js';

export function initPrintResourceForm() {
    const form = document.getElementById('print');
    if (!form) return;

    // Setup image preview
    setupImagePreview('imageUpload', 'imagePreview');

    // Setup tabs
    setupTabs(form);

    // Setup multi-author input
    const authorInput = new MultiAuthorInput(
        'author-input',
        'author-wrapper',
        'authors-hidden'
    );

    // Setup quantity calculation
    const calculateTotal = setupQuantityCalculation(form, 'totalQuantity');

    // Setup acquisition manager
    const acquisitionConfig = {
        tableBodySelector:  '#acquisitionTableBody',
        hiddenInputSelector: '#acquisitionsInput',
        addButtonSelector:  '#addAcquisitionBtn',
        fields: {
            // Library fields — captured per-acquisition so they go into the JSON
            library_id:        '#acq_library_id',
            library_name:      '#acq_library_name',
            // Standard acquisition fields
            source:            '[name="source"]',
            date_acquired:     '[name="date_acquired"]',
            cost:              '[name="cost"]',
            iar:               '[name="iar"]',
            remarks:           '[name="remarks"]',
            usable:            '[name="usable"]',
            partially_damaged: '[name="partially_damaged"]',
            damaged:           '[name="damaged"]',
            lost:              '[name="lost"]',
            condemnable:       '[name="condemnable"]',
            total_quantity:    '#totalQuantity',
        },
        onFieldsReset: calculateTotal,
        onFieldsSet:   calculateTotal,
    };

    const acquisitionManager = new AcquisitionManager(form, acquisitionConfig);

    // Override validate to also require library_id
    const originalValidate = acquisitionManager.validate.bind(acquisitionManager);
    acquisitionManager.validate = function (acquisition) {
        if (!acquisition.library_id) {
            alert('Please select a library.');
            return false;
        }
        return originalValidate(acquisition);
    };

    acquisitionManager.setupEventDelegation();

    // Expose edit and delete functions to global scope for backwards compatibility
    window.editPrintAcquisition  = (index) => acquisitionManager.edit(index);
    window.deletePrintAcquisition = (index) => acquisitionManager.delete(index);

    // Setup form submit with loading state
    setupFormSubmit(form, 'savePrintBtn', 'savePrintText', 'savePrintLoading');
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrintResourceForm);
} else {
    initPrintResourceForm();
}
