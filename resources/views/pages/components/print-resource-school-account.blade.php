<h2 class="text-lg font-semibold">Your School Library Resources</h2>

<div class="bg-white rounded-xl shadow p-4">
    <form method="GET" class="flex items-center gap-3">
        <div class="relative w-full">
            <input
                type="text"
                name="search"
                placeholder="Search by Title, Author, ISBN, Publisher, Grade, Subject..."
                value="{{ request('search') }}"
                class="w-full h-10 pl-10 pr-3 border rounded-lg text-sm"
            >

            <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.3-4.3"/>
            </svg>
        </div>

        <button
            class="h-10 px-4 bg-blue-600 text-white text-sm rounded-lg"
        >
            Search
        </button>

        <button
            type="button"
            id="resetFilters"
            class="h-10 px-4 bg-gray-200 hover:bg-gray-300 text-sm text-gray-800 rounded-lg"
        >
            Reset
        </button>
    </form>

</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs sticky top-0 z-10">
                <tr>
                    <th class="px-4 py-3 w-20">Image</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Author</th>
                    <th class="px-4 py-3">Publisher</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">ISBN</th>
                    <th class="px-4 py-3">Copyright</th>
                    <th class="px-4 py-3 text-center">Quantity Breakdown</th>
                    <th class="px-4 py-3 text-center">Actions</th>
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
                        <td class="px-4 py-3">
                            <img src="{{ asset('assets/images/' . ($item->image ?? 'default.jpg')) }}"
                                    alt="{{ $item->printTitle->title }}"
                                    class="w-12 h-16 object-cover rounded shadow-sm">
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800 max-w-xs">{{ $item->printTitle->title }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $authors }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->publisher }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item->type->type_name }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($item->subjects()->count())
                                <div class="flex flex-wrap gap-1">
                                    @foreach($item->subjects() as $sub)
                                        <span class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full">
                                            {{ $sub->subject->subject_name }} - {{ $sub->gradeLevel->grade }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-500 text-xs">No assignment</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $item->isbn }}</td>
                        <td class="px-4 py-3 text-center">{{ $item->copyright }}</td>
                        <td class="px-4 py-3 text-center text-xs">
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
                                    <button onclick='openPrintModal(@json($item->showDetails()))'
                                            class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                        View
                                    </button>
                                <a href=""
                                    class="px-3 py-1 text-xs rounded-lg bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-500">
                            No resources found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $resources->appends(request()->query())->links() }}
    </div>
</div>
