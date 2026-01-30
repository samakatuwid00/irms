@extends('public-layout.layout')

@section('title', 'Welcome to iRIMS-V')

@section('content')

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .card:nth-child(2) {
            animation-delay: 0.3s;
        }

        .card:nth-child(3) {
            animation-delay: 0.5s;
        }

        .card:hover .icon-container {
            animation: float 2s ease-in-out infinite;
        }

        .card:hover {
            transform: translateY(-8px);
        }

        .card {
            transition: all 0.3s ease;
        }
    </style>
    <div class="max-w-6xl w-full">
        <!-- Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <!-- Library System Card -->
            <div class="card bg-white rounded-2xl shadow-lg p-8 cursor-pointer hover:shadow-2xl">
                <div class="icon-container bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3 text-center">Library System</h2>
                <p class="text-gray-600 text-center mb-6">Manage books, members, and borrowing records efficiently</p>
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Access System
                </button>
            </div>

            <!-- Inventory Card -->
            <div class="card bg-white rounded-2xl shadow-lg p-8 cursor-pointer hover:shadow-2xl">
                <div class="icon-container bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3 text-center">Inventory</h2>
                <p class="text-gray-600 text-center mb-6">Track and manage your stock levels and supplies</p>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Access System
                </button>
            </div>

            <!-- Allocation System Card -->
            <div class="card bg-white rounded-2xl shadow-lg p-8 cursor-pointer hover:shadow-2xl">
                <div class="icon-container bg-purple-100 w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3 text-center">Allocation System</h2>
                <p class="text-gray-600 text-center mb-6">Optimize resource distribution and assignments</p>
                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Access System
                </button>
            </div>

        </div>
    </div>
@endsection
