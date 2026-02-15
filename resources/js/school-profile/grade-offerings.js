// Grade Offerings Module
export function initGradeOfferings() {
    const gradesForm = document.getElementById('gradeOfferingsForm');
    const saveGradesBtn = document.getElementById('saveGradesBtn');
    const gradesModal = document.getElementById('gradesConfirmModal');
    const selectedGradesList = document.getElementById('selectedGradesList');
    const gradeCheckboxes = document.querySelectorAll('.grade-checkbox');

    if (!gradesForm || !saveGradesBtn) return;

    // Store original grade selections
    const originalGrades = {};
    gradeCheckboxes.forEach(cb => {
        originalGrades[cb.name] = cb.checked;
    });

    // Enable/disable Save button for grades
    gradesForm.addEventListener('change', toggleSaveGradesButton);

    function hasGradeChanges() {
        return Array.from(gradeCheckboxes).some(cb => cb.checked !== originalGrades[cb.name]);
    }

    function toggleSaveGradesButton() {
        const dirty = hasGradeChanges();
        saveGradesBtn.disabled = !dirty;
        saveGradesBtn.classList.toggle('opacity-50', !dirty);
        saveGradesBtn.classList.toggle('cursor-not-allowed', !dirty);
    }

    // Make functions globally available for onclick handlers
    window.selectAllGrades = function() {
        gradeCheckboxes.forEach(cb => cb.checked = true);
        toggleSaveGradesButton();
    };

    window.deselectAllGrades = function() {
        gradeCheckboxes.forEach(cb => cb.checked = false);
        toggleSaveGradesButton();
    };

    window.openGradesConfirmModal = function() {
        if (!gradesModal || !selectedGradesList) return;

        const selected = Array.from(gradeCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => {
                if (cb.name === 'K') return 'Kindergarten';
                return `Grade ${cb.name.substring(1)}`;
            });

        if (selected.length === 0) {
            selectedGradesList.innerHTML = '<span class="text-gray-500 italic">No grades selected</span>';
        } else {
            selectedGradesList.innerHTML = selected.join(', ');
        }

        gradesModal.classList.remove('hidden');
    };

    window.closeGradesConfirmModal = function() {
        if (!gradesModal) return;
        gradesModal.classList.add('hidden');
    };

    window.submitGradesForm = function() {
        gradesForm.submit();
    };
}
