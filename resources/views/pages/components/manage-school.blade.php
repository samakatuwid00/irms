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
                       name="search"
                       value="{{ request('search') }}"
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
                        <td class="px-4 py-3">{{ $school->school_type ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->address ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->contact_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $school->email ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $school->date_establish ? \Carbon\Carbon::parse($school->date_establish)->format('M d, Y') : '—' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            <!-- Edit Button -->
                            <button type="button"
                                    data-modal-target="edit-school-modal"
                                    data-modal-toggle="edit-school-modal"
                                    class="text-yellow-600 hover:text-yellow-800"
                                    title="Edit"
                                    data-id="{{ $school->id }}"
                                    data-name="{{ $school->school_name }}"
                                    data-shortname="{{ $school->shortname }}"
                                    data-type="{{ $school->school_type }}"
                                    data-address="{{ $school->address }}"
                                    data-contact="{{ $school->contact_number }}"
                                    data-email="{{ $school->email }}"
                                    data-date="{{ $school->date_establish }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <!-- Delete Button -->
                            <button type="button"
                                    data-modal-target="delete-school-modal"
                                    data-modal-toggle="delete-school-modal"
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete"
                                    data-id="{{ $school->id }}"
                                    data-name="{{ $school->school_name }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            No schools found {{ request('search') ? 'matching "' . request('search') . '"' : '' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
        {{ $schools->appends(['search' => request('search')])->links() }}
    </div>

</div>
