document.addEventListener('DOMContentLoaded', () => {

    /* ------------------------------------------------------------------ */
    /*  State                                                               */
    /* ------------------------------------------------------------------ */
    const acquisitions = [];
    let editIndex = null;

    /* ------------------------------------------------------------------ */
    /*  DOM References                                                      */
    /* ------------------------------------------------------------------ */
    const tableBody         = document.getElementById('npAcquisitionTableBody');
    const hiddenInput       = document.getElementById('npAcquisitionsInput');
    const addBtn            = document.getElementById('addAcquisitionBtn');
    const totalField        = document.getElementById('npTotalQuantity');
    const form              = document.getElementById('addNonPrintAcquisitionForm');
    const saveBtn           = document.getElementById('npSaveBtn');
    const saveBtnText       = document.getElementById('npSaveBtnText');
    const saveBtnLoading    = document.getElementById('npSaveBtnLoading');
    const libraryEl         = document.getElementById('acqLibraryId');

    // Package Search Fields
    const packageSearchInput = document.getElementById('acqPackageSearch');
    const hiddenPackageId    = document.getElementById('acqPackage');

    // Guard: Exit if form doesn't exist on this page
    if (!form) return;

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */
    function getLibraryId() {
        return libraryEl ? libraryEl.value : '';
    }

    function getLibraryName() {
        if (!libraryEl) return '';
        if (libraryEl.tagName === 'SELECT') {
            const opt = libraryEl.options[libraryEl.selectedIndex];
            return opt ? (opt.dataset.name || opt.textContent) : '';
        }
        return libraryEl.dataset.name || '';
    }

    /**
     * Returns package_id only if user selected from suggestions.
     * Returns null if user typed custom text without selecting.
     */
    function getPackageId() {
        if (hiddenPackageId && hiddenPackageId.value.trim() !== '') {
            return hiddenPackageId.value;
        }
        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  Quantity Calculation                                                */
    /* ------------------------------------------------------------------ */
    document.querySelectorAll('.qty').forEach(input => {
        input.addEventListener('input', calcTotal);
    });

    function calcTotal() {
        let total = 0;
        document.querySelectorAll('.qty').forEach(inp => {
            total += parseInt(inp.value) || 0;
        });
        totalField.value = total;
    }

    /* ------------------------------------------------------------------ */
    /*  Get Field Values (including package_id logic)                      */
    /* ------------------------------------------------------------------ */
    function getFieldValues() {
        return {
            library_id:        getLibraryId(),
            library_name:      getLibraryName(),
            package_id:        getPackageId(),
            package_name:      packageSearchInput ? packageSearchInput.value.trim() : '', 
            source:            document.getElementById('acqSource').value,
            date_acquired:     document.getElementById('acqDate').value,
            cost:              document.getElementById('acqCost').value,
            iar:               document.getElementById('acqIar').value,
            remarks:           document.getElementById('acqRemarks').value || null,
            usable:            document.getElementById('acqUsable').value || '0',
            partially_damaged: document.getElementById('acqPartiallyDamaged').value || '0',
            damaged:           document.getElementById('acqDamaged').value || '0',
            lost:              document.getElementById('acqLost').value || '0',
            condemnable:       document.getElementById('acqCondemnable').value || '0',
            total_quantity:    totalField.value || '0',
        };
    }

    /* ------------------------------------------------------------------ */
    /*  Set Field Values (for Edit)                                         */
    /* ------------------------------------------------------------------ */
    function setFieldValues(acq) {
        if (libraryEl && libraryEl.tagName === 'SELECT' && acq.library_id) {
            libraryEl.value = acq.library_id;
        }

        // Set package fields for editing
        if (packageSearchInput && hiddenPackageId) {
            hiddenPackageId.value = acq.package_id || '';
            packageSearchInput.value = acq.package_name || '';
        }

        document.getElementById('acqSource').value           = acq.source || '';
        document.getElementById('acqDate').value             = acq.date_acquired || '';
        document.getElementById('acqCost').value             = acq.cost || '';
        document.getElementById('acqIar').value              = acq.iar || '';
        document.getElementById('acqRemarks').value          = acq.remarks || '';
        document.getElementById('acqUsable').value           = acq.usable || '0';
        document.getElementById('acqPartiallyDamaged').value = acq.partially_damaged || '0';
        document.getElementById('acqDamaged').value          = acq.damaged || '0';
        document.getElementById('acqLost').value             = acq.lost || '0';
        document.getElementById('acqCondemnable').value      = acq.condemnable || '0';

        calcTotal();
    }

    /* ------------------------------------------------------------------ */
    /*  Reset Fields                                                        */
    /* ------------------------------------------------------------------ */
    function resetFields() {
        if (libraryEl && libraryEl.tagName === 'SELECT') {
            libraryEl.selectedIndex = 0;
        }

        // Reset Package Search
        if (packageSearchInput) packageSearchInput.value = '';
        if (hiddenPackageId) hiddenPackageId.value = '';

        document.getElementById('acqSource').value           = '';
        document.getElementById('acqDate').value             = '';
        document.getElementById('acqCost').value             = '';
        document.getElementById('acqIar').value              = '';
        document.getElementById('acqRemarks').value          = '';
        document.getElementById('acqUsable').value           = '0';
        document.getElementById('acqPartiallyDamaged').value = '0';
        document.getElementById('acqDamaged').value          = '0';
        document.getElementById('acqLost').value             = '0';
        document.getElementById('acqCondemnable').value      = '0';

        calcTotal();
    }

    /* ------------------------------------------------------------------ */
    /*  Add / Edit Acquisition                                              */
    /* ------------------------------------------------------------------ */
    addBtn.addEventListener('click', handleAdd);

    function handleAdd() {
        const acq = getFieldValues();

        // Validation
        if (!acq.library_id) {
            alert('Please select a library.');
            return;
        }
        if (!acq.source || !acq.date_acquired) {
            alert('Source and Date Acquired are required.');
            return;
        }
        if (parseInt(acq.total_quantity) < 1) {
            alert('Total Quantity must be at least 1.');
            return;
        }

        if (editIndex !== null) {
            acquisitions[editIndex] = acq;
            editIndex = null;
            addBtn.textContent = '➕ Add Acquisition';
        } else {
            acquisitions.push(acq);
        }

        render();
        resetFields();
        updateHidden();
    }

    /* ------------------------------------------------------------------ */
    /*  Edit & Delete                                                       */
    /* ------------------------------------------------------------------ */
    function editAcq(index) {
        const acq = acquisitions[index];
        if (!acq) return;

        editIndex = index;
        addBtn.textContent = '✔ Update Acquisition';
        setFieldValues(acq);
        libraryEl?.scrollIntoView({ behavior: 'smooth' });
    }

    function deleteAcq(index) {
        if (!confirm('Remove this acquisition?')) return;

        acquisitions.splice(index, 1);
        if (editIndex === index) {
            editIndex = null;
            addBtn.textContent = '➕ Add Acquisition';
        }

        render();
        updateHidden();
    }

    /* ------------------------------------------------------------------ */
    /*  Render Table                                                        */
    /* ------------------------------------------------------------------ */
    function render() {
        tableBody.innerHTML = '';

        if (acquisitions.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="14" class="text-center text-gray-400 py-8">
                        No acquisitions added yet
                    </td>
                </tr>`;
            return;
        }

        acquisitions.forEach((acq, idx) => {
            const shortLibrary = acq.library_name?.length > 25 
                ? acq.library_name.substring(0, 22) + '...' 
                : (acq.library_name || '-');

            const shortRemark = acq.remarks?.length > 30 
                ? acq.remarks.substring(0, 27) + '...' 
                : (acq.remarks || '-');

            const packageDisplay = acq.package_name
                ? `<span class="text-gray-800">${esc(acq.package_name)}</span>`
                : `<span class="text-gray-400 italic">No package</span>`;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="border px-2 py-1 text-xs" title="${esc(acq.library_name)}">${esc(shortLibrary)}</td>
                <td class="border px-2 py-1 text-xs">${packageDisplay}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.source)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.date_acquired)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.cost) || '-'}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.iar) || '-'}</td>
                <td class="border px-2 py-1 text-xs">${esc(shortRemark)}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.usable || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.partially_damaged || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.damaged || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.lost || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.condemnable || 0}</td>
                <td class="border px-2 py-1 text-center text-xs font-semibold">${acq.total_quantity || 0}</td>
                <td class="border px-2 py-1 text-center">
                    <div class="flex justify-center gap-2">
                        <button type="button" data-action="edit" data-index="${idx}"
                            class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="Edit">✏</button>
                        <button type="button" data-action="delete" data-index="${idx}"
                            class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">🗑</button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Event Delegation for Edit & Delete                                  */
    /* ------------------------------------------------------------------ */
    tableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const index = parseInt(btn.dataset.index);
        if (btn.dataset.action === 'edit') {
            editAcq(index);
        } else if (btn.dataset.action === 'delete') {
            deleteAcq(index);
        }
    });

    /* ------------------------------------------------------------------ */
    /*  Hidden Input & Form Submit                                          */
    /* ------------------------------------------------------------------ */
    function updateHidden() {
        hiddenInput.value = JSON.stringify(acquisitions);
    }

    form.addEventListener('submit', (e) => {
        if (acquisitions.length === 0) {
            e.preventDefault();
            alert('Please add at least one acquisition before saving.');
            return;
        }

        updateHidden();
        saveBtn.disabled = true;
        saveBtnText.classList.add('hidden');
        saveBtnLoading.classList.remove('hidden');
    });

    /* ------------------------------------------------------------------ */
    /*  Utility                                                             */
    /* ------------------------------------------------------------------ */
    function esc(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

});