@php
    $activeTab = request('tab', 'division');
@endphp

<!-- Tab Navigation -->
<div class="bg-white rounded-t-xl shadow">
    <div class="flex border-b">
        <button type="button"
            class="tab-btn px-6 py-3 font-medium text-sm transition-colors border-b-2 {{ $activeTab === 'division' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}"
            data-tab="division">
            Division
        </button>
        <button type="button"
            class="tab-btn px-6 py-3 font-medium text-sm transition-colors border-b-2 {{ $activeTab === 'school' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800' }}"
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
        <div class="bg-white rounded-b-xl shadow p-4">
            <form method="GET" data-ajax class="flex gap-3 mb-4">
                <input type="hidden" name="tab" value="division">
                <input type="hidden" name="division_view" id="division-view-input" value="{{ request('division_view', 'card') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">

                <div class="relative w-full">
                    <input type="text" name="division_search"
                        placeholder="Search Division Library... Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                        value="{{ request('division_search') }}"
                        class="w-full h-10 pl-10 pr-3 border rounded-lg text-sm">

                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>

                <button type="submit" class="h-10 w-32 bg-blue-600 text-white rounded-lg">
                    Search
                </button>

                <button type="button"
                    class="reset-division h-10 w-32 bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg">
                    Reset
                </button>
            </form>
        </div>

        <!-- Toolbar: Export + Per Page + View Toggle for Division -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-4">
            
            <!-- Export Button -->
            <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'division'])) }}"
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
                    <label for="division-per-page" class="whitespace-nowrap font-medium hidden sm:inline">Show entries:</label>
                    <label for="division-per-page" class="whitespace-nowrap font-medium sm:hidden">Show:</label>
                    <select id="division-per-page"
                        class="per-page-select border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- View Toggle Buttons -->
                <div class="flex items-center bg-gray-100 p-1 rounded-xl">

                    <button type="button"
                        class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 text-gray-500 hover:text-gray-700"
                        data-target="division" data-view="card">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span class="hidden md:inline">Cards</span>
                    </button>

                    <button type="button"
                        class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 bg-white shadow text-blue-600"
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

        <div id="division-results-container">

            <!-- ── DIVISION TABLE VIEW ── -->
            <div id="division-table-view" class="bg-white rounded-xl shadow overflow-hidden my-4">
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
                                <tr class="hover:bg-gray-50">
                                    <!-- Clickable Cover -->
                                    <td class="px-2 py-3">
                                        <img 
                                            src="{{ $item->thumb_url }}" 
                                            alt="{{ $item->printTitle->title }}"
                                            class="w-12 h-16 object-cover rounded shadow cursor-pointer hover:scale-105 transition-transform duration-200"
                                            loading="lazy"
                                            onclick='openPrintModal(@json($item->showDetails($mainLibraryIds)))'
                                            title="Click to view details">
                                    </td>

                                    <td class="px-2 py-3 text-center font-medium text-gray-800 max-w-xs">
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
                                                @mouseenter="..."
                                                @mouseleave="..." @endif>
                                                <!-- Subject content remains unchanged -->
                                                <span class="inline-block px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700 cursor-default">
                                                    {{ $first->subject->abbrv }} - {{ $first->gradeLevel->grade }}
                                                    @if ($count > 1)
                                                        <span class="ml-1 text-green-600">+{{ $count - 1 }}</span>
                                                    @endif
                                                </span>
                                                <!-- Tooltip remains unchanged -->
                                            </div>
                                        @else
                                            <span class="text-gray-500 text-xs">No assignment</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-gray-600 font-mono text-xs">{{ $item->isbn }}</td>
                                    <td class="px-2 py-3 text-center">{{ $item->copyright }}</td>
                                    <td class="px-2 py-3 text-center text-xs">
                                        <!-- Quantity Breakdown unchanged -->
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
                                    <td class="px-2 py-3">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='openPrintModal(@json($item->showDetails($mainLibraryIds)))'
                                                class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                                View
                                            </button>
                                            <a href="{{ route('edit-resource', $item->id) }}"
                                                class="px-3 py-1 text-xs rounded-lg bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-8 text-gray-500">No division resources found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($resources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="p-4">
                        {{ $resources->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

            <!-- ── DIVISION CARD VIEW ── -->
            <div id="division-card-view" class="hidden my-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @forelse ($resources as $item)
                        @php
                            $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                            $qty = $item->scopedQuantities($mainLibraryIds);
                            $total = array_sum($qty);
                        @endphp
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group"
                             onclick='openPrintModal(@json($item->showDetails($mainLibraryIds)))'>

                            <!-- Cover image -->
                            <div class="relative w-full" style="padding-bottom: 140%;">
                                <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    loading="lazy">

                                <!-- Usable copies dot badge -->
                                <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2 py-0.5 rounded-full shadow
                                    {{ $qty['usable'] > 0 ? 'text-green-700' : 'text-red-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block {{ $qty['usable'] > 0 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                    {{ $total }}
                                </span>

                                <!-- Edit shortcut (top-left) -->
                                <a href="{{ route('edit-resource', $item->id) }}"
                                   onclick="event.stopPropagation()"
                                   class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 backdrop-blur-sm rounded-full p-1 shadow hover:bg-yellow-50"
                                   title="Edit">
                                    <svg class="w-3.5 h-3.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </a>
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
                            No division resources found.
                        </div>
                    @endforelse
                </div>

                @if ($resources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="bg-white rounded-xl shadow p-4 mt-4">
                        {{ $resources->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SCHOOL LIBRARY TAB                                           -->
    <!-- ============================================================ -->
    <div id="school-tab" class="tab-content {{ $activeTab === 'school' ? '' : 'hidden' }}">
        <form method="GET" data-ajax class="bg-white p-4 rounded-xl shadow space-y-4">
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

            <div class="flex gap-3">
                <div class="relative w-full">
                    <input type="text" name="school_search"
                        placeholder="Search School Library... Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                        value="{{ request('school_search') }}"
                        class="w-full h-10 pl-10 pr-3 border rounded-lg text-sm">

                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>

                <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2">Search</button>
            </div>

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
            @if (request()->has('district') || request()->has('school'))

                <!-- Toolbar: Export + Per Page + View Toggle for School -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-4">
                    
                    <!-- Export Button -->
                    <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'school'])) }}"
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
                                class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 text-gray-500 hover:text-gray-700"
                                data-target="school" data-view="card">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span class="hidden md:inline">Cards</span>
                            </button>

                            <button type="button"
                                class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 bg-white shadow text-blue-600"
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
                <div id="school-table-view" class="bg-white rounded-xl shadow overflow-hidden mt-4">
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
                                                        {{ $first->subject->abbrv }} -
                                                        {{ $first->gradeLevel->grade }}
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
                                        <td class="px-2 py-3">
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
                                        <td colspan="11" class="text-center py-8 text-gray-500">No school resources found.</td>
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

                <!-- ── SCHOOL CARD VIEW ── -->
                <div id="school-card-view" class="hidden mt-4">
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
                                No school resources found.
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
                    Select district/school and click "Load Data" to view school library resources.
                </div>
            @endif
        </div>
    </div>
</div>