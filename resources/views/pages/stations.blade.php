@extends('pages.layout.layout')

@section('title', 'Stations')

@section('page-title', 'Stations')

@section('content')
    @php
        $level = Auth::user()?->userType?->level;
    @endphp

    @if (Auth::check() && in_array($level, [2, 3, 4]))
        @if ($level === 4)
            @include('pages.components.manage-division')
        @elseif ($level === 3)
            @include('pages.components.manage-district-and-school')
        @elseif ($level === 2)
            @include('pages.components.manage-school')
        @endif
    @endif

@endsection
