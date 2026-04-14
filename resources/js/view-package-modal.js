// ─── Package Modal Logic ─────────────────────────────────────────────────────

/**
 * Open Package Modal
 * @param {Object} pkg 
 */
export function openPackageModal(pkg) {
    document.getElementById('packageName').textContent = pkg.name || 'Untitled Package';
    document.getElementById('packageTotalItems').textContent = `${pkg.total_items || 0} items`;
    document.getElementById('packageCreatedAt').textContent = pkg.created_at || '';
    document.getElementById('packageStatus').textContent = pkg.status || 'Active';

    const tbody = document.getElementById('packageResourcesBody');
    tbody.innerHTML = '';

    if (pkg.resources && pkg.resources.length > 0) {
        pkg.resources.forEach(res => {
            const qty = res.quantity || {};
            const total = qty.total || 
                (qty.usable + qty.partially_damaged + qty.damaged + qty.lost + qty.condemnable);

            // Handle subject display — first badge + "+N" with hover tooltip (mirrors blade reference)
            const subjectDisplay = (() => {
                if (!res.subjects || res.subjects.length === 0) {
                    return '<span class="text-gray-500 text-xs">No assignment</span>';
                }
                const first = res.subjects[0];
                const extra = res.subjects.length - 1;
                const extraBadge = extra > 0
                    ? `<span class="ml-1 text-green-600">+${extra}</span>`
                    : '';
                const tooltipRows = extra > 0
                    ? res.subjects.map(s => `<div class="py-1 border-b border-gray-700 last:border-0">${s}</div>`).join('')
                    : '';
                const tooltip = extra > 0
                    ? `<div data-pkg-tooltip
                            class="pointer-events-none fixed z-[100] invisible opacity-0
                                   bg-gray-800 text-white text-xs rounded-md py-2 px-3 shadow-xl
                                   min-w-[220px] max-w-sm whitespace-normal break-words
                                   transition-opacity duration-150 border border-gray-700">
                           ${tooltipRows}
                       </div>`
                    : '';
                return `<div class="relative inline-block max-w-full pkg-subject-wrap">
                            <span class="inline-block px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700 cursor-default">
                                ${first}${extraBadge}
                            </span>
                            ${tooltip}
                        </div>`;
            })();

            const row = `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <img src="${res.thumb_url}" 
                             alt="${res.title}"
                             class="w-12 h-16 object-cover rounded shadow"
                             onerror="this.src='/assets/images/default.jpg'">
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900">${res.title}</td>
                    <td class="px-4 py-3">
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                            ${res.type}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">${res.brand || ''}</td>
                    <td class="px-4 py-3 font-mono text-gray-600">${res.code || ''}</td>
                    <td class="px-4 py-3 text-gray-600">${res.version || ''}</td>
                    <td class="px-4 py-3 text-gray-600 break-all text-xs">${res.url || ''}</td>
                    <td class="px-4 py-3 text-gray-600">${res.size || ''}</td>
                    <td class="px-4 py-3 text-gray-600">${res.model || ''}</td>
                    <td class="px-4 py-3">${subjectDisplay}</td>
                    <td class="px-4 py-3">
                        <div class="text-center text-xs">
                            <div class="font-semibold text-blue-700 mb-1">Total: ${total}</div>
                            <div class="flex justify-center gap-4 text-[10px]">
                                <span class="text-green-600">U: ${qty.usable || 0}</span>
                                <span class="text-yellow-600">PD: ${qty.partially_damaged || 0}</span>
                                <span class="text-red-600">D: ${qty.damaged || 0}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-2 py-3">
                        <div class="flex justify-center gap-2">
                            <button onclick='openNonPrintModal(${JSON.stringify(res.details)})'
                                class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                View
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-12 text-gray-500">
                    No resources found in this package.
                </td>
            </tr>
        `;
    }

    document.getElementById('viewPackageModal').classList.remove('hidden');
}

export function closePackageModal() {
    document.getElementById('viewPackageModal').classList.add('hidden');
}

// Event listeners
function initPackageModal() {
    const modal = document.getElementById('viewPackageModal');
    if (!modal) return;

    modal.addEventListener('click', e => {
        if (e.target === modal) closePackageModal();
    });

    // Tooltip hover for subject pills (event delegation — works for dynamic rows)
    modal.addEventListener('mouseover', e => {
        const wrap = e.target.closest('.pkg-subject-wrap');
        if (!wrap) return;
        const tooltip = wrap.querySelector('[data-pkg-tooltip]');
        if (!tooltip) return;
        const rect = wrap.getBoundingClientRect();
        tooltip.style.left = rect.left + window.scrollX + 'px';
        tooltip.style.top  = rect.bottom + window.scrollY + 8 + 'px';
        tooltip.classList.remove('invisible', 'opacity-0');
        tooltip.classList.add('visible', 'opacity-100');
    });

    modal.addEventListener('mouseout', e => {
        const wrap = e.target.closest('.pkg-subject-wrap');
        if (!wrap) return;
        const tooltip = wrap.querySelector('[data-pkg-tooltip]');
        if (!tooltip) return;
        tooltip.classList.add('invisible', 'opacity-0');
        tooltip.classList.remove('visible', 'opacity-100');
    });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closePackageModal();
    }
});

document.addEventListener('DOMContentLoaded', initPackageModal);

// Global exposure
window.openPackageModal = openPackageModal;
window.closePackageModal = closePackageModal;