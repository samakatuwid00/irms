/**
 * Manages acquisition list functionality for resource forms
 */
export class AcquisitionManager {
    constructor(form, config) {
        this.form = form;
        this.config = config;
        this.acquisitions = [];
        this.editIndex = null;

        this.tableBody = form.querySelector(config.tableBodySelector);
        this.hiddenInput = form.querySelector(config.hiddenInputSelector);
        this.addButton = form.querySelector(config.addButtonSelector);

        this.init();
    }

    init() {
        if (this.addButton) {
            this.addButton.addEventListener('click', () => this.handleAdd());
        }

        this.render();
    }

    /**
     * Get current form field values
     */
    getFieldValues() {
        const values = {};
        for (const key in this.config.fields) {
            const selector = this.config.fields[key];
            const element = this.form.querySelector(selector);
            if (element) {
                values[key] = element.value;
            }
        }
        return values;
    }

    /**
     * Reset form fields
     */
    resetFields() {
        for (const key in this.config.fields) {
            const selector = this.config.fields[key];
            const element = this.form.querySelector(selector);
            if (element) {
                // Reset to 0 for quantity fields, empty string for others
                if (key.includes('damaged') || key.includes('lost') || key.includes('condemnable') || key.includes('usable')) {
                    element.value = '0';
                } else {
                    element.value = '';
                }
            }
        }

        // Trigger calculation update if available
        if (this.config.onFieldsReset) {
            this.config.onFieldsReset();
        }
    }

    /**
     * Set form fields to acquisition values
     */
    setFieldValues(acquisition) {
        for (const key in this.config.fields) {
            const selector = this.config.fields[key];
            const element = this.form.querySelector(selector);
            if (element && acquisition[key] !== undefined) {
                element.value = acquisition[key] || (key.includes('damaged') || key.includes('lost') ? '0' : '');
            }
        }

        // Trigger calculation update if available
        if (this.config.onFieldsSet) {
            this.config.onFieldsSet();
        }
    }

    /**
     * Validate acquisition data
     */
    validate(acquisition) {
        if (!acquisition.source || !acquisition.date_acquired) {
            alert('Source and Date Acquired are required.');
            return false;
        }

        if ((parseInt(acquisition.total_quantity) || 0) < 1) {
            alert('Total Quantity must be at least 1.');
            return false;
        }

        return true;
    }

    /**
     * Handle add/update acquisition
     */
    handleAdd() {
        const acquisition = this.getFieldValues();

        if (!this.validate(acquisition)) {
            return;
        }

        if (this.editIndex !== null) {
            this.acquisitions[this.editIndex] = acquisition;
            this.editIndex = null;
        } else {
            this.acquisitions.push(acquisition);
        }

        this.render();
        this.resetFields();
        this.updateHiddenInput();
    }

    /**
     * Edit acquisition at index
     */
    edit(index) {
        const acquisition = this.acquisitions[index];
        if (!acquisition) return;

        this.editIndex = index;
        this.setFieldValues(acquisition);

        // Scroll to form
        const sourceField = this.form.querySelector(this.config.fields.source);
        if (sourceField) {
            sourceField.scrollIntoView({ behavior: 'smooth' });
        }
    }

    /**
     * Delete acquisition at index
     */
    delete(index) {
        if (!confirm('Delete this acquisition?')) return;

        this.acquisitions.splice(index, 1);
        this.render();
        this.updateHiddenInput();
    }

    /**
     * Render acquisitions table
     */
    render() {
        if (!this.tableBody) return;

        this.tableBody.innerHTML = '';

        if (this.acquisitions.length === 0) {
            this.tableBody.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center text-gray-400 py-3">
                        No acquisitions added
                    </td>
                </tr>
            `;
            return;
        }

        this.acquisitions.forEach((acquisition, index) => {
            const row = this.createRow(acquisition, index);
            this.tableBody.innerHTML += row;
        });
    }

    /**
     * Create table row HTML
     */
    createRow(acquisition, index) {
        const shortRemark = acquisition.remarks?.length > 40
            ? acquisition.remarks.substring(0, 37) + '...'
            : (acquisition.remarks || '-');

        return `
            <tr>
                <td class="border px-2 py-1">${acquisition.source || ''}</td>
                <td class="border px-2 py-1">${acquisition.date_acquired || ''}</td>
                <td class="border px-2 py-1">${acquisition.cost || ''}</td>
                <td class="border px-2 py-1">${acquisition.iar || ''}</td>
                <td class="border px-2 py-1 text-xs">${shortRemark}</td>
                <td class="border px-2 py-1">${acquisition.usable || 0}</td>
                <td class="border px-2 py-1">${acquisition.partially_damaged || 0}</td>
                <td class="border px-2 py-1">${acquisition.damaged || 0}</td>
                <td class="border px-2 py-1">${acquisition.lost || 0}</td>
                <td class="border px-2 py-1">${acquisition.condemnable || 0}</td>
                <td class="border px-2 py-1 font-semibold">${acquisition.total_quantity || 0}</td>
                <td class="border px-2 py-1 text-center">
                    <div class="flex justify-center gap-2">
                        <button type="button"
                            data-action="edit"
                            data-index="${index}"
                            class="p-1 rounded hover:bg-blue-100 text-blue-600"
                            title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                            </svg>
                        </button>
                        <button type="button"
                            data-action="delete"
                            data-index="${index}"
                            class="p-1 rounded hover:bg-red-100 text-red-600"
                            title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Update hidden input with JSON data
     */
    updateHiddenInput() {
        if (this.hiddenInput) {
            this.hiddenInput.value = JSON.stringify(this.acquisitions);
        }
    }

    /**
     * Setup event delegation for dynamic buttons
     */
    setupEventDelegation() {
        if (!this.tableBody) return;

        this.tableBody.addEventListener('click', (e) => {
            const button = e.target.closest('button[data-action]');
            if (!button) return;

            const action = button.dataset.action;
            const index = parseInt(button.dataset.index);

            if (action === 'edit') {
                this.edit(index);
            } else if (action === 'delete') {
                this.delete(index);
            }
        });
    }
}
