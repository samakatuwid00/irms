{{-- Edit Non-Print Resource Form Component --}}
{{-- Resource metadata (title, type, brand, etc.) is READ-ONLY. --}}
{{-- Only the Acquisition section is editable. --}}
@if($nonprintResource)

{{-- ── Flash Messages ── --}}
@if(session('success'))
    <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded flex justify-between items-center" id="flash-success">
        <span>{{ session('success') }}</span>
        <button type="button" class="text-green-800 font-bold hover:text-green-900"
                onclick="document.getElementById('flash-success').remove()">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded flex justify-between items-center" id="flash-error">
        <span>{{ session('error') }}</span>
        <button type="button" class="text-red-800 font-bold hover:text-red-900"
                onclick="document.getElementById('flash-error').remove()">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 border border-red-200 rounded text-red-800 flex justify-between items-start" id="flash-validation">
        <ul class="list-disc pl-5 flex-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="ml-4 text-red-800 font-bold hover:text-red-900"
                onclick="document.getElementById('flash-validation').remove()">&times;</button>
    </div>
@endif

<form id="nonprint-edit"
      action="{{ route('update-nonprint-resource', $nonprintResource->id) }}"
      class="resource-form space-y-8"
      method="POST">
    @csrf
    @method('PUT')

    {{-- ── Hidden pass-through field to satisfy the controller validator ──
         Priority: first acquisition's library_id → resource's own library_id
         → first division library (level 3 only) → empty string.
         Existing nonprint acquisitions may have been created before per-acquisition
         library tracking was added, so the resource-level fallback is essential. --}}
    @if (Auth::user()->userType?->level === 3)
        <input type="hidden" name="library_id"
               value="{{ $nonprintResource->nonprintAcquisitions->first()?->library_id
                         ?? $nonprintResource->library_id
                         ?? $divisionLibraries->first()?->id
                         ?? '' }}">
    @else
        <input type="hidden" name="library_id"
               value="{{ $nonprintResource->nonprintAcquisitions->first()?->library_id
                         ?? $nonprintResource->library_id
                         ?? '' }}">
    @endif

    {{-- ========================= RESOURCE DETAILS (READ-ONLY DISPLAY) ========================= --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-4">
            <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
            </svg>
            <span class="text-sm font-semibold text-blue-700">Resource Information (Read-only)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- Cover Image --}}
            <div class="flex justify-center md:justify-start">
                <img src="{{ $nonprintResource->cover
                                ? asset('storage/' . $nonprintResource->cover)
                                : asset('assets/images/default.jpg') }}"
                     alt="Cover"
                     class="w-32 h-44 object-cover rounded-lg border border-blue-200 shadow-sm">
            </div>

            {{-- Metadata --}}
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Title / Name</label>
                    <p class="font-semibold text-gray-900">{{ $nonprintResource->nonprintTitle->title ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Type</label>
                    <p class="text-gray-800">{{ $nonprintResource->type->type_name ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Brand</label>
                    <p class="text-gray-800">{{ $nonprintResource->brand ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Model</label>
                    <p class="text-gray-800">{{ $nonprintResource->model ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Code</label>
                    <p class="text-gray-800">{{ $nonprintResource->code ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Version</label>
                    <p class="text-gray-800">{{ $nonprintResource->version ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">URL</label>
                    <p class="text-gray-800 break-all">{{ $nonprintResource->url ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Size</label>
                    <p class="text-gray-800">{{ $nonprintResource->size ?? '-' }}</p>
                </div>

                {{-- Subject / Grade Level --}}
                @php
                    $displaySubjectsNP = collect();
                    if (!empty($selectedSubjectGradeLevelsNP)) {
                        $displaySubjectsNP = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])
                            ->whereIn('id', $selectedSubjectGradeLevelsNP)
                            ->get();
                    }
                @endphp
                @if($displaySubjectsNP->isNotEmpty())
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Subject / Grade Level</label>
                        <p class="text-sm text-gray-700">
                            {{ $displaySubjectsNP->map(fn($sgl) =>
                                ($sgl->subject->subject_name ?? 'N/A') . ' (' . ($sgl->gradeLevel->grade ?? 'N/A') . ')'
                            )->join(', ') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================= ACQUISITION & CONDITION (EDITABLE) ========================= --}}
    <div class="bg-gray-50 border border-gray-300 rounded-xl p-6 space-y-6">
        <h3 class="text-lg font-semibold text-gray-700">Acquisition & Condition Details</h3>

        {{-- Library selector --}}
        <div>
            @if (Auth::user()->userType?->level === 3)
            <label class="block text-sm font-medium mb-1">
                Library <span class="text-red-500">*</span>
                <span class="text-xs font-normal text-gray-400">(saved with each acquisition)</span>
            </label>
                <select id="npAcqLibraryId" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                    <option value="" disabled selected>Select library</option>
                    @foreach ($divisionLibraries as $lib)
                        <option value="{{ $lib->id }}" data-name="{{ $lib->library_name }}">
                            {{ $lib->library_name }}
                        </option>
                    @endforeach
                </select>
            @elseif (Auth::user()->userType?->level === 4)
                <input id="npAcqLibraryId" type="hidden"
                       value="{{ $regionLibrary->id ?? '' }}"
                       data-name="{{ $regionLibrary->library_name ?? '' }}">
            @elseif (Auth::user()->userType?->level === 1)
                <input id="npAcqLibraryId" type="hidden"
                       value="{{ $schoolLibrary->id ?? '' }}"
                       data-name="{{ $schoolLibrary->library_name ?? '' }}">
            @else
                <input id="npAcqLibraryId" type="hidden" value="" data-name="">
                <p class="text-sm text-yellow-600">No library assigned to your account.</p>
            @endif
        </div>

        {{-- Remarks --}}
        <div>
            <label class="block text-sm font-medium mb-1">
                Remarks
                <span class="text-xs text-gray-500">(saved with each acquisition)</span>
            </label>
            <textarea id="npAcqRemarks" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                      placeholder="Any notes, condition details, or special remarks for this batch..."></textarea>
        </div>

        {{-- Source / Date / Cost / IAR --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Source <span class="text-red-500">*</span></label>
                <select id="npAcqSource" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="" disabled selected>Select source</option>
                    <option value="CO">DepEd - Central Office</option>
                    <option value="RO">Regional Office</option>
                    <option value="SDO">Schools Division Office</option>
                    <option value="LOCAL">Locally Developed</option>
                    <option value="DONATED">DONATED</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Date Acquired <span class="text-red-500">*</span></label>
                <input type="date" id="npAcqDate" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Cost</label>
                <input type="number" step="0.01" id="npAcqCost" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">IAR No.</label>
                <input type="text" id="npAcqIar" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
        </div>

        {{-- Condition & Quantity --}}
        <div>
            <h4 class="text-sm font-semibold mb-3 text-gray-600">
                Condition & Quantity <span class="text-red-500">*</span>
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div>
                    <label class="block text-xs mb-1">Usable</label>
                    <input type="number" id="npAcqUsable" value="0" min="0"
                           class="np-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1">Partially Damaged</label>
                    <input type="number" id="npAcqPartiallyDamaged" value="0" min="0"
                           class="np-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1">Damaged</label>
                    <input type="number" id="npAcqDamaged" value="0" min="0"
                           class="np-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1">Lost</label>
                    <input type="number" id="npAcqLost" value="0" min="0"
                           class="np-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs mb-1">Condemnable</label>
                    <input type="number" id="npAcqCondemnable" value="0" min="0"
                           class="np-qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs mb-1">Total Quantity</label>
                    <input type="number" id="npTotalQuantity" readonly
                           class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-sm font-semibold">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="button" id="npAddAcquisitionBtn"
                    class="px-4 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                ➕ Add Acquisition
            </button>
        </div>
    </div>

    {{-- ========================= ACQUISITION LIST ========================= --}}
    <div>
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
                <tbody id="npAcquisitionTableBody">
                    <tr>
                        <td colspan="13" class="text-center text-gray-400 py-3">Loading acquisitions...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <input type="hidden" name="acquisitions" id="npAcquisitionsInput">

    {{-- ========================= SUBMIT ========================= --}}
    <div class="flex justify-end">
        <button type="submit" id="updateNonPrintBtn"
                class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <span id="updateNonPrintText">Update Acquisitions</span>
            <span id="updateNonPrintLoading" class="hidden">
                <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
            </span>
        </button>
    </div>

</form>

{{-- ── Pre-load existing acquisitions from the database ── --}}
@php
    $nonprintAcquisitionsData = [];
    foreach ($nonprintResource->nonprintAcquisitions ?? [] as $acq) {
        $nonprintAcquisitionsData[] = [
            'id'                => $acq->id,
            'library_id'        => $acq->library_id        ?? '',
            'library_name'      => $acq->library_name      ?? '',
            'source'            => $acq->source,
            'date_acquired'     => $acq->date_acquired,
            'cost'              => $acq->cost               ?? '',
            'iar'               => $acq->iar                ?? '',
            'remarks'           => $acq->remarks            ?? '',
            'usable'            => $acq->usable,
            'partially_damaged' => $acq->partially_damaged,
            'damaged'           => $acq->damaged,
            'lost'              => $acq->lost,
            'condemnable'       => $acq->condemnable,
            'total_quantity'    => $acq->total_qty,
        ];
    }
@endphp

<script>
    (function () {
        let acquisitions = @json($nonprintAcquisitionsData);
        let editIndex    = null;

        const tableBody      = document.getElementById('npAcquisitionTableBody');
        const hiddenInput    = document.getElementById('npAcquisitionsInput');
        const addBtn         = document.getElementById('npAddAcquisitionBtn');
        const totalField     = document.getElementById('npTotalQuantity');
        const form           = document.getElementById('nonprint-edit');
        const saveBtn        = document.getElementById('updateNonPrintBtn');
        const saveBtnText    = document.getElementById('updateNonPrintText');
        const saveBtnLoading = document.getElementById('updateNonPrintLoading');
        const libraryEl      = document.getElementById('npAcqLibraryId');

        function getLibraryId() {
            return libraryEl ? libraryEl.value : '';
        }
        function getLibraryName() {
            if (!libraryEl) return '';
            if (libraryEl.tagName === 'SELECT') {
                const opt = libraryEl.options[libraryEl.selectedIndex];
                return opt ? (opt.dataset.name || opt.text) : '';
            }
            return libraryEl.dataset.name || '';
        }

        document.querySelectorAll('.np-qty').forEach(input => {
            input.addEventListener('input', calcTotal);
        });

        function calcTotal() {
            let total = 0;
            document.querySelectorAll('.np-qty').forEach(inp => { total += parseInt(inp.value) || 0; });
            totalField.value = total;
        }

        function getFieldValues() {
            return {
                library_id:        getLibraryId(),
                library_name:      getLibraryName(),
                source:            document.getElementById('npAcqSource').value,
                date_acquired:     document.getElementById('npAcqDate').value,
                cost:              document.getElementById('npAcqCost').value,
                iar:               document.getElementById('npAcqIar').value,
                remarks:           document.getElementById('npAcqRemarks').value,
                usable:            document.getElementById('npAcqUsable').value,
                partially_damaged: document.getElementById('npAcqPartiallyDamaged').value,
                damaged:           document.getElementById('npAcqDamaged').value,
                lost:              document.getElementById('npAcqLost').value,
                condemnable:       document.getElementById('npAcqCondemnable').value,
                total_quantity:    totalField.value,
            };
        }

        function setFieldValues(acq) {
            if (libraryEl && libraryEl.tagName === 'SELECT' && acq.library_id) {
                libraryEl.value = acq.library_id;
            }
            document.getElementById('npAcqSource').value            = acq.source            ?? '';
            document.getElementById('npAcqDate').value              = acq.date_acquired      ?? '';
            document.getElementById('npAcqCost').value              = acq.cost               ?? '';
            document.getElementById('npAcqIar').value               = acq.iar                ?? '';
            document.getElementById('npAcqRemarks').value           = acq.remarks            ?? '';
            document.getElementById('npAcqUsable').value            = acq.usable             ?? '0';
            document.getElementById('npAcqPartiallyDamaged').value  = acq.partially_damaged  ?? '0';
            document.getElementById('npAcqDamaged').value           = acq.damaged            ?? '0';
            document.getElementById('npAcqLost').value              = acq.lost               ?? '0';
            document.getElementById('npAcqCondemnable').value       = acq.condemnable        ?? '0';
            calcTotal();
        }

        function resetFields() {
            if (libraryEl && libraryEl.tagName === 'SELECT') libraryEl.selectedIndex = 0;
            document.getElementById('npAcqSource').value            = '';
            document.getElementById('npAcqDate').value              = '';
            document.getElementById('npAcqCost').value              = '';
            document.getElementById('npAcqIar').value               = '';
            document.getElementById('npAcqRemarks').value           = '';
            document.getElementById('npAcqUsable').value            = '0';
            document.getElementById('npAcqPartiallyDamaged').value  = '0';
            document.getElementById('npAcqDamaged').value           = '0';
            document.getElementById('npAcqLost').value              = '0';
            document.getElementById('npAcqCondemnable').value       = '0';
            calcTotal();
        }

        addBtn.addEventListener('click', handleAdd);

        function handleAdd() {
            const acq = getFieldValues();

            if (!acq.library_id) {
                alert('Please select a library.');
                return;
            }
            if (!acq.source || !acq.date_acquired) {
                alert('Source and Date Acquired are required.');
                return;
            }
            if ((parseInt(acq.total_quantity) || 0) < 1) {
                alert('Total Quantity must be at least 1.');
                return;
            }

            if (editIndex !== null) {
                if (acquisitions[editIndex].id) acq.id = acquisitions[editIndex].id;
                acquisitions[editIndex] = acq;
                editIndex = null;
                addBtn.textContent = '➕ Add Acquisition';
            } else {
                acquisitions.push(acq);
            }

            render();
            resetFields();
            updateHidden();
        }

        function render() {
            tableBody.innerHTML = '';

            if (!acquisitions.length) {
                tableBody.innerHTML =
                    '<tr><td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added yet</td></tr>';
                return;
            }

            acquisitions.forEach((acq, idx) => {
                const shortRemark  = (acq.remarks?.length > 30)
                    ? acq.remarks.substring(0, 27) + '...'
                    : (acq.remarks || '-');
                const shortLibrary = (acq.library_name?.length > 25)
                    ? acq.library_name.substring(0, 22) + '...'
                    : (acq.library_name || '-');

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="border px-2 py-1 text-xs" title="${esc(acq.library_name)}">${esc(shortLibrary)}</td>
                    <td class="border px-2 py-1 text-xs">${esc(acq.source)}</td>
                    <td class="border px-2 py-1 text-xs">${esc(acq.date_acquired)}</td>
                    <td class="border px-2 py-1 text-xs">${esc(acq.cost) || '-'}</td>
                    <td class="border px-2 py-1 text-xs">${esc(acq.iar) || '-'}</td>
                    <td class="border px-2 py-1 text-xs" title="${esc(acq.remarks)}">${esc(shortRemark)}</td>
                    <td class="border px-2 py-1 text-center text-xs">${acq.usable || 0}</td>
                    <td class="border px-2 py-1 text-center text-xs">${acq.partially_damaged || 0}</td>
                    <td class="border px-2 py-1 text-center text-xs">${acq.damaged || 0}</td>
                    <td class="border px-2 py-1 text-center text-xs">${acq.lost || 0}</td>
                    <td class="border px-2 py-1 text-center text-xs">${acq.condemnable || 0}</td>
                    <td class="border px-2 py-1 text-center text-xs font-semibold">${acq.total_quantity || 0}</td>
                    <td class="border px-2 py-1 text-center">
                        <div class="flex justify-center gap-1">
                            <button type="button" data-action="edit" data-index="${idx}"
                                    class="p-1 rounded hover:bg-blue-100 text-blue-600" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                </svg>
                            </button>
                            <button type="button" data-action="delete" data-index="${idx}"
                                    class="p-1 rounded hover:bg-red-100 text-red-600" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                        a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                                        M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                </svg>
                            </button>
                        </div>
                    </td>`;
                tableBody.appendChild(row);
            });
        }

        tableBody.addEventListener('click', e => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const idx = parseInt(btn.dataset.index);

            if (btn.dataset.action === 'edit') {
                editIndex = idx;
                addBtn.textContent = '✔ Update Acquisition';
                setFieldValues(acquisitions[idx]);
                libraryEl?.scrollIntoView({ behavior: 'smooth' });
            } else if (btn.dataset.action === 'delete') {
                if (!confirm('Remove this acquisition?')) return;
                acquisitions.splice(idx, 1);
                if (editIndex === idx) {
                    editIndex = null;
                    addBtn.textContent = '➕ Add Acquisition';
                }
                render();
                updateHidden();
            }
        });

        function updateHidden() {
            hiddenInput.value = JSON.stringify(acquisitions);
        }

        form.addEventListener('submit', e => {
            updateHidden();
            saveBtn.disabled = true;
            saveBtnText.classList.add('hidden');
            saveBtnLoading.classList.remove('hidden');
        });

        function esc(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(String(str ?? '')));
            return div.innerHTML;
        }

        render();
    })();
</script>

@endif
