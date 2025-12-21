@extends('pages.layout.layout')

@section('title', 'Print Resources')

@section('page-title', 'Print Resources')

@section('content')
    <div class="p-6 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h1 class="text-xl font-semibold text-gray-800">Print Resources</h1>

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
                           placeholder="Search by title, author, ISBN..."
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
                        <option {{ request('type') == 'Book' ? 'selected' : '' }}>Book</option>
                        <option {{ request('type') == 'Journal' ? 'selected' : '' }}>Journal</option>
                        <option {{ request('type') == 'Magazine' ? 'selected' : '' }}>Magazine</option>
                    </select>
                </div>

                <!-- Copyright Year -->
                <div>
                    <label class="text-xs text-gray-500">Copyright Year</label>
                    <select name="year" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All Years</option>
                        @for($y = now()->year; $y >= 1950; $y--)
                            <option {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Publisher -->
                <div>
                    <label class="text-xs text-gray-500">Publisher</label>
                    <input type="text" name="publisher" placeholder="e.g., DepEd" value="{{ request('publisher') }}"
                           class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
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
                        @php
                            $resources = collect([
                                ['id' => 1, 'image' => 'book1.jpg', 'title' => 'English Learner\'s Textbook Grade 5', 'author' => 'Maria Santos', 'publisher' => 'DepEd Philippines', 'type' => 'Book', 'isbn' => '978-971-655-123-4', 'copyright' => 2022, 'pages' => 256, 'edit_url' => '#edit-print-1', 'subjects' => [['subject' => 'English', 'grade' => '5']], 'quantities' => ['usable' => 142, 'partially_damaged' => 5, 'damaged' => 2, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 2, 'image' => 'book2.jpg', 'title' => 'Science and Health Journal Vol. 12', 'author' => 'Dr. Jose Reyes', 'publisher' => 'National Science Foundation', 'type' => 'Journal', 'isbn' => '0112-4567', 'copyright' => 2021, 'pages' => 120, 'edit_url' => '#edit-print-2', 'subjects' => [['subject' => 'Science', 'grade' => '7'],['subject' => 'Science', 'grade' => '8'],['subject' => 'Science', 'grade' => '9'],['subject' => 'Science', 'grade' => '10']], 'quantities' => ['usable' => 65, 'partially_damaged' => 8, 'damaged' => 4, 'lost' => 3, 'condemnable' => 0]],
                                ['id' => 3, 'image' => 'magazine1.jpg', 'title' => 'Filipino Culture Magazine', 'author' => 'Various', 'publisher' => 'Cultural Center PH', 'type' => 'Magazine', 'isbn' => 'N/A', 'copyright' => 2023, 'pages' => 64, 'edit_url' => '#edit-print-3', 'subjects' => [['subject' => 'Araling Panlipunan', 'grade' => '4'],['subject' => 'Araling Panlipunan', 'grade' => '5'],['subject' => 'Araling Panlipunan', 'grade' => '6'],['subject' => 'Filipino', 'grade' => '7']], 'quantities' => ['usable' => 185, 'partially_damaged' => 10, 'damaged' => 3, 'lost' => 2, 'condemnable' => 0]],
                                ['id' => 4, 'image' => 'book3.jpg', 'title' => 'Mathematics for Grade 10', 'author' => 'Ana Lopez & Team', 'publisher' => 'Rex Publishing', 'type' => 'Book', 'isbn' => '978-621-06-0456-2', 'copyright' => 2020, 'pages' => 412, 'edit_url' => '#edit-print-4', 'subjects' => [['subject' => 'Mathematics', 'grade' => '10']], 'quantities' => ['usable' => 105, 'partially_damaged' => 12, 'damaged' => 2, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 5, 'image' => 'book4.jpg', 'title' => 'Sibika at Kultura Grade 3', 'author' => 'Luzviminda Cruz', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-789-0', 'copyright' => 2021, 'pages' => 180, 'edit_url' => '#edit-print-5', 'subjects' => [['subject' => 'Araling Panlipunan', 'grade' => '3']], 'quantities' => ['usable' => 95, 'partially_damaged' => 4, 'damaged' => 1, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 6, 'image' => 'book5.jpg', 'title' => 'Pagbasa at Pagsulat Grade 2', 'author' => 'Rosario Garcia', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => 'N/A', 'copyright' => 2019, 'pages' => 210, 'edit_url' => '#edit-print-6', 'subjects' => [['subject' => 'Filipino', 'grade' => '2']], 'quantities' => ['usable' => 78, 'partially_damaged' => 6, 'damaged' => 3, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 7, 'image' => 'journal2.jpg', 'title' => 'Philippine Education Review 2023', 'author' => 'Various', 'publisher' => 'UP Press', 'type' => 'Journal', 'isbn' => '1234-5678', 'copyright' => 2023, 'pages' => 88, 'edit_url' => '#edit-print-7', 'subjects' => [['subject' => 'Professional Development', 'grade' => 'All']], 'quantities' => ['usable' => 45, 'partially_damaged' => 2, 'damaged' => 1, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 8, 'image' => 'book6.jpg', 'title' => 'Science G7 Learner\'s Material', 'author' => 'DepEd Team', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-456-7', 'copyright' => 2022, 'pages' => 320, 'edit_url' => '#edit-print-8', 'subjects' => [['subject' => 'Science', 'grade' => '7']], 'quantities' => ['usable' => 130, 'partially_damaged' => 7, 'damaged' => 3, 'lost' => 2, 'condemnable' => 0]],
                                ['id' => 9, 'image' => 'book7.jpg', 'title' => 'Technology and Livelihood Education Grade 8', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-621-06-1234-5', 'copyright' => 2021, 'pages' => 280, 'edit_url' => '#edit-print-9', 'subjects' => [['subject' => 'TLE', 'grade' => '8']], 'quantities' => ['usable' => 110, 'partially_damaged' => 9, 'damaged' => 4, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 10, 'image' => 'magazine2.jpg', 'title' => 'National Geographic Kids', 'author' => 'Various', 'publisher' => 'National Geographic', 'type' => 'Magazine', 'isbn' => 'N/A', 'copyright' => 2024, 'pages' => 48, 'edit_url' => '#edit-print-10', 'subjects' => [['subject' => 'Science', 'grade' => '4'],['subject' => 'Science', 'grade' => '5']], 'quantities' => ['usable' => 220, 'partially_damaged' => 15, 'damaged' => 5, 'lost' => 3, 'condemnable' => 0]],
                                ['id' => 11, 'image' => 'book8.jpg', 'title' => 'MAPEH Grade 6', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-890-1', 'copyright' => 2020, 'pages' => 250, 'edit_url' => '#edit-print-11', 'subjects' => [['subject' => 'MAPEH', 'grade' => '6']], 'quantities' => ['usable' => 88, 'partially_damaged' => 3, 'damaged' => 2, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 12, 'image' => 'book9.jpg', 'title' => 'EsP Grade 4', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-621-06-5678-9', 'copyright' => 2021, 'pages' => 190, 'edit_url' => '#edit-print-12', 'subjects' => [['subject' => 'Edukasyon sa Pagpapakatao', 'grade' => '4']], 'quantities' => ['usable' => 102, 'partially_damaged' => 5, 'damaged' => 1, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 13, 'image' => 'book10.jpg', 'title' => 'English Grade 9', 'author' => 'DepEd Team', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-234-5', 'copyright' => 2022, 'pages' => 300, 'edit_url' => '#edit-print-13', 'subjects' => [['subject' => 'English', 'grade' => '9']], 'quantities' => ['usable' => 115, 'partially_damaged' => 6, 'damaged' => 3, 'lost' => 2, 'condemnable' => 0]],
                                ['id' => 14, 'image' => 'book11.jpg', 'title' => 'Mathematics Grade 6', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-621-06-789-0', 'copyright' => 2021, 'pages' => 340, 'edit_url' => '#edit-print-14', 'subjects' => [['subject' => 'Mathematics', 'grade' => '6']], 'quantities' => ['usable' => 96, 'partially_damaged' => 4, 'damaged' => 2, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 15, 'image' => 'book12.jpg', 'title' => 'Science Grade 10', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-345-6', 'copyright' => 2023, 'pages' => 360, 'edit_url' => '#edit-print-15', 'subjects' => [['subject' => 'Science', 'grade' => '10']], 'quantities' => ['usable' => 140, 'partially_damaged' => 8, 'damaged' => 4, 'lost' => 3, 'condemnable' => 0]],
                                ['id' => 16, 'image' => 'book13.jpg', 'title' => 'Filipino Grade 5', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-621-06-901-2', 'copyright' => 2022, 'pages' => 270, 'edit_url' => '#edit-print-16', 'subjects' => [['subject' => 'Filipino', 'grade' => '5']], 'quantities' => ['usable' => 108, 'partially_damaged' => 5, 'damaged' => 2, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 17, 'image' => 'book14.jpg', 'title' => 'Araling Panlipunan Grade 10', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-678-9', 'copyright' => 2021, 'pages' => 310, 'edit_url' => '#edit-print-17', 'subjects' => [['subject' => 'Araling Panlipunan', 'grade' => '10']], 'quantities' => ['usable' => 92, 'partially_damaged' => 3, 'damaged' => 1, 'lost' => 0, 'condemnable' => 0]],
                                ['id' => 18, 'image' => 'book15.jpg', 'title' => 'TLE Grade 7', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-621-06-234-5', 'copyright' => 2023, 'pages' => 290, 'edit_url' => '#edit-print-18', 'subjects' => [['subject' => 'TLE', 'grade' => '7']], 'quantities' => ['usable' => 105, 'partially_damaged' => 7, 'damaged' => 3, 'lost' => 2, 'condemnable' => 0]],
                                ['id' => 19, 'image' => 'book16.jpg', 'title' => 'MAPEH Grade 9', 'author' => 'DepEd', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-971-655-012-3', 'copyright' => 2022, 'pages' => 280, 'edit_url' => '#edit-print-19', 'subjects' => [['subject' => 'MAPEH', 'grade' => '9']], 'quantities' => ['usable' => 98, 'partially_damaged' => 4, 'damaged' => 2, 'lost' => 1, 'condemnable' => 0]],
                                ['id' => 20, 'image' => 'book17.jpg', 'title' => 'English Grade 8', 'author' => 'DepEd Team', 'publisher' => 'DepEd', 'type' => 'Book', 'isbn' => '978-621-06-567-8', 'copyright' => 2021, 'pages' => 310, 'edit_url' => '#edit-print-20', 'subjects' => [['subject' => 'English', 'grade' => '8']], 'quantities' => ['usable' => 120, 'partially_damaged' => 6, 'damaged' => 3, 'lost' => 1, 'condemnable' => 0]],
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
                                        class="w-12 h-16 object-cover rounded shadow-sm">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 max-w-xs">{{ $item['title'] }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $item['author'] }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $item['publisher'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item['type'] }}</span>
                                </td>
                                <!-- NEW SUBJECT COLUMN -->
                                <td class="px-4 py-3 text-xs">
                                    @if(isset($item['subjects']) && count($item['subjects']) > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($item['subjects'] as $sub)
                                                <span class="inline-block bg-blue-100 text-blue-800 font-medium px-2 py-1 rounded-full">
                                                    {{ $sub['subject'] }} - Grade {{ $sub['grade'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-xs">No assignment</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $item['isbn'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $item['copyright'] }}</td>
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
                                        <button onclick='openPrintModal(@json($item))'
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

    @include('pages.components.view-print-modal')
@endsection
