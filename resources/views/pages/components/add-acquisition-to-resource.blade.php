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
            <a href="{{ route('print-resource.create') }}"
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

                    @if (Auth::user()->userType?->level === 3)
                        <label class="block text-sm font-medium mb-1">
                            Library <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-gray-400">(saved with each acquisition)</span>
                        </label>
                        {{-- Division user: dropdown of their division's libraries --}}
                        <select id="acqLibraryId" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
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
                    @elseif (Auth::user()->userType?->level === 1)
                        {{-- School user: single fixed library --}}
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
                <a href="{{ route('print-resource.create') }}"
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
@endsection

@push('scripts')
    @vite('resources/js/add-print-acquisition.js')
@endpush