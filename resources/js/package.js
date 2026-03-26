document.addEventListener('DOMContentLoaded', function() {
    const searchInput     = document.getElementById('acqPackageSearch');
    const suggestionsBox  = document.getElementById('packageSuggestions');
    const hiddenPackageId = document.getElementById('acqPackage');

    let debounceTimer;
    let selectedPackageId = null;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        selectedPackageId = null;
        hiddenPackageId.value = '';

        clearTimeout(debounceTimer);

        if (query.length < 2) {
            suggestionsBox.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/packages/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';

                    if (data.length === 0) {
                        suggestionsBox.innerHTML = `<div class="px-4 py-2 text-gray-500 text-sm">No packages found</div>`;
                        suggestionsBox.classList.remove('hidden');
                        return;
                    }

                    data.forEach(pkg => {
                        const div = document.createElement('div');
                        div.className = "px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm";
                        div.textContent = pkg.name;
                        div.dataset.id = pkg.id;

                        // ✅ Use mousedown + preventDefault instead of click
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault(); // Prevents blur from firing on searchInput

                            searchInput.value     = pkg.name;
                            hiddenPackageId.value = pkg.id;
                            selectedPackageId     = pkg.id;
                            suggestionsBox.classList.add('hidden');
                        });

                        suggestionsBox.appendChild(div);
                    });

                    suggestionsBox.classList.remove('hidden');
                });
        }, 300);
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            if (searchInput.value.trim() !== '' && !selectedPackageId) {
                hiddenPackageId.value = '';
            }

            // Always hide suggestions on blur
            suggestionsBox.classList.add('hidden');
        }, 200);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });
});