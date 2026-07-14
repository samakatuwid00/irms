<div class="station-management-page space-y-6">

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
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Manage Schools</h1>
    </div>

    <!-- ================= SEARCH ================= -->
    <div class="bg-white rounded-xl shadow p-3 sm:p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search Form -->
            <form method="GET" class="flex-1 flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="tab" value="schools">
                <div class="relative flex-1">
                    <input type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by school name, type, or address..."
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

            <!-- Add School Button -->
            <button type="button"
                    data-modal-target="add-school-modal"
                    data-modal-toggle="add-school-modal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Add School</span>
                <span class="sm:hidden">Add</span>
            </button>
        </div>
    </div>

    <!-- ================= TABLE - Responsive ================= -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-3 sm:px-4 py-3">School Name</th>
                        <th class="px-3 sm:px-4 py-3 hidden sm:table-cell">Short Name</th>
                        <th class="px-3 sm:px-4 py-3 hidden md:table-cell">School ID</th>
                        <th class="px-3 sm:px-4 py-3 hidden lg:table-cell">School Type</th>
                        <th class="px-3 sm:px-4 py-3 hidden xl:table-cell">Address</th>
                        <th class="px-3 sm:px-4 py-3 hidden lg:table-cell">Contact</th>
                        <th class="px-3 sm:px-4 py-3 hidden xl:table-cell">Email</th>
                        <th class="px-3 sm:px-4 py-3 hidden 2xl:table-cell">Established</th>
                        <th class="px-3 sm:px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse ($schools as $school)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-4 py-3 font-medium text-sm">{{ $school->school_name }}</td>
                            <td class="px-3 sm:px-4 py-3 hidden sm:table-cell">{{ $school->shortname ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-3 hidden md:table-cell">{{ $school->school_id ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-3 hidden lg:table-cell">
                                @php
                                    $typeLabels = [
                                        'primary' => 'Primary',
                                        'pecondary' => 'Secondary',
                                        'junior-high' => 'Junior High',
                                        'senior-high' => 'Senior High'
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($school->school_type == 'primary') bg-green-100 text-green-800
                                    @elseif($school->school_type == 'pecondary') bg-blue-100 text-blue-800
                                    @elseif($school->school_type == 'junior-high') bg-yellow-100 text-yellow-800
                                    @elseif($school->school_type == 'senior-high') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-600
                                    @endif">
                                    {{ ucfirst($typeLabels[$school->school_type] ?? $school->school_type ?? '—') }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 py-3 hidden xl:table-cell">{{ Str::limit($school->address ?? '—', 30) }}</td>
                            <td class="px-3 sm:px-4 py-3 hidden lg:table-cell">{{ $school->contact_number ?? '—' }}</td>
                            <td class="px-3 sm:px-4 py-3 hidden xl:table-cell">{{ Str::limit($school->email ?? '—', 25) }}</td>
                            <td class="px-3 sm:px-4 py-3 hidden 2xl:table-cell whitespace-nowrap">
                                {{ $school->date_establish ? \Carbon\Carbon::parse($school->date_establish)->format('M d, Y') : '—' }}
                            </td>

                            <td class="px-3 sm:px-4 py-3 text-center">
                                <div class="flex justify-center gap-2 sm:gap-3">
                                    <!-- Edit Button -->
                                    <button type="button"
                                            data-modal-target="edit-school-modal"
                                            data-modal-toggle="edit-school-modal"
                                            class="text-yellow-600 hover:text-yellow-800 transition"
                                            title="Edit"
                                            data-id="{{ $school->id }}"
                                            data-name="{{ $school->school_name }}"
                                            data-shortname="{{ $school->shortname }}"
                                            data-type="{{ $school->school_type }}"
                                            data-address="{{ $school->address }}"
                                            data-contact="{{ $school->contact_number }}"
                                            data-email="{{ $school->email }}"
                                            data-date="{{ $school->date_establish }}">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                </div>
                             </td>
                         </tr>
                    @empty
                         <tr>
                            <td colspan="9" class="px-3 sm:px-4 py-8 sm:py-12 text-center text-gray-500 text-sm">
                                No schools found {{ request('search') ? 'matching "' . request('search') . '"' : '' }}.
                             </td>
                         </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4 sm:mt-6 flex justify-center overflow-x-auto">
        {{ $schools->appends(['search' => request('search')])->links('pagination::tailwind') }}
    </div>

</div>

{{-- ADD SCHOOL MODAL - Responsive --}}
<div id="add-school-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-5 sm:p-7 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg sm:text-xl font-bold mb-4 sm:mb-6">Add School</h2>

        <form action="{{ route('schools.store') }}" method="POST">
            @csrf

            <div class="space-y-3 sm:space-y-4">
                <input name="school_name" required placeholder="School Name" 
                       class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">

                <input name="shortname" placeholder="Short Name" 
                       class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">

                <input name="school_id" class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base" 
                       placeholder="School ID">

                <select name="school_type" class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                    <option value="">Select School Type</option>
                    <option value="primary">Primary</option>
                    <option value="pecondary">Secondary</option>
                    <option value="junior-high">Junior High</option>
                    <option value="senior-high">Senior High</option>
                </select>

                <input name="address" placeholder="Address" 
                       class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">

                <input name="contact_number" placeholder="Contact Number" 
                       class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">

                <input name="email" type="email" placeholder="Email" 
                       class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">

                <input name="date_establish" type="date" 
                       class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
            </div>

            <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                <button type="button" onclick="closeModal('add-school-modal')"
                        class="px-4 py-2 bg-gray-200 rounded order-2 sm:order-1">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded order-1 sm:order-2">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT SCHOOL MODAL - Responsive --}}
<div id="edit-school-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-5 sm:p-7 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg sm:text-xl font-bold mb-4 sm:mb-6">Edit School</h2>

        <form method="POST" id="editSchoolForm">
            @csrf
            @method('PUT')

            <div class="space-y-3 sm:space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Name *</label>
                    <input id="edit_school_name" name="school_name" required 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Short Name</label>
                    <input id="edit_shortname" name="shortname" 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School ID</label>
                    <input id="edit_school_id" name="school_id" 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Type</label>
                    <select id="edit_school_type" name="school_type" 
                            class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                        <option value="">Select School Type</option>
                        <option value="primary">Primary</option>
                        <option value="pecondary">Secondary</option>
                        <option value="junior-high">Junior High</option>
                        <option value="senior-high">Senior High</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input id="edit_address" name="address" 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input id="edit_contact_number" name="contact_number" 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="edit_email" name="email" type="email" 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Established</label>
                    <input id="edit_date_establish" name="date_establish" type="date" 
                           class="w-full border rounded-lg px-4 py-2 text-sm sm:text-base">
                </div>
            </div>

            <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                <button type="button" onclick="closeModal('edit-school-modal')"
                        class="px-4 py-2 bg-gray-200 rounded order-2 sm:order-1">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded order-1 sm:order-2">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE SCHOOL MODAL - Responsive --}}
<div id="delete-school-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-xl p-5 sm:p-6 max-w-md w-full mx-3 sm:mx-0">
        <h2 class="text-base sm:text-lg font-bold text-red-600 mb-3 sm:mb-4">Delete School</h2>
        <p id="deleteSchoolMessage" class="mb-4 sm:mb-6 text-sm sm:text-base"></p>

        <form method="POST" id="deleteSchoolForm">
            @csrf
            @method('DELETE')

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                <button type="button" onclick="closeModal('delete-school-modal')"
                        class="px-4 py-2 bg-gray-200 rounded order-2 sm:order-1">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-red-600 text-white rounded order-1 sm:order-2">
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

    // ================= ADD SCHOOL =================
    document.querySelectorAll('[data-modal-target="add-school-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('add-school-modal');
        });
    });

    // ================= EDIT SCHOOL =================
    document.querySelectorAll('[data-modal-target="edit-school-modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('editSchoolForm');
            form.action = `/schools/${this.dataset.id}`;

            document.getElementById('edit_school_name').value = this.dataset.name;
            document.getElementById('edit_shortname').value = this.dataset.shortname || '';
            document.getElementById('edit_school_type').value = this.dataset.type || '';
            document.getElementById('edit_address').value = this.dataset.address || '';
            document.getElementById('edit_contact_number').value = this.dataset.contact || '';
            document.getElementById('edit_email').value = this.dataset.email || '';
            document.getElementById('edit_date_establish').value = this.dataset.date || '';

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

    // Close modal when clicking outside
    document.querySelectorAll('.fixed.inset-0.bg-black\\/50').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
</script>
