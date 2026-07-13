@extends('pages.layout.layout')

@section('title', 'Print Resources')
@section('page-title', 'Print Resources')
@section('header-title', 'List of Print Resources')
@section('header-subtitle', 'Browse and manage your print learning resources')
@section('breadcrumb', 'Print Resource')
@section('content')

    @php
        $level = Auth::user()->userType?->level ?? 0;
    @endphp

    <div class="space-y-4">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Print Resources</h1>
        </div>
    
    {{-- Hidden data attributes for JavaScript modules --}}
    <div id="print-resources-data"
         data-user-level="{{ $level }}"
         @if($level == 3 && isset($allSchools))
         data-all-schools="{{ json_encode($allSchools) }}"
         @endif
         @if($level == 4)
         data-all-districts="{{ json_encode($allDistricts ?? []) }}"
         data-all-schools="{{ json_encode($allSchools ?? []) }}"
         @endif
         style="display: none;">
    </div>

    <div id="print-resources-wrapper" class="space-y-4 sm:space-y-6">

        <!-- ===================================================== -->
        <!-- LEVEL 1: School account -->
        <!-- ===================================================== -->
        @if($level == 1)
            @include('pages.components.print-resource-school-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 2: District account -->
        <!-- ===================================================== -->
        @if($level == 2)
            @include('pages.components.print-resource-district-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 3: Division account -->
        <!-- ===================================================== -->
        @if($level == 3)
            @include('pages.components.print-resource-division-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 4: Region account - filter on demand          -->
        <!-- ===================================================== -->
        @if($level == 4)
            @include('pages.components.print-resource-region-account')
        @endif

    </div>

    </div>

    @include('pages.partials.resource-loading-skeleton', ['defaultView' => 'card'])

    <div id="print-export-progress"
         class="hidden fixed bottom-6 right-6 z-50 w-[calc(100%-3rem)] max-w-sm rounded-xl border border-gray-200 bg-white p-4 shadow-xl"
         role="status"
         aria-live="polite"
         aria-hidden="true">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-3">
                    <p id="print-export-title" class="text-sm font-semibold text-gray-800">Preparing Excel export</p>
                    <span id="print-export-percent" class="text-xs font-medium text-gray-500">0%</span>
                </div>
                <p id="print-export-message" class="mt-1 text-xs text-gray-500">
                    Please wait. Your download will start automatically.
                </p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200">
                    <div id="print-export-progress-bar"
                         class="h-full rounded-full bg-green-600 transition-all duration-300"
                         style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/print-resources.js')
    @vite('resources/js/print-resources-view-toggle.js')
    @include('pages.modals.view-print-modal')
@endsection
