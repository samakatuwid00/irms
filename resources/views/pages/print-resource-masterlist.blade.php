@extends('pages.layout.layout')

@section('title', 'Print Resource Masterlist')
@section('page-title', 'Print Resource Masterlist')
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
        $isEditing   = isset($resource);
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
                    class="page-tab-btn whitespace-nowrap pb-3 px-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                    id="editTabBtn">
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
                    <h3 class="text-base font-semibold text-gray-700">Approved Print Resources</h3>
                    <p class="text-xs text-gray-400 mt-0.5">All resources with approved status across the masterlist.</p>
                </div>
                <div class="flex gap-2">
                    <form method="GET" action="{{ route('masterlist.index') }}" class="flex gap-2">
                        <input type="hidden" name="active_tab" value="tab-masterlist">
                        <input type="text" name="ml_search" value="{{ request('ml_search') }}"
                               placeholder="Search title, author, ISBN..."
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Search
                        </button>
                        @if(request('ml_search'))
                            <a href="{{ route('masterlist.index') }}"
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
                            <th class="px-3 py-3 text-left">Author(s)</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Publisher</th>
                            <th class="px-3 py-3 text-left">Edition</th>
                            <th class="px-3 py-3 text-left">Copyright</th>
                            <th class="px-3 py-3 text-left">ISBN</th>
                            <th class="px-3 py-3 text-left">Subjects / Grade Levels</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($masterlist as $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2">
                                    <img src="{{ $row->cover ? asset('storage/' . $row->cover) : asset('assets/images/def.jpg') }}"
                                         alt="cover" class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-[180px]">
                                    <span title="{{ $row->printTitle->title ?? '' }}">{{ Str::limit($row->printTitle->title ?? '-', 40) }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 max-w-[140px]">
                                    {{ Str::limit($row->printTitle->authors->pluck('author_name')->join(', ') ?: '-', 35) }}
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $row->type->shortname ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $row->publisher ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $row->edition ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $row->copyright ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $row->isbn ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600 max-w-[220px] text-xs">
                                    @php
                                        $sglIds = $row->subject_grade_level_ids ? explode(',', $row->subject_grade_level_ids) : [];
                                        if (!empty($sglIds)) {
                                            $sgls = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])
                                                ->whereIn('id', $sglIds)->get();
                                            $sglText = $sgls->map(fn($s) => ($s->subject->subject_name ?? '') . ' - ' . ($s->gradeLevel->grade ?? ''))->join('; ');
                                        } else {
                                            $sglText = '-';
                                        }
                                    @endphp
                                    <span title="{{ $sglText }}">{{ Str::limit($sglText, 60) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                                data-view-id="{{ $row->id }}"
                                                data-cover="{{ $row->cover ? asset('storage/' . $row->cover) : asset('assets/images/def.jpg') }}"
                                                data-title="{{ $row->printTitle->title ?? '-' }}"
                                                data-authors="{{ $row->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}"
                                                data-type="{{ $row->type->shortname ?? '-' }}"
                                                data-publisher="{{ $row->publisher ?? '-' }}"
                                                data-volume="{{ $row->volume ?? '-' }}"
                                                data-edition="{{ $row->edition ?? '-' }}"
                                                data-copyright="{{ $row->copyright ?? '-' }}"
                                                data-isbn="{{ $row->isbn ?? '-' }}"
                                                data-pages="{{ $row->pages ?? '-' }}"
                                                data-subjects="{{ $sglText ?? '-' }}"
                                                class="view-resource-btn inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap font-medium">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </button>
                                        <a href="{{ route('masterlist.edit', $row->id) }}"
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
                                <td colspan="10" class="text-center text-gray-400 py-10">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <p class="text-sm font-medium">No approved resources found.</p>
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
                <p class="text-sm text-gray-500 mt-1">Update the details of this approved resource.</p>
            </div>
            <a href="{{ route('masterlist.index') }}"
               class="text-sm text-blue-600 hover:underline flex items-center gap-1 mt-1">
                &larr; Back to Masterlist
            </a>
        </div>

        <form id="editForm"
              action="{{ route('masterlist.update', $resource->id) }}"
              class="resource-form space-y-8"
              method="POST"
              enctype="multipart/form-data"
              autocomplete="off">
            @csrf
            @method('PUT')

            {{-- IMAGE + BASIC INFO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                <div class="h-full">
                    <div class="h-full flex flex-col items-center justify-between border-2 border-dashed border-blue-500 rounded-lg p-4 text-center">
                        <img id="imagePreview"
                             src="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                             data-default-src="{{ $resource->cover ? asset('storage/' . $resource->cover) : asset('assets/images/def.jpg') }}"
                             alt="Image preview"
                             class="w-full flex-1 object-cover rounded mb-4">
                        <input type="file" name="image" id="imageUpload" class="hidden" accept="image/*">
                        <label for="imageUpload" class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            Change Image
                        </label>
                        <p class="text-xs text-gray-500 mt-2">JPG, PNG &bull; Max 5MB. Leave blank to keep current.</p>
                    </div>
                </div>

                <div class="md:col-span-2 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="edit-title-input" required
                                   value="{{ $resource->printTitle->title ?? '' }}"
                                   data-server-value="{{ $resource->printTitle->title ?? '' }}"
                                   autocomplete="off"
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
                                   value="{{ $resource->publisher ?? '' }}"
                                   autocomplete="off"
                                   class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm mb-1">Type <span class="text-red-500">*</span></label>
                                <select name="type" required class="w-full border border-gray-300 rounded px-3 py-2">
                                    <option disabled>Select type</option>
                                    @foreach ($printTypes as $type)
                                        <option value="{{ $type->id }}"
                                            {{ $resource->print_type_id == $type->id ? 'selected' : '' }}>
                                            {{ $type->type_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Volume</label>
                                <input name="volume" value="{{ $resource->volume ?? '' }}"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Edition</label>
                                <input name="edition" value="{{ $resource->edition ?? '' }}"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm mb-1">Copyright</label>
                                <input name="copyright" type="number"
                                       value="{{ $resource->copyright ?? '' }}"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">ISBN</label>
                                <input name="isbn" value="{{ $resource->isbn ?? '' }}"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Pages</label>
                                <input name="pages" type="number"
                                       value="{{ $resource->pages ?? '' }}"
                                       autocomplete="off"
                                       class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUBJECT-GRADE LEVEL --}}
            @php
                $stages = [
                    'S1'  => ['tab' => 'stage1', 'label' => 'Key Stage 1', 'grades' => [0=>'K',1=>'1',2=>'2',3=>'3']],
                    'ES'  => ['tab' => 'stage2', 'label' => 'Key Stage 2', 'grades' => [4=>'4',5=>'5',6=>'6']],
                    'JHS' => ['tab' => 'jhs',    'label' => 'Junior High',  'grades' => [7=>'7',8=>'8',9=>'9',10=>'10']],
                    'SHS' => ['tab' => 'shs',    'label' => 'Senior High',  'grades' => [11=>'11',12=>'12']],
                ];
                $grouped    = $subjectGradeLevels->groupBy(['key_stage', 'subject_name']);
                $checkedIds = $editingSglIds ?? [];
            @endphp

            <div class="space-y-4">
                <div class="flex gap-6 border-b border-gray-300">
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
                <a href="{{ route('masterlist.index') }}"
                   class="px-5 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-sm">
                    Cancel
                </a>
                <button type="submit" id="savePrintBtn"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="savePrintText">Save Changes</span>
                    <span id="savePrintLoading" class="hidden"><i class="fas fa-spinner fa-spin mr-2"></i>Saving...</span>
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
                    <p class="text-xs text-gray-400 mt-0.5">Pending requests from schools in your division. Approve to add to masterlist or reject to remove.</p>
                </div>
                <div class="flex gap-2">
                    <form method="GET" action="{{ route('masterlist.index') }}" class="flex gap-2">
                        <input type="hidden" name="active_tab" value="tab-requests">
                        <input type="text" name="rq_search" value="{{ request('rq_search') }}"
                               placeholder="Search requests..."
                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                            Search
                        </button>
                        @if(request('rq_search'))
                            <a href="{{ route('masterlist.index', ['active_tab' => 'tab-requests']) }}"
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
                            <th class="px-3 py-3 text-left">Author(s)</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Publisher</th>
                            <th class="px-3 py-3 text-left">Edition</th>
                            <th class="px-3 py-3 text-left">Copyright</th>
                            <th class="px-3 py-3 text-left">Subjects / Grade Levels</th>
                            <th class="px-3 py-3 text-center">Date Submitted</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($requests as $req)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2">
                                    <img src="{{ $req->cover ? asset('storage/' . $req->cover) : asset('assets/images/def.jpg') }}"
                                         alt="cover" class="w-9 h-12 object-cover rounded border border-gray-200 shadow-sm">
                                </td>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-40">
                                    <span title="{{ $req->printTitle->title ?? '' }}">{{ Str::limit($req->printTitle->title ?? '-', 38) }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 max-w-32.5">
                                    {{ Str::limit($req->printTitle->authors->pluck('author_name')->join(', ') ?: '-', 32) }}
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $req->type->shortname ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $req->publisher ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $req->edition ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $req->copyright ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600 text-xs max-w-50">
                                    @php
                                        $sglIds = $req->subject_grade_level_ids ? explode(',', $req->subject_grade_level_ids) : [];
                                        if (!empty($sglIds)) {
                                            $reqSgls = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])
                                                ->whereIn('id', $sglIds)->get();
                                            $reqSglText = $reqSgls->map(fn($s) => ($s->subject->subject_name ?? '') . ' - ' . ($s->gradeLevel->grade ?? ''))->join('; ');
                                        } else {
                                            $reqSglText = '-';
                                        }
                                    @endphp
                                    <span title="{{ $reqSglText }}">{{ Str::limit($reqSglText, 55) }}</span>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-500 text-xs whitespace-nowrap">{{ $req->created_at?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- Approve --}}
                                        <form action="{{ route('masterlist.approve', $req->id) }}" method="POST"
                                              onsubmit="return confirm('Approve this resource request? It will be added to the masterlist.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition-colors font-medium whitespace-nowrap"
                                                    title="Approve">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Approve
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <form action="{{ route('masterlist.reject', $req->id) }}" method="POST"
                                              onsubmit="return confirm('Reject and delete this request? The title and authors will not be removed as they may be used by other resources.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition-colors font-medium whitespace-nowrap"
                                                    title="Reject">
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
                                <td colspan="10" class="text-center text-gray-400 py-10">
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
    <div id="viewResourceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="viewModalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-10">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl z-10 mb-10">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Resource Details</h3>
                    <button id="closeViewModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-1 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    <div class="flex gap-5">
                        {{-- Cover image --}}
                        <div class="shrink-0">
                            <img id="vm-cover" src="" alt="Cover"
                                 class="w-28 h-40 object-cover rounded-lg border border-gray-200 shadow-sm bg-gray-100">
                        </div>

                        {{-- Core metadata --}}
                        <div class="flex-1 min-w-0 space-y-2.5">
                            <div>
                                <h4 id="vm-title" class="text-lg font-bold text-gray-900 leading-snug"></h4>
                                <p id="vm-authors" class="text-sm text-gray-500 mt-0.5 italic"></p>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-0.5">
                                <span id="vm-type-badge"
                                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"></span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Approved
                                </span>
                            </div>

                            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm pt-1">
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Publisher</dt>
                                    <dd id="vm-publisher" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Copyright</dt>
                                    <dd id="vm-copyright" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Edition</dt>
                                    <dd id="vm-edition" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Volume</dt>
                                    <dd id="vm-volume" class="text-gray-700 mt-0.5"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">ISBN</dt>
                                    <dd id="vm-isbn" class="text-gray-700 mt-0.5 font-mono text-xs tracking-wider"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pages</dt>
                                    <dd id="vm-pages" class="text-gray-700 mt-0.5"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Subjects divider --}}
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Subjects / Grade Levels</p>
                        <p id="vm-subjects" class="text-sm text-gray-700 leading-relaxed"></p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                    <button id="closeViewModalFooter"
                            class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Seed edit-mode authors for JS --}}
