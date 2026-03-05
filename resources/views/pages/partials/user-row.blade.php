<tr class="hover:bg-gray-50" id="user-row-{{ $user->id }}">
    <td class="px-4 py-3 font-medium text-gray-800">{{ $user->firstname }} {{ $user->lastname }}</td>
    <td class="px-4 py-3">{{ $user->username }}</td>
    <td class="px-4 py-3">{{ $user->email }}</td>
    <td class="px-4 py-3">{{ $user->usertype_name }}</td>
    <td class="px-4 py-3">{{ $user->station_name ?? '-' }}</td>
    <td class="px-4 py-3">
        <span class="px-2 py-1 rounded-full text-xs {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
            {{ ucfirst($user->status) }}
        </span>
    </td>
    <td class="px-4 py-3 text-center">
        <div class="flex justify-center gap-2">
            @include('pages.partials.user-action-buttons', ['user' => $user])
        </div>
    </td>
</tr>