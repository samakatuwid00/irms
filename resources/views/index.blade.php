@extends('public-layout.layout')

@section('title', 'Login')

@section('content')
    <style>
        .left-panel {
            position: relative;
            overflow: hidden;
        }

        @keyframes float1 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-18px) rotate(3deg); }
        }
        @keyframes float2 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(12px) rotate(-4deg); }
        }
        @keyframes float3 {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            33% { transform: translateY(-10px) translateX(8px); }
            66% { transform: translateY(8px) translateX(-5px); }
        }
        .float-a { animation: float1 5s ease-in-out infinite; }
        .float-b { animation: float2 6s ease-in-out infinite 0.8s; }
        .float-c { animation: float3 7s ease-in-out infinite 1.5s; }
        .float-d { animation: float1 8s ease-in-out infinite 2s; }

        @keyframes pulse-scale {
            0%, 100% { transform: scale(1); opacity: 0.15; }
            50% { transform: scale(1.08); opacity: 0.25; }
        }

        .left-panel.theme-inventory {
            background: linear-gradient(
                135deg,
                #fbc6a4 0%,
                #f5ae85 40%,
                #e88357 100%
            );
        }

        .bg-blob-tr {
            position: absolute;
            width: 340px; height: 340px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            top: -100px; right: -100px;
            animation: pulse-scale 5s ease-in-out infinite;
        }
        .bg-blob-bl {
            position: absolute;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(0,0,0,0.08);
            bottom: -60px; left: -60px;
        }

        @media (prefers-reduced-motion: reduce) {
            .float-a, .float-b, .float-c, .float-d, .bg-blob-tr {
                animation: none;
            }
        }

        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .bounce-slow { animation: bounce-slow 2s ease-in-out infinite; }

        @keyframes progress-bar {
            0% { width: 0%; }
            60% { width: 75%; }
            100% { width: 75%; }
        }
        .progress-animate { animation: progress-bar 1.8s ease-out forwards; }
    </style>

    @php
        $libraryUrl    = 'http://irimsv-library.net';
    @endphp

    <div class="flex flex-col lg:flex-row w-full max-w-6xl mx-auto overflow-hidden rounded-none sm:rounded-2xl shadow-none sm:shadow-4xl min-h-screen lg:min-h-[600px]">

        {{-- Left Section --}}
        <div class="left-panel theme-inventory lg:flex lg:w-1/2 relative
                    py-8 px-4 sm:py-12 sm:px-8 lg:rounded-l-2xl">

            <div class="bg-blob-tr"></div>
            <div class="bg-blob-bl"></div>

            {{-- Bar Chart --}}
            <svg class="absolute float-a" style="top:4%; right:4%; width:150px; height:120px; opacity:.9;" viewBox="0 0 130 100">
                <line x1="10" y1="10" x2="10" y2="80" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                <line x1="10" y1="80" x2="125" y2="80" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                <line x1="10" y1="60" x2="125" y2="60" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3 3"/>
                <line x1="10" y1="40" x2="125" y2="40" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3 3"/>
                <line x1="10" y1="20" x2="125" y2="20" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3 3"/>
                <rect x="18" y="80" width="14" height="0" rx="2" fill="rgba(255,255,255,0.5)">
                    <animate attributeName="y" from="80" to="45" dur="1.2s" fill="freeze" begin="0s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    <animate attributeName="height" from="0" to="35" dur="1.2s" fill="freeze" begin="0s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </rect>
                <rect x="38" y="80" width="14" height="0" rx="2" fill="rgba(255,255,255,0.7)">
                    <animate attributeName="y" from="80" to="25" dur="1.2s" fill="freeze" begin="0.15s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    <animate attributeName="height" from="0" to="55" dur="1.2s" fill="freeze" begin="0.15s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </rect>
                <rect x="58" y="80" width="14" height="0" rx="2" fill="rgba(255,255,255,0.5)">
                    <animate attributeName="y" from="80" to="52" dur="1.2s" fill="freeze" begin="0.3s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    <animate attributeName="height" from="0" to="28" dur="1.2s" fill="freeze" begin="0.3s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </rect>
                <rect x="78" y="80" width="14" height="0" rx="2" fill="rgba(251,191,36,0.85)">
                    <animate attributeName="y" from="80" to="18" dur="1.2s" fill="freeze" begin="0.45s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    <animate attributeName="height" from="0" to="62" dur="1.2s" fill="freeze" begin="0.45s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </rect>
                <rect x="98" y="80" width="14" height="0" rx="2" fill="rgba(255,255,255,0.6)">
                    <animate attributeName="y" from="80" to="35" dur="1.2s" fill="freeze" begin="0.6s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    <animate attributeName="height" from="0" to="45" dur="1.2s" fill="freeze" begin="0.6s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </rect>
                <circle cx="25" cy="45" r="3" fill="white" opacity="0">
                    <animate attributeName="opacity" from="0" to="0.8" dur="0.3s" fill="freeze" begin="1.2s"/>
                </circle>
                <circle cx="45" cy="25" r="3" fill="white" opacity="0">
                    <animate attributeName="opacity" from="0" to="0.8" dur="0.3s" fill="freeze" begin="1.35s"/>
                </circle>
                <circle cx="65" cy="52" r="3" fill="white" opacity="0">
                    <animate attributeName="opacity" from="0" to="0.8" dur="0.3s" fill="freeze" begin="1.5s"/>
                </circle>
                <circle cx="85" cy="18" r="3" fill="rgba(251,191,36,1)" opacity="0">
                    <animate attributeName="opacity" from="0" to="1" dur="0.3s" fill="freeze" begin="1.65s"/>
                </circle>
                <circle cx="105" cy="35" r="3" fill="white" opacity="0">
                    <animate attributeName="opacity" from="0" to="0.8" dur="0.3s" fill="freeze" begin="1.8s"/>
                </circle>
            </svg>

            {{-- Line Graph --}}
            <svg class="absolute float-b" style="top:32%; left:2%; width:145px; height:105px; opacity:.8;" viewBox="0 0 130 90">
                <line x1="8" y1="8" x2="8" y2="72" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                <line x1="8" y1="72" x2="125" y2="72" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                <line x1="8" y1="55" x2="125" y2="55" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 3"/>
                <line x1="8" y1="38" x2="125" y2="38" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 3"/>
                <line x1="8" y1="22" x2="125" y2="22" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 3"/>
                <path d="M8,60 L28,48 L48,52 L68,28 L88,35 L108,18 L125,22 L125,72 L8,72 Z" fill="rgba(255,255,255,0.06)"/>
                <polyline points="8,60 28,48 48,52 68,28 88,35 108,18 125,22"
                          fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                          stroke-dasharray="300" stroke-dashoffset="300">
                    <animate attributeName="stroke-dashoffset" from="300" to="0" dur="1.8s" fill="freeze" begin="0.2s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </polyline>
                <circle cx="8"   cy="60" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="0.5s"/></circle>
                <circle cx="28"  cy="48" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="0.8s"/></circle>
                <circle cx="48"  cy="52" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.0s"/></circle>
                <circle cx="68"  cy="28" r="3.5" fill="rgba(251,191,36,1)" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.2s"/></circle>
                <circle cx="88"  cy="35" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.4s"/></circle>
                <circle cx="108" cy="18" r="3.5" fill="rgba(251,191,36,1)" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.6s"/></circle>
                <circle cx="125" cy="22" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.8s"/></circle>
                <circle cx="125" cy="22" r="7" fill="none" stroke="rgba(251,191,36,0.6)" stroke-width="1.5" opacity="0">
                    <animate attributeName="opacity" from="0" to="1" dur="0.3s" fill="freeze" begin="2s"/>
                    <animate attributeName="r" values="5;9;5" dur="2s" repeatCount="indefinite" begin="2s"/>
                    <animate attributeName="opacity" values="1;0.3;1" dur="2s" repeatCount="indefinite" begin="2s"/>
                </circle>
                <text x="8" y="85" fill="rgba(255,255,255,0.5)" font-size="7" font-family="monospace">Stock Trend</text>
            </svg>

            {{-- Donut Ring --}}
            <svg class="absolute float-c" style="bottom:8%; right:5%; width:110px; height:110px; opacity:.8;" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="38" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="10"/>
                <circle cx="50" cy="50" r="38" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="10"
                        stroke-linecap="round" stroke-dasharray="238" stroke-dashoffset="238"
                        transform="rotate(-90 50 50)">
                    <animate attributeName="stroke-dashoffset" from="238" to="52" dur="1.6s" fill="freeze" begin="0.3s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </circle>
                <circle cx="50" cy="50" r="28" fill="none" stroke="rgba(251,191,36,0.3)" stroke-width="5"
                        stroke-dasharray="176" stroke-dashoffset="176" transform="rotate(-90 50 50)">
                    <animate attributeName="stroke-dashoffset" from="176" to="44" dur="1.8s" fill="freeze" begin="0.6s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </circle>
                <text x="50" y="46" text-anchor="middle" fill="rgba(255,255,255,0.9)" font-size="14" font-weight="bold" font-family="monospace">78%</text>
                <text x="50" y="58" text-anchor="middle" fill="rgba(255,255,255,0.45)" font-size="7" font-family="monospace">In Stock</text>
            </svg>

            {{-- Stat Card 1 --}}
            <svg class="absolute float-d" style="top:5%; left:5%; width:105px; height:65px; opacity:1;" viewBox="0 0 95 55">
                <rect x="0" y="0" width="95" height="55" rx="6" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.25)" stroke-width="1"/>
                <polyline points="8,38 18,28 28,32 38,18 48,22 58,14 68,20 78,12 88,16"
                          fill="none" stroke="rgba(251,191,36,0.8)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                          stroke-dasharray="150" stroke-dashoffset="150">
                    <animate attributeName="stroke-dashoffset" from="150" to="0" dur="1.4s" fill="freeze" begin="0.5s"/>
                </polyline>
                <text x="8" y="12" fill="rgba(255,255,255,0.5)" font-size="6" font-family="monospace">TOTAL ITEMS</text>
                <text x="8" y="22" fill="rgba(255,255,255,0.9)" font-size="10" font-weight="bold" font-family="monospace">4,821</text>
                <polygon points="62,20 67,14 72,20" fill="rgba(134,239,172,0.8)"/>
                <text x="74" y="21" fill="rgba(134,239,172,0.8)" font-size="6" font-family="monospace">+12%</text>
            </svg>

            {{-- Stat Card 2 --}}
            <svg class="absolute float-a" style="bottom:6%; left:4%; width:105px; height:55px; opacity:1;" viewBox="0 0 95 48">
                <rect x="0" y="0" width="95" height="48" rx="6" fill="rgba(255,255,255,0.10)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                <rect x="8" y="30" width="79" height="5" rx="2" fill="rgba(255,255,255,0.15)"/>
                <rect x="8" y="30" width="0" height="5" rx="2" fill="rgba(251,191,36,0.85)">
                    <animate attributeName="width" from="0" to="55" dur="1.4s" fill="freeze" begin="0.8s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                </rect>
                <text x="8" y="12" fill="rgba(255,255,255,0.45)" font-size="6" font-family="monospace">LOW STOCK</text>
                <text x="8" y="22" fill="rgba(255,255,255,0.9)" font-size="10" font-weight="bold" font-family="monospace">127</text>
                <text x="69" y="38" fill="rgba(255,255,255,0.5)" font-size="6" font-family="monospace">69%</text>
            </svg>

            {{-- Grid background --}}
            <svg class="absolute top-0 left-0 w-full h-full" style="opacity:0.2;" viewBox="0 0 400 600" preserveAspectRatio="none">
                <defs>
                    <pattern id="inv-grid" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
                        <path d="M 30 0 L 0 0 0 30" fill="none" stroke="white" stroke-width="0.8"/>
                    </pattern>
                    <pattern id="inv-grid-major" x="0" y="0" width="150" height="150" patternUnits="userSpaceOnUse">
                        <path d="M 150 0 L 0 0 0 150" fill="none" stroke="white" stroke-width="1.5"/>
                    </pattern>
                </defs>
                <rect width="400" height="600" fill="url(#inv-grid)"/>
                <rect width="400" height="600" fill="url(#inv-grid-major)" opacity="0.5"/>
            </svg>

            {{-- Left panel content --}}
            <div class="relative z-10 flex flex-col h-full w-full text-white">
                <div class="flex flex-col flex-1 justify-center items-center text-center">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Main Logo"
                         class="h-20 w-20 sm:h-20 sm:w-28 lg:h-28 lg:w-36 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-wide mb-2">
                        <span class="text-[#b5e2ff]">i</span><span class="text-[#1A3263]">RIMS-</span><span class="text-[#DA3D20]">V</span>
                    </h2>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 leading-tight drop-shadow-md">
                        Inventory System!
                    </h1>
                    <p class="text-sm sm:text-base text-white/95 max-w-xs sm:max-w-md lg:max-w-lg drop-shadow leading-relaxed px-4">
                        Centrally track and manage resources across schools and divisions with real-time data, full transparency, and integrated learning resource needs analysis.
                    </p>
                </div>
                <div class="flex justify-center items-center gap-3 sm:gap-4 lg:gap-6 pb-4 sm:pb-6 lg:pb-8 mt-6 lg:mt-0">
                    <img src="{{ asset('assets/images/rov.png') }}" alt="Logo"
                        class="h-12 w-12 sm:h-16 sm:w-16 lg:h-20 lg:w-20 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                    <img src="{{ asset('assets/images/deped.png') }}" alt="Logo"
                        class="h-12 w-12 sm:h-16 sm:w-16 lg:h-20 lg:w-20 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                    <img src="{{ asset('assets/images/bp.png') }}" alt="Logo"
                        class="h-12 w-12 sm:h-16 sm:w-16 lg:h-20 lg:w-20 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                </div>
            </div>
        </div>

        {{-- Right Section - Login Form --}}
        <div class="w-full lg:w-1/2 bg-white p-4 sm:p-6 lg:p-12 flex flex-col justify-center">
            <div class="w-full max-w-md mx-auto">

                {{-- System switcher --}}
                <div class="mb-4 sm:mb-6">
                    <div class="bg-gray-100 p-1 rounded-lg flex gap-1">

                        {{-- Inventory (active) --}}
                        <div aria-current="page"
                             class="flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-semibold text-xs flex items-center justify-center gap-1.5 bg-orange-500 text-white shadow-md">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="text-[10px] sm:text-xs">Inventory</span>
                        </div>

                        {{-- Library (external link) --}}
                        <a href="{{ $libraryUrl }}"
                           class="flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium text-xs flex items-center justify-center gap-1.5 text-gray-500 hover:text-gray-700 transition-colors">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span class="text-[10px] sm:text-xs">Library</span>
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>

                        {{-- Allocation (triggers coming soon modal) --}}
                        <button type="button"
                                onclick="document.getElementById('allocationModal').classList.remove('hidden')"
                                class="flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium text-xs flex items-center justify-center gap-1.5 text-gray-500 hover:text-gray-700 transition-colors">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span class="text-[10px] sm:text-xs">Allocation</span>
                        </button>
                    </div>
                    <p class="text-center text-[11px] text-gray-400 mt-2">Library and Allocation open their own systems</p>

                    <div class="mt-4 sm:mt-6 text-center">
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Sign In</h2>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Track and manage inventory</p>
                    </div>
                </div>

                {{-- Alerts --}}
                @if($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 shadow-md relative transition-opacity duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 sm:mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="font-medium text-sm sm:text-base">{{ $errors->first() }}</p>
                            </div>
                            <button onclick="this.parentElement.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.parentElement.remove(), 300);"
                                    class="text-red-500 hover:text-red-700 focus:outline-none ml-2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div id="error-alert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 shadow-md relative transition-opacity duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 sm:mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="font-medium text-sm sm:text-base">{{ session('error') }}</p>
                            </div>
                            <button onclick="this.parentElement.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.parentElement.remove(), 300);"
                                    class="text-red-500 hover:text-red-700 focus:outline-none ml-2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('success'))
                    <div id="success-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6 shadow-md relative transition-opacity duration-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 sm:mr-3 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <p class="font-medium text-sm sm:text-base">{{ session('success') }}</p>
                            </div>
                            <button onclick="this.parentElement.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.parentElement.remove(), 300);"
                                    class="text-green-500 hover:text-green-700 focus:outline-none ml-2">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="mb-4 sm:mb-5 relative">
                        <div class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" placeholder="Username or email" required value="{{ old('username') }}"
                               class="w-full pl-10 sm:pl-12 pr-3 sm:pr-4 py-3 sm:py-3.5 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent @error('username') border-red-500 @enderror">
                    </div>

                    <div class="mb-4 sm:mb-5 relative">
                        <div class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Password" required
                               class="w-full pl-10 sm:pl-12 pr-10 sm:pr-12 py-3 sm:py-3.5 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent">
                        <i id="passwordToggle" class="fas fa-eye absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600 text-sm sm:text-base"></i>
                    </div>

                    <div class="mb-5 sm:mb-6 flex items-center justify-between text-xs sm:text-sm">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}
                                   class="mr-2 h-3.5 w-3.5 sm:h-4 sm:w-4 rounded border-gray-300 text-custom-yellow focus:ring-custom-yellow cursor-pointer">
                            <label for="remember" class="text-gray-600 cursor-pointer">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" id="loginButton"
                            class="w-full bg-custom-yellow text-gray-800 font-semibold py-3 sm:py-3.5 text-sm sm:text-base rounded-lg hover:bg-custom-yellow-hover transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg">
                        <span id="buttonText">Sign In</span>
                        <span id="buttonLoading" class="hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Logging in...
                        </span>
                    </button>
                </form>

                <div class="mt-5 sm:mt-6 text-center">
                    <p class="text-xs sm:text-sm text-gray-600">
                        New here?
                        <a href="{{ route('register') }}" class="text-custom-teal font-medium hover:underline">Create an Account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Allocation — Coming Soon Modal ===== --}}
    <div id="allocationModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
        onclick="if(event.target===this) closeAllocationModal()">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        {{-- Card --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden border border-gray-100">

            {{-- Top accent bar --}}
            <div class="h-1 w-full bg-gradient-to-r from-orange-400 via-yellow-400 to-orange-500"></div>

            {{-- Body --}}
            <div class="px-6 pt-6 pb-5">

                {{-- Icon + title row --}}
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-amber-50">
                        <svg class="w-5 h-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 102 0V6zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-sm text-gray-800">Coming Soon</h3>
                        <p class="text-xs text-gray-500">Allocation System</p>
                    </div>
                    <button type="button"
                            onclick="closeAllocationModal()"
                            class="ml-auto p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors focus:outline-none"
                            aria-label="Close">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 leading-relaxed">
                    The Allocation module is currently under development and will be available soon.
                    Stay tuned for updates!
                </p>

                {{-- OK button --}}
                <button type="button"
                        onclick="closeAllocationModal()"
                        class="mt-5 w-full py-2.5 rounded-xl text-sm font-semibold bg-orange-500 hover:bg-orange-600 text-white transition-colors">
                    Got it
                </button>
            </div>

            {{-- Bottom note --}}
            <div class="px-6 py-3 border-t border-gray-100 text-xs text-center text-gray-400">
                — The iRIMS-V Team
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.__LOGIN__ = {
            hasError   : {{ $errors->any() ? 'true' : 'false' }},
            hasSuccess : {{ session('success') ? 'true' : 'false' }},
        };

        function closeAllocationModal() {
            document.getElementById('allocationModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAllocationModal();
        });
    </script>
    @vite('resources/js/login.js')
@endpush