// Reset Filters Module
export function initResetFilters() {
    const resetBtn = document.getElementById('resetFilters');

    if (!resetBtn) return;

    resetBtn.addEventListener('click', () => {
        const levelSelect = document.getElementById('level');
        const divisionSelect = document.getElementById('division');
        const districtSelect = document.getElementById('district');
        const schoolSelect = document.getElementById('school');
        const searchInput = document.getElementById('search');

        // Reset all select elements to 'all'
        if (levelSelect) levelSelect.value = 'all';
        if (divisionSelect) divisionSelect.value = 'all';
        if (districtSelect) districtSelect.value = 'all';
        if (schoolSelect) schoolSelect.value = 'all';

        // Clear search input
        if (searchInput) searchInput.value = '';

        // Call update functions if they exist (for cascade dropdowns)
        if (typeof window.updateDistricts === 'function') {
            window.updateDistricts();
        }
        if (typeof window.updateSchools === 'function') {
            window.updateSchools();
        }

        // Redirect to clean URL
        window.location.href = window.location.pathname;
    });
}
