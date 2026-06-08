<form method="GET" data-ajax class="bg-white p-4 rounded-xl shadow space-y-4">

    @if(request()->has('division') && request('division') !== 'all')
    <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
        <svg class="w-4 h-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Showing resources for the division selected from the <strong>BOSY Status</strong> chart.</span>
        <a href="{{ route('print-resources') }}" class="ml-auto font-medium underline hover:text-gray-900 whitespace-nowrap">Clear filter</a>
    </div>
    @endif

    <!--  SEARCH  -->
    <div class="flex gap-2">
        <div class="relative flex-1">
            <input type="text" name="search"
                placeholder="Search School Library... Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                value="{{ request('search') }}" class="w-full pl-10 py-2 border rounded-lg text-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
        </div>

        <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2">
            Search
        </button>
    </div>

    <!-- FILTERS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <select name="division" id="division" class="h-10 border px-3 rounded-lg">
            <option value="all" {{ request('division') === 'all' ? 'selected' : '' }}>
                All Divisions
            </option>
            @foreach ($divisions as $div)
                <option value="{{ $div->id }}" {{ request('division') == $div->id ? 'selected' : '' }}>
                    {{ $div->division_name }}
                </option>
            @endforeach
        </select>

        <select name="district" id="district" class="h-10 border px-3 rounded-lg">
            <option value="all" {{ request('district') === 'all' ? 'selected' : '' }}>
                All Districts
            </option>
            @if (request('division') && request('division') !== 'all')
                @foreach ($districts as $d)
                    <option value="{{ $d->id }}" {{ request('district') == $d->id ? 'selected' : '' }}>
                        {{ $d->district_name }}
                    </option>
                @endforeach
            @endif
        </select>

        <select name="school" id="school" class="h-10 border px-3 rounded-lg">
            <option value="all" {{ request('school') === 'all' ? 'selected' : '' }}>
                All Schools
            </option>
            @if (request('district') && request('district') !== 'all')
                @foreach ($schools as $s)
                    <option value="{{ $s->id }}" {{ request('school') == $s->id ? 'selected' : '' }}>
                        {{ $s->school_name }}
                    </option>
                @endforeach
            @endif
        </select>

        <!-- Load + Reset -->
        <div class="flex gap-3">
            <button type="submit" class="h-10 w-32 bg-blue-600 text-white rounded-lg">
                Load Data
            </button>

            <button type="button" id="resetFilters"
                class="h-10 w-32 bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg">
                Reset
            </button>
        </div>
    </div>

    <!-- Hidden view input – carries current view through search/pagination -->
    <input type="hidden" name="view" id="view-input" value="{{ request('view', 'card') }}">
    <!-- Hidden per-page input – kept in sync by the entries selector -->
    <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">
</form>

<div id="table-results-container">
    @if (request()->has('division') || request()->has('district') || request()->has('school'))

        <!-- Toolbar: Export + Per Page + View Toggle -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-4">
            
            <!-- Export Button -->
            <a href="{{ route('print-resources.export', request()->query()) }}"
                class="inline-flex items-center justify-center sm:justify-start gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors text-sm font-medium w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="hidden xs:inline">Export to Excel</span>
                <span class="xs:hidden">Export to Excel</span>
            </a>

            <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                
                <!-- Per Page Selector -->
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label for="region-per-page" class="whitespace-nowrap font-medium hidden sm:inline">Show entries:</label>
                    <label for="region-per-page" class="whitespace-nowrap font-medium sm:hidden">Show:</label>
                    <select id="region-per-page"
                        class="per-page-select border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        data-context="region">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- View Toggle Buttons -->
                <div class="flex items-center bg-gray-100 p-1 rounded-xl">
                                        <button type="button"
                        class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 text-gray-500 hover:text-gray-700"
                        data-view="card">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span class="hidden md:inline">Cards</span>
                    <button type="button"
                        class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 bg-white shadow text-blue-600"
                        data-view="table">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M3 6h18M3 14h18M3 18h18" />
                        </svg>
                        <span class="hidden md:inline">Table</span>
                    </button>
                    

                    </button>
                </div>
            </div>
        </div>

        <!-- ── TABLE VIEW ── -->
        <div id="table-view" class="bg-white rounded-xl shadow overflow-hidden mt-4">
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
                                <!-- Clickable Cover -->
                                <td class="px-2 py-3">
                                    <img 
                                        src="{{ $item->thumb_url }}" 
                                        alt="{{ $item->printTitle->title }}"
                                        class="w-12 h-16 object-cover rounded shadow cursor-pointer hover:scale-105 transition-transform duration-200"
                                        loading="lazy"
                                        onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)))'
                                        title="Click to view details">
                                </td>

                                <td class="px-2 py-3 font-medium text-gray-800 max-w-xs">
                                    {{ $item->printTitle->title }}
                                </td>
                                <td class="px-2 py-3 text-gray-600">{{ $authors }}</td>
                                <td class="px-2 py-3 text-gray-600">{{ $item->publisher }}</td>
                                <td class="px-2 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
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
                                                            {{ $sub->subject->subject_name }} —
                                                            {{ $sub->gradeLevel->grade }}
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
                                <td colspan="11" class="text-center py-8 text-gray-500">No resources found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($filteredResources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="p-4">
                    {{ $filteredResources->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

        <!-- ── CARD VIEW ── -->
        <div id="card-view" class="hidden mt-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @forelse ($filteredResources as $item)
                    @php
                        $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                        $qty = $item->scopedQuantities($filteredLibraryIds);
                        $total = array_sum($qty);
                    @endphp
                    <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group"
                         onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)))'>

                        <!-- Cover image -->
                        <div class="relative w-full" style="padding-bottom: 140%;">
                            <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                loading="lazy">

                            <!-- Total copies dot badge -->
                            <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2 py-0.5 rounded-full shadow
                                {{ $qty['usable'] > 0 ? 'text-green-700' : 'text-red-600' }}">
                                <span class="w-1.5 h-1.5 rounded-full inline-block {{ $qty['usable'] > 0 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $total }}
                            </span>
                        </div>

                        <!-- Card footer info -->
                        <div class="p-3 flex flex-col gap-1 flex-1">
                            <h3 class="text-xs font-semibold text-gray-900 leading-tight line-clamp-2">
                                {{ $item->printTitle->title }}
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
                    {{ $filteredResources->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    @else
        <div class="bg-white p-6 rounded-xl shadow text-center text-gray-600 mt-4">
            Select division/district/school and click "Load Data" to view resources.
        </div>
    @endif
</div>