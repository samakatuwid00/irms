
export function initGradeLevelTabs(form) {
    const tabs     = form.querySelectorAll('.tab-btn');
    const contents = form.querySelectorAll('.tab-content');

    if (tabs.length === 0) return;

    function activateTab(tab) {
        tabs.forEach(t => {
            t.classList.remove('border-blue-600', 'text-blue-600', 'active');
            t.classList.add('text-gray-600');
        });

        contents.forEach(c => c.classList.add('hidden'));

        tab.classList.add('border-blue-600', 'text-blue-600', 'active');
        tab.classList.remove('text-gray-600');

        const target = form.querySelector(`#${tab.dataset.tab}`);
        if (target) target.classList.remove('hidden');
    }

    activateTab(tabs[0]);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => activateTab(tab));
    });
}
