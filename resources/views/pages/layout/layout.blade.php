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
    @vite(['resources/js/charts/bosy.js'])
    @vite(['resources/css/bosy/bosy.css'])

    <!-- htmx -->
    <script src="https://unpkg.com/htmx.org@1.9.12"></script>

    <!-- CSRF Token for forms -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS & App JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Alpine JS (collapse plugin must load before core) -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
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
                                        Add Print
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
                                        Add Non-Print
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
                                    Print
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
                                    Non-Print
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

                    <!-- Masterlist Submenu (Mobile) -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [3, 4]))
                        <li>
                            <button type="button" id="mobile-masterlist-toggle"
                                    class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-lg transition-colors
                                        {{ request()->routeIs('masterlist.*', 'nonprint-masterlist.*') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                <span class="flex items-center gap-3">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    Masterlist
                                </span>
                                <svg id="mobile-masterlist-chevron"
                                    class="w-4 h-4 transition-transform {{ request()->routeIs('masterlist.*', 'nonprint-masterlist.*') ? 'rotate-180' : '' }}"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            <ul id="mobile-masterlist-submenu"
                                class="mt-1 ml-8 space-y-1 {{ request()->routeIs('masterlist.*', 'nonprint-masterlist.*') ? '' : 'hidden' }}">
                                <li>
                                    <a href="{{ route('masterlist.index') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                            {{ request()->routeIs('masterlist.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M6 9V2h12v7"/>
                                            <path d="M6 18h12v4H6z"/>
                                            <path d="M6 14h12"/>
                                        </svg>
                                        Print
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('nonprint-masterlist.index') }}"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                                            {{ request()->routeIs('nonprint-masterlist.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>
                                        Non-Print
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

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

    <div class="flex h-screen"
         x-data="{
             collapsed: JSON.parse(localStorage.getItem('sidebar_collapsed') || 'false'),
             accountOpen: false,
             toggleSidebar() {
                 this.collapsed = !this.collapsed;
                 localStorage.setItem('sidebar_collapsed', JSON.stringify(this.collapsed));
             }
         }">
        <!-- Desktop Sidebar -->
        <div :class="collapsed ? 'md:w-20' : 'md:w-64'"
             class="hidden md:flex md:flex-col bg-white shadow-lg shrink-0 transition-all duration-300 ease-in-out">
            <div class="flex items-center border-b border-gray-300 shrink-0 p-4"
                 :class="collapsed ? 'flex-col gap-3 py-4 px-2' : 'justify-between'">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="iRIMS-V Logo"
                         class="w-10 h-10 object-contain shrink-0">
                    <h2 x-show="!collapsed" x-transition.opacity.duration.200ms
                        class="text-xl font-bold tracking-wide whitespace-nowrap">
                        <span class="text-[#0AC4E0]">i</span><span class="text-[#1A3263]">RIMS-</span><span class="text-[#DA3D20]">V</span>
                    </h2>
                </div>
                <button @click="toggleSidebar()" aria-label="Toggle sidebar"
                        class="flex items-center justify-center w-8 h-8 rounded-lg
                               hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg :class="collapsed ? 'rotate-180' : ''"
                         class="w-5 h-5 transition-transform duration-300"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <nav class="p-3 flex-1 overflow-y-auto overflow-x-hidden">
                <ul class="space-y-1">

                    <!-- Dashboard -->
                    <li class="relative group rounded-lg">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-x-3.5 py-2 text-sm text-gray-800 rounded-lg transition-all duration-200
                                  {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100' }}"
                           :class="collapsed ? 'justify-center px-2' : 'px-2.5'">
                            <svg class="size-5 shrink-0 transition-transform duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-6v-7H10v7H4a1 1 0 0 1-1-1z"/>
                            </svg>
                            <span x-show="!collapsed" x-transition.opacity.duration.200ms class="whitespace-nowrap">Dashboard</span>
                        </a>
                        <template x-if="collapsed">
                            <div class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-900 text-white text-xs rounded-lg shadow-lg whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150 z-[60]">
                                Dashboard
                                <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                            </div>
                        </template>
                    </li>

                    <!-- Add Resource Accordion (Desktop) -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 3]))
                        <li class="relative group" x-data="{ open: {{ request()->routeIs('print-resource.create', 'nonprint-resource.create') ? 'true' : 'false' }} }">
                            <button @click="if(!collapsed) open = !open"
                                    class="w-full flex items-center gap-x-3.5 py-2 text-sm rounded-lg transition-all duration-200
                                    {{ request()->routeIs('print-resource.create', 'nonprint-resource.create') ? 'bg-blue-100 text-blue-600 font-semibold' : 'text-gray-800 hover:bg-gray-100' }}"
                                    :class="collapsed ? 'justify-center px-2' : 'px-2.5'"
                                    aria-label="Add Resource">
                                <svg class="size-5 shrink-0 transition-transform duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M4 19a2 2 0 0 0 2 2h12"/><path d="M4 5a2 2 0 0 1 2-2h12v14H6a2 2 0 0 0-2 2z"/>
                                    <path d="M12 7v6"/><path d="M9 10h6"/>
                                </svg>
                                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="flex-1 text-left whitespace-nowrap">Add Resource</span>
                                <svg x-show="!collapsed" :class="open ? 'rotate-180' : ''" class="size-4 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div x-show="open && !collapsed" x-collapse x-cloak class="pt-2 ps-8 space-y-1">
                                <a href="{{ route('print-resource.create') }}"
                                   class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                   {{ request()->routeIs('print-resource.create') ? 'bg-blue-50 text-blue-600 translate-x-1' : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/>
                                    </svg>
                                    Add Print
                                </a>
                                <a href="{{ route('nonprint-resource.create') }}"
                                   class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                   {{ request()->routeIs('nonprint-resource.create') ? 'bg-blue-50 text-blue-600 translate-x-1' : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                                    </svg>
                                    Add Non-Print
                                </a>
                            </div>
                            <template x-if="collapsed">
                                <div class="absolute left-full top-0 ml-2 py-2 bg-white rounded-lg shadow-xl border border-gray-200 min-w-[180px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-[60]">
                                    <div class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Add Resource</div>
                                    <a href="{{ route('print-resource.create') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/></svg>
                                        Add Print
                                    </a>
                                    <a href="{{ route('nonprint-resource.create') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                        Add Non-Print
                                    </a>
                                </div>
                            </template>
                        </li>
                    @endif

                    <!-- Resources Accordion (Desktop) -->
                    <li class="relative group rounded-lg transition-colors"
                        x-data="{ open: {{ request()->routeIs('print-resources', 'nonprint-resources') ? 'true' : 'false' }} }">
                        <button type="button"
                                @click="if(!collapsed) open = !open"
                                class="w-full flex items-center gap-x-3.5 py-2 text-sm rounded-lg transition-all duration-200
                                {{ request()->routeIs('print-resources', 'nonprint-resources')
                                    ? 'bg-blue-100 text-blue-600 font-semibold'
                                    : 'text-gray-800 hover:bg-gray-100' }}"
                                :class="collapsed ? 'justify-center px-2' : 'px-2.5'"
                                aria-label="Resources">
                            <svg class="size-5 shrink-0 transition-transform duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                            <span x-show="!collapsed" x-transition.opacity.duration.200ms class="flex-1 text-left whitespace-nowrap">Resources</span>
                            <svg x-show="!collapsed" :class="open ? 'rotate-180' : ''" class="size-4 shrink-0 transition-transform"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div x-show="open && !collapsed" x-collapse x-cloak class="pt-2 ps-8 space-y-1">
                            <a href="{{ route('print-resources') }}"
                               class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                               {{ request()->routeIs('print-resources')
                                   ? 'bg-blue-50 text-blue-600 translate-x-1'
                                   : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/>
                                </svg>
                                Print
                            </a>
                            <a href="{{ route('nonprint-resources') }}"
                               class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                               {{ request()->routeIs('nonprint-resources')
                                   ? 'bg-blue-50 text-blue-600 translate-x-1'
                                   : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                                </svg>
                                Non-Print
                            </a>
                        </div>
                        <template x-if="collapsed">
                            <div class="absolute left-full top-0 ml-2 py-2 bg-white rounded-lg shadow-xl border border-gray-200 min-w-[180px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-[60]">
                                <div class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Resources</div>
                                <a href="{{ route('print-resources') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/></svg>
                                    Print
                                </a>
                                <a href="{{ route('nonprint-resources') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    Non-Print
                                </a>
                            </div>
                        </template>
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
                        <li class="relative group rounded-lg transition-colors">
                            <a href="{{ $routeName }}"
                               class="flex items-center gap-x-3.5 py-2 text-sm text-gray-800 rounded-lg transition-all duration-200
                                      {{ request()->routeIs('users') ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100' }}"
                               :class="collapsed ? 'justify-center px-2' : 'px-2.5'">
                                <svg class="size-5 shrink-0 transition-transform duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/>
                                    <circle cx="17" cy="7" r="3"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                </svg>
                                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="whitespace-nowrap">{{ $label }}</span>
                            </a>
                            <template x-if="collapsed">
                                <div class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-900 text-white text-xs rounded-lg shadow-lg whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150 z-[60]">
                                    {{ $label }}
                                    <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                                </div>
                            </template>
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
                        <li class="relative group rounded-lg transition-colors">
                            <a href="{{ $routeName }}"
                               class="flex items-center gap-x-3.5 py-2 text-sm text-gray-800 rounded-lg transition-all duration-200
                                      {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600 font-semibold' : 'hover:bg-gray-100' }}"
                               :class="collapsed ? 'justify-center px-2' : 'px-2.5'">
                                <svg class="size-5 shrink-0 transition-transform duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 3l9 6-9 6-9-6z"/>
                                    <path d="M3 21h18"/>
                                </svg>
                                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="whitespace-nowrap">{{ $label }}</span>
                            </a>
                            <template x-if="collapsed">
                                <div class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-gray-900 text-white text-xs rounded-lg shadow-lg whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150 z-[60]">
                                    {{ $label }}
                                    <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900"></div>
                                </div>
                            </template>
                        </li>
                    @endif

                    <!-- Divider Line -->
                    @if (Auth::check() && 
                         in_array(Auth::user()?->userType?->level, [2, 3, 4]) && 
                         in_array(Auth::user()?->userType?->level, [3, 4]))
                        <li class="px-2.5 py-2">
                            <div class="h-px bg-gray-200"></div>
                        </li>
                    @endif

                    <!-- Masterlist Accordion (Desktop) -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [3, 4]))
                        <li class="relative group rounded-lg transition-colors"
                            x-data="{ open: {{ request()->routeIs('masterlist.*', 'nonprint-masterlist.*') ? 'true' : 'false' }} }">
                            <button type="button"
                                    @click="if(!collapsed) open = !open"
                                    class="w-full flex items-center gap-x-3.5 py-2 text-sm rounded-lg transition-all duration-200
                                    {{ request()->routeIs('masterlist.*', 'nonprint-masterlist.*')
                                        ? 'bg-blue-100 text-blue-600 font-semibold'
                                        : 'text-gray-800 hover:bg-gray-100' }}"
                                    :class="collapsed ? 'justify-center px-2' : 'px-2.5'"
                                    aria-label="Masterlist">
                                <svg class="size-5 shrink-0 transition-transform duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                <span x-show="!collapsed" x-transition.opacity.duration.200ms class="flex-1 text-left whitespace-nowrap">Masterlist</span>
                                <svg x-show="!collapsed" :class="open ? 'rotate-180' : ''" class="size-4 shrink-0 transition-transform"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            <div x-show="open && !collapsed" x-collapse x-cloak class="pt-2 ps-8 space-y-1">
                                <a href="{{ route('masterlist.index') }}"
                                class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                {{ request()->routeIs('masterlist.*')
                                    ? 'bg-blue-50 text-blue-600 translate-x-1'
                                    : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/>
                                    </svg>
                                    Print
                                </a>
                                <a href="{{ route('nonprint-masterlist.index') }}"
                                class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                {{ request()->routeIs('nonprint-masterlist.*')
                                    ? 'bg-blue-50 text-blue-600 translate-x-1'
                                    : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                                    </svg>
                                    Non-Print
                                </a>
                            </div>
                            <template x-if="collapsed">
                                <div class="absolute left-full top-0 ml-2 py-2 bg-white rounded-lg shadow-xl border border-gray-200 min-w-[180px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-[60]">
                                    <div class="px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wide">Masterlist</div>
                                    <a href="{{ route('masterlist.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/></svg>
                                        Print
                                    </a>
                                    <a href="{{ route('nonprint-masterlist.index') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                        Non-Print
                                    </a>
                                </div>
                            </template>
                        </li>
                    @endif
                </ul>
            </nav>

            <div x-show="!collapsed" x-transition.opacity.duration.200ms class="p-8">
                <div class="flex items-center justify-center">
                    <img src="{{ Auth::user()?->station_logo_url ?? asset('assets/images/logo.png') }}" alt="Logo" class="h-20 w-auto">
                </div>
            </div>
            <div x-show="collapsed" x-transition.opacity.duration.200ms class="py-4 flex items-center justify-center">
                <img src="{{ Auth::user()?->station_logo_url ?? asset('assets/images/logo.png') }}" alt="Logo" class="h-8 w-8 object-contain">
            </div>

            <!-- User Menu -->
            <footer class="mt-auto border-t border-gray-300 p-2 mb-5 relative"
                    x-data="{ accountOpen: false }" @click.outside="accountOpen = false">

                {{-- ── COLLAPSED: avatar-only button + icon-only flyout ── --}}
                <div x-show="collapsed" class="flex justify-center">
                    <div class="relative">
                        <button type="button"
                                @click="accountOpen = !accountOpen"
                                class="flex items-center justify-center w-10 h-10 rounded-full hover:ring-2 hover:ring-blue-400 transition">
                            <img class="size-10 rounded-full border-2 border-gray-200"
                                 src="{{ auth()->user()->photo ? asset('storage/' . auth()->user()->photo) : asset('assets/images/default.jpg') }}"
                                 alt="User Avatar">
                        </button>

                        {{-- Icon-only dropdown, opens upward to the right --}}
                        <div x-show="accountOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             x-cloak
                             class="absolute bottom-full left-full mb-2 ml-2 bg-white border border-gray-200 rounded-lg shadow-xl z-50 py-1 min-w-[44px]">
                            {{-- My Account --}}
                            <a href="{{ route('profile') }}"
                               title="My Account"
                               class="flex items-center justify-center w-11 h-11 rounded-lg text-sm {{ request()->routeIs('profile') ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a6 6 0 0 1 12 0v2"/>
                                </svg>
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
                                   title="{{ $label }}"
                                   class="flex items-center justify-center w-11 h-11 rounded-lg text-sm {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600' : 'text-gray-700 hover:bg-gray-100' }}">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 9l9-6 9 6v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        <path d="M9 22V12h6v10"/>
                                    </svg>
                                </a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        title="Sign out"
                                        class="flex items-center justify-center w-11 h-11 rounded-lg text-red-600 hover:bg-red-50">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M17 16l4-4-4-4"/>
                                        <path d="M7 12h14"/>
                                        <path d="M7 4v16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── EXPANDED: full name button + full dropdown ── --}}
                <div x-show="!collapsed" class="relative w-full">
                    <button type="button"
                            @click="accountOpen = !accountOpen"
                            class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                        <img class="size-10 rounded-full"
                             src="{{ auth()->user()->photo ? asset('storage/' . auth()->user()->photo) : asset('assets/images/default.jpg') }}"
                             alt="User Avatar">
                        <span class="flex-1 text-left truncate">
                            {{ Auth::user()->firstname }} {{ Auth::user()->lastname }}
                        </span>
                        <svg :class="accountOpen ? 'rotate-180' : ''"
                             class="shrink-0 size-3.5 transition-transform"
                             xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="m7 15 5 5 5-5"/>
                            <path d="m7 9 5-5 5 5"/>
                        </svg>
                    </button>

                    <div x-show="accountOpen"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         x-cloak
                         class="absolute bottom-full mb-2 w-full bg-white border rounded-lg shadow-lg z-50">
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
                <main class="p-6 overflow-y-auto mt-16 md:mt-0 flex-1">
                    @yield('content')
                    <!-- Copyright Footer - sits below content, scrolls with page -->
                    <p class="w-full text-center pt-6 pb-3 text-xs text-gray-400 select-none border-t border-gray-200 mt-8">
                        Designed &amp; Developed by<br>
                        <span class="font-semibold text-gray-500">LRMS Naga</span> &copy; Copyright 2022
                    </p>
                </main>
            </div>
    </div>

    @stack('scripts')
</body>
</html>
