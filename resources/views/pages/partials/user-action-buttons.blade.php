{{-- resources/views/pages/partials/user-action-buttons.blade.php --}}
@unless($hideActions ?? false)
<div class="flex items-center gap-1">
    @if($user->status === 'pending')
        <form
            hx-patch="{{ route('users.updateStatus', $user->id) }}"
            hx-target="#user-row-{{ $user->id }}"
            hx-swap="outerHTML"
            hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
        >
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="active">
            <button type="submit" class="p-1 rounded-lg bg-green-100 text-green-700 hover:bg-green-200" title="Approve">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </form>
    @elseif($user->status === 'active')
        <form
            hx-patch="{{ route('users.updateStatus', $user->id) }}"
            hx-target="#user-row-{{ $user->id }}"
            hx-swap="outerHTML"
            hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
        >
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="deactivated">
            <button type="submit" class="p-1 rounded-lg bg-red-100 text-red-700 hover:bg-red-200" title="Deactivate">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </form>
    @elseif($user->status === 'deactivated')
        <form
            hx-patch="{{ route('users.updateStatus', $user->id) }}"
            hx-target="#user-row-{{ $user->id }}"
            hx-swap="outerHTML"
            hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
        >
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="active">
            <button type="submit" class="p-1 rounded-lg bg-green-100 text-green-700 hover:bg-green-200" title="Reactivate">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </form>
    @endif

    <!-- Change Password Button (shown for all users) - FIXED VERSION -->
    <button 
        onclick="openChangePasswordModal('{{ $user->id }}', '{{ addslashes($user->firstname) }} {{ addslashes($user->lastname) }}')"
        class="p-1 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200"
        title="Change Password"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
    </button>
</div>
@endunless