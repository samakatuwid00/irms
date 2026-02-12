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

    <!-- Tab Content -->
    <div id="content-region" class="tab-content">
        <!-- ================= REGION USERS ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <h2 class="text-lg font-semibold mb-4">Region Users</h2>

            <form method="GET" action="{{ route('users') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">

                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Search Region Users</label>
                    <input type="text" name="search_main" value="{{ request('search_main') }}" placeholder="Search by name, username, or email..." class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm">
                </div>

                <!-- User Type -->
                <div>
                    <label class="text-xs text-gray-500">User Type</label>
                    <select name="usertype_main" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        @foreach (\App\Models\UserType::where('level', 4)->get() as $type)
                            <option value="{{ $type->id }}" {{ request('usertype_main') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status_main" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status_main') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status_main') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="deactivated" {{ request('status_main') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                    </select>
                </div>

                <!-- Hidden field to track active tab -->
                <input type="hidden" name="active_tab" value="region">

                <!-- Buttons -->
                <div class="md:col-span-4 flex justify-end gap-2">
                    <a href="{{ route('users') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Reset</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Apply Filters</button>
                </div>

            </form>

            <!-- Region Users Table -->
            <div class="overflow-x-auto bg-white rounded-xl shadow">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">User Type</th>
                            <th class="px-4 py-3">Station</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($mainUsers as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $user->firstname }} {{ $user->lastname }}</td>
                                <td class="px-4 py-3">{{ $user->username }}</td>
                                <td class="px-4 py-3">{{ $user->email }}</td>
                                <td class="px-4 py-3">{{ $user->usertype_name }}</td>
                                <td class="px-4 py-3">{{ $user->station_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs
                                        {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        @if($user->status === 'pending')
                                            <form method="POST" action="{{ route('users.updateStatus', $user->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">Approve</button>
                                            </form>
                                        @elseif($user->status === 'active')
                                            <form method="POST" action="{{ route('users.updateStatus', $user->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="deactivated">
                                                <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-red-100 text-red-700 hover:bg-red-200">Deactivate</button>
                                            </form>
                                        @elseif($user->status === 'deactivated')
                                            <form method="POST" action="{{ route('users.updateStatus', $user->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">Reactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-center text-gray-500">No region users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-4">
                {{ $mainUsers->links() }}
            </div>
        </div>
    </div>

    <div id="content-division" class="tab-content hidden">
        <!-- ================= DIVISION USERS ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <h2 class="text-lg font-semibold mb-4">Division Users</h2>

            <form method="GET" action="{{ route('users') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">

                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Search Division Users</label>
                    <input type="text" name="search_sub" value="{{ request('search_sub') }}" placeholder="Search by name, username, or email..." class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm">
                </div>

                <!-- User Type -->
                <div>
                    <label class="text-xs text-gray-500">User Type</label>
                    <select name="usertype_sub" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        @foreach (\App\Models\UserType::where('level', 3)->get() as $type)
                            <option value="{{ $type->id }}" {{ request('usertype_sub') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status_sub" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status_sub') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="pending" {{ request('status_sub') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="deactivated" {{ request('status_sub') === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                    </select>
                </div>

                <!-- Hidden field to track active tab -->
                <input type="hidden" name="active_tab" value="division">

                <!-- Buttons -->
                <div class="md:col-span-4 flex justify-end gap-2">
                    <a href="{{ route('users') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Reset</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Apply Filters</button>
                </div>

            </form>

            <!-- Division Users Table -->
            <div class="overflow-x-auto bg-white rounded-xl shadow">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">User Type</th>
                            <th class="px-4 py-3">Station</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($subUsers as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $user->firstname }} {{ $user->lastname }}</td>
                                <td class="px-4 py-3">{{ $user->username }}</td>
                                <td class="px-4 py-3">{{ $user->email }}</td>
                                <td class="px-4 py-3">{{ $user->usertype_name }}</td>
                                <td class="px-4 py-3">{{ $user->station_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs
                                        {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        @if($user->status === 'pending')
                                            <form method="POST" action="{{ route('users.updateStatus', $user->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">Approve</button>
                                            </form>
                                        @elseif($user->status === 'active')
                                            <form method="POST" action="{{ route('users.updateStatus', $user->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="deactivated">
                                                <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-red-100 text-red-700 hover:bg-red-200">Deactivate</button>
                                            </form>
                                        @elseif($user->status === 'deactivated')
                                            <form method="POST" action="{{ route('users.updateStatus', $user->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">Reactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-center text-gray-500">No division users found under this region.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-4">
                {{ $subUsers->links() }}
            </div>
        </div>
    </div>

</div>

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
    }

    // On page load, check if there's an active tab parameter
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('active_tab');

        if (activeTab === 'division') {
            switchTab('division');
        }
    });
</script>
