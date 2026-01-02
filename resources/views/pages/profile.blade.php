@extends('pages.layout.layout')

@section('title', 'User Profile')

@section('page-title', 'User Profile')

@section('content')

<div class="max-w-6xl mx-auto px-4 py-8">

    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            class="mb-6 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex justify-between items-start transition-all duration-500"
        >
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="ml-4 text-green-800 font-bold">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            class="mb-6 rounded-lg bg-red-100 border border-red-300 text-red-800 px-4 py-3 relative transition-all duration-500"
        >
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button @click="show = false" class="absolute top-1 right-2 text-red-800 font-bold">&times;</button>
        </div>
    @endif

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">My Profile</h1>
        <p class="text-sm text-gray-500">Manage your personal information and security</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: Profile Picture --}}
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex flex-col items-center text-center">

                {{-- Avatar --}}
                <div class="relative">
                    <img
                        src="{{ auth()->user()->photo ? asset('storage/' . auth()->user()->photo) : asset('assets/images/default.jpg') }}"
                        class="w-48 h-48 rounded-full object-cover
                            border-2 border-dotted border-gray-300
                            shadow-sm"
                        alt="Profile Photo"
                    >
                </div>

                <h2 class="mt-4 font-semibold text-gray-800">
                    {{ auth()->user()->firstname }} {{ auth()->user()->lastname }}
                </h2>

                <p class="text-sm uppercase tracking-wide text-gray-500">
                    {{ auth()->user()->usertype_name }}
                </p>

                {{-- Status Badge --}}
                <span class="mt-2 inline-flex items-center gap-2
                            px-3 py-1 rounded-full
                            text-xs font-semibold
                            bg-green-100 text-green-700">

                    <span class="w-2 h-2 rounded-full bg-green-600"></span>
                    Active

                </span>

                {{-- Divider --}}
                <div class="w-full border-t border-dashed my-5"></div>

                {{-- User Meta Info --}}
                <div class="space-y-2 text-sm text-gray-600 text-left w-full">

                    <p>
                        <span class="font-semibold text-gray-500">Email:</span>
                        <span class="ml-1 font-medium text-gray-800 break-all">
                            {{ auth()->user()->email }}
                        </span>
                    </p>

                    <p>
                        <span class="font-semibold text-gray-500">Date Joined:</span>
                        <span class="ml-1 font-medium text-gray-800">
                            {{ auth()->user()->created_at->format('M d, Y') }}
                        </span>
                    </p>

                </div>

                {{-- Change Photo --}}
                <form action="" enctype="multipart/form-data" class="mt-6 w-full">
                    @csrf
                    @method('PUT')

                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2 text-left">
                        Change Photo
                    </label>

                    <input
                        type="file"
                        name="photo"
                        class="block w-full rounded-lg
                            text-sm text-gray-600
                            border-2 border-dotted border-gray-300
                            bg-gray-50
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-xs file:font-semibold
                            file:bg-blue-100 file:text-blue-700
                            hover:file:bg-blue-200
                            focus:bg-white focus:border-blue-500
                            transition"
                        required
                    >

                    <div class="mt-4 flex justify-center">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center
                                bg-blue-600 text-white
                                p-3 rounded-full shadow
                                hover:bg-blue-700 hover:shadow-md
                                focus:outline-none focus:ring-2 focus:ring-blue-300
                                transition"
                            title="Update Photo"
                        > Update Photo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT: Information & Password --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- User Information --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6 border-b pb-2">
                    Personal Information
                </h3>

                <form action = "{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Input Group --}}
                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                First Name
                            </label>
                            <input
                                type="text"
                                name="firstname"
                                value="{{ auth()->user()->firstname }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                                    transition"
                            >
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Middle Name
                            </label>
                            <input
                                type="text"
                                name="middlename"
                                value="{{ auth()->user()->middlename }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                                    transition"
                            >
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Last Name
                            </label>
                            <input
                                type="text"
                                name="lastname"
                                value="{{ auth()->user()->lastname }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                                    transition"
                            >
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Extension
                            </label>
                            <input
                                type="text"
                                name="extension_name"
                                value="{{ auth()->user()->extension_name }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                                    transition"
                            >
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Username
                            </label>
                            <input
                                type="text"
                                name="username"
                                value="{{ auth()->user()->username }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100
                                    transition"
                            >
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Email
                            </label>
                            <input
                                type="email"
                                name="email"
                                value="{{ auth()->user()->email }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-100
                                    transition"
                            >
                        </div>

                        <div class="relative md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Contact Number
                            </label>
                            <input
                                type="text"
                                name="contact_number"
                                value="{{ auth()->user()->contact_number }}"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-green-500 focus:ring-2 focus:ring-green-100
                                    transition"
                            >
                        </div>

                    </div>

                    <div class="mt-6 text-right">
                        <button
                            type="submit"
                            class="group inline-flex items-center justify-center
                                bg-green-600 text-white
                                p-3 rounded-full shadow
                                hover:bg-green-700 hover:shadow-md
                                focus:outline-none focus:ring-2 focus:ring-green-300
                                transition"
                            title="Save Changes"
                        > Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6 border-b pb-2">
                    Change Password
                </h3>

                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5">

                        {{-- Current Password --}}
                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Current Password
                            </label>
                            <input
                                type="password"
                                name="current_password"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-red-500 focus:ring-2 focus:ring-red-100
                                    transition"
                            >
                        </div>

                        {{-- New Password --}}
                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                New Password
                            </label>
                            <input
                                type="password"
                                name="password"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-orange-500 focus:ring-2 focus:ring-orange-100
                                    transition"
                            >
                        </div>

                        {{-- Confirm Password --}}
                        <div class="relative">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                Confirm Password
                            </label>
                            <input
                                type="password"
                                name="password_confirmation"
                                class="w-full rounded-lg border-2 border-dotted border-gray-300 bg-gray-50
                                    px-4 py-2 text-gray-700
                                    focus:bg-white focus:border-green-500 focus:ring-2 focus:ring-green-100
                                    transition"
                            >
                        </div>

                    </div>

                    <div class="mt-6 text-right">
                        <button
                            type="submit"
                            class="group inline-flex items-center justify-center
                                bg-red-600 text-white
                                p-3 rounded-full shadow
                                hover:bg-red-700 hover:shadow-md
                                focus:outline-none focus:ring-2 focus:ring-red-300
                                transition"
                            title="Update Password"
                        > Update Password
                        </button>
                    </div>
                </form>
            </div>


        </div>
    </div>
</div>
@endsection
