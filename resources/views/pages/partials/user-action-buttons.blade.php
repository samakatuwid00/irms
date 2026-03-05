{{-- resources/views/pages/partials/user-action-buttons.blade.php --}}
@if($user->status === 'pending')
    <form
        hx-patch="{{ route('users.updateStatus', $user->id) }}"
        hx-target="#user-row-{{ $user->id }}"
        hx-swap="outerHTML"
        hx-vals='{"status": "active"}'
        hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
    >
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="active">
        <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">Approve</button>
    </form>
@elseif($user->status === 'active')
    <form
        hx-patch="{{ route('users.updateStatus', $user->id) }}"
        hx-target="#user-row-{{ $user->id }}"
        hx-swap="outerHTML"
        hx-vals='{"status": "deactivated"}'
        hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
    >
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="deactivated">
        <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-red-100 text-red-700 hover:bg-red-200">Deactivate</button>
    </form>
@elseif($user->status === 'deactivated')
    <form
        hx-patch="{{ route('users.updateStatus', $user->id) }}"
        hx-target="#user-row-{{ $user->id }}"
        hx-swap="outerHTML"
        hx-vals='{"status": "active"}'
        hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
    >
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="active">
        <button type="submit" class="px-3 py-1 text-xs rounded-lg bg-green-100 text-green-700 hover:bg-green-200">Reactivate</button>
    </form>
@endif