@if($isEditing)
<script>window.__editAuthors = @json($editingAuthors ?? []);</script>
@endif

<script>
(function () {
    // ── PAGE-LEVEL TAB MANAGEMENT ───────────────────────────────────────
    const pageTabBtns     = document.querySelectorAll('.page-tab-btn');
    const pageTabContents = document.querySelectorAll('.page-tab-content');
    const isEditing       = {{ $isEditing ? 'true' : 'false' }};

    function activatePageTab(targetId) {
        if (isEditing && targetId !== 'tab-edit') {
            window.location.href = '{{ route('masterlist.index') }}';
            return;
        }

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
    }

    const initialTab = isEditing ? 'tab-edit' : 'tab-masterlist';
    activatePageTab(initialTab);

    pageTabBtns.forEach(btn => {
        btn.addEventListener('click', () => activatePageTab(btn.dataset.pageTab));
    });

    window.addEventListener('pageshow', function (e) {
        const titleInput = document.getElementById('edit-title-input');
        if (titleInput) {
            titleInput.value = titleInput.getAttribute('data-server-value') ?? '';
        }
    });

    // ── SGL TABS (inside edit form) ─────────────────────────────────────
    const sglTabBtns     = document.querySelectorAll('.sgl-tab-btn');
    const sglTabContents = document.querySelectorAll('.sgl-tab-content');

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
    const imageUpload  = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');
    if (imageUpload && imagePreview) {
        imageUpload.addEventListener('change', function () {
            if (this.files[0]) {
                imagePreview.src = URL.createObjectURL(this.files[0]);
            }
        });
    }

    // ── VIEW RESOURCE MODAL ──────────────────────────────────────────────
    const viewModal        = document.getElementById('viewResourceModal');
    const viewBackdrop     = document.getElementById('viewModalBackdrop');
    const closeViewBtn     = document.getElementById('closeViewModal');
    const closeViewFooter  = document.getElementById('closeViewModalFooter');

    const vmCover      = document.getElementById('vm-cover');
    const vmTitle      = document.getElementById('vm-title');
    const vmAuthors    = document.getElementById('vm-authors');
    const vmTypeBadge  = document.getElementById('vm-type-badge');
    const vmPublisher  = document.getElementById('vm-publisher');
    const vmCopyright  = document.getElementById('vm-copyright');
    const vmEdition    = document.getElementById('vm-edition');
    const vmVolume     = document.getElementById('vm-volume');
    const vmIsbn       = document.getElementById('vm-isbn');
    const vmPages      = document.getElementById('vm-pages');
    const vmSubjects   = document.getElementById('vm-subjects');

    function openViewModal(btn) {
        const id = btn.dataset.viewId;

        vmCover.src              = btn.dataset.cover;
        vmTitle.textContent      = btn.dataset.title;
        vmAuthors.textContent    = btn.dataset.authors !== '-' ? btn.dataset.authors : '';
        vmTypeBadge.textContent  = btn.dataset.type;
        vmPublisher.textContent  = btn.dataset.publisher;
        vmCopyright.textContent  = btn.dataset.copyright;
        vmEdition.textContent    = btn.dataset.edition;
        vmVolume.textContent     = btn.dataset.volume;
        vmIsbn.textContent       = btn.dataset.isbn;
        vmPages.textContent      = btn.dataset.pages;
        vmSubjects.textContent   = btn.dataset.subjects;

        viewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
        viewModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.view-resource-btn').forEach(btn => {
        btn.addEventListener('click', () => openViewModal(btn));
    });

    closeViewBtn    && closeViewBtn.addEventListener('click', closeViewModal);
    closeViewFooter && closeViewFooter.addEventListener('click', closeViewModal);
    viewBackdrop    && viewBackdrop.addEventListener('click', closeViewModal);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !viewModal.classList.contains('hidden')) closeViewModal();
    });

    // ── AUTHOR TAGS (edit form only — guarded) ──────────────────────────
    const authorWrapper = document.getElementById('author-wrapper');
    const authorInput   = document.getElementById('author-input');
    const authorsHidden = document.getElementById('authors-hidden');

    if (authorWrapper) {
        let authors = (window.__editAuthors || []).slice();

        function renderTags() {
            authorWrapper.querySelectorAll('.author-tag').forEach(el => el.remove());
            authors.forEach((name, idx) => {
                const tag = document.createElement('span');
                tag.className = 'author-tag inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs rounded-full px-2 py-0.5';
                tag.innerHTML = `${name} <button type="button" data-idx="${idx}" class="remove-author text-blue-600 hover:text-red-600 font-bold">&times;</button>`;
                authorWrapper.insertBefore(tag, authorInput);
            });
            authorsHidden.value = JSON.stringify(authors);
        }

        authorWrapper.addEventListener('click', e => {
            const btn = e.target.closest('.remove-author');
            if (btn) { authors.splice(+btn.dataset.idx, 1); renderTags(); }
        });

        if (authorInput) {
            authorInput.addEventListener('keydown', e => {
                if ((e.key === 'Enter' || e.key === ',') && authorInput.value.trim()) {
                    e.preventDefault();
                    const name = authorInput.value.trim().replace(/,$/, '');
                    if (name && !authors.includes(name)) { authors.push(name); renderTags(); }
                    authorInput.value = '';
                }
            });
        }

        renderTags();
    }

    // ── SUBMIT SPINNER ───────────────────────────────────────────────────
    const saveBtn      = document.getElementById('savePrintBtn');
    const saveText     = document.getElementById('savePrintText');
    const saveLoading  = document.getElementById('savePrintLoading');
    const editFormEl   = document.getElementById('editForm');
    if (editFormEl && saveBtn) {
        editFormEl.addEventListener('submit', () => {
            saveBtn.disabled = true;
            if (saveText)    saveText.classList.add('hidden');
            if (saveLoading) saveLoading.classList.remove('hidden');
        });
    }
})();
</script>

@vite(['resources/js/add-print-resource.js'])

@endsection
