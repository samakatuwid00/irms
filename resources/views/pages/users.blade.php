@extends('pages.layout.layout')

@section('title', 'Users')

@section('page-title', 'USers')

@section('content')
    <div class="p-6 space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h1 class="text-xl font-semibold text-gray-800">Users</h1>
        </div>

        <!-- ================= SEARCH ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" class="flex items-center gap-3">

                <div class="relative w-full">
                    <input type="text"
                        name="search"
                        placeholder="Search by name, username, or email..."
                        class="w-full pl-10 pr-3 py-2 border rounded-lg text-sm focus:ring focus:ring-blue-200">

                    <!-- Search Icon -->
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>

                <button type="submit"
                        class="p-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>

            </form>
        </div>

        <!-- ================= FILTERS ================= -->
        <div class="bg-white rounded-xl shadow p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- User Type -->
                <div>
                    <label class="text-xs text-gray-500">User Type</label>
                    <select name="usertype"
                            class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option>Admin</option>
                        <option>Librarian</option>
                        <option>User</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-xs text-gray-500">Status</label>
                    <select name="status"
                            class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>

                <!-- Station -->
                <div>
                    <label class="text-xs text-gray-500">Station</label>
                    <select name="station"
                            class="w-full mt-1 px-3 py-2 border rounded-lg text-sm">
                        <option value="">All</option>
                        <option>Station 1</option>
                        <option>Station 2</option>
                        <option>Station III</option>
                    </select>
                </div>

                <!-- Apply -->
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                        Apply Filters
                    </button>
                </div>

            </form>
        </div>

        <!-- ================= TABLE ================= -->
        <div class="bg-white rounded-xl shadow overflow-hidden">

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

                    @php
                        $users = collect([
                            [
                                'name'=>'Juan Dela Cruz',
                                'username'=>'jdelacruz',
                                'email'=>'juan.delacruz@email.com',
                                'type'=>'Admin',
                                'station'=>'Station 1',
                                'status'=>'Active'
                            ],
                            [
                                'name'=>'Maria Santos',
                                'username'=>'msantos',
                                'email'=>'maria.santos@email.com',
                                'type'=>'Librarian',
                                'station'=>'Station 2',
                                'status'=>'Active'
                            ],
                            [
                                'name'=>'Pedro Reyes',
                                'username'=>'preyes',
                                'email'=>'pedro.reyes@email.com',
                                'type'=>'User',
                                'station'=>'Station III',
                                'status'=>'Inactive'
                            ],
                        ]);
                    @endphp

                    @foreach ($users as $user)
                    <tr class="hover:bg-gray-50">

                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $user['name'] }}
                        </td>

                        <td class="px-4 py-3">{{ $user['username'] }}</td>

                        <td class="px-4 py-3">{{ $user['email'] }}</td>

                        <td class="px-4 py-3">{{ $user['type'] }}</td>

                        <td class="px-4 py-3">{{ $user['station'] }}</td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $user['status'] === 'Active'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-gray-200 text-gray-700' }}">
                                {{ $user['status'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-2">

                                <!-- View -->
                                <a href="#"
                                class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200">
                                    View
                                </a>

                                <!-- Send Email -->
                                <a href="mailto:{{ $user['email'] }}"
                                class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">
                                    Send Email
                                </a>

                            </div>
                        </td>

                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>

        <!-- ================= PAGINATION ================= -->
        <div class="flex justify-end">
            <nav class="inline-flex items-center gap-1 text-sm">
                <a href="#" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</a>
                <span class="px-3 py-1 bg-blue-600 text-white rounded">1</span>
                <a href="#" class="px-3 py-1 border rounded hover:bg-gray-100">2</a>
                <a href="#" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a>
            </nav>
        </div>

    </div>
@endsection
