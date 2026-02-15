
export class ResourceTabManager {
    constructor(config) {
        this.tabButtons = document.querySelectorAll(config.tabButtonSelector);
        this.printForm = document.getElementById(config.printFormId);
        this.nonprintForm = document.getElementById(config.nonprintFormId);
        this.storageKey = config.storageKey || 'add_resource_active_tab';

        this.init();
    }

    init() {
        if (!this.tabButtons.length || !this.printForm || !this.nonprintForm) {
            return;
        }

        // Setup click handlers
        this.tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.activateTab(btn.dataset.tab);
            });
        });

        // Restore saved tab or default to print
        const savedTab = sessionStorage.getItem(this.storageKey);
        this.activateTab(savedTab === 'nonprint' ? 'nonprint' : 'print');

        // Clear storage when leaving page
        this.setupStorageCleanup();
    }

    /**
     * Activate a specific tab
     * @param {string} tab - 'print' or 'nonprint'
     */
    activateTab(tab) {
        // Update tab button styles
        this.tabButtons.forEach(btn => {
            btn.classList.remove('border-blue-600', 'text-blue-600');
            btn.classList.add(
                'border-transparent',
                'text-gray-600',
                'hover:text-blue-600',
                'hover:border-gray-300'
            );
        });

        const activeBtn = document.querySelector(`[data-tab="${tab}"]`);
        if (activeBtn) {
            activeBtn.classList.remove(
                'border-transparent',
                'text-gray-600',
                'hover:text-blue-600',
                'hover:border-gray-300'
            );
            activeBtn.classList.add('border-blue-600', 'text-blue-600');
        }

        // Toggle form visibility
        if (tab === 'print') {
            this.printForm.classList.remove('hidden');
            this.nonprintForm.classList.add('hidden');
        } else {
            this.nonprintForm.classList.remove('hidden');
            this.printForm.classList.add('hidden');
        }

        // Save active tab to session storage
        sessionStorage.setItem(this.storageKey, tab);
    }

    /**
     * Setup cleanup of session storage when leaving page
     */
    setupStorageCleanup() {
        window.addEventListener('beforeunload', () => {
            if (document.visibilityState === 'hidden') {
                sessionStorage.removeItem(this.storageKey);
            }
        });
    }
}

/**
 * Initialize tab manager with default config
 */
export function initResourceTabs() {
    new ResourceTabManager({
        tabButtonSelector: '.resource-tab-btn',
        printFormId: 'print-form',
        nonprintFormId: 'nonprint-form',
        storageKey: 'add_resource_active_tab'
    });
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initResourceTabs);
} else {
    initResourceTabs();
}
