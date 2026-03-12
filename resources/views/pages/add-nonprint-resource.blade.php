@extends('pages.layout.layout')

@section('title', 'Add Non-Print Resource')
@section('page-title', 'Add Non-Print Resource')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Add Non-Print Resource')

@section('content')
<div class="p-6 space-y-6">
    @include('pages.partials.page-header')

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
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6" id="pageTabs">
            <button type="button" data-page-tab="tab-search"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                Search Masterlist
            </button>

            @if ($isDivision)
                <button type="button" data-page-tab="tab-add" id="tabAddBtn"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 {{ $isEditing ? '' : 'hidden' }}">
                    {{ $isEditing ? 'Edit Resource' : 'Add to Masterlist' }}
                </button>
            @else
                <button type="button" data-page-tab="tab-add" id="tabAddBtn"
                        class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 {{ $isEditing ? '' : 'hidden' }}">
                    {{ $isEditing ? 'Edit Request' : 'Request Add to Masterlist' }}
                </button>
            @endif

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
                <h2 class="text-base font-semibold text-gray-800">Search Existing Non-Print Resources</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Search the masterlist by title or author, then add your acquisition records to an existing entry.
                </p>
            </div>
            <div>
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <input type="text" id="searchInput" placeholder="Type a title..."
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            autocomplete="off">
                        <span id="searchSpinner" class="absolute right-3 top-3.5 hidden">
                            <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                        </span>
                    </div>
                    <button id="searchBtn" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition-colors">Search</button>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium">No titles found</p>
                <p class="text-sm mt-1">Try a different keyword or <button type="button" class="trigger-tab-add text-blue-600 underline">submit a new request</button>.</p>
            </div>
            <div id="initialHint" class="text-center py-16 text-gray-400">
                <svg class="mx-auto mb-4 h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                </svg>
                <p class="font-medium">Start by searching for a non-print resource title</p>
            </div>
        </div>
    </div>

    {{-- =================================================================
        TAB 2 - MANUAL ADD / EDIT REQUEST
    ================================================================== --}}
    <div id="tab-add" class="page-tab-content hidden">

        <div class="mb-5 flex items-start justify-between">
            <div>
                @if($isEditing)
                    <h2 class="text-base font-semibold text-gray-800">Edit Resource Request</h2>
                    <p class="text-sm text-gray-500 mt-1">Update the details of your pending submission.</p>
                @elseif($isDivision)
                    <h2 class="text-base font-semibold text-gray-800">Add New Non-Print Resource to Masterlist</h2>
                    <p class="text-sm text-gray-500 mt-1">As a division account, resources you add are automatically approved and added to the masterlist.</p>
                @else
                    <h2 class="text-base font-semibold text-gray-800">Submit a New Non-Print Resource Request</h2>
                    <p class="text-sm text-gray-500 mt-1">Fill in the details below. Your submission will be reviewed before it appears in the masterlist.</p>
                @endif
            </div>
            @if($isEditing)
                <button type="button" id="backToRequestsBtn"
                        class="text-sm text-blue-600 hover:underline flex items-center gap-1 mt-1">
                    &larr; Back to My Requests
                </button>
            @endif
        </div>

        {{-- THE FORM --}}
        @if($isEditing)
            <form id="nonprintForm"
                action="{{ route('nonprint-resource.update', $editResource->id) }}"
                class="resource-form space-y-8"
                method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
        @else
            <form id="nonprintForm"
                action="{{ route('nonprint-resource.store') }}"
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
                        @if($isEditing && isset($editResource->cover) && $editResource->cover)
                            <img id="nonprintImagePreview"
                                src="{{ asset('storage/' . $editResource->cover) }}"
                                data-default-src="{{ asset('storage/' . $editResource->cover) }}"
                                alt="Image preview"
                                class="w-full flex-1 object-cover rounded mb-4">
                        @else
                            <img id="nonprintImagePreview"
                                src="{{ asset('assets/images/def.jpg') }}"
                                data-default-src="{{ asset('assets/images/def.jpg') }}"
                                alt="Image preview"
                                class="w-full flex-1 object-cover rounded mb-4">
                        @endif
                        <input type="file" name="image" id="nonprintImageUpload" class="hidden" accept="image/*">
                        <label for="nonprintImageUpload" class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            {{ $isEditing ? 'Change Image' : 'Choose Image' }}
                        </label>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG &bull; Max 5MB{{ $isEditing ? '. Leave blank to keep current.' : '' }}</p>
                    </div>
                </div>

                {{-- Inputs --}}
                <div class="md:col-span-2 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Title / Name <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required
                                value="{{ old('title', $isEditing ? ($editResource->nonprintTitle->title ?? '') : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Type <span class="text-red-500">*</span></label>
                            <select name="type" required class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="" disabled {{ !$isEditing && !old('type') ? 'selected' : '' }}>Select type</option>
                                @foreach ($nonprintTypes as $type)
                                    <option value="{{ $type->id }}"
                                        {{ old('type', $isEditing ? $editResource->nonprint_type_id : '') == $type->id ? 'selected' : '' }}>
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Brand</label>
                            <input type="text" name="brand"
                                value="{{ old('brand', $isEditing ? $editResource->brand : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Code</label>
                            <input type="text" name="code"
                                value="{{ old('code', $isEditing ? $editResource->code : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Version</label>
                            <input type="text" name="version"
                                value="{{ old('version', $isEditing ? $editResource->version : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Model</label>
                            <input type="text" name="model"
                                value="{{ old('model', $isEditing ? $editResource->model : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">URL <span class="text-xs text-gray-500">(if applicable)</span></label>
                            <input type="text" name="url" placeholder="https://example.com"
                                value="{{ old('url', $isEditing ? $editResource->url : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Size <span class="text-xs text-gray-500">(e.g., dimensions or file size)</span></label>
                            <input type="text" name="size" placeholder="e.g., 50x30 cm or 2.4 GB"
                                value="{{ old('size', $isEditing ? $editResource->size : '') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBJECT-GRADE LEVEL --}}
            @php
                $stages = [
                    'S1'  => ['tab' => 'np-stage1', 'label' => 'Key Stage 1', 'grades' => [0=>'K',1=>'1',2=>'2',3=>'3']],
                    'ES'  => ['tab' => 'np-stage2', 'label' => 'Key Stage 2', 'grades' => [4=>'4',5=>'5',6=>'6']],
                    'JHS' => ['tab' => 'np-jhs',    'label' => 'Junior High',  'grades' => [7=>'7',8=>'8',9=>'9',10=>'10']],
                    'SHS' => ['tab' => 'np-shs',    'label' => 'Senior High',  'grades' => [11=>'11',12=>'12']],
                ];
                $grouped    = $subjectGradeLevels->groupBy(['key_stage', 'subject_name']);
                $checkedIds = old('subject_grade_levels', $isEditing ? ($editingSglIds ?? []) : []);
            @endphp

            <div class="space-y-4">
                <div class="flex gap-6 border-b border-gray-300">
                    @foreach ($stages as $stage)
                        <button type="button"
                                class="np-sgl-tab-btn {{ $loop->first ? 'active border-blue-600 text-blue-600' : 'border-transparent text-gray-600' }} whitespace-nowrap pb-2 px-1 text-sm font-medium border-b-2"
                                data-np-sgl-tab="{{ $stage['tab'] }}">
                            {{ $stage['label'] }}
                        </button>
                    @endforeach
                </div>

                @foreach ($stages as $stageKey => $stage)
                    <div id="{{ $stage['tab'] }}" class="np-sgl-tab-content {{ !$loop->first ? 'hidden' : '' }}">
                        <table class="w-full border border-gray-300 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 px-3 py-2 text-left w-72">Subject</th>
                                    @foreach ($stage['grades'] as $gradeLabel)
                                        <th class="border border-gray-300 px-2 py-2">{{ $gradeLabel }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grouped[$stageKey] ?? [] as $subject => $rows)
                                    @php $gradeMap = collect($rows)->keyBy('sort_order'); @endphp
                                    <tr>
                                        <td class="border border-gray-300 px-3 py-2">{{ $subject }}</td>
                                        @foreach ($stage['grades'] as $sortOrder => $label)
                                            <td class="border border-gray-300 text-center">
                                                @if ($gradeMap->has($sortOrder))
                                                    <input type="checkbox"
                                                        name="subject_grade_levels[]"
                                                        value="{{ $gradeMap[$sortOrder]->subject_grade_level_id }}"
                                                        {{ in_array($gradeMap[$sortOrder]->subject_grade_level_id, $checkedIds) ? 'checked' : '' }}>
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

            {{-- SUBMIT --}}
            <div class="flex justify-end gap-3">
                @if($isEditing)
                    <button type="button" id="cancelEditBtn"
                            class="px-6 py-2 bg-gray-100 text-gray-700 border border-gray-300 rounded hover:bg-gray-200">
                        Cancel
                    </button>
                @endif
                <button type="submit" id="saveNonPrintBtn"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="saveNonPrintText">{{ $isEditing ? 'Update Request' : 'Save Non-Print Resource' }}</span>
                    <span id="saveNonPrintLoading" class="hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Saving...
                    </span>
                </button>
            </div>

        </form>
    </div>

    {{-- =================================================================
        TAB 3 - MY REQUESTS (school users only)
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
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Brand</th>
                            <th class="px-3 py-3 text-left">Model</th>
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
                                        alt="cover" class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-40">
                                    <span title="{{ $resource->nonprintTitle->title ?? '' }}">{{ Str::limit($resource->nonprintTitle->title ?? '-', 38) }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $resource->type->type_name ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $resource->brand ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $resource->model ?? '-' }}</td>

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

                                {{-- ACTIONS --}}
                                <td class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1.5">

                                        {{-- View Details --}}
                                        <div class="relative group">
                                            <button type="button"
                                                    class="np-req-view-btn inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200 hover:bg-indigo-100 transition-colors"
                                                    data-id="{{ $resource->id }}"
                                                    data-status="{{ $resource->status }}"
                                                    data-cover="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                                                    data-title="{{ $resource->nonprintTitle->title ?? '-' }}"
                                                    data-type="{{ $resource->type->type_name ?? '-' }}"
                                                    data-brand="{{ $resource->brand ?? '-' }}"
                                                    data-code="{{ $resource->code ?? '-' }}"
                                                    data-version="{{ $resource->version ?? '-' }}"
                                                    data-model="{{ $resource->model ?? '-' }}"
                                                    data-url="{{ $resource->url ?? '-' }}"
                                                    data-size="{{ $resource->size ?? '-' }}"
                                                    data-subjects="{{ $sglText }}"
                                                    data-submitted="{{ $resource->created_at?->format('M d, Y') ?? '-' }}"
                                                    data-edit-url="{{ route('nonprint-resource.edit', $resource->id) }}"
                                                    data-delete-url="{{ route('nonprint-resource.destroy', $resource->id) }}">
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
                                            {{-- Edit --}}
                                            <div class="relative group">
                                                <a href="{{ route('nonprint-resource.edit', $resource->id) }}"
                                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-100 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                                    </svg>
                                                </a>
                                                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                    Edit Request
                                                </span>
                                            </div>

                                            {{-- Delete --}}
                                            <div class="relative group">
                                                <form method="POST" action="{{ route('nonprint-resource.destroy', $resource->id) }}"
                                                      onsubmit="return confirm('Delete this request? This cannot be undone.')">
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
                                <td colspan="9" class="text-center text-gray-400 py-10">
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
                <div class="mt-4">{{ $myRequests->appends(['active_tab' => 'tab-requests'])->links() }}</div>
            @endif
        </div>
    </div>
    @endif

    {{-- ===== SEARCH DETAIL MODAL ===== --}}
    <div id="npDetailModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="npModalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl z-10 mb-10">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800" id="npModalTitle">Resource Details</h3>
                    <button id="npCloseModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-1 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="npModalLoading" class="py-16 text-center text-gray-400">
                    <svg class="animate-spin mx-auto h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <p class="mt-2 text-sm">Loading...</p>
                </div>
                <div id="npModalBody" class="hidden p-6 space-y-4">
                    <div class="flex gap-4">
                        <img id="npModalCover" src="" alt="Cover" class="w-24 h-32 object-cover rounded border border-gray-200 shadow-sm flex-shrink-0">
                        <div class="flex-1 space-y-1">
                            <h4 id="npModalBookTitle" class="text-lg font-bold text-gray-900 leading-snug"></h4>
                            <p class="text-xs text-gray-500 font-medium">Subjects / Grade Levels</p>
                            <p id="npModalSubjects" class="text-sm text-gray-700 leading-relaxed"></p>
                        </div>
                    </div>
                    <div>
                        <h5 class="text-sm font-semibold text-gray-600 mb-2">Available Editions / Variants</h5>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border border-gray-200 rounded">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Cover</th>
                                        <th class="px-3 py-2 text-left">Type</th>
                                        <th class="px-3 py-2 text-left">Brand</th>
                                        <th class="px-3 py-2 text-left">Code</th>
                                        <th class="px-3 py-2 text-left">Version</th>
                                        <th class="px-3 py-2 text-left">Model</th>
                                        <th class="px-3 py-2 text-left">URL</th>
                                        <th class="px-3 py-2 text-left">Size</th>
                                        <th class="px-3 py-2 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="npModalEditionsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MY REQUESTS VIEW MODAL ===== --}}
    <div id="npReqViewModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="npReqModalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl z-10 mb-10">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Request Details</h3>
                    <button id="npCloseReqModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-1 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div class="flex gap-4 items-start">
                        <img id="nprvm-cover" src="" alt="Cover" class="w-20 h-28 object-cover rounded border border-gray-200 shadow-sm flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h4 id="nprvm-title" class="text-lg font-bold text-gray-900 leading-snug"></h4>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span id="nprvm-type-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"></span>
                                <span id="nprvm-status-badge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                            </div>
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">Brand</dt><dd id="nprvm-brand" class="text-gray-700 mt-0.5"></dd></div>
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">Code</dt><dd id="nprvm-code" class="text-gray-700 mt-0.5"></dd></div>
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">Version</dt><dd id="nprvm-version" class="text-gray-700 mt-0.5"></dd></div>
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">Model</dt><dd id="nprvm-model" class="text-gray-700 mt-0.5"></dd></div>
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">URL</dt><dd id="nprvm-url" class="text-gray-700 mt-0.5 text-xs break-all"></dd></div>
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">Size</dt><dd id="nprvm-size" class="text-gray-700 mt-0.5"></dd></div>
                        <div><dt class="text-xs font-medium text-gray-400 uppercase">Submitted</dt><dd id="nprvm-submitted" class="text-gray-700 mt-0.5"></dd></div>
                    </dl>
                    <div>
                        <dt class="text-xs font-medium text-gray-400 uppercase mb-1">Subjects / Grade Levels</dt>
                        <p id="nprvm-subjects" class="text-sm text-gray-700 leading-relaxed"></p>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                    <button id="npCloseReqModalFooter" class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const STORAGE_KEY      = 'nonprint_active_tab';
    const isDivision       = {{ $isDivision ? 'true' : 'false' }};
    const isEditing        = {{ $isEditing  ? 'true' : 'false' }};
    const VALID_TABS       = isDivision ? ['tab-search', 'tab-add'] : ['tab-search', 'tab-add', 'tab-requests'];
    const pageTabBtns      = document.querySelectorAll('.page-tab-btn');
    const pageTabContents  = document.querySelectorAll('.page-tab-content');

    window.addEventListener('pageshow', function (e) {
        if (isEditing && e.persisted) {
            sessionStorage.setItem(STORAGE_KEY, 'tab-requests');
            window.location.replace('{{ route('nonprint-resource.create') }}');
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

    // ── PAGE-LEVEL TABS ──────────────────────────────────────────────────────
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

    // ── REVEAL & ACTIVATE TAB-ADD ────────────────────────────────────────────
    // The tab button is hidden on fresh load (non-edit mode).
    // It becomes visible only after the user explicitly clicks
    // "Manual Add" or "submit a new request" in the search tab,
    // enforcing the search-first workflow.
    function revealAndOpenTabAdd() {
        const tabAddBtn = document.getElementById('tabAddBtn');
        if (tabAddBtn) tabAddBtn.classList.remove('hidden');
        activatePageTab('tab-add', true);
    }

    document.querySelectorAll('#triggerTabAdd, .trigger-tab-add').forEach(el => {
        el.addEventListener('click', revealAndOpenTabAdd);
    });

    // ── RESET TAB-ADD ────────────────────────────────────────────────────────
    // Called when the user navigates away from tab-add (non-edit mode).
    // Re-hides the tab button, clears sessionStorage, and resets the form.
    function resetTabAdd() {
        const tabAddBtn = document.getElementById('tabAddBtn');
        if (tabAddBtn) tabAddBtn.classList.add('hidden');

        sessionStorage.removeItem(STORAGE_KEY);

        const form = document.getElementById('nonprintForm');
        if (!form) return;

        form.reset();

        // Reset image preview back to default
        const preview = document.getElementById('nonprintImagePreview');
        if (preview) preview.src = preview.dataset.defaultSrc;

        // Reset SGL checkboxes
        document.querySelectorAll('input[name="subject_grade_levels[]"]').forEach(cb => {
            cb.checked = false;
        });
    }

    // ── INITIAL TAB ──────────────────────────────────────────────────────────
    let initialTab;
    if (isEditing) {
        initialTab = 'tab-add';
    } else {
        initialTab = '{{ session('active_tab') }}'
            || sessionStorage.getItem(STORAGE_KEY)
            || 'tab-search';

        // If sessionStorage remembers tab-add from a previous visit,
        // make sure the tab button is visible again so the UI is consistent.
        if (initialTab === 'tab-add') {
            const tabAddBtn = document.getElementById('tabAddBtn');
            if (tabAddBtn) tabAddBtn.classList.remove('hidden');
        }
    }
    activatePageTab(initialTab, false);

    // ── TAB BUTTON CLICKS ────────────────────────────────────────────────────
    pageTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.pageTab;

            // If in edit mode and navigating away from tab-add, reset to create mode
            if (isEditing && targetTab !== 'tab-add') {
                sessionStorage.setItem(STORAGE_KEY, targetTab);
                window.location.href = '{{ route('nonprint-resource.create') }}?tab=' + targetTab;
                return;
            }

            // If navigating away from tab-add (non-edit mode), reset form and re-hide tab
            if (!isEditing && targetTab !== 'tab-add') {
                resetTabAdd();
            }

            activatePageTab(targetTab, true);
        });
    });

    // ── BACK / CANCEL ────────────────────────────────────────────────────────
    function goBackToRequests() {
        sessionStorage.setItem(STORAGE_KEY, 'tab-requests');
        window.location.href = '{{ route('nonprint-resource.create') }}';
    }
    const backBtn   = document.getElementById('backToRequestsBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    if (backBtn)   backBtn.addEventListener('click',   goBackToRequests);
    if (cancelBtn) cancelBtn.addEventListener('click', goBackToRequests);

    // ── SGL INNER TABS ───────────────────────────────────────────────────────
    const sglTabBtns     = document.querySelectorAll('.np-sgl-tab-btn');
    const sglTabContents = document.querySelectorAll('.np-sgl-tab-content');

    function activateSglTab(targetId) {
        sglTabBtns.forEach(btn => {
            const active = btn.dataset.npSglTab === targetId;
            btn.classList.toggle('border-blue-600',   active);
            btn.classList.toggle('text-blue-600',      active);
            btn.classList.toggle('active',             active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-600',      !active);
        });
        sglTabContents.forEach(c => c.classList.toggle('hidden', c.id !== targetId));
    }
    sglTabBtns.forEach(btn => btn.addEventListener('click', () => activateSglTab(btn.dataset.npSglTab)));
    if (sglTabBtns.length) activateSglTab(sglTabBtns[0].dataset.npSglTab);

    // ── SEARCH ───────────────────────────────────────────────────────────────
    const searchInput  = document.getElementById('searchInput');
    const searchBtn    = document.getElementById('searchBtn');
    const spinner      = document.getElementById('searchSpinner');
    const resultsArea  = document.getElementById('resultsArea');
    const resultsList  = document.getElementById('resultsList');
    const resultCount  = document.getElementById('resultCount');
    const emptyState   = document.getElementById('emptyState');
    const initialHint  = document.getElementById('initialHint');
    const npModal      = document.getElementById('npDetailModal');
    const npBackdrop   = document.getElementById('npModalBackdrop');
    const npCloseBtn   = document.getElementById('npCloseModal');
    const npModalLoad  = document.getElementById('npModalLoading');
    const npModalBody  = document.getElementById('npModalBody');
    const npEdBody     = document.getElementById('npModalEditionsBody');

    let searchTimeout = null;

    function performSearch() {
        const q = searchInput.value.trim();
        if (q.length < 2) return;
        showSpinner(true);
        hideAll();

        fetch(`{{ route('search-nonprint-resource.search') }}?q=${encodeURIComponent(q)}`, {
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

    searchBtn && searchBtn.addEventListener('click', performSearch);
    searchInput && searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') performSearch(); });
    searchInput && searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        if (searchInput.value.trim().length >= 2) searchTimeout = setTimeout(performSearch, 450);
    });

    function renderResults(titles) {
        resultsList.innerHTML = '';
        resultCount.textContent = `${titles.length} title(s) found`;
        resultsArea.classList.remove('hidden');

        titles.forEach(title => {
            const editionBadges = title.editions.map(e => {
                let label = esc(e.type);
                if (e.brand && e.brand !== '-') label += ` · ${esc(e.brand)}`;
                return `<span class="inline-flex items-center text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${label}</span>`;
            }).join(' ');

            const card = document.createElement('div');
            card.className = 'border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white';
            card.innerHTML = `
                <div class="flex items-start gap-4">
                    <img src="${esc(title.cover)}" alt="cover" class="w-12 h-16 object-cover rounded shadow-sm flex-shrink-0 border border-gray-200">
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <p class="font-semibold text-gray-900">${esc(title.title)}</p>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            <span class="font-medium">Subjects:</span> ${esc(title.subjects)}
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            ${editionBadges || '<span class="text-xs text-gray-400">No variants</span>'}
                        </div>
                    </div>
                    <div class="flex-shrink-0 self-center">
                        <button data-title-id="${esc(title.id)}"
                                data-uniqueness-hash="${esc(title.uniqueness_hash)}"
                                class="np-view-btn text-xs px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap font-medium">
                            View Details
                        </button>
                    </div>
                </div>`;

            card.querySelector('.np-view-btn').addEventListener('click', function () {
                openDetailModal(this.dataset.titleId, this.dataset.uniquenessHash);
            });
            resultsList.appendChild(card);
        });
    }

    function openDetailModal(titleId, uniquenessHash) {
        npModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        npModalBody.classList.add('hidden');
        npModalLoad.classList.remove('hidden');

        fetch(`{{ url('search-nonprint') }}/${titleId}/details?hash=${encodeURIComponent(uniquenessHash)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            populateModal(data);
            npModalLoad.classList.add('hidden');
            npModalBody.classList.remove('hidden');
        })
        .catch(() => {
            npModalLoad.innerHTML = '<p class="text-red-500 text-sm px-8">Failed to load details. Please try again.</p>';
        });
    }

    function populateModal(d) {
        document.getElementById('npModalCover').src             = d.cover;
        document.getElementById('npModalBookTitle').textContent = d.title;
        document.getElementById('npModalSubjects').textContent  = d.subjects;

        npEdBody.innerHTML = '';
        if (!d.editions || !d.editions.length) {
            npEdBody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-400 py-6 text-xs">No variants found</td></tr>';
            return;
        }
        d.editions.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors';
            tr.innerHTML = `
                <td class="px-3 py-2"><img src="${esc(e.cover)}" alt="cover" class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm"></td>
                <td class="px-3 py-2 text-gray-700">${esc(e.type)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.brand)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.code)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.version)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.model)}</td>
                <td class="px-3 py-2 text-gray-700 text-xs break-all max-w-xs">${esc(e.url)}</td>
                <td class="px-3 py-2 text-gray-700">${esc(e.size)}</td>
                <td class="px-3 py-2 text-center">
                    <a href="${esc(e.add_url)}"
                       class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap font-medium">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add
                    </a>
                </td>`;
            npEdBody.appendChild(tr);
        });
    }

    function closeDetailModal() { npModal.classList.add('hidden'); document.body.style.overflow = ''; }
    npCloseBtn  && npCloseBtn.addEventListener('click',   closeDetailModal);
    npBackdrop  && npBackdrop.addEventListener('click',   closeDetailModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !npModal.classList.contains('hidden')) closeDetailModal(); });

    function showSpinner(show) { spinner && spinner.classList.toggle('hidden', !show); }
    function hideAll() {
        resultsArea  && resultsArea.classList.add('hidden');
        emptyState   && emptyState.classList.add('hidden');
        initialHint  && initialHint.classList.add('hidden');
    }

    // ── MY REQUESTS VIEW MODAL ───────────────────────────────────────────────
    const reqViewModal   = document.getElementById('npReqViewModal');
    const reqBackdrop    = document.getElementById('npReqModalBackdrop');
    const closeReqBtn    = document.getElementById('npCloseReqModal');
    const closeReqFooter = document.getElementById('npCloseReqModalFooter');

    function openReqModal(btn) {
        const status = parseInt(btn.dataset.status);

        document.getElementById('nprvm-cover').src             = btn.dataset.cover;
        document.getElementById('nprvm-title').textContent     = btn.dataset.title;
        document.getElementById('nprvm-type-badge').textContent= btn.dataset.type;
        document.getElementById('nprvm-brand').textContent     = btn.dataset.brand;
        document.getElementById('nprvm-code').textContent      = btn.dataset.code;
        document.getElementById('nprvm-version').textContent   = btn.dataset.version;
        document.getElementById('nprvm-model').textContent     = btn.dataset.model;
        document.getElementById('nprvm-url').textContent       = btn.dataset.url;
        document.getElementById('nprvm-size').textContent      = btn.dataset.size;
        document.getElementById('nprvm-subjects').textContent  = btn.dataset.subjects;
        document.getElementById('nprvm-submitted').textContent = btn.dataset.submitted;

        const statusBadge = document.getElementById('nprvm-status-badge');
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

    document.querySelectorAll('.np-req-view-btn').forEach(btn => btn.addEventListener('click', () => openReqModal(btn)));
    closeReqBtn    && closeReqBtn.addEventListener('click',    closeReqModal);
    closeReqFooter && closeReqFooter.addEventListener('click', closeReqModal);
    reqBackdrop    && reqBackdrop.addEventListener('click',    closeReqModal);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !reqViewModal.classList.contains('hidden')) closeReqModal();
    });

    // ── UTILITY ──────────────────────────────────────────────────────────────
    function esc(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str ?? '')));
        return d.innerHTML;
    }

})();
</script>

@vite(['resources/js/add-nonprint-resource.js'])

@endsection