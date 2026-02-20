<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LRMIS')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- echarts -->
    @vite(['resources/js/charts/availability.js'])
    @vite(['resources/js/charts/ratio.js'])
    @vite(['resources/js/charts/lr.js'])
    @vite(['resources/js/charts/exdef.js'])
    @vite(['resources/js/charts/heatmap.js'])
    @vite(['resources/js/charts/visualization.js'])

    <!-- CSRF Token for forms -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS & App JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Alpine JS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Custom Styles -->
    <style>
        .bg-custom-yellow { background-color: #F3C623; }
        .hover\:bg-custom-yellow-hover:hover { background-color: #e6b81e; }
        .text-custom-teal { color: #127681; }
        .border-custom-teal { border-color: #127681; }

        /* Smooth transitions for mobile menu */
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        #mobile-menu.open {
            max-height: calc(100vh - 64px);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100">

    <!-- Mobile Top Navigation Bar -->
    <nav class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white shadow-lg">
        <!-- Top Bar Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
            <!-- Logo and Title -->
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('assets/images/logo.png') }}"
                    alt="iRIMS-V Logo"
                    class="w-8 h-8 object-contain"
                />
                <h2 class="text-lg font-bold tracking-wide">
                    <span class="text-[#0AC4E0]">i</span><span class="text-[#1A3263]">RIMS-</span><span class="text-[#DA3D20]">V</span>
                </h2>
            </div>

            <!-- User Avatar and Menu Toggle -->
            <div class="flex items-center gap-3">
                <img class="w-8 h-8 rounded-full border-2 border-gray-200"
                     src="{{ auth()->user()->photo ? asset('storage/' . auth()->user()->photo) : asset('assets/images/default.jpg') }}"
                     alt="User Avatar">

                <button id="mobile-menu-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <svg id="menu-open-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="menu-close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Collapsible Mobile Menu -->
        <div id="mobile-menu" class="bg-white border-b border-gray-200 overflow-y-auto">
            <nav class="px-4 py-2">
                <ul class="space-y-1">

                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg transition-colors
                                  {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-6v-7H10v7H4a1 1 0 0 1-1-1z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>

                    <!-- Add Resource Submenu (Mobile) -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 3]))
                        <li>
                            <button type="button" id="mobile-add-resource-toggle"
                                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-colors
                                           {{ request()->routeIs('print-resource.create', 'nonprint-resource.create') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                <span class="flex items-center gap-3">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M4 19a2 2 0 0 0 2 2h12"/>
                                        <path d="M4 5a2 2 0 0 1 2-2h12v14H6a2 2 0 0 0-2 2z"/>
                                        <path d="M12 7v6"/>
                                        <path d="M9 10h6"/>
                                    </svg>
                                    Add Resource
                                </span>
                                <svg id="mobile-add-resource-chevron"
                                     class="w-4 h-4 transition-transform {{ request()->routeIs('print-resource.create', 'nonprint-resource.create') ? 'rotate-180' : '' }}"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            <ul id="mobile-add-resource-submenu"
                                class="mt-1 ml-8 space-y-1 {{ request()->routeIs('print-resource.create', 'nonprint-resource.create') ? '' : 'hidden' }}">
                                <li>
                                    <a href="{{ route('print-resource.create') }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                              {{ request()->routeIs('print-resource.create') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M6 9V2h12v7"/>
                                            <path d="M6 18h12v4H6z"/>
                                            <path d="M6 14h12"/>
                                        </svg>
                                        Add Print Resource
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('nonprint-resource.create') }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                              {{ request()->routeIs('nonprint-resource.create') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>
                                        Add Non-Print Resource
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- Resources Submenu (Mobile) -->
                    <li>
                        <button type="button" id="mobile-resources-toggle"
                                class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-colors
                                       {{ request()->routeIs('print-resources', 'nonprint-resources') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                            <span class="flex items-center gap-3">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                </svg>
                                Resources
                            </span>
                            <svg id="mobile-resources-chevron"
                                 class="w-4 h-4 transition-transform {{ request()->routeIs('print-resources', 'nonprint-resources') ? 'rotate-180' : '' }}"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <ul id="mobile-resources-submenu"
                            class="mt-1 ml-8 space-y-1 {{ request()->routeIs('print-resources', 'nonprint-resources') ? '' : 'hidden' }}">
                            <li>
                                <a href="{{ route('print-resources') }}"
                                   class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                          {{ request()->routeIs('print-resources') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M6 9V2h12v7"/>
                                        <path d="M6 18h12v4H6z"/>
                                        <path d="M6 14h12"/>
                                    </svg>
                                    Print Resource
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('nonprint-resources') }}"
                                   class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                          {{ request()->routeIs('nonprint-resources') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <path d="M14 2v6h6"/>
                                    </svg>
                                    Non-Print Resource
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Users -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;
                            $routeName = 'users';
                            $label = match ($level) {
                                1 => 'School Users',
                                2 => 'District Users',
                                3 => 'Division Users',
                                4 => 'Region Users',
                            };
                        @endphp
                        <li>
                            <a href="{{ $routeName }}"
                               class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs('users') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/>
                                    <circle cx="17" cy="7" r="3"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                </svg>
                                {{ $label }}
                            </a>
                        </li>
                    @endif

                    <!-- Stations -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;
                            $routeName = 'stations';
                            $label = match ($level) {
                                2 => 'Manage School',
                                3 => 'Manage District and School',
                                4 => 'Manage Division',
                            };
                        @endphp
                        <li>
                            <a href="{{ $routeName }}"
                               class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 3l9 6-9 6-9-6z"/>
                                    <path d="M3 21h18"/>
                                </svg>
                                {{ $label }}
                            </a>
                        </li>
                    @endif

                    <!-- Divider -->
                    <li class="py-2">
                        <div class="border-t border-gray-200"></div>
                    </li>

                    <!-- My Account -->
                    <li>
                        <a href="{{ route('profile') }}"
                           class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg transition-colors
                                  {{ request()->routeIs('profile') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M6 21v-2a6 6 0 0 1 12 0v2"/>
                            </svg>
                            My Account
                        </a>
                    </li>

                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;
                            $routeName = match ($level) {
                                1 => 'school-profile',
                                2 => 'district-profile',
                                3 => 'division-profile',
                                4 => 'region-profile',
                            };
                            $label = match ($level) {
                                1 => 'School Profile',
                                2 => 'District Profile',
                                3 => 'Division Profile',
                                4 => 'Region Profile',
                            };
                        @endphp
                        <li>
                            <a href="{{ route($routeName) }}"
                               class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg transition-colors
                                      {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 9l9-6 9 6v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <path d="M9 22V12h6v10"/>
                                </svg>
                                {{ $label }}
                            </a>
                        </li>
                    @endif

                    <!-- Logout -->
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg transition-colors text-red-600 hover:bg-red-50">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17 16l4-4-4-4"/>
                                    <path d="M7 12h14"/>
                                    <path d="M7 4v16"/>
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </li>

                </ul>
            </nav>
        </div>
    </nav>

    <div class="flex h-screen">
        <!-- Desktop Sidebar -->
        <div class="hidden md:flex md:w-64 md:flex-col bg-white shadow-lg">
            <div class="flex items-center justify-between p-4 border-b border-gray-300">
                <div class="flex items-center gap-3">
                    <img
                        src="{{ asset('assets/images/logo.png') }}"
                        alt="iRIMS-V Logo"
                        class="w-10 h-10 object-contain"
                    />
                    <h2 class="text-xl font-bold tracking-wide">
                        <span class="text-[#0AC4E0]">i</span><span class="text-[#1A3263]">RIMS-</span><span class="text-[#DA3D20]">V</span>
                    </h2>
                </div>
            </div>

            <nav class="p-4 flex-1 overflow-y-auto">
                <ul class="space-y-2 hs-accordion-group">

                    <!-- Dashboard -->
                    <li class="rounded-lg transition-colors">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg
                                  {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-6v-7H10v7H4a1 1 0 0 1-1-1z"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>

                    <!-- Add Resource Accordion (Desktop) -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 3]))
                        <li class="hs-accordion rounded-lg transition-colors" id="add-resource-accordion">
                            <button type="button"
                                    class="hs-accordion-toggle w-full flex justify-between py-2 px-2.5 text-sm rounded-lg
                                    {{ request()->routeIs('print-resource.create', 'nonprint-resource.create')
                                        ? 'bg-blue-100 text-blue-600'
                                        : 'text-gray-800 hover:bg-gray-100' }}"
                                    aria-controls="add-resource-accordion-collapse">
                                <span class="flex items-center gap-x-3.5">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M4 19a2 2 0 0 0 2 2h12"/>
                                        <path d="M4 5a2 2 0 0 1 2-2h12v14H6a2 2 0 0 0-2 2z"/>
                                        <path d="M12 7v6"/>
                                        <path d="M9 10h6"/>
                                    </svg>
                                    Add Resource
                                </span>
                                <svg class="hs-accordion-active:rotate-180 size-4 transition-transform"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            <div id="add-resource-accordion-collapse"
                                 class="hs-accordion-content overflow-hidden transition-[height] duration-300
                                 {{ request()->routeIs('print-resource.create', 'nonprint-resource.create') ? '' : 'hidden' }}">
                                <ul class="pt-2 ps-8 space-y-1">
                                    <li>
                                        <a href="{{ route('print-resource.create') }}"
                                           class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                           {{ request()->routeIs('print-resource.create')
                                               ? 'bg-blue-50 text-blue-600 translate-x-1'
                                               : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M6 9V2h12v7"/>
                                                <path d="M6 18h12v4H6z"/>
                                                <path d="M6 14h12"/>
                                            </svg>
                                            Add Print Resource
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('nonprint-resource.create') }}"
                                           class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                           {{ request()->routeIs('nonprint-resource.create')
                                               ? 'bg-blue-50 text-blue-600 translate-x-1'
                                               : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <path d="M14 2v6h6"/>
                                            </svg>
                                            Add Non-Print Resource
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @endif

                    <!-- Resources Accordion (Desktop) -->
                    <li class="hs-accordion rounded-lg transition-colors"
                        id="resource-accordion {{ request()->routeIs('print-resources', 'nonprint-resources') ? 'active' : '' }}">
                        <button type="button"
                                class="hs-accordion-toggle w-full flex justify-between py-2 px-2.5 text-sm rounded-lg
                                {{ request()->routeIs('print-resources', 'nonprint-resources')
                                    ? 'bg-blue-100 text-blue-600'
                                    : 'text-gray-800 hover:bg-gray-100' }}"
                                aria-controls="resource-accordion-collapse">
                            <span class="flex items-center gap-x-3.5">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                </svg>
                                Resources
                            </span>
                            <svg class="hs-accordion-active:rotate-180 size-4 transition-transform"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div id="resource-accordion-collapse"
                             class="hs-accordion-content overflow-hidden transition-[height] duration-300
                             {{ request()->routeIs('print-resources', 'nonprint-resources') ? '' : 'hidden' }}">
                            <ul class="pt-2 ps-8 space-y-1">
                                <li>
                                    <a href="{{ route('print-resources') }}"
                                       class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                       {{ request()->routeIs('print-resources')
                                           ? 'bg-blue-50 text-blue-600 translate-x-1'
                                           : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M6 9V2h12v7"/>
                                            <path d="M6 18h12v4H6z"/>
                                            <path d="M6 14h12"/>
                                        </svg>
                                        Print Resource
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('nonprint-resources') }}"
                                       class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                       {{ request()->routeIs('nonprint-resources')
                                           ? 'bg-blue-50 text-blue-600 translate-x-1'
                                           : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>
                                        Non-Print Resource
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Users -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;
                            $routeName = 'users';
                            $label = match ($level) {
                                1 => 'School Users',
                                2 => 'District Users',
                                3 => 'Division Users',
                                4 => 'Region Users',
                            };
                        @endphp
                        <li class="rounded-lg transition-colors">
                            <a href="{{ $routeName }}"
                               class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg
                                      {{ request()->routeIs('users') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/>
                                    <circle cx="17" cy="7" r="3"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                </svg>
                                {{ $label }}
                            </a>
                        </li>
                    @endif

                    <!-- Stations -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;
                            $routeName = 'stations';
                            $label = match ($level) {
                                2 => 'Manage School',
                                3 => 'Manage District and School',
                                4 => 'Manage Division',
                            };
                        @endphp
                        <li class="rounded-lg transition-colors">
                            <a href="{{ $routeName }}"
                               class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg
                                      {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 3l9 6-9 6-9-6z"/>
                                    <path d="M3 21h18"/>
                                </svg>
                                {{ $label }}
                            </a>
                        </li>
                    @endif

                </ul>
            </nav>

            <div class="p-8">
                <div class="flex items-center justify-center">
                    <img src="{{ Auth::user()?->station_logo_url ?? asset('assets/images/logo.png') }}" alt="Logo" class="h-20 w-auto">
                </div>
            </div>

            <!-- User Menu -->
            <footer class="mt-auto border-t border-gray-300 p-2 mb-5 relative">
                <div class="relative w-full">
                    <button id="accountToggle" type="button"
                            class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                        <img class="size-10 rounded-full"
                             src="{{ auth()->user()->photo ? asset('storage/' . auth()->user()->photo) : asset('assets/images/default.jpg') }}"
                             alt="User Avatar">
                        <span class="flex-1 text-left truncate">
                            {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}
                        </span>
                        <svg id="accountChevron" class="shrink-0 size-3.5 transition-transform"
                             xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="m7 15 5 5 5-5"/>
                            <path d="m7 9 5-5 5 5"/>
                        </svg>
                    </button>

                    <div id="accountMenu"
                         class="absolute bottom-full mb-2 w-full hidden opacity-0 transition-all duration-200 bg-white border rounded-lg shadow-lg z-50">
                        <div class="p-1 space-y-1">
                            <a href="{{ route('profile') }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('profile') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a6 6 0 0 1 12 0v2"/>
                                </svg>
                                My Account
                            </a>
                            @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 2, 3, 4]))
                                @php
                                    $level = Auth::user()->userType?->level;
                                    $routeName = match ($level) {
                                        1 => 'school-profile',
                                        2 => 'district-profile',
                                        3 => 'division-profile',
                                        4 => 'region-profile',
                                    };
                                    $label = match ($level) {
                                        1 => 'School Profile',
                                        2 => 'District Profile',
                                        3 => 'Division Profile',
                                        4 => 'Region Profile',
                                    };
                                @endphp
                                <a href="{{ route($routeName) }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 9l9-6 9 6v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        <path d="M9 22V12h6v10"/>
                                    </svg>
                                    {{ $label }}
                                </a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 text-left">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M17 16l4-4-4-4"/>
                                        <path d="M7 12h14"/>
                                        <path d="M7 4v16"/>
                                    </svg>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="p-6 flex-1 overflow-y-auto mt-16 md:mt-0">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="hidden md:block fixed bottom-6 right-6 z-40">
        <div id="fabMenu" class="relative">
            <div id="menuItems" class="absolute bottom-20 right-0 mb-4 space-y-3 opacity-0 scale-0 transition-all duration-300 origin-bottom-right pointer-events-none">
                <a href="#" class="block w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="fas fa-tachometer-alt text-custom-teal text-xl"></i>
                    <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-3 py-1 rounded opacity-0 transition-opacity whitespace-nowrap">Dashboard</span>
                </a>
                <a href="#" class="block w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="fas fa-user text-custom-teal text-xl"></i>
                    <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-3 py-1 rounded opacity-0 transition-opacity whitespace-nowrap">Profile</span>
                </a>
                <a href="#" class="block w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="fas fa-cog text-custom-teal text-xl"></i>
                    <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-3 py-1 rounded opacity-0 transition-opacity whitespace-nowrap">Settings</span>
                </a>
                <a href="#" class="block w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-100 transition">
                    <i class="fas fa-question-circle text-custom-teal text-xl"></i>
                    <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-3 py-1 rounded opacity-0 transition-opacity whitespace-nowrap">Support</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="relative">
                    @csrf
                    <button type="submit"
                        class="block w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-gray-100 transition group">
                        <i class="fas fa-sign-out-alt text-custom-teal text-xl"></i>
                        <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-800 text-white text-xs px-3 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                            Logout
                        </span>
                    </button>
                </form>
            </div>

            <button id="fabButton" class="w-14 h-14 bg-custom-yellow rounded-full shadow-xl flex items-center justify-center hover:bg-custom-yellow-hover transition duration-300">
                <i id="fabIcon" class="fas fa-bars text-gray-800 text-2xl"></i>
            </button>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
