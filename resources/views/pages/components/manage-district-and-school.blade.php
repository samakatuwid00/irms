<div class="p-6 space-y-6">

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
                                <button type="button"
                                        data-modal-target="delete-district-modal"
                                        data-id="{{ $district->id }}"
                                        data-name="{{ $district->district_name }}"
                                        class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
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
        {{ $districts->appends(['district_search' => request('district_search')])->links() }}
    </div>

</div>

{{-- ADD DISTRICT MODAL --}}
<div id="add-district-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
<div id="edit-district-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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
<div id="delete-district-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
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

<script>

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
</script>


