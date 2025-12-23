<div class="p-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-xl font-semibold text-gray-800">Districts</h1>

        <!-- Add District Button -->
        <button type="button"
                data-modal-target="add-district-modal"
                data-modal-toggle="add-district-modal"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 text-sm font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add District
        </button>
    </div>

    <!-- ================= SEARCH ================= -->
    <div class="bg-white rounded-xl shadow p-4">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="tab" value="districts">
            <div class="relative w-full">
                <input type="text"
                       name="district_search"
                       value="{{ request('district_search') }}"
                       placeholder="Search by district name..."
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

    <!-- ================= TABLE ================= -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">District Name</th>
                    <th class="px-4 py-3">Short Name</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Legislative District</th>
                    <th class="px-4 py-3">Contact Number</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Date Established</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse ($districts as $district)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $district->district_name }}</td>
                        <td class="px-4 py-3">{{ $district->shortname ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $district->address ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $district->legislative_district ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $district->contact_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $district->email ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $district->date_establish ? \Carbon\Carbon::parse($district->date_establish)->format('M d, Y') : '—' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            <!-- Edit / Delete buttons same pattern as division -->
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No districts found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-center">
        {{ $districts->appends(['district_search' => request('district_search')])->links() }}
    </div>

</div>


<div class="p-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-xl font-semibold text-gray-800">Schools</h1>

        <!-- Add School Button -->
        <button type="button"
                data-modal-target="add-school-modal"
                data-modal-toggle="add-school-modal"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 text-sm font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add School
        </button>
    </div>

    <!-- ================= SEARCH ================= -->
    <div class="bg-white rounded-xl shadow p-4">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="tab" value="schools">
            <div class="relative w-full">
                <input type="text"
                       name="school_search"
                       value="{{ request('school_search') }}"
                       placeholder="Search by school name, type, or address..."
                       class="w-full pl-10 pr-3 py-2 border rounded-lg text-sm focus:ring focus:ring-blue-200">
                <!-- Search Icon -->
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

    <!-- ================= TABLE ================= -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">School Name</th>
                    <th class="px-4 py-3">Short Name</th>
                    <th class="px-4 py-3">District</th>
                    <th class="px-4 py-3">School Type</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Contact Number</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Date Established</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse ($schools as $school)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $school->school_name }}</td>
                        <td class="px-4 py-3">{{ $school->shortname ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->district?->district_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->school_type ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->address ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->contact_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->email ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $school->date_establish ? \Carbon\Carbon::parse($school->date_establish)->format('M d, Y') : '—' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            <!-- Edit / Delete buttons -->
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            No schools found {{ request('search') ? 'matching "' . request('search') . '"' : '' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-center">
       {{ $schools->appends(['school_search' => request('school_search')])->links() }}
    </div>

</div>

