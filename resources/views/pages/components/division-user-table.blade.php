<div class="p-6 space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
    </div>

    <!-- ================= DIVISION USERS ================= -->
    <div class="bg-white rounded-xl shadow p-4">
        <h2 class="text-lg font-semibold mb-4">Division Users</h2>

        <form method="GET" action="{{ route('users') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">

            <!-- Search -->
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Search Division Users</label>
                <input type="text" name="search_main" value="{{ request('search_main') }}" placeholder="Search by name, username, or email..." class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm">
            </div>

            <!-- User Type -->
            <div>
                <label class="text-xs text-gray-500">User Type</label>
                <select name="usertype_main" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                    <option value="">All</option>
                    @foreach (\App\Models\UserType::where('level', 3)->get() as $type)
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

            <!-- Preserve sub filters -->
            <input type="hidden" name="search_sub" value="{{ request('search_sub') }}">
            <input type="hidden" name="usertype_sub" value="{{ request('usertype_sub') }}">
            <input type="hidden" name="status_sub" value="{{ request('status_sub') }}">

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
                    @forelse ($mainUsers as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $user->firstname }} {{ $user->lastname }}</td>
                            <td class="px-4 py-3">{{ $user->username }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->usertype_name }}</td>
                            <td class="px-4 py-3">{{ $user->station?->station_name ?? '-' }}</td>
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
                            <td colspan="7" class="px-4 py-3 text-center text-gray-500">No division users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            {{ $mainUsers->links() }}
        </div>
    </div>

    <!-- ================= DISTRICT USERS UNDER DIVISION ================= -->
    <div class="bg-white rounded-xl shadow p-4">
        <h2 class="text-lg font-semibold mb-4">District Users Under Division</h2>

        <form method="GET" action="{{ route('users') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">

            <!-- Search -->
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Search District Users</label>
                <input type="text" name="search_sub" value="{{ request('search_sub') }}" placeholder="Search by name, username, or email..." class="w-full pl-3 pr-3 py-2 border rounded-lg text-sm">
            </div>

            <!-- User Type -->
            <div>
                <label class="text-xs text-gray-500">User Type</label>
                <select name="usertype_sub" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                    <option value="">All</option>
                    @foreach (\App\Models\UserType::where('level', 2)->get() as $type)
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

            <!-- Preserve main filters -->
            <input type="hidden" name="search_main" value="{{ request('search_main') }}">
            <input type="hidden" name="usertype_main" value="{{ request('usertype_main') }}">
            <input type="hidden" name="status_main" value="{{ request('status_main') }}">

            <!-- Buttons -->
            <div class="md:col-span-4 flex justify-end gap-2">
                <a href="{{ route('users') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">Reset</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Apply Filters</button>
            </div>

        </form>

        <!-- District Users Table -->
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
                            <td class="px-4 py-3">{{ $user->station?->station_name ?? '-' }}</td>
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
                            <td colspan="7" class="px-4 py-3 text-center text-gray-500">No district users found under this division.</td>
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
