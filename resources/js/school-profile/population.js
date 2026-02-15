// Population Module
export function initPopulation() {
    const schoolYearSelect = document.getElementById('schoolYearSelect');
    const populationFormContainer = document.getElementById('populationFormContainer');
    const populationForm = document.getElementById('populationForm');
    const savePopulationBtn = document.getElementById('savePopulationBtn');
    const populationModal = document.getElementById('populationConfirmModal');
    const populationInputs = document.querySelectorAll('.population-input');

    if (!populationForm) return;

    // Store original population data
    const originalPopulation = {};
    populationInputs.forEach(input => {
        originalPopulation[input.name] = input.value;
    });

    // School year selection change
    if (schoolYearSelect) {
        schoolYearSelect.addEventListener('change', function() {
            const syId = this.value;
            if (syId) {
                // Get the base URL from the current window location
                const baseUrl = window.location.origin + window.location.pathname;
                window.location.href = `${baseUrl}?sy_id=${syId}`;
            } else if (populationFormContainer) {
                populationFormContainer.classList.add('hidden');
            }
        });
    }

    // Calculate totals per grade
    function calculateGradeTotal(grade) {
        const maleInput = document.querySelector(`input[data-grade="${grade}"][data-type="male"]`);
        const femaleInput = document.querySelector(`input[data-grade="${grade}"][data-type="female"]`);
        const totalInput = document.querySelector(`input[data-grade="${grade}"].grade-total`);

        if (maleInput && femaleInput && totalInput) {
            const male = parseInt(maleInput.value) || 0;
            const female = parseInt(femaleInput.value) || 0;
            totalInput.value = male + female;
        }
    }

    // Calculate overall totals
    function calculateOverallTotals() {
        let totalMale = 0;
        let totalFemale = 0;

        document.querySelectorAll('.population-input[data-type="male"]').forEach(input => {
            totalMale += parseInt(input.value) || 0;
        });

        document.querySelectorAll('.population-input[data-type="female"]').forEach(input => {
            totalFemale += parseInt(input.value) || 0;
        });

        const grandTotal = totalMale + totalFemale;

        const totalMaleEl = document.getElementById('totalMale');
        const totalFemaleEl = document.getElementById('totalFemale');
        const grandTotalEl = document.getElementById('grandTotal');

        if (totalMaleEl) totalMaleEl.value = totalMale;
        if (totalFemaleEl) totalFemaleEl.value = totalFemale;
        if (grandTotalEl) grandTotalEl.value = grandTotal;

        return { totalMale, totalFemale, grandTotal };
    }

    // Update totals on input change
    populationInputs.forEach(input => {
        input.addEventListener('input', function() {
            const grade = this.getAttribute('data-grade');
            calculateGradeTotal(grade);
            calculateOverallTotals();
            toggleSavePopulationButton();
        });
    });

    // Check if population data has changed
    function hasPopulationChanges() {
        return Array.from(populationInputs).some(input => {
            return input.value !== (originalPopulation[input.name] || '0');
        });
    }

    // Toggle save button
    function toggleSavePopulationButton() {
        if (!savePopulationBtn) return;

        const dirty = hasPopulationChanges();
        savePopulationBtn.disabled = !dirty;
        savePopulationBtn.classList.toggle('opacity-50', !dirty);
        savePopulationBtn.classList.toggle('cursor-not-allowed', !dirty);
    }

    // Make functions globally available for onclick handlers
    window.openPopulationConfirmModal = function() {
        if (!populationModal) return;

        const totals = calculateOverallTotals();
        const confirmTotalMale = document.getElementById('confirmTotalMale');
        const confirmTotalFemale = document.getElementById('confirmTotalFemale');
        const confirmGrandTotal = document.getElementById('confirmGrandTotal');

        if (confirmTotalMale) confirmTotalMale.textContent = totals.totalMale;
        if (confirmTotalFemale) confirmTotalFemale.textContent = totals.totalFemale;
        if (confirmGrandTotal) confirmGrandTotal.textContent = totals.grandTotal;

        populationModal.classList.remove('hidden');
    };

    window.closePopulationConfirmModal = function() {
        if (!populationModal) return;
        populationModal.classList.add('hidden');
    };

    window.submitPopulationForm = function() {
        populationForm.submit();
    };

    // Initialize totals on page load
    const grades = ['K', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'g8', 'g9', 'g10', 'g11', 'g12'];
    grades.forEach(grade => calculateGradeTotal(grade));
    calculateOverallTotals();
}
