<div x-data="{ activeTab: new URLSearchParams(window.location.search).get('tab') || '{{ request('tab', 'school') }}' }" class="space-y-6">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button
                @click="activeTab = 'school'; $nextTick(() => { const url = new URL(window.location); url.searchParams.set('tab', 'school'); window.history.replaceState({}, '', url); })"
                :class="activeTab === 'school' ? 'border-blue-600 text-blue-600' :
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                Your School Resources
            </button>
            <button
                @click="activeTab = 'division'; $nextTick(() => { const url = new URL(window.location); url.searchParams.set('tab', 'division'); window.history.replaceState({}, '', url); })"
                :class="activeTab === 'division' ? 'border-blue-600 text-blue-600' :
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                Division Resources
            </button>
        </nav>
    </div>

    <!-- ============================================================ -->
    <!-- SCHOOL RESOURCES TAB                                         -->
    <!-- ============================================================ -->
    <div x-show="activeTab === 'school'" x-cloak>
        <form method="GET" data-ajax class="space-y-4">
            <input type="hidden" name="tab" value="school">
            <input type="hidden" name="school_view" id="school-view-input" value="{{ request('school_view', 'card') }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">

            <!-- Header + Search -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-700">Your School Resources</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Resources available in your school library.</p>
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
                    <button type="button" onclick="window.location.href='{{ route('print-resources') }}'"
                        class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-sm font-medium text-gray-800 rounded-lg transition-colors">
                        Reset
                    </button>
                </div>
            </div>

        @include('pages.partials.resource-toolbar', [
            'exportHref' => route('print-resources.export', array_merge(request()->query(), ['tab' => 'school'])),
            'perPageId' => 'school-per-page',
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'context' => 'school',
            'target' => 'school',
            'activeView' => request('school_view', 'card'),
        ])
        </form>

        <div id="school-results-container">

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
                            @forelse ($resources as $item)
                                @php
                                    $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                    $qty = $item->scopedQuantities($mainLibraryIds);
                                    $total = array_sum($qty);
                                @endphp
                                <tr class="hover:bg-gray-50 border-b border-gray-300">
                                    <!-- Clickable Cover -->
                                    <td class="px-2 py-3">
                                        <img 
                                            src="{{ $item->thumb_url }}" 
                                            alt="{{ $item->printTitle->title }}"
                                            class="cover-img w-12 h-16 object-cover rounded shadow cursor-pointer hover:scale-105 transition-transform duration-200"
                                            loading="lazy"
                                            onclick='openPrintModal(@json($item->showDetails($schoolLibrary ? [$schoolLibrary->id] : [])), "school")'
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
                                                <span title="Usable"><strong class="text-green-600">{{ $qty['usable'] }}</strong> Usable</span>
                                                <span title="Partially Damaged"><strong class="text-yellow-600">{{ $qty['partially_damaged'] }}</strong> PD</span>
                                            </div>
                                            <div class="flex justify-center gap-3 text-gray-600">
                                                <span title="Damaged"><strong class="text-red-600">{{ $qty['damaged'] }}</strong> Damaged</span>
                                                <span title="Lost"><strong class="text-purple-600">{{ $qty['lost'] }}</strong> Lost</span>
                                                <span title="Condemnable"><strong class="text-gray-800">{{ $qty['condemnable'] }}</strong> Cond.</span>
                                            </div>
                                            <div class="font-semibold text-gray-800 border-t border-gray-300 pt-1">
                                                Total: {{ $total }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="flex justify-center gap-2">
                                            <button onclick='openPrintModal(@json($item->showDetails($schoolLibrary ? [$schoolLibrary->id] : [])), "school")'
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
                    {{ $resources->appends(request()->query())->links('pagination::print-resource') }}
                </div>
            </div>

            <!-- ── SCHOOL CARD VIEW ── -->
            <div id="school-card-view" class="hidden mt-4">
                @if ($resources->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach ($resources as $item)
                        @php
                            $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                            $qty = $item->scopedQuantities($mainLibraryIds);
                            $total = array_sum($qty);
                        @endphp
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group transition-all duration-200 ease-out hover:-translate-y-1 hover:shadow-lg"
                             onclick='openPrintModal(@json($item->showDetails($schoolLibrary ? [$schoolLibrary->id] : [])), "school")'>

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

                                <!-- Edit shortcut (top-left) -->
                                <a href="{{ route('edit-resource', $item->id) }}"
                                   onclick="event.stopPropagation()"
                                   title="Edit resource"
                                   aria-label="Edit resource"
                                   class="absolute top-2 left-2 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full border border-blue-200 bg-white/95 text-blue-600 shadow-sm backdrop-blur-sm transition-colors hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:h-8 sm:w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                    </svg>
                                </a>
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
                </div>
                    <div class="p-4 mt-4">
                        {{ $resources->appends(request()->query())->links('pagination::print-resource') }}
                    </div>
                @else
                    <div class="text-center py-16 text-gray-400">
                        <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="font-medium">No resources found.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DIVISION RESOURCES TAB                                       -->
    <!-- ============================================================ -->
    <div x-show="activeTab === 'division'" x-cloak>
        <form method="GET" data-ajax class="space-y-4">
            <input type="hidden" name="tab" value="division">
            <input type="hidden" name="division_view" id="division-view-input" value="{{ request('division_view', 'card') }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}" class="per-page-hidden-input">

            <!-- Header + Search -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-700">Division Resources</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Resources from the division library hub.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative">
                        @include('pages.partials.search-input', [
                            'name' => 'division_search',
                            'placeholder' => 'Search title, author, ISBN...',
                            'value' => request('division_search'),
                        ])
                    </div>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Search
                    </button>
                    <button type="button" onclick="window.location.href='{{ route('print-resources') }}?tab=division'"
                        class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-sm font-medium text-gray-800 rounded-lg transition-colors">
                        Reset
                    </button>
                </div>
            </div>

        @include('pages.partials.resource-toolbar', [
            'exportHref' => route('print-resources.export', array_merge(request()->query(), ['tab' => 'division'])),
            'perPageId' => 'division-per-page',
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'context' => 'division',
            'target' => 'division',
            'activeView' => request('division_view', 'card'),
        ])
        </form>

        <div id="division-results-container">

            <!-- ── DIVISION TABLE VIEW ── -->
            <div id="division-table-view" class="bg-white rounded-xl shadow overflow-hidden mt-4">
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
                            @forelse ($divisionResources as $item)
                                @php
                                    $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                                    $qty = $item->scopedQuantities($divisionLibraryIds);
                                    $total = array_sum($qty);
                                @endphp
                                <tr class="hover:bg-gray-50 border-b border-gray-300">
                                    <td class="px-2 py-3">
                                        <img src="{{ $item->thumb_url }}" alt="{{ $item->printTitle->title }}"
                                             class="cover-img w-12 h-16 object-cover rounded shadow" loading="lazy">
                                    </td>
                                    <td class="px-2 py-3 font-medium text-gray-800 max-w-xs">
                                        @include('pages.components.verified-title', ['title' => $item->printTitle->title, 'verified' => $item->verified])
                                    </td>
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
                                            <button onclick='openPrintModal(@json($item->showDetails($divisionLibraryIds)), "division")'
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
                    {{ $divisionResources->appends(array_merge(request()->query(), ['tab' => 'division']))->links('pagination::print-resource') }}
                </div>
            </div>

            <!-- ── DIVISION CARD VIEW ── -->
            <div id="division-card-view" class="hidden mt-4">
                @if ($divisionResources->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    @foreach ($divisionResources as $item)
                        @php
                            $authors = $item->printTitle->authors->pluck('author_name')->join(', ');
                            $qty = $item->scopedQuantities($divisionLibraryIds);
                            $total = array_sum($qty);
                        @endphp
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col cursor-pointer group transition-all duration-200 ease-out hover:-translate-y-1 hover:shadow-lg"
                             onclick='openPrintModal(@json($item->showDetails($divisionLibraryIds)), "division")'>

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
                </div>
                    <div class="p-4 mt-4">
                        {{ $divisionResources->appends(array_merge(request()->query(), ['tab' => 'division']))->links('pagination::print-resource') }}
                    </div>
                @else
                    <div class="text-center py-16 text-gray-400">
                        <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="font-medium">No division resources found.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
