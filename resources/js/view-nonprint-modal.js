
/**
 * Safely parse an integer, falling back to 0.
 * @param {*} value
 * @returns {number}
 */
function safeInt(value) {
    return parseInt(value || 0, 10);
}

/**
 * Format a numeric cost value as a Philippine Peso string.
 * @param {string|number} cost
 * @returns {string}
 */
function formatCost(cost) {
    if (!cost) return '-';
    return '₱' + parseFloat(cost).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

// ─── Modal Logic ─────────────────────────────────────────────────────────────

const DEFAULT_IMAGE = '/assets/images/default.jpg';

/**
 * Open the Non-Print Resource modal and populate it with the given resource data.
 *
 * @param {Object} resource
 * @param {string}   resource.image
 * @param {string}   resource.title
 * @param {string}   resource.type
 * @param {string}   resource.brand
 * @param {string}   resource.code
 * @param {string}   resource.version
 * @param {string}   resource.url
 * @param {string}   resource.size
 * @param {string}   resource.model
 * @param {Array}    resource.subjects      - [{ subject, grade }]
 * @param {Array}    resource.acquisitions  - [{ source, date_acquired, cost, iar, remarks, usable, partially_damaged, damaged, lost, condemnable }]
 */
export function openNonPrintModal(resource) {
    // ── Image ──────────────────────────────────────────────────────────────
    const imgElement = document.getElementById('nonprintImage');
    imgElement.src = resource.image || DEFAULT_IMAGE;
    imgElement.alt = resource.title || 'Book Cover';
    imgElement.onerror = function () {
        this.src = DEFAULT_IMAGE;
        this.onerror = null; // prevent infinite loop
    };

    // ── Basic Info ─────────────────────────────────────────────────────────
    document.getElementById('nonprintTitle').textContent = resource.title   || 'N/A';
    document.getElementById('nonprintType').textContent  = resource.type    || '-';
    document.getElementById('brand').textContent         = resource.brand   || '-';
    document.getElementById('code').textContent          = resource.code    || '-';
    document.getElementById('version').textContent       = resource.version || 'N/A';
    document.getElementById('url').textContent           = resource.url     || '-';
    document.getElementById('size').textContent          = resource.size    || '-';
    document.getElementById('model').textContent         = resource.model   || '-';

    // ── Subject Assignment ─────────────────────────────────────────────────
    const subjectsContainer = document.getElementById('nonprintSubjects');
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
    const tbody = document.getElementById('nonprintAcquisitionBody');
    tbody.innerHTML = '';

    const totals = { usable: 0, pd: 0, damaged: 0, lost: 0, condemnable: 0 };

    if (resource.acquisitions && resource.acquisitions.length > 0) {
        resource.acquisitions.forEach(aq => {
            const usable      = safeInt(aq.usable);
            const pd          = safeInt(aq.partially_damaged);
            const damaged     = safeInt(aq.damaged);
            const lost        = safeInt(aq.lost);
            const condemnable = safeInt(aq.condemnable);
            const total       = usable + pd + damaged + lost + condemnable;

            tbody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">${aq.source          || '-'}</td>
                    <td class="px-3 py-2">${aq.date_acquired   || '-'}</td>
                    <td class="px-3 py-2">${formatCost(aq.cost)}</td>
                    <td class="px-3 py-2">${aq.iar             || '-'}</td>
                    <td class="px-3 py-2 text-xs">${aq.remarks || '-'}</td>
                    <td class="px-3 py-2 text-center text-green-600  font-medium">${usable}</td>
                    <td class="px-3 py-2 text-center text-yellow-600 font-medium">${pd}</td>
                    <td class="px-3 py-2 text-center text-red-600    font-medium">${damaged}</td>
                    <td class="px-3 py-2 text-center text-purple-600 font-medium">${lost}</td>
                    <td class="px-3 py-2 text-center text-gray-800   font-medium">${condemnable}</td>
                    <td class="px-3 py-2 text-center font-bold text-blue-600">${total}</td>
                </tr>
            `);

            totals.usable      += usable;
            totals.pd          += pd;
            totals.damaged     += damaged;
            totals.lost        += lost;
            totals.condemnable += condemnable;
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-gray-500">No acquisition records.</td></tr>';
    }

    // ── Overall Quantity Summary ───────────────────────────────────────────
    const grandTotal = totals.usable + totals.pd + totals.damaged + totals.lost + totals.condemnable;

    document.getElementById('nonprintUsable').textContent      = totals.usable;
    document.getElementById('nonprintPD').textContent          = totals.pd;
    document.getElementById('nonprintDamaged').textContent     = totals.damaged;
    document.getElementById('nonprintLost').textContent        = totals.lost;
    document.getElementById('nonprintCondemnable').textContent = totals.condemnable;
    document.getElementById('nonprintTotal').textContent       = grandTotal;

    // ── Show Modal ─────────────────────────────────────────────────────────
    document.getElementById('viewNonPrintModal').classList.remove('hidden');
}

/**
 * Close the Non-Print Resource modal.
 */
export function closeNonPrintModal() {
    document.getElementById('viewNonPrintModal').classList.add('hidden');
}

// ─── Event Listeners ─────────────────────────────────────────────────────────

function initModalListeners() {
    const modal = document.getElementById('viewNonPrintModal');
    if (!modal) return;

    // Click-outside to close
    modal.addEventListener('click', e => {
        if (e.target === modal) closeNonPrintModal();
    });
}

// Escape key to close
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeNonPrintModal();
});

document.addEventListener('DOMContentLoaded', initModalListeners);

// ─── Global Exposure (for Blade inline onclick handlers) ──────────────────────
window.openNonPrintModal  = openNonPrintModal;
window.closeNonPrintModal = closeNonPrintModal;
