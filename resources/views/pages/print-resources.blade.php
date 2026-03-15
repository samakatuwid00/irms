@extends('pages.layout.layout')

@section('title', 'Print Resources')
@section('page-title', 'Print Resources')
@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Print Resource')
@section('content')

    @php
        $level = Auth::user()->userType?->level ?? 0;
    @endphp

    @include('pages.partials.page-header')

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

    <div id="print-resources-wrapper" class="space-y-4">

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

    @vite('resources/js/print-resources.js')

    @include('pages.modals.view-print-modal')
@endsection
