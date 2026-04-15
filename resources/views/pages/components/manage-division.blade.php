<div class="p-4 sm:p-6 space-y-4 sm:space-y-6">

    {{-- MESSAGE --}}
    @if (session('success'))
        <div id="success-message"
            class="relative bg-green-50 border border-green-200 text-green-800 px-4 sm:px-5 py-3 sm:py-4 rounded-lg shadow-md mb-4 sm:mb-6 transform translate-y-0 transition-all duration-500 ease-in-out text-sm sm:text-base">

            <button type="button"
                    onclick="closeSuccessMessage()"
                    class="absolute top-2 sm:top-3 right-3 sm:right-4 text-green-700 hover:text-green-900">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <p class="font-medium pr-6">{{ session('success') }}</p>
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Manage Divisions</h1>
    </div>

    <!-- ================= SEARCH ================= -->
    <div class="bg-white rounded-xl shadow p-3 sm:p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search Form -->
            <form method="GET" class="flex-1 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <input type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search divisions..."
                        class="w-full pl-10 pr-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-blue-200 focus:outline-none">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>

                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg> 
                    <span class="hidden sm:inline">Search</span>
                </button>
            </form>

            <!-- Add Division Button -->
            <button type="button"
                    data-modal-target="add-division-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Add Division</span>
                <span class="sm:hidden">Add</span>
            </button>
        </div>
    </div>

    <!-- Table - Responsive with horizontal scroll -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-3 sm:px-6 py-3 sm:py-4">Division Name</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden sm:table-cell">Short Name</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden md:table-cell">Region</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden lg:table-cell">Address</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden xl:table-cell">Legislative District</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden lg:table-cell">Contact</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden xl:table-cell">Email</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 hidden 2xl:table-cell">Established</th>
                        <th class="px-3 sm:px-6 py-3 sm:py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($divisions as $division)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 font-medium text-gray-900 text-sm">{{ $division->division_name }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden sm:table-cell">{{ $division->shortname ?? '—' }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden md:table-cell">{{ $division->region?->region_name ?? '—' }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden lg:table-cell">{{ Str::limit($division->address ?? '—', 30) }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden xl:table-cell">{{ $division->legislative_district ?? '—' }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden lg:table-cell">{{ $division->contact_number ?? '—' }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden xl:table-cell">{{ Str::limit($division->email ?? '—', 25) }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 hidden 2xl:table-cell whitespace-nowrap">
                                {{ $division->date_establish
                                    ? \Carbon\Carbon::parse($division->date_establish)->format('M d, Y')
                                    : '—' }}
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4">
                                <div class="flex justify-center gap-2 sm:gap-3">
                                    <!-- Edit -->
                                    <button type="button"
                                            data-modal-target="edit-division-modal"
                                            title="Edit Division"
                                            data-id="{{ $division->id }}"
                                            data-name="{{ $division->division_name }}"
                                            data-shortname="{{ $division->shortname }}"
                                            data-address="{{ $division->address }}"
                                            data-district="{{ $division->legislative_district }}"
                                            data-contact="{{ $division->contact_number }}"
                                            data-email="{{ $division->email }}"
                                            data-date="{{ $division->date_establish }}"
                                            class="text-yellow-600 hover:text-yellow-800 transition">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 sm:px-6 py-8 sm:py-12 text-center text-gray-500 text-sm">
                                No divisions found{{ request('search') ? ' matching "' . request('search') . '"' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4 sm:mt-6 flex justify-center overflow-x-auto">
        {{ $divisions->appends(['search' => request('search')])->links('pagination::tailwind') }}
    </div>
</div>

<!-- ====================== ADD MODAL - Responsive ====================== -->
<div id="add-division-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-5 sm:p-7 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Add New Division</h2>

        <form action="{{ route('divisions.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Division Name *</label>
                    <input type="text" name="division_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Short Name</label>
                    <input type="text" name="shortname" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="address" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Legislative District</label>
                    <input type="text" name="legislative_district" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_number" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Established</label>
                    <input type="date" name="date_establish" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>
            </div>

            <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row justify-end gap-2 sm:gap-4">
                <button type="button" onclick="closeModal('add-division-modal')"
                        class="px-4 sm:px-6 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition order-2 sm:order-1">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 sm:px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium order-1 sm:order-2">
                    Add Division
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ====================== EDIT MODAL - Responsive ====================== -->
<div id="edit-division-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-5 sm:p-7 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Edit Division</h2>

        <form method="POST" id="editDivisionForm">
            @csrf
            @method('PUT')
            <div class="space-y-4 sm:space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Division Name *</label>
                    <input type="text" name="division_name" id="edit_division_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Short Name</label>
                    <input type="text" name="shortname" id="edit_shortname" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="address" id="edit_address" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Legislative District</label>
                    <input type="text" name="legislative_district" id="edit_legislative_district" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" name="contact_number" id="edit_contact_number" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit_email" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Established</label>
                    <input type="date" name="date_establish" id="edit_date_establish" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm sm:text-base">
                </div>
            </div>

            <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row justify-end gap-2 sm:gap-4">
                <button type="button" onclick="closeModal('edit-division-modal')"
                        class="px-4 sm:px-6 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition order-2 sm:order-1">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 sm:px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium order-1 sm:order-2">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ====================== DELETE MODAL - Responsive ====================== -->
<div id="delete-division-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-5 sm:p-7 w-full max-w-md">
        <h2 class="text-xl sm:text-2xl font-bold text-red-700 mb-4 sm:mb-5">Confirm Deletion</h2>
        <p class="text-gray-700 mb-5 sm:mb-6 text-sm sm:text-base" id="deleteDivisionMessage"></p>

        <form method="POST" id="deleteDivisionForm">
            @csrf
            @method('DELETE')

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-4">
                <button type="button" onclick="closeModal('delete-division-modal')"
                        class="px-4 sm:px-6 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition order-2 sm:order-1">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 sm:px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium order-1 sm:order-2">
                    Yes, Delete Permanently
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ====================== JAVASCRIPT ====================== -->
<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // Add Modal
    document.querySelectorAll('[data-modal-target="add-division-modal"]').forEach(btn => {
        btn.addEventListener('click', () => openModal('add-division-modal'));
    });

    // Edit Modal
    document.querySelectorAll('[data-modal-target="edit-division-modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('editDivisionForm');
            form.action = `/divisions/${this.dataset.id}`;

            document.getElementById('edit_division_name').value = this.dataset.name;
            document.getElementById('edit_shortname').value = this.dataset.shortname || '';
            document.getElementById('edit_address').value = this.dataset.address || '';
            document.getElementById('edit_legislative_district').value = this.dataset.district || '';
            document.getElementById('edit_contact_number').value = this.dataset.contact || '';
            document.getElementById('edit_email').value = this.dataset.email || '';
            document.getElementById('edit_date_establish').value = this.dataset.date || '';

            openModal('edit-division-modal');
        });
    });

    // Delete Modal
    document.querySelectorAll('[data-modal-target="delete-division-modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('deleteDivisionForm');
            form.action = `/divisions/${this.dataset.id}`;

            document.getElementById('deleteDivisionMessage').innerHTML =
                `Are you sure you want to permanently delete <strong>"${this.dataset.name}"</strong>?<br>
                 <span class="text-red-600 font-medium">This action cannot be undone.</span>`;

            openModal('delete-division-modal');
        });
    });

    // Close modal when clicking outside
    document.querySelectorAll('.fixed.inset-0.bg-black\\/50').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
</script>