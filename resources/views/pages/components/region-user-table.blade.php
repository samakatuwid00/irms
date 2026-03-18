<div class="p-6 space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button
                onclick="switchTab('region')"
                id="tab-region"
                class="tab-button border-b-2 border-blue-600 text-blue-600 py-4 px-1 text-sm font-medium">
                Region Users
            </button>
            <button
                onclick="switchTab('division')"
                id="tab-division"
                class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium">
                Division Users
            </button>
        </nav>
    </div>

    <!-- REGION TAB -->
    <div id="content-region" class="tab-content">
        <div class="bg-white rounded-xl shadow p-4">            
            <form
                hx-get="{{ route('users') }}"
                hx-target="#region-table-container"
                hx-swap="innerHTML"
                hx-push-url="true"
                hx-indicator="#region-spinner"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4"
            >
                <input type="hidden" name="active_tab" value="main">
                
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Search Region Users</label>
                    <input type="text" name="search_main" value="{{ request('search_main') }}"
                        placeholder="Search by name, username, or email..."
                        class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm"
                        hx-get="{{ route('users') }}"
                        hx-target="#region-table-container"
                        hx-swap="innerHTML"
                        hx-trigger="keyup changed delay:400ms"
                        hx-include="closest form"
                        hx-push-url="true">
                </div>
                
                <div>
                    <label class="text-xs text-gray-500">User Type</label>
                    <select name="usertype_main"
                        class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"
                        hx-get="{{ route('users') }}"
                        hx-target="#region-table-container"
                        hx-swap="innerHTML"
                        hx-trigger="change"
                        hx-include="closest form"
                        hx-push-url="true">
                        <option value="">All</option>
                        @foreach (\App\Models\UserType::where('level', 4)->get() as $type)
                            <option value="{{ $type->id }}" {{ request('usertype_main') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status_main"
                        class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"
                        hx-get="{{ route('users') }}"
                        hx-target="#region-table-container"
                        hx-swap="innerHTML"
                        hx-trigger="change"
                        hx-include="closest form"
                        hx-push-url="true">
                        <option value="">All</option>
                        <option value="active" {{ request('status_main') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status_main') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="deactivated" {{ request('status_main') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                    </select>
                </div>
                
                <div class="md:col-span-4 flex justify-end gap-2">
                    <a href="{{ route('users') }}?active_tab=region"
                        hx-get="{{ route('users') }}?active_tab=region"
                        hx-target="#region-table-container"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Reset</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Apply Filters</button>
                </div>
            </form>

            <!-- Loading spinner -->
            <div class="relative">
                <div id="region-spinner" class="htmx-indicator absolute inset-0 z-10 flex items-center justify-center bg-white/60 rounded-lg">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>

                <!-- Table target -->
                <div id="region-table-container">
                    @include('pages.partials.users-table', [
                        'users' => $mainUsers,
                        'emptyMessage' => 'No region users found.',
                        'activeTab' => 'region'
                    ])
                </div>
            </div>
        </div>
    </div>

    <!-- DIVISION TAB -->
    <div id="content-division" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow p-4">            
            <form
                hx-get="{{ route('users') }}"
                hx-target="#division-table-container"
                hx-swap="innerHTML"
                hx-push-url="true"
                hx-indicator="#division-spinner"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4"
            >
                <input type="hidden" name="active_tab" value="sub">
                
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Search Division Users</label>
                    <input type="text" name="search_sub" value="{{ request('search_sub') }}"
                        placeholder="Search by name, username, or email..."
                        class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm"
                        hx-get="{{ route('users') }}"
                        hx-target="#division-table-container"
                        hx-swap="innerHTML"
                        hx-trigger="keyup changed delay:400ms"
                        hx-include="closest form"
                        hx-push-url="true">
                </div>
                
                <div>
                    <label class="text-xs text-gray-500">User Type</label>
                    <select name="usertype_sub"
                        class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"
                        hx-get="{{ route('users') }}"
                        hx-target="#division-table-container"
                        hx-swap="innerHTML"
                        hx-trigger="change"
                        hx-include="closest form"
                        hx-push-url="true">
                        <option value="">All</option>
                        @foreach (\App\Models\UserType::where('level', 3)->get() as $type)
                            <option value="{{ $type->id }}" {{ request('usertype_sub') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status_sub"
                        class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"
                        hx-get="{{ route('users') }}"
                        hx-target="#division-table-container"
                        hx-swap="innerHTML"
                        hx-trigger="change"
                        hx-include="closest form"
                        hx-push-url="true">
                        <option value="">All</option>
                        <option value="active" {{ request('status_sub') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status_sub') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="deactivated" {{ request('status_sub') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                    </select>
                </div>
                
                <div class="md:col-span-4 flex justify-end gap-2">
                    <a href="{{ route('users') }}?active_tab=division"
                        hx-get="{{ route('users') }}?active_tab=division"
                        hx-target="#division-table-container"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Reset</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Apply Filters</button>
                </div>
            </form>

            <div class="relative">
                <!-- Loading spinner -->
                <div id="division-spinner" class="htmx-indicator absolute inset-0 z-10 flex items-center justify-center bg-white/60 rounded-lg">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>

                <!-- Table target -->
                <div id="division-table-container">
                    @include('pages.partials.users-table', [
                        'users' => $subUsers,
                        'emptyMessage' => 'No division users found under this region.',
                        'activeTab' => 'division'
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- change password -->
<x-change-password-modal />

<script>
    function switchTab(tab) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remove active styles from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-blue-600', 'text-blue-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });

        // Show selected tab content
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Add active styles to selected tab
        const activeTab = document.getElementById('tab-' + tab);
        activeTab.classList.add('border-blue-600', 'text-blue-600');
        activeTab.classList.remove('border-transparent', 'text-gray-500');

        // Update URL with active tab parameter without page reload
        const url = new URL(window.location);
        url.searchParams.set('active_tab', tab);
        window.history.pushState({}, '', url);
    }

    // On page load, check if there's an active tab parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('active_tab');

        if (activeTab && ['region', 'division'].includes(activeTab)) {
            switchTab(activeTab);
        } else {
            // Default to region tab if no parameter
            switchTab('region');
        }
    });
</script>

<style>
    .htmx-indicator { display: none; }
    .htmx-request .htmx-indicator { display: flex; }
    .htmx-request.htmx-indicator { display: flex; }
</style>