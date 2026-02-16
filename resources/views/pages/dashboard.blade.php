@extends('pages.layout.layout')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', 'Dashboard overview of your Learning Resource Management System')
@section('breadcrumb', 'Dashboard')

@section('content')

<div class="p-6 space-y-6">
    <!-- ================= HEADER ================= -->
    @include('pages.partials.page-header')

    <!-- ================= SUMMARY CARDS ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 lg:gap-6">

        <!-- Total Learning Resources -->
        <div class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">

            <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                <div class="min-w-0 flex-1 space-y-0.5">
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">Total Learning Resources</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                        {{ number_format($totalLrData['total'] ?? 0) }}
                    </p>
                </div>
                <div class="bg-blue-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
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
                        <div class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                            {{ $label }}
                        </div>
                        <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div> <!-- small arrow -->
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Total Population (same pattern - icon size & tooltip style) -->
        <div class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">
            <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                <div class="min-w-0 flex-1 space-y-0.5">
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">Total Population</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                        {{ number_format($populationData['total'] ?? 0) }}
                    </p>
                </div>
                <div class="bg-green-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
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
                        <div class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                            {{ $label }}
                        </div>
                        <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Overall Ratio (same tooltip & icon style) -->
        <div class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">
            <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                <div class="min-w-0 flex-1 space-y-0.5">
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">Overall Ratio</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight break-all">
                        {{ $overallRatioData['ratio_display'] ?? '—' }}
                    </p>
                </div>
                <div class="bg-yellow-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
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
                        <div class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                            {{ $label }}
                        </div>
                        <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- LR Needs – now matches the hover behavior & reliability of the other cards -->
        <div class="bg-gradient-to-br from-blue-50/70 to-cyan-50/50 rounded-xl shadow-sm hover:shadow transition-shadow duration-200 p-4 sm:p-5 min-h-[152px] flex flex-col">

            <div class="flex items-start justify-between gap-3 mb-3 sm:mb-4">
                <div class="min-w-0 flex-1 space-y-0.5">
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">LR Needs</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800 tracking-tight">
                        {{ number_format($lrNeedsData['total_needs'] ?? 0) }}
                    </p>
                </div>
                <div class="bg-red-100/80 p-2 sm:p-2.5 rounded-full flex-shrink-0">
                    <svg class="w-6 h-6 lg:w-7 lg:h-7 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
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
                            <div class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
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

    <!-- Dropdown filter -->
    <div class="relative max-w-[300px] mb-4">

        <!-- Floating label -->
        <label
            for="globalFilter"
            class="absolute left-3 -top-2 px-2 bg-gray-100 text-xs font-semibold text-gray-600 tracking-wide z-10">
            Visualization
        </label>

        <!-- Select -->
        <select
            id="globalFilter"
            class="w-full px-3 py-2 text-sm bg-gray-100 border border-black rounded-lg
                focus:ring-2 focus:ring-indigo-400 focus:border-black
                hover:border-gray-700 transition appearance-none cursor-pointer pr-9">
            <option value="lr-availability">Subject Level LR Availability</option>
            <option value="lr-ratio">Learning Resources to Learner Ratio</option>
            <option value="lr-exdef">Excess / Deficiency</option>
            <option value="lr-heatmap">Equitable Distribution</option>
        </select>

        <!-- Arrow -->
        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </span>

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
            <p class="text-base font-bold text-gray-700">Excess | Deficiency</p>
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
            <p class="text-m text-500 mb-2 font-bold">Equitable Distribution</p>
            <div id="heatmap" class="w-full h-full"></div> <!-- ← subtract header height -->
        </div>
    </x-chart-card>

    <!-- Bosy Period -->
    <x-chart-card
        id="bosy-status"
        title="BOSY Status"
        class="chart-container">
        <!-- Period & CY Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-4">
            <div>
                <p class="text-xs text-gray-500">BOSY Period</p>
                <p class="text-lg font-semibold text-gray-800">05 June – 25 Dec</p>
            </div>

            <div class="text-left sm:text-right">
                <p class="text-xs text-gray-500">Calendar Year</p>
                <p class="text-2xl font-bold text-cyan-600">CY 2026</p>
            </div>
        </div>

        <!-- Status Items -->
        <div class="space-y-5">
            <!-- Albay -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 flex-shrink-0">
                    <img
                        src="https://upload.wikimedia.org/wikipedia/commons/3/38/Seal_of_Albay.svg"
                        alt="Albay"
                        class="w-full h-full rounded-full object-cover bg-white p-1 shadow-sm"
                        onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/2/20/Department_of_Education.svg'; this.alt='DepEd Default';">
                </div>
                <div class="flex-1 grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-3 sm:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Albay</h3>
                        <p class="text-sm text-gray-600">Feb 26 26</p>
                    </div>
                    <div class="col-span-5 sm:col-span-6">
                        <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                            <div class="bg-red-600 h-6 rounded-full" style="width: 10%"></div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <p class="text-sm font-medium text-gray-700">12,934</p>
                        <p class="text-sm font-bold text-gray-800">10%</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <span class="px-4 py-1.5 bg-gray-100 rounded-md text-sm font-semibold text-gray-800">Partial</span>
                    </div>
                </div>
            </div>

            <!-- Camarines Norte -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 flex-shrink-0">
                    <img
                        src="https://upload.wikimedia.org/wikipedia/commons/1/1f/CamNor_Seal.svg"
                        alt="Camarines Norte"
                        class="w-full h-full rounded-full object-cover bg-white p-1 shadow-sm"
                        onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/2/20/Department_of_Education.svg'; this.alt='DepEd Default';">
                </div>
                <div class="flex-1 grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-3 sm:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Camarines Norte</h3>
                        <p class="text-sm text-gray-600">Feb 26 26</p>
                    </div>
                    <div class="col-span-5 sm:col-span-6">
                        <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                            <div class="bg-green-600 h-6 rounded-full" style="width: 25%"></div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <p class="text-sm font-medium text-gray-700">223</p>
                        <p class="text-sm font-bold text-gray-800">25%</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <span class="px-4 py-1.5 bg-gray-100 rounded-md text-sm font-semibold text-gray-800">In-progress</span>
                    </div>
                </div>
            </div>

            <!-- Catanduanes -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 flex-shrink-0">
                    <img
                        src="https://upload.wikimedia.org/wikipedia/commons/1/18/Official_Seal_of_Catanduanes.svg"
                        alt="Catanduanes"
                        class="w-full h-full rounded-full object-cover bg-white p-1 shadow-sm"
                        onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/2/20/Department_of_Education.svg'; this.alt='DepEd Default';">
                </div>
                <div class="flex-1 grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-3 sm:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Catanduanes</h3>
                        <p class="text-sm text-gray-600">Feb 26 26</p>
                    </div>
                    <div class="col-span-5 sm:col-span-6">
                        <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                            <div class="bg-purple-500 h-6 rounded-full" style="width: 50%"></div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <p class="text-sm font-medium text-gray-700">100,000</p>
                        <p class="text-sm font-bold text-gray-800">50%</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <span class="px-4 py-1.5 bg-gray-100 rounded-md text-sm font-semibold text-gray-800">Advanced</span>
                    </div>
                </div>
            </div>

            <!-- Masbate City -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 flex-shrink-0">
                    <img
                        src="https://upload.wikimedia.org/wikipedia/commons/f/f9/Ph_seal_Masbate_City.png"
                        alt="Masbate City"
                        class="w-full h-full rounded-full object-cover bg-white p-1 shadow-sm"
                        onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/2/20/Department_of_Education.svg'; this.alt='DepEd Default';">
                </div>
                <div class="flex-1 grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-3 sm:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Masbate City</h3>
                        <p class="text-sm text-gray-600">Feb 26 26</p>
                    </div>
                    <div class="col-span-5 sm:col-span-6">
                        <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                            <div class="bg-yellow-500 h-6 rounded-full" style="width: 75%"></div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <p class="text-sm font-medium text-gray-700">4,234</p>
                        <p class="text-sm font-bold text-gray-800">75%</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <span class="px-4 py-1.5 bg-gray-100 rounded-md text-sm font-semibold text-gray-800">In-review</span>
                    </div>
                </div>
            </div>

            <!-- Naga City -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 flex-shrink-0">
                    <img
                        src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Naga_City_CamSur_Seal.svg"
                        alt="Naga City"
                        class="w-full h-full rounded-full object-cover bg-white p-1 shadow-sm"
                        onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/2/20/Department_of_Education.svg'; this.alt='DepEd Default';">
                </div>
                <div class="flex-1 grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-3 sm:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Naga City</h3>
                        <p class="text-sm text-gray-600">Feb 26 26</p>
                    </div>
                    <div class="col-span-5 sm:col-span-6">
                        <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                            <div class="bg-emerald-500 h-6 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <p class="text-sm font-medium text-gray-700">12,934</p>
                        <p class="text-sm font-bold text-gray-800">100%</p>
                    </div>
                    <div class="col-span-2 text-right">
                        <span class="px-4 py-1.5 bg-gray-100 rounded-md text-sm font-semibold text-gray-800">Complete</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="mt-6 pt-6 border-t border-gray-200 text-sm space-y-2 text-gray-600">
            <p>
                <span class="text-red-600 font-bold">Note:</span>
                <span class="font-bold text-gray-800"> Status</span> = average of status of all schools under each division.
            </p>
            <p>
                <span class="font-bold text-gray-800">BOSY / EOSY</span> is to be set by the Regional Account. It
                <span class="font-bold text-gray-800"> automatically RESETS ALL to 0 Status</span> based on saved dates for every period.
            </p>
            <p>
                <span class="font-bold text-gray-800">ALL DATA</span> from past period finalizes and flagged as
                <span class="font-bold text-gray-800"> permanent</span>.
            </p>
        </div>
    </x-chart-card>
</div>
@endsection