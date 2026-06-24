@extends('pages.layout.layout')

@section('title', 'Division Profile')
@section('page-title', 'Division Profile')

@section('header-title', $division->division_name)
@section('header-subtitle', 'Manage division profile information')
@section('breadcrumb', 'Division Profile')

@section('content')

@include('pages.partials.page-header')

{{-- ================== FLASH MESSAGE ================== --}}
@if(session('success') || session('info'))
<div id="alertBox" class="mb-4 rounded-lg px-4 py-3 relative
    {{ session('success') ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
    <span>{{ session('success') ?? session('info') }}</span>
    <button onclick="closeAlert()" class="absolute top-1 right-1 text-gray-500 hover:text-gray-800">&times;</button>
</div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-100 text-red-800 px-4 py-3">
        <ul class="list-disc pl-5 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- ================= LEFT: LOGO ================= --}}
    <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center gap-4">

        {{-- Logo Preview --}}
        <div class="relative">
            <img
                src="{{ $division->logo ? asset('storage/' . $division->logo) : asset('assets/images/default.jpg') }}"
                alt="Division Logo"
                class="w-64 h-64 rounded-xl object-cover border-2 border-dashed border-gray-300 bg-gray-50"
                id="logoPreview"
            >
        </div>

        {{-- Logo Upload Form --}}
        <form id="logoForm" action="{{ route('division.logo.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <label for="logo"
                class="block w-full cursor-pointer border-2 border-dashed border-gray-300 rounded-lg px-4 py-3 text-sm text-gray-500 hover:border-blue-400 hover:bg-blue-50 transition">
                <span class="font-medium text-gray-700">Choose logo</span>
                <span class="block text-xs text-gray-400 mt-1">PNG or JPG (Max 2MB)</span>
            </label>
            <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg" class="hidden">

            <button type="submit" id="logoSubmitBtn"
                class="w-full px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow mt-2 transition opacity-50 cursor-not-allowed"
                disabled>
                Update Logo
            </button>
        </form>

        {{-- Division Name --}}
        <div class="mt-2">
            <h2 class="text-lg font-semibold text-gray-800">{{ $division->division_name }}</h2>
            <p class="text-sm text-gray-500">{{ $division->shortname }}</p>
        </div>

    </div>

    {{-- ================= RIGHT: DIVISION INFO ================= --}}
    <div class="md:col-span-2">

        {{-- Corrected form: method="POST" + proper action --}}
        <form id="divisionForm" class="bg-white rounded-xl shadow p-6"
              action="{{ route('division.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <h3 class="text-lg font-semibold mb-6">Division Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Division Name --}}
                <div>
                    <label class="text-xs text-gray-500">Division Name *</label>
                    <input type="text" name="division_name" value="{{ $division->division_name }}" class="input" required>
                    <p class="error"></p>
                </div>

                {{-- Short Name --}}
                <div>
                    <label class="text-xs text-gray-500">Short Name</label>
                    <input type="text" name="shortname" value="{{ $division->shortname }}" class="input">
                    <p class="error"></p>
                </div>

                {{-- Email --}}
                <div>
                    <label class="text-xs text-gray-500">Email *</label>
                    <input type="email" name="email" value="{{ $division->email }}" class="input" required>
                    <p class="error"></p>
                </div>

                {{-- Contact Number --}}
                <div>
                    <label class="text-xs text-gray-500">Contact Number</label>
                    <input type="text" name="contact_number" value="{{ $division->contact_number }}" class="input">
                    <p class="error"></p>
                </div>

                {{-- Legislative District --}}
                <div>
                    <label class="text-xs text-gray-500">Legislative District</label>
                    <input type="text" name="legislative_district" value="{{ $division->legislative_district }}" class="input">
                    <p class="error"></p>
                </div>

                {{-- Date Established --}}
                <div>
                    <label class="text-xs text-gray-500">Date Established</label>

                    {{-- Readable date input --}}
                    <input
                        type="text"
                        id="date_display"
                        value="{{ $division->date_establish ? \Carbon\Carbon::parse($division->date_establish)->format('F d, Y') : '' }}"
                        readonly
                        class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                        onclick="switchToDate()"
                    >

                    {{-- Actual date input (hidden) --}}
                    <input
                        type="date"
                        name="date_establish"
                        id="date_input"
                        value="{{ $division->date_establish }}"
                        class="hidden mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                        onchange="updateReadableDate()"
                        onblur="switchToText()"
                    >
                    <p class="error"></p>
                </div>

                {{-- Address --}}
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Address</label>
                    <textarea name="address" class="input" rows="3">{{ $division->address }}</textarea>
                    <p class="error"></p>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button" id="saveBtn" onclick="openConfirmModal()" class="btn-primary opacity-50 cursor-not-allowed" disabled>
                    Save Changes
                </button>
            </div>
        </form>

    </div>
</div>

{{-- ================= CONFIRM MODAL ================= --}}
<div id="confirmModal" class="fixed inset-0 hidden z-50 bg-black/50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-2">Confirm Save</h3>

        <p class="text-sm text-gray-600 mb-3">You changed the following:</p>
        <ul id="changedFields" class="text-sm text-gray-700 list-disc pl-5 mb-5"></ul>

        <div class="flex justify-end gap-3">
            <button onclick="closeConfirmModal()" class="btn-secondary">Cancel</button>
            <button type="button" onclick="submitForm()" id="confirmBtn" class="btn-primary">Yes, Save</button>
        </div>
    </div>
</div>

{{-- ================= STYLES ================= --}}
<style>
    .input { width:100%; border:2px dashed #d1d5db; border-radius:.5rem; padding:.5rem .75rem; }
    .error { font-size:.75rem; color:#dc2626; }
    .btn-primary { background:#2563eb; color:#fff; padding:.5rem 1.25rem; border-radius:.5rem; }
    .btn-secondary { border:1px solid #d1d5db; padding:.5rem 1.25rem; border-radius:.5rem; }
</style>


{{-- ================= LIBRARY HUBS ================= --}}
<div class="bg-white rounded-xl shadow overflow-hidden mt-4">
    <div class="p-4 border-b border-gray-200">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Library Hubs</h2>
                    <p class="text-sm text-gray-500">Manage library hubs assigned to this division station.</p>
                </div>

                <button type="button"
                        id="openAddLibraryHubModal"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Library Hub</span>
                </button>
            </div>

            <form method="GET" action="{{ route('division-profile') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="active_tab" value="library_hubs">

                <div class="relative flex-1">
                    <input type="text"
                        name="library_hub_search"
                        value="{{ $libraryHubSearch }}"
                        placeholder="Search by hub name, librarian, email, or exact resource count..."
                        class="w-full pl-10 pr-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-blue-200 focus:outline-none">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <span>Search</span>
                    </button>

                    <a href="{{ route('division-profile', ['active_tab' => 'library_hubs']) }}"
                       class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium whitespace-nowrap">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-3 sm:px-4 py-3">Library Hub Name</th>
                    <th class="px-3 sm:px-4 py-3">Librarian</th>
                    <th class="px-3 sm:px-4 py-3 text-right">Estimated Resources</th>
                    <th class="px-3 sm:px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @forelse ($libraryHubs as $hub)
                    @php
                        $librarian = $hub->librarianUser;
                        $librarianName = $librarian
                            ? trim($librarian->firstname . ' ' . $librarian->lastname . ' ' . ($librarian->extension_name ?? ''))
                            : 'Unassigned';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 sm:px-4 py-3 font-medium text-gray-800">{{ $hub->library_name }}</td>
                        <td class="px-3 sm:px-4 py-3">
                            <div class="font-medium text-gray-700">{{ $librarianName }}</div>
                            <div class="text-xs text-gray-500">{{ $librarian?->email ?? '-' }}</div>
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                            {{ number_format((int) $hub->estimated_resource) }}
                        </td>
                        <td class="px-3 sm:px-4 py-3">
                            <div class="flex justify-center gap-2 sm:gap-3">
                                <button type="button"
                                        class="edit-library-hub-btn text-yellow-600 hover:text-yellow-800"
                                        title="Edit library hub"
                                        data-action="{{ route('division.library-hubs.update', $hub->id) }}"
                                        data-library-name="{{ $hub->library_name }}"
                                        data-librarian="{{ $hub->librarian }}"
                                        data-estimated-resource="{{ $hub->estimated_resource }}">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 sm:px-4 py-8 text-center text-gray-500">
                            No library hubs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($libraryHubs->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $libraryHubs->links() }}
        </div>
    @endif
</div>

{{-- ================= ADD LIBRARY HUB MODAL ================= --}}
<div id="addLibraryHubModal" class="fixed inset-0 hidden z-50 bg-black/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-lg font-semibold">Add Library Hub</h3>
                <p class="text-sm text-gray-500">Create a division library hub record.</p>
            </div>
            <button type="button" class="close-library-hub-modal text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <form action="{{ route('division.library-hubs.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs text-gray-500">Library Hub Name *</label>
                <input type="text" name="library_name" value="{{ old('library_name') }}" class="input" required>
            </div>

            <div>
                <label class="text-xs text-gray-500">Librarian *</label>
                <select name="librarian" class="input" required>
                    @foreach ($divisionLibrarians as $user)
                        @php
                            $userName = trim($user->firstname . ' ' . $user->lastname . ' ' . ($user->extension_name ?? ''));
                        @endphp
                        <option value="{{ $user->id }}" {{ old('librarian', Auth::id()) === $user->id ? 'selected' : '' }}>
                            {{ $userName }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-500">Estimated Resources *</label>
                <input type="number" name="estimated_resource" value="{{ old('estimated_resource', 0) }}" min="0" class="input" required>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="close-library-hub-modal btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Library Hub</button>
            </div>
        </form>
    </div>
</div>

{{-- ================= EDIT LIBRARY HUB MODAL ================= --}}
<div id="editLibraryHubModal" class="fixed inset-0 hidden z-50 bg-black/50 items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-lg">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-lg font-semibold">Edit Library Hub</h3>
                <p class="text-sm text-gray-500">Update this division library hub record.</p>
            </div>
            <button type="button" class="close-library-hub-modal text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <form id="editLibraryHubForm" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="text-xs text-gray-500">Library Hub Name *</label>
                <input type="text" name="library_name" id="editLibraryHubName" class="input" required>
            </div>

            <div>
                <label class="text-xs text-gray-500">Librarian *</label>
                <select name="librarian" id="editLibraryHubLibrarian" class="input" required>
                    @foreach ($divisionLibrarians as $user)
                        @php
                            $userName = trim($user->firstname . ' ' . $user->lastname . ' ' . ($user->extension_name ?? ''));
                        @endphp
                        <option value="{{ $user->id }}">{{ $userName }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-500">Estimated Resources *</label>
                <input type="number" name="estimated_resource" id="editLibraryHubEstimatedResource" min="0" class="input" required>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="close-library-hub-modal btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
    
@vite('resources/js/division-profile.js')

@endsection
