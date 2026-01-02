<!-- ================= PAGE HEADER ================= -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            @yield('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
        </h1>

        <p class="text-gray-500 text-sm mt-1">
            @yield('header-subtitle')
        </p>
    </div>

    <div class="text-sm text-gray-500">
        <nav class="flex gap-1">
            <a href="{{ route('dashboard') }}" class="hover:underline">Home</a> /
            <span>@yield('breadcrumb')</span>
        </nav>
    </div>
</div>
