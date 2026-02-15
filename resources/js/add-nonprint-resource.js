/**
 * Non-Print Resource Form Handler
 */
import {
    setupImagePreview,
    setupTabs,
    setupQuantityCalculation,
    setupFormSubmit
} from './add-resource-modules/form-utils.js';
import { AcquisitionManager } from './add-resource-modules/acquisition-manager.js';

export function initNonPrintResourceForm() {
    const form = document.getElementById('nonprintForm');
    if (!form) return;

    // Setup image preview
    setupImagePreview('nonprintImageUpload', 'nonprintImagePreview');

    // Setup tabs
    setupTabs(form);

    // Setup quantity calculation
    const calculateTotal = setupQuantityCalculation(form, 'nonprintTotalQuantity');

    // Setup acquisition manager
    const acquisitionConfig = {
        tableBodySelector: '#nonprintAcquisitionTableBody',
        hiddenInputSelector: '#nonprintAcquisitionsInput',
        addButtonSelector: '#addNonPrintAcquisitionBtn',
        fields: {
            source: '[name="source"]',
            date_acquired: '[name="date_acquired"]',
            cost: '[name="cost"]',
            iar: '[name="iar"]',
            remarks: '[name="remarks"]',
            usable: '[name="usable"]',
            partially_damaged: '[name="partially_damaged"]',
            damaged: '[name="damaged"]',
            lost: '[name="lost"]',
            condemnable: '[name="condemnable"]',
            total_quantity: '#nonprintTotalQuantity'
        },
        onFieldsReset: calculateTotal,
        onFieldsSet: calculateTotal
    };

    const acquisitionManager = new AcquisitionManager(form, acquisitionConfig);
    acquisitionManager.setupEventDelegation();

    // Expose edit and delete functions to global scope for backwards compatibility
    window.editNonPrintAcquisition = (index) => acquisitionManager.edit(index);
    window.deleteNonPrintAcquisition = (index) => acquisitionManager.delete(index);

    // Setup form submit with loading state
    setupFormSubmit(form, 'saveNonPrintBtn', 'saveNonPrintText', 'saveNonPrintLoading');
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNonPrintResourceForm);
} else {
    initNonPrintResourceForm();
}
