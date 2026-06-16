/**
 * edit-print-resource.js
 * Handles the acquisition list logic for the Edit Print Resource form.
 *
 * Only the current user's library acquisitions are shown and editable in the table.
 * Acquisitions belonging to other libraries are passed through silently on save
 * (via window.__otherAcquisitions) so the service layer does not delete them.
 */

document.addEventListener('DOMContentLoaded', () => {
    initEditPrintResource(
        window.__printAcquisitions ?? [],
        window.__otherAcquisitions ?? [],
    );
});

export function initEditPrintResource(acquisitionsData, otherAcquisitionsData = []) {
    // Editable rows — only this user's library(ies)
    let acquisitions = Array.isArray(acquisitionsData) ? acquisitionsData : [];

    // Read-only pass-through rows — other libraries; never displayed, always included on save
    const otherAcquisitions = Array.isArray(otherAcquisitionsData) ? otherAcquisitionsData : [];

    let editIndex = null;

    const tableBody      = document.getElementById('acquisitionTableBody');
    const hiddenInput    = document.getElementById('acquisitionsInput');
    const addBtn         = document.getElementById('addAcquisitionBtn');
    const totalField     = document.getElementById('totalQuantity');
    const form           = document.getElementById('print-edit');
    const saveBtn        = document.getElementById('updatePrintBtn');
    const saveBtnText    = document.getElementById('updatePrintText');
    const saveBtnLoading = document.getElementById('updatePrintLoading');
    const libraryEl      = document.getElementById('acqLibraryId');
    const otherNotice    = document.getElementById('otherLibraryNotice');

    // Show the informational banner if there are other-library rows
    if (otherNotice && otherAcquisitions.length > 0) {
        otherNotice.classList.remove('hidden');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    function getLibraryId() {
        return libraryEl ? libraryEl.value : '';
    }

    function getLibraryName() {
        if (!libraryEl) return '';
        if (libraryEl.tagName === 'SELECT') {
            const opt = libraryEl.options[libraryEl.selectedIndex];
            return opt ? (opt.dataset.name || opt.text) : '';
        }
        return libraryEl.dataset.name || '';
    }

    function calcTotal() {
        let total = 0;
        document.querySelectorAll('.qty').forEach(inp => { total += parseInt(inp.value) || 0; });
        totalField.value = total;
    }

    function getFieldValues(preserveId = null) {
        const data = {
            library_id:        getLibraryId(),
            library_name:      getLibraryName(),
            source:            document.getElementById('acqSource').value,
            date_acquired:     document.getElementById('acqDate').value,
            cost:              document.getElementById('acqCost').value,
            iar:               document.getElementById('acqIar').value,
            remarks:           document.getElementById('acqRemarks').value,
            usable:            document.getElementById('acqUsable').value,
            partially_damaged: document.getElementById('acqPartiallyDamaged').value,
            damaged:           document.getElementById('acqDamaged').value,
            lost:              document.getElementById('acqLost').value,
            condemnable:       document.getElementById('acqCondemnable').value,
            total_quantity:    totalField.value,
        };
        if (preserveId) {
            data.id = preserveId;
        }
        return data;
    }

    function setFieldValues(acq) {
        if (libraryEl && libraryEl.tagName === 'SELECT' && acq.library_id) {
            libraryEl.value = acq.library_id;
        }
        document.getElementById('acqSource').value           = acq.source            ?? '';
        document.getElementById('acqDate').value             = acq.date_acquired      ?? '';
        document.getElementById('acqCost').value             = acq.cost               ?? '';
        document.getElementById('acqIar').value              = acq.iar                ?? '';
        document.getElementById('acqRemarks').value          = acq.remarks            ?? '';
        document.getElementById('acqUsable').value           = acq.usable             ?? '0';
        document.getElementById('acqPartiallyDamaged').value = acq.partially_damaged  ?? '0';
        document.getElementById('acqDamaged').value          = acq.damaged            ?? '0';
        document.getElementById('acqLost').value             = acq.lost               ?? '0';
        document.getElementById('acqCondemnable').value      = acq.condemnable        ?? '0';
        calcTotal();
    }

    function resetFields() {
        if (libraryEl && libraryEl.tagName === 'SELECT') libraryEl.selectedIndex = 0;
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

    /**
     * Merge the user's (editable) acquisitions with the other-library (pass-through)
     * rows before writing to the hidden input.  The service layer will update the
     * editable rows and leave the pass-through rows unchanged.
     */
    function updateHidden() {
        hiddenInput.value = JSON.stringify([...acquisitions, ...otherAcquisitions]);
    }

    /**
     * Validate data integrity: ensure all acquisitions have required fields.
     */
    function validateAcquisitionsData() {
        const issues = [];
        acquisitions.forEach((acq, idx) => {
            if (!acq.library_id)    issues.push(`Row ${idx + 1}: Missing library_id`);
            if (!acq.source)        issues.push(`Row ${idx + 1}: Missing source`);
            if (!acq.date_acquired) issues.push(`Row ${idx + 1}: Missing date_acquired`);
        });
        return issues;
    }

    function esc(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

    // ── Render table ─────────────────────────────────────────────────────────
    // Only the current user's library acquisitions are rendered.
    // Other-library rows are intentionally excluded from the table.

    function render() {
        tableBody.innerHTML = '';

        if (!acquisitions.length) {
            tableBody.innerHTML =
                '<tr><td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added yet</td></tr>';
            return;
        }

        const sortedAcquisitions = [...acquisitions].sort(
            (a, b) => (b.date_acquired || '').localeCompare(a.date_acquired || '')
        );

        sortedAcquisitions.forEach((acq) => {
            const idx = acquisitions.indexOf(acq);
            const shortRemark  = (acq.remarks?.length > 30)
                ? acq.remarks.substring(0, 27) + '...'
                : (acq.remarks || '-');
            const shortLibrary = (acq.library_name?.length > 25)
                ? acq.library_name.substring(0, 22) + '...'
                : (acq.library_name || '-');

            const row = document.createElement('tr');
            row.setAttribute('data-id', acq.id || '');
            row.setAttribute('data-index', idx);
            row.innerHTML = `
                <td class="border px-2 py-1 text-xs" title="${esc(acq.library_name)}">${esc(shortLibrary)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.source)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.date_acquired)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.cost) || '-'}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.iar) || '-'}</td>
                <td class="border px-2 py-1 text-xs" title="${esc(acq.remarks)}">${esc(shortRemark)}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.usable || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.partially_damaged || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.damaged || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.lost || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.condemnable || 0}</td>
                <td class="border px-2 py-1 text-center text-xs font-semibold">${acq.total_quantity || 0}</td>
                <td class="border px-2 py-1 text-center">
                    <div class="flex justify-center gap-1">
                        <button type="button" data-action="edit" data-index="${idx}"
                                class="p-1 rounded hover:bg-blue-100 text-blue-600" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                            </svg>
                        </button>
                        <button type="button" data-action="delete" data-index="${idx}"
                                class="p-1 rounded hover:bg-red-100 text-red-600" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                    a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                                    M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                            </svg>
                        </button>
                    </div>
                </td>`;
            tableBody.appendChild(row);
        });
    }

    // ── Event: quantity inputs → auto-total ───────────────────────────────────

    document.querySelectorAll('.qty').forEach(input => {
        input.addEventListener('input', calcTotal);
    });

    // ── Event: Add / Update acquisition ──────────────────────────────────────

    function handleAdd() {
        const acq = getFieldValues(editIndex !== null ? acquisitions[editIndex].id : null);

        if (!acq.library_id) {
            alert('Please select a library.');
            return;
        }
        if (!acq.source || !acq.date_acquired) {
            alert('Source and Date Acquired are required.');
            return;
        }
        if ((parseInt(acq.total_quantity) || 0) < 1) {
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

    addBtn.addEventListener('click', handleAdd);

    // ── Event: Edit / Delete row buttons ─────────────────────────────────────

    tableBody.addEventListener('click', e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const idx = parseInt(btn.dataset.index);

        if (btn.dataset.action === 'edit') {
            editIndex = idx;
            addBtn.textContent = '✔ Update Acquisition';
            setFieldValues(acquisitions[idx]);
            libraryEl?.scrollIntoView({ behavior: 'smooth' });
        } else if (btn.dataset.action === 'delete') {
            if (!confirm('Remove this acquisition?')) return;
            acquisitions.splice(idx, 1);
            if (editIndex === idx) {
                editIndex = null;
                addBtn.textContent = '➕ Add Acquisition';
            }
            render();
            updateHidden();
        }
    });

    // ── Event: Form submit ────────────────────────────────────────────────────

    form.addEventListener('submit', (e) => {
        const validationIssues = validateAcquisitionsData();
        if (validationIssues.length > 0) {
            alert('Data validation failed:\n' + validationIssues.join('\n'));
            e.preventDefault();
            return;
        }

        updateHidden();
        saveBtn.disabled = true;
        saveBtnText.classList.add('hidden');
        saveBtnLoading.classList.remove('hidden');
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    render();
    updateHidden(); // pre-populate hidden input so a save with no changes still passes through all rows
}