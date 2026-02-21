@extends('pages.layout.layout')

@section('title', 'Add Print Resource')
@section('page-title', 'Add Print Resource')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Add Print Resource')

@section('content')
<div class="p-6 space-y-6">
    @include('pages.partials.page-header')

    <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-xl font-semibold mb-4">Add Print Resource</h2>

        {{-- ===================== TABS ===================== --}}
        <div class="flex border-b border-gray-200 mb-6">
            <button type="button"
                    class="print-tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors border-blue-600 text-blue-600"
                    data-tab="search">
                Search Existing
            </button>
            <button type="button"
                    class="print-tab-btn px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-blue-600 hover:border-gray-300 transition-colors"
                    data-tab="manual">
                Manual Input
            </button>
        </div>

        {{-- ===================== TAB: SEARCH ===================== --}}
        <div id="tab-search">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded flex justify-between items-center" id="flash-success">
                    <span>{{ session('success') }}</span>
                    <button type="button" class="text-green-800 font-bold hover:text-green-900"
                        onclick="document.getElementById('flash-success').remove()">&times;</button>
                </div>
            @endif

            {{-- Description --}}
            <div class="mb-6">
                <p class="text-sm text-gray-500">
                    Search the masterlist by title or author. Select a result and add your acquisition records.
                    If no title is found, switch to the <strong>Manual Input</strong> tab to add a new one.
                </p>
            </div>

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
                <p class="text-sm mt-1">Try a different keyword, or switch to <strong>Manual Input</strong> to add a new resource.</p>
            </div>

            {{-- Initial Hint --}}
            <div id="initialHint" class="text-center py-16 text-gray-400">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                </svg>
                <p class="font-medium">Start by searching for a title or author</p>
            </div>

            {{-- ===================== INLINE ADD ACQUISITION (shown after clicking Add) ===================== --}}
            <div id="addAcquisitionSection" class="hidden mt-8 border-t border-gray-200 pt-8">

                {{-- Selected Resource Info (read-only banner) --}}
                <div id="selectedResourceBanner" class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                        </svg>
                        <span class="text-sm font-semibold text-blue-700">Selected Resource (Read-only)</span>
                        <button type="button" id="clearSelectedResource"
                            class="ml-auto text-xs text-blue-500 underline hover:text-blue-700">
                            ✕ Clear selection
                        </button>
                    </div>
                    <div class="flex gap-4 items-start">
                        <img id="selectedCover" src="" alt="Cover"
                            class="w-16 h-22 object-cover rounded border border-blue-200 shadow-sm flex-shrink-0">
                        <div class="text-sm space-y-1">
                            <p><span class="font-medium text-gray-500">Title:</span> <span id="selectedTitle" class="font-semibold text-gray-900"></span></p>
                            <p><span class="font-medium text-gray-500">Author(s):</span> <span id="selectedAuthors" class="text-gray-800"></span></p>
                            <p><span class="font-medium text-gray-500">Type:</span> <span id="selectedType" class="text-gray-800"></span></p>
                            <p><span class="font-medium text-gray-500">Publisher:</span> <span id="selectedPublisher" class="text-gray-800"></span></p>
                            <p><span class="font-medium text-gray-500">Edition:</span> <span id="selectedEdition" class="text-gray-800"></span></p>
                            <p><span class="font-medium text-gray-500">Copyright:</span> <span id="selectedCopyright" class="text-gray-800"></span></p>
                            <p><span class="font-medium text-gray-500">Subject / Grade Level:</span> <span id="selectedSubjects" class="text-gray-800"></span></p>
                        </div>
                    </div>
                </div>

                {{-- Acquisition Form --}}
                <form id="inlineAcquisitionForm"
                    action=""
                    method="POST">
                    @csrf
                    <input type="hidden" name="resource_id" id="selectedResourceId">
                    <input type="hidden" name="acquisitions" id="inlineAcquisitionsInput">

                    <div class="bg-gray-50 border border-gray-300 rounded-xl p-6 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-700">Acquisition & Condition Details</h3>

                        {{-- Library --}}
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Library <span class="text-red-500">*</span>
                                <span class="text-xs font-normal text-gray-400">(saved with each acquisition)</span>
                            </label>

                            @if (Auth::user()->userType?->level === 3)
                                <select id="acq_library_id" name="acq_library_id_display"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    <option value="" disabled selected>Select library</option>
                                    @foreach ($divisionLibraries as $lib)
                                        <option value="{{ $lib->id }}" data-name="{{ $lib->library_name }}">
                                            {{ $lib->library_name }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif (Auth::user()->userType?->level === 4)
                                <input id="acq_library_id" type="hidden"
                                    value="{{ $regionLibrary->id ?? '' }}"
                                    data-name="{{ $regionLibrary->library_name ?? '' }}">
                                <p class="text-sm text-gray-700 border border-gray-200 bg-white rounded px-3 py-2">
                                    {{ $regionLibrary->library_name ?? 'Region Library' }}
                                </p>
                            @elseif (Auth::user()->userType?->level === 1)
                                <input id="acq_library_id" type="hidden"
                                    value="{{ $schoolLibrary->id ?? '' }}"
                                    data-name="{{ $schoolLibrary->library_name ?? '' }}">
                                <p class="text-sm text-gray-700 border border-gray-200 bg-white rounded px-3 py-2">
                                    {{ $schoolLibrary->library_name ?? 'School Library' }}
                                </p>
                            @else
                                <input id="acq_library_id" type="hidden" value="" data-name="">
                                <p class="text-sm text-yellow-600">No library assigned to your account.</p>
                            @endif
                            <input type="hidden" id="acq_library_name">
                        </div>

                        {{-- Remarks --}}
                        <div>
                            <label class="block text-sm font-medium mb-1">Remarks
                                <span class="text-xs text-gray-500">(saved with each acquisition)</span>
                            </label>
                            <textarea name="remarks" id="inlineRemarks" rows="2"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                placeholder="Any notes or condition remarks..."></textarea>
                        </div>

                        {{-- Source / Date / Cost / IAR --}}
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Source <span class="text-red-500">*</span></label>
                                <select id="inlineSource" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    <option value="" disabled selected>Select source</option>
                                    <option value="CO">DepEd - Central Office</option>
                                    <option value="RO">Regional Office</option>
                                    <option value="SDO">Schools Division Office</option>
                                    <option value="LOCAL">Locally Developed</option>
                                    <option value="DONATED">DONATED</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Date Acquired <span class="text-red-500">*</span></label>
                                <input type="date" id="inlineDate" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Cost</label>
                                <input type="number" step="0.01" id="inlineCost" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">IAR No.</label>
                                <input type="text" id="inlineIar" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            </div>
                        </div>

                        {{-- Condition & Quantity --}}
                        <div>
                            <h4 class="text-sm font-semibold mb-3 text-gray-600">Condition & Quantity <span class="text-red-500">*</span></h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                                <div>
                                    <label class="block text-xs mb-1">Usable</label>
                                    <input type="number" id="inlineUsable" value="0" min="0"
                                        class="inline-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs mb-1">Partially Damaged</label>
                                    <input type="number" id="inlinePartiallyDamaged" value="0" min="0"
                                        class="inline-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs mb-1">Damaged</label>
                                    <input type="number" id="inlineDamaged" value="0" min="0"
                                        class="inline-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs mb-1">Lost</label>
                                    <input type="number" id="inlineLost" value="0" min="0"
                                        class="inline-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs mb-1">Condemnable</label>
                                    <input type="number" id="inlineCondemnable" value="0" min="0"
                                        class="inline-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs mb-1">Total Quantity</label>
                                    <input type="number" id="inlineTotalQuantity" readonly
                                        class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-sm font-semibold">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" id="inlineAddAcquisitionBtn"
                                class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                                ➕ Add Acquisition
                            </button>
                        </div>
                    </div>

                    {{-- Acquisition List --}}
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-3 text-gray-700">Acquisition List</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border border-gray-300 text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border border-gray-300 px-2 py-1 text-left">Library</th>
                                        <th class="border border-gray-300 px-2 py-1">Source</th>
                                        <th class="border border-gray-300 px-2 py-1">Date</th>
                                        <th class="border border-gray-300 px-2 py-1">Cost</th>
                                        <th class="border border-gray-300 px-2 py-1">IAR</th>
                                        <th class="border border-gray-300 px-2 py-1">Remarks</th>
                                        <th class="border border-gray-300 px-2 py-1">Usable</th>
                                        <th class="border border-gray-300 px-2 py-1">PD</th>
                                        <th class="border border-gray-300 px-2 py-1">Damaged</th>
                                        <th class="border border-gray-300 px-2 py-1">Lost</th>
                                        <th class="border border-gray-300 px-2 py-1">Cond.</th>
                                        <th class="border border-gray-300 px-2 py-1">Total</th>
                                        <th class="border border-gray-300 px-2 py-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="inlineAcquisitionTableBody">
                                    <tr>
                                        <td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added yet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-between pt-6">
                        <button type="button" id="clearSelectedResource2"
                            class="text-sm text-gray-500 hover:text-gray-700 underline">
                            ← Back to search results
                        </button>
                        <button type="submit" id="inlineSaveBtn"
                            class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span id="inlineSaveBtnText">Save Acquisition(s)</span>
                            <span id="inlineSaveBtnLoading" class="hidden">
                                <svg class="inline animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>{{-- end #addAcquisitionSection --}}

        </div>{{-- end #tab-search --}}

        {{-- ===================== TAB: MANUAL INPUT ===================== --}}
        <div id="tab-manual" class="hidden">
            @include('pages.components.add-print-resource')
        </div>

    </div>
</div>

{{-- =================== DETAIL MODAL =================== --}}
<div id="detailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
    <div id="modalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>

    <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl z-10 mb-10">

            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Resource Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="modalLoading" class="flex justify-center items-center py-20">
                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>

            <div id="modalBody" class="hidden p-6 space-y-6">
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

                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-3">
                        Available Editions
                        <span class="text-xs font-normal text-gray-400 ml-1">— click Add on the edition you want</span>
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

{{-- =================== JAVASCRIPT =================== --}}
<script>
(function () {
    /* ================================================================
       TAB SWITCHING  (mirrors add-resource-index.js pattern)
    ================================================================ */
    const tabBtns   = document.querySelectorAll('.print-tab-btn');
    const tabSearch = document.getElementById('tab-search');
    const tabManual = document.getElementById('tab-manual');
    const STORAGE_KEY = 'add_print_resource_active_tab';

    function activateTab(tab) {
        tabBtns.forEach(btn => {
            const isActive = btn.dataset.tab === tab;
            btn.classList.toggle('border-blue-600', isActive);
            btn.classList.toggle('text-blue-600', isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-600', !isActive);
            btn.classList.toggle('hover:text-blue-600', !isActive);
            btn.classList.toggle('hover:border-gray-300', !isActive);
        });
        tabSearch.classList.toggle('hidden', tab !== 'search');
        tabManual.classList.toggle('hidden', tab !== 'manual');
        sessionStorage.setItem(STORAGE_KEY, tab);
    }

    tabBtns.forEach(btn => btn.addEventListener('click', () => activateTab(btn.dataset.tab)));

    const savedTab = sessionStorage.getItem(STORAGE_KEY);
    activateTab(savedTab === 'manual' ? 'manual' : 'search');

    window.addEventListener('beforeunload', () => {
        if (document.visibilityState === 'hidden') sessionStorage.removeItem(STORAGE_KEY);
    });

    /* ================================================================
       SEARCH
    ================================================================ */
    const searchInput   = document.getElementById('searchInput');
    const searchBtn     = document.getElementById('searchBtn');
    const spinner       = document.getElementById('searchSpinner');
    const resultsArea   = document.getElementById('resultsArea');
    const resultsList   = document.getElementById('resultsList');
    const resultCount   = document.getElementById('resultCount');
    const emptyState    = document.getElementById('emptyState');
    const initialHint   = document.getElementById('initialHint');
    const acqSection    = document.getElementById('addAcquisitionSection');

    let searchTimeout = null;

    function performSearch() {
        const q = searchInput.value.trim();
        if (q.length < 2) return;

        showSpinner(true);
        hideSearchStates();

        fetch(`{{ route('search-print-resource.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            showSpinner(false);
            if (!data.length) { emptyState.classList.remove('hidden'); return; }
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

    function renderResults(titles) {
        resultsList.innerHTML = '';
        resultCount.textContent = `${titles.length} title(s) found`;
        resultsArea.classList.remove('hidden');

        titles.forEach(title => {
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
                        <button data-title-id="${esc(title.id)}"
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

    /* ================================================================
       MODAL
    ================================================================ */
    const detailModal       = document.getElementById('detailModal');
    const modalBackdrop     = document.getElementById('modalBackdrop');
    const closeModalBtn     = document.getElementById('closeModal');
    const modalLoading      = document.getElementById('modalLoading');
    const modalBody         = document.getElementById('modalBody');
    const modalEditionsBody = document.getElementById('modalEditionsBody');

    function openModal(titleId) {
        detailModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        modalBody.classList.add('hidden');
        modalLoading.classList.remove('hidden');

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
            modalLoading.innerHTML = '<p class="text-red-500 text-sm px-8">Failed to load details. Please try again.</p>';
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
        document.getElementById('modalCover').src              = d.cover;
        document.getElementById('modalBookTitle').textContent  = d.title;
        document.getElementById('modalAuthors').textContent    = d.authors;
        document.getElementById('modalSubjects').textContent   = d.subjects;

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
                    <button type="button"
                        data-resource-id="${esc(e.id)}"
                        data-cover="${esc(e.cover)}"
                        data-title="${esc(d.title)}"
                        data-authors="${esc(d.authors)}"
                        data-subjects="${esc(d.subjects)}"
                        data-type="${esc(e.type)}"
                        data-publisher="${esc(e.publisher)}"
                        data-edition="${esc(e.edition)}"
                        data-copyright="${esc(e.copyright)}"
                        class="edition-add-btn inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap font-medium">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add
                    </button>
                </td>
            `;
            modalEditionsBody.appendChild(tr);
        });

        // Wire Add buttons
        modalEditionsBody.querySelectorAll('.edition-add-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectEdition(this.dataset);
                closeModalFn();
            });
        });
    }

    /* ================================================================
       SELECT EDITION → show inline acquisition form
    ================================================================ */
    function selectEdition(data) {
        // Set form action dynamically with the real resource ID
        document.getElementById('inlineAcquisitionForm').action =
            `/resources/search-print/${data.resourceId}/add-inline`;

        // Hide search results so only acquisition form is visible
        hideSearchResults();

        // Populate banner
        document.getElementById('selectedResourceId').value      = data.resourceId;
        document.getElementById('selectedCover').src             = data.cover;
        document.getElementById('selectedTitle').textContent     = data.title;
        document.getElementById('selectedAuthors').textContent   = data.authors;
        document.getElementById('selectedType').textContent      = data.type;
        document.getElementById('selectedPublisher').textContent = data.publisher || '-';
        document.getElementById('selectedEdition').textContent   = data.edition || '-';
        document.getElementById('selectedCopyright').textContent = data.copyright || '-';
        document.getElementById('selectedSubjects').textContent  = data.subjects || '-';

        // Show acquisition section and scroll to it
        acqSection.classList.remove('hidden');
        acqSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Clear selected resource
    function clearSelection() {
        acqSection.classList.add('hidden');
        document.getElementById('selectedResourceId').value = '';
        inlineAcquisitions.length = 0;
        renderInlineTable();
        updateInlineHidden();
    }

    document.getElementById('clearSelectedResource').addEventListener('click', clearSelection);
    document.getElementById('clearSelectedResource2').addEventListener('click', clearSelection);

    /* ================================================================
       INLINE ACQUISITION MANAGER
    ================================================================ */
    const inlineAcquisitions = [];
    let inlineEditIndex = null;

    const inlineTableBody = document.getElementById('inlineAcquisitionTableBody');
    const inlineHidden    = document.getElementById('inlineAcquisitionsInput');
    const inlineAddBtn    = document.getElementById('inlineAddAcquisitionBtn');
    const inlineTotalField = document.getElementById('inlineTotalQuantity');
    const inlineForm       = document.getElementById('inlineAcquisitionForm');
    const inlineSaveBtn    = document.getElementById('inlineSaveBtn');
    const inlineSaveBtnText    = document.getElementById('inlineSaveBtnText');
    const inlineSaveBtnLoading = document.getElementById('inlineSaveBtnLoading');

    const libraryEl = document.getElementById('acq_library_id');
    const libraryNameEl = document.getElementById('acq_library_name');

    // Sync library name hidden input (for select)
    if (libraryEl && libraryEl.tagName === 'SELECT') {
        libraryEl.addEventListener('change', syncLibraryName);
    }
    function syncLibraryName() {
        if (!libraryEl || libraryEl.tagName !== 'SELECT') return;
        const opt = libraryEl.options[libraryEl.selectedIndex];
        if (libraryNameEl) libraryNameEl.value = opt ? (opt.dataset.name || opt.text) : '';
    }

    function getLibraryId() {
        return libraryEl ? libraryEl.value : '';
    }
    function getLibraryName() {
        if (!libraryEl) return '';
        if (libraryEl.tagName === 'SELECT') {
            const opt = libraryEl.options[libraryEl.selectedIndex];
            return opt ? (opt.dataset.name || opt.text) : '';
        }
        return libraryEl.dataset.name || '';
    }

    // Quantity calc
    document.querySelectorAll('.inline-qty').forEach(input => {
        input.addEventListener('input', calcInlineTotal);
    });
    function calcInlineTotal() {
        let total = 0;
        document.querySelectorAll('.inline-qty').forEach(inp => { total += parseInt(inp.value) || 0; });
        inlineTotalField.value = total;
    }

    function getInlineFieldValues() {
        return {
            library_id:        getLibraryId(),
            library_name:      getLibraryName(),
            source:            document.getElementById('inlineSource').value,
            date_acquired:     document.getElementById('inlineDate').value,
            cost:              document.getElementById('inlineCost').value,
            iar:               document.getElementById('inlineIar').value,
            remarks:           document.getElementById('inlineRemarks').value,
            usable:            document.getElementById('inlineUsable').value,
            partially_damaged: document.getElementById('inlinePartiallyDamaged').value,
            damaged:           document.getElementById('inlineDamaged').value,
            lost:              document.getElementById('inlineLost').value,
            condemnable:       document.getElementById('inlineCondemnable').value,
            total_quantity:    inlineTotalField.value,
        };
    }

    function resetInlineFields() {
        if (libraryEl && libraryEl.tagName === 'SELECT') libraryEl.selectedIndex = 0;
        document.getElementById('inlineSource').value            = '';
        document.getElementById('inlineDate').value              = '';
        document.getElementById('inlineCost').value              = '';
        document.getElementById('inlineIar').value               = '';
        document.getElementById('inlineRemarks').value           = '';
        document.getElementById('inlineUsable').value            = '0';
        document.getElementById('inlinePartiallyDamaged').value  = '0';
        document.getElementById('inlineDamaged').value           = '0';
        document.getElementById('inlineLost').value              = '0';
        document.getElementById('inlineCondemnable').value       = '0';
        calcInlineTotal();
    }

    function setInlineFieldValues(acq) {
        if (libraryEl && libraryEl.tagName === 'SELECT' && acq.library_id) libraryEl.value = acq.library_id;
        document.getElementById('inlineSource').value            = acq.source ?? '';
        document.getElementById('inlineDate').value              = acq.date_acquired ?? '';
        document.getElementById('inlineCost').value              = acq.cost ?? '';
        document.getElementById('inlineIar').value               = acq.iar ?? '';
        document.getElementById('inlineRemarks').value           = acq.remarks ?? '';
        document.getElementById('inlineUsable').value            = acq.usable ?? '0';
        document.getElementById('inlinePartiallyDamaged').value  = acq.partially_damaged ?? '0';
        document.getElementById('inlineDamaged').value           = acq.damaged ?? '0';
        document.getElementById('inlineLost').value              = acq.lost ?? '0';
        document.getElementById('inlineCondemnable').value       = acq.condemnable ?? '0';
        calcInlineTotal();
    }

    inlineAddBtn.addEventListener('click', () => {
        const acq = getInlineFieldValues();
        if (!acq.library_id) { alert('Please select a library.'); return; }
        if (!acq.source || !acq.date_acquired) { alert('Source and Date Acquired are required.'); return; }
        if ((parseInt(acq.total_quantity) || 0) < 1) { alert('Total Quantity must be at least 1.'); return; }

        if (inlineEditIndex !== null) {
            inlineAcquisitions[inlineEditIndex] = acq;
            inlineEditIndex = null;
            inlineAddBtn.textContent = '➕ Add Acquisition';
        } else {
            inlineAcquisitions.push(acq);
        }
        renderInlineTable();
        resetInlineFields();
        updateInlineHidden();
    });

    function renderInlineTable() {
        inlineTableBody.innerHTML = '';
        if (!inlineAcquisitions.length) {
            inlineTableBody.innerHTML = '<tr><td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added yet</td></tr>';
            return;
        }
        inlineAcquisitions.forEach((acq, idx) => {
            const shortRemark  = (acq.remarks?.length > 30) ? acq.remarks.substring(0, 27) + '...' : (acq.remarks || '-');
            const shortLibrary = (acq.library_name?.length > 25) ? acq.library_name.substring(0, 22) + '...' : (acq.library_name || '-');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border px-2 py-1 text-xs" title="${esc(acq.library_name)}">${esc(shortLibrary)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.source)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.date_acquired)}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.cost) || '-'}</td>
                <td class="border px-2 py-1 text-xs">${esc(acq.iar) || '-'}</td>
                <td class="border px-2 py-1 text-xs">${esc(shortRemark)}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.usable || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.partially_damaged || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.damaged || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.lost || 0}</td>
                <td class="border px-2 py-1 text-center text-xs">${acq.condemnable || 0}</td>
                <td class="border px-2 py-1 text-center text-xs font-semibold">${acq.total_quantity || 0}</td>
                <td class="border px-2 py-1 text-center">
                    <div class="flex justify-center gap-1">
                        <button type="button" data-action="edit" data-index="${idx}"
                            class="p-1 rounded hover:bg-blue-100 text-blue-600" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                            </svg>
                        </button>
                        <button type="button" data-action="delete" data-index="${idx}"
                            class="p-1 rounded hover:bg-red-100 text-red-600" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            inlineTableBody.appendChild(tr);
        });
    }

    inlineTableBody.addEventListener('click', e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const idx = parseInt(btn.dataset.index);
        if (btn.dataset.action === 'edit') {
            inlineEditIndex = idx;
            inlineAddBtn.textContent = '✔ Update Acquisition';
            setInlineFieldValues(inlineAcquisitions[idx]);
            libraryEl?.scrollIntoView({ behavior: 'smooth' });
        } else if (btn.dataset.action === 'delete') {
            if (!confirm('Remove this acquisition?')) return;
            inlineAcquisitions.splice(idx, 1);
            if (inlineEditIndex === idx) { inlineEditIndex = null; inlineAddBtn.textContent = '➕ Add Acquisition'; }
            renderInlineTable();
            updateInlineHidden();
        }
    });

    function updateInlineHidden() {
        inlineHidden.value = JSON.stringify(inlineAcquisitions);
    }

    inlineForm.addEventListener('submit', e => {
        if (!document.getElementById('selectedResourceId').value) {
            e.preventDefault();
            alert('No resource selected.');
            return;
        }
        if (!inlineAcquisitions.length) {
            e.preventDefault();
            alert('Please add at least one acquisition before saving.');
            return;
        }
        updateInlineHidden();
        inlineSaveBtn.disabled = true;
        inlineSaveBtnText.classList.add('hidden');
        inlineSaveBtnLoading.classList.remove('hidden');
    });

    /* ================================================================
       HELPERS
    ================================================================ */
    function showSpinner(show) { spinner.classList.toggle('hidden', !show); }

    function hideSearchStates() {
        resultsArea.classList.add('hidden');
        emptyState.classList.add('hidden');
        initialHint.classList.add('hidden');
    }

    function hideSearchResults() {
        resultsArea.classList.add('hidden');
        emptyState.classList.add('hidden');
        initialHint.classList.add('hidden');
        searchInput.value = '';
        resultsList.innerHTML = '';
    }

    function esc(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }
})();
</script>

@vite(['resources/js/add-print-resource.js'])
@endsection
