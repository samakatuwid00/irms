// Level 4 Division → District → School Cascade Module
export function initLevel4Cascade() {
    const divisionSelect = document.getElementById('division');
    const districtSelect = document.getElementById('district');
    const schoolSelect = document.getElementById('school');

    if (!divisionSelect || !districtSelect || !schoolSelect) return;

    // Get data from data attributes
    const allDistrictsElement = document.querySelector('[data-all-districts]');
    const allSchoolsElement = document.querySelector('[data-all-schools]');

    if (!allDistrictsElement || !allSchoolsElement) return;

    const allDistricts = JSON.parse(allDistrictsElement.dataset.allDistricts || '[]');
    const allSchools = JSON.parse(allSchoolsElement.dataset.allSchools || '[]');

    // Get persisted selections from URL
    const urlParams = new URLSearchParams(window.location.search);
    const selectedDivision = urlParams.get('division') || '';
    const selectedDistrict = urlParams.get('district') || '';
    const selectedSchool = urlParams.get('school') || '';

    function updateDistricts() {
        const divisionId = divisionSelect.value;

        districtSelect.innerHTML = '<option value="all">All Districts</option>';
        schoolSelect.innerHTML = '<option value="all">All Schools</option>';

        if (!divisionId || divisionId === 'all') return;

        allDistricts
            .filter(d => d.division_id == divisionId)
            .forEach(d => {
                const selected = d.id == selectedDistrict ? 'selected' : '';
                districtSelect.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${d.id}" ${selected}>${d.district_name}</option>`
                );
            });

        updateSchools();
    }

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

    // Restore previous selections
    if (selectedDivision) {
        divisionSelect.value = selectedDivision;
        updateDistricts();
    }

    // Event listeners
    divisionSelect.addEventListener('change', () => {
        districtSelect.value = 'all';
        schoolSelect.value = 'all';
        updateDistricts();
    });

    districtSelect.addEventListener('change', () => {
        schoolSelect.value = 'all';
        updateSchools();
    });

    // Make functions available globally if needed
    window.updateDistricts = updateDistricts;
    window.updateSchools = updateSchools;
}
