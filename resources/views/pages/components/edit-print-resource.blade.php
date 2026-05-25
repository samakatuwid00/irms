{{-- Edit Print Resource Form Component --}}
{{-- Resource metadata (title, authors, image, grade levels, etc.) is READ-ONLY. --}}
{{-- Only the Acquisition section is editable. --}}
@if($printResource)

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

<form id="print-edit"
      action="{{ route('update-print-resource', $printResource->id) }}"
      class="resource-form space-y-8"
      method="POST">
    @csrf
    @method('PUT')

    {{-- ── Hidden pass-through fields to satisfy the controller validator ──
         The controller still validates these fields (title, type, etc.) so we
         pass the current values unchanged. Nothing about the resource itself
         changes — only acquisitions are written. --}}
    <input type="hidden" name="title"     value="{{ $printResource->printTitle->title }}">
    <input type="hidden" name="type"      value="{{ $printResource->print_type_id }}">
    <input type="hidden" name="publisher" value="{{ $printResource->publisher }}">
    <input type="hidden" name="volume"    value="{{ $printResource->volume }}">
    <input type="hidden" name="edition"   value="{{ $printResource->edition }}">
    <input type="hidden" name="copyright" value="{{ $printResource->copyright }}">
    <input type="hidden" name="isbn"      value="{{ $printResource->isbn }}">
    <input type="hidden" name="pages"     value="{{ $printResource->pages }}">
    <input type="hidden" name="authors"
           value="{{ json_encode($printResource->printTitle->authors->pluck('author_name')->toArray()) }}">

    {{-- Pass all currently assigned subject/grade-level IDs through unchanged --}}
    @foreach($selectedSubjectGradeLevels as $sglId)
        <input type="hidden" name="subject_grade_levels[]" value="{{ $sglId }}">
    @endforeach

    {{-- library_id: required by controller validation.
         Use the library from the first existing acquisition if available;
         for division users fall back to the first library in the dropdown. --}}
    @if (Auth::user()->userType?->level === 3)
        <input type="hidden" name="library_id"
               value="{{ $printResource->printAcquisitions->first()?->library_id ?? ($divisionLibraries->first()?->id ?? '') }}">
    @else
        <input type="hidden" name="library_id"
               value="{{ $printResource->printAcquisitions->first()?->library_id ?? '' }}">
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
                <img src="{{ $printResource->cover
                                ? asset('storage/' . $printResource->cover)
                                : asset('assets/images/default.jpg') }}"
                     alt="Cover"
                     class="w-32 h-44 object-cover rounded-lg border border-blue-200 shadow-sm">
            </div>

            {{-- Metadata --}}
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Title</label>
                    <p class="font-semibold text-gray-900">{{ $printResource->printTitle->title ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Author(s)</label>
                    <p class="text-gray-800">
                        {{ $printResource->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Type</label>
                    <p class="text-gray-800">{{ $printResource->type->type_name ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Publisher</label>
                    <p class="text-gray-800">{{ $printResource->publisher ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Edition</label>
                    <p class="text-gray-800">{{ $printResource->edition ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Volume</label>
                    <p class="text-gray-800">{{ $printResource->volume ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Copyright</label>
                    <p class="text-gray-800">{{ $printResource->copyright ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">Pages</label>
                    <p class="text-gray-800">{{ $printResource->pages ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-0.5">ISBN</label>
                    <p class="text-gray-800">{{ $printResource->isbn ?? '-' }}</p>
                </div>

                {{-- Subject / Grade Level --}}
                @php
                    $displaySubjects = collect();
                    if (!empty($selectedSubjectGradeLevels)) {
                        $displaySubjects = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])
                            ->whereIn('id', $selectedSubjectGradeLevels)
                            ->get();
                    }
                @endphp
                @if($displaySubjects->isNotEmpty())
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Subject / Grade Level</label>
                        <p class="text-sm text-gray-700">
                            {{ $displaySubjects->map(fn($sgl) =>
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
                <select id="acqLibraryId" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                    <option value="" disabled selected>Select library</option>
                    @foreach ($divisionLibraries as $lib)
                        <option value="{{ $lib->id }}" data-name="{{ $lib->library_name }}">
                            {{ $lib->library_name }}
                        </option>
                    @endforeach
                </select>
            @elseif (Auth::user()->userType?->level === 4)
                <input id="acqLibraryId" type="hidden"
                       value="{{ $regionLibrary->id ?? '' }}"
                       data-name="{{ $regionLibrary->library_name ?? '' }}">
            @elseif (Auth::user()->userType?->level === 1)
                <input id="acqLibraryId" type="hidden"
                       value="{{ $schoolLibrary->id ?? '' }}"
                       data-name="{{ $schoolLibrary->library_name ?? '' }}">
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
                        <td colspan="13" class="text-center text-gray-400 py-3">Loading acquisitions...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <input type="hidden" name="acquisitions" id="acquisitionsInput">

    {{-- ========================= SUBMIT ========================= --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('print-resources') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200 border border-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Print Resources
        </a>

        <div class="flex gap-3">
            <a href="{{ route('print-resources') }}"
               class="px-5 py-2 bg-white text-gray-600 text-sm rounded border border-gray-300 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" id="updatePrintBtn"
                    class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="updatePrintText">Update Acquisitions</span>
                <span id="updatePrintLoading" class="hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                </span>
            </button>
        </div>
    </div>

</form>

@php
    $userLevel   = Auth::user()->userType?->level;
    
    $editableLibraryIds = [];
    
    if ($userLevel === 1) {
        if ($schoolLibrary) {
            $editableLibraryIds[] = $schoolLibrary->id;
        }
    } elseif ($userLevel === 3) {
        $editableLibraryIds = $divisionLibraries->pluck('id')->toArray();
    } elseif ($userLevel === 4) {
        if ($regionLibrary) {
            $editableLibraryIds[] = $regionLibrary->id;
        }
    }

    $acquisitionsData = [];
    $acqQuery = $printResource->printAcquisitions ?? collect();

    foreach ($acqQuery as $acq) {
        $isUserLibrary = in_array($acq->library_id, $editableLibraryIds);
        
        $acquisitionsData[] = [
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
            'isUserLibrary'     => $isUserLibrary,
        ];
    }
@endphp

<script>
    window.__printAcquisitions = @json($acquisitionsData);
</script>

@vite('resources/js/edit-print-resource.js')

@endif