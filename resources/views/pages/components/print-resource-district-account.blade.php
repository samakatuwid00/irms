<form method="GET" data-ajax class="space-y-4">

    @if(request()->has('school') && request('school') !== 'all')
        <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Showing resources for the school selected from the <strong>BOSY Status</strong> chart.</span>
            <a href="{{ route('print-resources') }}?tab=school" class="ml-auto font-medium underline hover:text-blue-900 whitespace-nowrap">Clear filter</a>
        </div>
    @endif

    <!-- Header + Search -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-700">District Library Resources</h3>
            <p class="text-xs text-gray-400 mt-0.5">Resources from all schools in your district.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative">
                @include('pages.partials.search-input', [
                    'name' => 'search',
                    'placeholder' => 'Search title, author, ISBN...',
                    'value' => request('search'),
                ])
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Search
            </button>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="w-full sm:w-48">
                <x-filter-select
                    id="school"
                    label="School"
                    name="school"
                    maxWidth="none"
                    class="mb-0"
                >
                    <option value="all" {{ request('school') === 'all' ? 'selected' : '' }}>
                        All Schools (this District)
                    </option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" {{ request('school') == $school->id ? 'selected' : '' }}>
                            {{ $school->school_name }}
                        </option>
                    @endforeach
                </x-filter-select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Load Data
                </button>
                <button type="button" id="resetFilters"
                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-sm font-medium text-gray-800 rounded-lg transition-colors">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden view input – carries current view through search/pagination -->
    <input type="hidden" name="view" id="view-input" value="{{ request('view', 'card') }}">
    <!-- Hidden per-page input – kept in sync by the entries selector -->
    <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">
</form>

<div id="table-results-container">
    @if (request()->has('school'))

        @include('pages.partials.resource-toolbar', [
            'exportHref' => route('print-resources.export', request()->query()),
            'perPageId' => 'district-per-page',
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'context' => 'default',
            'target' => null,
            'activeView' => request('view', 'card'),
        ])

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
                            <th class="px-2 py-3 text-center">School</th>
                            <th class="px-6 py-3 text-center">Actions</th>
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
                                        class="cover-img w-12 h-16 object-cover rounded shadow cursor-pointer hover:scale-105 transition-transform duration-200"
                                        loading="lazy"
                                        onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)), "district")'
                                        title="Click to view details">
                                </td>

                                <td class="px-2 py-3 font-medium text-gray-800 max-w-xs">
                                    @include('pages.components.verified-title', ['title' => $item->printTitle->title, 'verified' => $item->verified])
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
                                <td class="px-2 py-3 text-center text-xs">School Library</td>
                                <td class="px-2 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)), "district")'
                                            class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                            View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-8 text-gray-500">No resources found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($filteredResources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="p-4">
                    {{ $filteredResources->appends(request()->query())->links('pagination::print-resource') }}
                </div>
            @endif
        </div>

        <!-- ── CARD VIEW ── -->
        <div id="card-view" class="hidden mt-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @if ($filteredResources->count() > 0)
                    @foreach ($filteredResources as $item)
                        @php
                            $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                            $qty = $item->scopedQuantities($filteredLibraryIds);
                            $total = array_sum($qty);
                        @endphp
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group transition-all duration-200 ease-out hover:-translate-y-1 hover:shadow-lg"
                             onclick='openPrintModal(@json($item->showDetails($filteredLibraryIds)), "district")'>

                            <!-- Cover image -->
                            <div class="relative w-full" style="padding-bottom: 140%;">
                                <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                    class="cover-img absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
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
                    @endforeach
                    @if ($filteredResources instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="col-span-full p-4">
                            {{ $filteredResources->appends(request()->query())->links('pagination::print-resource') }}
                        </div>
                    @endif
                @else
                    <div class="col-span-full text-center py-16 text-gray-400">
                        <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="font-medium">No resources found.</p>
                    </div>
                @endif
            </div>
        </div>

    @else
        <div class="text-center py-16 text-gray-400">
            <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="font-medium">Start by loading data to view records</p>
            <p class="text-sm mt-1">Select a school (or All Schools) and click "Load Data" to view school library resources.</p>
        </div>
    @endif
</div>