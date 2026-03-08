
document.addEventListener('DOMContentLoaded', () => {
    initEditNonPrintResource(window.__nonprintAcquisitions ?? []);
});

export function initEditNonPrintResource(acquisitionsData) {
    let acquisitions = Array.isArray(acquisitionsData) ? acquisitionsData : [];
    let editIndex    = null;

    const tableBody      = document.getElementById('npAcquisitionTableBody');
    const hiddenInput    = document.getElementById('npAcquisitionsInput');
    const addBtn         = document.getElementById('npAddAcquisitionBtn');
    const totalField     = document.getElementById('npTotalQuantity');
    const form           = document.getElementById('nonprint-edit');
    const saveBtn        = document.getElementById('updateNonPrintBtn');
    const saveBtnText    = document.getElementById('updateNonPrintText');
    const saveBtnLoading = document.getElementById('updateNonPrintLoading');
    const libraryEl      = document.getElementById('npAcqLibraryId');

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
        document.querySelectorAll('.np-qty').forEach(inp => { total += parseInt(inp.value) || 0; });
        totalField.value = total;
    }

    function getFieldValues() {
        return {
            library_id:        getLibraryId(),
            library_name:      getLibraryName(),
            source:            document.getElementById('npAcqSource').value,
            date_acquired:     document.getElementById('npAcqDate').value,
            cost:              document.getElementById('npAcqCost').value,
            iar:               document.getElementById('npAcqIar').value,
            remarks:           document.getElementById('npAcqRemarks').value,
            usable:            document.getElementById('npAcqUsable').value,
            partially_damaged: document.getElementById('npAcqPartiallyDamaged').value,
            damaged:           document.getElementById('npAcqDamaged').value,
            lost:              document.getElementById('npAcqLost').value,
            condemnable:       document.getElementById('npAcqCondemnable').value,
            total_quantity:    totalField.value,
        };
    }

    function setFieldValues(acq) {
        if (libraryEl && libraryEl.tagName === 'SELECT' && acq.library_id) {
            libraryEl.value = acq.library_id;
        }
        document.getElementById('npAcqSource').value           = acq.source            ?? '';
        document.getElementById('npAcqDate').value             = acq.date_acquired      ?? '';
        document.getElementById('npAcqCost').value             = acq.cost               ?? '';
        document.getElementById('npAcqIar').value              = acq.iar                ?? '';
        document.getElementById('npAcqRemarks').value          = acq.remarks            ?? '';
        document.getElementById('npAcqUsable').value           = acq.usable             ?? '0';
        document.getElementById('npAcqPartiallyDamaged').value = acq.partially_damaged  ?? '0';
        document.getElementById('npAcqDamaged').value          = acq.damaged            ?? '0';
        document.getElementById('npAcqLost').value             = acq.lost               ?? '0';
        document.getElementById('npAcqCondemnable').value      = acq.condemnable        ?? '0';
        calcTotal();
    }

    function resetFields() {
        if (libraryEl && libraryEl.tagName === 'SELECT') libraryEl.selectedIndex = 0;
        document.getElementById('npAcqSource').value           = '';
        document.getElementById('npAcqDate').value             = '';
        document.getElementById('npAcqCost').value             = '';
        document.getElementById('npAcqIar').value              = '';
        document.getElementById('npAcqRemarks').value          = '';
        document.getElementById('npAcqUsable').value           = '0';
        document.getElementById('npAcqPartiallyDamaged').value = '0';
        document.getElementById('npAcqDamaged').value          = '0';
        document.getElementById('npAcqLost').value             = '0';
        document.getElementById('npAcqCondemnable').value      = '0';
        calcTotal();
    }

    function updateHidden() {
        hiddenInput.value = JSON.stringify(acquisitions);
    }

    function esc(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

    // ── Render table ─────────────────────────────────────────────────────────

    function render() {
        tableBody.innerHTML = '';

        if (!acquisitions.length) {
            tableBody.innerHTML =
                '<tr><td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added yet</td></tr>';
            return;
        }

        acquisitions.forEach((acq, idx) => {
            const shortRemark  = (acq.remarks?.length > 30)
                ? acq.remarks.substring(0, 27) + '...'
                : (acq.remarks || '-');
            const shortLibrary = (acq.library_name?.length > 25)
                ? acq.library_name.substring(0, 22) + '...'
                : (acq.library_name || '-');

            const row = document.createElement('tr');
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

    document.querySelectorAll('.np-qty').forEach(input => {
        input.addEventListener('input', calcTotal);
    });

    // ── Event: Add / Update acquisition ──────────────────────────────────────

    function handleAdd() {
        const acq = getFieldValues();

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
            if (acquisitions[editIndex].id) acq.id = acquisitions[editIndex].id;
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

    form.addEventListener('submit', () => {
        updateHidden();
        saveBtn.disabled = true;
        saveBtnText.classList.add('hidden');
        saveBtnLoading.classList.remove('hidden');
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    render();
}