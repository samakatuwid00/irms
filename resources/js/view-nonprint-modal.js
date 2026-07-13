// ─── Modal Logic ─────────────────────────────────────────────────────────────

/**
 * Open the Non-Print Resource modal and populate it with the given resource data.
 *
 * The `resource.acquisitions` array is already pre-filtered server-side:
 *   - Opened standalone           → only non-packaged acquisitions (package_id IS NULL)
 *   - Opened from Package modal   → only that package's acquisitions
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
 * @param {Array}    resource.acquisitions  - pre-filtered by server
 */
export function openNonPrintModal(resource) {
    const safeInt = value => parseInt(value || 0, 10);
    const formatCost = cost => {
        if (!cost) return '-';
        return '₱' + parseFloat(cost).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const table = document.getElementById('nonprintAcquisitionTable');
    const userLevel = parseInt(table?.dataset?.userLevel || 0, 10);
    const isLevel4 = userLevel === 4;

    const DEFAULT_IMAGE = '/assets/images/default.jpg';

    // ── Image ──────────────────────────────────────────────────────────────
    const imgElement = document.getElementById('nonprintImage');
    const DEFAULT_IMAGE = '/assets/images/default.jpg';

    if (!resource.image || resource.image.includes('default.jpg')) {
        imgElement.src = DEFAULT_IMAGE;
        imgElement.style.filter = 'none';
    } else {
        imgElement.style.filter = 'blur(10px)';
        imgElement.style.transition = 'filter 0.4s ease';
        imgElement.src = resource.thumb_url || resource.image;

        const fullImg = new Image();
        fullImg.onload = function () {
            imgElement.src = fullImg.src;
            imgElement.style.filter = 'blur(0px)';
        };
        fullImg.onerror = function () {
            imgElement.style.filter = 'blur(0px)';
        };
        fullImg.src = resource.image;
    }

    imgElement.alt = resource.title || 'Resource Cover';

    // ── Basic Info ─────────────────────────────────────────────────────────
    document.getElementById('nonprintTitle').textContent = resource.title   || 'N/A';
    document.getElementById('nonprintType').textContent  = resource.type    || '-';
    document.getElementById('brand').textContent         = resource.brand   || '-';
    document.getElementById('code').textContent          = resource.code    || '-';
    document.getElementById('version').textContent       = resource.version || '-';
    document.getElementById('url').textContent           = resource.url     || '-';
    document.getElementById('size').textContent          = resource.size    || '-';
    document.getElementById('model').textContent         = resource.model   || '-';

    // ── Subject Assignment ─────────────────────────────────────────────────
    const subjectsContainer = document.getElementById('nonprintSubjects');
    if (resource.subjects && resource.subjects.length > 0) {
        subjectsContainer.innerHTML = resource.subjects
            .map(item => `
                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                    ${item.subject} - ${item.grade}
                </span>`)
            .join('');
    } else {
        subjectsContainer.innerHTML = '<p class="text-gray-500">No subject assignment.</p>';
    }

    // ── Render Acquisitions ────────────────────────────────────────────────
    // Data is already pre-filtered server-side — render everything as received.
    function renderAcquisitions(acquisitions) {
        const tbody = document.getElementById('nonprintAcquisitionBody');
        tbody.innerHTML = '';

        const totals = { usable: 0, pd: 0, damaged: 0, lost: 0, condemnable: 0 };

        if (acquisitions.length > 0) {
            acquisitions.forEach(aq => {
                const usable      = safeInt(aq.usable);
                const pd          = safeInt(aq.partially_damaged);
                const damaged     = safeInt(aq.damaged);
                const lost        = safeInt(aq.lost);
                const condemnable = safeInt(aq.condemnable);
                const total       = usable + pd + damaged + lost + condemnable;

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
            const colspan = isLevel4 ? 13 : 12;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center py-4 text-gray-500">
                        No acquisition records.
                    </td>
                </tr>`;
        }

        // ── Update quantity summary ────────────────────────────────────────
        const grandTotal = totals.usable + totals.pd + totals.damaged + totals.lost + totals.condemnable;
        document.getElementById('nonprintUsable').textContent      = totals.usable;
        document.getElementById('nonprintPD').textContent          = totals.pd;
        document.getElementById('nonprintDamaged').textContent     = totals.damaged;
        document.getElementById('nonprintLost').textContent        = totals.lost;
        document.getElementById('nonprintCondemnable').textContent = totals.condemnable;
        document.getElementById('nonprintTotal').textContent       = grandTotal;
    }

    // Render the pre-filtered acquisitions directly — no client-side toggle needed
    renderAcquisitions(resource.acquisitions || []);

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

// ─── Global Exposure (for Blade inline onclick handlers) ─────────────────────
window.openNonPrintModal  = openNonPrintModal;
window.closeNonPrintModal = closeNonPrintModal;