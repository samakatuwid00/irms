// Level 3 Tab Switching Module
export function initLevel3TabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const validTabs = ['division', 'school'];
    const defaultTab = 'division';

    if (tabButtons.length === 0 || tabContents.length === 0) return;

    function getAvailableTab(tabName) {
        if (!validTabs.includes(tabName)) return null;

        const button = Array.from(tabButtons).find(btn => btn.dataset.tab === tabName);
        const content = Array.from(tabContents).find(item => item.id === `${tabName}-tab`);

        return button && content ? { button, content } : null;
    }

    // Use a valid URL tab or fall back to the division default.
    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    const activeTab = getAvailableTab(requestedTab) ? requestedTab : defaultTab;

    // Function to switch tabs
    function switchTab(tabName) {
        const target = getAvailableTab(tabName);
        if (!target) return false;

        // Update buttons
        tabButtons.forEach(btn => {
            if (btn.dataset.tab === tabName) {
                btn.classList.add('border-blue-600', 'text-blue-600');
                btn.classList.remove('border-transparent', 'text-gray-600');
            } else {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-600');
            }
        });

        // Update content
        tabContents.forEach(content => {
            if (content === target.content) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });

        return true;
    }

    // Initialize with active tab
    switchTab(activeTab);

    // Add click handlers
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            switchTab(btn.dataset.tab);
        });
    });

    // Reset buttons - make globally available for inline onclick
    window.resetDivisionTab = function() {
        window.location.href = window.location.pathname + '?tab=division';
    };

    window.resetSchoolTab = function() {
        window.location.href = window.location.pathname + '?tab=school';
    };

    // Also handle if reset buttons use event listeners
    const resetDivisionBtn = document.querySelector('.reset-division');
    const resetSchoolBtn = document.querySelector('.reset-school');

    if (resetDivisionBtn) {
        resetDivisionBtn.addEventListener('click', window.resetDivisionTab);
    }

    if (resetSchoolBtn) {
        resetSchoolBtn.addEventListener('click', window.resetSchoolTab);
    }
}
