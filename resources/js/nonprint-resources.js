
import { initLevel3TabSwitching } from './resource-table-modules/level3-tabs.js';
import { initLevel3DistrictSchoolCascade } from './resource-table-modules/level3-cascade.js';
import { initLevel4Cascade } from './resource-table-modules/level4-cascade.js';
import { initResetFilters } from './resource-table-modules/reset-filters.js';

// Get level from the page
const levelElement = document.querySelector('[data-user-level]');
const level = levelElement ? parseInt(levelElement.dataset.userLevel) : 0;

// Initialize modules when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Level 3: Division account with tabs
    if (level === 3) {
        initLevel3TabSwitching();
        initLevel3DistrictSchoolCascade();
    }

    // Level 4: Region account with three-level cascade
    if (level === 4) {
        initLevel4Cascade();
    }

    // Reset filters (available for all levels)
    initResetFilters();
});
