@extends('pages.layout.layout')

@section('title', 'Add Print Resource')
@section('page-title', 'Add Print Resource')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Add Print Resource')

@section('content')
<div class="-mx-3 space-y-4 sm:-mx-2 lg:mx-0">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Add Print Resource</h1>
    </div>

    {{-- ===== FLASH MESSAGES ===== --}}
    @if(session('success'))
        <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded flex justify-between items-center" id="flash-success">
            <span>{{ session('success') }}</span>
            <button type="button" class="text-green-800 font-bold hover:text-green-900" onclick="document.getElementById('flash-success').remove()">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded flex justify-between items-center" id="flash-error">
            <span>{{ session('error') }}</span>
            <button type="button" class="text-red-800 font-bold hover:text-red-900" onclick="document.getElementById('flash-error').remove()">&times;</button>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-200 rounded text-red-800 flex justify-between items-start" id="flash-validation">
            <ul class="list-disc pl-5 flex-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="ml-4 text-red-800 font-bold hover:text-red-900" onclick="document.getElementById('flash-validation').remove()">&times;</button>
        </div>
    @endif

    @php
        $isEditing  = isset($editResource);
        $isDivision = $isDivision ?? false;
    @endphp

    {{-- ===== PAGE-LEVEL TABS ===== --}}
    <div class="mb-5 sm:mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-4 overflow-x-auto sm:gap-6" id="pageTabs">
            <button type="button" data-page-tab="tab-search"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                Search Masterlist
            </button>

            @if ($isDivision)
                <button type="button" data-page-tab="tab-add" id="tabAddBtn"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 {{ $isEditing ? '' : 'hidden' }}">
                    {{ $isEditing ? 'Edit Request' : 'Add to Masterlist' }}
                </button>
            @else
                <button type="button" data-page-tab="tab-add" id="tabAddBtn"
                        class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 {{ $isEditing ? '' : 'hidden' }}">
                    {{ $isEditing ? 'Edit Request' : 'Request Add to Masterlist' }}
                </button>
            @endif

            {{-- My Requests tab: only for school users (not division) --}}
            @if(!$isDivision)
            <button type="button" data-page-tab="tab-requests"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                My Requests
                @if(isset($pendingCount) && $pendingCount > 0)
                    <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">{{ $pendingCount }}</span>
                @endif
            </button>
            @endif
        </nav>
    </div>

    {{-- =================================================================
        TAB 1 - SEARCH EXISTING
    ================================================================== --}}
    <div id="tab-search" class="page-tab-content hidden">
        <div class="space-y-6">
            <div>
                <h2 class="text-base font-semibold text-gray-800">Search Existing Print Resources</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Search the masterlist by title, author, ISBN, publisher, or subject, then add your acquisition records to an existing entry.
                </p>
            </div>
            <div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <input type="text" id="searchInput" placeholder="Search title, author, ISBN..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            autocomplete="off">
                        <span id="searchSpinner" class="absolute right-3 top-3.5 hidden">
                            <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                        </span>
                    </div>
                    <button id="searchBtn" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors sm:w-auto">Search</button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Minimum 2 characters to search.</p>
            </div>
            <div id="resultsArea" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Search Results</h3>
                    <span id="resultCount" class="text-xs text-gray-400"></span>
                </div>
                <div id="resultsList" class="space-y-3"></div>
            </div>
            <div id="emptyState" class="hidden text-center py-16 text-gray-400">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="font-medium">No titles found</p>
                <p class="text-sm mt-1">Try a different keyword or <button type="button" class="trigger-tab-add text-blue-600 underline">Request Add to Masterlist</button>.</p>
            </div>
            <div id="initialHint" class="text-center py-16 text-gray-400">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                </svg>
                <p class="font-medium">Start by searching the masterlist</p>
            </div>
        </div>
    </div>

    {{-- =================================================================
        TAB 2 - MANUAL ADD  /  EDIT REQUEST  (same form, dual purpose)
    ================================================================== --}}
    <div id="tab-add" class="page-tab-content hidden">

        {{-- Context-sensitive header --}}
        <div class="mb-5 flex items-start justify-between">
            <div>
                @if($isEditing)
                    <h2 class="text-base font-semibold text-gray-800">Edit Resource Request</h2>
                    <p class="text-sm text-gray-500 mt-1">Update the details of your pending submission.</p>
                @elseif($isDivision)
                    <h2 class="text-base font-semibold text-gray-800">Add New Resource to Masterlist</h2>
                    <p class="text-sm text-gray-500 mt-1">As a division account, resources you add are automatically approved and added to the masterlist.</p>
                @else
                    <h2 class="text-base font-semibold text-gray-800">Submit a New Resource Request</h2>
                    <p class="text-sm text-gray-500 mt-1">Fill in the details below. Your submission will be reviewed before it appears in the masterlist.</p>
                @endif
            </div>
            @if($isEditing)
                {{-- Back button: resets form and goes back to My Requests tab --}}
                <button type="button" id="backToRequestsBtn"
                        class="text-sm text-blue-600 hover:underline flex items-center gap-1 mt-1">
                    &larr; Back to My Requests
                </button>
            @endif
        </div>

        {{-- ---- THE FORM ---- --}}
        @if($isEditing)
            <form id="print"
                action="{{ route('print-resource.update', $editResource->id) }}"
                class="resource-form space-y-8"
                method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
        @else
            <form id="print"
                action="{{ route('print-resource.store') }}"
                class="resource-form space-y-8"
                method="POST"
                enctype="multipart/form-data">
                @csrf
        @endif

            {{-- IMAGE + BASIC INFO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

                {{-- Image --}}
                <div class="h-full">
                    <div class="h-full flex flex-col items-center justify-between border-2 border-dashed border-blue-500 rounded-lg p-4 text-center">
                        @if($isEditing && $editResource->cover)
                            <img id="imagePreview"
                                src="{{ asset('storage/' . $editResource->cover) }}"
                                data-default-src="{{ asset('storage/' . $editResource->cover) }}"
                                alt="Image preview"
                                class="w-full flex-1 object-cover rounded mb-4">
                        @else
                            <img id="imagePreview"
                                src="{{ asset('assets/images/def.jpg') }}"
                                data-default-src="{{ asset('assets/images/def.jpg') }}"
                                alt="Image preview"
                                class="w-full flex-1 object-cover rounded mb-4">
                        @endif
                        <input type="file" name="image" id="imageUpload" class="hidden" accept="image/*">
                        <label for="imageUpload" class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            {{ $isEditing ? 'Change Image' : 'Choose Image' }}
                        </label>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG &bull; Max 5MB{{ $isEditing ? '. Leave blank to keep current.' : '' }}</p>
                    </div>
                </div>

                {{-- Inputs --}}
                <div class="md:col-span-2 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required
                                value="{{ old('title', $isEditing ? ($editResource->printTitle->title ?? '') : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Authors</label>
                            <div class="flex flex-wrap gap-2 border border-gray-300 rounded px-2 py-2" id="author-wrapper">
                                <input type="text" id="author-input" class="flex-1 outline-none border-none" placeholder="Type author name and press Enter">
                            </div>
                            <input type="hidden" name="authors" id="authors-hidden" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Publisher</label>
                            <input type="text" name="publisher"
                                value="{{ old('publisher', $isEditing ? $editResource->publisher : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm mb-1">Type <span class="text-red-500">*</span></label>
                                <select name="type" required class="w-full border border-gray-300 rounded px-3 py-2">
                                    <option disabled {{ !$isEditing && !old('type') ? 'selected' : '' }}>Select type</option>
                                    @foreach ($printTypes as $type)
                                        <option value="{{ $type->id }}"
                                            {{ old('type', $isEditing ? $editResource->print_type_id : '') == $type->id ? 'selected' : '' }}>
                                            {{ $type->type_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Volume</label>
                                <input name="volume"
                                    value="{{ old('volume', $isEditing ? $editResource->volume : '') }}"
                                    class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Edition</label>
                                <input name="edition"
                                    value="{{ old('edition', $isEditing ? $editResource->edition : '') }}"
                                    class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm mb-1">Copyright</label>
                                <select name="copyright" class="w-full border border-gray-300 rounded px-3 py-2">
                                    <option value="">Select year</option>
                                    <option value="no_copyright" {{ (string)old('copyright', $isEditing ? $editResource->copyright : '') === 'no_copyright' ? 'selected' : '' }}>No Copyright</option>
                                    @php $selectedCopyright = old('copyright', $isEditing ? $editResource->copyright : ''); @endphp
                                    @for ($year = date('Y'); $year >= 1900; $year--)
                                        <option value="{{ $year }}" {{ (string)$selectedCopyright === (string)$year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm mb-1">ISBN</label>
                                <input name="isbn"
                                    value="{{ old('isbn', $isEditing ? $editResource->isbn : '') }}"
                                    class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Pages</label>
                                <input name="pages" type="number"
                                    value="{{ old('pages', $isEditing ? $editResource->pages : '') }}"
                                    class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBJECT-GRADE LEVEL --}}
            @php
                $useKeyStageTabs = in_array((int) $level, [2, 3], true);
                $stages = [
                    'KS1' => ['tab' => 'stage1', 'label' => 'Key Stage 1', 'grades' => [0=>'K',1=>'1',2=>'2',3=>'3']],
                    'KS2' => ['tab' => 'stage2', 'label' => 'Key Stage 2', 'grades' => [4=>'4',5=>'5',6=>'6']],
                    'KS3' => ['tab' => 'jhs',    'label' => 'Junior High',  'grades' => [7=>'7',8=>'8',9=>'9',10=>'10']],
                    'KS4' => ['tab' => 'shs',    'label' => 'Senior High',  'grades' => [11=>'11',12=>'12']],
                ];
                $groupedByStage = $subjectGradeLevels->groupBy(['key_stage', 'subject_name']);
                $gradeColumns = $subjectGradeLevels
                    ->unique('grade_level_id')
                    ->sortBy('sort_order')
                    ->values();
                $groupedSubjects = $subjectGradeLevels->groupBy('subject_name');
                $checkedIds = old('subject_grade_levels', $isEditing ? ($editingSglIds ?? []) : []);
            @endphp

            @if ($useKeyStageTabs)
                <div class="space-y-4">
                    <div class="flex gap-6 border-b border-gray-300 overflow-x-auto">
                        @foreach ($stages as $stage)
                            <button type="button"
                                    class="sgl-tab-btn {{ $loop->first ? 'active border-blue-600 text-blue-600' : 'border-transparent text-gray-600' }} whitespace-nowrap pb-2 px-1 text-sm font-medium border-b-2"
                                    data-sgl-tab="{{ $stage['tab'] }}">
                                {{ $stage['label'] }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($stages as $stageKey => $stage)
                        <div id="{{ $stage['tab'] }}" class="sgl-tab-content {{ !$loop->first ? 'hidden' : '' }}">
                            <table class="w-full border border-gray-300 text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border border-gray-300 px-3 py-2 text-left w-72">Subject</th>
                                        @foreach ($stage['grades'] as $gradeLabel)
                                            <th class="border border-gray-300">{{ $gradeLabel }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupedByStage[$stageKey] ?? [] as $subject => $rows)
                                        @php $gradeMap = collect($rows)->keyBy('sort_order'); @endphp
                                        <tr>
                                            <td class="border border-gray-300 px-3 py-2">{{ $subject }}</td>
                                            @foreach ($stage['grades'] as $sortOrder => $label)
                                                <td class="border border-gray-300 text-center">
                                                    @if ($gradeMap->has($sortOrder))
                                                        <input type="checkbox"
                                                            name="subject_grade_levels[]"
                                                            value="{{ $gradeMap[$sortOrder]->subject_grade_level_id }}"
                                                            {{ in_array($gradeMap[$sortOrder]->subject_grade_level_id, $checkedIds, true) ? 'checked' : '' }}>
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
            @elseif ($subjectGradeLevels->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $curriculumMessage ?? 'No subject and grade-level mappings are available.' }}
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-300">
                    <table class="min-w-max w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border-b border-r border-gray-300 px-3 py-2 text-left sticky left-0 bg-gray-100 min-w-64">
                                    Subject
                                </th>
                                @foreach ($gradeColumns as $grade)
                                    <th class="border-b border-r border-gray-300 px-3 py-2 text-center min-w-24">
                                        {{ $grade->grade_level }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupedSubjects as $subject => $rows)
                                @php $gradeMap = collect($rows)->keyBy('grade_level_id'); @endphp
                                <tr>
                                    <td class="border-b border-r border-gray-300 px-3 py-2 sticky left-0 bg-white font-medium">
                                        {{ $subject }}
                                    </td>
                                    @foreach ($gradeColumns as $grade)
                                        @php $mapping = $gradeMap->get($grade->grade_level_id); @endphp
                                        <td class="border-b border-r border-gray-300 text-center px-3 py-2">
                                            @if ($mapping)
                                                <input type="checkbox"
                                                    name="subject_grade_levels[]"
                                                    value="{{ $mapping->subject_grade_level_id }}"
                                                    {{ in_array($mapping->subject_grade_level_id, $checkedIds, true) ? 'checked' : '' }}>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @error('subject_grade_levels')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('subject_grade_levels.*')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            {{-- SUBMIT / CANCEL --}}
            <div class="flex justify-end gap-3">
                @if($isEditing)
                    {{-- Cancel: resets form, goes back to My Requests --}}
                    <button type="button" id="cancelEditBtn"
                            class="px-5 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-sm">
                        Cancel
                    </button>
                @endif
                <button type="submit" id="savePrintBtn"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="savePrintText">
                        @if($isEditing)
                            Save Changes
                        @elseif($isDivision)
                            Add to Masterlist
                        @else
                            Submit Request
                        @endif
                    </span>
                    <span id="savePrintLoading" class="hidden"><i class="fas fa-spinner fa-spin mr-2"></i>Saving...</span>
                </button>
            </div>

        </form>
    </div>

    {{-- =================================================================
        TAB 3 - MY REQUESTS  (school users only)
    ================================================================== --}}
    @if(!$isDivision)
    <div id="tab-requests" class="page-tab-content hidden">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-700">My Submitted Requests</h3>
                <p class="text-xs text-gray-400">Pending requests can still be edited or deleted.</p>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-3 text-left w-10">Cover</th>
                            <th class="px-3 py-3 text-left">Title</th>
                            <th class="px-3 py-3 text-left">Author(s)</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Publisher</th>
                            <th class="px-3 py-3 text-left">Edition</th>
                            <th class="px-3 py-3 text-left">Copyright</th>
                            <th class="px-3 py-3 text-left">Subjects / Grades</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3 text-center">Date Submitted</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($myRequests as $resource)
                            <tr class="hover:bg-gray-50 transition-colors {{ $isEditing && $editResource->id === $resource->id ? 'bg-blue-50 ring-1 ring-blue-200' : '' }}">
                                <td class="px-3 py-2">
                                    <img src="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
alt="cover" class="cover-img w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-40">
                                    <span title="{{ $resource->printTitle->title ?? '' }}">{{ Str::limit($resource->printTitle->title ?? '-', 38) }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 max-w-32.5">
                                    {{ Str::limit($resource->printTitle->authors->pluck('author_name')->join(', ') ?: '-', 32) }}
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $resource->type->type_name ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $resource->publisher ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $resource->edition ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $resource->copyright ?? '-' }}</td>

                                {{-- Subject / Grade Levels --}}
                                <td class="px-3 py-2 text-gray-600 text-xs max-w-50">
                                    @php
                                        $sglIds = $resource->subject_grade_level_ids ? explode(',', $resource->subject_grade_level_ids) : [];
                                        if (!empty($sglIds)) {
                                            $sgls    = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])->whereIn('id', $sglIds)->get();
                                            $sglText = $sgls->map(fn($s) => ($s->subject->subject_name ?? '') . '-' . ($s->gradeLevel->grade ?? ''))->join(', ');
                                        } else {
                                            $sglText = '-';
                                        }
                                    @endphp
                                    <span title="{{ $sglText }}">{{ Str::limit($sglText, 55) }}</span>
                                </td>

                                <td class="px-3 py-2 text-center">
                                    @if($resource->status == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                    @elseif($resource->status == 1)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center text-gray-500 text-xs whitespace-nowrap">{{ $resource->created_at?->format('M d, Y') ?? '-' }}</td>

                                {{-- ── ACTIONS COLUMN ── --}}
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1.5">

                                        {{-- View Details (always visible) --}}
                                        <div class="relative group">
                                            <button type="button"
                                                    class="req-view-btn inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 transition-colors"
                                                    data-id="{{ $resource->id }}"
                                                    data-status="{{ $resource->status }}"
                                                    data-cover="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                                                    data-title="{{ $resource->printTitle->title ?? '-' }}"
                                                    data-authors="{{ $resource->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}"
                                                    data-type="{{ $resource->type->type_name ?? '-' }}"
                                                    data-publisher="{{ $resource->publisher ?? '-' }}"
                                                    data-volume="{{ $resource->volume ?? '-' }}"
                                                    data-edition="{{ $resource->edition ?? '-' }}"
                                                    data-copyright="{{ $resource->copyright ?? '-' }}"
                                                    data-isbn="{{ $resource->isbn ?? '-' }}"
                                                    data-pages="{{ $resource->pages ?? '-' }}"
                                                    data-subjects="{{ $sglText }}"
                                                    data-submitted="{{ $resource->created_at?->format('M d, Y') ?? '-' }}"
                                                    data-edit-url="{{ route('print-resource.edit', $resource->id) }}"
                                                    data-delete-url="{{ route('print-resource.destroy', $resource->id) }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                View Details
                                            </span>
                                        </div>

                                        @if($resource->status == 0)
                                            {{-- Edit — full page nav to edit route; JS will open tab-add on load --}}
                                            <div class="relative group">
                                                <a href="{{ route('print-resource.edit', $resource->id) }}"
                                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-100 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                                    </svg>
                                                </a>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                    Edit Request
                                                </span>
                                            </div>

                                            {{-- Delete (pending only) --}}
                                            <div class="relative group">
                                                <form method="POST" action="{{ route('print-resource.destroy', $resource->id) }}"
                                                      onsubmit="return confirm('Delete this request? The title and authors will not be removed if other resources still reference them.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition-colors">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                    Delete
                                                </span>
                                            </div>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-gray-400 py-10">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm font-medium">No requests submitted yet.</p>
                                    <p class="text-xs mt-1">Use the <strong>Search Masterlist</strong> tab to search first, then submit a new request if your title is not found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($myRequests, 'hasPages') && $myRequests->hasPages())
                <div class="mt-4">{{ $myRequests->appends(['active_tab' => 'tab-requests'])->links('pagination::print-resource') }}</div>
            @endif
        </div>
    </div>
    @endif

    {{-- ===== MY REQUESTS VIEW MODAL ===== --}}
    <div id="reqViewModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="reqModalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl z-10 mb-10">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Request Details</h3>
                    <button id="closeReqModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-1 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    <div class="flex gap-5">
                        <div class="shrink-0">
                            <img id="rvm-cover" src="" alt="Cover"
                                 class="w-28 h-40 object-cover rounded-lg border border-gray-200 shadow-sm bg-gray-100">
                        </div>
                        <div class="flex-1 min-w-0 space-y-2.5">
                            <div>
                                <h4 id="rvm-title" class="text-lg font-bold text-gray-900 leading-snug"></h4>
                                <p id="rvm-authors" class="text-sm text-gray-500 mt-0.5 italic"></p>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-0.5">
                                <span id="rvm-type-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"></span>
                                <span id="rvm-status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm pt-1">
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Publisher</dt>
                                    <dd id="rvm-publisher" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Copyright</dt>
                                    <dd id="rvm-copyright" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Edition</dt>
                                    <dd id="rvm-edition" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Volume</dt>
                                    <dd id="rvm-volume" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">ISBN</dt>
                                    <dd id="rvm-isbn" class="text-gray-700 mt-0.5 font-mono text-xs tracking-wider"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pages</dt>
                                    <dd id="rvm-pages" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Date Submitted</dt>
                                    <dd id="rvm-submitted" class="text-gray-700 mt-0.5"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Subjects / Grade Levels</p>
                        <p id="rvm-subjects" class="text-sm text-gray-700 leading-relaxed"></p>
                    </div>
                </div>
                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <button id="closeReqModalFooter"
                            class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DETAIL MODAL (Search Existing tab) ===== --}}
    <div id="detailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="modalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative min-h-screen flex items-start justify-center p-3 pt-4 sm:p-4 sm:pt-8">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-6xl z-10 mb-6 sm:mb-10 overflow-hidden">
                <div class="sticky top-0 z-20 flex items-center justify-between gap-3 p-4 sm:p-5 border-b border-gray-200 bg-white">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800" id="modalTitle">Resource Details</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-2 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="modalLoading" class="flex justify-center items-center py-20">
                    <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </div>
                <div id="modalBody" class="hidden max-h-[calc(100vh-8rem)] overflow-y-auto p-4 space-y-5 sm:p-5 lg:p-6 lg:space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:gap-5">
                        <div class="shrink-0 flex justify-center sm:block">
                            <img id="modalCover" src="" alt="Cover" class="w-28 h-40 object-cover rounded-lg border border-gray-200 shadow-sm">
                        </div>
                        <div class="flex-1 min-w-0 space-y-2">
                            <h4 id="modalBookTitle" class="text-lg sm:text-xl font-bold text-gray-900 leading-snug break-words"></h4>
                            <p class="text-sm text-gray-600 break-words"><span class="font-medium">Author(s):</span> <span id="modalAuthors"></span></p>
                            <div class="pt-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Subject / Grade Level</p>
                                <p id="modalSubjects" class="text-sm text-gray-700 leading-relaxed break-words"></p>
                            </div>
                        </div>
                    </div>
                    <hr class="border-gray-200">
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-3">Available Editions
                            <span class="text-xs font-normal text-gray-400 ml-1">- click Add on the edition you want to copy to your library</span>
                        </p>
                        <div class="-mx-4 overflow-x-auto border border-gray-200 sm:mx-0 sm:rounded-lg">
                            <table class="min-w-[860px] w-full text-sm">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                                    <tr>
                                        <th class="px-3 py-2 text-left w-12">Cover</th>
                                        <th class="px-3 py-2 text-left">Type</th>
                                        <th class="px-3 py-2 text-left">Publisher</th>
                                        <th class="px-3 py-2 text-left">Edition</th>
                                        <th class="px-3 py-2 text-left">Volume</th>
                                        <th class="px-3 py-2 text-left">Copyright</th>
                                        <th class="px-3 py-2 text-left">ISBN</th>
                                        <th class="px-3 py-2 text-left">Pages</th>
                                        <th class="px-3 py-2 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="modalEditionsBody" class="divide-y divide-gray-100 bg-white"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ===== Seed edit-mode authors for JS ===== --}}
