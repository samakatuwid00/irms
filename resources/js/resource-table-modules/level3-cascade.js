// Level 3 District → School Cascade Module
export function initLevel3DistrictSchoolCascade() {
    const districtSelect = document.getElementById('district');
    const schoolSelect = document.getElementById('school');

    if (!districtSelect || !schoolSelect) return;

    // Get data from data attributes or global scope
    const allSchoolsElement = document.querySelector('[data-all-schools]');
    if (!allSchoolsElement) return;

    const allSchools = JSON.parse(allSchoolsElement.dataset.allSchools || '[]');

    // Get persisted selections from URL
    const urlParams = new URLSearchParams(window.location.search);
    const selectedDistrict = urlParams.get('district') || '';
    const selectedSchool = urlParams.get('school') || '';

    function updateSchools() {
        const districtId = districtSelect.value;
        schoolSelect.innerHTML = '<option value="all">All Schools</option>';

        if (!districtId || districtId === 'all') return;

        allSchools
            .filter(s => s.district_id == districtId)
            .forEach(s => {
                const selected = s.id == selectedSchool ? 'selected' : '';
                schoolSelect.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${s.id}" ${selected}>${s.school_name}</option>`
                );
            });
    }

    // Restore previous selection
    if (selectedDistrict) {
        districtSelect.value = selectedDistrict;
        updateSchools();
    }

    // Handle district change
    districtSelect.addEventListener('change', () => {
        schoolSelect.value = 'all';
        updateSchools();
    });
}
