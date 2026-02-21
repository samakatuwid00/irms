<!-- Non-Print Resource Form -->

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

<form id="nonprintForm" action="{{ route('nonprint-resource.create') }}" class="resource-form space-y-8" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- =========================
        1ST GROUP
    ========================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

        <!-- LEFT: IMAGE -->
        <div class="h-full">
            <div class="h-full flex flex-col items-center justify-between
                        border-2 border-dashed border-blue-500 rounded-lg
                        p-4 text-center">

                <img
                    id="nonprintImagePreview"
                    src="{{ asset('assets/images/def.jpg') }}"
                    alt="Image preview"
                    class="w-full flex-1 object-cover rounded mb-4"
                >

                <input
                    type="file"
                    name="image"
                    id="nonprintImageUpload"
                    class="hidden"
                    accept="image/*"
                >

                <label
                    for="nonprintImageUpload"
                    class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                >
                    Choose Image
                </label>

                <p class="text-xs text-gray-500 mt-2">
                    JPG, PNG • Max 5MB
                </p>
            </div>
        </div>

        <!-- RIGHT: INPUTS -->
        <div class="md:col-span-2 space-y-6">

            <!-- Title / Type / Brand -->
            <div class="space-y-4">
                    @if (Auth::user()->userType?->level === 3)
                        <div>
                            <label class="block text-sm font-medium mb-1">Library</label>
                            <select name="library_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                                @foreach ($divisionLibraries as $library)
                                    <option value="{{ $library->id }}">{{ $library->library_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif (Auth::user()->userType?->level === 4)
                        <input type="hidden" name="library_id" id="library_id" value='{{ $regionLibrary->id }}' readonly required>
                    @elseif (Auth::user()->userType?->level === 1)
                        <input type="hidden" name="library_id" id="library_id" value='{{ $schoolLibrary->id }}' readonly required>
                    @endif
                <div>
                    <label class="block text-sm font-medium mb-1">Title/Name</label>
                    <input type="text" name="title" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select name="type" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="" disabled selected>Select type</option>
                                @foreach ($nonprintTypes as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Brand</label>
                    <input type="text" name="brand" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

            <!-- Code / Version / Model -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Code</label>
                    <input type="text" name="code" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Version</label>
                    <input type="text" name="version" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Model</label>
                    <input type="text" name="model" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

            <!-- URL / Size -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">URL <span class="text-xs text-gray-500">(if applicable)</span></label>
                    <input type="url" name="url" placeholder="https://example.com" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Size <span class="text-xs text-gray-500">(e.g., dimensions or file size)</span></label>
                    <input type="text" name="size" placeholder="e.g., 50x30 cm or 2.4 GB" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
            </div>

        </div>
    </div>

    {{-- ========================= 2ND GROUP ========================== --}}
    {{-- ========================= SUBJECT–GRADE LEVEL MAPPING ========================== --}}

        @php
        $stages = [
            'S1' => [
                'tab' => 'stage1',
                'label' => 'Stage 1',
                'grades' => [
                    0 => 'K',
                    1 => '1',
                    2 => '2',
                    3 => '3',
                ],
            ],
            'ES' => [
                'tab' => 'stage2',
                'label' => 'Stage 2',
                'grades' => [
                    4 => '4',
                    5 => '5',
                    6 => '6',
                ],
            ],
            'JHS' => [
                'tab' => 'jhs',
                'label' => 'Junior High',
                'grades' => [
                    7 => '7',
                    8 => '8',
                    9 => '9',
                    10 => '10',
                ],
            ],
            'SHS' => [
                'tab' => 'shs',
                'label' => 'Senior High',
                'grades' => [
                    11 => '11',
                    12 => '12',
                ],
            ],
        ];

        // Group data: [key_stage][subject_name][]
        $grouped = $subjectGradeLevels->groupBy(['key_stage', 'subject_name']);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2 space-y-4">

                {{-- ================= TABS ================= --}}
                <div class="flex gap-6 border-b">
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
                                        // Map grade sort_order → subject_grade_level row
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

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Remarks <span class="text-xs text-gray-500">(will be saved with each acquisition)</span>
                    </label>
                    <textarea name="remarks" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"
                            placeholder="Any notes, condition details, or special remarks for this batch..."></textarea>
                </div>

                <!-- TOP ROW -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Source</label>
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
                        <label class="block text-sm font-medium mb-1">Date Acquired</label>
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

                <!-- CONDITION QUANTITY -->
                <div>
                    <h4 class="text-sm font-semibold mb-3 text-gray-600">Condition & Quantity</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <div><label class="block text-xs mb-1">Usable</label><input type="number" name="usable" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Partially Damaged</label><input type="number" name="partially_damaged" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Damaged</label><input type="number" name="damaged" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Lost</label><input type="number" name="lost" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Condemnable</label><input type="number" name="condemnable" value="0" min="0" class="qty w-full border border-gray-300 rounded px-3 py-2"></div>
                        <div class="md:col-span-2">
                            <label class="block text-xs mb-1">Total Quantity</label>
                            <input type="number" name="total_quantity" id="nonprintTotalQuantity" readonly class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 font-semibold">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="addNonPrintAcquisitionBtn"
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
                    <tbody id="nonprintAcquisitionTableBody">
                        <tr><td colspan="12" class="text-center text-gray-400 py-3">No acquisitions added</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <input type="hidden" name="acquisitions" id="nonprintAcquisitionsInput">

    <!-- SUBMIT -->
    <div class="flex justify-end">
        <button type="submit" id="saveNonPrintBtn" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <span id="saveNonPrintText">Save Non-Print Resource</span>
            <span id="saveNonPrintLoading" class="hidden">
                <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
            </span>
        </button>
    </div>
</form>

    @vite(['resources/js/add-nonprint-resource.js'])

