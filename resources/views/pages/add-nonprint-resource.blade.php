@extends('pages.layout.layout')

@section('title', 'Add Non-Print Resource')
@section('page-title', 'Add Non-Print Resource')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Add Non-Print Resource')

@section('content')
<div class="p-6 space-y-6">
    @include('pages.partials.page-header')

    <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-xl font-semibold mb-6">Add Non-Print Resource</h2>

        @include('pages.components.add-nonprint-resource')
    </div>
</div>

@vite(['resources/js/add-nonprint-resource.js'])
@endsection
