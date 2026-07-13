@extends('pages.layout.layout')

@section('title', 'Edit Resource')

@section('page-title', 'Edit Resource')

@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Edit Resource')

@section('content')
    <div class="-mx-3 space-y-4 sm:-mx-2 lg:mx-0">
        <!-- ================= HEADER ================= -->
        @include('pages.partials.page-header')
        <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <div class="bg-white shadow rounded-xl p-3 sm:p-4 lg:p-5">
            <h2 class="text-lg sm:text-xl font-semibold mb-4">Edit Resource</h2>

            <!-- Tabs -->
            <div class="flex overflow-x-auto border-b border-gray-200 mb-5 sm:mb-6">
                <button type="button"
                        class="resource-tab-btn shrink-0 px-4 sm:px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                        data-tab="print"
                        id="print-tab">
                    Print Resource
                </button>
                <button type="button"
                        class="resource-tab-btn shrink-0 px-4 sm:px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                        data-tab="nonprint"
                        id="nonprint-tab">
                    Non-Print Resource
                </button>
            </div>

            <!-- Forms Container -->
            <div class="min-w-0">
                <!-- Print Resource Form -->
                <div id="print-form" class="hidden min-w-0">
                    @include('pages.components.edit-print-resource')
                </div>

                <!-- Non-Print Resource Form -->
                <div id="nonprint-form" class="hidden min-w-0">
                    @include('pages.components.edit-nonprint-resource')
                </div>
            </div>
        </div>
    </div>

    {{-- ── Inject page-level data for the tab-switcher entry point ── --}}
    <script>
        window.__editResourcesData = {
            resourceId:          '{{ $printResource->id ?? $nonprintResource->id ?? "" }}',
            hasPrintResource:    {{ $printResource    ? 'true' : 'false' }},
            hasNonprintResource: {{ $nonprintResource ? 'true' : 'false' }},
            tabParam:            '{{ request()->query("tab", "") }}',
        };
    </script>

    @vite('resources/js/edit-resource-index.js')
@endsection
