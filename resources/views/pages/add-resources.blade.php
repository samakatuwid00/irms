@extends('pages.layout.layout')

@section('title', 'Add Resource')

@section('page-title', 'Add Resource')

@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Add Resource')

@section('content')
    <div class="p-6 space-y-6">
        <!-- ================= HEADER ================= -->
        @include('pages.partials.page-header')
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-xl font-semibold mb-4">Add Resource</h2>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button type="button"
                        class="resource-tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors
                               border-blue-600 text-blue-600"
                        data-tab="print">
                    Print Resource
                </button>
                <button type="button"
                        class="resource-tab-btn px-6 py-3 text-sm font-medium border-b-2 border-transparent
                               text-gray-600 hover:text-blue-600 hover:border-gray-300"
                        data-tab="nonprint">
                    Non-Print Resource
                </button>
            </div>

            <!-- Forms Container -->
            <div>
                <!-- Print Resource Form (Visible by default) -->
                <div id="print-form">
                    @include('pages.components.add-print-resource')
                </div>

                <!-- Non-Print Resource Form (Hidden by default) -->
                <div id="nonprint-form" class="hidden">
                    @include('pages.components.add-nonprint-resource')
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/add-resource-index.js'])
@endsection
