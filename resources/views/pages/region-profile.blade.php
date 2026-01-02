@extends('pages.layout.layout')

@section('title', 'Region Profile')
@section('page-title', 'Region Profile')

@section('header-title', $region->region_name)
@section('header-subtitle', 'Manage regional profile information')
@section('breadcrumb', 'Region Profile')

@section('content')

@include('pages.partials.page-header')

<form class="grid grid-cols-1 md:grid-cols-3 gap-6" method="POST" action="" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- ================= LEFT: LOGO ================= -->
    <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center gap-4">

        <!-- Logo Preview -->
        <div class="relative">
            <img
                src="{{ $region->logo ? asset('storage/'.$region->logo) : asset('assets/images/default.jpg') }}"
                alt="Region Logo"
                class="w-64 h-64 rounded-xl object-cover border-2 border-dashed border-gray-300 bg-gray-50"
            >
        </div>

        <!-- File Input -->
        <div class="w-full">
            <label
                for="logo"
                class="block w-full cursor-pointer border-2 border-dashed border-gray-300 rounded-lg px-4 py-3 text-sm text-gray-500 hover:border-blue-400 hover:bg-blue-50 transition">
                <span class="font-medium text-gray-700">Choose logo</span>
                <span class="block text-xs text-gray-400 mt-1">PNG or JPG</span>
            </label>

            <input
                id="logo"
                type="file"
                name="logo"
                class="hidden"
                accept="image/*"
            >
        </div>

        <!-- Update Logo Button -->
        <button type="submit"
            class="w-full px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow transition">
            Update Logo
        </button>

        <!-- Region Name -->
        <div class="mt-2">
            <h2 class="text-lg font-semibold text-gray-800">{{ $region->region_name }}</h2>
            <p class="text-sm text-gray-500">{{ $region->shortname }}</p>
        </div>

    </div>

    <!-- ================= RIGHT: DETAILS ================= -->
    <div class="md:col-span-2 bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Region Information</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Region Name -->
            <div>
                <label class="text-xs text-gray-500">Region Name</label>
                <input type="text"
                    name="region_name"
                    value="{{ old('region_name', $region->region_name) }}"
                    class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition">
            </div>

            <!-- Short Name -->
            <div>
                <label class="text-xs text-gray-500">Short Name</label>
                <input type="text"
                    name="shortname"
                    value="{{ old('shortname', $region->shortname) }}"
                    class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition">
            </div>

            <!-- Email -->
            <div>
                <label class="text-xs text-gray-500">Email</label>
                <input type="email"
                    name="email"
                    value="{{ old('email', $region->email) }}"
                    class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition">
            </div>

            <!-- Contact Number -->
            <div>
                <label class="text-xs text-gray-500">Contact Number</label>
                <input type="text"
                    name="contact_number"
                    value="{{ old('contact_number', $region->contact_number) }}"
                    class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition">
            </div>

            <!-- Date Established -->
            <div>
                <label class="text-xs text-gray-500">Date Established</label>

                <!-- Readable date -->
                <input
                    type="text"
                    id="date_display"
                    value="{{ \Carbon\Carbon::parse($region->date_establish)->format('F d, Y') }}"
                    readonly
                    class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                    onclick="switchToDate()"
                >

                <!-- Actual date input -->
                <input
                    type="date"
                    id="date_input"
                    name="date_establish"
                    value="{{ $region->date_establish }}"
                    class="hidden mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                    onchange="updateReadableDate()"
                    onblur="switchToText()"
                >
            </div>

            <!-- Address -->
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Address</label>
                <textarea name="address" rows="3"
                    class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition">{{ old('address', $region->address) }}</textarea>
            </div>

        </div>

        <!-- ================= ACTIONS ================= -->
        <div class="flex justify-end gap-3 mt-6">
            <button type="reset"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                Cancel
            </button>

            <button type="submit"
                class="px-5 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow transition">
                Save Changes
            </button>
        </div>
    </div>
</form>

<script>
    function switchToDate() {
        document.getElementById('date_display').classList.add('hidden');
        const dateInput = document.getElementById('date_input');
        dateInput.classList.remove('hidden');
        dateInput.focus();
    }

    function switchToText() {
        document.getElementById('date_input').classList.add('hidden');
        document.getElementById('date_display').classList.remove('hidden');
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
        }
    }
</script>

@endsection
