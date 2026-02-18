@extends('public-layout.layout')

@section('title', 'Login')

@section('content')
    <style>
        /* Left panel transition */
        .left-panel {
            transition: background 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        /* Shape layers - all absolutely positioned, transition in/out */
        .shape-layer {
            position: absolute;
            inset: 0;
            transition: opacity 0.5s ease, transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            pointer-events: none;
        }
        .shape-layer.active {
            opacity: 1;
        }
        .shape-layer.exit {
            opacity: 0;
            transform: scale(1.04);
        }

        /* Floating animation for shapes */
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
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes pulse-scale {
            0%, 100% { transform: scale(1); opacity: 0.15; }
            50% { transform: scale(1.08); opacity: 0.25; }
        }
        @keyframes dash-move {
            to { stroke-dashoffset: -200; }
        }
        @keyframes morph {
            0%, 100% { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
            50% { border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%; }
        }

        /* Panel bg transitions */
        .left-panel.theme-inventory {
            background: linear-gradient(
                135deg,
                #fbc6a4 0%,
                #f5ae85 40%,
                #e88357 100%
            );
        }
        .left-panel.theme-library {
            background: linear-gradient(
                135deg,
                #a9cdfc 0%,
                #85b8fa 40%,
                #5fa0f6 100%
            );
        }

        .left-panel.theme-allocation {
            background: linear-gradient(
                135deg,
                #aaf3c8 0%,
                #7ee8ae 40%,
                #4fdb93 100%
            );
        }
        /* Thematic floating icon animations */
        .float-a { animation: float1 5s ease-in-out infinite; }
        .float-b { animation: float2 6s ease-in-out infinite 0.8s; }
        .float-c { animation: float3 7s ease-in-out infinite 1.5s; }
        .float-d { animation: float1 8s ease-in-out infinite 2s; }
        .spin-icon { animation: spin-slow 20s linear infinite; }
        .spin-rev { animation: spin-slow 25s linear infinite reverse; }

        /* Shared bg blob */
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

        /* Logo glimmer on tab switch */
        @keyframes glimmer {
            0% { filter: drop-shadow(0 0 20px rgba(255,255,255,1.0)); }
            50% { filter: drop-shadow(0 0 40px rgba(255,255,255,1.0)) brightness(1.15); }
            100% { filter: drop-shadow(0 0 20px rgba(255,255,255,1.0)); }
        }
        .logo-pulse { animation: glimmer 0.6s ease-out; }
    </style>

    <div class="flex flex-col lg:flex-row w-full max-w-6xl mx-auto overflow-hidden rounded-none sm:rounded-2xl shadow-none sm:shadow-4xl min-h-screen lg:min-h-[600px]">

        {{-- Left Section --}}
        <div class="left-panel theme-inventory lg:flex lg:w-1/2 relative
                    py-8 px-4 sm:py-12 sm:px-8 lg:rounded-l-2xl">

            {{-- ============================================================ --}}
            {{-- INVENTORY SHAPES: animated stats, bar chart, line graph, KPI rings --}}
            {{-- ============================================================ --}}
            <div class="shape-layer active" id="shapes-inventory">
                <div class="bg-blob-tr"></div>
                <div class="bg-blob-bl"></div>

                {{-- === ANIMATED BAR CHART (top-right) === --}}
                <svg class="absolute float-a" style="top:4%; right:4%; width:150px; height:120px; opacity:.9;" viewBox="0 0 130 100">
                    <!-- grid lines -->
                    <line x1="10" y1="10" x2="10" y2="80" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                    <line x1="10" y1="80" x2="125" y2="80" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                    <line x1="10" y1="60" x2="125" y2="60" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3 3"/>
                    <line x1="10" y1="40" x2="125" y2="40" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3 3"/>
                    <line x1="10" y1="20" x2="125" y2="20" stroke="rgba(255,255,255,0.1)" stroke-width="1" stroke-dasharray="3 3"/>
                    <!-- bars with grow animation -->
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
                    <!-- value dots on top of bars -->
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

                {{-- === LINE GRAPH with animated draw (mid-left) === --}}
                <svg class="absolute float-b" style="top:32%; left:2%; width:145px; height:105px; opacity:.8;" viewBox="0 0 130 90">
                    <!-- grid -->
                    <line x1="8" y1="8" x2="8" y2="72" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                    <line x1="8" y1="72" x2="125" y2="72" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                    <line x1="8" y1="55" x2="125" y2="55" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 3"/>
                    <line x1="8" y1="38" x2="125" y2="38" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 3"/>
                    <line x1="8" y1="22" x2="125" y2="22" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="3 3"/>
                    <!-- area fill under line -->
                    <path d="M8,60 L28,48 L48,52 L68,28 L88,35 L108,18 L125,22 L125,72 L8,72 Z"
                          fill="rgba(255,255,255,0.06)"/>
                    <!-- animated line path -->
                    <polyline points="8,60 28,48 48,52 68,28 88,35 108,18 125,22"
                              fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                              stroke-dasharray="300" stroke-dashoffset="300">
                        <animate attributeName="stroke-dashoffset" from="300" to="0" dur="1.8s" fill="freeze" begin="0.2s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    </polyline>
                    <!-- data points -->
                    <circle cx="8"   cy="60" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="0.5s"/></circle>
                    <circle cx="28"  cy="48" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="0.8s"/></circle>
                    <circle cx="48"  cy="52" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.0s"/></circle>
                    <circle cx="68"  cy="28" r="3.5" fill="rgba(251,191,36,1)" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.2s"/></circle>
                    <circle cx="88"  cy="35" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.4s"/></circle>
                    <circle cx="108" cy="18" r="3.5" fill="rgba(251,191,36,1)" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.6s"/></circle>
                    <circle cx="125" cy="22" r="3.5" fill="white" opacity="0"><animate attributeName="opacity" from="0" to="1" dur="0.2s" fill="freeze" begin="1.8s"/></circle>
                    <!-- moving tracker dot on latest point -->
                    <circle cx="125" cy="22" r="7" fill="none" stroke="rgba(251,191,36,0.6)" stroke-width="1.5" opacity="0">
                        <animate attributeName="opacity" from="0" to="1" dur="0.3s" fill="freeze" begin="2s"/>
                        <animate attributeName="r" values="5;9;5" dur="2s" repeatCount="indefinite" begin="2s"/>
                        <animate attributeName="opacity" values="1;0.3;1" dur="2s" repeatCount="indefinite" begin="2s"/>
                    </circle>
                    <!-- label -->
                    <text x="8" y="85" fill="rgba(255,255,255,0.5)" font-size="7" font-family="monospace">Stock Trend</text>
                </svg>

                {{-- === DONUT / KPI RING (bottom-right) === --}}
                <svg class="absolute float-c" style="bottom:8%; right:5%; width:110px; height:110px; opacity:.8;" viewBox="0 0 100 100">
                    <!-- bg ring -->
                    <circle cx="50" cy="50" r="38" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="10"/>
                    <!-- animated progress ring — 78% -->
                    <circle cx="50" cy="50" r="38" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="10"
                            stroke-linecap="round" stroke-dasharray="238" stroke-dashoffset="238"
                            transform="rotate(-90 50 50)">
                        <animate attributeName="stroke-dashoffset" from="238" to="52" dur="1.6s" fill="freeze" begin="0.3s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    </circle>
                    <!-- second thinner ring (inner) -->
                    <circle cx="50" cy="50" r="28" fill="none" stroke="rgba(251,191,36,0.3)" stroke-width="5"
                            stroke-dasharray="176" stroke-dashoffset="176" transform="rotate(-90 50 50)">
                        <animate attributeName="stroke-dashoffset" from="176" to="44" dur="1.8s" fill="freeze" begin="0.6s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    </circle>
                    <!-- center label -->
                    <text x="50" y="46" text-anchor="middle" fill="rgba(255,255,255,0.9)" font-size="14" font-weight="bold" font-family="monospace">78%</text>
                    <text x="50" y="58" text-anchor="middle" fill="rgba(255,255,255,0.45)" font-size="7" font-family="monospace">In Stock</text>
                </svg>

                {{-- === FLOATING STAT CARD (top-left, small) === --}}
                <svg class="absolute float-d" style="top:5%; left:5%; width:105px; height:65px; opacity:1;" viewBox="0 0 95 55">
                    <!-- card bg -->
                    <rect x="0" y="0" width="95" height="55" rx="6" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.25)" stroke-width="1"/>
                    <!-- sparkline inside card -->
                    <polyline points="8,38 18,28 28,32 38,18 48,22 58,14 68,20 78,12 88,16"
                              fill="none" stroke="rgba(251,191,36,0.8)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                              stroke-dasharray="150" stroke-dashoffset="150">
                        <animate attributeName="stroke-dashoffset" from="150" to="0" dur="1.4s" fill="freeze" begin="0.5s"/>
                    </polyline>
                    <!-- stat label -->
                    <text x="8" y="12" fill="rgba(255,255,255,0.5)" font-size="6" font-family="monospace">TOTAL ITEMS</text>
                    <text x="8" y="22" fill="rgba(255,255,255,0.9)" font-size="10" font-weight="bold" font-family="monospace">4,821</text>
                    <!-- up arrow indicator -->
                    <polygon points="62,20 67,14 72,20" fill="rgba(134,239,172,0.8)"/>
                    <text x="74" y="21" fill="rgba(134,239,172,0.8)" font-size="6" font-family="monospace">+12%</text>
                </svg>

                {{-- === SECOND STAT CARD (bottom-left, small) === --}}
                <svg class="absolute float-a" style="bottom:6%; left:4%; width:105px; height:55px; opacity:1;" viewBox="0 0 95 48">
                    <rect x="0" y="0" width="95" height="48" rx="6" fill="rgba(255,255,255,0.10)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                    <!-- mini horizontal bar -->
                    <rect x="8" y="30" width="79" height="5" rx="2" fill="rgba(255,255,255,0.15)"/>
                    <rect x="8" y="30" width="0" height="5" rx="2" fill="rgba(251,191,36,0.85)">
                        <animate attributeName="width" from="0" to="55" dur="1.4s" fill="freeze" begin="0.8s" calcMode="spline" keySplines="0.4 0 0.2 1"/>
                    </rect>
                    <text x="8" y="12" fill="rgba(255,255,255,0.45)" font-size="6" font-family="monospace">LOW STOCK</text>
                    <text x="8" y="22" fill="rgba(255,255,255,0.9)" font-size="10" font-weight="bold" font-family="monospace">127</text>
                    <text x="69" y="38" fill="rgba(255,255,255,0.5)" font-size="6" font-family="monospace">69%</text>
                </svg>

                {{-- Subtle chart-paper grid background --}}
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
            </div>

            {{-- ============================================================ --}}
            {{-- LIBRARY SHAPES: open books, reading lamp, magnifier, bookmark --}}
            {{-- ============================================================ --}}
            <div class="shape-layer" id="shapes-library">
                <div class="bg-blob-tr"></div>
                <div class="bg-blob-bl"></div>

                {{-- Open book (large, top-right) --}}
                <svg class="absolute float-a" style="top:6%; right:3%; width:140px; height:110px; opacity:0.6;" viewBox="0 0 120 90">
                    <!-- left page -->
                    <path d="M60,10 Q30,10 10,20 L10,75 Q30,65 60,70 Z" fill="none" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
                    <!-- right page -->
                    <path d="M60,10 Q90,10 110,20 L110,75 Q90,65 60,70 Z" fill="none" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
                    <!-- spine -->
                    <line x1="60" y1="10" x2="60" y2="70" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
                    <!-- text lines left page -->
                    <line x1="20" y1="30" x2="52" y2="28" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <line x1="20" y1="38" x2="52" y2="36" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <line x1="20" y1="46" x2="45" y2="44" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <line x1="20" y1="54" x2="52" y2="52" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <!-- text lines right page -->
                    <line x1="68" y1="30" x2="100" y2="28" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <line x1="68" y1="38" x2="100" y2="36" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <line x1="68" y1="46" x2="90" y2="44" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                    <line x1="68" y1="54" x2="100" y2="52" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                </svg>

                {{-- Stack of books (bottom-left) --}}
                <svg class="absolute float-b" style="bottom:12%; left:5%; width:110px; height:100px; opacity:0.7;" viewBox="0 0 90 80">
                    <!-- book 1 (bottom, widest) -->
                    <rect x="5" y="58" width="80" height="14" rx="3" fill="none" stroke="white" stroke-width="2.5"/>
                    <line x1="14" y1="58" x2="14" y2="72" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                    <!-- book 2 -->
                    <rect x="10" y="42" width="70" height="14" rx="3" fill="none" stroke="white" stroke-width="2.5"/>
                    <line x1="19" y1="42" x2="19" y2="56" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                    <!-- book 3 -->
                    <rect x="15" y="26" width="60" height="14" rx="3" fill="none" stroke="white" stroke-width="2.5"/>
                    <line x1="24" y1="26" x2="24" y2="40" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>
                    <!-- book 4 (top, narrowest) -->
                    <rect x="20" y="12" width="50" height="12" rx="3" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
                </svg>

                {{-- Magnifying glass (mid-right) --}}
                <svg class="absolute float-c" style="top:40%; right:6%; width:90px; height:90px; opacity:0.7;" viewBox="0 0 80 80">
                    <circle cx="32" cy="32" r="22" fill="none" stroke="white" stroke-width="3"/>
                    <circle cx="32" cy="32" r="14" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="1.5"/>
                    <line x1="48" y1="48" x2="70" y2="70" stroke="white" stroke-width="4" stroke-linecap="round"/>
                    <!-- reflection glint -->
                    <line x1="22" y1="20" x2="26" y2="24" stroke="rgba(255,255,255,0.6)" stroke-width="2" stroke-linecap="round"/>
                </svg>

                {{-- Bookmark ribbon (top-left) --}}
                <svg class="absolute float-d" style="top:5%; left:10%; width:55px; height:80px; opacity:0.6;" viewBox="0 0 40 70">
                    <polygon points="5,5 35,5 35,60 20,48 5,60" fill="none" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
                    <!-- decorative star on bookmark -->
                    <polygon points="20,18 22,24 28,24 23,28 25,34 20,30 15,34 17,28 12,24 18,24" fill="rgba(255,255,255,0.35)" stroke="none"/>
                </svg>

                {{-- Subtle horizontal line rules (like ruled paper) --}}
                <svg class="absolute top-0 left-0 w-full h-full" style="opacity:0.2;" viewBox="0 0 400 600" preserveAspectRatio="none">
                    <line x1="0" y1="80"  x2="400" y2="80"  stroke="white" stroke-width="1"/>
                    <line x1="0" y1="140" x2="400" y2="140" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="200" x2="400" y2="200" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="260" x2="400" y2="260" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="320" x2="400" y2="320" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="380" x2="400" y2="380" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="440" x2="400" y2="440" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="500" x2="400" y2="500" stroke="white" stroke-width="1"/>
                    <line x1="0" y1="560" x2="400" y2="560" stroke="white" stroke-width="1"/>
                    <!-- margin line -->
                    <line x1="50" y1="0" x2="50" y2="600" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
                </svg>
            </div>

            {{-- ============================================================ --}}
            {{-- ALLOCATION SHAPES: pie chart, arrows, distribution funnel, map pin --}}
            {{-- ============================================================ --}}
            <div class="shape-layer" id="shapes-allocation">
                <div class="bg-blob-tr"></div>
                <div class="bg-blob-bl"></div>

                {{-- Pie / donut chart (top-right) --}}
                <svg class="absolute spin-icon" style="top:4%; right:4%; width:120px; height:120px; opacity:0.7;" viewBox="0 0 100 100">
                    <!-- donut segments -->
                    <circle cx="50" cy="50" r="36" fill="none" stroke="rgba(255,255,255,0.25)" stroke-width="18"/>
                    <!-- segment 1 ~40% -->
                    <circle cx="50" cy="50" r="36" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="18"
                            stroke-dasharray="90 136" stroke-dashoffset="0" transform="rotate(-90 50 50)"/>
                    <!-- segment 2 ~30% -->
                    <circle cx="50" cy="50" r="36" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="18"
                            stroke-dasharray="68 158" stroke-dashoffset="-90" transform="rotate(-90 50 50)"/>
                    <!-- inner hole -->
                    <circle cx="50" cy="50" r="20" fill="rgba(0,0,0,0.12)"/>
                    <!-- center percent text -->
                    <text x="50" y="55" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="10" font-weight="bold">%</text>
                </svg>

                {{-- Distribution arrows / flow (mid-left) --}}
                <svg class="absolute float-a" style="top:34%; left:3%; width:110px; height:120px; opacity:0.6;" viewBox="0 0 90 100">
                    <!-- central hub -->
                    <circle cx="45" cy="40" r="12" fill="none" stroke="white" stroke-width="2.5"/>
                    <circle cx="45" cy="40" r="5" fill="rgba(255,255,255,0.4)"/>
                    <!-- arrow to top-left -->
                    <line x1="35" y1="30" x2="18" y2="15" stroke="white" stroke-width="2" stroke-dasharray="4 3"/>
                    <polygon points="12,10 22,12 18,22" fill="white" opacity="0.7"/>
                    <!-- arrow to top-right -->
                    <line x1="55" y1="30" x2="72" y2="15" stroke="white" stroke-width="2" stroke-dasharray="4 3"/>
                    <polygon points="78,10 68,12 72,22" fill="white" opacity="0.7"/>
                    <!-- arrow down-left -->
                    <line x1="36" y1="50" x2="18" y2="70" stroke="white" stroke-width="2" stroke-dasharray="4 3"/>
                    <polygon points="12,76 16,65 26,70" fill="white" opacity="0.7"/>
                    <!-- arrow down-right -->
                    <line x1="54" y1="50" x2="72" y2="70" stroke="white" stroke-width="2" stroke-dasharray="4 3"/>
                    <polygon points="78,76 68,65 72,76" fill="white" opacity="0.7"/>
                </svg>

                {{-- Funnel / filter (bottom-right) --}}
                <svg class="absolute float-b" style="bottom:10%; right:5%; width:90px; height:110px; opacity:0.9;" viewBox="0 0 80 100">
                    <!-- funnel shape -->
                    <polygon points="5,10 75,10 50,45 50,85 30,85 30,45" fill="none" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
                    <!-- input dots (items entering) -->
                    <circle cx="18" cy="5" r="4" fill="rgba(255,255,255,0.5)"/>
                    <circle cx="40" cy="4" r="4" fill="rgba(255,255,255,0.5)"/>
                    <circle cx="62" cy="5" r="4" fill="rgba(255,255,255,0.5)"/>
                    <!-- output lines -->
                    <line x1="40" y1="85" x2="40" y2="98" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="34" y1="93" x2="40" y2="98" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="46" y1="93" x2="40" y2="98" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>

                {{-- Map pin / location (top-left) --}}
                <svg class="absolute float-c" style="top:6%; left:8%; width:70px; height:80px; opacity:0.7;" viewBox="0 0 56 70">
                    <!-- pin body -->
                    <path d="M28,5 C14,5 6,16 6,26 C6,42 28,62 28,62 C28,62 50,42 50,26 C50,16 42,5 28,5 Z"
                          fill="none" stroke="white" stroke-width="2.5"/>
                    <!-- inner circle -->
                    <circle cx="28" cy="26" r="9" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
                    <circle cx="28" cy="26" r="4" fill="rgba(255,255,255,0.4)"/>
                </svg>

                {{-- Small bar chart (mid-right) --}}
                <svg class="absolute float-d" style="top:55%; right:5%; width:85px; height:70px; opacity:0.8;" viewBox="0 0 80 60">
                    <!-- baseline -->
                    <line x1="5" y1="52" x2="75" y2="52" stroke="white" stroke-width="1.5"/>
                    <!-- bars -->
                    <rect x="10" y="30" width="10" height="22" rx="2" fill="rgba(255,255,255,0.5)"/>
                    <rect x="25" y="18" width="10" height="34" rx="2" fill="rgba(255,255,255,0.7)"/>
                    <rect x="40" y="38" width="10" height="14" rx="2" fill="rgba(255,255,255,0.45)"/>
                    <rect x="55" y="10" width="10" height="42" rx="2" fill="rgba(255,255,255,0.6)"/>
                    <!-- trend line -->
                    <polyline points="15,28 30,16 45,36 60,8" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" stroke-dasharray="3 2"/>
                </svg>

                {{-- Subtle dot-matrix background --}}
                <svg class="absolute top-0 left-0 w-full h-full" style="opacity:0.3;" viewBox="0 0 400 600">
                    <defs>
                        <pattern id="alloc-dots" x="0" y="0" width="28" height="28" patternUnits="userSpaceOnUse">
                            <circle cx="3" cy="3" r="2" fill="white"/>
                        </pattern>
                    </defs>
                    <rect width="400" height="600" fill="url(#alloc-dots)"/>
                </svg>
            </div>

            {{-- Content (always on top) --}}
            <div class="relative z-10 flex flex-col h-full w-full text-white">
                <div class="flex flex-col flex-1 justify-center items-center text-center">
                    <img id="mainLogo" src="{{ asset('assets/images/logo.png') }}" alt="Main Logo"
                         class="h-20 w-20 sm:h-20 sm:w-28 lg:h-28 lg:w-36 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)] mb-3"
                         style="transition: transform 0.5s cubic-bezier(0.34,1.56,0.64,1);">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-wide mb-2">
                        <span class="text-[#b5e2ff]">i</span><span class="text-[#1A3263]">RIMS-</span><span class="text-[#DA3D20]">V</span>
                    </h2>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 leading-tight drop-shadow-md">Welcome back!</h1>
                    <p class="text-sm sm:text-base text-white/95 max-w-xs sm:max-w-md lg:max-w-lg drop-shadow leading-relaxed px-4">
                        An innovative, ICT-enabled platform that centralizes and tracks learning resources in Region V, providing real-time mapping, monitoring, and management across schools and divisions to enhance efficiency, transparency, and data-driven decision-making in Learning Resource Management.
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
                {{-- System Tabs --}}
                <div class="mb-4 sm:mb-6">
                    <div class="bg-gray-100 p-1 rounded-lg flex gap-1">
                        <button type="button" class="system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs active"
                                data-system="inventory">
                            <div class="flex items-center justify-center space-x-1 sm:space-x-1.5">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span class="text-[10px] sm:text-xs">Inventory</span>
                            </div>
                        </button>
                        <button type="button" class="system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs"
                                data-system="library">
                            <div class="flex items-center justify-center space-x-1 sm:space-x-1.5">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <span class="text-[10px] sm:text-xs">Library</span>
                            </div>
                        </button>
                        <button type="button" class="system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs"
                                data-system="allocation">
                            <div class="flex items-center justify-center space-x-1 sm:space-x-1.5">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <span class="text-[10px] sm:text-xs">Allocation</span>
                            </div>
                        </button>
                    </div>

                    <div class="mt-4 sm:mt-6 text-center">
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800" id="systemTitle">Sign In</h2>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1" id="systemDescription">Track and manage inventory</p>
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
                    <input type="hidden" name="system" id="systemInput" value="inventory">

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

                    <div class="mb-5 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0 text-xs sm:text-sm">
                        <div class="flex items-center">
                            <input type="checkbox" id="notRobot" name="notRobot" class="mr-2 h-3.5 w-3.5 sm:h-4 sm:w-4 rounded border-gray-300 text-custom-yellow focus:ring-custom-yellow" required>
                            <label for="notRobot" class="text-gray-600">I'm not a robot</label>
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
@endsection

@push('scripts')
    <script>
        // Password toggle
        const toggle = document.getElementById('passwordToggle');
        const password = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('#error-alert, #success-alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 8000);
            });

            // Login form loading state
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const buttonLoading = document.getElementById('buttonLoading');
            loginForm.addEventListener('submit', function () {
                loginButton.disabled = true;
                buttonText.classList.add('hidden');
                buttonLoading.classList.remove('hidden');
            });

            // Tab system
            const systemTabs = document.querySelectorAll('.system-tab');
            const systemInput = document.getElementById('systemInput');
            const systemTitle = document.getElementById('systemTitle');
            const systemDescription = document.getElementById('systemDescription');
            const leftPanel = document.querySelector('.left-panel');
            const mainLogo = document.getElementById('mainLogo');

            const systemInfo = {
                inventory: {
                    title: 'Sign In',
                    description: 'Track and manage inventory',
                    activeClass: 'bg-orange-500 text-white shadow-md',
                    inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200',
                    panelTheme: 'theme-inventory',
                },
                library: {
                    title: 'Sign In',
                    description: 'Access books and manage resources',
                    activeClass: 'bg-blue-500 text-white shadow-md',
                    inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200',
                    panelTheme: 'theme-library',
                },
                allocation: {
                    title: 'Sign In',
                    description: 'Manage resource distribution',
                    activeClass: 'bg-green-500 text-white shadow-md',
                    inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200',
                    panelTheme: 'theme-allocation',
                }
            };

            let currentSystem = 'inventory';

            function switchSystem(system) {
                if (system === currentSystem) return;
                const info = systemInfo[system];
                const prevInfo = systemInfo[currentSystem];

                // Swap shape layers
                const prevShapes = document.getElementById('shapes-' + currentSystem);
                const nextShapes = document.getElementById('shapes-' + system);

                if (prevShapes) {
                    prevShapes.classList.remove('active');
                    prevShapes.classList.add('exit');
                    setTimeout(() => prevShapes.classList.remove('exit'), 600);
                }
                if (nextShapes) {
                    nextShapes.style.transform = 'scale(0.97)';
                    nextShapes.classList.add('active');
                    setTimeout(() => { nextShapes.style.transform = ''; }, 50);
                }

                // Swap panel background theme
                leftPanel.classList.remove('theme-inventory', 'theme-library', 'theme-allocation');
                leftPanel.classList.add(info.panelTheme);

                // Logo bounce animation
                mainLogo.style.transform = 'scale(1.15) rotate(-5deg)';
                mainLogo.classList.add('logo-pulse');
                setTimeout(() => {
                    mainLogo.style.transform = '';
                    mainLogo.classList.remove('logo-pulse');
                }, 600);

                // Update tabs
                systemTabs.forEach(t => {
                    const tSystem = t.getAttribute('data-system');
                    t.className = `system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[tSystem].inactiveClass}`;
                    t.classList.remove('active');
                });
                const activeTab = document.querySelector(`.system-tab[data-system="${system}"]`);
                if (activeTab) {
                    activeTab.className = `system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs ${info.activeClass}`;
                    activeTab.classList.add('active');
                }

                // Update form and labels
                systemInput.value = system;
                systemTitle.textContent = info.title;
                systemDescription.textContent = info.description;

                currentSystem = system;
            }

            systemTabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    switchSystem(this.getAttribute('data-system'));
                });
            });

            // Set initial active tab styling
            const initialTab = document.querySelector('.system-tab.active');
            if (initialTab) {
                const system = initialTab.getAttribute('data-system');
                initialTab.className = `system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[system].activeClass}`;
            }
        });
    </script>
@endpush
