@extends('pages.layout.layout')

@section('title', 'Non-Print Resources')

@section('page-title', 'Non-Print Resources')

@section('content')
    <div class="p-6 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h1 class="text-xl font-semibold text-gray-800">Non-Print Resources</h1>

            <a href="{{ route('add-resources') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                + Add Resource
            </a>
        </div>

        <!-- ================= SEARCH ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" class="flex items-center gap-3">
                <div class="relative w-full">
                    <input type="text"
                           name="search"
                           placeholder="Search by title, brand, model, or code..."
                           value="{{ request('search') }}"
                           class="w-full pl-10 pr-3 py-2 border rounded-lg text-sm focus:ring focus:ring-blue-200">

                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                         xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>

                <button type="submit"
                        class="p-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- ================= FILTERS ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Type -->
                <div>
                    <label class="text-xs text-gray-500">Type</label>
                    <select name="type" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All Types</option>
                        <option {{ request('type') == 'Equipment' ? 'selected' : '' }}>Equipment</option>
                        <option {{ request('type') == 'Furniture' ? 'selected' : '' }}>Furniture</option>
                        <option {{ request('type') == 'ICT Device' ? 'selected' : '' }}>ICT Device</option>
                        <option {{ request('type') == 'Software' ? 'selected' : '' }}>Software</option>
                        <option {{ request('type') == 'Teaching Aid' ? 'selected' : '' }}>Teaching Aid</option>
                        <option {{ request('type') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <!-- Brand -->
                <div>
                    <label class="text-xs text-gray-500">Brand</label>
                    <input type="text" name="brand" placeholder="e.g., Epson, Dell" value="{{ request('brand') }}"
                           class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                </div>

                <!-- Year Acquired -->
                <div>
                    <label class="text-xs text-gray-500">Year Acquired</label>
                    <select name="year" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All Years</option>
                        @for($y = now()->year; $y >= 2000; $y--)
                            <option {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Apply Filters -->
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- ================= TABLE ================= -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 w-20">Image</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Brand</th>
                            <th class="px-4 py-3">Model</th>
                            <th class="px-4 py-3">Year Acquired</th>
                            <th class="px-4 py-3 text-center">Quantity Breakdown</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @php
                            $resources = collect([
                                ['id' => 1, 'image' => 'projector.jpg', 'title' => 'LCD Projector', 'type' => 'ICT Device', 'brand' => 'Epson', 'model' => 'EB-X51', 'code' => 'PROJ-2023-001', 'year_acquired' => 2023, 'edit_url' => '#edit-nonprint-1', 'subjects' => [['subject' => 'Science', 'grade' => '7'],['subject' => 'Science', 'grade' => '8'],['subject' => 'Science', 'grade' => '9'],['subject' => 'Science', 'grade' => '10'],['subject' => 'Mathematics', 'grade' => '10']], 'quantities' => ['usable' => 18, 'needs_repair' => 2, 'damaged' => 1, 'lost' => 0, 'condemnable' => 1]],
                                ['id' => 2, 'image' => 'laptop.jpg', 'title' => 'Student Laptop Set', 'type' => 'ICT Device', 'brand' => 'Lenovo', 'model' => 'IdeaPad 3', 'code' => 'LAP-2024-001', 'year_acquired' => 2024, 'edit_url' => '#edit-nonprint-2', 'subjects' => [['subject' => 'Computer Studies', 'grade' => '11'],['subject' => 'Computer Studies', 'grade' => '12'],['subject' => 'TLE', 'grade' => '9'],['subject' => 'TLE', 'grade' => '10']], 'quantities' => ['usable' => 45, 'needs_repair' => 3, 'damaged' => 1, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 3, 'image' => 'whiteboard.jpg', 'title' => 'Interactive Whiteboard', 'type' => 'Teaching Aid', 'brand' => 'SMART Board', 'model' => 'SBX885', 'code' => 'IWB-2022-001', 'year_acquired' => 2022, 'edit_url' => '#edit-nonprint-3', 'subjects' => [['subject' => 'All Subjects', 'grade' => 'K-12']], 'quantities' => ['usable' => 8, 'needs_repair' => 1, 'damaged' => 1, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 4, 'image' => 'desk.jpg', 'title' => 'Classroom Desk and Chair Set', 'type' => 'Furniture', 'brand' => 'Local Manufacturer', 'model' => 'Standard 40x60', 'code' => 'FURN-2021-001', 'year_acquired' => 2021, 'edit_url' => '#edit-nonprint-4', 'subjects' => [['subject' => 'All Subjects', 'grade' => 'K-12']], 'quantities' => ['usable' => 120, 'needs_repair' => 15, 'damaged' => 8, 'lost' => 2, 'condemnable' => 5]],
                                // Add more rows (5–20)
                                ['id' => 5, 'image' => 'printer.jpg', 'title' => 'Laser Printer', 'type' => 'ICT Device', 'brand' => 'HP', 'model' => 'LaserJet Pro M404n', 'code' => 'PRT-2023-002', 'year_acquired' => 2023, 'edit_url' => '#edit-nonprint-5', 'subjects' => [['subject' => 'All Subjects', 'grade' => 'K-12']], 'quantities' => ['usable' => 10, 'needs_repair' => 1, 'damaged' => 0, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 6, 'image' => 'tablet.jpg', 'title' => 'Student Tablets', 'type' => 'ICT Device', 'brand' => 'Samsung', 'model' => 'Galaxy Tab A8', 'code' => 'TAB-2024-001', 'year_acquired' => 2024, 'edit_url' => '#edit-nonprint-6', 'subjects' => [['subject' => 'All Subjects', 'grade' => 'K-12']], 'quantities' => ['usable' => 30, 'needs_repair' => 2, 'damaged' => 1, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 7, 'image' => 'cabinet.jpg', 'title' => 'Steel Filing Cabinet', 'type' => 'Furniture', 'brand' => 'Local', 'model' => '4-Drawer', 'code' => 'CAB-2022-001', 'year_acquired' => 2022, 'edit_url' => '#edit-nonprint-7', 'subjects' => [['subject' => 'Admin', 'grade' => 'N/A']], 'quantities' => ['usable' => 15, 'needs_repair' => 3, 'damaged' => 2, 'lost' => 0, 'condemnable' => 1]],
                                ['id' => 8, 'image' => 'projector2.jpg', 'title' => 'Portable Projector', 'type' => 'ICT Device', 'brand' => 'BenQ', 'model' => 'MS550', 'code' => 'PROJ-2023-003', 'year_acquired' => 2023, 'edit_url' => '#edit-nonprint-8', 'subjects' => [['subject' => 'All Subjects', 'grade' => 'K-12']], 'quantities' => ['usable' => 6, 'needs_repair' => 1, 'damaged' => 0, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 9, 'image' => 'monitor.jpg', 'title' => 'Computer Monitors', 'type' => 'ICT Device', 'brand' => 'Dell', 'model' => 'P2419H', 'code' => 'MON-2024-001', 'year_acquired' => 2024, 'edit_url' => '#edit-nonprint-9', 'subjects' => [['subject' => 'Computer Studies', 'grade' => '11']], 'quantities' => ['usable' => 20, 'needs_repair' => 2, 'damaged' => 1, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 10, 'image' => 'chair.jpg', 'title' => 'Student Chairs', 'type' => 'Furniture', 'brand' => 'Local', 'model' => 'Plastic', 'code' => 'CHR-2021-001', 'year_acquired' => 2021, 'edit_url' => '#edit-nonprint-10', 'subjects' => [['subject' => 'All Subjects', 'grade' => 'K-12']], 'quantities' => ['usable' => 200, 'needs_repair' => 20, 'damaged' => 10, 'lost' => 5, 'condemnable' => 3]],
                                // ... continue adding 10 more if needed
                            ]);
                        @endphp
                        @foreach ($resources as $item)
                            @php
                                $qty = $item['quantities'];
                                $total = array_sum($qty);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <img src="{{ asset('assets/images/' . ($item['image'] ?? 'default.jpg')) }}"
                                        alt="{{ $item['title'] }}"
                                        class="w-12 h-12 object-cover rounded shadow-sm">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 max-w-xs">{{ $item['title'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">{{ $item['type'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if(isset($item['subjects']) && count($item['subjects']) > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($item['subjects'] as $sub)
                                                <span class="inline-block bg-indigo-100 text-indigo-800 font-medium px-2 py-1 rounded-full">
                                                    {{ $sub['subject'] }} - Grade {{ $sub['grade'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-xs">No assignment</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $item['brand'] }}</td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $item['model'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $item['year_acquired'] }}</td>
                                <td class="px-4 py-3 text-center text-xs">
                                    <div class="space-y-1">
                                        <div class="flex justify-center gap-3 text-gray-700">
                                            <span title="Working/Usable"><strong class="text-green-600">{{ $qty['usable'] }}</strong> Usable</span>
                                            <span title="Needs Repair"><strong class="text-orange-600">{{ $qty['needs_repair'] }}</strong> Repair</span>
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
                                        <button onclick='openNonPrintModal(@json($item))'
                                                class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                            View
                                        </button>
                                        <a href="{{ $item['edit_url'] }}"
                                        class="px-3 py-1 text-xs rounded-lg bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= PAGINATION ================= -->
        <div class="flex justify-end">
            <nav class="inline-flex items-center gap-1 text-sm">
                <a href="#" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</a>
                <span class="px-3 py-1 bg-blue-600 text-white rounded">1</span>
                <a href="#" class="px-3 py-1 border rounded hover:bg-gray-100">2</a>
                <a href="#" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a>
            </nav>
        </div>
    </div>

    @include('pages.components.view-nonprint-modal')
@endsection
