<!-- Non-Print Resource Form -->
<form id="nonprintForm" action="{{ route('add-nonprint-resource') }}" class="resource-form space-y-8" method="POST" enctype="multipart/form-data">
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
                    src="{{ asset('assets/images/default.jpg') }}"
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
                            <select name="library_id" class="w-full border rounded px-3 py-2" required>
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
                    <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select name="type" class="w-full border rounded px-3 py-2" required>
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
                    <input type="text" name="brand" class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <!-- Code / Version / Model -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Code</label>
                    <input type="text" name="code" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Version</label>
                    <input type="text" name="version" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Model</label>
                    <input type="text" name="model" class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <!-- URL / Size -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">URL <span class="text-xs text-gray-500">(if applicable)</span></label>
                    <input type="url" name="url" placeholder="https://example.com" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Size <span class="text-xs text-gray-500">(e.g., dimensions or file size)</span></label>
                    <input type="text" name="size" placeholder="e.g., 50x30 cm or 2.4 GB" class="w-full border rounded px-3 py-2">
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

                        <table class="w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left w-72">
                                        Subject
                                    </th>

                                    @foreach ($stage['grades'] as $gradeLabel)
                                        <th class="border">
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
                                        <td class="border px-3 py-2">
                                            {{ $subject }}
                                        </td>

                                        @foreach ($stage['grades'] as $sortOrder => $label)
                                            <td class="border text-center">
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
        <div class="bg-gray-50 border rounded-xl p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-700">
                    Acquisition & Condition Details
                </h3>

                <div class="flex justify-end">
                    <button type="button" id="addNonPrintAcquisitionBtn"
                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        ➕ Add Acquisition
                    </button>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Remarks <span class="text-xs text-gray-500">(will be saved with each acquisition)</span>
                    </label>
                    <textarea name="remarks" rows="3" class="w-full border rounded px-3 py-2"
                            placeholder="Any notes, condition details, or special remarks for this batch..."></textarea>
                </div>

                <!-- TOP ROW -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Source</label>
                        <select id="source" name="source" class="w-full border rounded px-3 py-2" required>
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
                        <input type="date" name="date_acquired" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Cost</label>
                        <input type="number" step="0.01" name="cost" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">IAR No.</label>
                        <input type="text" name="iar" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <!-- CONDITION QUANTITY -->
                <div>
                    <h4 class="text-sm font-semibold mb-3 text-gray-600">Condition & Quantity</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <div><label class="block text-xs mb-1">Usable</label><input type="number" name="usable" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Partially Damaged</label><input type="number" name="partially_damaged" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Damaged</label><input type="number" name="damaged" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Lost</label><input type="number" name="lost" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Condemnable</label><input type="number" name="condemnable" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div class="md:col-span-2">
                            <label class="block text-xs mb-1">Total Quantity</label>
                            <input type="number" name="total_quantity" id="nonprintTotalQuantity" readonly class="w-full bg-gray-100 border rounded px-3 py-2 font-semibold">
                        </div>
                    </div>
                </div>
        </div>

        {{-- ========================= ACQUISITION LIST ========================== --}}
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Acquisition List</h3>
            <div class="overflow-x-auto">
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Source</th>
                            <th class="border px-2 py-1">Date</th>
                            <th class="border px-2 py-1">Cost</th>
                            <th class="border px-2 py-1">IAR</th>
                            <th class="border px-2 py-1">Remarks</th>
                            <th class="border px-2 py-1">Usable</th>
                            <th class="border px-2 py-1">PD</th>
                            <th class="border px-2 py-1">Damaged</th>
                            <th class="border px-2 py-1">Lost</th>
                            <th class="border px-2 py-1">Cond.</th>
                            <th class="border px-2 py-1">Total</th>
                            <th class="border px-2 py-1">Actions</th>
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
        <button class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Save Non-Print Resource
        </button>
    </div>
