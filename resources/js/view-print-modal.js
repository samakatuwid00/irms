// ─── Modal Logic ─────────────────────────────────────────────────────────────

/**
 * Open the Print Resource modal and populate it with the given resource data.
 *
 * @param {Object} resource
 * @param {string}   resource.image
 * @param {string}   resource.title
 * @param {string}   resource.author
 * @param {string}   resource.publisher
 * @param {string}   resource.type
 * @param {string}   resource.isbn
 * @param {string}   resource.copyright
 * @param {string}   resource.pages
 * @param {Array}    resource.subjects      - [{ subject, grade }]
 * @param {Array}    resource.acquisitions  - [{ library_name, source, date_acquired, cost, iar, remarks, usable, partially_damaged, damaged, lost, condemnable }]
 */
export function openPrintModal(resource) {
    const safeInt = value => parseInt(value || 0, 10);
    const formatCost = cost => {
        if (!cost) return '-';
        return '₱' + parseFloat(cost).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    // ── Read user level from table data attribute ──────────────────────────
    const table = document.getElementById('printAcquisitionTable');
    const userLevel = parseInt(table?.dataset?.userLevel || 0, 10);
    const isLevel4 = userLevel === 4;

    const DEFAULT_IMAGE = '/assets/images/default.jpg';

    // ── Image ──────────────────────────────────────────────────────────────
    const imgElement = document.getElementById('printImage');
    imgElement.src = resource.image || DEFAULT_IMAGE;
    imgElement.alt = resource.title || 'Book Cover';
    imgElement.onerror = function () {
        this.src = DEFAULT_IMAGE;
        this.onerror = null; // prevent infinite loop
    };

    // ── Basic Info ─────────────────────────────────────────────────────────
    document.getElementById('printTitle').textContent     = resource.title     || 'N/A';
    document.getElementById('printAuthor').textContent    = resource.author    || '-';
    document.getElementById('printPublisher').textContent = resource.publisher || '-';
    document.getElementById('printType').textContent      = resource.type      || '-';
    document.getElementById('printISBN').textContent      = resource.isbn      || 'N/A';
    document.getElementById('printCopyright').textContent = resource.copyright || '-';
    document.getElementById('printPages').textContent     = resource.pages     || '-';

    // ── Subject Assignment ─────────────────────────────────────────────────
    const subjectsContainer = document.getElementById('printSubjects');
    if (resource.subjects && resource.subjects.length > 0) {
        subjectsContainer.innerHTML = resource.subjects
            .map(
                item => `<span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                            ${item.subject} - ${item.grade}
                         </span>`
            )
            .join('');
    } else {
        subjectsContainer.innerHTML = '<p class="text-gray-500">No subject assignment.</p>';
    }

    // ── Acquisition History ────────────────────────────────────────────────
        const tbody = document.getElementById('printAcquisitionBody');
    tbody.innerHTML = '';
    const totals = { usable: 0, pd: 0, damaged: 0, lost: 0, condemnable: 0 };

    if (resource.acquisitions && resource.acquisitions.length > 0) {
        resource.acquisitions.forEach(aq => {
            const usable     = safeInt(aq.usable);
            const pd         = safeInt(aq.partially_damaged);
            const damaged    = safeInt(aq.damaged);
            const lost       = safeInt(aq.lost);
            const condemnable = safeInt(aq.condemnable);
            const total      = usable + pd + damaged + lost + condemnable;

            // Division cell only for level 4
            const divisionCell = isLevel4
                ? `<td class="px-0.5 py-0.5">
                       <span class="inline-block bg-purple-50 text-purple-700 text-xs px-0.5 py-0.5 rounded-full">
                           ${aq.division_name || '-'}
                       </span>
                   </td>`
                : '';

            tbody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-gray-50">
                    ${divisionCell}
                    <td class="px-0.5 py-0.5">
                        <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-0.5 py-0.5 rounded-full whitespace-nowrap">
                            ${aq.library_name || '-'}
                        </span>
                    </td>
                    <td class="px-0.5 py-0.5">${aq.source || '-'}</td>
                    <td class="px-0.5 py-0.5">${aq.date_acquired || '-'}</td>
                    <td class="px-0.5 py-0.5">${formatCost(aq.cost)}</td>
                    <td class="px-0.5 py-0.5">${aq.iar || '-'}</td>
                    <td class="px-0.5 py-0.5 text-xs">${aq.remarks || '-'}</td>
                    <td class="px-0.5 py-0.5 text-center text-green-600 text-xs">${usable}</td>
                    <td class="px-0.5 py-0.5 text-center text-yellow-600 text-xs">${pd}</td>
                    <td class="px-0.5 py-0.5 text-center text-red-600 text-xs">${damaged}</td>
                    <td class="px-0.5 py-0.5 text-center text-purple-600 text-xs">${lost}</td>
                    <td class="px-0.5 py-0.5 text-center text-gray-800 text-xs">${condemnable}</td>
                    <td class="px-0.5 py-0.5 text-center font-bold text-blue-600">${total}</td>
                </tr>
            `);

            totals.usable      += usable;
            totals.pd          += pd;
            totals.damaged     += damaged;
            totals.lost        += lost;
            totals.condemnable += condemnable;
        });
    } else {
        // colspan adjusts based on level
        const colspan = isLevel4 ? 13 : 12;
        tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-4 text-gray-500">No acquisition records.</td></tr>`;
    }

    // ── Overall Quantity Summary ───────────────────────────────────────────
    const grandTotal = totals.usable + totals.pd + totals.damaged + totals.lost + totals.condemnable;

    document.getElementById('printUsable').textContent      = totals.usable;
    document.getElementById('printPD').textContent          = totals.pd;
    document.getElementById('printDamaged').textContent     = totals.damaged;
    document.getElementById('printLost').textContent        = totals.lost;
    document.getElementById('printCondemnable').textContent = totals.condemnable;
    document.getElementById('printTotal').textContent       = grandTotal;

    // ── Show Modal ─────────────────────────────────────────────────────────
    document.getElementById('viewPrintModal').classList.remove('hidden');
}

/**
 * Close the Print Resource modal.
 */
export function closePrintModal() {
    document.getElementById('viewPrintModal').classList.add('hidden');
}

// ─── Event Listeners ─────────────────────────────────────────────────────────

function initModalListeners() {
    const modal = document.getElementById('viewPrintModal');
    if (!modal) return;

    // Click-outside to close
    modal.addEventListener('click', e => {
        if (e.target === modal) closePrintModal();
    });
}

// Escape key to close
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closePrintModal();
});

document.addEventListener('DOMContentLoaded', initModalListeners);

// ─── Global Exposure (for Blade inline onclick handlers) ─────────────────────
// If you use onclick="openPrintModal(...)" in your HTML, these need to be global.
window.openPrintModal  = openPrintModal;
window.closePrintModal = closePrintModal;