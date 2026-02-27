<!-- Region Scope (Only for level 4 and above) -->
@if($userLevel >= 4)
<div class="relative max-w-[300px] mb-4">
    <label for="regionFilter"
        class="absolute left-3 -top-2 px-2 bg-gray-100 text-xs font-semibold text-gray-600 tracking-wide z-10">
        Region / Library Level
    </label>
    <select id="regionFilter" class="w-full px-3 py-2 text-sm bg-gray-100 border border-black rounded-lg
                       focus:ring-2 focus:ring-indigo-400 focus:border-black
                       hover:border-gray-700 transition appearance-none cursor-pointer pr-9">
        @foreach($regionOptions as $opt)
        <option value="{{ $opt['value'] }}" {{ $userLevel >= 3 && $opt['value'] === 'region-hub' ? 'selected' : '' }}>
            {{ $opt['label'] }}
        </option>
        @endforeach
    </select>
    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </span>
</div>
@endif

<!-- Division / District Scope (ONLY for level 3 users) -->
@if($userLevel == 3)
<div class="relative max-w-[300px] mb-4" id="divisionWrapper">
    <label for="divisionFilter"
        class="absolute left-3 -top-2 px-2 bg-gray-100 text-xs font-semibold text-gray-600 tracking-wide z-10">
        District
    </label>
    <select id="divisionFilter" class="w-full px-3 py-2 text-sm bg-gray-100 border border-black rounded-lg
                       focus:ring-2 focus:ring-indigo-400 focus:border-black
                       hover:border-gray-700 transition appearance-none cursor-pointer pr-9">
        @foreach($divisions as $district)
        <option value="{{ $district['id'] }}">{{ $district['name'] }}</option>
        @endforeach
    </select>
    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </span>
</div>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const regionSelect = document.getElementById('regionFilter');
        const divisionSelect = document.getElementById('divisionFilter');
        const visualizationSelect = document.getElementById('globalFilter');
        const userLevel = {
            {
                $userLevel
            }
        };

        function getCurrentLibraryId() {
            // For level 3 users, always use division/district filter
            if (userLevel === 3 && divisionSelect) {
                return divisionSelect.value || null;
            }

            // For level 4+ users, only use region filter
            if (userLevel >= 4 && regionSelect) {
                return regionSelect.value || null;
            }

            return null;
        }

        // Set up event listeners based on user level
        if (userLevel >= 4 && regionSelect) {
            regionSelect.addEventListener('change', refreshAllCharts);
        }

        if (userLevel === 3 && divisionSelect) {
            divisionSelect.addEventListener('change', refreshAllCharts);
        }

        if (visualizationSelect) {
            visualizationSelect.addEventListener('change', () => {
                // if you want to show/hide charts based on selected visualization
                // or just refresh the visible one
            });
        }

        // Initial chart load
        refreshAllCharts();
    });
</script>
