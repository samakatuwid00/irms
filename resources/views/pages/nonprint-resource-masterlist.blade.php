@extends('pages.layout.layout')

@section('title', 'Non-Print Resource Masterlist')
@section('page-title', 'Non-Print Resource Masterlist')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Masterlist')

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
        $isEditing    = isset($resource);
        $pendingCount = ($level === 3 && $requests) ? $requests->total() : 0;
    @endphp

    {{-- ===== PAGE-LEVEL TABS ===== --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6" id="pageTabs">
            <button type="button" data-page-tab="tab-masterlist"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                Masterlist
            </button>

            @if($isEditing)
            <button type="button" data-page-tab="tab-edit"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                Edit Resource
            </button>
            @endif

            @if($level === 3)
            <button type="button" data-page-tab="tab-requests"
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                School Requests
                @if($pendingCount > 0)
                    <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">{{ $pendingCount }}</span>
                @endif
            </button>
            @endif
        </nav>
    </div>

    {{-- =================================================================
        TAB 1 — MASTERLIST
    ================================================================== --}}
    <div id="tab-masterlist" class="page-tab-content hidden">
        <div class="space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-700">Approved Non-Print Resources</h3>
                    <p class="text-xs text-gray-400 mt-0.5">All non-print resources with approved status across the masterlist.</p>
                </div>
                <div class="flex gap-2">
                    <form method="GET" action="{{ route('nonprint-masterlist.index') }}" class="flex gap-2">
                        <input type="hidden" name="active_tab" value="tab-masterlist">
                        <input type="text" name="ml_search" value="{{ request('ml_search') }}"
                               placeholder="Search title, type, brand..."
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Search
                        </button>
                        @if(request('ml_search'))
                            <a href="{{ route('nonprint-masterlist.index') }}"
                               class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
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
                            <th class="px-3 py-3 text-left">Version</th>
                            <th class="px-3 py-3 text-left">Subjects / Grade Levels</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($masterlist as $item)
                            @php
                                $sglIds  = $item->subject_grade_level_ids ? explode(',', $item->subject_grade_level_ids) : [];
                                $sgls    = !empty($sglIds)
                                    ? \App\Models\SubjectGradeLevel::with(['subject','gradeLevel'])->whereIn('id', $sglIds)->get()
                                    : collect();
                                $sglText = $sgls->map(fn($s) => ($s->subject->subject_name ?? '') . ' - Gr.' . ($s->gradeLevel->grade ?? ''))->join('; ') ?: '-';
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2">
                                    <img src="{{ $item->cover ? asset('storage/' . $item->cover) : asset('assets/images/def.jpg') }}"
                                         alt="cover" class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-[180px]">
                                    <span title="{{ $item->nonprintTitle->title ?? '' }}">{{ Str::limit($item->nonprintTitle->title ?? '-', 40) }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $item->type->type_name ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $item->brand ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $item->model ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $item->version ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600 max-w-[220px] text-xs">
                                    <span title="{{ $sglText }}">{{ Str::limit($sglText, 60) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- View --}}
                                        <button type="button"
                                                class="view-resource-btn inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap font-medium"
                                                data-view-id="{{ $item->id }}"
                                                data-cover="{{ $item->cover ? asset('storage/' . $item->cover) : asset('assets/images/def.jpg') }}"
                                                data-title="{{ $item->nonprintTitle->title ?? '-' }}"
                                                data-type="{{ $item->type->type_name ?? '-' }}"
                                                data-brand="{{ $item->brand ?? '-' }}"
                                                data-code="{{ $item->code ?? '-' }}"
                                                data-version="{{ $item->version ?? '-' }}"
                                                data-model="{{ $item->model ?? '-' }}"
                                                data-url="{{ $item->url ?? '-' }}"
                                                data-size="{{ $item->size ?? '-' }}"
                                                data-subjects="{{ $sglText }}">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </button>
                                        {{-- Edit --}}
                                        <a href="{{ route('nonprint-masterlist.edit', $item->id) }}"
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors whitespace-nowrap font-medium">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                            </svg>
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-400 py-10">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm font-medium">No approved non-print resources found.</p>
                                    @if(request('ml_search'))
                                        <p class="text-xs mt-1">Try a different search term.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($masterlist->hasPages())
                <div class="mt-4">
                    {{ $masterlist->appends(array_filter(['ml_search' => request('ml_search'), 'active_tab' => 'tab-masterlist']))->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- =================================================================
        TAB 2 — EDIT RESOURCE (only present when editing)
    ================================================================== --}}
    @if($isEditing)
    <div id="tab-edit" class="page-tab-content hidden">
        <div class="mb-5 flex items-start justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-800">Edit Resource</h2>
                <p class="text-sm text-gray-500 mt-1">Update the details of this approved non-print resource.</p>
            </div>
            <button type="button" data-page-tab="tab-masterlist"
                    class="page-tab-btn text-sm text-blue-600 hover:underline flex items-center gap-1 mt-1">
                &larr; Back to Masterlist
            </button>
        </div>

        <form id="editNpForm"
              action="{{ route('nonprint-masterlist.update', $resource->id) }}"
              class="space-y-8"
              method="POST"
              enctype="multipart/form-data"
              autocomplete="off">
            @csrf
            @method('PUT')

            {{-- IMAGE + BASIC INFO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                {{-- Cover image --}}
                <div class="h-full">
                    <div class="h-full flex flex-col items-center justify-between border-2 border-dashed border-blue-500 rounded-lg p-4 text-center">
                        <img id="npImagePreview"
                             src="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                             data-default-src="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                             alt="Image preview"
                             class="w-full flex-1 object-cover rounded mb-4">
                        <input type="file" name="image" id="npImageUpload" class="hidden" accept="image/*">
                        <label for="npImageUpload" class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            Change Image
                        </label>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG &bull; Max 5MB. Leave blank to keep current.</p>
                    </div>
                </div>

                {{-- Fields --}}
                <div class="md:col-span-2 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Title <span class="text-red-500">*</span></label>
                        {{--
                            FIX: Never use old() for title. Always render the server value directly.
                            The data-server-value attribute is used by the pageshow JS handler
                            to re-stamp the correct value if the browser restores this page from bfcache.
                        --}}
                        <input type="text" name="title" id="np-edit-title-input" required
                               value="{{ $resource->nonprintTitle->title ?? '' }}"
                               data-server-value="{{ $resource->nonprintTitle->title ?? '' }}"
                               autocomplete="off"
                               class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Type <span class="text-red-500">*</span></label>
                            <select name="type" required class="w-full border border-gray-300 rounded px-3 py-2">
                                <option disabled>Select type</option>
                                @foreach ($nonprintTypes as $type)
                                    <option value="{{ $type->id }}"
                                        {{ $resource->nonprint_type_id == $type->id ? 'selected' : '' }}>
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Brand</label>
                            <input type="text" name="brand"
                                   value="{{ $resource->brand ?? '' }}"
                                   autocomplete="off"
                                   class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Code</label>
                            <input type="text" name="code"
                                   value="{{ $resource->code ?? '' }}"
                                   autocomplete="off"
                                   class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Version</label>
                            <input type="text" name="version"
                                   value="{{ $resource->version ?? '' }}"
                                   autocomplete="off"
                                   class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Model</label>
                            <input type="text" name="model"
                                   value="{{ $resource->model ?? '' }}"
                                   autocomplete="off"
                                   class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Size</label>
                            <input type="text" name="size"
                                   value="{{ $resource->size ?? '' }}"
                                   autocomplete="off"
                                   class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">URL / Link</label>
                            <input type="text" name="url"
                                   value="{{ $resource->url ?? '' }}"
                                   autocomplete="off"
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
                // FIX: Never use old() for checkboxes — always use server-rendered $editingSglIds
                $checkedIds = $editingSglIds ?? [];
            @endphp

            <div class="space-y-4">
                <div class="flex gap-6 border-b border-gray-300">
                    @foreach ($stages as $stage)
                        <button type="button"
                                class="np-sgl-tab-btn {{ $loop->first ? 'active border-blue-600 text-blue-600' : 'border-transparent text-gray-600' }} whitespace-nowrap pb-2 px-1 text-sm font-medium border-b-2"
                                data-sgl-tab="{{ $stage['tab'] }}">
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
                                        <th class="border border-gray-300">{{ $gradeLabel }}</th>
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

            <div class="flex justify-end gap-3">
                <button type="button" data-page-tab="tab-masterlist"
                        class="page-tab-btn px-5 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-sm">
                    Cancel
                </button>
                <button type="submit" id="saveNpBtn"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="saveNpText">Save Changes</span>
                    <span id="saveNpLoading" class="hidden"><i class="fas fa-spinner fa-spin mr-2"></i>Saving...</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- =================================================================
        TAB 3 — SCHOOL REQUESTS (division only, level 3)
    ================================================================== --}}
    @if($level === 3)
    <div id="tab-requests" class="page-tab-content hidden">
        <div class="space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-700">School Resource Requests</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Pending non-print requests from schools in your division. Approve to add to masterlist or reject to remove.</p>
                </div>
                <div class="flex gap-2">
                    <form method="GET" action="{{ route('nonprint-masterlist.index') }}" class="flex gap-2">
                        <input type="hidden" name="active_tab" value="tab-requests">
                        <input type="text" name="rq_search" value="{{ request('rq_search') }}"
                               placeholder="Search requests..."
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Search
                        </button>
                        @if(request('rq_search'))
                            <a href="{{ route('nonprint-masterlist.index', ['active_tab' => 'tab-requests']) }}"
                               class="px-4 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
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
                            <th class="px-3 py-3 text-left">Version</th>
                            <th class="px-3 py-3 text-left">Subjects / Grade Levels</th>
                            <th class="px-3 py-3 text-center">Date Submitted</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($requests as $req)
                            @php
                                $reqSglIds  = $req->subject_grade_level_ids ? explode(',', $req->subject_grade_level_ids) : [];
                                $reqSgls    = !empty($reqSglIds)
                                    ? \App\Models\SubjectGradeLevel::with(['subject','gradeLevel'])->whereIn('id', $reqSglIds)->get()
                                    : collect();
                                $reqSglText = $reqSgls->map(fn($s) => ($s->subject->subject_name ?? '') . ' - Gr.' . ($s->gradeLevel->grade ?? ''))->join('; ') ?: '-';
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2">
                                    <img src="{{ $req->cover ? asset('storage/' . $req->cover) : asset('assets/images/def.jpg') }}"
                                         alt="cover" class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-[160px]">
                                    <span title="{{ $req->nonprintTitle->title ?? '' }}">{{ Str::limit($req->nonprintTitle->title ?? '-', 38) }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $req->type->type_name ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $req->brand ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $req->model ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $req->version ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600 text-xs max-w-[200px]">
                                    <span title="{{ $reqSglText }}">{{ Str::limit($reqSglText, 55) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-500 text-xs whitespace-nowrap">
                                    {{ $req->created_at?->format('M d, Y') ?? '-' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- View --}}
                                        <button type="button"
                                                class="view-resource-btn inline-flex items-center gap-1 text-xs px-2.5 py-1.5 bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors font-medium whitespace-nowrap"
                                                data-view-id="{{ $req->id }}"
                                                data-cover="{{ $req->cover ? asset('storage/' . $req->cover) : asset('assets/images/def.jpg') }}"
                                                data-title="{{ $req->nonprintTitle->title ?? '-' }}"
                                                data-type="{{ $req->type->type_name ?? '-' }}"
                                                data-brand="{{ $req->brand ?? '-' }}"
                                                data-code="{{ $req->code ?? '-' }}"
                                                data-version="{{ $req->version ?? '-' }}"
                                                data-model="{{ $req->model ?? '-' }}"
                                                data-url="{{ $req->url ?? '-' }}"
                                                data-size="{{ $req->size ?? '-' }}"
                                                data-subjects="{{ $reqSglText }}"
                                                data-is-request="true">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </button>

                                        {{-- Approve --}}
                                        <form action="{{ route('nonprint-masterlist.approve', $req->id) }}" method="POST"
                                              onsubmit="return confirm('Approve this resource request? It will be added to the masterlist.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition-colors font-medium whitespace-nowrap">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Approve
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form action="{{ route('nonprint-masterlist.reject', $req->id) }}" method="POST"
                                              onsubmit="return confirm('Reject and delete this request? The title will not be removed as it may be used by other resources.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition-colors font-medium whitespace-nowrap">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-gray-400 py-10">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm font-medium">No pending requests.</p>
                                    @if(request('rq_search'))
                                        <p class="text-xs mt-1">Try a different search term.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests && $requests->hasPages())
                <div class="mt-4">
                    {{ $requests->appends(array_filter(['rq_search' => request('rq_search'), 'active_tab' => 'tab-requests']))->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ===== VIEW RESOURCE MODAL ===== --}}
    <div id="viewNpModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="viewNpModalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl z-10 mb-10">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Resource Details</h3>
                    <button id="closeNpViewModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-1 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    <div class="flex gap-5">
                        {{-- Cover --}}
                        <div class="shrink-0">
                            <img id="vm-cover" src="" alt="Cover"
                                 class="w-28 h-40 object-cover rounded-lg border border-gray-200 shadow-sm bg-gray-100">
                        </div>

                        {{-- Core metadata --}}
                        <div class="flex-1 min-w-0 space-y-2.5">
                            <div>
                                <h4 id="vm-title" class="text-lg font-bold text-gray-900 leading-snug"></h4>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-0.5">
                                <span id="vm-type-badge"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"></span>
                                <span id="vm-status-badge"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Approved
                                </span>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm pt-1">
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Brand</dt>
                                    <dd id="vm-brand" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Code</dt>
                                    <dd id="vm-code" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Version</dt>
                                    <dd id="vm-version" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Model</dt>
                                    <dd id="vm-model" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Size</dt>
                                    <dd id="vm-size" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">URL / Link</dt>
                                    <dd id="vm-url" class="text-gray-700 mt-0.5 text-xs break-all"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Subjects --}}
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Subjects / Grade Levels</p>
                        <p id="vm-subjects" class="text-sm text-gray-700 leading-relaxed"></p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <button id="closeNpViewModalFooter"
                            class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                        Close
                    </button>
                    <a id="vm-edit-link" href="#"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                        </svg>
                        Edit Resource
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    // ── PAGE-LEVEL TAB MANAGEMENT ───────────────────────────────────────
    const STORAGE_KEY     = 'nonprint_masterlist_activeTab';
    const pageTabBtns     = document.querySelectorAll('.page-tab-btn');
    const pageTabContents = document.querySelectorAll('.page-tab-content');

    function activatePageTab(targetId, save = true) {
        const validIds = Array.from(pageTabContents).map(c => c.id);
        if (!validIds.includes(targetId)) targetId = validIds[0] ?? 'tab-masterlist';

        pageTabBtns.forEach(btn => {
            const isActive = btn.dataset.pageTab === targetId;
            btn.classList.toggle('border-blue-600',    isActive);
            btn.classList.toggle('text-blue-600',      isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-500',      !isActive);
        });
        pageTabContents.forEach(c => c.classList.toggle('hidden', c.id !== targetId));
        if (save) sessionStorage.setItem(STORAGE_KEY, targetId);
    }

    let initialTab = '{{ session('active_tab') }}'
                  || (new URLSearchParams(location.search)).get('active_tab')
                  || sessionStorage.getItem(STORAGE_KEY)
                  || 'tab-masterlist';

    @if($isEditing)
    initialTab = 'tab-edit';
    @endif

    activatePageTab(initialTab, false);

    pageTabBtns.forEach(btn => {
        btn.addEventListener('click', () => activatePageTab(btn.dataset.pageTab, true));
    });

    // ── bfcache: re-stamp title input with server value on back/forward nav ──
    window.addEventListener('pageshow', function (e) {
        const titleInput = document.getElementById('np-edit-title-input');
        if (titleInput) {
            titleInput.value = titleInput.getAttribute('data-server-value') ?? '';
        }
    });

    // ── SGL TABS (inside edit form) ─────────────────────────────────────
    const sglTabBtns     = document.querySelectorAll('.np-sgl-tab-btn');
    const sglTabContents = document.querySelectorAll('.np-sgl-tab-content');

    function activateSglTab(id) {
        sglTabBtns.forEach(btn => {
            const a = btn.dataset.sglTab === id;
            btn.classList.toggle('border-blue-600',    a);
            btn.classList.toggle('text-blue-600',      a);
            btn.classList.toggle('active',             a);
            btn.classList.toggle('border-transparent', !a);
            btn.classList.toggle('text-gray-600',      !a);
        });
        sglTabContents.forEach(c => c.classList.toggle('hidden', c.id !== id));
    }

    sglTabBtns.forEach(btn => btn.addEventListener('click', () => activateSglTab(btn.dataset.sglTab)));
    if (sglTabBtns.length) activateSglTab(sglTabBtns[0].dataset.sglTab);

    // ── IMAGE PREVIEW ────────────────────────────────────────────────────
    const npImageUpload  = document.getElementById('npImageUpload');
    const npImagePreview = document.getElementById('npImagePreview');
    if (npImageUpload && npImagePreview) {
        npImageUpload.addEventListener('change', function () {
            if (this.files[0]) {
                npImagePreview.src = URL.createObjectURL(this.files[0]);
            }
        });
    }

    // ── VIEW RESOURCE MODAL ──────────────────────────────────────────────
    const viewModal       = document.getElementById('viewNpModal');
    const viewBackdrop    = document.getElementById('viewNpModalBackdrop');
    const closeViewBtn    = document.getElementById('closeNpViewModal');
    const closeViewFooter = document.getElementById('closeNpViewModalFooter');

    const vmCover       = document.getElementById('vm-cover');
    const vmTitle       = document.getElementById('vm-title');
    const vmTypeBadge   = document.getElementById('vm-type-badge');
    const vmStatusBadge = document.getElementById('vm-status-badge');
    const vmBrand       = document.getElementById('vm-brand');
    const vmCode        = document.getElementById('vm-code');
    const vmVersion     = document.getElementById('vm-version');
    const vmModel       = document.getElementById('vm-model');
    const vmSize        = document.getElementById('vm-size');
    const vmUrl         = document.getElementById('vm-url');
    const vmSubjects    = document.getElementById('vm-subjects');
    const vmEditLink    = document.getElementById('vm-edit-link');

    function openViewModal(btn) {
        const isRequest = btn.dataset.isRequest === 'true';

        vmCover.src            = btn.dataset.cover;
        vmTitle.textContent    = btn.dataset.title;
        vmTypeBadge.textContent = btn.dataset.type;
        vmBrand.textContent    = btn.dataset.brand;
        vmCode.textContent     = btn.dataset.code;
        vmVersion.textContent  = btn.dataset.version;
        vmModel.textContent    = btn.dataset.model;
        vmSize.textContent     = btn.dataset.size;
        vmUrl.textContent      = btn.dataset.url !== '-' ? btn.dataset.url : '-';
        vmSubjects.textContent = btn.dataset.subjects;

        // Status badge: pending requests show yellow, approved show green
        if (isRequest) {
            vmStatusBadge.textContent = '⏳ Pending Approval';
            vmStatusBadge.className   = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
            vmEditLink.classList.add('hidden');
        } else {
            vmStatusBadge.textContent = '✓ Approved';
            vmStatusBadge.className   = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
            vmEditLink.href           = `/nonprint-masterlist/${btn.dataset.viewId}/edit`;
            vmEditLink.classList.remove('hidden');
        }

        viewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        viewModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.view-resource-btn').forEach(btn => {
        btn.addEventListener('click', () => openViewModal(btn));
    });

    closeViewBtn    && closeViewBtn.addEventListener('click', closeModal);
    closeViewFooter && closeViewFooter.addEventListener('click', closeModal);
    viewBackdrop    && viewBackdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !viewModal.classList.contains('hidden')) closeModal();
    });

    // ── SUBMIT SPINNER ───────────────────────────────────────────────────
    const saveBtn     = document.getElementById('saveNpBtn');
    const saveText    = document.getElementById('saveNpText');
    const saveLoading = document.getElementById('saveNpLoading');
    const editFormEl  = document.getElementById('editNpForm');
    if (editFormEl && saveBtn) {
        editFormEl.addEventListener('submit', () => {
            saveBtn.disabled = true;
            if (saveText)    saveText.classList.add('hidden');
            if (saveLoading) saveLoading.classList.remove('hidden');
        });
    }
})();
</script>

@endsection