@if($isEditing)
<script>window.__editAuthors = @json($editingAuthors ?? []);</script>
@endif

<script>
(function () {
    const STORAGE_KEY     = 'addPrintResource_activeTab';
    const isDivision      = {{ $isDivision ? 'true' : 'false' }};
    const isEditing       = {{ $isEditing  ? 'true' : 'false' }};
    const VALID_TABS      = isDivision ? ['tab-search', 'tab-add'] : ['tab-search', 'tab-add', 'tab-requests'];
    const pageTabBtns     = document.querySelectorAll('.page-tab-btn');
    const pageTabContents = document.querySelectorAll('.page-tab-content');

    window.addEventListener('pageshow', function (e) {
        if (isEditing && e.persisted) {
            sessionStorage.setItem(STORAGE_KEY, 'tab-requests');
            window.location.replace('{{ route('print-resource.create') }}');
        }
    });

    window.addEventListener('pagehide', function () {
        if (!isEditing) {
            sessionStorage.removeItem(STORAGE_KEY);
        }
    });

    if (isEditing) {
        history.replaceState(null, '', '{{ url('/edit-request') }}');
    }

    // ────────────────────────────────────────────────────────────────────────
    // PAGE-LEVEL TAB MANAGEMENT
    // ────────────────────────────────────────────────────────────────────────
    function activatePageTab(targetId, saveToStorage = true) {
        if (!VALID_TABS.includes(targetId)) targetId = 'tab-search';

        pageTabBtns.forEach(btn => {
            const active = btn.dataset.pageTab === targetId;
            btn.classList.toggle('border-blue-600',   active);
            btn.classList.toggle('text-blue-600',      active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-500',      !active);
        });

        pageTabContents.forEach(c => c.classList.toggle('hidden', c.id !== targetId));

        if (saveToStorage) sessionStorage.setItem(STORAGE_KEY, targetId);
    }

    // ────────────────────────────────────────────────────────────────────────
    // REVEAL & ACTIVATE TAB-ADD
    // ────────────────────────────────────────────────────────────────────────
    function revealAndOpenTabAdd() {
        const tabAddBtn = document.getElementById('tabAddBtn');
        if (tabAddBtn) tabAddBtn.classList.remove('hidden');
        activatePageTab('tab-add', true);
    }

    function resetTabAdd() {
        const tabAddBtn = document.getElementById('tabAddBtn');
        if (tabAddBtn) tabAddBtn.classList.add('hidden');

        sessionStorage.removeItem(STORAGE_KEY);

        const form = document.getElementById('print');
        if (!form) return;

        form.reset();

        const preview = document.getElementById('imagePreview');
        if (preview) preview.src = preview.dataset.defaultSrc;

        const authorWrapper = document.getElementById('author-wrapper');
        const authorInput   = document.getElementById('author-input');
        const authorsHidden = document.getElementById('authors-hidden');
        if (authorWrapper) {
            authorWrapper.querySelectorAll('.author-tag').forEach(tag => tag.remove());
        }
        if (authorsHidden) authorsHidden.value = '';
        if (authorInput)   authorInput.value   = '';

        document.querySelectorAll('input[name="subject_grade_levels[]"]').forEach(cb => {
            cb.checked = false;
        });
    }

    document.querySelectorAll('#triggerTabAdd, .trigger-tab-add').forEach(el => {
        el.addEventListener('click', revealAndOpenTabAdd);
    });

    // ── INITIAL TAB ──────────────────────────────────────────────────────────
    let initialTab;
    if (isEditing) {
        initialTab = 'tab-add';
    } else {
        initialTab = '{{ session('active_tab') }}'
            || sessionStorage.getItem(STORAGE_KEY)
            || 'tab-search';

        if (initialTab === 'tab-add') {
            const tabAddBtn = document.getElementById('tabAddBtn');
            if (tabAddBtn) tabAddBtn.classList.remove('hidden');
        }
    }
    if ('{{ session('just_added_acquisition') }}' === '1') initialTab = 'tab-search';

    activatePageTab(initialTab, false);

    // Normal tab-button clicks
    pageTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.pageTab;

            if (isEditing && targetTab !== 'tab-add') {
                sessionStorage.setItem(STORAGE_KEY, targetTab);
                window.location.href = '{{ route('print-resource.create') }}?tab=' + targetTab;
                return;
            }

            if (!isEditing && targetTab !== 'tab-add') {
                resetTabAdd();
            }

            activatePageTab(targetTab, true);
        });
    });

    // ────────────────────────────────────────────────────────────────────────
    // CANCEL / BACK TO REQUESTS
    // ────────────────────────────────────────────────────────────────────────
    function goBackToRequests() {
        sessionStorage.setItem(STORAGE_KEY, 'tab-requests');
        window.location.href = '{{ route('print-resource.create') }}';
    }

    const backBtn   = document.getElementById('backToRequestsBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    if (backBtn)   backBtn.addEventListener('click',   goBackToRequests);
    if (cancelBtn) cancelBtn.addEventListener('click', goBackToRequests);

    // Division and District accounts use the full-curriculum key-stage tabs.
    const sglTabBtns     = document.querySelectorAll('.sgl-tab-btn');
    const sglTabContents = document.querySelectorAll('.sgl-tab-content');

    function activateSglTab(targetId) {
        sglTabBtns.forEach(btn => {
            const active = btn.dataset.sglTab === targetId;
            btn.classList.toggle('border-blue-600', active);
            btn.classList.toggle('text-blue-600', active);
            btn.classList.toggle('active', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-600', !active);
        });
        sglTabContents.forEach(content => {
            content.classList.toggle('hidden', content.id !== targetId);
        });
    }

    sglTabBtns.forEach(btn => {
        btn.addEventListener('click', () => activateSglTab(btn.dataset.sglTab));
    });
    if (sglTabBtns.length) {
        activateSglTab(sglTabBtns[0].dataset.sglTab);
    }

    // ────────────────────────────────────────────────────────────────────────
    // SEARCH EXISTING
    // ────────────────────────────────────────────────────────────────────────
    const searchInput       = document.getElementById('searchInput');
    const searchBtn         = document.getElementById('searchBtn');
    const spinner           = document.getElementById('searchSpinner');
    const resultsArea       = document.getElementById('resultsArea');
    const resultsList       = document.getElementById('resultsList');
    const resultCount       = document.getElementById('resultCount');
    const emptyState        = document.getElementById('emptyState');
    const initialHint       = document.getElementById('initialHint');
    const detailModal       = document.getElementById('detailModal');
    const modalBackdrop     = document.getElementById('modalBackdrop');
    const closeModalBtn     = document.getElementById('closeModal');
    const modalLoading      = document.getElementById('modalLoading');
    const modalBody         = document.getElementById('modalBody');
    const modalEditionsBody = document.getElementById('modalEditionsBody');

    let searchTimeout = null;

    function performSearch() {
        const q = searchInput.value.trim();
        if (q.length < 2) return;
        showSpinner(true);
        hideAll();

        fetch(`{{ route('search-print-resource.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            showSpinner(false);
            if (!data.length) { emptyState.classList.remove('hidden'); return; }
            renderResults(data);
        })
        .catch(() => { showSpinner(false); emptyState.classList.remove('hidden'); });
    }

    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') performSearch(); });
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        if (searchInput.value.trim().length >= 2) searchTimeout = setTimeout(performSearch, 450);
    });

    // ── ESCAPE + HIGHLIGHT HELPERS ───────────────────────────────────────────
    // esc() escapes the string first (XSS-safe).
    // highlight() then wraps each matched term from the query in a <mark> tag.
    function esc(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str ?? '')));
        return d.innerHTML;
    }

    function highlight(str, query) {
        const result = esc(str); // escape first — never run regex on raw user input
        if (!query) return result;

        const terms = query
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .map(term => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')); // escape regex special chars

        if (!terms.length) return result;

        const regex = new RegExp(`(${terms.join('|')})`, 'gi');
        return result.replace(regex, '<mark class="bg-yellow-200 text-gray-900 rounded px-0.5">$1</mark>');
    }
    // ─────────────────────────────────────────────────────────────────────────

    function renderResults(titles) {
        resultsList.innerHTML = '';
        resultCount.textContent = `${titles.length} title(s) found`;
        resultsArea.classList.remove('hidden');

        // Raw query string passed straight into highlight()
        const query = searchInput.value.trim();

        titles.forEach(title => {
            const editionBadges = title.editions.map(e => {
                let label = esc(e.type);
                if (e.edition   && e.edition   !== '-') label += ` - Ed. ${esc(e.edition)}`;
                if (e.copyright && e.copyright !== '-') label += ` (${esc(e.copyright)})`;
                return `<span class="inline-flex items-center text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${label}</span>`;
            }).join(' ');

            const card = document.createElement('div');
            card.className = 'border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white';
            card.innerHTML = `
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                    <img src="${esc(title.cover)}" alt="cover" class="cover-img w-16 h-22 object-cover rounded shadow-sm flex-shrink-0 border border-gray-200 sm:w-12 sm:h-16">
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <p class="font-semibold text-gray-900 break-words">${highlight(title.title, query)}</p>
                        <p class="text-xs text-gray-500">${highlight(title.authors, query)}</p>
                        <p class="text-xs text-gray-600 leading-relaxed break-words">
                            <span class="font-medium">Subjects:</span> ${highlight(title.subjects, query)}
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            ${editionBadges || '<span class="text-xs text-gray-400">No editions</span>'}
                        </div>
                    </div>
                    <div class="w-full flex-shrink-0 self-stretch sm:w-auto sm:self-center">
                        <button data-title-id="${esc(title.id)}"
                                data-uniqueness-hash="${esc(title.uniqueness_hash)}"
                                class="view-btn w-full text-xs px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap font-medium sm:w-auto">
                            View Details
                        </button>
                    </div>
                </div>`;

            card.querySelector('.view-btn').addEventListener('click', function () {
                openModal(this.dataset.titleId, this.dataset.uniquenessHash);
            });
            resultsList.appendChild(card);
        });
    }

    function openModal(titleId, uniquenessHash) {
        detailModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        modalBody.classList.add('hidden');
        modalLoading.classList.remove('hidden');

        fetch(`{{ url('search-print') }}/${titleId}/details?hash=${encodeURIComponent(uniquenessHash)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            populateModal(data);
            modalLoading.classList.add('hidden');
            modalBody.classList.remove('hidden');
        })
        .catch(() => {
            modalLoading.innerHTML = '<p class="text-red-500 text-sm px-8">Failed to load details. Please try again.</p>';
        });
    }

    function populateModal(d) {
        document.getElementById('modalTitle').textContent     = 'Resource Details';
        document.getElementById('modalCover').src             = d.cover;
        document.getElementById('modalBookTitle').textContent = d.title;
        document.getElementById('modalAuthors').textContent   = d.authors;
        document.getElementById('modalSubjects').textContent  = d.subjects;

        modalEditionsBody.innerHTML = '';
        if (!d.editions || !d.editions.length) {
            modalEditionsBody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-400 py-6 text-xs">No editions found</td></tr>';
            return;
        }
        d.editions.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors';
            tr.innerHTML = `
                <td class="px-3 py-2"><img src="${esc(e.cover)}" alt="cover" class="cover-img w-9 h-12 object-cover rounded border border-gray-200 shadow-sm"></td>
                <td class="px-3 py-2 text-gray-700">${esc(e.type)}</td>
                <td class="px-3 py-2 text-gray-700 break-words">${esc(e.publisher)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.edition)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.volume)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.copyright)}</td>
                <td class="px-3 py-2 text-gray-700 break-all">${esc(e.isbn)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.pages)}</td>
                <td class="px-3 py-2 text-center">
                    <a href="${esc(e.add_url)}"
                       class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap font-medium">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add
                    </a>
                </td>`;
            modalEditionsBody.appendChild(tr);
        });
    }

    function closeModalFn() { detailModal.classList.add('hidden'); document.body.style.overflow = ''; }
    closeModalBtn.addEventListener('click', closeModalFn);
    modalBackdrop.addEventListener('click', closeModalFn);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModalFn(); });

    function showSpinner(show) { spinner.classList.toggle('hidden', !show); }
    function hideAll() {
        resultsArea.classList.add('hidden');
        emptyState.classList.add('hidden');
        initialHint.classList.add('hidden');
    }

    // ────────────────────────────────────────────────────────────────────────
    // MY REQUESTS VIEW MODAL
    // ────────────────────────────────────────────────────────────────────────
    const reqViewModal     = document.getElementById('reqViewModal');
    const reqModalBackdrop = document.getElementById('reqModalBackdrop');
    const closeReqBtn      = document.getElementById('closeReqModal');
    const closeReqFooter   = document.getElementById('closeReqModalFooter');

    function openReqModal(btn) {
        const status = parseInt(btn.dataset.status);

        document.getElementById('rvm-cover').src              = btn.dataset.cover;
        document.getElementById('rvm-title').textContent      = btn.dataset.title;
        document.getElementById('rvm-authors').textContent    = btn.dataset.authors !== '-' ? btn.dataset.authors : '';
        document.getElementById('rvm-type-badge').textContent = btn.dataset.type;
        document.getElementById('rvm-publisher').textContent  = btn.dataset.publisher;
        document.getElementById('rvm-copyright').textContent  = btn.dataset.copyright;
        document.getElementById('rvm-edition').textContent    = btn.dataset.edition;
        document.getElementById('rvm-volume').textContent     = btn.dataset.volume;
        document.getElementById('rvm-isbn').textContent       = btn.dataset.isbn;
        document.getElementById('rvm-pages').textContent      = btn.dataset.pages;
        document.getElementById('rvm-subjects').textContent   = btn.dataset.subjects;
        document.getElementById('rvm-submitted').textContent  = btn.dataset.submitted;

        const statusBadge = document.getElementById('rvm-status-badge');
        if (status === 0) {
            statusBadge.textContent = 'Pending';
            statusBadge.className   = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
        } else if (status === 1) {
            statusBadge.textContent = '✓ Approved';
            statusBadge.className   = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
        } else {
            statusBadge.textContent = 'Rejected';
            statusBadge.className   = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800';
        }

        reqViewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeReqModal() { reqViewModal.classList.add('hidden'); document.body.style.overflow = ''; }

    document.querySelectorAll('.req-view-btn').forEach(btn => btn.addEventListener('click', () => openReqModal(btn)));
    closeReqBtn      && closeReqBtn.addEventListener('click',      closeReqModal);
    closeReqFooter   && closeReqFooter.addEventListener('click',   closeReqModal);
    reqModalBackdrop && reqModalBackdrop.addEventListener('click', closeReqModal);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !reqViewModal.classList.contains('hidden')) closeReqModal();
    });

})();
</script>

@vite(['resources/js/add-print-resource.js'])

@endsection
