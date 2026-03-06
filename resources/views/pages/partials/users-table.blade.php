{{-- resources/views/pages/partials/users-table.blade.php --}}
{{-- Variables: $users (paginator), $emptyMessage (string), $activeTab (string) --}}

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
            @forelse ($users as $user)
                <tr class="hover:bg-gray-50" id="user-row-{{ $user->id }}">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $user->firstname }} {{ $user->lastname }}</td>
                    <td class="px-4 py-3">{{ $user->username }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">{{ $user->usertype_name }}</td>
                    <td class="px-4 py-3">{{ $user->station_name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span id="status-badge-{{ $user->id }}"
                            class="px-2 py-1 rounded-full text-xs {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700') }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div id="action-cell-{{ $user->id }}" class="flex justify-center gap-2">
                            @include('pages.partials.user-action-buttons', ['user' => $user])
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-3 text-center text-gray-500">{{ $emptyMessage }}</td>
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
    (function () {
        const paginationDivs = document.querySelectorAll('.htmx-pagination');
        const paginationDiv = paginationDivs[paginationDivs.length - 1];
        const activeTab = paginationDiv.dataset.activeTab;

        // Map tab name to its table container ID
        const containerMap = {
            'main':     'main-table-container',
            'sub':      'sub-table-container',
            'subsub':   'subsub-table-container',
            'region':   'region-table-container',
            'division': 'division-table-container',
        };

        const containerId = containerMap[activeTab];

        paginationDiv.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();

                const url = new URL(this.href);
                url.searchParams.set('active_tab', activeTab);

                if (containerId) {
                    htmx.ajax('GET', url.toString(), {
                        target: '#' + containerId,
                        swap: 'innerHTML',
                        pushURL: true
                    });
                }
            });
        });
    })();
</script>