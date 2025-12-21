@extends('pages.layout.layout')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('content')
<div class="p-6 space-y-6">
    <!-- ================= HEADER ================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Welcome, Admin</h1>
            <p class="text-gray-500 text-sm mt-1">Dashboard overview of your Learning Resource Management System</p>
        </div>
        <div class="text-sm text-gray-500">
            <nav class="flex gap-1">
                <a href="#" class="hover:underline">Home</a> /
                <span>Dashboard</span>
            </nav>
        </div>
    </div>

    <!-- ================= SUMMARY CARDS ================= -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        <!-- Total Users -->
        <div class="bg-white rounded-xl shadow p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Total Users</p>
                <p class="text-xl font-semibold text-gray-800">120</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M16 14a4 4 0 0 0-8 0"/>
                    <circle cx="12" cy="8" r="4"/>
                </svg>
            </div>
        </div>

        <!-- Total Resources -->
        <div class="bg-white rounded-xl shadow p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Total Resources</p>
                <p class="text-xl font-semibold text-gray-800">350</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 20h9"/>
                    <path d="M12 4h9"/>
                    <path d="M4 4h4"/>
                    <path d="M4 12h4"/>
                    <path d="M4 20h4"/>
                </svg>
            </div>
        </div>

        <!-- Total Stations -->
        <div class="bg-white rounded-xl shadow p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Total Stations</p>
                <p class="text-xl font-semibold text-gray-800">8</p>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 12l2-2 4 4 8-8 4 4"/>
                </svg>
            </div>
        </div>

        <!-- Resources Pending Approval -->
        <div class="bg-white rounded-xl shadow p-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Pending Approval</p>
                <p class="text-xl font-semibold text-gray-800">15</p>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 8v4l3 3"/>
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
        </div>

    </div>

    <!-- ================= QUICK ACTIONS ================= -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">

        <a href="" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-4 flex flex-col items-center justify-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M16 14a4 4 0 0 0-8 0"/>
                <circle cx="12" cy="8" r="4"/>
            </svg>
            <span class="text-sm font-medium">Manage Users</span>
        </a>

        <a href="" class="bg-green-600 hover:bg-green-700 text-white rounded-xl p-4 flex flex-col items-center justify-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 20h9"/>
                <path d="M12 4h9"/>
                <path d="M4 4h4"/>
                <path d="M4 12h4"/>
                <path d="M4 20h4"/>
            </svg>
            <span class="text-sm font-medium">Manage Resources</span>
        </a>

        <a href="" class="bg-yellow-600 hover:bg-yellow-700 text-white rounded-xl p-4 flex flex-col items-center justify-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 12l2-2 4 4 8-8 4 4"/>
            </svg>
            <span class="text-sm font-medium">Manage Stations</span>
        </a>

        <a href="" class="bg-red-600 hover:bg-red-700 text-white rounded-xl p-4 flex flex-col items-center justify-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3"/>
                <circle cx="12" cy="12" r="10"/>
            </svg>
            <span class="text-sm font-medium">Pending Resources</span>
        </a>

    </div>

    <!-- ================= LATEST RESOURCES ================= -->
    <div class="bg-white rounded-xl shadow p-4 mt-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Latest Resources</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Station</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Uploaded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y">

                    @php
                        $resources = collect([
                            ['title'=>'Resource 1','type'=>'Print','station'=>'Station 1','status'=>'Approved','user'=>'Juan D.'],
                            ['title'=>'Resource 2','type'=>'Non-Print','station'=>'Station 2','status'=>'Pending','user'=>'Maria S.'],
                            ['title'=>'Resource 3','type'=>'Print','station'=>'Station III','status'=>'Approved','user'=>'Pedro R.'],
                        ]);
                    @endphp

                    @foreach ($resources as $resource)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $resource['title'] }}</td>
                        <td class="px-4 py-2">{{ $resource['type'] }}</td>
                        <td class="px-4 py-2">{{ $resource['station'] }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $resource['status'] === 'Approved'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $resource['status'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $resource['user'] }}</td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
