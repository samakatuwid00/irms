<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LRMIS')</title>

    <!-- CSRF Token for forms -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


    <!-- Alphine JS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Custom Styles -->
    <style>
        .bg-custom-yellow { background-color: #F3C623; }
        .hover\:bg-custom-yellow-hover:hover { background-color: #e6b81e; }
        .text-custom-teal { color: #127681; }
        .border-custom-teal { border-color: #127681; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 overflow-x-hidden">
    <div class="flex h-screen">

        <!-- Sidebar -->
        <div id="sidebar"
             class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg
                    transform -translate-x-full md:translate-x-0
                    md:static md:inset-0
                    transition-transform duration-300 ease-in-out
                    flex flex-col">

            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-xl font-semibold">Navigation</h2>
                <button id="close-sidebar" class="md:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <nav class="p-4 flex-1">
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

                    <!-- Add Resource -->
                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 3]))
                        <li class="rounded-lg transition-colors">
                            <a href="{{ route('add-resources') }}"
                            class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg
                                    {{ request()->routeIs('add-resources') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M4 19a2 2 0 0 0 2 2h12"/>
                                    <path d="M4 5a2 2 0 0 1 2-2h12v14H6a2 2 0 0 0-2 2z"/>
                                    <path d="M12 7v6"/>
                                    <path d="M9 10h6"/>
                                </svg>
                                Add Resource
                            </a>
                        </li>
                    @endif

                    <!-- Resources Accordion -->
                    <li class="hs-accordion rounded-lg transition-colors"
                        id="resource-accordion
                        {{ request()->routeIs('print-resources', 'nonprint-resources') ? 'active' : '' }}">

                        <button type="button"
                                class="hs-accordion-toggle w-full flex justify-between py-2 px-2.5 text-sm rounded-lg
                                {{ request()->routeIs('print-resources', 'nonprint-resources')
                                    ? 'bg-blue-100 text-blue-600'
                                    : 'text-gray-800 hover:bg-gray-100' }}"
                                aria-controls="resource-accordion-collapse">

                            <span class="flex items-center gap-x-3.5">
                                <!-- Folder Icon -->
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                </svg>
                                Resources
                            </span>

                            <svg class="hs-accordion-active:rotate-180 size-4 transition-transform"
                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div id="resource-accordion-collapse"
                            class="hs-accordion-content overflow-hidden transition-[height] duration-300
                            {{ request()->routeIs('print-resources', 'nonprint-resources') ? '' : 'hidden' }}">

                            <ul class="pt-2 ps-8 space-y-1">

                                <!-- Print Resource -->
                                <li>
                                    <a href="{{ route('print-resources') }}"
                                    class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                    {{ request()->routeIs('print-resources')
                                        ? 'bg-blue-50 text-blue-600 translate-x-1'
                                        : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">

                                        <!-- Printer Icon -->
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M6 9V2h12v7"/>
                                            <path d="M6 18h12v4H6z"/>
                                            <path d="M6 14h12"/>
                                        </svg>

                                        Print Resource
                                    </a>
                                </li>

                                <!-- Non-Print Resource -->
                                <li>
                                    <a href="{{ route('nonprint-resources') }}"
                                    class="flex items-center gap-2 py-2 px-3 text-sm rounded-lg transition-all duration-200
                                    {{ request()->routeIs('nonprint-resources')
                                        ? 'bg-blue-50 text-blue-600 translate-x-1'
                                        : 'hover:bg-blue-50 hover:text-blue-600 hover:translate-x-1' }}">

                                        <!-- File Icon -->
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>

                                        Non-Print Resource
                                    </a>
                                </li>

                            </ul>
                        </div>
                    </li>

                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [1, 2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;

                            $routeName = match ($level) {
                                1 => 'users',
                                2 => 'users',
                                3 => 'users',
                                4 => 'users',
                            };

                            $label = match ($level) {
                                1 => 'School Users',
                                2 => 'District Users',
                                3 => 'Division Users',
                                4 => 'Region Users',
                            };
                        @endphp

                        <!-- Users -->
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

                    @if (Auth::check() && in_array(Auth::user()?->userType?->level, [2, 3, 4]))
                        @php
                            $level = Auth::user()->userType?->level;

                            $routeName = match ($level) {
                                2 => 'stations',
                                3 => 'stations',
                                4 => 'stations',
                            };

                            $label = match ($level) {
                                2 => 'Manage School',
                                3 => 'Manage District and School',
                                4 => 'Manage Division',
                            };
                        @endphp

                        {{-- Stations --}}
                        <li class="rounded-lg transition-colors">
                            <a href="{{ $routeName }}"
                            class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg
                                    {{ request()->routeIs($routeName) ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 3l9 6-9 6-9-6z"/>
                                    <path d="M3 21h18"/>
                                </svg>
                                {{ $label }}
                            </a>
                        </li>
                    @endif
                    <li class="rounded-lg transition-colors">
                        <a href="{{ route('generate-report') }}"
                        class="flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg
                                {{ request()->routeIs('generate-report') ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' }}">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9 17v-6"/>
                                <path d="M13 17V7"/>
                                <path d="M17 17v-4"/>
                                <path d="M3 3h18v18H3z"/>
                            </svg>
                            Generate Report
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Menu -->
            <footer class="mt-auto border-t p-2 mb-5 relative">
                <div class="relative w-full">
                    <button id="accountToggle" type="button"
                            class="w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm text-gray-800 rounded-lg hover:bg-gray-100 transition-colors">
                        <img class="size-6 rounded-full"
                             src="{{ Auth::user()->avatar ?? 'https://images.unsplash.com/photo-1734122415415-88cb1d7d5dc0?q=80&w=320&h=320&auto=format&fit=facearea&facepad=3' }}"
                             alt="User Avatar">
                        <span class="flex-1 text-left truncate">
                            {{ Auth::user()->firstname}} {{ Auth::user()->lastname}}
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
        <div class="flex-1 flex flex-col overflow-x-hidden">
            <!-- Mobile Header -->
            <header class="md:hidden bg-white shadow p-4 flex justify-between items-center">
                <button id="open-sidebar">☰</button>
                <h2 class="text-lg font-semibold">@yield('page-title', 'Dashboard')</h2>
            </header>

            <!-- Main Content -->
            <main class="p-6 flex-1 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
    <!-- Floating Action Button with Quick Access Menu -->
    <div class="fixed bottom-6 right-6 z-50">
        <div id="fabMenu" class="relative">
            <!-- Menu Items (hidden initially) -->
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
                        class="block w-12 h-12 bg-white rounded-full shadow-lg
                            flex items-center justify-center
                            hover:bg-gray-100 transition group">

                        <i class="fas fa-sign-out-alt text-custom-teal text-xl"></i>

                        <!-- Tooltip -->
                        <span
                            class="absolute right-16 top-1/2 -translate-y-1/2
                                bg-gray-800 text-white text-xs px-3 py-1 rounded
                                opacity-0 group-hover:opacity-100
                                transition-opacity whitespace-nowrap">
                            Logout
                        </span>
                    </button>
                </form>
            </div>

            <!-- Main FAB Button -->
            <button id="fabButton" class="w-14 h-14 bg-custom-yellow rounded-full shadow-xl flex items-center justify-center hover:bg-custom-yellow-hover transition duration-300">
                <i id="fabIcon" class="fas fa-bars text-gray-800 text-2xl"></i>
            </button>
        </div>
    </div>
    <script>
        const fabButton = document.getElementById('fabButton');
        const menuItems = document.getElementById('menuItems');
        const fabIcon = document.getElementById('fabIcon');

        fabButton.addEventListener('click', () => {
            const isOpen = menuItems.classList.contains('opacity-100');

            if (isOpen) {
                menuItems.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
                menuItems.classList.add('opacity-0', 'scale-0', 'pointer-events-none');
                fabIcon.classList.remove('fa-times');
                fabIcon.classList.add('fa-bars');
            } else {
                menuItems.classList.remove('opacity-0', 'scale-0', 'pointer-events-none');
                menuItems.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
                fabIcon.classList.remove('fa-bars');
                fabIcon.classList.add('fa-times');
            }
        });

        // Optional: Tooltip show on hover (for desktop)
        document.querySelectorAll('#menuItems a').forEach(link => {
            link.addEventListener('mouseenter', () => {
                link.querySelector('span').classList.remove('opacity-0');
                link.querySelector('span').classList.add('opacity-100');
            });
            link.addEventListener('mouseleave', () => {
                link.querySelector('span').classList.remove('opacity-100');
                link.querySelector('span').classList.add('opacity-0');
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /* ================================
            SIDEBAR TOGGLE (MOBILE)
            ================================= */
            const openSidebar = document.getElementById('open-sidebar');
            const closeSidebar = document.getElementById('close-sidebar');
            const sidebar = document.getElementById('sidebar');

            openSidebar?.addEventListener('click', () => {
                sidebar.classList.remove('-translate-x-full');
            });

            closeSidebar?.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
            });

            /* ================================
            PRELINE INIT
            ================================= */
            if (window.preline) {
                preline.autoInit();
            }

            /* ================================
            FOOTER ACCOUNT DROPDOWN (FIXED)
            ================================= */
            const toggle = document.getElementById('accountToggle');
            const menu = document.getElementById('accountMenu');
            const chevron = document.getElementById('accountChevron');

            if (toggle && menu) {

                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();

                    const isOpen = !menu.classList.contains('hidden');

                    menu.classList.add('hidden', 'opacity-0');
                    chevron.classList.remove('rotate-180');

                    if (!isOpen) {
                        menu.classList.remove('hidden');
                        requestAnimationFrame(() => {
                            menu.classList.remove('opacity-0');
                        });
                        chevron.classList.add('rotate-180');
                    }
                });

                menu.addEventListener('click', (e) => {
                    e.stopPropagation();
                });

                document.addEventListener('click', () => {
                    menu.classList.add('hidden', 'opacity-0');
                    chevron.classList.remove('rotate-180');
                });
            }

            /* ================================
            KEEP RESOURCES ACCORDION OPEN
            ================================= */
            @if (request()->routeIs('resources.*'))
                const resourceAccordion = document.getElementById('resource-accordion-collapse');
                if (resourceAccordion) {
                    resourceAccordion.classList.remove('hidden');
                }
            @endif

        });
    </script>


    @stack('scripts')
</body>
</html>
