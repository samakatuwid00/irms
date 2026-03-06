<div class="p-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
    </div>

    <!-- SCHOOL USERS -->
    <div class="bg-white rounded-xl shadow p-4">
        <h2 class="text-lg font-semibold mb-4">School Users</h2>
        <form
            hx-get="{{ route('users') }}"
            hx-target="#main-table-container"
            hx-swap="innerHTML"
            hx-push-url="true"
            hx-indicator="#main-spinner"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4"
        >
            <input type="hidden" name="active_tab" value="main">
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Search School Users</label>
                <input type="text" name="search_main" value="{{ request('search_main') }}"
                    placeholder="Search by name, username, or email..."
                    class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm"
                    hx-get="{{ route('users') }}"
                    hx-target="#main-table-container"
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
                    hx-target="#main-table-container"
                    hx-swap="innerHTML"
                    hx-trigger="change"
                    hx-include="closest form"
                    hx-push-url="true">
                    <option value="">All</option>
                    @foreach (\App\Models\UserType::where('level', 1)->get() as $type)
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
                    hx-target="#main-table-container"
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
                <a href="{{ route('users') }}?active_tab=main"
                    hx-get="{{ route('users') }}?active_tab=main"
                    hx-target="#main-table-container"
                    hx-swap="innerHTML"
                    hx-push-url="true"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Reset</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Apply Filters</button>
            </div>
        </form>

        <!-- Loading spinner -->
        <div id="main-spinner" class="htmx-indicator flex justify-center py-4">
            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </div>

        <!-- Table target -->
        <div id="main-table-container">
            @include('pages.partials.users-table', [
                'users' => $mainUsers,
                'pageName' => 'main_page',
                'emptyMessage' => 'No school users found.',
                'activeTab' => 'main'
            ])
        </div>
    </div>

</div>

<style>
    .htmx-indicator { display: none; }
    .htmx-request .htmx-indicator { display: flex; }
    .htmx-request.htmx-indicator { display: flex; }
</style>