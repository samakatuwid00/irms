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
                        placeholder="Search Division Library... Search by Title, Brand, Code, Version, Model, Subject..."
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
            <a href="{{ route('nonprint-resources.export', array_merge(request()->query(), ['tab' => 'division'])) }}"
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
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-600 uppercase text-xs sticky top-0 z-10">
                            <tr>
                                <th class="px-2 py-3 text-left w-18">Cover</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Brand</th>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Version</th>
                                <th class="px-4 py-3">URL</th>
                                <th class="px-4 py-3">Size</th>
                                <th class="px-4 py-3">Model</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3 text-center">Quantity Breakdown</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($resources as $item)
                                @php
                                    $qty = $item->quantities;
                                    $total = array_sum($qty);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-3">
                                        <img src="{{ $item->thumb_url }}" alt="{{ $item->nonprintTitle->title }}"
                                            class="w-12 h-16 object-cover rounded shadow" loading="lazy">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs">
                                        {{ $item->nonprintTitle->title }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item->type->type_name }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->brand }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->code }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->version }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->url }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->size }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->model }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        @if ($item->subjects()->count())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($item->subjects() as $sub)
                                                    <span
                                                        class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full">
                                                        {{ $sub->subject->subject_name }} -
                                                        {{ $sub->gradeLevel->grade }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-500 text-xs">No assignment</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
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
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='openNonPrintModal(@json($item->showDetails($mainLibraryIds)))'
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
                                    <td colspan="12" class="text-center py-8 text-gray-500">No division resources
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
                        placeholder="Search School Library... Search by Title, Brand, Code, Version, Model, Subject..."
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
                    <a href="{{ route('nonprint-resources.export', array_merge(request()->query(), ['tab' => 'school'])) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export to Excel
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden mt-4">
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-600 uppercase text-xs sticky top-0 z-10">
                                <tr>
                                    <th class="px-2 py-3 text-left w-18">Cover</th>
                                    <th class="px-4 py-3">Title</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Brand</th>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Version</th>
                                    <th class="px-4 py-3">URL</th>
                                    <th class="px-4 py-3">Size</th>
                                    <th class="px-4 py-3">Model</th>
                                    <th class="px-4 py-3">Subject</th>
                                    <th class="px-4 py-3 text-center">Quantity Breakdown</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($filteredResources as $item)
                                    @php
                                        $qty = $item->quantities;
                                        $total = array_sum($qty);
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-2 py-3">
                                            <img src="{{ $item->thumb_url }}"
                                                alt="{{ $item->nonprintTitle->title }}"
                                                class="w-12 h-16 object-cover rounded shadow" loading="lazy">
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-800 max-w-xs">
                                            {{ $item->nonprintTitle->title }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item->type->type_name }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->brand }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->code }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->version }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->url }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->size }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->model }}</td>
                                        <td class="px-4 py-3 text-xs">
                                            @if ($item->subjects()->count())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach ($item->subjects() as $sub)
                                                        <span
                                                            class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full">
                                                            {{ $sub->subject->subject_name }} -
                                                            {{ $sub->gradeLevel->grade }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-500 text-xs">No assignment</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-xs">
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
                                        <td class="px-4 py-3">
                                            <div class="flex justify-center gap-2">
                                                <button onclick='openNonPrintModal(@json($item->showDetails($filteredLibraryIds)))'
                                                    class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                                    View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-8 text-gray-500">No resources found.
                                        </td>
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