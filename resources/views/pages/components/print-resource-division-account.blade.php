<h2 class="text-lg font-semibold">Library Resources</h2>

<!-- Tab Navigation -->
<div class="bg-white rounded-t-xl shadow">
    <div class="flex border-b">
        <button type="button"
            class="tab-btn px-6 py-3 font-medium text-sm transition-colors border-b-2 border-blue-600 text-blue-600"
            data-tab="division">
            Division
        </button>
        <button type="button"
            class="tab-btn px-6 py-3 font-medium text-sm transition-colors border-b-2 border-transparent text-gray-600 hover:text-gray-800"
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
    <div id="division-tab" class="tab-content">
        <div class="bg-white rounded-b-xl shadow p-4">
            <form method="GET" data-ajax class="flex gap-3 mb-4">
                <input type="hidden" name="tab" value="division">

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

        <!-- Export Button for Division Library -->
        <div class="export-btn-wrapper flex justify-end mt-4">
            <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'division'])) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export to Excel
            </a>
        </div>

        <div id="division-results-container">
            <div class="bg-white rounded-xl shadow overflow-hidden my-4">
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
                                <th class="px-2 py-3">Library</th>
                                <th class="px-2 py-3 text-center">Quantity Breakdown</th>
                                <th class="px-2 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($resources as $item)
                                @php
                                    $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                    $qty = $item->quantities;
                                    $total = array_sum($qty);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-3">
                                        <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                            class="w-12 h-16 object-cover rounded shadow" loading="lazy">
                                    </td>
                                    <td class="px-2 py-3 text-center font-medium text-gray-800 max-w-xs">
                                        {{ $item->printTitle->title }}</td>
                                    <td class="px-2 py-3 text-gray-600">{{ $authors }}</td>
                                    <td class="px-2 py-3 text-gray-600">{{ $item->publisher }}</td>
                                    <td class="px-2 py-3">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item->type->shortname }}</span>
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
                                                <span
                                                    class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full cursor-default">
                                                    {{ $first->subject->subject_name }} -
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
                                    <td class="px-2 py-3 text-gray-700">
                                        <span
                                            class="text-xs font-medium text-blue-600">{{ $item->library_name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-2 py-3 text-center text-xs">
                                        <div class="space-y-1">
                                            <div class="flex justify-center gap-3 text-gray-700">
                                                <span title="Usable"><strong
                                                        class="text-green-600">{{ $qty['usable'] }}</strong>
                                                    Usable</span>
                                                <span title="Partially Damaged"><strong
                                                        class="text-yellow-600">{{ $qty['partially_damaged'] }}</strong>
                                                    PD</span>
                                            </div>
                                            <div class="flex justify-center gap-3 text-gray-600">
                                                <span title="Damaged"><strong
                                                        class="text-red-600">{{ $qty['damaged'] }}</strong>
                                                    Damaged</span>
                                                <span title="Lost"><strong
                                                        class="text-purple-600">{{ $qty['lost'] }}</strong>
                                                    Lost</span>
                                                <span title="Condemnable"><strong
                                                        class="text-gray-800">{{ $qty['condemnable'] }}</strong>
                                                    Cond.</span>
                                            </div>
                                            <div class="font-semibold text-gray-800 border-t pt-1">Total:
                                                {{ $total }}</div>
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
                                    <td colspan="11" class="text-center py-8 text-gray-500">No division resources
                                        found.</td>
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
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SCHOOL LIBRARY TAB                                           -->
    <!-- ============================================================ -->
    <div id="school-tab" class="tab-content hidden">
        <form method="GET" data-ajax class="bg-white p-4 rounded-xl shadow space-y-4">
            <input type="hidden" name="tab" value="school">

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
                <!-- Export Button for School Library -->
                <div class="export-btn-wrapper flex justify-end mt-4">
                    <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'school'])) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export to Excel
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden mt-4">
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
                                        $qty = $item->quantities;
                                        $total = array_sum($qty);
                                    @endphp
                                    <tr class="hover:bg-gray-50 border border-gray-300">
                                        <td class="px-2 py-3">
                                            <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                                class="w-12 h-16 object-cover rounded shadow" loading="lazy">
                                        </td>
                                        <td class="px-2 py-3 font-medium text-gray-800 max-w-xs">
                                            {{ $item->printTitle->title }}</td>
                                        <td class="px-2 py-3 text-gray-600">{{ $authors }}</td>
                                        <td class="px-2 py-3 text-gray-600">{{ $item->publisher }}</td>
                                        <td class="px-2 py-3">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item->type->shortname }}</span>
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
                                                    <span
                                                        class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full cursor-default">
                                                        {{ $first->subject->subject_name }} -
                                                        {{ $first->gradeLevel->grade }}
                                                        @if ($count > 1)
                                                            <span
                                                                class="ml-1 text-green-600">+{{ $count - 1 }}</span>
                                                        @endif
                                                    </span>

                                                    @if ($count > 1)
                                                        <div data-tooltip
                                                            class="pointer-events-none fixed z-[100] invisible opacity-0
                                                                    bg-gray-800 text-white text-xs rounded-md py-2 px-3 shadow-xl
                                                                    min-w-[220px] max-w-sm whitespace-normal break-words
                                                                    transition-opacity duration-150 border border-gray-700">
                                                            @foreach ($item->subjects() as $sub)
                                                                <div
                                                                    class="py-1 border-b border-gray-700 last:border-0">
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
                                                    <span title="Usable"><strong
                                                            class="text-green-600">{{ $qty['usable'] }}</strong>
                                                        Usable</span>
                                                    <span title="Partially Damaged"><strong
                                                            class="text-yellow-600">{{ $qty['partially_damaged'] }}</strong>
                                                        PD</span>
                                                </div>
                                                <div class="flex justify-center gap-3 text-gray-600">
                                                    <span title="Damaged"><strong
                                                            class="text-red-600">{{ $qty['damaged'] }}</strong>
                                                        Damaged</span>
                                                    <span title="Lost"><strong
                                                            class="text-purple-600">{{ $qty['lost'] }}</strong>
                                                        Lost</span>
                                                    <span title="Condemnable"><strong
                                                            class="text-gray-800">{{ $qty['condemnable'] }}</strong>
                                                        Cond.</span>
                                                </div>
                                                <div class="font-semibold text-gray-800 border-t pt-1">Total:
                                                    {{ $total }}</div>
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
                                        <td colspan="10" class="text-center py-8 text-gray-500">No school resources
                                            found.</td>
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
            @else
                <div class="bg-white p-6 rounded-xl shadow text-center text-gray-600 mt-4">
                    Select district/school and click "Load Data" to view school library resources.
                </div>
            @endif
        </div>
    </div>

</div>