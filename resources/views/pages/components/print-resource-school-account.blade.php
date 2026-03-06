<div x-data="{ activeTab: new URLSearchParams(window.location.search).get('tab') || '{{ request('tab', 'school') }}' }" class="space-y-4">
    <h2 class="text-lg font-semibold">Library Resources</h2>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-xl shadow">
        <div class="flex border-b border-gray-200">
            <button
                @click="activeTab = 'school'; $nextTick(() => { const url = new URL(window.location); url.searchParams.set('tab', 'school'); window.history.replaceState({}, '', url); })"
                :class="activeTab === 'school' ? 'border-blue-600 text-blue-600' :
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="px-6 py-3 border-b-2 font-medium text-sm transition-colors">
                Your School Resources
            </button>
            <button
                @click="activeTab = 'division'; $nextTick(() => { const url = new URL(window.location); url.searchParams.set('tab', 'division'); window.history.replaceState({}, '', url); })"
                :class="activeTab === 'division' ? 'border-blue-600 text-blue-600' :
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="px-6 py-3 border-b-2 font-medium text-sm transition-colors">
                Division Resources
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SCHOOL RESOURCES TAB                                         -->
    <!-- ============================================================ -->
    <div x-show="activeTab === 'school'" x-cloak>
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" data-ajax class="flex items-center gap-3">
                <input type="hidden" name="tab" value="school">
                <div class="relative w-full">
                    <input type="text" name="search"
                        placeholder="Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                        value="{{ request('search') }}"
                        class="w-full h-10 pl-10 pr-3 border border-gray-300 rounded-lg text-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>

                <button class="h-10 px-4 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    Search
                </button>

                <button type="button" onclick="window.location.href='{{ route('print-resources') }}'"
                    class="h-10 px-4 bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg">
                    Reset
                </button>
            </form>
        </div>

        <!-- Button row: estimated resource + export -->
        <div class="flex justify-between items-center mt-4">
            <form action="{{ route('school-library.update-estimated-resource') }}" method="POST"
                class="flex items-center gap-3">
                @csrf
                @method('PATCH')

                <label for="estimated_resource" class="text-sm font-medium text-gray-700">
                    Estimated Resources:
                </label>

                <input type="number" name="estimated_resource" id="estimated_resource" min="0"
                    value="{{ $schoolLibrary->estimated_resource ?? 0 }}"
                    class="w-32 h-10 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                    Save
                </button>

                <span class="text-gray-600 text-sm">
                    (The total number of inputted resources is {{ $countPercent->pct_of_estimated ?? 0 }}% of the
                    estimated resources)
                </span>

                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                        class="flex items-center justify-between gap-2 px-3 py-2 text-sm text-green-800 bg-green-100 rounded-lg">
                        <span>{{ session('success') }}</span>
                        <button type="button" @click="show = false"
                            class="ml-3 font-bold text-green-700 hover:text-green-900">✕</button>
                    </div>
                @endif

                @error('estimated_resource')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </form>

            <a href="{{ route('print-resources.export', array_merge(request()->query(), ['tab' => 'school'])) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export to Excel
            </a>
        </div>

        <div id="table-results-container">
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
                            @forelse ($resources as $item)
                                @php
                                    $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                    $qty = $item->quantities;
                                    $total = array_sum($qty);
                                @endphp
                                <tr class="hover:bg-gray-50 border-b border-gray-300">
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
                                            <div class="font-semibold text-gray-800 border-t border-gray-300 pt-1">
                                                Total: {{ $total }}</div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='openPrintModal(@json($item->showDetails()))'
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
                                    <td colspan="10" class="text-center py-8 text-gray-500">No resources found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $resources->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DIVISION RESOURCES TAB                                       -->
    <!-- ============================================================ -->
    <div x-show="activeTab === 'division'" x-cloak>
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" data-ajax class="flex items-center gap-3">
                <input type="hidden" name="tab" value="division">
                <div class="relative w-full">
                    <input type="text" name="division_search"
                        placeholder="Search Division Library... Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                        value="{{ request('division_search') }}"
                        class="w-full h-10 pl-10 pr-3 border border-gray-300 rounded-lg text-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>

                <button class="h-10 px-4 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    Search
                </button>

                <button type="button" onclick="window.location.href='{{ route('print-resources') }}?tab=division'"
                    class="h-10 px-4 bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg">
                    Reset
                </button>
            </form>
        </div>

        <!-- Export Button for Division Resources -->
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
                                <th class="px-2 py-3">Library</th>
                                <th class="px-2 py-3 text-center">Quantity Breakdown</th>
                                <th class="px-2 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($divisionResources as $item)
                                @php
                                    $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                    $qty = $item->quantities;
                                    $total = array_sum($qty);
                                @endphp
                                <tr class="hover:bg-gray-50 border-b border-gray-300">
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
                                    <td class="px-2 py-3 text-gray-600 text-xs">
                                        <span
                                            class="inline-block bg-purple-100 text-purple-700 px-2 py-1 rounded-full">
                                            {{ $item->library_name ?? 'N/A' }}
                                        </span>
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
                                            <div class="font-semibold text-gray-800 border-t border-gray-300 pt-1">
                                                Total: {{ $total }}</div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='openPrintModal(@json($item->showDetails()))'
                                                class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                                View
                                            </button>
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

                <div class="p-4">
                    {{ $divisionResources->appends(array_merge(request()->query(), ['tab' => 'division']))->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
