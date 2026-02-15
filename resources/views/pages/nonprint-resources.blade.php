@extends('pages.layout.layout')

@section('title', 'Non-Print Resources')
@section('page-title', 'Non-Print Resources')
@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Non-Print Resource')
@section('content')

    @php
        $level = Auth::user()->userType?->level ?? 0;
    @endphp

    @include('pages.partials.page-header')

    {{-- Hidden data attributes for JavaScript modules --}}
    <div id="nonprint-resources-data"
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

    <div class="space-y-4">

        {{-- <!-- ================= HEADER ================= -->
        <div class="flex justify-end items-center">
            @if($level == 1 || $level == 3)
                <a href="{{ route('add-resources') }}"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    + Add Resource
                </a>
            @endif
        </div> --}}

        <!-- ===================================================== -->
        <!-- LEVEL 1: School account -->
        <!-- ===================================================== -->
        @if($level == 1)
            @include('pages.components.nonprint-resource-school-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 2: District account -->
        <!-- ===================================================== -->
        @if($level == 2)
            @include('pages.components.nonprint-resource-district-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 3: Division account -->
        <!-- ===================================================== -->
        @if($level == 3)
            @include('pages.components.nonprint-resource-division-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 4: Region account - filter on demand          -->
        <!-- ===================================================== -->
        @if($level == 4)
            @include('pages.components.nonprint-resource-region-account')
        @endif

    </div>

    @vite('resources/js/nonprint-resources.js')

    @include('pages.modals.view-nonprint-modal')
@endsection
