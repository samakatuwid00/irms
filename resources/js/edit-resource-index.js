
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons   = document.querySelectorAll('.resource-tab-btn');
    const printForm    = document.getElementById('print-form');
    const nonprintForm = document.getElementById('nonprint-form');
    const printTab     = document.getElementById('print-tab');
    const nonprintTab  = document.getElementById('nonprint-tab');

    if (!printForm || !nonprintForm) return;

    const {
        resourceId,
        hasPrintResource,
        hasNonprintResource,
        tabParam,
    } = window.__editResourcesData ?? {};

    const storageKey = `activeResourceTab_${resourceId}`;

    function activateTab(tabType) {
        tabButtons.forEach(b => {
            b.classList.remove('border-blue-600', 'text-blue-600');
            b.classList.add('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
        });

        printForm.classList.add('hidden');
        nonprintForm.classList.add('hidden');

        if (tabType === 'nonprint') {
            nonprintTab.classList.remove('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
            nonprintTab.classList.add('border-blue-600', 'text-blue-600');
            nonprintForm.classList.remove('hidden');
            sessionStorage.setItem(storageKey, 'nonprint');
        } else {
            printTab.classList.remove('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
            printTab.classList.add('border-blue-600', 'text-blue-600');
            printForm.classList.remove('hidden');
            sessionStorage.setItem(storageKey, 'print');
        }
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tab));
    });

    const storedTab = sessionStorage.getItem(storageKey);
    let initialTab  = 'print';

    if (tabParam === 'print' || tabParam === 'nonprint') {
        initialTab = tabParam;
    } else if (storedTab === 'print' || storedTab === 'nonprint') {
        initialTab = storedTab;
    } else if (!hasPrintResource && hasNonprintResource) {
        initialTab = 'nonprint';
    }

    activateTab(initialTab);
});
