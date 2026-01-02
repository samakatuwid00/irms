<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Learning Resource Management System')</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Alphine JS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Custom Styles -->
    <style>
        .bg-custom-yellow { background-color: #F3C623; }
        .hover\:bg-custom-yellow-hover:hover { background-color: #e6b81e; }
        .text-custom-teal { color: #127681; }
        .border-custom-teal { border-color: #127681; }

        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #127681;
        }
    </style>

    @stack('styles')
</head>

<body class="bg-white text-gray-800 font-sans min-h-screen flex flex-col">

    <header class="bg-white py-4">
        <div class="container mx-auto px-6 flex justify-center items-center">
            <div class="flex items-center space-x-4">
                <img src="{{ asset('assets/images/rov.png') }}" alt="Logo" class="h-16 w-16 rounded-full">
                <img src="{{ asset('assets/images/deped.png') }}" alt="Logo" class="h-16 w-16">
                <img src="{{ asset('assets/images/bp.png') }}" alt="Logo" class="h-16 w-16">
            </div>
        </div>
    </header>

    {{-- Page Content --}}
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        @yield('content')
    </main>
    <footer class="bg-white py-6 border-t">
        <div class="container mx-auto px-6 text-center text-sm text-gray-600">
            &copy; {{ date('Y') }} Learning Resource Management System. All rights reserved.
        </div>
    </footer>
    @stack('scripts')

</body>
</html>
