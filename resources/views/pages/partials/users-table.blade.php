{{-- resources/views/pages/partials/users-table.blade.php --}}
{{-- Variables: $users (paginator), $emptyMessage (string), $activeTab (string), $allowStationEdit (bool) --}}

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
                @unless($hideActions ?? false)
                <th class="px-4 py-3 text-center">Actions</th>
                @endunless
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($users as $user)
                <tr class="hover:bg-gray-50" id="user-row-{{ $user->id }}">
                    <td class="px-4 py-3 font-medium text-gray-600 uppercase">{{ $user->firstname }} {{ $user->lastname }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->username }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->usertype_name }}</td>
                    <td class="px-4 py-3 text-gray-600">
                        @if (($allowStationEdit ?? false) && $activeTab === 'sub')
                            {{-- Resolve the division this user's district belongs to.
                                For a division-level admin, all sub-users are in their division,
                                so $divisionId passed from the parent is the correct scope. --}}
                            @php $editDivisionId = $divisionId ?? ''; @endphp

                            <div class="inline-edit-station flex items-center gap-1 group"
                                data-user-id="{{ $user->id }}">

                                {{-- Display mode --}}
                                <span class="station-label" id="station-label-{{ $user->id }}">
                                    {{ $user->station_name ?? '—' }}
                                </span>

                                {{-- Pencil button — passes divisionId + current station_id --}}
                                <button type="button"
                                    class="edit-station-btn ml-1 text-gray-400 hover:text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                    title="Change district station"
                                    onclick="openStationEdit(
                                        {{ json_encode($user->id) }},
                                        {{ json_encode($editDivisionId) }},
                                        {{ json_encode($user->station_id ?? '') }}
                                    )">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </button>

                                {{-- Edit mode (hidden by default) --}}
                                <div class="station-edit-form hidden items-center gap-1" id="station-edit-{{ $user->id }}">
                                    <select
                                        class="station-select border border-blue-400 rounded px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                        onchange="saveStation({{ json_encode($user->id) }}, this)"
                                        onblur="cancelStationEdit({{ json_encode($user->id) }})">
                                        <option value="">— Loading… —</option>
                                    </select>
                                    <button type="button"
                                        class="text-gray-400 hover:text-red-500"
                                        title="Cancel"
                                        onmousedown="cancelStationEdit({{ json_encode($user->id) }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Saving spinner --}}
                                <span class="station-saving hidden ml-1" id="station-saving-{{ $user->id }}">
                                    <svg class="animate-spin h-3.5 w-3.5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                </span>
                            </div>
                        @else
                            {{ $user->station_name ?? '-' }}
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span id="status-badge-{{ $user->id }}"
                            class="px-2 py-1 rounded-full text-xs {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    @unless($hideActions ?? false)
                    <td class="px-4 py-3 text-center">
                        <div id="action-cell-{{ $user->id }}" class="flex justify-center gap-2">
                            @include('pages.partials.user-action-buttons', ['user' => $user])
                        </div>
                    </td>
                    @endunless
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($hideActions ?? false) ? 6 : 7 }}" class="px-4 py-3 text-center text-gray-500">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="flex justify-end mt-4 htmx-pagination" data-active-tab="{{ $activeTab ?? request('active_tab', 'main') }}">
    {{ $users->links() }}
</div>

<script>
    // ── Pagination HTMX wiring (unchanged) ───────────────────────────────────
    (function () {
        const paginationDivs = document.querySelectorAll('.htmx-pagination');
        if (paginationDivs.length === 0) return;
        const paginationDiv = paginationDivs[paginationDivs.length - 1];
        const activeTab = paginationDiv.dataset.activeTab;
        const containerMap = {
            'main':     'main-table-container',
            'sub':      'sub-table-container',
            'subsub':   'subsub-table-container',
            'region':   'region-table-container',
            'division': 'division-table-container',
            'school':   'school-table-container',
        };
        const containerId = containerMap[activeTab];
        if (containerId) {
            paginationDiv.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    url.searchParams.set('active_tab', activeTab);
                    htmx.ajax('GET', url.toString(), {
                        target: '#' + containerId,
                        swap: 'innerHTML',
                        pushURL: true,
                    });
                });
            });
        }
    })();

    // ── Inline station edit (only rendered when allowStationEdit is true) ─────
    @if ($allowStationEdit ?? false)

    /**
     * Open the edit dropdown for a user row.
     * Fetches only districts belonging to the given divisionId.
     */
    async function openStationEdit(userId, divisionId, currentStationId) {
        // Switch to edit mode
        document.getElementById('station-label-' + userId).classList.add('hidden');
        const pencilBtn = document.querySelector('[data-user-id="' + userId + '"] .edit-station-btn');
        if (pencilBtn) pencilBtn.classList.add('hidden');

        const editForm = document.getElementById('station-edit-' + userId);
        editForm.classList.remove('hidden');
        editForm.classList.add('flex');

        const select = editForm.querySelector('select');
        select.innerHTML = '<option value="">— Loading… —</option>';
        select.disabled = true;

        try {
            const res = await fetch(`/divisions/${divisionId}/districts`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const districts = await res.json();

            select.innerHTML = '<option value="">— Select District —</option>';

            if (districts.length === 0) {
                select.innerHTML = '<option value="" disabled>No districts in this division</option>';
            } else {
                districts.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.district_name;
                    // Pre-select the user's current station
                    if (String(d.id) === String(currentStationId)) opt.selected = true;
                    select.appendChild(opt);
                });
            }
        } catch (err) {
            console.error('Could not load districts:', err);
            select.innerHTML = '<option value="" disabled>— Error loading districts —</option>';
        } finally {
            select.disabled = false;
            select.focus();
        }
    }

    /** Revert to display mode without saving. */
    function cancelStationEdit(userId) {
        document.getElementById('station-label-' + userId).classList.remove('hidden');
        const pencilBtn = document.querySelector('[data-user-id="' + userId + '"] .edit-station-btn');
        if (pencilBtn) pencilBtn.classList.remove('hidden');

        const editForm = document.getElementById('station-edit-' + userId);
        editForm.classList.add('hidden');
        editForm.classList.remove('flex');
    }

    /** PATCH the new station_id and update the visible label. */
    function saveStation(userId, selectEl) {
        const districtId   = selectEl.value;
        const districtName = districtId
            ? selectEl.options[selectEl.selectedIndex].text
            : '—';

        // Hide form, show spinner
        const editForm = document.getElementById('station-edit-' + userId);
        editForm.classList.add('hidden');
        editForm.classList.remove('flex');
        const spinner = document.getElementById('station-saving-' + userId);
        spinner.classList.remove('hidden');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        fetch(`/users/${userId}/station`, {
            method: 'PATCH',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  csrfToken,
                'Accept':        'application/json',
            },
            body: JSON.stringify({ station_id: districtId || null }),
        })
        .then(res => {
            if (!res.ok) throw new Error('Server error: ' + res.status);
            return res.json();
        })
        .then(() => {
            document.getElementById('station-label-' + userId).textContent = districtName;
        })
        .catch(err => {
            console.error('Failed to update station:', err);
            alert('Could not update station. Please try again.');
        })
        .finally(() => {
            spinner.classList.add('hidden');
            document.getElementById('station-label-' + userId).classList.remove('hidden');
            const pencilBtn = document.querySelector('[data-user-id="' + userId + '"] .edit-station-btn');
            if (pencilBtn) pencilBtn.classList.remove('hidden');
        });
    }

    @endif
</script>