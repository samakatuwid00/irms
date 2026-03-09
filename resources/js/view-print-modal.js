export function openPrintModal(resource) {
    const DEFAULT_IMAGE = '/assets/images/default.jpg';
    const imgElement = document.getElementById('printImage');
    imgElement.src = resource.image || DEFAULT_IMAGE;
    imgElement.alt = resource.title || 'Book Cover';
    imgElement.onerror = function () {
        this.src = DEFAULT_IMAGE;
        this.onerror = null;
    };

    document.getElementById('printTitle').textContent     = resource.title     || 'N/A';
    document.getElementById('printAuthor').textContent    = resource.author    || '-';
    document.getElementById('printPublisher').textContent = resource.publisher || '-';
    document.getElementById('printType').textContent      = resource.type      || '-';
    document.getElementById('printISBN').textContent      = resource.isbn      || 'N/A';
    document.getElementById('printCopyright').textContent = resource.copyright || '-';
    document.getElementById('printPages').textContent     = resource.pages     || '-';

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

    const tbody = document.getElementById('printAcquisitionBody');
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
                    <td class="px-3 py-2">
                        <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap">
                            ${aq.library_name || '-'}
                        </span>
                    </td>
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
        tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-gray-500">No acquisition records.</td></tr>';
    }

    const grandTotal = totals.usable + totals.pd + totals.damaged + totals.lost + totals.condemnable;

    document.getElementById('printUsable').textContent      = totals.usable;
    document.getElementById('printPD').textContent          = totals.pd;
    document.getElementById('printDamaged').textContent     = totals.damaged;
    document.getElementById('printLost').textContent        = totals.lost;
    document.getElementById('printCondemnable').textContent = totals.condemnable;
    document.getElementById('printTotal').textContent       = grandTotal;

    document.getElementById('viewPrintModal').classList.remove('hidden');
}


export function closePrintModal() {
    document.getElementById('viewPrintModal').classList.add('hidden');
}


function initModalListeners() {
    const modal = document.getElementById('viewPrintModal');
    if (!modal) return;

    modal.addEventListener('click', e => {
        if (e.target === modal) closePrintModal();
    });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closePrintModal();
});

document.addEventListener('DOMContentLoaded', initModalListeners);

window.openPrintModal  = openPrintModal;
window.closePrintModal = closePrintModal;