export function initLibraryHubs() {
    const addModal = document.getElementById('addLibraryHubModal');
    const editModal = document.getElementById('editLibraryHubModal');
    const openAddButton = document.getElementById('openAddLibraryHubModal');
    const editForm = document.getElementById('editLibraryHubForm');
    const editName = document.getElementById('editLibraryHubName');
    const editLibrarian = document.getElementById('editLibraryHubLibrarian');
    const editEstimatedResource = document.getElementById('editLibraryHubEstimatedResource');

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    openAddButton?.addEventListener('click', () => openModal(addModal));

    document.querySelectorAll('.close-library-hub-modal').forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(addModal);
            closeModal(editModal);
        });
    });

    [addModal, editModal].forEach((modal) => {
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.querySelectorAll('.edit-library-hub-btn').forEach((button) => {
        button.addEventListener('click', () => {
            if (!editForm || !editName || !editLibrarian || !editEstimatedResource) return;

            editForm.action = button.dataset.action || '';
            editName.value = button.dataset.libraryName || '';
            editLibrarian.value = button.dataset.librarian || '';
            editEstimatedResource.value = button.dataset.estimatedResource || 0;

            openModal(editModal);
        });
    });
}
