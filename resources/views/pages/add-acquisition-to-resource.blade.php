@extends('pages.layout.layout')

@section('title', 'Add Acquisition')
@section('page-title', 'Add Acquisition')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('breadcrumb', 'Add Acquisition')

@section('content')
<div class="p-6 space-y-6">
    @include('pages.partials.page-header')

    <div class="bg-white shadow rounded-xl p-6">

        {{-- Page Heading --}}
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('search-print-resource.index') }}"
               class="text-gray-400 hover:text-gray-700 transition-colors" title="Back to Search">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Add Acquisition</h2>
                <p class="text-sm text-gray-500">Resource details are locked. Fill in the acquisition information below.</p>
            </div>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-200 rounded text-red-800 flex justify-between items-start"
                 id="flash-validation">
                <ul class="list-disc pl-5 flex-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="ml-4 text-red-800 font-bold hover:text-red-900"
                        onclick="document.getElementById('flash-validation').remove()">&times;</button>
            </div>
        @endif

        <form
            id="addAcquisitionForm"
            action="{{ route('search-print-resource.store', $resource->id) }}"
            method="POST"
            class="space-y-8"
        >
            @csrf

            {{-- ========================= RESOURCE DETAILS (READ-ONLY) ========================= --}}
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
                        <img
                            src="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                            alt="Cover"
                            class="w-32 h-44 object-cover rounded-lg border border-blue-200 shadow-sm"
                        >
                    </div>

                    {{-- Details --}}
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Title</label>
                            <p class="font-semibold text-gray-900">{{ $resource->printTitle->title ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Author(s)</label>
                            <p class="text-gray-800">{{ $resource->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Type</label>
                            <p class="text-gray-800">{{ $resource->type->type_name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Publisher</label>
                            <p class="text-gray-800">{{ $resource->publisher ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Edition</label>
                            <p class="text-gray-800">{{ $resource->edition ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Volume</label>
                            <p class="text-gray-800">{{ $resource->volume ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Copyright</label>
                            <p class="text-gray-800">{{ $resource->copyright ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Pages</label>
                            <p class="text-gray-800">{{ $resource->pages ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">ISBN</label>
                            <p class="text-gray-800">{{ $resource->isbn ?? '-' }}</p>
                        </div>

                        {{-- Subjects --}}
                        @if($subjects->isNotEmpty())
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Subject / Grade Level</label>
                            <p class="text-sm text-gray-700">
                                {{ $subjects->map(fn($sgl) => ($sgl->subject->subject_name ?? 'N/A') . ' (' . ($sgl->gradeLevel->grade ?? 'N/A') . ')')->join(', ') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ========================= ACQUISITION & CONDITION ========================= --}}
            <div class="bg-gray-50 border border-gray-300 rounded-xl p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-700">Acquisition & Condition Details</h3>

                {{-- Library selector (now part of each acquisition) --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Library <span class="text-red-500">*</span>
                        <span class="text-xs font-normal text-gray-400">(saved with each acquisition)</span>
                    </label>

                    @if (Auth::user()->userType?->level === 3)
                        {{-- Division user: dropdown of their division's libraries --}}
                        <select id="acqLibraryId" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                            <option value="" disabled selected>Select library</option>
                            @foreach ($divisionLibraries as $lib)
                                <option value="{{ $lib->id }}" data-name="{{ $lib->library_name }}">
                                    {{ $lib->library_name }}
                                </option>
                            @endforeach
                        </select>
                    @elseif (Auth::user()->userType?->level === 4)
                        {{-- Region user: single fixed library --}}
                        <input id="acqLibraryId" type="hidden"
                            value="{{ $regionLibrary->id ?? '' }}"
                            data-name="{{ $regionLibrary->library_name ?? '' }}">
                        <p class="text-sm text-gray-700 border border-gray-200 bg-white rounded px-3 py-2">
                            {{ $regionLibrary->library_name ?? 'Region Library' }}
                        </p>
                    @elseif (Auth::user()->userType?->level === 1)
                        {{-- School user: single fixed library --}}
                        <input id="acqLibraryId" type="hidden"
                            value="{{ $schoolLibrary->id ?? '' }}"
                            data-name="{{ $schoolLibrary->library_name ?? '' }}">
                        <p class="text-sm text-gray-700 border border-gray-200 bg-white rounded px-3 py-2">
                            {{ $schoolLibrary->library_name ?? 'School Library' }}
                        </p>
                    @else
                        <input id="acqLibraryId" type="hidden" value="" data-name="">
                        <p class="text-sm text-yellow-600">No library assigned to your account.</p>
                    @endif
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Remarks
                        <span class="text-xs text-gray-500">(saved with each acquisition)</span>
                    </label>
                    <textarea id="acqRemarks" rows="3"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        placeholder="Any notes, condition details, or special remarks for this batch..."></textarea>
                </div>

                {{-- Source / Date / Cost / IAR --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Source <span class="text-red-500">*</span></label>
                        <select id="acqSource" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
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
                        <input type="date" id="acqDate" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Cost</label>
                        <input type="number" step="0.01" id="acqCost" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">IAR No.</label>
                        <input type="text" id="acqIar" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
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
                            <input type="number" id="acqUsable" value="0" min="0"
                                class="qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Partially Damaged</label>
                            <input type="number" id="acqPartiallyDamaged" value="0" min="0"
                                class="qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Damaged</label>
                            <input type="number" id="acqDamaged" value="0" min="0"
                                class="qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Lost</label>
                            <input type="number" id="acqLost" value="0" min="0"
                                class="qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Condemnable</label>
                            <input type="number" id="acqCondemnable" value="0" min="0"
                                class="qty w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs mb-1">Total Quantity</label>
                            <input type="number" id="totalQuantity" readonly
                                class="w-full bg-gray-100 border border-gray-300 rounded px-3 py-2 text-sm font-semibold">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" id="addAcquisitionBtn"
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
                        <tbody id="acquisitionTableBody">
                            <tr>
                                <td colspan="13" class="text-center text-gray-400 py-3">
                                    No acquisitions added yet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <input type="hidden" name="acquisitions" id="acquisitionsInput">

            {{-- ========================= SUBMIT ========================= --}}
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('search-print-resource.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 underline">
                    ← Back to Search
                </a>
                <button type="submit" id="saveBtn"
                    class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span id="saveBtnText">Save Acquisition(s)</span>
                    <span id="saveBtnLoading" class="hidden">
                        <svg class="inline animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>

        </form>
    </div>
</div>

{{-- =================== INLINE JAVASCRIPT =================== --}}
<script>
    (function () {
        /* ---- State ---- */
        const acquisitions = [];
        let editIndex = null;

        /* ---- DOM refs ---- */
        const tableBody      = document.getElementById('acquisitionTableBody');
        const hiddenInput    = document.getElementById('acquisitionsInput');
        const addBtn         = document.getElementById('addAcquisitionBtn');
        const totalField     = document.getElementById('totalQuantity');
        const form           = document.getElementById('addAcquisitionForm');
        const saveBtn        = document.getElementById('saveBtn');
        const saveBtnText    = document.getElementById('saveBtnText');
        const saveBtnLoading = document.getElementById('saveBtnLoading');

        /* ---- Library element: works for both <select> and <input type="hidden"> ---- */
        const libraryEl = document.getElementById('acqLibraryId');

        function getLibraryId() {
            return libraryEl ? libraryEl.value : '';
        }

        function getLibraryName() {
            if (!libraryEl) return '';
            // For <select>, read the selected option's data-name attribute
            if (libraryEl.tagName === 'SELECT') {
                const opt = libraryEl.options[libraryEl.selectedIndex];
                return opt ? (opt.dataset.name || opt.text) : '';
            }
            // For <input type="hidden">, read data-name attribute
            return libraryEl.dataset.name || '';
        }

        /* ============================================================
        QUANTITY CALCULATION
        ============================================================ */
        document.querySelectorAll('.qty').forEach(input => {
            input.addEventListener('input', calcTotal);
        });

        function calcTotal() {
            let total = 0;
            document.querySelectorAll('.qty').forEach(inp => { total += parseInt(inp.value) || 0; });
            totalField.value = total;
        }

        /* ============================================================
        GET / SET / RESET FIELDS
        ============================================================ */
        function getFieldValues() {
            return {
                library_id:        getLibraryId(),
                library_name:      getLibraryName(),
                source:            document.getElementById('acqSource').value,
                date_acquired:     document.getElementById('acqDate').value,
                cost:              document.getElementById('acqCost').value,
                iar:               document.getElementById('acqIar').value,
                remarks:           document.getElementById('acqRemarks').value,
                usable:            document.getElementById('acqUsable').value,
                partially_damaged: document.getElementById('acqPartiallyDamaged').value,
                damaged:           document.getElementById('acqDamaged').value,
                lost:              document.getElementById('acqLost').value,
                condemnable:       document.getElementById('acqCondemnable').value,
                total_quantity:    totalField.value,
            };
        }

        function setFieldValues(acq) {
            // Library: only restore for <select>; hidden inputs are fixed
            if (libraryEl && libraryEl.tagName === 'SELECT' && acq.library_id) {
                libraryEl.value = acq.library_id;
            }
            document.getElementById('acqSource').value            = acq.source ?? '';
            document.getElementById('acqDate').value              = acq.date_acquired ?? '';
            document.getElementById('acqCost').value              = acq.cost ?? '';
            document.getElementById('acqIar').value               = acq.iar ?? '';
            document.getElementById('acqRemarks').value           = acq.remarks ?? '';
            document.getElementById('acqUsable').value            = acq.usable ?? '0';
            document.getElementById('acqPartiallyDamaged').value  = acq.partially_damaged ?? '0';
            document.getElementById('acqDamaged').value           = acq.damaged ?? '0';
            document.getElementById('acqLost').value              = acq.lost ?? '0';
            document.getElementById('acqCondemnable').value       = acq.condemnable ?? '0';
            calcTotal();
        }

        function resetFields() {
            if (libraryEl && libraryEl.tagName === 'SELECT') libraryEl.selectedIndex = 0;
            document.getElementById('acqSource').value            = '';
            document.getElementById('acqDate').value              = '';
            document.getElementById('acqCost').value              = '';
            document.getElementById('acqIar').value               = '';
            document.getElementById('acqRemarks').value           = '';
            document.getElementById('acqUsable').value            = '0';
            document.getElementById('acqPartiallyDamaged').value  = '0';
            document.getElementById('acqDamaged').value           = '0';
            document.getElementById('acqLost').value              = '0';
            document.getElementById('acqCondemnable').value       = '0';
            calcTotal();
        }

        /* ============================================================
        ADD / EDIT / DELETE
        ============================================================ */
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

        function editAcq(index) {
            const acq = acquisitions[index];
            if (!acq) return;
            editIndex = index;
            addBtn.textContent = '✔ Update Acquisition';
            setFieldValues(acq);
            libraryEl?.scrollIntoView({ behavior: 'smooth' });
        }

        function deleteAcq(index) {
            if (!confirm('Remove this acquisition?')) return;
            acquisitions.splice(index, 1);
            if (editIndex === index) { editIndex = null; addBtn.textContent = '➕ Add Acquisition'; }
            render();
            updateHidden();
        }

        /* ============================================================
        RENDER TABLE  (13 columns now: Library + original 12)
        ============================================================ */
        function render() {
            tableBody.innerHTML = '';

            if (!acquisitions.length) {
                tableBody.innerHTML =
                    '<tr><td colspan="13" class="text-center text-gray-400 py-3">No acquisitions added yet</td></tr>';
                return;
            }

            acquisitions.forEach((acq, idx) => {
                const shortRemark = (acq.remarks?.length > 30)
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
                    <td class="border px-2 py-1 text-xs">${esc(shortRemark)}</td>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                </svg>
                            </button>
                            <button type="button" data-action="delete" data-index="${idx}"
                                class="p-1 rounded hover:bg-red-100 text-red-600" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Event delegation
        tableBody.addEventListener('click', e => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const idx = parseInt(btn.dataset.index);
            if (btn.dataset.action === 'edit') editAcq(idx);
            else if (btn.dataset.action === 'delete') deleteAcq(idx);
        });

        /* ============================================================
        HIDDEN INPUT & FORM SUBMIT
        ============================================================ */
        function updateHidden() {
            hiddenInput.value = JSON.stringify(acquisitions);
        }

        form.addEventListener('submit', e => {
            if (!acquisitions.length) {
                e.preventDefault();
                alert('Please add at least one acquisition before saving.');
                return;
            }
            updateHidden();
            saveBtn.disabled = true;
            saveBtnText.classList.add('hidden');
            saveBtnLoading.classList.remove('hidden');
        });

        /* ============================================================
        HELPERS
        ============================================================ */
        function esc(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(String(str ?? '')));
            return div.innerHTML;
        }
    })();
</script>
@endsection
