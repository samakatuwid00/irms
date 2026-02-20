{{-- Print Resource Form --}}
    {{-- Success Message --}}
    @if(session('success'))
        <div
            class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded flex justify-between items-center"
            id="flash-success"
        >
            <span>{{ session('success') }}</span>
            <button
                type="button"
                class="text-green-800 font-bold hover:text-green-900"
                onclick="document.getElementById('flash-success').remove();"
            >&times;</button>
        </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <div
            class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded flex justify-between items-center"
            id="flash-error"
        >
            <span>{{ session('error') }}</span>
            <button
                type="button"
                class="text-red-800 font-bold hover:text-red-900"
                onclick="document.getElementById('flash-error').remove();"
            >&times;</button>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div
            class="mb-4 p-4 bg-red-100 border border-red-200 rounded text-red-800 flex justify-between items-start"
            id="flash-validation"
        >
            <ul class="list-disc pl-5 flex-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button
                type="button"
                class="ml-4 text-red-800 font-bold hover:text-red-900"
                onclick="document.getElementById('flash-validation').remove();"
            >&times;</button>
        </div>
    @endif

    {{--
        No `name="library_id"` on any top-level input anymore.
        Library is now captured per-acquisition inside the acquisitions JSON.
    --}}
    <form id="print" action="{{ route('add-print-resource') }}" class="resource-form space-y-8" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- ========================= 1ST GROUP ========================== --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

            {{-- LEFT: IMAGE  --}}
            <div class="h-full">
                <div class="h-full flex flex-col items-center justify-between
                            border-2 border-dashed border-blue-500 rounded-lg
                            p-4 text-center">

                    <img
                        id="imagePreview"
                        src="{{ asset('assets/images/def.jpg') }}"
                        alt="Image preview"
                        class="w-full flex-1 object-cover rounded mb-4"
                    >

                    <input
                        type="file"
                        name="image"
                        id="imageUpload"
                        class="hidden"
                        accept="image/*"
                    >

                    <label
                        for="imageUpload"
                        class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                    >
                        Choose Image
                    </label>

                    <p class="text-xs text-gray-500 mt-2">
                        JPG, PNG • Max 5MB
                    </p>
                </div>
            </div>

            {{-- RIGHT: INPUTS --}}
            <div class="md:col-span-2 space-y-6">

                {{-- Title / Author / Publisher --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            required
                            class="w-full border border-gray-300 rounded px-3 py-2"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Authors</label>

                        {{-- Visible input --}}
                        <div class="flex flex-wrap gap-2 border border-gray-300 rounded px-2 py-2" id="author-wrapper">
                            <input
                                type="text"
                                id="author-input"
                                class="flex-1 outline-none border-none"
                                placeholder="Type author name and press Enter"
                            >
                        </div>

                        {{-- Hidden input (stores array) --}}
                        <input type="hidden" name="authors" id="authors-hidden" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Publisher</label>
                        <input type="text" name="publisher" class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>

                {{-- Two Columns --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Type<span class="text-red-500">*</span></label>
                            <select name="type" class="w-full border border-gray-300 rounded px-3 py-2" required>
                                <option selected disabled>Select type</option>
                                @foreach ($printTypes as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Volume</label>
                            <input name="volume" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Edition</label>
                            <input name="edition" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Copyright</label>
                            <input name="copyright" type="number" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">ISBN</label>
                            <input name="isbn" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Pages</label>
                            <input name="pages" type="number" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>
                </div>

            </div>
        </div>

    {{-- ========================= SUBJECT–GRADE LEVEL MAPPING ========================== --}}

        @php
        $stages = [
            'S1' => [
                'tab'    => 'stage1',
                'label'  => 'Key Stage 1',
                'grades' => [0 => 'K', 1 => '1', 2 => '2', 3 => '3'],
            ],
            'ES' => [
                'tab'    => 'stage2',
                'label'  => 'Key Stage 2',
                'grades' => [4 => '4', 5 => '5', 6 => '6'],
            ],
            'JHS' => [
                'tab'    => 'jhs',
                'label'  => 'Junior High',
                'grades' => [7 => '7', 8 => '8', 9 => '9', 10 => '10'],
            ],
            'SHS' => [
                'tab'    => 'shs',
                'label'  => 'Senior High',
                'grades' => [11 => '11', 12 => '12'],
            ],
        ];

        $grouped = $subjectGradeLevels->groupBy(['key_stage', 'subject_name']);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2 space-y-4">

                {{-- ================= TABS ================= --}}
                <div class="flex gap-6 border-b border-gray-300">
                    @foreach ($stages as $stage)
                        <button
                            type="button"
                            class="tab-btn {{ $loop->first ? 'active border-blue-600 text-blue-600' : 'text-gray-600' }}"
                            data-tab="{{ $stage['tab'] }}">
                            {{ $stage['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- ================= TAB CONTENTS ================= --}}
                @foreach ($stages as $stageKey => $stage)
                    <div
                        id="{{ $stage['tab'] }}"
                        class="tab-content {{ !$loop->first ? 'hidden' : '' }}">

                        <table class="w-full border border-gray-300 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 px-3 py-2 text-left w-72">
                                        Subject
                                    </th>

                                    @foreach ($stage['grades'] as $gradeLabel)
                                        <th class="border border-gray-300">
                                            {{ $gradeLabel }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($grouped[$stageKey] ?? [] as $subject => $rows)
                                    @php
                                        $gradeMap = collect($rows)->keyBy('sort_order');
                                    @endphp

                                    <tr>
                                        <td class="border border-gray-300 px-3 py-2">
                                            {{ $subject }}
                                        </td>

                                        @foreach ($stage['grades'] as $sortOrder => $label)
                                            <td class="border border-gray-300 text-center">
                                                @if ($gradeMap->has($sortOrder))
                                                    <input
                                                        type="checkbox"
                                                        name="subject_grade_levels[]"
                                                        value="{{ $gradeMap[$sortOrder]->subject_grade_level_id }}">
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                @endforeach

            </div>
        </div>

        {{-- ========================= 3RD GROUP (ACQUISITION & CONDITION) ========================== --}}
        <div class="bg-gray-50 border border-gray-300 rounded-xl p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-700">
                Acquisition & Condition Details
            </h3>

            {{-- ---- LIBRARY (per-acquisition) ---- --}}
            @php $userLevel = Auth::user()->userType?->level; @endphp

            @if ($userLevel === 1)
                {{--
                    School account: library is fixed and needs no user interaction.
                    Both inputs are hidden; the inline sync script below still fires
                    so AcquisitionManager picks up the correct id + name values.
                --}}
                <input
                    type="hidden"
                    id="acq_library_id"
                    value="{{ $schoolLibrary->id ?? '' }}"
                    data-name="{{ $schoolLibrary->library_name ?? '' }}"
                >
                <input type="hidden" id="acq_library_name">

            @else
                {{--
                    Division / Region (and any other) accounts: show the label
                    and the appropriate control so they can choose / see their library.
                --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Library <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-400">(saved with each acquisition)</span>
                    </label>

                    @if ($userLevel === 3)
                        {{-- Division users pick from their libraries --}}
                        <select
                            id="acq_library_id"
                            class="w-full border border-gray-300 rounded px-3 py-2"
                        >
                            <option value="" disabled selected>Select library</option>
                            @foreach ($divisionLibraries as $library)
                                <option
                                    value="{{ $library->id }}"
                                    data-name="{{ $library->library_name }}"
                                >{{ $library->library_name }}</option>
                            @endforeach
                        </select>

                    @elseif ($userLevel === 4)
                        {{-- Region users have one fixed library — show read-only display --}}
                        <input
                            type="hidden"
                            id="acq_library_id"
                            value="{{ $regionLibrary->id ?? '' }}"
                            data-name="{{ $regionLibrary->library_name ?? '' }}"
                        >
                        <p class="text-sm text-gray-700 border border-gray-200 bg-white rounded px-3 py-2">
                            {{ $regionLibrary->library_name ?? 'Region Library' }}
                        </p>
                    @endif

                    <input type="hidden" id="acq_library_name">
                </div>
            @endif

            {{-- Remarks --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Remarks <span class="text-xs text-gray-500">(will be saved with each acquisition)</span>
                </label>
                <textarea name="remarks" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"
                        placeholder="Any notes, condition details, or special remarks for this batch..."></textarea>
            </div>

            {{-- TOP ROW --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Source<span class="text-red-500">*</span></label>
                    <select id="source" name="source" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="" selected disabled>Select source</option>
                        <option value="CO">DepEd - Central Office</option>
                        <option value="RO">Regional Office</option>
                        <option value="SDO">Schools Division Office</option>
                        <option value="LOCAL">Locally Developed</option>
                        <option value="DONATED">DONATED</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Date Acquired<span class="text-red-500">*</span></label>
                    <input type="date" name="date_acquired" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cost</label>
                    <input type="number" step="0.01" name="cost" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">IAR No.</label>
                    <input type="text" name="iar" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

            {{-- CONDITION QUANTITY --}}
            <div>
                <h4 class="text-sm font-semibold mb-3 text-gray-600">Condition & Quantity <span class="text-red-500">*</span></h4>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                    <div><label class="block text-xs mb-1">Usable</label><input type="number" name="usable" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                    <div><label class="block text-xs mb-1">Partially Damaged</label><input type="number" name="partially_damaged" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                    <div><label class="block text-xs mb-1">Damaged</label><input type="number" name="damaged" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                    <div><label class="block text-xs mb-1">Lost</label><input type="number" name="lost" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                    <div><label class="block text-xs mb-1">Condemnable</label><input type="number" name="condemnable" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                    <div class="md:col-span-2">
                        <label class="block text-xs mb-1">Total Quantity</label>
                        <input type="number" name="total_quantity" id="totalQuantity" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 font-semibold">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" id="addAcquisitionBtn"
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    ➕ Add Acquisition
                </button>
            </div>
        </div>

        {{-- ========================= ACQUISITION LIST ========================== --}}
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Acquisition List</h3>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-2 py-1 text-left">Library</th>
                            <th class="border border-gray-300 px-2 py-1">Source</th>
                            <th class="border border-gray-300 px-2 py-1">Date</th>
                            <th class="border border-gray-300 px-2 py-1">Cost</th>
                            <th class="border border-gray-300 px-2 py-1">IAR</th>
                            <th class="border border-gray-300 px-2 py-1">Remarks</th>
                            <th class="border border-gray-300 px-2 py-1">Usable</th>
                            <th class="border border-gray-300 px-2 py-1">PD</th>
                            <th class="border border-gray-300 px-2 py-1">Damaged</th>
                            <th class="border border-gray-300 px-2 py-1">Lost</th>
                            <th class="border border-gray-300 px-2 py-1">Cond.</th>
                            <th class="border border-gray-300 px-2 py-1">Total</th>
                            <th class="border border-gray-300 px-2 py-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="acquisitionTableBody">
                        <tr><td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <input type="hidden" name="acquisitions" id="acquisitionsInput">

        {{-- SUBMIT --}}
        <div class="flex justify-end">
            <button type="submit" id="savePrintBtn" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="savePrintText">Save Print Resource</span>
                <span id="savePrintLoading" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                </span>
            </button>
        </div>
    </form>

    {{--
        Sync library name into the hidden companion whenever the select changes.
        Works for all account types:
          - Level 1 (school): both inputs are hidden; fires once on load to
            populate acq_library_name from the data-name attribute.
          - Level 3 (division): fires on SELECT change + on load.
          - Level 4 (region): both inputs are hidden; fires once on load.
    --}}
    <script>
    (function () {
        const libSelect = document.getElementById('acq_library_id');
        const libName   = document.getElementById('acq_library_name');

        if (!libSelect || !libName) return;

        function syncName() {
            if (libSelect.tagName === 'SELECT') {
                const opt = libSelect.options[libSelect.selectedIndex];
                libName.value = opt ? (opt.dataset.name || opt.text || '') : '';
            } else {
                // hidden input — name is in data-name attribute
                libName.value = libSelect.dataset.name || '';
            }
        }

        libSelect.addEventListener('change', syncName);
        syncName(); // populate on page load for hidden-input cases (school / region)
    })();
    </script>

    @vite(['resources/js/add-print-resource.js'])
