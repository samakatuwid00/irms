// Level 3 Tab Switching Module
export function initLevel3TabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabButtons.length === 0 || tabContents.length === 0) return;

    // Check for active tab from URL parameter or default to 'division'
    const activeTab = new URLSearchParams(window.location.search).get('tab') || 'division';

    // Function to switch tabs
    function switchTab(tabName) {
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
            if (content.id === `${tabName}-tab`) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
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
