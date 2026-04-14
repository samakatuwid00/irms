@extends('pages.layout.layout')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', 'Dashboard overview of your Learning Resource Management System')
@section('breadcrumb', 'Dashboard')

@section('content')

    <div class="p-6 space-y-6">
        <!-- ================= HEADER ================= -->
        @include('pages.partials.page-header')

        <!-- ================= SUMMARY CARDS ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 lg:gap-6">

            <!-- Total Learning Resources -->
            <div
                class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">

                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">Total Learning Resources</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                            {{ number_format($totalLrData['total'] ?? 0) }}
                        </p>
                    </div>
                    <div class="bg-blue-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
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
                class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">Total Population</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                            {{ number_format($populationData['total'] ?? 0) }}
                        </p>
                    </div>
                    <div class="bg-green-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
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
                class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">Overall Ratio</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight break-all">
                            {{ $overallRatioData['ratio_display'] ?? '—' }}
                        </p>
                    </div>
                    <div class="bg-yellow-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
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
                class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">

                <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <p class="text-xs sm:text-sm text-gray-500 font-medium">LR Needs</p>
                        <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                            {{ number_format($lrNeedsData['total_needs'] ?? 0) }}
                        </p>
                    </div>
                    <div class="bg-red-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
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
        class="w-full sm:flex-1 min-w-0"
    />

    <x-filter-select
        id="schoolYearFilter"
        label="Key Stages"
        :options="[
            ['value' => 'All', 'label' => 'All Key Stages', 'selected' => true],
            ['value' => 'K1', 'label' => 'Key Stage 1'],
            ['value' => 'K2', 'label' => 'Key Stage 2'],
            ['value' => 'JH', 'label' => 'Junior High'],
            ['value' => 'SH', 'label' => 'Senior High'],
        ]"
        class="w-full sm:flex-1 min-w-0"
    />

    <!-- Print Type Filter -->
    <x-filter-select
        id="printTypeFilter"
        label="Print Types"
        :options="$printTypeOptions"
        class="w-full sm:flex-1 min-w-0"
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
        @if($userLevel >= 4)
            <x-filter-select
                id="regionFilter"
                label="Region / Library Level"
                :options="$regionOptions"
            />
        @endif
        @if($userLevel == 3)
            <x-filter-select id="divisionFilter" label="District">
                @foreach($divisions as $district)
                    <option value="{{ $district['id'] }}">{{ $district['name'] }}</option>
                @endforeach
            </x-filter-select>
        @endif

        <x-chart-card id="bosy-status" title="BOSY Status" class="chart-container">

            <!-- Period & CY Header -->
            <div class="space-y-1.5">
                <p class="text-m font-bold mb-0">Inventory Status/Monitoring</p>
            </div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                
                <div>
                    <p class="text-xs font-medium tracking-wide text-gray-500">
                        PERIOD: Beginning-Of-School Year
                    </p>
                    <p class="text-lg font-semibold text-gray-900 period-display">
                        05 June – 25 Dec
                    </p>
                </div>

                <div class="text-left sm:text-right">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Calendar Year
                    </p>
                    <p class="text-2xl font-bold text-cyan-600 year-display">
                        CY {{ now()->month }}
                    </p>
                </div>
            </div>

            <!-- Scroll Wrapper -->
            <div class="overflow-x-auto overflow-y-hidden scroll-smooth pb-1 -mb-1">

                <!-- Scroll Container (max 5 items visible) -->
                <div id="bosy-divisions-container" class="max-h-[360px] overflow-y-auto overflow-x-visible scroll-smooth
                            min-h-[200px] min-w-[480px] pr-1 space-y-1">

                    <!-- Skeleton loaders -->
                    @include('pages.partials.bosy-skeleton')

                </div>

            </div>

            <!-- Notes Section -->
            <div class="mt-8 pt-6 border-t border-gray-200
                        text-sm text-gray-600 leading-relaxed space-y-3">

                <p>
                    <span class="font-bold text-red-600">Note:</span>
                    <span class="font-semibold text-gray-800"> Status</span>
                    = Progress based on total LR vs estimated resources. BOSY / EOSY is set by the Regional Account.
                    It automatically RESETS ALL to 0 when period changes. Finalized data from past period becomes permanent
                </p>
                
            </div>

        </x-chart-card>

    </div>
@endsection