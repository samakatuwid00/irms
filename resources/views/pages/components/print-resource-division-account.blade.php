@php
    $activeTab = request('tab', 'division');
    $currentUserTypeId = auth()->user()->usertype_id ?? '';
    $isSdoSupplyOfficer = $currentUserTypeId === 'fd43d1da-64c7-4be2-9f2c-d419f599404f';
    $perPage = $perPage ?? 10;
    $perPageOptions = $perPageOptions ?? [5, 10, 15, 20];
@endphp

<!-- Tab Navigation -->
<div class="bg-white rounded-t-xl shadow">
    <div class="flex border-b overflow-x-auto">
        <button type="button"
            class="tab-btn flex-shrink-0 px-6 py-3 font-medium text-sm transition-colors border-b-2 {{ $activeTab === 'division' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}"
            data-tab="division">
            Division
        </button>
        <button type="button"
            class="tab-btn flex-shrink-0 px-6 py-3 font-medium text-sm transition-colors border-b-2 {{ $activeTab === 'school' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}"
            data-tab="school">
            School
        </button>
    </div>
</div>

<!-- Tab Content Container -->
<div class="tab-content-wrapper">

    <!-- ============================================================ -->
    <!-- DIVISION LIBRARY TAB                                         -->
    <!-- ============================================================ -->
    <div id="division-tab" class="tab-content {{ $activeTab === 'school' ? 'hidden' : '' }}">
        <div class="bg-white p-4 rounded-b-xl shadow space-y-4">
            <form method="GET" data-ajax class="space-y-4" id="division-form">
                <input type="hidden" name="tab" value="division">
                <input type="hidden" name="division_view" id="division-view-input" value="{{ request('division_view', 'card') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">

                <!-- Search -->
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" name="division_search"
                            placeholder="Search Division Library... Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                            value="{{ request('division_search') }}"
                            class="w-full pl-10 py-2 border rounded-lg text-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2">Search</button>
                                            <button type="button" id="resetDivisionFilters"
                            class="h-10 w-32 bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg">
                            Reset
                        </button>
                </div>
            </form>
        </div>

        <div id="division-results-container">
            @if (request()->has('division_search') && request('division_search') !== '' || $resources->count() > 0)

                <!-- Toolbar: Export + Per Page + View Toggle for Division -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-4">
                    
                    <!-- Export Button -->
                    <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'division'])) }}"
                        class="inline-flex items-center justify-center sm:justify-start gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors text-sm font-medium w-full sm:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>

                    <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                        
                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <label for="division-per-page" class="whitespace-nowrap font-medium hidden sm:inline">Show entries:</label>
                            <label for="division-per-page" class="whitespace-nowrap font-medium sm:hidden">Show:</label>
                            <select id="division-per-page"
                                class="per-page-select border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                data-context="division">
                                @foreach ($perPageOptions as $opt)
                                    <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="flex items-center bg-gray-100 p-1 rounded-xl">
                            <button type="button"
                                class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 bg-white shadow text-blue-600"
                                data-target="division" data-view="card">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span class="hidden md:inline">Cards</span>
                            </button>
                            <button type="button"
                                class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 text-gray-500 hover:text-gray-700"
                                data-target="division" data-view="table">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M3 6h18M3 14h18M3 18h18" />
                                </svg>
                                <span class="hidden md:inline">Table</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── DIVISION TABLE VIEW ── -->
                <div id="division-table-view" class="hidden bg-white rounded-xl shadow overflow-hidden mt-4">
                    <div class="overflow-x-auto max-h-150 overflow-y-auto">
                        <table class="w-full text-sm text-center">
                            <thead class="bg-gray-100 text-gray-600 uppercase text-xs sticky top-0 z-10">
                                <tr>
                                    <th class="px-2 py-3">Cover</th>
                                    <th class="px-2 py-3">Title</th>
                                    <th class="px-2 py-3">Author</th>
                                    <th class="px-2 py-3">Publisher</th>
                                    <th class="px-2 py-3">Type</th>
                                    <th class="px-2 py-3">Subject</th>
                                    <th class="px-2 py-3">ISBN</th>
                                    <th class="px-2 py-3">Copyright</th>
                                    <th class="px-2 py-3 text-center">Quantity Breakdown</th>
                                    <th class="px-2 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($resources as $item)
                                    @php
                                        $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                        $qty = $item->scopedQuantities($mainLibraryIds);
                                        $total = array_sum($qty);
                                    @endphp
                                    <tr class="hover:bg-gray-50 border border-gray-300">
                                        <td class="px-2 py-3">
                                            <img 
                                                src="{{ $item->thumb_url }}" 
                                                alt="{{ $item->printTitle->title }}"
                                                class="w-12 h-16 object-cover rounded shadow cursor-pointer hover:scale-105 transition-transform duration-200"
                                                loading="lazy"
                                                onclick='openPrintModal(@json($item->showDetails($mainLibraryIds)))'
                                                title="Click to view details">
                                        </td>
                                        <td class="px-2 py-3 font-medium text-gray-800 max-w-xs">{{ $item->printTitle->title }}</td>
                                        <td class="px-2 py-3 text-gray-600">{{ $authors }}</td>
                                        <td class="px-2 py-3 text-gray-600">{{ $item->publisher }}</td>
                                        <td class="px-2 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">
                                                {{ $item->type->shortname }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-3 text-xs">
                                            @if ($item->subjects()->count())
                                                @php
                                                    $first = $item->subjects()->first();
                                                    $count = $item->subjects()->count();
                                                @endphp
                                                <div class="relative inline-block max-w-full"
                                                    @if ($count > 1) x-data
                                                    @mouseenter="
                                                        let trigger = $el;
                                                        let tooltip = $el.querySelector('[data-tooltip]');
                                                        let rect = trigger.getBoundingClientRect();
                                                        tooltip.style.left = (rect.left + window.scrollX) + 'px';
                                                        tooltip.style.top = (rect.bottom + window.scrollY + 8) + 'px';
                                                        tooltip.classList.remove('invisible', 'opacity-0');
                                                        tooltip.classList.add('visible', 'opacity-100');
                                                    "
                                                    @mouseleave="
                                                        let tooltip = $el.querySelector('[data-tooltip]');
                                                        tooltip.classList.add('invisible', 'opacity-0');
                                                        tooltip.classList.remove('visible', 'opacity-100');
                                                    " @endif>
                                                    <span class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full cursor-default">
                                                        {{ $first->subject->subject_name }} - {{ $first->gradeLevel->grade }}
                                                        @if ($count > 1)
                                                            <span class="ml-1 text-green-600">+{{ $count - 1 }}</span>
                                                        @endif
                                                    </span>
                                                    @if ($count > 1)
                                                        <div data-tooltip
                                                            class="pointer-events-none fixed z-[100] invisible opacity-0
                                                                    bg-gray-800 text-white text-xs rounded-md py-2 px-3 shadow-xl
                                                                    min-w-[220px] max-w-sm whitespace-normal break-words
                                                                    transition-opacity duration-150 border border-gray-700">
                                                            @foreach ($item->subjects() as $sub)
                                                                <div class="py-1 border-b border-gray-700 last:border-0">
                                                                    {{ $sub->subject->subject_name }} — {{ $sub->gradeLevel->grade }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-500 text-xs">No assignment</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-3 text-gray-600 font-mono text-xs">{{ $item->isbn }}</td>
                                        <td class="px-2 py-3 text-center">{{ $item->copyright }}</td>
                                        <td class="px-2 py-3 text-center text-xs">
                                            <div class="space-y-1">
                                                <div class="flex justify-center gap-3 text-gray-700">
                                                    <span title="Usable"><strong class="text-green-600">{{ $qty['usable'] }}</strong> Usable</span>
                                                    <span title="Partially Damaged"><strong class="text-yellow-600">{{ $qty['partially_damaged'] }}</strong> PD</span>
                                                </div>
                                                <div class="flex justify-center gap-3 text-gray-600">
                                                    <span title="Damaged"><strong class="text-red-600">{{ $qty['damaged'] }}</strong> Damaged</span>
                                                    <span title="Lost"><strong class="text-purple-600">{{ $qty['lost'] }}</strong> Lost</span>
                                                    <span title="Condemnable"><strong class="text-gray-800">{{ $qty['condemnable'] }}</strong> Cond.</span>
                                                </div>
                                                <div class="font-semibold text-gray-800 border-t pt-1">Total: {{ $total }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-center gap-2">
                                                <button onclick='openPrintModal(@json($item->showDetails($mainLibraryIds)))'
                                                    class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                                    View
                                                </button>
                                        @if(!$isSdoSupplyOfficer)
                                                <a href="{{ route('edit-resource', $item->id) }}"
                                                    class="px-3 py-1 text-xs rounded-lg bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isSdoSupplyOfficer ? '10' : '11' }}" class="text-center py-8 text-gray-500">No division resources found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($resources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="p-4">
                            {{ $resources->appends(array_merge(request()->query(), ['tab' => 'division']))->links('pagination::print-resource') }}
                        </div>
                    @endif
                </div>

                <!-- ── DIVISION CARD VIEW ── -->
                <div id="division-card-view" class="mt-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        @forelse ($resources as $item)
                            @php
                                $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                $qty = $item->scopedQuantities($mainLibraryIds);
                                $total = array_sum($qty);
                            @endphp
                            <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group"
                                 onclick='openPrintModal(@json($item->showDetails($mainLibraryIds)))'>
                                <div class="relative w-full" style="padding-bottom: 140%;">
                                    <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        loading="lazy">
                                    <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2 py-0.5 rounded-full shadow
                                        {{ $qty['usable'] > 0 ? 'text-green-700' : 'text-red-600' }}">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block {{ $qty['usable'] > 0 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        {{ $total }}
                                    </span>
                                    @if(!$isSdoSupplyOfficer)
                                    <a href="{{ route('edit-resource', $item->id) }}"
                                       onclick="event.stopPropagation()"
                                       class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 backdrop-blur-sm rounded-full p-1 shadow hover:bg-yellow-50"
                                       title="Edit">
                                        <svg class="w-3.5 h-3.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                                        </svg>
                                    </a>
                                    @endif
                                </div>
                                <div class="p-3 flex flex-col gap-1 flex-1">
                                    <h3 class="text-xs font-semibold text-gray-900 leading-tight line-clamp-2 inline-flex items-start gap-1">
                                        @include('pages.components.verified-badge', ['verified' => $item->verified, 'class' => 'w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5'])
                                        <span>{{ $item->printTitle->title }}</span>
                                    </h3>
                                    @if ($authors)
                                        <p class="text-xs text-gray-500 truncate">{{ $authors }}</p>
                                    @endif
                                    <div class="mt-auto pt-2 flex items-center justify-between gap-1">
                                        <span class="px-1.5 py-0.5 text-xs rounded bg-indigo-50 text-indigo-700 font-medium truncate max-w-[70%]">
                                            {{ $item->type->shortname }}
                                        </span>
                                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $total }} copies</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full bg-white rounded-xl shadow p-8 text-center text-gray-500">
                                No division resources found.
                            </div>
                        @endforelse
                    </div>

                    @if ($resources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="bg-white rounded-xl shadow p-4 mt-4">
                            {{ $resources->appends(array_merge(request()->query(), ['tab' => 'division']))->links('pagination::print-resource') }}
                        </div>
                    @endif
                </div>

            @else
                <div class="bg-white p-6 rounded-xl shadow text-center text-gray-600 mt-4">
                    Enter a search term and click "Search" or "Load Data" to view division library resources.
                </div>
            @endif
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SCHOOL LIBRARY TAB                                           -->
    <!-- ============================================================ -->
    <div id="school-tab" class="tab-content {{ $activeTab === 'school' ? '' : 'hidden' }}">
        <form method="GET" data-ajax class="bg-white p-4 rounded-b-xl shadow space-y-4" id="school-form">
            <input type="hidden" name="tab" value="school">
            <input type="hidden" name="school_view" id="school-view-input" value="{{ request('school_view', 'card') }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">

            @if($activeTab === 'school' && request()->has('school') && request('school') !== 'all')
            <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Showing resources for the school selected from the <strong>BOSY Status</strong> chart.</span>
                <a href="{{ route('print-resources') }}?tab=school" class="ml-auto font-medium underline hover:text-blue-900 whitespace-nowrap">Clear filter</a>
            </div>
            @endif

            <!-- Search -->
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input type="text" name="school_search"
                        placeholder="Search School Library... Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                        value="{{ request('school_search') }}"
                        class="w-full pl-10 py-2 border rounded-lg text-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>
                <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2">Search</button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="district" id="district" class="border px-3 py-2 rounded-lg">
                    <option value="all" {{ request('district') === 'all' ? 'selected' : '' }}>All Districts
                    </option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}"
                            {{ request('district') == $district->id ? 'selected' : '' }}>
                            {{ $district->district_name }}
                        </option>
                    @endforeach
                </select>

                <select name="school" id="school" class="border px-3 py-2 rounded-lg">
                    <option value="all" {{ request('school') === 'all' ? 'selected' : '' }}>All Schools</option>
                    @if (request('district') && request('district') !== 'all')
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}"
                                {{ request('school') == $school->id ? 'selected' : '' }}>
                                {{ $school->school_name }}
                            </option>
                        @endforeach
                    @endif
                </select>

                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2">Load Data</button>
                    <button type="button"
                        class="reset-school bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg px-4 py-2">
                        Reset
                    </button>
                </div>
            </div>
        </form>

        <div id="school-results-container">
            @if (request()->has('district') || request()->has('school') || request()->has('school_search'))

                <!-- Toolbar: Export + Per Page + View Toggle for School -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-4">
                    
                    <!-- Export Button -->
                    <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'school'])) }}"
                        class="inline-flex items-center justify-center sm:justify-start gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors text-sm font-medium w-full sm:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>

                    <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                        
                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <label for="school-per-page" class="whitespace-nowrap font-medium hidden sm:inline">Show entries:</label>
                            <label for="school-per-page" class="whitespace-nowrap font-medium sm:hidden">Show:</label>
                            <select id="school-per-page"
                                class="per-page-select border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                data-context="school">
                                @foreach ($perPageOptions as $opt)
                                    <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="flex items-center bg-gray-100 p-1 rounded-xl">
                            <button type="button"
                                class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 bg-white shadow text-blue-600"
                                data-target="school" data-view="card">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span class="hidden md:inline">Cards</span>
                            </button>
                            <button type="button"
                                class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 text-gray-500 hover:text-gray-700"
                                data-target="school" data-view="table">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M3 6h18M3 14h18M3 18h18" />
                                </svg>
                                <span class="hidden md:inline">Table</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── SCHOOL TABLE VIEW ── -->
                <div id="school-table-view" class="hidden bg-white rounded-xl shadow overflow-hidden mt-4">
                    <div class="overflow-x-auto max-h-150 overflow-y-auto">
                        <table class="w-full text-sm text-center">
                            <thead class="bg-gray-100 text-gray-600 uppercase text-xs sticky top-0 z-10">
                                <tr>
                                    <th class="px-2 py-3">Cover</th>
                                    <th class="px-2 py-3">Title</th>
                                    <th class="px-2 py-3">Author</th>
                                    <th class="px-2 py-3">Publisher</th>
                                    <th class="px-2 py-3">Type</th>
                                    <th class="px-2 py-3">Subject</th>
                                    <th class="px-2 py-3">ISBN</th>
                                    <th class="px-2 py-3">Copyright</th>
                                    <th class="px-2 py-3 text-center">Quantity Breakdown</th>
                                    <th class="px-2 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($filteredResources as $item)
                                    @php
                                        $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                        $qty = $item->scopedQuantities($filteredLibraryIds);
                                        $total = array_sum($qty);
                                    @endphp
                                    <tr class="hover:bg-gray-50 border border-gray-300">
                                        <td class="px-2 py-3">
                                            <img 
                                                src="{{ $item->thumb_url }}" 
                                                alt="{{ $item->printTitle->title }}"
                                                class="w-12 h-16 object-cover rounded shadow cursor-pointer hover:scale-105 transition-transform duration-200"
                                                loading="lazy"
                                                onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)))'
                                                title="Click to view details">
                                        </td>
                                        <td class="px-2 py-3 font-medium text-gray-800 max-w-xs">{{ $item->printTitle->title }}</td>
                                        <td class="px-2 py-3 text-gray-600">{{ $authors }}</td>
                                        <td class="px-2 py-3 text-gray-600">{{ $item->publisher }}</td>
                                        <td class="px-2 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item->type->shortname }}</span>
                                        </td>
                                        <td class="px-2 py-3 text-xs">
                                            @if ($item->subjects()->count())
                                                @php
                                                    $first = $item->subjects()->first();
                                                    $count = $item->subjects()->count();
                                                @endphp
                                                <div class="relative inline-block max-w-full"
                                                    @if ($count > 1) x-data
                                                    @mouseenter="
                                                        let trigger = $el;
                                                        let tooltip = $el.querySelector('[data-tooltip]');
                                                        let rect = trigger.getBoundingClientRect();
                                                        tooltip.style.left = (rect.left + window.scrollX) + 'px';
                                                        tooltip.style.top = (rect.bottom + window.scrollY + 8) + 'px';
                                                        tooltip.classList.remove('invisible', 'opacity-0');
                                                        tooltip.classList.add('visible', 'opacity-100');
                                                    "
                                                    @mouseleave="
                                                        let tooltip = $el.querySelector('[data-tooltip]');
                                                        tooltip.classList.add('invisible', 'opacity-0');
                                                        tooltip.classList.remove('visible', 'opacity-100');
                                                    " @endif>
                                                    <span class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full cursor-default">
                                                        {{ $first->subject->subject_name }} - {{ $first->gradeLevel->grade }}
                                                        @if ($count > 1)
                                                            <span class="ml-1 text-green-600">+{{ $count - 1 }}</span>
                                                        @endif
                                                    </span>
                                                    @if ($count > 1)
                                                        <div data-tooltip
                                                            class="pointer-events-none fixed z-[100] invisible opacity-0
                                                                    bg-gray-800 text-white text-xs rounded-md py-2 px-3 shadow-xl
                                                                    min-w-[220px] max-w-sm whitespace-normal break-words
                                                                    transition-opacity duration-150 border border-gray-700">
                                                            @foreach ($item->subjects() as $sub)
                                                                <div class="py-1 border-b border-gray-700 last:border-0">
                                                                    {{ $sub->subject->subject_name }} — {{ $sub->gradeLevel->grade }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-500 text-xs">No assignment</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-3 text-gray-600 font-mono text-xs">{{ $item->isbn }}</td>
                                        <td class="px-2 py-3 text-center">{{ $item->copyright }}</td>
                                        <td class="px-2 py-3 text-center text-xs">
                                            <div class="space-y-1">
                                                <div class="flex justify-center gap-3 text-gray-700">
                                                    <span title="Usable"><strong class="text-green-600">{{ $qty['usable'] }}</strong> Usable</span>
                                                    <span title="Partially Damaged"><strong class="text-yellow-600">{{ $qty['partially_damaged'] }}</strong> PD</span>
                                                </div>
                                                <div class="flex justify-center gap-3 text-gray-600">
                                                    <span title="Damaged"><strong class="text-red-600">{{ $qty['damaged'] }}</strong> Damaged</span>
                                                    <span title="Lost"><strong class="text-purple-600">{{ $qty['lost'] }}</strong> Lost</span>
                                                    <span title="Condemnable"><strong class="text-gray-800">{{ $qty['condemnable'] }}</strong> Cond.</span>
                                                </div>
                                                <div class="font-semibold text-gray-800 border-t pt-1">Total: {{ $total }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-center gap-2">
                                                <button onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)))'
                                                    class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                                    View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-8 text-gray-500">No resources found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($filteredResources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="p-4">
                            {{ $filteredResources->appends(array_merge(request()->query(), ['tab' => 'school']))->links('pagination::print-resource') }}
                        </div>
                    @endif
                </div>

                <!-- ── SCHOOL CARD VIEW ── -->
                <div id="school-card-view" class="mt-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        @forelse ($filteredResources as $item)
                            @php
                                $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                $qty = $item->scopedQuantities($filteredLibraryIds);
                                $total = array_sum($qty);
                            @endphp
                            <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group"
                                 onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)))'>
                                <div class="relative w-full" style="padding-bottom: 140%;">
                                    <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        loading="lazy">
                                    <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2 py-0.5 rounded-full shadow
                                        {{ $qty['usable'] > 0 ? 'text-green-700' : 'text-red-600' }}">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block {{ $qty['usable'] > 0 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        {{ $total }}
                                    </span>
                                </div>
                                <div class="p-3 flex flex-col gap-1 flex-1">
                                    <h3 class="text-xs font-semibold text-gray-900 leading-tight line-clamp-2 inline-flex items-start gap-1">
                                        @include('pages.components.verified-badge', ['verified' => $item->verified, 'class' => 'w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5'])
                                        <span>{{ $item->printTitle->title }}</span>
                                    </h3>
                                    @if ($authors)
                                        <p class="text-xs text-gray-500 truncate">{{ $authors }}</p>
                                    @endif
                                    <div class="mt-auto pt-2 flex items-center justify-between gap-1">
                                        <span class="px-1.5 py-0.5 text-xs rounded bg-blue-50 text-blue-700 font-medium truncate max-w-[70%]">
                                            {{ $item->type->shortname }}
                                        </span>
                                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $total }} copies</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full bg-white rounded-xl shadow p-8 text-center text-gray-500">
                                No resources found.
                            </div>
                        @endforelse
                    </div>

                    @if ($filteredResources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="bg-white rounded-xl shadow p-4 mt-4">
                            {{ $filteredResources->appends(array_merge(request()->query(), ['tab' => 'school']))->links('pagination::print-resource') }}
                        </div>
                    @endif
                </div>

            @else
                <div class="bg-white p-6 rounded-xl shadow text-center text-gray-600 mt-4">
                    Select district/school and click "Load Data" to view school library resources.
                </div>
            @endif
        </div>
    </div>
</div>


<!-- ============================================================ -->
<!-- TAB + INTERACTION JAVASCRIPT                                 -->
<!-- ============================================================ -->
<script>
(function () {
    'use strict';

    // ── Tab switching with URL update ──────────────────────────
    function switchTab(targetTab) {
        // Update URL without reload
        const url = new URL(window.location.href);
        url.searchParams.set('tab', targetTab);
        window.history.pushState({}, '', url);
        
        // Update active tab button styles
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.classList.remove('border-blue-600', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-600');
            if (btn.dataset.tab === targetTab) {
                btn.classList.add('border-blue-600', 'text-blue-600');
                btn.classList.remove('border-transparent', 'text-gray-600');
            }
        });

        // Show/hide tab panels
        document.querySelectorAll('.tab-content').forEach(function (panel) {
            panel.classList.add('hidden');
        });
        var panel = document.getElementById(targetTab + '-tab');
        if (panel) panel.classList.remove('hidden');
        
        // Store current tab in session storage
        sessionStorage.setItem('activeTab', targetTab);
    }

    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var target = this.dataset.tab;
            switchTab(target);
        });
    });

    // ── AJAX Form Submission Handler ──────────────────────────
    function handleFormSubmit(form, containerId) {
        const formData = new FormData(form);
        const url = new URL(form.action || window.location.href);
        
        // Add form data to URL
        for (let [key, value] of formData.entries()) {
            if (value) url.searchParams.set(key, value);
        }
        
        // Show loading state
        const container = document.getElementById(containerId);
        if (container) {
        }
        
        // Fetch data
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            // Parse the response HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract the relevant container content
            let newContent = '';
            if (containerId === 'division-results-container') {
                const divisionResults = doc.getElementById('division-results-container');
                if (divisionResults) newContent = divisionResults.innerHTML;
            } else if (containerId === 'school-results-container') {
                const schoolResults = doc.getElementById('school-results-container');
                if (schoolResults) newContent = schoolResults.innerHTML;
            }
            
            if (container && newContent) {
                container.innerHTML = newContent;
                // Reinitialize event handlers for the new content
                reinitializeEventHandlers();
            }
            
            // Update URL without reload
            window.history.pushState({}, '', url);
        })
        .catch(error => {
            console.error('Error:', error);
            if (container) {
                container.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-center">Error loading data. Please try again.</div>';
            }
        });
    }

    // ── Division Form Handler ──────────────────────────────────
    const divisionForm = document.getElementById('division-form');
    if (divisionForm) {
        divisionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this, 'division-results-container');
        });
    }

    // ── School Tab Form Handler ───────────────────────────────
    const schoolForm = document.getElementById('school-form');
    if (schoolForm) {
        schoolForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this, 'school-results-container');
        });
    }

    // ── Restore active tab on page load ───────────────────────
    const savedTab = sessionStorage.getItem('activeTab') || 
                     new URLSearchParams(window.location.search).get('tab') || 
                     'division';
    switchTab(savedTab);

    // ── View toggle (cards / table) with AJAX ─────────────────
    function updateView(target, view) {
        // Update hidden input
        const viewInput = document.getElementById(target + '-view-input');
        if (viewInput) viewInput.value = view;
        
        // Update UI
        const tablePanel = document.getElementById(target + '-table-view');
        const cardPanel = document.getElementById(target + '-card-view');
        
        if (tablePanel && cardPanel) {
            if (view === 'table') {
                tablePanel.classList.remove('hidden');
                cardPanel.classList.add('hidden');
            } else {
                cardPanel.classList.remove('hidden');
                tablePanel.classList.add('hidden');
            }
        }
        
        // Save view preference
        localStorage.setItem(target + '_view', view);
    }
    
    document.querySelectorAll('.view-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = this.dataset.target;
            var view = this.dataset.view;
            
            // Toggle button active state
            document.querySelectorAll('[data-target="' + target + '"].view-toggle-btn').forEach(function (b) {
                b.classList.remove('bg-white', 'shadow', 'text-blue-600');
                b.classList.add('text-gray-500', 'hover:text-gray-700');
            });
            this.classList.add('bg-white', 'shadow', 'text-blue-600');
            this.classList.remove('text-gray-500', 'hover:text-gray-700');
            
            updateView(target, view);
        });
    });
    
    // Restore saved views
    ['division', 'school'].forEach(target => {
        const savedView = localStorage.getItem(target + '_view');
        if (savedView) {
            updateView(target, savedView);
            // Also update the toggle button state
            const btn = document.querySelector(`.view-toggle-btn[data-target="${target}"][data-view="${savedView}"]`);
            if (btn) {
                document.querySelectorAll(`[data-target="${target}"].view-toggle-btn`).forEach(b => {
                    b.classList.remove('bg-white', 'shadow', 'text-blue-600');
                    b.classList.add('text-gray-500', 'hover:text-gray-700');
                });
                btn.classList.add('bg-white', 'shadow', 'text-blue-600');
                btn.classList.remove('text-gray-500', 'hover:text-gray-700');
            }
        }
    });

    // ── Per-page selectors with AJAX ──────────────────────────
    document.querySelectorAll('.per-page-select').forEach(function (sel) {
        sel.removeEventListener('change', sel._listener);
        sel._listener = function() {
            const context = this.dataset.context;
            const form = context === 'division' ? divisionForm : schoolForm;
            if (form) {
                const perPageInputs = form.querySelectorAll('.per-page-hidden-input');
                perPageInputs.forEach(input => input.value = this.value);
                form.dispatchEvent(new Event('submit'));
            }
        };
        sel.addEventListener('change', sel._listener);
    });

    // ── Reinitialize event handlers after AJAX load ───────────
    function reinitializeEventHandlers() {
        // Reattach view toggle handlers
        document.querySelectorAll('.view-toggle-btn').forEach(function (btn) {
            btn.removeEventListener('click', btn._listener);
            btn._listener = function() {
                var target = this.dataset.target;
                var view = this.dataset.view;
                document.querySelectorAll('[data-target="' + target + '"].view-toggle-btn').forEach(function (b) {
                    b.classList.remove('bg-white', 'shadow', 'text-blue-600');
                    b.classList.add('text-gray-500', 'hover:text-gray-700');
                });
                this.classList.add('bg-white', 'shadow', 'text-blue-600');
                this.classList.remove('text-gray-500', 'hover:text-gray-700');
                updateView(target, view);
            };
            btn.addEventListener('click', btn._listener);
        });
        
        // Reattach per-page select handlers
        document.querySelectorAll('.per-page-select').forEach(function (sel) {
            sel.removeEventListener('change', sel._listener);
            sel._listener = function() {
                const context = this.dataset.context;
                const form = context === 'division' ? divisionForm : schoolForm;
                if (form) {
                    const perPageInputs = form.querySelectorAll('.per-page-hidden-input');
                    perPageInputs.forEach(input => input.value = this.value);
                    form.dispatchEvent(new Event('submit'));
                }
            };
            sel.addEventListener('change', sel._listener);
        });

        // Reattach form submit handlers (in case they were replaced)
        const newDivisionForm = document.getElementById('division-form');
        if (newDivisionForm && !newDivisionForm._listener) {
            newDivisionForm._listener = function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'division-results-container');
            };
            newDivisionForm.addEventListener('submit', newDivisionForm._listener);
        }

        const newSchoolForm = document.getElementById('school-form');
        if (newSchoolForm && !newSchoolForm._listener) {
            newSchoolForm._listener = function(e) {
                e.preventDefault();
                handleFormSubmit(this, 'school-results-container');
            };
            newSchoolForm.addEventListener('submit', newSchoolForm._listener);
        }
    }

    // ── Reset buttons ──────────────────────────────────────────
    var resetDivision = document.getElementById('resetDivisionFilters');
    if (resetDivision) {
        resetDivision.addEventListener('click', function () {
            window.location.href = '{{ route('print-resources') }}?tab=division';
        });
    }

    var resetSchool = document.getElementById('resetSchoolFilters');
    if (resetSchool) {
        resetSchool.addEventListener('click', function () {
            window.location.href = '{{ route('print-resources') }}?tab=school';
        });
    }
    
    // ── School tab cascading ──────────────────────────────────
    const stDistrict = document.getElementById('school-tab-district');
    const stSchool = document.getElementById('school-tab-school');
    
    if (stDistrict && stSchool) {
        // Remove existing listeners to prevent duplicates
        const newDistrict = stDistrict.cloneNode(true);
        const newSchool = stSchool.cloneNode(true);
        stDistrict.parentNode.replaceChild(newDistrict, stDistrict);
        stSchool.parentNode.replaceChild(newSchool, stSchool);
        
        const finalDistrict = document.getElementById('school-tab-district');
        const finalSchool = document.getElementById('school-tab-school');
        
        if (finalDistrict) {
            finalDistrict.addEventListener('change', function () {
                var distId = this.value;
                // We need to reload schools via AJAX or use pre-loaded data
                // For simplicity, we'll submit the form to reload
                if (schoolForm) {
                    schoolForm.dispatchEvent(new Event('submit'));
                }
            });
        }
    }
    
    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        const tab = new URLSearchParams(window.location.search).get('tab') || 'division';
        switchTab(tab);
        // Reload current form data
        const activeForm = document.querySelector('.tab-content:not(.hidden) form[data-ajax]');
        if (activeForm) {
            activeForm.dispatchEvent(new Event('submit'));
        }
    });
    
})();
</script>