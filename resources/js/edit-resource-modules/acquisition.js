export function initAcquisitionManager(form, options) {
    const {
        tableBodyId,
        acquisitionsInputId,
        totalQuantityId,
        addBtnId,
        editGlobal,
        deleteGlobal,
        deleteConfirmMsg = 'Delete this acquisition?',
        initialData = [],
    } = options;

    let acquisitions = [...initialData];
    let editIndex = null;

    const tableBody       = form.querySelector(`#${tableBodyId}`);
    const acquisitionsInput = form.querySelector(`#${acquisitionsInputId}`);
    const totalField      = form.querySelector(`#${totalQuantityId}`);

    // ── Field readers ─────────────────────────────────────────────────────────
    const fields = {
        source:           () => form.querySelector('[name="source"]').value,
        date_acquired:    () => form.querySelector('[name="date_acquired"]').value,
        cost:             () => form.querySelector('[name="cost"]').value,
        iar:              () => form.querySelector('[name="iar"]').value,
        remarks:          () => form.querySelector('[name="remarks"]').value.trim(),
        usable:           () => form.querySelector('[name="usable"]').value,
        partially_damaged:() => form.querySelector('[name="partially_damaged"]').value,
        damaged:          () => form.querySelector('[name="damaged"]').value,
        lost:             () => form.querySelector('[name="lost"]').value,
        condemnable:      () => form.querySelector('[name="condemnable"]').value,
        total_quantity:   () => totalField.value,
    };

    // ── Quantity total ────────────────────────────────────────────────────────
    const qtyInputs = form.querySelectorAll('.qty');

    const calculateTotal = () => {
        let total = 0;
        qtyInputs.forEach(input => { total += parseInt(input.value) || 0; });
        totalField.value = total;
    };

    qtyInputs.forEach(input => input.addEventListener('input', calculateTotal));

    // ── Render table ──────────────────────────────────────────────────────────
    const render = () => {
        tableBody.innerHTML = '';

        if (acquisitions.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="12" class="text-center text-gray-400 py-3">No acquisitions added</td></tr>`;
            return;
        }

        acquisitions.forEach((a, index) => {
            const shortRemark = a.remarks && a.remarks.length > 40
                ? a.remarks.substring(0, 37) + '...'
                : a.remarks || '-';

            tableBody.innerHTML += `
                <tr>
                    <td class="border border-gray-300 px-2 py-1">${a.source}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.date_acquired}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.cost || '-'}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.iar || '-'}</td>
                    <td class="border border-gray-300 px-2 py-1 text-xs">${shortRemark}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.usable}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.partially_damaged}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.damaged}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.lost}</td>
                    <td class="border border-gray-300 px-2 py-1">${a.condemnable}</td>
                    <td class="border border-gray-300 px-2 py-1 font-semibold">${a.total_quantity}</td>
                    <td class="border border-gray-300 px-2 py-1 text-center">
                        <div class="flex justify-center gap-2">
                            <button type="button"
                                onclick="${editGlobal}(${index})"
                                class="p-1 rounded hover:bg-blue-100 text-blue-600"
                                title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153
                                        3 21l1.847-4.5L16.862 4.487z"/>
                                </svg>
                            </button>
                            <button type="button"
                                onclick="${deleteGlobal}(${index})"
                                class="p-1 rounded hover:bg-red-100 text-red-600"
                                title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                        a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                                        M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });
    };

    // ── Reset form inputs ─────────────────────────────────────────────────────
    const reset = () => {
        form.querySelector('[name="remarks"]').value       = '';
        form.querySelector('[name="source"]').value        = '';
        form.querySelector('[name="date_acquired"]').value = '';
        form.querySelector('[name="cost"]').value          = '';
        form.querySelector('[name="iar"]').value           = '';
        form.querySelector('[name="usable"]').value        = 0;
        form.querySelector('[name="partially_damaged"]').value = 0;
        form.querySelector('[name="damaged"]').value       = 0;
        form.querySelector('[name="lost"]').value          = 0;
        form.querySelector('[name="condemnable"]').value   = 0;
        if (totalField) totalField.value                   = 0;
    };

    // ── Add / update acquisition ──────────────────────────────────────────────
    form.querySelector(`#${addBtnId}`).addEventListener('click', () => {
        const acquisition = {};
        for (const key in fields) acquisition[key] = fields[key]();

        if (!acquisition.source || !acquisition.date_acquired) {
            alert('Source and Date Acquired are required.');
            return;
        }
        if ((parseInt(acquisition.total_quantity) || 0) < 1) {
            alert('Total Quantity must be at least 1.');
            return;
        }

        if (editIndex !== null) {
            if (acquisitions[editIndex].id) {
                acquisition.id = acquisitions[editIndex].id;
            }
            acquisitions[editIndex] = acquisition;
            editIndex = null;
        } else {
            acquisitions.push(acquisition);
        }

        render();
        reset();
    });

    // ── Edit row (exposed on window for inline onclick) ───────────────────────
    window[editGlobal] = (index) => {
        const b = acquisitions[index];
        editIndex = index;
        form.querySelector('[name="source"]').value             = b.source;
        form.querySelector('[name="date_acquired"]').value      = b.date_acquired;
        form.querySelector('[name="cost"]').value               = b.cost || '';
        form.querySelector('[name="iar"]').value                = b.iar || '';
        form.querySelector('[name="remarks"]').value            = b.remarks || '';
        form.querySelector('[name="usable"]').value             = b.usable;
        form.querySelector('[name="partially_damaged"]').value  = b.partially_damaged || 0;
        form.querySelector('[name="damaged"]').value            = b.damaged;
        form.querySelector('[name="lost"]').value               = b.lost;
        form.querySelector('[name="condemnable"]').value        = b.condemnable;
        calculateTotal();
        form.querySelector('[name="source"]').scrollIntoView({ behavior: 'smooth' });
    };

    // ── Delete row (exposed on window for inline onclick) ─────────────────────
    window[deleteGlobal] = (index) => {
        if (!confirm(deleteConfirmMsg)) return;
        acquisitions.splice(index, 1);
        render();
    };

    // ── Serialise on submit ───────────────────────────────────────────────────
    form.addEventListener('submit', () => {
        acquisitionsInput.value = JSON.stringify(acquisitions);
    });

    // Initial paint
    render();
}
