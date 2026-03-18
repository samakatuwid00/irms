<div class="p-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Manage Districts and Schools</h1>
    </div>

    {{-- MESSAGE --}}
    @if (session('success'))
        <div id="success-message"
            class="relative bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-lg shadow-md mb-6 transform translate-y-0 transition-all duration-500 ease-in-out">

            <button type="button"
                    onclick="closeSuccessMessage()"
                    class="absolute top-3 right-4 text-green-700 hover:text-green-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <p class="font-medium">{{ session('success') }}</p>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const msg = document.getElementById('success-message');
                if (msg) {
                    setTimeout(() => {
                        msg.style.opacity = '0';
                        msg.style.transform = 'translateY(-20px)';
                        setTimeout(() => msg.remove(), 500);
                    }, 5000);
                }
            });

            function closeSuccessMessage() {
                const msg = document.getElementById('success-message');
                if (msg) {
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-20px)';
                    setTimeout(() => msg.remove(), 500);
                }
            }
        </script>
    @endif

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-8">
            <button type="button"
                    onclick="switchTab('districts')"
                    id="tab-districts"
                    class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors
                           border-blue-500 text-blue-600">
                Districts
            </button>
            <button type="button"
                    onclick="switchTab('schools')"
                    id="tab-schools"
                    class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors
                           border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                Schools
            </button>
        </nav>
    </div>

    {{-- ================= DISTRICTS TAB ================= --}}
    <div id="content-districts" class="tab-content">

        <!-- ================= SEARCH ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <div class="flex items-center gap-3">
                <!-- Search Form -->
                <form method="GET" class="flex-1 flex items-center gap-3">
                    <input type="hidden" name="active_tab" value="districts">
                    <div class="relative flex-1">
                        <input type="text"
                            name="district_search"
                            value="{{ request('district_search') }}"
                            placeholder="Search districts..."
                            class="w-full pl-10 pr-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-blue-200 focus:outline-none">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg> Search
                    </button>
                </form>

                <!-- Add District Button (outside form) -->
                <button type="button"
                        data-modal-target="add-district-modal"
                        data-modal-toggle="add-district-modal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add District
                </button>
            </div>
        </div>

        <!-- ================= TABLE ================= -->
        <div class="bg-white rounded-xl shadow overflow-hidden mt-4">
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

                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-3">
                                    <!-- Edit -->
                                    <button type="button"
                                            data-modal-target="edit-district-modal"
                                            data-id="{{ $district->id }}"
                                            data-name="{{ $district->district_name }}"
                                            data-shortname="{{ $district->shortname }}"
                                            data-address="{{ $district->address }}"
                                            data-district="{{ $district->legislative_district }}"
                                            data-contact="{{ $district->contact_number }}"
                                            data-email="{{ $district->email }}"
                                            data-date="{{ $district->date_establish }}"
                                            class="text-yellow-600 hover:text-yellow-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    <!-- <button type="button"
                                            data-modal-target="delete-district-modal"
                                            data-id="{{ $district->id }}"
                                            data-name="{{ $district->district_name }}"
                                            class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button> -->
                                </div>
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
            {{ $districts->appends(['district_search' => request('district_search'), 'active_tab' => 'districts'])->links() }}
        </div>
    </div>

    {{-- ================= SCHOOLS TAB ================= --}}
    <div id="content-schools" class="tab-content hidden">

    <!-- ================= DISTRICT SELECTOR & SEARCH ================= -->
    <div class="bg-white rounded-xl shadow p-4">
        <form method="GET" id="schoolFilterForm">
            <input type="hidden" name="active_tab" value="schools">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <!-- District Dropdown - takes 4 columns -->
                <div class="md:col-span-4 flex flex-col">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select District</label>
                    <select name="selected_district"
                            id="districtSelector"
                            onchange="this.form.submit()"
                            class="w-full border rounded-lg px-4 py-2.5 focus:ring focus:ring-blue-200 text-sm">
                        <option value="">-- Select District --</option>
                        @if($districtsWithSchools)
                            @foreach($districtsWithSchools as $district)
                                <option value="{{ $district->id }}"
                                        {{ request('selected_district') == $district->id ? 'selected' : '' }}>
                                    {{ $district->district_name }} ({{ $district->schools->count() }} schools)
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Search Box - takes 8 columns -->
                <div class="md:col-span-8 flex flex-col">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Schools</label>
                    <div class="flex items-center gap-2">
                        <!-- Search Input - expanded -->
                        <div class="relative flex-grow">
                            <input type="text"
                                name="school_search"
                                value="{{ request('school_search') }}"
                                placeholder="Search schools by name, city, or zip code..."
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                        </div>

                        <!-- Search Button -->
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            Search
                        </button>

                        <!-- Add School Button -->
                        <button type="button"
                                data-modal-target="add-school-modal"
                                id="add-school-btn-header"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add School
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

        <!-- ================= SCHOOLS TABLE ================= -->
        @if(request('selected_district'))
            @php
                $selectedDistrict = $districtsWithSchools->firstWhere('id', request('selected_district'));
                $schoolsToDisplay = $selectedDistrict ? $selectedDistrict->schools : collect();

                // Apply search filter if exists
                if (request('school_search')) {
                    $searchTerm = strtolower(request('school_search'));
                    $schoolsToDisplay = $schoolsToDisplay->filter(function($school) use ($searchTerm) {
                        return str_contains(strtolower($school->school_name), $searchTerm) ||
                               str_contains(strtolower($school->shortname ?? ''), $searchTerm) ||
                               str_contains(strtolower($school->school_type ?? ''), $searchTerm) ||
                               str_contains(strtolower($school->address ?? ''), $searchTerm) ||
                               str_contains(strtolower($school->email ?? ''), $searchTerm);
                    });
                }
            @endphp

            <div class="bg-white rounded-xl shadow overflow-hidden mt-4">
                <!-- District Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <h3 class="text-lg font-bold text-white">{{ $selectedDistrict->district_name }}</h3>
                    <p class="text-blue-100 text-sm">{{ $schoolsToDisplay->count() }} school(s) found</p>
                </div>

                @if($schoolsToDisplay->count() > 0)
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">School Name</th>
                                <th class="px-4 py-3">School ID</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Short Name</th>
                                <th class="px-4 py-3">Address</th>
                                <th class="px-4 py-3">Contact</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($schoolsToDisplay as $school)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium">{{ $school->school_name }}</td>
                                    <td class="px-4 py-3">{{ $school->school_id ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if($school->school_type)
                                            <span class="px-2 py-1 text-xs rounded-full
                                                @if($school->school_type == 'ELEMENTARY') bg-green-100 text-green-800
                                                @elseif($school->school_type == 'HIGH SCHOOL') bg-blue-100 text-blue-800
                                                @else bg-purple-100 text-purple-800
                                                @endif">
                                                {{ $school->school_type }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $school->shortname ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $school->address ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $school->contact_number ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $school->email ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center gap-3">
                                            <!-- Edit -->
                                            <button type="button"
                                                    data-modal-target="edit-school-modal"
                                                    data-id="{{ $school->id }}"
                                                    data-name="{{ $school->school_name }}"
                                                    data-shortname="{{ $school->shortname }}"
                                                    data-address="{{ $school->address }}"
                                                    data-contact="{{ $school->contact_number }}"
                                                    data-email="{{ $school->email }}"
                                                    data-date="{{ $school->date_establish }}"
                                                    data-legislative="{{ $school->legislative_school }}"
                                                    data-type="{{ $school->school_type }}"
                                                    class="text-yellow-600 hover:text-yellow-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <!-- Delete -->
                                            <!-- <button type="button"
                                                    data-modal-target="delete-school-modal"
                                                    data-id="{{ $school->id }}"
                                                    data-name="{{ $school->school_name }}"
                                                    class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button> -->
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="px-4 py-8 text-center text-gray-500">
                        No schools found{{ request('school_search') ? ' matching your search' : '' }}.
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <p class="text-lg font-medium">Please select a district to view schools</p>
            </div>
        @endif
    </div>

</div>

{{-- ADD DISTRICT MODAL --}}
<div id="add-district-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-7 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-6">Add District</h2>

        <form action="{{ route('districts.store') }}" method="POST">
            @csrf

            <div class="space-y-4">
                <input name="district_name" required placeholder="District Name"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="shortname" placeholder="Short Name"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="address" placeholder="Address"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="legislative_district" placeholder="Legislative District"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="contact_number" placeholder="Contact Number"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="email" type="email" placeholder="Email"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="date_establish" type="date"
                       class="w-full border rounded-lg px-4 py-2">
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('add-district-modal')" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT DISTRICT MODAL --}}
<div id="edit-district-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-7 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-6">Edit District</h2>

        <form method="POST" id="editDistrictForm">
            @csrf
            @method('PUT')

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">District Name *</label>
                <input id="edit_district_name" name="district_name" required class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Short Name</label>
                <input id="edit_shortname" name="shortname" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input id="edit_address" name="address" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Legislative District</label>
                <input id="edit_legislative_district" name="legislative_district" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input id="edit_contact_number" name="contact_number" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="edit_email" name="email" type="email" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Date Established</label>
                <input id="edit_date_establish" name="date_establish" type="date" class="w-full border rounded-lg px-4 py-2">
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('edit-district-modal')" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE DISTRICT MODAL --}}
<div id="delete-district-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <h2 class="text-lg font-bold text-red-600 mb-4">Delete District</h2>
        <p id="deleteDistrictMessage" class="mb-6"></p>

        <form method="POST" id="deleteDistrictForm">
            @csrf
            @method('DELETE')

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('delete-district-modal')" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-red-600 text-white rounded">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ADD SCHOOL MODAL --}}
<div id="add-school-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-7 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-6">Add School</h2>

        <form action="{{ route('schools.store') }}" method="POST" id="addSchoolForm">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">District *</label>
                    <select name="district_id" id="add_school_district_id" required class="w-full border rounded-lg px-4 py-2">
                        <option value="">-- Select District --</option>
                        @if($districtsWithSchools)
                            @foreach($districtsWithSchools as $district)
                                <option value="{{ $district->id }}"
                                        {{ request('selected_district') == $district->id ? 'selected' : '' }}>
                                    {{ $district->district_name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <input name="school_name" required placeholder="School Name *"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="school_id" required placeholder="School ID *"
                       class="w-full border rounded-lg px-4 py-2">

                <select name="school_type" class="w-full border rounded-lg px-4 py-2">
                    <option value="" selected disabled>Select School Type</option>
                    <option value="KINDER">Kinder Only</option>
                    <option value="ELEMENTARY">Elementary</option>
                    <option value="JHS">Junior High School</option>
                    <option value="SHS"> Senior High School</option>
                    <option value="JSHS">Junior and Senior High School</option>
                    <option value="INTEGRATED">Integrated School</option>
                </select>

                <input name="shortname" placeholder="Short Name"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="address" placeholder="Address"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="contact_number" placeholder="Contact Number"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="email" type="email" placeholder="Email"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="date_establish" type="date" placeholder="Date Established"
                       class="w-full border rounded-lg px-4 py-2">

                <input name="legislative_school" placeholder="Legislative School"
                       class="w-full border rounded-lg px-4 py-2">
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('add-school-modal')" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT SCHOOL MODAL --}}
<div id="edit-school-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-7 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-6">Edit School</h2>

        <form method="POST" id="editSchoolForm">
            @csrf
            @method('PUT')

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">School Name *</label>
                <input id="edit_school_name" name="school_name" required class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">School Type</label>
                <select id="edit_school_type" name="school_type" class="w-full border rounded-lg px-4 py-2">
                    <option value="" selected disabled>Select School Type</option>
                    <option value="KINDER">Kinder Only</option>
                    <option value="ELEMENTARY">Elementary</option>
                    <option value="JHS">Junior High School</option>
                    <option value="SHS"> Senior High School</option>
                    <option value="JSHS">Junior and Senior High School</option>
                    <option value="INTEGRATED">Integrated School</option>
                </select>

                <label class="block text-sm font-medium text-gray-700 mb-1">Short Name</label>
                <input id="edit_school_shortname" name="shortname" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input id="edit_school_address" name="address" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                <input id="edit_school_contact_number" name="contact_number" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="edit_school_email" name="email" type="email" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Date Established</label>
                <input id="edit_school_date_establish" name="date_establish" type="date" class="w-full border rounded-lg px-4 py-2">

                <label class="block text-sm font-medium text-gray-700 mb-1">Legislative School</label>
                <input id="edit_school_legislative" name="legislative_school" class="w-full border rounded-lg px-4 py-2">
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('edit-school-modal')" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE SCHOOL MODAL --}}
<div id="delete-school-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full">
        <h2 class="text-lg font-bold text-red-600 mb-4">Delete School</h2>
        <p id="deleteSchoolMessage" class="mb-6"></p>

        <form method="POST" id="deleteSchoolForm">
            @csrf
            @method('DELETE')

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal('delete-school-modal')" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-red-600 text-white rounded">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ================= TAB SWITCHING =================
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active state from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-blue-500', 'text-blue-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });

        // Show selected tab content
        document.getElementById('content-' + tabName).classList.remove('hidden');

        // Add active state to selected tab
        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-blue-500', 'text-blue-600');

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('active_tab', tabName);
        window.history.pushState({}, '', url);
    }

    // Initialize tab on page load
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('active_tab') || 'districts';
        switchTab(activeTab);
    });

    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // ================= ADD DISTRICT =================
    document.querySelectorAll('[data-modal-target="add-district-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('add-district-modal');
        });
    });

    // ================= EDIT DISTRICT =================
    document.querySelectorAll('[data-modal-target="edit-district-modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('editDistrictForm');
            form.action = `/districts/${this.dataset.id}`;

            document.getElementById('edit_district_name').value = this.dataset.name;
            document.getElementById('edit_shortname').value = this.dataset.shortname || '';
            document.getElementById('edit_address').value = this.dataset.address || '';
            document.getElementById('edit_legislative_district').value = this.dataset.district || '';
            document.getElementById('edit_contact_number').value = this.dataset.contact || '';
            document.getElementById('edit_email').value = this.dataset.email || '';
            document.getElementById('edit_date_establish').value = this.dataset.date || '';

            openModal('edit-district-modal');
        });
    });

    // ================= DELETE DISTRICT =================
    document.querySelectorAll('[data-modal-target="delete-district-modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('deleteDistrictForm');
            form.action = `/districts/${this.dataset.id}`;

            document.getElementById('deleteDistrictMessage').innerHTML =
                `Are you sure you want to permanently delete <strong>"${this.dataset.name}"</strong>?<br>
                <span class="text-red-600 font-medium">This action cannot be undone.</span>`;

            openModal('delete-district-modal');
        });
    });

    // ================= ADD SCHOOL =================
    document.querySelectorAll('[data-modal-target="add-school-modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            // Pre-select district if coming from a specific district context
            const selectedDistrict = document.getElementById('districtSelector')?.value;
            if (selectedDistrict) {
                document.getElementById('add_school_district_id').value = selectedDistrict;
            }

            openModal('add-school-modal');
        });
    });

    // ================= EDIT SCHOOL =================
    document.querySelectorAll('[data-modal-target="edit-school-modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('editSchoolForm');
            form.action = `/schools/${this.dataset.id}`;

            document.getElementById('edit_school_name').value = this.dataset.name;
            document.getElementById('edit_school_type').value = this.dataset.type || '';
            document.getElementById('edit_school_shortname').value = this.dataset.shortname || '';
            document.getElementById('edit_school_address').value = this.dataset.address || '';
            document.getElementById('edit_school_contact_number').value = this.dataset.contact || '';
            document.getElementById('edit_school_email').value = this.dataset.email || '';
            document.getElementById('edit_school_date_establish').value = this.dataset.date || '';
            document.getElementById('edit_school_legislative').value = this.dataset.legislative || '';

            openModal('edit-school-modal');
        });
    });

    // ================= DELETE SCHOOL =================
    document.querySelectorAll('[data-modal-target="delete-school-modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('deleteSchoolForm');
            form.action = `/schools/${this.dataset.id}`;

            document.getElementById('deleteSchoolMessage').innerHTML =
                `Are you sure you want to permanently delete <strong>"${this.dataset.name}"</strong>?<br>
                <span class="text-red-600 font-medium">This action cannot be undone.</span>`;

            openModal('delete-school-modal');
        });
    });
</script>
