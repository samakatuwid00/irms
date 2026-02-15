// School Information Form Module
export function initSchoolInfoForm() {
    const form = document.getElementById('schoolForm');
    const saveBtn = document.getElementById('saveBtn');
    const modal = document.getElementById('confirmModal');
    const changedList = document.getElementById('changedFields');

    if (!form || !saveBtn) return;

    const originalData = Object.fromEntries(new FormData(form).entries());

    // Enable/disable Save button
    form.addEventListener('input', toggleSaveButton);

    function hasChanges() {
        const current = new FormData(form);
        return [...current.entries()].some(([k, v]) => v !== (originalData[k] ?? ''));
    }

    function toggleSaveButton() {
        const dirty = hasChanges();
        saveBtn.disabled = !dirty;
        saveBtn.classList.toggle('opacity-50', !dirty);
        saveBtn.classList.toggle('cursor-not-allowed', !dirty);
    }

    // Make functions globally available for onclick handlers
    window.openConfirmModal = function() {
        if (!modal || !changedList) return;

        changedList.innerHTML = '';
        getChangedFields().forEach(f => {
            changedList.innerHTML += `<li>${f}</li>`;
        });
        modal.classList.remove('hidden');
    };

    window.closeConfirmModal = function() {
        if (!modal) return;
        modal.classList.add('hidden');
    };

    function getChangedFields() {
        const current = new FormData(form);
        return [...current.entries()]
            .filter(([k, v]) => v !== (originalData[k] ?? ''))
            .map(([k]) => k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
    }

    // Submit form via normal POST (reload page)
    window.submitForm = function() {
        form.submit();
    };
}
