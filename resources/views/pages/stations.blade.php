@extends('pages.layout.layout')

@section('title', 'Stations')

@section('page-title', 'Stations')

@section('content')
    <div class="p-6 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h1 class="text-xl font-semibold text-gray-800">Stations</h1>
        </div>

        <!-- ================= SEARCH ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" class="flex items-center gap-3">

                <div class="relative w-full">
                    <input type="text"
                        name="search"
                        placeholder="Search by station name..."
                        class="w-full pl-10 pr-3 py-2 border rounded-lg text-sm focus:ring focus:ring-blue-200">

                    <!-- Search Icon -->
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>

                <button type="submit"
                        class="p-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>

            </form>
        </div>

        <!-- ================= FILTERS ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Station Type -->
                <div>
                    <label class="text-xs text-gray-500">Station Type</label>
                    <select name="type"
                            class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option>Main</option>
                        <option>Branch</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status"
                            class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>

                <!-- Apply -->
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

            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Station Name</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                    @php
                        $stations = collect([
                            [
                                'name'=>'Station 1',
                                'type'=>'Main',
                                'location'=>'Building A',
                                'status'=>'Active'
                            ],
                            [
                                'name'=>'Station 2',
                                'type'=>'Branch',
                                'location'=>'Building B',
                                'status'=>'Active'
                            ],
                            [
                                'name'=>'Station III',
                                'type'=>'Branch',
                                'location'=>'Building C',
                                'status'=>'Inactive'
                            ],
                        ]);
                    @endphp

                    @foreach ($stations as $station)
                    <tr class="hover:bg-gray-50">

                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $station['name'] }}
                        </td>

                        <td class="px-4 py-3">{{ $station['type'] }}</td>

                        <td class="px-4 py-3">{{ $station['location'] }}</td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $station['status'] === 'Active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-gray-200 text-gray-700' }}">
                                {{ $station['status'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-2">

                                <!-- View -->
                                <a href="#"
                                class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                    View
                                </a>

                                <!-- Edit -->
                                <a href="#"
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
@endsection
