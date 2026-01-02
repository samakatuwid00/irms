@extends('pages.layout.layout')

@section('title', 'Region Profile')
@section('page-title', 'Region Profile')

@section('header-title', $region->region_name)
@section('header-subtitle', 'Manage regional profile information')
@section('breadcrumb', 'Region Profile')

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
                src="{{ $region->logo ? asset('storage/' . $region->logo) : asset('assets/images/default.jpg') }}"
                alt="Region Logo"
                class="w-64 h-64 rounded-xl object-cover border-2 border-dashed border-gray-300 bg-gray-50"
                id="logoPreview"
            >
        </div>

        {{-- Logo Upload Form --}}
        <form id="logoForm" action="" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <label for="logo"
                class="block w-full cursor-pointer border-2 border-dashed border-gray-300 rounded-lg px-4 py-3 text-sm text-gray-500 hover:border-blue-400 hover:bg-blue-50 transition">
                <span class="font-medium text-gray-700">Choose logo</span>
                <span class="block text-xs text-gray-400 mt-1">PNG or JPG</span>
            </label>
            <input type="file" name="logo" id="logo" accept="image/*" class="hidden">

            <button type="submit"
                class="w-full px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow mt-2 transition">
                Update Logo
            </button>
        </form>

        {{-- Region Name --}}
        <div class="mt-2">
            <h2 class="text-lg font-semibold text-gray-800">{{ $region->region_name }}</h2>
            <p class="text-sm text-gray-500">{{ $region->shortname }}</p>
        </div>

    </div>

    {{-- ================= RIGHT: REGION INFO ================= --}}
    <div class="md:col-span-2">

        {{-- Corrected form: method="POST" + proper action --}}
        <form id="regionForm" class="bg-white rounded-xl shadow p-6"
              action="{{ route('region.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <h3 class="text-lg font-semibold mb-6">Region Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Region Name --}}
                <div>
                    <label class="text-xs text-gray-500">Region Name *</label>
                    <input type="text" name="region_name" value="{{ $region->region_name }}" class="input" required>
                    <p class="error"></p>
                </div>

                {{-- Short Name --}}
                <div>
                    <label class="text-xs text-gray-500">Short Name</label>
                    <input type="text" name="shortname" value="{{ $region->shortname }}" class="input">
                    <p class="error"></p>
                </div>

                {{-- Email --}}
                <div>
                    <label class="text-xs text-gray-500">Email *</label>
                    <input type="email" name="email" value="{{ $region->email }}" class="input" required>
                    <p class="error"></p>
                </div>

                {{-- Contact Number --}}
                <div>
                    <label class="text-xs text-gray-500">Contact Number</label>
                    <input type="text" name="contact_number" value="{{ $region->contact_number }}" class="input">
                    <p class="error"></p>
                </div>

                {{-- Date Established --}}
                <div>
                    <label class="text-xs text-gray-500">Date Established</label>

                    {{-- Readable date input --}}
                    <input
                        type="text"
                        id="date_display"
                        value="{{ $region->date_establish ? \Carbon\Carbon::parse($region->date_establish)->format('F d, Y') : '' }}"
                        readonly
                        class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                        onclick="switchToDate()"
                    >

                    {{-- Actual date input (hidden) --}}
                    <input
                        type="date"
                        name="date_establish"
                        id="date_input"
                        value="{{ $region->date_establish }}"
                        class="hidden mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                        onchange="updateReadableDate()"
                        onblur="switchToText()"
                    >
                    <p class="error"></p>
                </div>

                {{-- Address --}}
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Address</label>
                    <textarea name="address" class="input" rows="3">{{ $region->address }}</textarea>
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

{{-- ================= JS ================= --}}
<script>
const form = document.getElementById('regionForm');
const saveBtn = document.getElementById('saveBtn');
const modal = document.getElementById('confirmModal');
const changedList = document.getElementById('changedFields');
const alertBox = document.getElementById('alertBox');
const logoInput = document.getElementById('logo');
const logoPreview = document.getElementById('logoPreview');

const originalData = Object.fromEntries(new FormData(form).entries());

// Enable/disable Save button
form.addEventListener('input', toggleSaveButton);
function hasChanges() {
    const current = new FormData(form);
    return [...current.entries()].some(([k,v]) => v !== (originalData[k] ?? ''));
}
function toggleSaveButton() {
    const dirty = hasChanges();
    saveBtn.disabled = !dirty;
    saveBtn.classList.toggle('opacity-50', !dirty);
    saveBtn.classList.toggle('cursor-not-allowed', !dirty);
}

// Modal logic
function openConfirmModal() {
    changedList.innerHTML = '';
    getChangedFields().forEach(f => {
        changedList.innerHTML += `<li>${f}</li>`;
    });
    modal.classList.remove('hidden');
}
function closeConfirmModal() { modal.classList.add('hidden'); }
function getChangedFields() {
    const current = new FormData(form);
    return [...current.entries()]
        .filter(([k,v]) => v !== (originalData[k] ?? ''))
        .map(([k]) => k.replace(/_/g,' ').replace(/\b\w/g,l=>l.toUpperCase()));
}

// Submit form via normal POST (reload page)
function submitForm() {
    form.submit();
}

// Flash message
let alertTimeout = null;
function closeAlert() {
    clearTimeout(alertTimeout);
    if(alertBox) alertBox.classList.add('hidden');
}
if(alertBox) {
    alertTimeout = setTimeout(() => {
        alertBox.classList.add('hidden');
    }, 6000);
}

// Logo preview
function previewLogo(event) {
    if (event.target.files && event.target.files[0]) {
        logoPreview.src = URL.createObjectURL(event.target.files[0]);
    }
}
if (logoInput) logoInput.addEventListener('change', previewLogo);

// ================= DATE ESTABLISHED SWITCH =================
function switchToDate() {
    document.getElementById('date_display').classList.add('hidden');
    const dateInput = document.getElementById('date_input');
    dateInput.classList.remove('hidden');
    dateInput.focus();
}

function switchToText() {
    const dateInput = document.getElementById('date_input');
    const dateDisplay = document.getElementById('date_display');
    dateInput.classList.add('hidden');
    dateDisplay.classList.remove('hidden');
    updateReadableDate();
}

function updateReadableDate() {
    const dateInput = document.getElementById('date_input');
    const display = document.getElementById('date_display');
    if (dateInput.value) {
        const date = new Date(dateInput.value);
        display.value = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } else {
        display.value = '';
    }
}
</script>

@endsection
