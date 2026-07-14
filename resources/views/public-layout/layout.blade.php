<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Learning Resource Management System')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('lrmis.theme');
                var theme = stored === 'dark' || stored === 'light'
                    ? stored
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <!-- Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

<body class="bg-gradient-to-br from-[#A0F0FF] via-[#B0C8E0] to-[#FFB49A] text-gray-900 font-sans min-h-screen flex flex-col dark:from-slate-950 dark:via-slate-900 dark:to-slate-800 dark:text-slate-100">

    <div class="fixed right-4 top-4 z-50">
        <x-theme-toggle compact class="h-10 w-10 px-0" />
    </div>

    {{-- Page Content --}}
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        @yield('content')
    </main>

    @stack('scripts')
</body>


</html>
