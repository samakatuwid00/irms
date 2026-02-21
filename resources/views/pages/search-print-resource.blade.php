@extends('pages.layout.layout')

@section('title', 'Search Print Resource')
@section('page-title', 'Search Print Resource')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('breadcrumb', 'Search Print Resource')

@section('content')
<div class="p-6 space-y-6">
    @include('pages.partials.page-header')

    <div class="bg-white shadow rounded-xl p-6">

        {{-- Page Heading --}}
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Search Existing Print Resources</h2>
            <p class="text-sm text-gray-500 mt-1">
                Search the masterlist by title or author. If a resource already exists, select it and add your acquisition records.
                If no title is found, you can manually add a new print resource to the masterlist via request.
            </p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded flex justify-between items-center" id="flash-success">
                <span>{{ session('success') }}</span>
                <button type="button" class="text-green-800 font-bold hover:text-green-900"
                    onclick="document.getElementById('flash-success').remove()">&times;</button>
            </div>
        @endif

        {{-- Search Bar --}}
        <div class="mb-6">
            <div class="flex gap-3">
                <div class="relative flex-1">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Type a title or author name..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        autocomplete="off"
                    >
                    <span id="searchSpinner" class="absolute right-3 top-3.5 hidden">
                        <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </span>
                </div>
                <button id="searchBtn"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">
                    Search
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Minimum 2 characters to search.</p>
        </div>

        {{-- Results Area --}}
        <div id="resultsArea" class="hidden">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Search Results</h3>
                <span id="resultCount" class="text-xs text-gray-400"></span>
            </div>
            <div id="resultsList" class="space-y-3"></div>
        </div>

        {{-- Empty State --}}
        <div id="emptyState" class="hidden text-center py-16 text-gray-400">
            <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="font-medium">No titles found</p>
            <p class="text-sm mt-1">Try a different keyword.
            </p>
        </div>

        {{-- Initial Hint --}}
        <div id="initialHint" class="text-center py-16 text-gray-400">
            <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
            </svg>
            <p class="font-medium">Start by searching for a title or author</p>
        </div>
    </div>
</div>

{{-- =================== DETAIL MODAL =================== --}}
<div id="detailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div id="modalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>

    <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl z-10 mb-10">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Resource Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Loading --}}
            <div id="modalLoading" class="flex justify-center items-center py-20">
                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>

            {{-- Modal Body --}}
            <div id="modalBody" class="hidden p-6 space-y-6">

                {{-- Cover + Title / Author / Subjects --}}
                <div class="flex gap-5">
                    <div class="shrink-0">
                        <img id="modalCover" src="" alt="Cover"
                            class="w-28 h-40 object-cover rounded-lg border border-gray-200 shadow-sm">
                    </div>
                    <div class="flex-1 space-y-2">
                        <h4 id="modalBookTitle" class="text-xl font-bold text-gray-900 leading-snug"></h4>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Author(s):</span>
                            <span id="modalAuthors"></span>
                        </p>
                        <div class="pt-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Subject / Grade Level</p>
                            <p id="modalSubjects" class="text-sm text-gray-700 leading-relaxed"></p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200">

                {{-- Editions Table --}}
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-3">
                        Available Editions
                        <span class="text-xs font-normal text-gray-400 ml-1">— click Add on the edition you want to copy to your library</span>
                    </p>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                <tr>
                                    <th class="px-3 py-2 text-left w-12">Cover</th>
                                    <th class="px-3 py-2 text-left">Type</th>
                                    <th class="px-3 py-2 text-left">Publisher</th>
                                    <th class="px-3 py-2 text-left">Edition</th>
                                    <th class="px-3 py-2 text-left">Volume</th>
                                    <th class="px-3 py-2 text-left">Copyright</th>
                                    <th class="px-3 py-2 text-left">ISBN</th>
                                    <th class="px-3 py-2 text-left">Pages</th>
                                    <th class="px-3 py-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="modalEditionsBody" class="divide-y divide-gray-100 bg-white"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- =================== INLINE JAVASCRIPT =================== --}}
