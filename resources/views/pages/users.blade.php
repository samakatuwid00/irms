@extends('pages.layout.layout')

@section('title', 'Users')

@section('page-title', 'USers')

@section('content')
    @php
        $level = Auth::user()?->userType?->level;
    @endphp

    @if (Auth::check() && in_array($level, [1, 2, 3, 4]))
        @if ($level === 4)
            @include('pages.components.region-user-table')
        @elseif ($level === 3)
            @include('pages.components.division-user-table')
        @elseif ($level === 2)
            @include('pages.components.district-user-table')
        @elseif ($level === 1)
            @include('pages.components.school-user-table')
        @endif
    @endif

@endsection
