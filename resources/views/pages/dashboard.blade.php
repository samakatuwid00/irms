@extends('pages.layout.layout')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', 'Dashboard overview of your Learning Resource Management System')
@section('breadcrumb', 'Dashboard')

@section('content')

    <div class="p-0 space-y-6">
        <!-- ================= HEADER ================= -->
        @include('pages.partials.page-header')

        <!-- ================= SUMMARY CARDS ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 lg:gap-6">

            <!-- Total Learning Resources -->
            <div
                @if($showLrSourceToggle) x-data="{ divisionHubSelected: true, showAll: true }" @endif
                class="group/icon bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">

                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">Total Learning Resources</p>
                        @if($showLrSourceToggle)
                            <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                                <span x-show="showAll">{{ number_format($totalLrData['total'] ?? 0) }}</span>
                                <span x-cloak x-show="!showAll && divisionHubSelected">{{ number_format($totalLrData['division_lr_hub'] ?? 0) }}</span>
                                <span x-cloak x-show="!showAll && !divisionHubSelected">{{ number_format($totalLrData['school_lr'] ?? 0) }}</span>
                            </p>
                            <div class="flex items-center gap-2 text-[11px] sm:text-xs text-gray-500 font-medium">
                                <button type="button" aria-label="Previous source" @click="showAll ? (showAll = false, divisionHubSelected = true) : (divisionHubSelected ? divisionHubSelected = false : showAll = true)"
                                    class="inline-flex items-center justify-center rounded text-gray-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span x-show="showAll">All</span>
                                <span x-cloak x-show="!showAll && divisionHubSelected">Division Hub</span>
                                <span x-cloak x-show="!showAll && !divisionHubSelected">School</span>
                                <button type="button" aria-label="Next source" @click="showAll ? (showAll = false, divisionHubSelected = true) : (divisionHubSelected ? divisionHubSelected = false : showAll = true)"
                                    class="inline-flex items-center justify-center rounded text-gray-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                                {{ number_format($totalLrData['total'] ?? 0) }}
                            </p>
                        @endif
                    </div>
                    <div class="bg-blue-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0 transition-transform duration-300 group-hover/icon:scale-110 group-hover/icon:-rotate-6">
                        <svg class="w-6 h-6 lg:w-7 lg:h-7 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path
                                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                </div>

                <div class="mt-auto space-y-1.5 text-xs sm:text-sm">
                    @foreach(['Print' => 'print', 'Non-Print' => 'non_print'] as $label => $key)
                        <div class="group relative flex justify-between text-gray-600">
                            <span class="truncate pr-3">{{ $label }}</span>
                            <span class="font-semibold text-gray-800 whitespace-nowrap">
                                {{ number_format($totalLrData[$key] ?? 0) }}
                            </span>
                            <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block z-20 pointer-events-none">
                                <div
                                    class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                                    {{ $label }}
                                </div>
                                <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div> <!-- small arrow -->
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Total Population (same pattern - icon size & tooltip style) -->
            <div
                class="group/icon bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">Total Population</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                            {{ number_format($populationData['total'] ?? 0) }}
                        </p>
                    </div>
                    <div class="bg-green-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0 transition-transform duration-300 group-hover/icon:scale-110 group-hover/icon:-rotate-6">
                        <svg class="w-6 h-6 lg:w-7 lg:h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                </div>

                <div class="mt-auto space-y-1.5 text-xs sm:text-sm">
                    @foreach(['Male' => 'male', 'Female' => 'female'] as $label => $key)
                        <div class="group relative flex justify-between text-gray-600">
                            <span class="truncate pr-3">{{ $label }}</span>
                            <span class="font-semibold text-gray-800 whitespace-nowrap">
                                {{ number_format($populationData[$key] ?? 0) }}
                            </span>
                            <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block z-20 pointer-events-none">
                                <div
                                    class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                                    {{ $label }}
                                </div>
                                <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Overall Ratio (same tooltip & icon style) -->
            <div
                class="group/icon bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">Overall Ratio</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight break-all">
                            {{ $overallRatioData['ratio_display'] ?? '—' }}
                        </p>
                    </div>
                    <div class="bg-yellow-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0 transition-transform duration-300 group-hover/icon:scale-110 group-hover/icon:-rotate-6">
                        <svg class="w-6 h-6 lg:w-7 lg:h-7 text-yellow-600" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                </div>

                <div class="mt-auto space-y-1.5 text-xs sm:text-sm">
                    @foreach(['Total LR' => 'total_lr', 'Total Population' => 'total_population'] as $label => $key)
                        <div class="group relative flex justify-between text-gray-600">
                            <span class="truncate pr-3">{{ $label }}</span>
                            <span class="font-semibold text-gray-800 whitespace-nowrap">
                                {{ number_format($overallRatioData[$key] ?? 0) }}
                            </span>
                            <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block z-20 pointer-events-none">
                                <div
                                    class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                                    {{ $label }}
                                </div>
                                <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- LR Needs – now matches the hover behavior & reliability of the other cards -->
            <div
                class="group/icon bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">

                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">LR Needs</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                            {{ number_format($lrNeedsData['total_needs'] ?? 0) }}
                        </p>
                    </div>
                    <div class="bg-red-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0 transition-transform duration-300 group-hover/icon:scale-110 group-hover/icon:-rotate-6">
                        <svg class="w-6 h-6 lg:w-7 lg:h-7 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <div class="mt-auto space-y-1.5 text-xs sm:text-sm">
                    @forelse($lrNeedsData['needs'] as $need)
                        <div class="group relative flex justify-between text-gray-600">
                            <span class="truncate pr-3">{{ $need['subject_grade'] }}</span>
                            <span class="font-semibold text-red-700 whitespace-nowrap">
                                {{ number_format($need['needed']) }}
                            </span>

                            <!-- tooltip stays the same -->
                            <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block z-20 pointer-events-none">
                                <div
                                    class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                                    {{ $need['subject_grade'] }}
                                </div>
                                <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-green-600 font-medium text-center py-3 text-sm">
                            No LR needs
                        </div>
                    @endforelse
                </div>

            </div>

        </div>

        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-4 md:gap-6 mb-0">
            
            <x-filter-select
                id="globalFilter"
                label="Visualization"
                :options="[
                    ['value' => 'lr-availability', 'label' => 'Subject Level LR Availability'],
                    ['value' => 'lr-ratio',        'label' => 'Learning Resources to Learner Ratio'],
                    ['value' => 'lr-exdef',        'label' => 'Excess / Deficiency'],
                    ['value' => 'lr-heatmap',      'label' => 'Equitable Distribution'],
                ]"
                class="w-full sm:flex-1 min-w-0 mb-4"
            />

            @if($userLevel !== 1)
                <x-filter-select
                    id="schoolYearFilter"
                    label="Key Stages"
                    :options="[
                        ['value' => 'K1', 'label' => 'Key Stage 1'],
                        ['value' => 'K2', 'label' => 'Key Stage 2'],
                        ['value' => 'JH', 'label' => 'Junior High'],
                        ['value' => 'SH', 'label' => 'Senior High'],
                    ]"
                    class="w-full sm:flex-1 min-w-0 mb-4"
                />
            @endif

            <!-- Print Type Filter -->
            <x-filter-select
                id="printTypeFilter"
                label="Print Types"
                :options="$printTypeOptions"
                class="w-full sm:flex-1 min-w-0 mb-4"
            />

        </div>
  
        <!-- LR Availability -->
        <x-chart-card id="lr-availability" title="LR Availability" class="chart-container">
            <p class="text-xs text-gray-500">Subject Level LR Availability</p>
            <p class="text-m font-bold mb-2">Available Learning Resources Per Subject</p>

            <!-- Give this div a sensible aspect ratio or min-height -->
            <div class="w-full aspect-[4/3] min-h-[340px] max-h-[700px]">
                <div id="chart" class="w-full h-full"></div>
            </div>
        </x-chart-card>

        <!-- LR Ratio -->
        <x-chart-card id="lr-ratio" title="LR Ratio" class="chart-container hidden">
            <p class="text-xs text-gray-500">Learning Resources To Learner Ratio</p>
            <p class="text-m font-bold mb-2">Ratio of Learning Resources Per Grade Level</p>
            <div class="w-full aspect-[4/3] min-h-[340px] max-h-[700px]">
                <div id="main" class="w-full h-full"></div>
            </div>
        </x-chart-card>

        <!-- LR Exdef -->
        <x-chart-card id="lr-exdef" title="LR Exdef" class="chart-container hidden">
            <div class="space-y-1.5">
                <p class="text-xs text-gray-500">Grade-Subject ExDef</p>
                <p class="text-m font-bold mb-2">Excess | Deficiency</p>
            </div>

            <!-- Responsive container – no fixed px height -->
            <div class="w-full aspect-[4/3] min-h-[420px] max-h-[760px] mt-3">
                <div id="exdef" class="w-full h-full rounded-md overflow-hidden"></div>
            </div>
        </x-chart-card>

        <!-- LR Heatmap -->
        <x-chart-card id="lr-heatmap" title="LR Heatmap" class="chart-container hidden">
            <div class="w-full aspect-[4/3] min-h-[420px] max-h-[760px] mt-3">
                <p class="text-xs text-gray-500">Heat Map</p>
                <p class="text-m font-bold mb-2">Equitable Distribution</p>
                <div id="heatmap" class="w-full h-full"></div> <!-- ← subtract header height -->
            </div>
        </x-chart-card>

        <!-- BOSY -->
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-4 md:gap-6 mb-0">

            @if($userLevel >= 4)
                <x-filter-select
                    id="regionFilter"
                    label="Region / Library Level"
                    :options="$regionOptions"
                    class="w-full sm:flex-1 min-w-0 mb-4"
                />
            @endif
            @if($userLevel == 3)
                <x-filter-select id="divisionFilter" label="District" class="w-full sm:flex-1 min-w-0 mb-4">
                    @foreach($divisions as $district)
                        <option value="{{ $district['id'] }}">{{ $district['name'] }}</option>
                    @endforeach
                </x-filter-select>
            @endif

            <!-- Print Type Filter for BOSY -->
            <x-filter-select
                id="bosyPrintTypeFilter"
                label="Print Types"
                :options="$printTypeOptions"
                class="w-full sm:flex-1 min-w-0 mb-4"
            />

        </div>

        <x-chart-card
            id="bosy-status"
            title="BOSY Status"
            class="chart-container"
        >
 
            {{-- ── Period & CY Header ── --}}
            <div class="space-y-1.5">
                <p class="text-m font-bold mb-0">Inventory Status/Monitoring</p>
            </div>
 
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
 
                {{-- Left: Period --}}
                <div>
                    <p class="text-xs font-medium tracking-wide text-gray-500">
                        PERIOD: Beginning-Of-School Year
                    </p>
                    {{-- Populated from DB; JS will keep it fresh after an update --}}
                    <p class="text-lg font-semibold text-gray-900" id="bosyPeriodDisplay">
                        {{ $bosySettings->period_display }}
                    </p>
                </div>
 
                {{-- Right: Calendar Year + optional edit button --}}
                <div class="text-left sm:text-right flex items-center gap-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Calendar Year
                        </p>
                        <p class="text-2xl font-bold text-cyan-600" id="bosyYearDisplay">
                            CY {{ $bosySettings->calendar_year }}
                        </p>
                    </div>
 
                    {{-- Edit button — rendered only for Regional Accounts --}}
                    @if($userLevel >= 4)
                        <button
                            type="button"
                            id="openBosySettingsBtn"
                            class="mt-5 sm:mt-0 inline-flex items-center justify-center w-9 h-9
                                   text-cyan-700 hover:text-cyan-800 hover:bg-cyan-50
                                   rounded-xl border border-cyan-200 transition-colors
                                   focus:outline-none focus:ring-2 focus:ring-cyan-300"
                            title="Update BOSY Period & Calendar Year"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
 
            {{-- ── BOSY Edit Modal (only rendered for regional accounts) ── --}}
            @if($userLevel >= 4)
            <div
                id="bosySettingsModal"
                class="fixed inset-0 z-50 hidden items-center justify-center
                       bg-black/40 backdrop-blur-sm p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="bosyModalTitle"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md
                            ring-1 ring-gray-200 overflow-hidden">
 
                    {{-- Modal header --}}
                    <div class="flex items-center justify-between px-6 py-4
                                border-b border-gray-100 bg-cyan-50/60">
                        <h2 id="bosyModalTitle"
                            class="text-base font-semibold text-gray-800 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-cyan-600"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2"/>
                            </svg>
                            Update BOSY Period & Calendar Year
                        </h2>
                        <button type="button" id="closeBosyModalBtn"
                                class="text-gray-400 hover:text-gray-600 rounded-lg
                                       focus:outline-none focus:ring-2 focus:ring-cyan-300 p-1"
                                aria-label="Close modal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
 
                    {{-- Modal body --}}
                    <div class="px-6 py-5 space-y-5">
 
                        {{-- Alert area (hidden until needed) --}}
                        <div id="bosyModalAlert" class="hidden rounded-lg px-4 py-3 text-sm font-medium"></div>
 
                        {{-- Calendar Year --}}
                        <div>
                            <label for="bosyCalendarYear"
                                   class="block text-sm font-medium text-gray-700 mb-1.5">
                                Calendar Year
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                id="bosyCalendarYear"
                                min="2000" max="2100"
                                value="{{ $bosySettings->calendar_year }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
                                       text-gray-800 shadow-sm
                                       focus:border-cyan-400 focus:ring-2 focus:ring-cyan-200
                                       focus:outline-none transition"
                                placeholder="e.g. 2025"
                            >
                        </div>
 
                        {{-- Period Start --}}
                        <div>
                            <label for="bosyPeriodStart"
                                   class="block text-sm font-medium text-gray-700 mb-1.5">
                                Period Start
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="bosyPeriodStart"
                                value="{{ $bosySettings->period_start ? $bosySettings->period_start->format('Y-m-d') : '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
                                       text-gray-800 shadow-sm
                                       focus:border-cyan-400 focus:ring-2 focus:ring-cyan-200
                                       focus:outline-none transition"
                            >
                        </div>
 
                        {{-- Period End --}}
                        <div>
                            <label for="bosyPeriodEnd"
                                   class="block text-sm font-medium text-gray-700 mb-1.5">
                                Period End
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="bosyPeriodEnd"
                                value="{{ $bosySettings->period_end ? $bosySettings->period_end->format('Y-m-d') : '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
                                       text-gray-800 shadow-sm
                                       focus:border-cyan-400 focus:ring-2 focus:ring-cyan-200
                                       focus:outline-none transition"
                            >
                        </div>
 
                        {{-- Optional custom label --}}
                        <div>
                            <label for="bosyPeriodLabel"
                                   class="block text-sm font-medium text-gray-700 mb-1.5">
                                Custom Period Label
                                <span class="text-xs text-gray-400 font-normal">(optional — auto-generated if blank)</span>
                            </label>
                            <input
                                type="text"
                                id="bosyPeriodLabel"
                                maxlength="60"
                                value="{{ $bosySettings->period_label ?? '' }}"
                                placeholder="e.g. 05 June – 25 Dec"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
                                       text-gray-800 shadow-sm
                                       focus:border-cyan-400 focus:ring-2 focus:ring-cyan-200
                                       focus:outline-none transition"
                            >
                        </div>
 
                        {{-- Info note --}}
                        <p class="text-xs text-gray-500 bg-amber-50 border border-amber-200
                                  rounded-lg px-3 py-2.5 leading-relaxed">
                            <span class="font-semibold text-amber-700">⚠ Regional Account only.</span>
                            This update is global — the new period and calendar year will be displayed
                            to <strong>all stations</strong> on their next page load.
                        </p>
                    </div>
 
                    {{-- Modal footer --}}
                    <div class="flex justify-end gap-3 px-6 py-4
                                border-t border-gray-100 bg-gray-50/60">
                        <button
                            type="button"
                            id="cancelBosyModalBtn"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600
                                   bg-white border border-gray-300 hover:bg-gray-50
                                   focus:outline-none focus:ring-2 focus:ring-gray-200 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            id="saveBosySettingsBtn"
                            class="px-5 py-2 rounded-lg text-sm font-semibold text-white
                                   bg-cyan-600 hover:bg-cyan-700
                                   focus:outline-none focus:ring-2 focus:ring-cyan-400 transition
                                   disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            {{-- Spinner (hidden by default) --}}
                            <svg id="bosySaveSpinner"
                                 class="hidden animate-spin w-4 h-4 text-white"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span id="bosySaveBtnLabel">Save Changes</span>
                        </button>
                    </div>
                </div>
            </div>
            @endif
            {{-- ── End Modal ── --}}
 
            {{-- ── BOSY Search Bar ── --}}
            <div class="bosy-search-wrapper mb-4">
                <div class="bosy-search-bar">
                    <svg class="bosy-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input
                        type="text"
                        id="bosySearchInput"
                        placeholder="Search station name..."
                        class="bosy-search-input"
                        autocomplete="off"
                    >
                    {{-- Clear button is inside the bar so it stays right-anchored at every screen width --}}
                    <button type="button" id="bosySearchClear"
                            class="bosy-search-clear hidden" title="Clear search">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <span id="bosySearchCount" class="bosy-search-count hidden"></span>
                </div>
            </div>
 
            {{-- ── Scroll Wrapper ── --}}
            <div class="bosy-x-scroll-wrapper">
                <div id="bosy-divisions-container"
                     data-can-edit-school-nec="{{ $canEditSchoolNec ? '1' : '0' }}"
                     data-school-nec-update-base="{{ url('/dashboard/bosy-schools') }}"
                     class="bosy-scroll-container min-h-[200px] min-w-[320px] sm:min-w-[480px] pr-1 space-y-1">
                    @include('pages.partials.bosy-skeleton')
                </div>
            </div>
 
            {{-- ── Notes ── --}}
            <div class="mt-8 pt-6 border-t border-gray-200
                        text-sm text-gray-600 leading-relaxed space-y-3">
                @if ($userLevel === 1)
                    <p>
                        <span class="font-bold text-red-600">Note:</span>
                        <span class="font-semibold text-gray-800"> Status</span>
                        = Progress based on total LR vs estimated resources. BOSY / EOSY is set by the Regional Account.
                        It automatically RESETS ALL to 0 when period changes. Finalized data from past period becomes permanent.
                    </p>
                @else
                    <p>
                        <span class="font-bold text-red-600">Note:</span>
                        <span class="font-semibold text-gray-800"> Net Expected Count (NEC)</span>
                        = Population &times; Number of Grade Offerings &times; Number of Subject Area. This serves as the default projected inventory count and remains in effect until validated by the Supply Officer and LRMS personnel to reflect the actual inventory, resulting in 100% Inventory Status.
                    </p>
                @endif
            </div>
 
        </x-chart-card>

        @if($canEditSchoolNec)
            <div id="schoolNecModal"
                 class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4"
                 role="dialog" aria-modal="true" aria-labelledby="schoolNecModalTitle">
                <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-indigo-50/60 px-5 py-4">
                        <div>
                            <h2 id="schoolNecModalTitle" class="font-semibold text-gray-800">Validate School NEC</h2>
                            <p id="schoolNecSchoolName" class="mt-0.5 text-xs text-gray-500"></p>
                        </div>
                        <button type="button" id="closeSchoolNecModalBtn"
                                class="rounded-lg p-1.5 text-gray-500 hover:bg-white hover:text-gray-700"
                                aria-label="Close NEC editor">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="schoolNecForm" class="space-y-4 p-5">
                        <div>
                            <label for="schoolNecInput" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Validated Net Expected Count
                            </label>
                            <input type="number" id="schoolNecInput" name="estimated_resource"
                                   min="0" max="2147483647" step="1" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <p class="mt-1.5 text-xs text-gray-500">
                                Enter 0 to remove the validated value and return to the computed NEC.
                            </p>
                        </div>

                        <div id="schoolNecAlert" class="hidden rounded-lg px-3 py-2 text-sm"></div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <button type="button" id="cancelSchoolNecModalBtn"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" id="saveSchoolNecBtn"
                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                Save NEC
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if($userLevel >= 4)
<script>
(function () {
    'use strict';
 
    // ── Element refs ──────────────────────────────────────────────────────────
    const openBtn       = document.getElementById('openBosySettingsBtn');
    const modal         = document.getElementById('bosySettingsModal');
    const closeBtn      = document.getElementById('closeBosyModalBtn');
    const cancelBtn     = document.getElementById('cancelBosyModalBtn');
    const saveBtn       = document.getElementById('saveBosySettingsBtn');
    const spinner       = document.getElementById('bosySaveSpinner');
    const saveBtnLabel  = document.getElementById('bosySaveBtnLabel');
    const alertBox      = document.getElementById('bosyModalAlert');
 
    const yearInput     = document.getElementById('bosyCalendarYear');
    const startInput    = document.getElementById('bosyPeriodStart');
    const endInput      = document.getElementById('bosyPeriodEnd');
    const labelInput    = document.getElementById('bosyPeriodLabel');
 
    // ── Display refs (in the card header) ────────────────────────────────────
    const yearDisplay   = document.getElementById('bosyYearDisplay');
    const periodDisplay = document.getElementById('bosyPeriodDisplay');
 
    // ── CSRF token (Laravel standard) ────────────────────────────────────────
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
 
    // ── Helpers ───────────────────────────────────────────────────────────────
 
    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        hideAlert();
        setSaving(false);
    }
 
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        hideAlert();
    }
 
    function showAlert(message, type = 'error') {
        alertBox.textContent = message;
        alertBox.className = [
            'rounded-lg px-4 py-3 text-sm font-medium',
            type === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700',
        ].join(' ');
        alertBox.classList.remove('hidden');
    }
 
    function hideAlert() {
        alertBox.classList.add('hidden');
        alertBox.textContent = '';
    }
 
    function setSaving(isSaving) {
        saveBtn.disabled = isSaving;
        spinner.classList.toggle('hidden', !isSaving);
        saveBtnLabel.textContent = isSaving ? 'Saving…' : 'Save Changes';
    }
 
    // ── Open / Close ──────────────────────────────────────────────────────────
 
    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
 
    // Close on backdrop click
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
 
    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
 
    // ── Save ──────────────────────────────────────────────────────────────────
 
    saveBtn.addEventListener('click', async function () {
        hideAlert();
 
        // ── Client-side validation ────────────────────────────────────────
        const year  = parseInt(yearInput.value, 10);
        const start = startInput.value;
        const end   = endInput.value;
        const label = labelInput.value.trim();
 
        if (!year || year < 2000 || year > 2100) {
            showAlert('Please enter a valid calendar year between 2000 and 2100.');
            yearInput.focus();
            return;
        }
        if (!start) {
            showAlert('Please select a period start date.');
            startInput.focus();
            return;
        }
        if (!end) {
            showAlert('Please select a period end date.');
            endInput.focus();
            return;
        }
        if (end < start) {
            showAlert('Period end date must be on or after the start date.');
            endInput.focus();
            return;
        }
 
        // ── POST to backend ───────────────────────────────────────────────
        setSaving(true);
 
        try {
            const response = await fetch('{{ route("dashboard.bosy-settings.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    calendar_year: year,
                    period_start:  start,
                    period_end:    end,
                    period_label:  label || null,
                }),
            });
 
            const data = await response.json();
 
            if (!response.ok) {
                // Show Laravel validation errors if present
                if (data.errors) {
                    const messages = Object.values(data.errors).flat().join(' ');
                    showAlert(messages);
                } else {
                    showAlert(data.error ?? data.message ?? 'Failed to save. Please try again.');
                }
                setSaving(false);
                return;
            }
 
            // ── Update header display immediately ─────────────────────
            yearDisplay.textContent   = `CY ${data.calendar_year}`;
            periodDisplay.textContent = data.period_display;
 
            showAlert('BOSY settings updated successfully.', 'success');
 
            // Auto-close after a short delay so the user sees the toast
            setTimeout(closeModal, 1500);
 
        } catch (err) {
            console.error('BOSY settings update error:', err);
            showAlert('A network error occurred. Please check your connection and try again.');
        } finally {
            setSaving(false);
        }
    });
 
})();
</script>
@endif
    </div>
@endsection