<script>
(function () {
    /* ---- DOM refs ---- */
    const searchInput       = document.getElementById('searchInput');
    const searchBtn         = document.getElementById('searchBtn');
    const spinner           = document.getElementById('searchSpinner');
    const resultsArea       = document.getElementById('resultsArea');
    const resultsList       = document.getElementById('resultsList');
    const resultCount       = document.getElementById('resultCount');
    const emptyState        = document.getElementById('emptyState');
    const initialHint       = document.getElementById('initialHint');
    const detailModal       = document.getElementById('detailModal');
    const modalBackdrop     = document.getElementById('modalBackdrop');
    const closeModalBtn     = document.getElementById('closeModal');
    const modalLoading      = document.getElementById('modalLoading');
    const modalBody         = document.getElementById('modalBody');
    const modalEditionsBody = document.getElementById('modalEditionsBody');

    let searchTimeout = null;

    /* ============================================================
       SEARCH
    ============================================================ */
    function performSearch() {
        const q = searchInput.value.trim();
        if (q.length < 2) return;

        showSpinner(true);
        hideAll();

        fetch(`{{ route('search-print-resource.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            showSpinner(false);
            if (!data.length) {
                emptyState.classList.remove('hidden');
                return;
            }
            renderResults(data);
        })
        .catch(() => {
            showSpinner(false);
            emptyState.classList.remove('hidden');
        });
    }

    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') performSearch(); });
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        if (searchInput.value.trim().length >= 2) searchTimeout = setTimeout(performSearch, 450);
    });

    /* ============================================================
       RENDER RESULT CARDS  (one per title, one View Details button)
    ============================================================ */
    function renderResults(titles) {
        resultsList.innerHTML = '';
        resultCount.textContent = `${titles.length} title(s) found`;
        resultsArea.classList.remove('hidden');

        titles.forEach(title => {
            // Edition summary badges (type · edition · copyright)
            const editionBadges = title.editions.map(e => {
                let label = esc(e.type);
                if (e.edition && e.edition !== '-') label += ' · Ed. ' + esc(e.edition);
                if (e.copyright && e.copyright !== '-') label += ' (' + esc(String(e.copyright)) + ')';
                return `<span class="inline-flex items-center text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${label}</span>`;
            }).join('');

            const card = document.createElement('div');
            card.className = 'border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white';
            card.innerHTML = `
                <div class="flex items-start gap-4">
                    <img src="${esc(title.cover)}" alt="cover"
                        class="w-12 h-16 object-cover rounded shadow-sm flex-shrink-0 border border-gray-200">

                    <div class="flex-1 min-w-0 space-y-1.5">
                        <p class="font-semibold text-gray-900">${esc(title.title)}</p>
                        <p class="text-xs text-gray-500">${esc(title.authors)}</p>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            <span class="font-medium">Subjects:</span> ${esc(title.subjects)}
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            ${editionBadges || '<span class="text-xs text-gray-400">No editions</span>'}
                        </div>
                    </div>

                    <div class="flex-shrink-0 self-center">
                        <button
                            data-title-id="${esc(title.id)}"
                            class="view-btn text-xs px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap font-medium">
                            View Details
                        </button>
                    </div>
                </div>
            `;

            card.querySelector('.view-btn').addEventListener('click', function () {
                openModal(this.dataset.titleId);
            });

            resultsList.appendChild(card);
        });
    }

    /* ============================================================
       MODAL  (fetches by title ID, shows editions table)
    ============================================================ */
    function openModal(titleId) {
        detailModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Reset state
        modalBody.classList.add('hidden');
        modalLoading.classList.remove('hidden');
        modalLoading.innerHTML = `
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>`;

        fetch(`{{ url('resources/search-print') }}/${titleId}/details`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            populateModal(data);
            modalLoading.classList.add('hidden');
            modalBody.classList.remove('hidden');
        })
        .catch(() => {
            modalLoading.innerHTML =
                '<p class="text-red-500 text-sm px-8">Failed to load details. Please try again.</p>';
        });
    }

    function closeModalFn() {
        detailModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    closeModalBtn.addEventListener('click', closeModalFn);
    modalBackdrop.addEventListener('click', closeModalFn);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModalFn(); });

    function populateModal(d) {
        document.getElementById('modalTitle').textContent     = 'Resource Details';
        document.getElementById('modalCover').src             = d.cover;
        document.getElementById('modalBookTitle').textContent = d.title;
        document.getElementById('modalAuthors').textContent   = d.authors;

        // Subjects: plain comma-separated string
        document.getElementById('modalSubjects').textContent  = d.subjects;

        // Editions table rows
        modalEditionsBody.innerHTML = '';

        if (!d.editions || !d.editions.length) {
            modalEditionsBody.innerHTML =
                '<tr><td colspan="9" class="text-center text-gray-400 py-6 text-xs">No editions found</td></tr>';
            return;
        }

        d.editions.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors';
            tr.innerHTML = `
                <td class="px-3 py-2">
                    <img src="${esc(e.cover)}" alt="cover"
                        class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                </td>
                <td class="px-3 py-2 text-gray-700">${esc(e.type)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.publisher)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.edition)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.volume)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.copyright)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.isbn)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.pages)}</td>
                <td class="px-3 py-2 text-center">
                    <a href="${esc(e.add_url)}"
                        class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap font-medium">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add
                    </a>
                </td>
            `;
            modalEditionsBody.appendChild(tr);
        });
    }

    /* ============================================================
       HELPERS
    ============================================================ */
    function showSpinner(show) {
        spinner.classList.toggle('hidden', !show);
    }

    function hideAll() {
        resultsArea.classList.add('hidden');
        emptyState.classList.add('hidden');
        initialHint.classList.add('hidden');
    }

    function esc(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }
})();
</script>
@endsection