</form>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('nonprintForm');
        if (!form) return;

        // ── IMAGE PREVIEW ───────────────────────────────────────
        const imageUpload = form.querySelector('#nonprintImageUpload');
        const imagePreview = form.querySelector('#nonprintImagePreview');

        imageUpload?.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size must be less than 5MB.');
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });

        // ── TABS ─────────────────────────────────────────────────
        const tabs = form.querySelectorAll('.tab-btn');
        const contents = form.querySelectorAll('.tab-content');

        function activateTab(tab) {
            tabs.forEach(t => {
                t.classList.remove('border-blue-600', 'text-blue-600', 'active');
                t.classList.add('text-gray-600');
            });

            contents.forEach(c => c.classList.add('hidden'));

            tab.classList.add('border-blue-600', 'text-blue-600', 'active');
            tab.classList.remove('text-gray-600');

            const target = form.querySelector(`#${tab.dataset.tab}`);
            target?.classList.remove('hidden');
        }

        if (tabs.length > 0) {
            activateTab(tabs[0]);
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => activateTab(tab));
        });

        // ── QUANTITY CALCULATION ─────────────────────────────────
        const qtyInputs = form.querySelectorAll('.qty');
        const totalField = form.querySelector('#nonprintTotalQuantity');

        const calculateTotal = () => {
            let total = 0;
            qtyInputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            if (totalField) totalField.value = total;
        };

        qtyInputs.forEach(input => input.addEventListener('input', calculateTotal));

        // ── ACQUISITIONS MANAGEMENT (Non-Print version) ───────────
        let nonprintAcquisitions = [];
        let nonprintEditIndex = null;

        const npFields = {
            source: () => form.querySelector('[name="source"]').value,
            date_acquired: () => form.querySelector('[name="date_acquired"]').value,
            cost: () => form.querySelector('[name="cost"]').value,
            iar: () => form.querySelector('[name="iar"]').value,
            remarks: () => form.querySelector('[name="remarks"]').value.trim(),
            usable: () => form.querySelector('[name="usable"]').value,
            partially_damaged: () => form.querySelector('[name="partially_damaged"]').value,
            damaged: () => form.querySelector('[name="damaged"]').value,
            lost: () => form.querySelector('[name="lost"]').value,
            condemnable: () => form.querySelector('[name="condemnable"]').value,
            total_quantity: () => form.querySelector('#nonprintTotalQuantity').value,
        };

        const acquisitionTableBody = form.querySelector('#nonprintAcquisitionTableBody');
        const acquisitionsInput = form.querySelector('#nonprintAcquisitionsInput');

        const renderNonPrintAcquisitions = () => {
            if (!acquisitionTableBody) return;

            acquisitionTableBody.innerHTML = '';
            if (nonprintAcquisitions.length === 0) {
                acquisitionTableBody.innerHTML = `<tr><td colspan="12" class="text-center text-gray-400 py-3">No acquisitions added</td></tr>`;
                return;
            }

            nonprintAcquisitions.forEach((a, index) => {
                const shortRemark = a.remarks?.length > 40 ? a.remarks.substring(0, 37) + '...' : (a.remarks || '-');
                acquisitionTableBody.innerHTML += `
                    <tr>
                        <td class="border px-2 py-1">${a.source || ''}</td>
                        <td class="border px-2 py-1">${a.date_acquired || ''}</td>
                        <td class="border px-2 py-1">${a.cost || ''}</td>
                        <td class="border px-2 py-1">${a.iar || ''}</td>
                        <td class="border px-2 py-1 text-xs">${shortRemark}</td>
                        <td class="border px-2 py-1">${a.usable || 0}</td>
                        <td class="border px-2 py-1">${a.partially_damaged || 0}</td>
                        <td class="border px-2 py-1">${a.damaged || 0}</td>
                        <td class="border px-2 py-1">${a.lost || 0}</td>
                        <td class="border px-2 py-1">${a.condemnable || 0}</td>
                        <td class="border px-2 py-1 font-semibold">${a.total_quantity || 0}</td>
                        <td class="border px-2 py-1 text-center">
                            <div class="flex justify-center gap-2">
                                <button type="button" onclick="editNonPrintAcquisition(${index})"
                                    class="p-1 rounded hover:bg-blue-100 text-blue-600" title="Edit">
                                    <!-- edit svg ... -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                    </svg>
                                </button>
                                <button type="button" onclick="deleteNonPrintAcquisition(${index})"
                                    class="p-1 rounded hover:bg-red-100 text-red-600" title="Delete">
                                    <!-- delete svg ... -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });
        };

        const resetNonPrintAcquisitionForm = () => {
            ['remarks','source','date_acquired','cost','iar','usable','partially_damaged','damaged','lost','condemnable']
                .forEach(name => {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el) el.value = name.includes('damaged') || name.includes('lost') ? '0' : '';
                });
            calculateTotal();
        };

        form.querySelector('#addNonPrintAcquisitionBtn')?.addEventListener('click', () => {
            const acquisition = {};
            for (const key in npFields) {
                acquisition[key] = npFields[key]();
            }

            if (!acquisition.source || !acquisition.date_acquired) {
                alert('Source and Date Acquired are required.');
                return;
            }
            if ((parseInt(acquisition.total_quantity) || 0) < 1) {
                alert('Total Quantity must be at least 1.');
                return;
            }

            if (nonprintEditIndex !== null) {
                nonprintAcquisitions[nonprintEditIndex] = acquisition;
                nonprintEditIndex = null;
            } else {
                nonprintAcquisitions.push(acquisition);
            }

            renderNonPrintAcquisitions();
            resetNonPrintAcquisitionForm();
        });

        // Expose functions with unique names (avoid global pollution)
        window.editNonPrintAcquisition = (index) => {
            const item = nonprintAcquisitions[index];
            if (!item) return;

            nonprintEditIndex = index;

            Object.keys(npFields).forEach(key => {
                const el = form.querySelector(`[name="${key}"]`);
                if (el) el.value = item[key] ?? (key.includes('damaged') || key.includes('lost') ? '0' : '');
            });

            calculateTotal();
            form.querySelector('[name="source"]')?.scrollIntoView({ behavior: 'smooth' });
        };

        window.deleteNonPrintAcquisition = (index) => {
            if (!confirm('Delete this acquisition?')) return;
            nonprintAcquisitions.splice(index, 1);
            renderNonPrintAcquisitions();
        };

        // Submit handler
        form.addEventListener('submit', () => {
            if (acquisitionsInput) {
                acquisitionsInput.value = JSON.stringify(nonprintAcquisitions);
            }
        });

        // Initial render
        renderNonPrintAcquisitions();
    });
</script>
