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

    // School year selection change - AJAX VERSION
    if (schoolYearSelect) {
        schoolYearSelect.addEventListener('change', async function() {
            const syId = this.value;
            
            if (!syId) {
                if (populationFormContainer) {
                    populationFormContainer.classList.add('hidden');
                }
                return;
            }
            
            // Show loading state
            if (populationFormContainer) {
                populationFormContainer.style.opacity = '0.5';
                populationFormContainer.style.pointerEvents = 'none';
            }
            
            try {
                // Fetch population data via AJAX
                const response = await fetch(`/school/population/${syId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) throw new Error('Failed to fetch population data');
                
                const data = await response.json();
                
                // Update the form with fetched data
                updatePopulationForm(syId, data.population, data.gradeOffering);
                
                // Show the form container
                if (populationFormContainer) {
                    populationFormContainer.classList.remove('hidden');
                    populationFormContainer.style.opacity = '1';
                    populationFormContainer.style.pointerEvents = 'auto';
                }
                
                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('sy_id', syId);
                window.history.pushState({}, '', url);
                
            } catch (error) {
                console.error('Error loading population data:', error);
                alert('Failed to load population data. Please try again.');
                
                // Reset loading state
                if (populationFormContainer) {
                    populationFormContainer.style.opacity = '1';
                    populationFormContainer.style.pointerEvents = 'auto';
                }
            }
        });
    }
    
    // Function to update the form with fetched data
    function updatePopulationForm(syId, population, gradeOffering) {
        // Update hidden sy_id field
        const syIdInput = document.getElementById('sy_id');
        if (syIdInput) syIdInput.value = syId;
        
        // Clear and rebuild originalPopulation object
        Object.keys(originalPopulation).forEach(key => delete originalPopulation[key]);
        
        // Update all population inputs
        const grades = ['K', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'g8', 'g9', 'g10', 'g11', 'g12'];
        
        grades.forEach(grade => {
            const maleField = grade === 'K' ? 'k_m' : grade.toLowerCase() + '_m';
            const femaleField = grade === 'K' ? 'k_f' : grade.toLowerCase() + '_f';
            
            const maleInput = document.querySelector(`input[name="${maleField}"]`);
            const femaleInput = document.querySelector(`input[name="${femaleField}"]`);
            
            if (maleInput) {
                maleInput.value = population?.[maleField] || 0;
                // Update original data
                originalPopulation[maleField] = maleInput.value;
            }
            
            if (femaleInput) {
                femaleInput.value = population?.[femaleField] || 0;
                // Update original data
                originalPopulation[femaleField] = femaleInput.value;
            }
            
            // Recalculate grade totals
            calculateGradeTotal(grade);
        });
        
        // Recalculate overall totals
        calculateOverallTotals();
        
        // Disable save button (no changes yet)
        toggleSavePopulationButton();
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