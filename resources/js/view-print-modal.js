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
// ── Basic Info ─────────────────────────────────────────────────────────
const titleContainer = document.getElementById('printTitle');

if (resource.verified) {
    titleContainer.innerHTML = `
        <span class="inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" 
                 class="w-7 h-7 text-blue-600 shrink-0" 
                 fill="currentColor"
                 viewBox="0 0 20 20"
                 aria-label="Verified learning resource"
                 title="Verified by SDO / Division librarian">
                <path fill-rule="evenodd"
                      d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"/>
            </svg>
            <span class="text-2xl font-semibold text-gray-900 leading-tight">${resource.title || 'N/A'}</span>
        </span>
    `;
} else {
    titleContainer.innerHTML = `
        <span class="text-2xl font-semibold text-gray-900 leading-tight">${resource.title || 'N/A'}</span>
    `;
}
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
        const sortedAcquisitions = [...resource.acquisitions].sort((a, b) => {
            const dateA = Date.parse(a.date_acquired_raw || a.date_acquired) || 0;
            const dateB = Date.parse(b.date_acquired_raw || b.date_acquired) || 0;
            return dateB - dateA;
        });

        sortedAcquisitions.forEach(aq => {
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
                    <td class="px-0.5 py-0.5 uppercase">${aq.iar || '-'}</td>
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