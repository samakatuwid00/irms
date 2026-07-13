@extends('pages.layout.layout')

@section('title', 'Print Resource Masterlist')
@section('page-title', 'Print Resource Masterlist')
@section('header-title', 'Welcome, ' . Auth::user()->firstname . ' ' . Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Masterlist')

@section('content')

<div id="print-resources-wrapper" class="space-y-4">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Print Masterlist</h1>
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
                            class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm 
                            w-full md:w-[300px] lg:w-[400px] xl:w-[500px] 2xl:w-[600px]
                            focus:outline-none focus:ring-2 focus:ring-blue-500">
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

            {{-- ── View Toggle Toolbar ── --}}
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label class="whitespace-nowrap font-medium">Show entries:</label>
                    <select id="ml-per-page-select"
                        class="border border-gray-300 rounded-xl px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        onchange="handlePerPageChange(this, 'tab-masterlist', 'ml_per_page', 'ml-view-input')">
                        @foreach([5, 10, 15, 20] as $opt)
                            <option value="{{ $opt }}" {{ request('ml_per_page', 10) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Hidden input so JS and per-page onchange can always read the current view --}}
                <input type="hidden" id="ml-view-input" value="{{ request('ml_view', 'card') }}">
                <div class="flex items-center bg-gray-100 p-1 rounded-xl">
                    <button type="button"
                        class="ml-view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5"
                        data-ml-view="card">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="hidden md:inline">Cards</span>
                    </button>
                    <button type="button"
                        class="ml-view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5"
                        data-ml-view="table">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                        </svg>
                        <span class="hidden md:inline">Table</span>
                    </button>
                </div>
            </div>

            {{-- ── TABLE VIEW ── --}}
            <div id="ml-table-view">
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-2 py-3 text-left w-18">Cover</th>
                            <th class="px-6 py-3 text-left">Title</th>
                            <th class="px-2 py-3 text-left">Author(s)</th>
                            <th class="px-2 py-3 text-left">Type</th>
                            <th class="px-2 py-3 text-left">Publisher</th>
                            <th class="px-2 py-3 text-left">Edition</th>
                            <th class="px-2 py-3 text-left">Copyright</th>
                            <th class="px-2 py-3 text-left">ISBN</th>
                            <th class="px-2 py-3 text-left">Subjects / Grade Levels</th>
                            <th class="px-2 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($masterlist as $row)
                            @php
                                $sglIds  = $row->subject_grade_level_ids ? explode(',', $row->subject_grade_level_ids) : [];
                                $sglText = '-';
                                if (!empty($sglIds)) {
                                    $sgls    = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])->whereIn('id', $sglIds)->get();
                                    $sglText = $sgls->map(fn($s) => ($s->subject->subject_name ?? '') . ' - ' . ($s->gradeLevel->grade ?? ''))->join('; ');
                                }

                                $history = collect($row->verification_history ?? []);
                                $currentVerifier = $history
                                    ->reverse()
                                    ->first(fn($item) => !empty($item['action_type']) && $item['action_type'] !== 'first_verification')
                                    ?? $history->firstWhere('action_type', 'first_verification')
                                    ?? $history->first();

                                $currentVerifierName = $currentVerifier['name'] ?? ($row->verifiedBy ? trim(($row->verifiedBy->firstname ?? '') . ' ' . ($row->verifiedBy->lastname ?? '')) : '');
                                $currentVerifierRole = $currentVerifier['role'] ?? ($row->verifiedBy?->userType?->type_name ?? '');
                                $currentVerifierLevel = $currentVerifier['level'] ?? ($row->verifiedBy?->userType?->level ?? '');
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-2 py-2">
                                    <img src="{{ $row->thumb_url }}" alt="cover"
                                         class="cover-img w-14 h-18 object-cover rounded border border-gray-200 shadow-sm" loading="lazy">
                                </td>
                                <td class="px-2 py-2 font-medium text-gray-900 max-w-75">
                                    <span class="inline-flex items-center gap-1.5" title="{{ $row->verified ? 'Verified by SDO / Division librarian' : ($row->printTitle->title ?? '') }}">
                                        @include('pages.components.verified-badge', ['verified' => $row->verified])
                                        <span>{{ Str::limit($row->printTitle->title ?? '-', 40) }}</span>
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-gray-600 max-w-35">
                                    {{ Str::limit($row->printTitle->authors->pluck('author_name')->join(', ') ?: '-', 35) }}
                                </td>
                                <td class="px-2 py-2 text-gray-600 whitespace-nowrap">{{ $row->type->shortname ?? '-' }}</td>
                                <td class="px-2 py-2 text-gray-600">{{ $row->publisher ?? '-' }}</td>
                                <td class="px-2 py-2 text-gray-600">{{ $row->edition ?? '-' }}</td>
                                <td class="px-2 py-2 text-gray-600">{{ $row->copyright ?? '-' }}</td>
                                <td class="px-2 py-2 text-gray-600">{{ $row->isbn ?? '-' }}</td>
                                <td class="px-2 py-2 text-gray-600 max-w-55 text-xs">
                                    <span title="{{ $sglText }}">{{ Str::limit($sglText, 60) }}</span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                                data-view-id="{{ $row->id }}"
                                                data-cover="{{ $row->cover_url }}"
                                                data-title="{{ $row->printTitle->title ?? '-' }}"
                                                data-authors="{{ $row->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}"
                                                data-type="{{ $row->type->type_name ?? '-' }}"
                                                data-publisher="{{ $row->publisher ?? '-' }}"
                                                data-volume="{{ $row->volume ?? '-' }}"
                                                data-edition="{{ $row->edition ?? '-' }}"
                                                data-copyright="{{ $row->copyright ?? '-' }}"
                                                data-isbn="{{ $row->isbn ?? '-' }}"
                                                data-pages="{{ $row->pages ?? '-' }}"
                                                data-subjects="{{ $sglText }}"
                                                data-verified="{{ $row->verified ? '1' : '0' }}"
                                                data-verified-by-name="{{ $row->verifiedBy ? trim(($row->verifiedBy->firstname ?? '') . ' ' . ($row->verifiedBy->lastname ?? '')) : '' }}"
                                                data-verified-by-role="{{ $row->verifiedBy?->userType?->type_name ?? '' }}"
                                                data-verified-by-level="{{ $row->verifiedBy?->userType?->level ?? '' }}"
                                                data-current-verifier-name="{{ $currentVerifierName }}"
                                                data-current-verifier-role="{{ $currentVerifierRole }}"
                                                data-current-verifier-level="{{ $currentVerifierLevel }}"
                                                data-verification-history='@json($row->verification_history ?? [])'
                                                class="view-resource-btn inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap font-medium">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </button>
                                        <a href="{{ route('masterlist.edit', $row->id) }}?ml_page={{ $masterlist->currentPage() }}{{ request('ml_search') ? '&ml_search=' . urlencode(request('ml_search')) : '' }}&ml_view={{ request('ml_view', 'table') }}"
                                           class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors whitespace-nowrap font-medium">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        {{-- Delete --}}
                                        <!-- <form action="{{ route('masterlist.destroy', $row->id) }}" method="POST"
                                            data-delete-form
                                            data-title="{{ $row->printTitle->title ?? 'this resource' }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="ml_page"   value="{{ $masterlist->currentPage() }}">
                                            <input type="hidden" name="ml_search" value="{{ request('ml_search') }}">
                                            <button type="button"
                                                    data-delete-btn
                                                    class="inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition-colors whitespace-nowrap font-medium">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form> -->
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
                    {{ $masterlist->appends(array_filter(['ml_search' => request('ml_search'), 'active_tab' => 'tab-masterlist', 'ml_per_page' => request('ml_per_page', 10), 'ml_view' => request('ml_view', 'table')]))->links('pagination::print-resource') }}
                </div>
            @endif
        </div>{{-- end ml-table-view --}}

            {{-- ── CARD VIEW ── --}}
            <div id="ml-card-view" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4 mt-2">
                    @forelse($masterlist as $row)
                        @php
                            $sglIdsC  = $row->subject_grade_level_ids ? explode(',', $row->subject_grade_level_ids) : [];
                            $sglTextC = '-';
                            if (!empty($sglIdsC)) {
                                $sglsC    = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])->whereIn('id', $sglIdsC)->get();
                                $sglTextC = $sglsC->map(fn($s) => ($s->subject->subject_name ?? '') . ' - ' . ($s->gradeLevel->grade ?? ''))->join('; ');
                            }

                            $historyC = collect($row->verification_history ?? []);
                            $currentVerifierC = $historyC
                                ->reverse()
                                ->first(fn($item) => !empty($item['action_type']) && $item['action_type'] !== 'first_verification')
                                ?? $historyC->firstWhere('action_type', 'first_verification')
                                ?? $historyC->first();

                            $currentVerifierNameC = $currentVerifierC['name'] ?? ($row->verifiedBy ? trim(($row->verifiedBy->firstname ?? '') . ' ' . ($row->verifiedBy->lastname ?? '')) : '');
                            $currentVerifierRoleC = $currentVerifierC['role'] ?? ($row->verifiedBy?->userType?->type_name ?? '');
                            $currentVerifierLevelC = $currentVerifierC['level'] ?? ($row->verifiedBy?->userType?->level ?? '');
                        @endphp
                        <div class="relative bg-white rounded-xl shadow overflow-hidden flex flex-col group cursor-pointer"
                             onclick="(function(el){
                                 var btn = el.querySelector('.view-resource-btn');
                                 if(btn) btn.click();
                             })(this)">
                            <div class="relative w-full" style="padding-bottom:140%;">
                                <img src="{{ $row->thumb_url }}" alt="{{ $row->printTitle->title ?? '' }}"
                                     class="cover-img absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                     loading="lazy">
                                <a href="{{ route('masterlist.edit', $row->id) }}?ml_page={{ $masterlist->currentPage() }}{{ request('ml_search') ? '&ml_search=' . urlencode(request('ml_search')) : '' }}&ml_view=card"
                                   onclick="event.stopPropagation()"
                                   title="Edit resource"
                                   aria-label="Edit resource"
                                   class="absolute top-2 left-2 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full border border-blue-200 bg-white/95 text-blue-600 shadow-sm backdrop-blur-sm transition-colors hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:h-8 sm:w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                    </svg>
                                </a>
                                <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2 py-0.5 rounded-full shadow text-blue-700">
                                    {{ $row->type->shortname ?? '' }}
                                </span>
                            </div>
                            <div class="p-3 flex flex-col gap-1 flex-1">
                                <h3 class="text-xs font-semibold text-gray-900 leading-tight line-clamp-2 inline-flex items-start gap-1">
                                    @include('pages.components.verified-badge', ['verified' => $row->verified, 'class' => 'w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5'])
                                    <span>{{ $row->printTitle->title ?? '-' }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 truncate">{{ $row->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $row->publisher ?? '-' }}</p>
                                <div class="mt-auto pt-2 flex items-center justify-between gap-1">
                                    {{-- hidden view btn so card click triggers it --}}
                                    <button type="button"
                                            data-view-id="{{ $row->id }}"
                                            data-cover="{{ $row->cover_url }}"
                                            data-title="{{ $row->printTitle->title ?? '-' }}"
                                            data-authors="{{ $row->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}"
                                            data-type="{{ $row->type->type_name ?? '-' }}"
                                            data-publisher="{{ $row->publisher ?? '-' }}"
                                            data-volume="{{ $row->volume ?? '-' }}"
                                            data-edition="{{ $row->edition ?? '-' }}"
                                            data-copyright="{{ $row->copyright ?? '-' }}"
                                            data-isbn="{{ $row->isbn ?? '-' }}"
                                            data-pages="{{ $row->pages ?? '-' }}"
                                            data-subjects="{{ $sglTextC }}"
                                            data-verified="{{ $row->verified ? '1' : '0' }}"
                                            data-verified-by-name="{{ $row->verifiedBy ? trim(($row->verifiedBy->firstname ?? '') . ' ' . ($row->verifiedBy->lastname ?? '')) : '' }}"
                                            data-verified-by-role="{{ $row->verifiedBy?->userType?->type_name ?? '' }}"
                                            data-verified-by-level="{{ $row->verifiedBy?->userType?->level ?? '' }}"
                                            data-current-verifier-name="{{ $currentVerifierNameC }}"
                                            data-current-verifier-role="{{ $currentVerifierRoleC }}"
                                            data-current-verifier-level="{{ $currentVerifierLevelC }}"
                                            data-verification-history='@json($row->verification_history ?? [])'
                                            class="view-resource-btn hidden">
                                    </button>
                                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $row->copyright ?? '' }}</span>
                                    <span class="text-[11px] text-blue-600 sm:hidden">Tap card to view</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-gray-400 py-10">
                            <p class="text-sm font-medium">No approved resources found.</p>
                        </div>
                    @endforelse
                </div>
                @if($masterlist->hasPages())
                    <div class="mt-4">
                        {{ $masterlist->appends(array_filter(['ml_search' => request('ml_search'), 'active_tab' => 'tab-masterlist', 'ml_per_page' => request('ml_per_page', 10), 'ml_view' => request('ml_view', 'table')]))->links('pagination::print-resource') }}
                    </div>
                @endif
            </div>{{-- end ml-card-view --}}

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

            <input type="hidden" name="ml_page" value="{{ request('ml_page') }}">
            <input type="hidden" name="ml_search" value="{{ request('ml_search') }}">

            {{-- IMAGE + BASIC INFO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                <div class="h-full">
                    <div class="h-full flex flex-col items-center justify-between border-2 border-dashed border-blue-500 rounded-lg p-4 text-center">
                        <div class="w-full" style="aspect-ratio: 3/3.5;">
                            <img id="imagePreview"
                                 src="{{ $resource->cover_url }}"
                                 data-default-src="{{ $resource->cover_url }}"
                                 alt="Image preview"
                                 class="cover-img w-full h-full object-cover rounded mb-4">
                        </div>
                        <input type="file" name="image" id="imageUpload" class="hidden" accept="image/*">
                        <label for="imageUpload" class="cursor-pointer mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
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

                    @if(in_array($level, [3, 4]))
                        @if($resource->verified)
                            <div class="border border-blue-100 rounded-lg px-4 py-3 bg-blue-50/40">
                                <div class="flex items-start gap-3">
                                    @include('pages.components.verified-badge', ['verified' => true, 'class' => 'w-5 h-5 text-blue-600 shrink-0 mt-0.5'])
                                    <div class="flex-1 min-w-0">
                                        <span class="block text-sm font-medium text-gray-800">Verified learning resource</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">
                                            This LR remains verified. Editing it will be added to the verification history and will not replace the original verifier.
                                        </span>
                                        @if($resource->verified_at)
                                            <span class="block text-xs text-blue-600 mt-1">
                                                First verified on {{ $resource->verified_at->format('M d, Y') }}
                                                @if($resource->verifiedBy)
                                                    by {{ $resource->verifiedBy->firstname }} {{ $resource->verifiedBy->lastname }}
                                                @endif
                                            </span>
                                        @endif

                                        @if(!empty($verificationHistory))
                                            {{-- ── Verification Timeline Accordion ── --}}
                                            <div class="mt-3 border-t border-blue-100 pt-3">
                                                <button type="button"
                                                        onclick="toggleVerificationTimeline(this)"
                                                        class="flex items-center gap-2 text-xs font-semibold text-blue-700 hover:text-blue-900 transition-colors group w-full text-left">
                                                    <svg class="timeline-chevron w-3.5 h-3.5 shrink-0 transition-transform duration-200"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                    <span>Verification Timeline</span>
                                                    <span class="ml-auto font-normal text-blue-500">
                                                        {{ count($verificationHistory) }} record{{ count($verificationHistory) === 1 ? '' : 's' }}
                                                    </span>
                                                </button>

                                                <div class="timeline-panel hidden mt-3 space-y-3">
                                                    @foreach($verificationHistory as $historyItem)
                                                        <div class="rounded-lg border border-blue-100 bg-white px-4 py-3">
                                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-1">
                                                                <div>
                                                                    <p class="text-sm font-semibold text-gray-800">{{ $historyItem['name'] ?? 'Unknown user' }}</p>
                                                                    <p class="text-xs text-gray-500">
                                                                        {{ $historyItem['role'] ?? 'User' }}
                                                                    </p>
                                                                </div>
                                                                <div class="sm:text-right">
                                                                    <p class="text-xs font-semibold text-blue-700">{{ $historyItem['action_label'] ?? 'Verification Action' }}</p>
                                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $historyItem['created_at'] ?? '-' }}</p>
                                                                </div>
                                                            </div>

                                                            @if(!empty($historyItem['comment']))
                                                                <p class="text-sm text-gray-600 mt-2 border-t border-blue-50 pt-2">{{ $historyItem['comment'] }}</p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Comment <span class="text-red-500">*</span>
                                </label>
                                <textarea name="comment"
                                          id="verified-edit-comment"
                                          required
                                          rows="3"
                                          class="w-full border border-gray-300 rounded px-3 py-2"
                                          placeholder="Explain why this verified LR needs to be edited.">{{ old('comment') }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">Required for edits after verification.</p>
                            </div>
                        @else
                            <div class="border border-blue-100 rounded-lg px-4 py-3 bg-blue-50/40">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox"
                                           name="verified"
                                           value="1"
                                           {{ old('verified', false) ? 'checked' : '' }}
                                           class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-800">Verified learning resource</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">
                                            Mark this LR as reviewed and trusted by the SDO librarian or division office.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- SUBJECT-GRADE LEVEL --}}
            @php
                $stages = [
                    'KS1' => ['tab' => 'stage1', 'label' => 'Key Stage 1', 'grades' => [0=>'K',1=>'1',2=>'2',3=>'3']],
                    'KS2' => ['tab' => 'stage2', 'label' => 'Key Stage 2', 'grades' => [4=>'4',5=>'5',6=>'6']],
                    'KS3' => ['tab' => 'jhs',    'label' => 'Junior High',  'grades' => [7=>'7',8=>'8',9=>'9',10=>'10']],
                    'KS4' => ['tab' => 'shs',    'label' => 'Senior High',  'grades' => [11=>'11',12=>'12']],
                ];
                $grouped    = $subjectGradeLevels->groupBy(['key_stage', 'subject_name']);
                $checkedIds = old('subject_grade_levels', $editingSglIds ?? []);
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

            @error('subject_grade_levels')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('subject_grade_levels.*')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

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
                    <p class="text-xs text-gray-400 mt-0.5">Pending requests from all schools. Requests outside your division are view-only.</p>
                </div>
                <div class="flex gap-2">
                    <form method="GET" action="{{ route('masterlist.index') }}" class="flex gap-2">
                        <input type="hidden" name="active_tab" value="tab-requests">
                        <input type="text" name="rq_search" value="{{ request('rq_search') }}"
                            placeholder="Search requests..."
                                class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm 
                                    w-full md:w-[200px] lg:w-[300px] xl:w-[400px] 2xl:w-[500px]
                                    focus:outline-none focus:ring-2 focus:ring-blue-500">
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

            {{-- ── View Toggle Toolbar ── --}}
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <label class="whitespace-nowrap font-medium">Show entries:</label>
                    <select id="rq-per-page-select"
                        class="border border-gray-300 rounded-xl px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        onchange="handlePerPageChange(this, 'tab-requests', 'rq_per_page', 'rq-view-input')">
                        @foreach([5, 10, 15, 20] as $opt)
                            <option value="{{ $opt }}" {{ request('rq_per_page', 10) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Hidden input so JS and per-page onchange can always read the current view --}}
                <input type="hidden" id="rq-view-input" value="{{ request('rq_view', 'table') }}">
                <div class="flex items-center bg-gray-100 p-1 rounded-xl">
                    <button type="button"
                        class="rq-view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5"
                        data-rq-view="card">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="hidden md:inline">Cards</span>
                    </button>
                    <button type="button"
                        class="rq-view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5"
                        data-rq-view="table">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                        </svg>
                        <span class="hidden md:inline">Table</span>
                    </button>
                </div>
            </div>

            {{-- ── TABLE VIEW ── --}}
            <div id="rq-table-view">
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
                            <th class="px-3 py-3 text-left">Origin</th>
                            <th class="px-3 py-3 text-center">Date Submitted</th>
                            <th class="px-3 py-3 text-center">Requested by</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($requests as $req)
                            @php
                                $reqSglIds  = $req->subject_grade_level_ids ? explode(',', $req->subject_grade_level_ids) : [];
                                $reqSglText = '-';
                                if (!empty($reqSglIds)) {
                                    $reqSgls    = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])->whereIn('id', $reqSglIds)->get();
                                    $reqSglText = $reqSgls->map(fn($s) => ($s->subject->subject_name ?? '') . ' - ' . ($s->gradeLevel->grade ?? ''))->join('; ');
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2">
                                    <img src="{{ $req->thumb_url }}" alt="cover"
                                        class="cover-img w-9 h-12 object-cover rounded border border-gray-200 shadow-sm cursor-pointer hover:opacity-80 transition-opacity"
                                        loading="lazy"
                                        onclick="showImageModal('{{ $req->thumb_url }}', '{{ addslashes($req->printTitle->title ?? 'Image') }}')">
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
                                <td class="px-3 py-2 text-gray-600">{{ $req->isbn ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600 text-xs max-w-50">
                                    <span title="{{ $reqSglText }}">{{ Str::limit($reqSglText, 55) }}</span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-600 min-w-56">
                                    <div class="font-medium text-gray-800">{{ $req->request_school_name ?? '-' }}</div>
                                    <div>{{ $req->request_district_name ?? '-' }}</div>
                                    <div>{{ $req->request_division_name ?? '-' }}</div>
                                    <div>{{ $req->request_region_name ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-500 text-xs whitespace-nowrap">{{ $req->created_at?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600 uppercase">
                                    {{ $req->encodedBy ? trim("{$req->encodedBy->firstname} {$req->encodedBy->lastname}") : '-' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        {{-- View --}}
                                        <button type="button"
                                                data-view-id="{{ $req->id }}"
                                                data-cover="{{ $req->cover_url }}"
                                                data-title="{{ $req->printTitle->title ?? '-' }}"
                                                data-authors="{{ $req->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}"
                                                data-type="{{ $req->type->type_name ?? '-' }}"
                                                data-publisher="{{ $req->publisher ?? '-' }}"
                                                data-volume="{{ $req->volume ?? '-' }}"
                                                data-edition="{{ $req->edition ?? '-' }}"
                                                data-copyright="{{ $req->copyright ?? '-' }}"
                                                data-isbn="{{ $req->isbn ?? '-' }}"
                                                data-pages="{{ $req->pages ?? '-' }}"
                                                data-subjects="{{ $reqSglText }}"
                                                class="view-resource-btn inline-flex items-center gap-1 text-xs px-2.5 py-1.5 bg-indigo-50 text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors font-medium whitespace-nowrap">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </button>

                                        @if($req->can_manage_request)
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
                                        @else
                                            <span class="text-xs text-gray-400" title="{{ $req->request_scope_tooltip }}">View only</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-gray-400 py-10">
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
                    {{ $requests->appends(array_filter(['rq_search' => request('rq_search'), 'active_tab' => 'tab-requests', 'rq_per_page' => request('rq_per_page', 10), 'rq_view' => request('rq_view', 'table')]))->links('pagination::print-resource') }}
                </div>
            @endif
            </div>{{-- end rq-table-view --}}

            {{-- ── CARD VIEW ── --}}
            <div id="rq-card-view" class="hidden">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mt-2">
                    @forelse($requests as $req)
                        @php
                            $reqSglIdsC  = $req->subject_grade_level_ids ? explode(',', $req->subject_grade_level_ids) : [];
                            $reqSglTextC = '-';
                            if (!empty($reqSglIdsC)) {
                                $reqSglsC    = \App\Models\SubjectGradeLevel::with(['subject', 'gradeLevel'])->whereIn('id', $reqSglIdsC)->get();
                                $reqSglTextC = $reqSglsC->map(fn($s) => ($s->subject->subject_name ?? '') . ' - ' . ($s->gradeLevel->grade ?? ''))->join('; ');
                            }
                        @endphp
                        <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col group cursor-pointer"
                             onclick="(function(el){ var btn = el.querySelector('.view-resource-btn'); if(btn) btn.click(); })(this)">
                            <div class="relative w-full" style="padding-bottom:140%;">
                                <img src="{{ $req->thumb_url }}" alt="{{ $req->printTitle->title ?? '' }}"
                                     class="cover-img absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                     loading="lazy">
                                <span class="absolute top-2 right-2 inline-flex items-center gap-1 bg-white/90 backdrop-blur-sm text-xs font-semibold px-2 py-0.5 rounded-full shadow text-blue-700">
                                    {{ $req->type->shortname ?? '' }}
                                </span>
                                <span class="absolute bottom-2 left-2 inline-flex items-center gap-1 bg-black/50 text-white text-xs px-2 py-0.5 rounded-full">
                                    {{ $req->created_at?->format('M d, Y') ?? '' }}
                                </span>
                            </div>
                            <div class="p-3 flex flex-col gap-1 flex-1">
                                <h3 class="text-xs font-semibold text-gray-900 leading-tight line-clamp-2">{{ $req->printTitle->title ?? '-' }}</h3>
                                <p class="text-xs text-gray-500 truncate">{{ $req->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}</p>
                                <p class="text-xs text-gray-400 truncate">by {{ $req->encodedBy ? trim("{$req->encodedBy->firstname} {$req->encodedBy->lastname}") : '-' }}</p>
                                <p class="text-[11px] text-gray-500 leading-snug">
                                    {{ $req->request_school_name ?? '-' }}<br>
                                    {{ $req->request_division_name ?? '-' }}
                                </p>
                                <div class="mt-auto pt-2 flex items-center justify-between gap-1 flex-wrap">
                                    {{-- hidden view btn so card click triggers it --}}
                                    <button type="button"
                                            data-view-id="{{ $req->id }}"
                                            data-cover="{{ $req->cover_url }}"
                                            data-title="{{ $req->printTitle->title ?? '-' }}"
                                            data-authors="{{ $req->printTitle->authors->pluck('author_name')->join(', ') ?: '-' }}"
                                            data-type="{{ $req->type->type_name ?? '-' }}"
                                            data-publisher="{{ $req->publisher ?? '-' }}"
                                            data-volume="{{ $req->volume ?? '-' }}"
                                            data-edition="{{ $req->edition ?? '-' }}"
                                            data-copyright="{{ $req->copyright ?? '-' }}"
                                            data-isbn="{{ $req->isbn ?? '-' }}"
                                            data-pages="{{ $req->pages ?? '-' }}"
                                            data-subjects="{{ $reqSglTextC }}"
                                            class="view-resource-btn hidden">
                                    </button>
                                    @if($req->can_manage_request)
                                        <form action="{{ route('masterlist.approve', $req->id) }}" method="POST"
                                              onsubmit="event.stopPropagation(); return confirm('Approve this resource request?')"
                                              onclick="event.stopPropagation()">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 font-medium">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('masterlist.reject', $req->id) }}" method="POST"
                                              onsubmit="event.stopPropagation(); return confirm('Reject this request?')"
                                              onclick="event.stopPropagation()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 font-medium">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Reject
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400" title="{{ $req->request_scope_tooltip }}">View only</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center text-gray-400 py-10">
                            <p class="text-sm font-medium">No pending requests.</p>
                        </div>
                    @endforelse
                </div>
                @if($requests && $requests->hasPages())
                    <div class="mt-4">
                        {{ $requests->appends(array_filter(['rq_search' => request('rq_search'), 'active_tab' => 'tab-requests', 'rq_per_page' => request('rq_per_page', 10), 'rq_view' => request('rq_view', 'table')]))->links('pagination::print-resource') }}
                    </div>
                @endif
            </div>{{-- end rq-card-view --}}

        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="hideImageModal()">
        <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
            <button onclick="hideImageModal()" 
                    class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors text-3xl font-bold">
                &times;
            </button>
            <img id="modalImage" src="" alt="Full size cover" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl">
            <div id="modalCaption" class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-center py-2 px-4 rounded-b-lg">
            </div>
        </div>
    </div>

    <style>
    /* Optional: Add fade animation */
    #imageModal {
        transition: opacity 0.3s ease;
    }
    #imageModal:not(.hidden) {
        animation: fadeIn 0.2s ease-out;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    </style>
    @endif

    {{-- ===== VIEW RESOURCE MODAL ===== --}}
    <div id="viewResourceModal" class="fixed inset-0 z-50 hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div id="viewModalBackdrop" class="fixed inset-0 bg-black/50 transition-opacity"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4 pt-10 pb-10">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-auto z-10 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-gray-200 bg-white sticky top-0 z-20">
                    <h3 class="text-lg font-semibold text-gray-800">Resource Details</h3>
                    <button id="closeViewModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-2 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-5 sm:p-6 max-h-[calc(100vh-140px)] overflow-y-auto">
                    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start">

                        {{-- LEFT SIDE (Image) --}}
                        <div class="w-full lg:w-auto lg:shrink-0 flex justify-center lg:justify-start">
                            <div class="w-full max-w-[290px] lg:w-[290px]">
                                <img id="vm-cover" src="" alt="Cover"
                                    class="w-full max-h-[420px] lg:max-h-[460px] object-contain rounded-xl border border-gray-200 shadow-sm bg-gray-100">
                            </div>
                        </div>

                        {{-- RIGHT SIDE (Details) --}}
                        <div class="flex-1 min-w-0 flex flex-col gap-4 w-full">

                            {{-- Title & Authors --}}
                            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-4">
                                <div class="flex items-start gap-2">
                                    <span id="vm-verified-badge" class="hidden mt-1 shrink-0">
                                        @include('pages.components.verified-badge', ['verified' => true, 'class' => 'w-5 h-5 text-blue-600'])
                                    </span>
                                    <div class="min-w-0">
                                        <h4 id="vm-title" class="text-lg sm:text-xl font-bold text-gray-900 leading-tight"></h4>
                                        <p id="vm-authors" class="text-sm text-gray-500 mt-1"></p>
                                        <p id="vm-verified-note" class="hidden text-xs text-blue-600 mt-1 font-medium"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Type Badge --}}
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Type</span>
                                <span id="vm-type-badge"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                </span>
                            </div>

                            {{-- Metadata Grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">Publisher</p>
                                    <p id="vm-publisher" class="text-sm font-medium text-gray-700"></p>
                                </div>
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">Copyright</p>
                                    <p id="vm-copyright" class="text-sm font-medium text-gray-700"></p>
                                </div>
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">Edition</p>
                                    <p id="vm-edition" class="text-sm font-medium text-gray-700"></p>
                                </div>
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">Volume</p>
                                    <p id="vm-volume" class="text-sm font-medium text-gray-700"></p>
                                </div>
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">ISBN</p>
                                    <p id="vm-isbn" class="text-sm font-medium text-gray-700 font-mono tracking-wider break-all"></p>
                                </div>
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-1">Pages</p>
                                    <p id="vm-pages" class="text-sm font-medium text-gray-700"></p>
                                </div>
                            </div>

                            {{-- Subjects --}}
                            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Subjects / Grade Levels</p>
                                <p id="vm-subjects" class="text-sm text-gray-600 leading-relaxed"></p>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-5 sm:px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <a id="viewModalEditBtn"
                       href="#"
                       class="hidden inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                        </svg>
                        Edit
                    </a>
                    <button id="closeViewModalFooter"
                            class="px-6 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@include('pages.partials.resource-loading-skeleton')

{{-- Seed edit-mode authors for JS --}}
@if($isEditing)
<script>window.__editAuthors = @json($editingAuthors ?? []);</script>
@endif

<script>
    (function () {
    const pageTabBtns = document.querySelectorAll('.page-tab-btn');
    const isEditing   = {{ $isEditing ? 'true' : 'false' }};

    function activatePageTab(targetId) {
        if (isEditing && targetId !== 'tab-edit') {
            window.location.href = '{{ route('masterlist.index') }}';
            return;
        }

        const liveContents = document.querySelectorAll('.page-tab-content');
        const validIds = Array.from(liveContents).map(c => c.id);
        if (!validIds.includes(targetId)) targetId = validIds[0] ?? 'tab-masterlist';

        pageTabBtns.forEach(btn => {
            const isActive = btn.dataset.pageTab === targetId;
            btn.classList.toggle('border-blue-600',    isActive);
            btn.classList.toggle('text-blue-600',      isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-500',      !isActive);
        });
        liveContents.forEach(c => c.classList.toggle('hidden', c.id !== targetId));
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

    // ── IMAGE CROPPER + PREVIEW ──────────────────────────────────────────
    (function () {

        class ImageCropperModal {
            constructor() {
                this._modal = null; this._canvas = null; this._ctx = null;
                this._image = null; this._originalFile = null; this._resolvePromise = null;
                this._zoom = 1; this._panX = 0; this._panY = 0;
                this._crop = null;
                this._mode = 'idle'; this._resizeHandle = null;
                this._saved = {}; this._drawStart = null;
                this._canvasW = 0; this._canvasH = 0;
                this._injectStyles();
                this._buildModal();
            }
            open(file) {
                return new Promise(resolve => {
                    this._resolvePromise = resolve;
                    this._originalFile  = file;
                    this._loadFile(file);
                });
            }
            _buildModal() {
                const el = document.createElement('div');
                el.id = 'icpModal'; el.className = 'icp-overlay hidden';
                el.innerHTML = `
                    <div class="icp-dialog">
                        <div class="icp-header">
                            <div class="icp-header-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                                Crop &amp; Compress Image
                            </div>
                            <button type="button" id="icpCloseBtn" class="icp-close-btn" title="Cancel">&times;</button>
                        </div>
                        <div class="icp-hint">
                            <span>🔍 <b>Scroll / buttons</b> to zoom</span>
                            <span>✋ <b>Drag image</b> to pan</span>
                            <span>✂️ <b>Drag on image</b> to draw crop</span>
                            <span>↔️ <b>Drag handles</b> to resize</span>
                        </div>
                        <div class="icp-canvas-wrap"><canvas id="icpCanvas"></canvas></div>
                        <div class="icp-footer">
                            <div class="icp-zoom-bar">
                                <button type="button" id="icpZoomOut" title="Zoom out"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg></button>
                                <span id="icpZoomLabel">100%</span>
                                <button type="button" id="icpZoomIn" title="Zoom in"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg></button>
                                <button type="button" id="icpZoomFit" class="icp-fit-btn">Fit</button>
                            </div>
                            <div class="icp-info">
                                <span id="icpDimInfo">Draw a crop area on the image</span>
                                <span id="icpSizeInfo"></span>
                            </div>
                            <div class="icp-actions">
                                <button type="button" id="icpCancelBtn"  class="icp-btn-secondary">Cancel</button>
                                <button type="button" id="icpResetBtn"   class="icp-btn-secondary"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.3"/></svg> Reset</button>
                                <button type="button" id="icpApplyBtn"   class="icp-btn-primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Apply </button>
                            </div>
                        </div>
                    </div>`;
                document.body.appendChild(el);
                this._modal  = el;
                this._canvas = document.getElementById('icpCanvas');
                this._ctx    = this._canvas.getContext('2d');
                document.getElementById('icpCloseBtn').addEventListener('click',  () => this._cancel());
                document.getElementById('icpCancelBtn').addEventListener('click', () => this._cancel());
                document.getElementById('icpResetBtn').addEventListener('click',  () => { this._crop = null; this._draw(); this._updateInfo(); });
                document.getElementById('icpApplyBtn').addEventListener('click',  () => this._apply());
                document.getElementById('icpZoomIn').addEventListener('click',    () => this._zoomBy(0.15));
                document.getElementById('icpZoomOut').addEventListener('click',   () => this._zoomBy(-0.15));
                document.getElementById('icpZoomFit').addEventListener('click',   () => this._fitImage());
                this._canvas.addEventListener('mousedown',  e => this._onDown(e));
                this._canvas.addEventListener('mousemove',  e => this._onMove(e));
                this._canvas.addEventListener('mouseup',    () => this._onUp());
                this._canvas.addEventListener('mouseleave', () => this._onUp());
                this._canvas.addEventListener('wheel',      e => this._onWheel(e), { passive: false });
                this._canvas.addEventListener('touchstart', e => this._onDown(e), { passive: false });
                this._canvas.addEventListener('touchmove',  e => this._onMove(e), { passive: false });
                this._canvas.addEventListener('touchend',   () => this._onUp());
            }
            _loadFile(file) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = new Image();
                    img.onload = () => { this._image = img; this._crop = null; this._setupCanvas(); this._fitImage(); this._show(); };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            _setupCanvas() {
                const maxW = Math.min(780, window.innerWidth - 48);
                const maxH = Math.min(500, window.innerHeight - 230);
                this._canvasW = this._canvas.width  = maxW;
                this._canvasH = this._canvas.height = maxH;
            }
            _fitImage() {
                const img = this._image;
                const scaleW = this._canvasW / img.naturalWidth;
                const scaleH = this._canvasH / img.naturalHeight;
                this._zoom = Math.min(scaleW, scaleH, 1);
                this._panX = (this._canvasW - img.naturalWidth  * this._zoom) / 2;
                this._panY = (this._canvasH - img.naturalHeight * this._zoom) / 2;
                this._clampPan(); this._draw(); this._updateZoomLabel();
            }
            _zoomBy(delta, pivotX, pivotY) {
                const oldZoom = this._zoom;
                const newZoom = Math.min(10, Math.max(0.05, oldZoom + delta));
                if (newZoom === oldZoom) return;
                const px = pivotX ?? this._canvasW / 2;
                const py = pivotY ?? this._canvasH / 2;
                this._panX = px - (px - this._panX) * (newZoom / oldZoom);
                this._panY = py - (py - this._panY) * (newZoom / oldZoom);
                this._zoom = newZoom;
                this._clampPan(); this._draw(); this._updateZoomLabel();
            }
            _clampPan() {
                const imgW = this._image.naturalWidth  * this._zoom;
                const imgH = this._image.naturalHeight * this._zoom;
                const m = 50;
                this._panX = Math.max(m - imgW, Math.min(this._canvasW - m, this._panX));
                this._panY = Math.max(m - imgH, Math.min(this._canvasH - m, this._panY));
            }
            _updateZoomLabel() {
                const el = document.getElementById('icpZoomLabel');
                if (el) el.textContent = Math.round(this._zoom * 100) + '%';
            }
            _toImage(cx, cy) { return { x: (cx - this._panX) / this._zoom, y: (cy - this._panY) / this._zoom }; }
            _draw() {
                const ctx = this._ctx, cw = this._canvasW, ch = this._canvasH;
                ctx.clearRect(0, 0, cw, ch);
                this._drawCheckerboard();
                ctx.save(); ctx.translate(this._panX, this._panY); ctx.scale(this._zoom, this._zoom);
                ctx.drawImage(this._image, 0, 0); ctx.restore();
                if (!this._crop) return;
                const { x, y, w, h } = this._crop;
                ctx.fillStyle = 'rgba(0,0,0,0.55)'; ctx.fillRect(0, 0, cw, ch);
                ctx.save(); ctx.beginPath(); ctx.rect(x, y, w, h); ctx.clip();
                ctx.translate(this._panX, this._panY); ctx.scale(this._zoom, this._zoom);
                ctx.drawImage(this._image, 0, 0); ctx.restore();
                ctx.strokeStyle = '#3B82F6'; ctx.lineWidth = 1.5; ctx.strokeRect(x + 0.5, y + 0.5, w, h);
                ctx.strokeStyle = 'rgba(255,255,255,0.28)'; ctx.lineWidth = 1;
                for (let i = 1; i <= 2; i++) {
                    ctx.beginPath(); ctx.moveTo(x + w/3*i, y);   ctx.lineTo(x + w/3*i, y+h); ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(x, y + h/3*i);   ctx.lineTo(x+w, y + h/3*i); ctx.stroke();
                }
                const HS = 7;
                this._handles(x, y, w, h).forEach(({ hx, hy }) => {
                    ctx.fillStyle = '#fff'; ctx.strokeStyle = '#3B82F6'; ctx.lineWidth = 1.5;
                    ctx.fillRect(hx - HS/2, hy - HS/2, HS, HS); ctx.strokeRect(hx - HS/2, hy - HS/2, HS, HS);
                });
            }
            _drawCheckerboard() {
                const ctx = this._ctx, s = 14;
                for (let r = 0; r * s < this._canvasH; r++)
                    for (let c = 0; c * s < this._canvasW; c++) {
                        ctx.fillStyle = (r + c) % 2 === 0 ? '#c8cdd4' : '#e2e5ea';
                        ctx.fillRect(c*s, r*s, s, s);
                    }
            }
            _handles(x, y, w, h) {
                return [
                    {id:'nw',hx:x,    hy:y    },{id:'n', hx:x+w/2,hy:y    },{id:'ne',hx:x+w,hy:y    },
                    {id:'e', hx:x+w,  hy:y+h/2},{id:'se',hx:x+w,  hy:y+h  },{id:'s', hx:x+w/2,hy:y+h},
                    {id:'sw',hx:x,    hy:y+h  },{id:'w', hx:x,    hy:y+h/2},
                ];
            }
            _hitHandle(mx, my) {
                if (!this._crop) return null;
                const { x, y, w, h } = this._crop, tol = 10;
                for (const { id, hx, hy } of this._handles(x, y, w, h))
                    if (Math.abs(mx - hx) <= tol && Math.abs(my - hy) <= tol) return id;
                return null;
            }
            _insideCrop(mx, my) {
                if (!this._crop) return false;
                const { x, y, w, h } = this._crop;
                return mx > x+8 && mx < x+w-8 && my > y+8 && my < y+h-8;
            }
            _insideImage(mx, my) {
                return mx >= this._panX && mx <= this._panX + this._image.naturalWidth  * this._zoom &&
                       my >= this._panY && my <= this._panY + this._image.naturalHeight * this._zoom;
            }
            _getPos(e) {
                const rect = this._canvas.getBoundingClientRect();
                const src  = e.touches ? e.touches[0] : e;
                return { x: src.clientX - rect.left, y: src.clientY - rect.top };
            }
            _onDown(e) {
                if (e.type === 'touchstart') e.preventDefault();
                const pos = this._getPos(e);
                const handle = this._hitHandle(pos.x, pos.y);
                if (handle) { this._mode = 'resizing'; this._resizeHandle = handle; this._saved = { ...pos, crop: { ...this._crop } }; return; }
                if (this._insideCrop(pos.x, pos.y)) { this._mode = 'moving'; this._saved = { ...pos, crop: { ...this._crop } }; return; }
                if (this._insideImage(pos.x, pos.y)) { this._mode = 'drawing'; this._drawStart = { ...pos }; this._crop = null; return; }
                this._mode = 'panning'; this._saved = { ...pos, panX: this._panX, panY: this._panY };
            }
            _onMove(e) {
                if (e.type === 'touchmove') e.preventDefault();
                const pos = this._getPos(e);
                this._updateCursor(pos);
                const dx = pos.x - (this._saved.x ?? pos.x);
                const dy = pos.y - (this._saved.y ?? pos.y);
                switch (this._mode) {
                    case 'panning':
                        this._panX = this._saved.panX + dx; this._panY = this._saved.panY + dy;
                        this._clampPan(); this._draw(); break;
                    case 'drawing': {
                        const ds = this._drawStart;
                        const x = Math.min(ds.x, pos.x), y = Math.min(ds.y, pos.y);
                        const w = Math.abs(pos.x - ds.x), h = Math.abs(pos.y - ds.y);
                        if (w > 5 || h > 5) this._crop = { x, y, w, h };
                        this._draw(); this._updateInfo(); break;
                    }
                    case 'moving': {
                        const { w, h } = this._saved.crop;
                        const cw = this._canvasW, ch = this._canvasH;
                        let nx = Math.max(0, Math.min(this._saved.crop.x + dx, cw - w));
                        let ny = Math.max(0, Math.min(this._saved.crop.y + dy, ch - h));
                        this._crop = { x: nx, y: ny, w, h };
                        this._draw(); this._updateInfo(); break;
                    }
                    case 'resizing': this._doResize(pos); break;
                }
            }
            _onUp() { this._mode = 'idle'; this._resizeHandle = null; }
            _onWheel(e) {
                e.preventDefault();
                const pos = this._getPos(e);
                this._zoomBy(e.deltaY < 0 ? 0.14 : -0.14, pos.x, pos.y);
            }
            _updateCursor(pos) {
                const handle = this._hitHandle(pos.x, pos.y);
                if (handle) {
                    const map = { nw:'nwse-resize',se:'nwse-resize',ne:'nesw-resize',sw:'nesw-resize',n:'ns-resize',s:'ns-resize',e:'ew-resize',w:'ew-resize' };
                    this._canvas.style.cursor = map[handle] || 'pointer';
                } else if (this._insideCrop(pos.x, pos.y)) {
                    this._canvas.style.cursor = 'move';
                } else if (this._insideImage(pos.x, pos.y)) {
                    this._canvas.style.cursor = this._mode === 'panning' ? 'grabbing' : 'crosshair';
                } else {
                    this._canvas.style.cursor = this._mode === 'panning' ? 'grabbing' : 'grab';
                }
            }
            _doResize(pos) {
                const MIN = 20, cw = this._canvasW, ch = this._canvasH;
                const dx = pos.x - this._saved.x, dy = pos.y - this._saved.y;
                let { x, y, w, h } = this._saved.crop;
                const id = this._resizeHandle;
                if (id.includes('e')) w = Math.max(MIN, Math.min(cw - x, w + dx));
                if (id.includes('s')) h = Math.max(MIN, Math.min(ch - y, h + dy));
                if (id.includes('w')) { const nx = Math.max(0, Math.min(x + w - MIN, x + dx)); w += x - nx; x = nx; }
                if (id.includes('n')) { const ny = Math.max(0, Math.min(y + h - MIN, y + dy)); h += y - ny; y = ny; }
                this._crop = { x, y, w, h }; this._draw(); this._updateInfo();
            }
            _updateInfo() {
                const el = document.getElementById('icpDimInfo');
                if (!el) return;
                if (!this._crop) { el.textContent = 'Draw a crop area on the image'; return; }
                const { w, h } = this._crop;
                el.textContent = `Crop: ${Math.round(w / this._zoom)} × ${Math.round(h / this._zoom)} px`;
            }
            async _apply() {
                const btn = document.getElementById('icpApplyBtn');
                btn.disabled = true; btn.innerHTML = '<span class="icp-spinner"></span> Processing…';
                try {
                    const file = await this._cropAndCompress();
                    const sizeEl = document.getElementById('icpSizeInfo');
                    if (sizeEl) sizeEl.textContent = `Output: ${(file.size / 1024).toFixed(1)} KB`;
                    this._hide(); this._resolvePromise(file);
                } catch (err) {
                    console.error(err); alert('Failed to process the image. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Apply & Compress`;
                }
            }
            async _cropAndCompress() {
                const img = this._image;
                let natX, natY, natW, natH;
                if (this._crop) {
                    const { x, y, w, h } = this._crop;
                    const tl = this._toImage(x, y), br = this._toImage(x + w, y + h);
                    natX = Math.max(0, Math.round(tl.x)); natY = Math.max(0, Math.round(tl.y));
                    natW = Math.min(img.naturalWidth  - natX, Math.round(br.x - tl.x));
                    natH = Math.min(img.naturalHeight - natY, Math.round(br.y - tl.y));
                } else {
                    natX = 0; natY = 0; natW = img.naturalWidth; natH = img.naturalHeight;
                }
                const off = document.createElement('canvas');
                off.width = natW; off.height = natH;
                off.getContext('2d').drawImage(img, natX, natY, natW, natH, 0, 0, natW, natH);
                const TARGET = 100 * 1024;
                const baseName = (this._originalFile?.name || 'image.jpg').replace(/\.[^.]+$/, '');
                const toBlob = (canvas, type, quality) =>
                    new Promise((res, rej) => canvas.toBlob(b => b ? res(b) : rej(new Error('toBlob failed')), type, quality));
                let quality = 0.92, blob;
                while (quality >= 0.05) {
                    blob = await toBlob(off, 'image/jpeg', quality);
                    if (blob.size <= TARGET) break;
                    quality = parseFloat((quality - 0.05).toFixed(2));
                }
                if (blob.size > TARGET) {
                    let scale = 0.9;
                    while (scale >= 0.2) {
                        const sc = document.createElement('canvas');
                        sc.width  = Math.round(natW * scale);
                        sc.height = Math.round(natH * scale);
                        sc.getContext('2d').drawImage(off, 0, 0, sc.width, sc.height);
                        blob = await toBlob(sc, 'image/jpeg', 0.85);
                        if (blob.size <= TARGET) break;
                        scale = parseFloat((scale - 0.1).toFixed(2));
                    }
                }
                return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' });
            }
            _cancel() { this._hide(); this._resolvePromise(null); }
            _show() { this._modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
            _hide() {
                this._modal.classList.add('hidden'); document.body.style.overflow = '';
                const si = document.getElementById('icpSizeInfo'), di = document.getElementById('icpDimInfo');
                if (si) si.textContent = '';
                if (di) di.textContent = 'Draw a crop area on the image';
            }
            _injectStyles() {
                if (document.getElementById('icpStyles')) return;
                const s = document.createElement('style'); s.id = 'icpStyles';
                s.textContent = `
                    .icp-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.82);display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;}
                    .icp-overlay.hidden{display:none!important;}
                    .icp-dialog{background:#fff;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:12px;max-width:840px;width:100%;box-shadow:0 32px 100px rgba(0,0,0,0.55);box-sizing:border-box;}
                    .icp-header{display:flex;align-items:center;justify-content:space-between;}
                    .icp-header-title{display:flex;align-items:center;gap:8px;font-weight:700;font-size:1rem;color:#0f172a;}
                    .icp-close-btn{font-size:1.7rem;line-height:1;background:none;border:none;cursor:pointer;color:#94a3b8;padding:0 4px;transition:color .15s;}
                    .icp-close-btn:hover{color:#ef4444;}
                    .icp-hint{display:flex;flex-wrap:wrap;gap:8px 16px;font-size:0.73rem;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;padding:7px 14px;border-radius:8px;}
                    .icp-canvas-wrap{background:#0f172a;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;}
                    #icpCanvas{display:block;cursor:crosshair;user-select:none;-webkit-user-select:none;touch-action:none;}
                    .icp-footer{display:flex;flex-wrap:wrap;align-items:center;gap:10px;justify-content:space-between;}
                    .icp-zoom-bar{display:flex;align-items:center;gap:6px;}
                    .icp-zoom-bar button{width:30px;height:30px;border-radius:7px;border:1px solid #cbd5e1;background:#f1f5f9;cursor:pointer;color:#334155;display:flex;align-items:center;justify-content:center;transition:background .15s;}
                    .icp-zoom-bar button:hover{background:#dbeafe;border-color:#93c5fd;color:#1d4ed8;}
                    .icp-fit-btn{width:auto!important;padding:0 12px;font-size:0.78rem;font-weight:600;}
                    #icpZoomLabel{min-width:46px;text-align:center;font-size:0.82rem;color:#475569;font-variant-numeric:tabular-nums;font-weight:600;}
                    .icp-info{display:flex;gap:14px;font-size:0.79rem;color:#64748b;flex:1;justify-content:center;}
                    .icp-actions{display:flex;gap:8px;align-items:center;}
                    .icp-btn-primary{display:inline-flex;align-items:center;gap:6px;background:#3b82f6;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:0.86rem;font-weight:600;transition:background .15s,box-shadow .15s;box-shadow:0 2px 8px rgba(59,130,246,0.35);}
                    .icp-btn-primary:hover:not(:disabled){background:#2563eb;}
                    .icp-btn-primary:disabled{opacity:.55;cursor:not-allowed;}
                    .icp-btn-secondary{display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:8px 14px;border-radius:8px;cursor:pointer;font-size:0.86rem;transition:background .15s;}
                    .icp-btn-secondary:hover{background:#e2e8f0;}
                    .icp-spinner{display:inline-block;width:13px;height:13px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:icpSpin 0.7s linear infinite;}
                    @keyframes icpSpin{to{transform:rotate(360deg);}}
                    @media(max-width:540px){.icp-dialog{padding:12px;gap:10px;}.icp-footer{flex-direction:column;align-items:stretch;}.icp-actions{flex-wrap:wrap;}.icp-actions button{flex:1;}.icp-info{justify-content:flex-start;}.icp-hint span{display:block;}}
                `;
                document.head.appendChild(s);
            }
        }

        let _cropperInstance = null;
        function getCropper() {
            if (!_cropperInstance) _cropperInstance = new ImageCropperModal();
            return _cropperInstance;
        }

        function setupImagePreview(uploadId, previewId) {
            const imageUpload  = document.getElementById(uploadId);
            const imagePreview = document.getElementById(previewId);
            if (!imageUpload || !imagePreview) return;
            if (!imagePreview.dataset.defaultSrc)
                imagePreview.dataset.defaultSrc = imagePreview.src || '';
            imageUpload.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (!file) return;
                const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowed.includes(file.type)) {
                    alert('Please select a valid image file (JPEG or PNG).');
                    imageUpload.value = ''; imagePreview.src = imagePreview.dataset.defaultSrc || ''; return;
                }
                if (file.size > 20 * 1024 * 1024) {
                    alert('File is too large (max 20 MB before compression).');
                    imageUpload.value = ''; imagePreview.src = imagePreview.dataset.defaultSrc || ''; return;
                }
                const croppedFile = await getCropper().open(file);
                if (!croppedFile) {
                    imageUpload.value = ''; imagePreview.src = imagePreview.dataset.defaultSrc || ''; return;
                }
                try {
                    const dt = new DataTransfer();
                    dt.items.add(croppedFile);
                    imageUpload.files = dt.files;
                } catch { /* fallback */ }
                const reader = new FileReader();
                reader.onload = e => { imagePreview.src = e.target.result; };
                reader.readAsDataURL(croppedFile);
            });
        }

        setupImagePreview('imageUpload', 'imagePreview');

    })();

    // ── VIEW RESOURCE MODAL ──────────────────────────────────────────────
    const viewModal       = document.getElementById('viewResourceModal');
    const viewBackdrop    = document.getElementById('viewModalBackdrop');
    const closeViewBtn    = document.getElementById('closeViewModal');
    const closeViewFooter = document.getElementById('closeViewModalFooter');

    function parseVerificationHistory(raw) {
        if (!raw) return [];
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function verifierLabel(name, role = '') {
        const roleText = role || 'User';
        return `${name} (${roleText})`;
    }

    function verificationCaption(history, currentName = '', currentRole = '', currentLevel = '', fallbackName = '', fallbackRole = '', fallbackLevel = '') {
        if (currentName) {
            return `Verified by ${verifierLabel(currentName, currentRole || 'User')}`;
        }

        const latestReverification = [...history]
            .reverse()
            .find(item => item.action_type && item.action_type !== 'first_verification');

        if (latestReverification) {
            return `Verified by ${verifierLabel(
                latestReverification.name || 'Unknown user',
                latestReverification.role || 'User'
            )}`;
        }

        const firstVerification = history.find(item => item.action_type === 'first_verification') || history[0];
        if (firstVerification) {
            return `Verified by ${verifierLabel(
                firstVerification.name || 'Unknown user',
                firstVerification.role || 'User'
            )}`;
        }

        if (fallbackName) {
            return `Verified by ${verifierLabel(fallbackName, fallbackRole || 'User')}`;
        }

        return 'Verified learning resource';
    }

    function openViewModal(btn) {
        document.getElementById('vm-cover').src              = btn.dataset.cover;
        document.getElementById('vm-title').textContent      = btn.dataset.title;
        document.getElementById('vm-authors').textContent    = btn.dataset.authors !== '-' ? btn.dataset.authors : '';
        document.getElementById('vm-type-badge').textContent = btn.dataset.type;
        document.getElementById('vm-publisher').textContent  = btn.dataset.publisher;
        document.getElementById('vm-copyright').textContent  = btn.dataset.copyright;
        document.getElementById('vm-edition').textContent    = btn.dataset.edition;
        document.getElementById('vm-volume').textContent     = btn.dataset.volume;
        document.getElementById('vm-isbn').textContent       = btn.dataset.isbn;
        document.getElementById('vm-pages').textContent      = btn.dataset.pages;
        document.getElementById('vm-subjects').textContent   = btn.dataset.subjects;

        // ── Edit button in view modal ──
        const viewEditBtn = document.getElementById('viewModalEditBtn');
        if (viewEditBtn && btn.dataset.viewId) {
            viewEditBtn.href = '/print-masterlist/' + encodeURIComponent(btn.dataset.viewId) + '/edit';
            viewEditBtn.classList.remove('hidden');
        } else if (viewEditBtn) {
            viewEditBtn.classList.add('hidden');
        }

        const isVerified = btn.dataset.verified === '1';
        const history = parseVerificationHistory(btn.dataset.verificationHistory);
        const verifiedNote = document.getElementById('vm-verified-note');

        document.getElementById('vm-verified-badge').classList.toggle('hidden', !isVerified);
        verifiedNote.classList.toggle('hidden', !isVerified);
        verifiedNote.textContent = isVerified
            ? verificationCaption(
                history,
                btn.dataset.currentVerifierName || '',
                btn.dataset.currentVerifierRole || '',
                btn.dataset.currentVerifierLevel || '',
                btn.dataset.verifiedByName || '',
                btn.dataset.verifiedByRole || '',
                btn.dataset.verifiedByLevel || ''
            )
            : '';

        viewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModalFn() {
        viewModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function attachViewBtnListeners() {
        document.querySelectorAll('.view-resource-btn').forEach(btn => {
            if (btn.dataset.viewBound) return;
            btn.dataset.viewBound = '1';
            btn.addEventListener('click', () => openViewModal(btn));
        });
    }

    attachViewBtnListeners();
    // Initial setup for view toggles
    setupMasterlistViewToggle();
    setupRequestsViewToggle();

    // ── MASTERLIST VIEW TOGGLE ───────────────────────────────────────────
    function setupMasterlistViewToggle() {
        const ML_KEY = 'print-masterlist-view';
        const mlInput = document.getElementById('ml-view-input');
        if (!mlInput) return;

        function applyMlView(view, persist = true) {
            const tableEl = document.getElementById('ml-table-view');
            const cardEl  = document.getElementById('ml-card-view');
            if (!tableEl || !cardEl) return;

            // Pure client-side switch - no reload
            if (view === 'card') {
                tableEl.classList.add('hidden');
                cardEl.classList.remove('hidden');
            } else {
                cardEl.classList.add('hidden');
                tableEl.classList.remove('hidden');
            }

            // Update button styles
            document.querySelectorAll('.ml-view-toggle-btn').forEach(btn => {
                const isActive = btn.getAttribute('data-ml-view') === view;
                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('shadow', isActive);
                btn.classList.toggle('text-blue-600', isActive);
                btn.classList.toggle('text-gray-500', !isActive);
                btn.classList.toggle('hover:text-gray-700', !isActive);
            });

            if (mlInput) mlInput.value = view;

            // Update URL quietly (for pagination consistency)
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('ml_view', view);
                history.replaceState(null, '', url.toString());
            } catch(e) {}

            if (persist) {
                try { localStorage.setItem(ML_KEY, view); } catch(e) {}
            }

            attachViewBtnListeners();
        }

        // Re-bind listeners every time (important after AJAX)
        document.querySelectorAll('.ml-view-toggle-btn').forEach(btn => {
            btn.removeEventListener('click', btn._mlHandler);
            btn._mlHandler = () => applyMlView(btn.getAttribute('data-ml-view'));
            btn.addEventListener('click', btn._mlHandler);
        });

        // Initial apply
        let initialView = 'table';
        const fromUrl = mlInput.value;
        try { initialView = localStorage.getItem(ML_KEY) || 'table'; } catch(e) {}
        if (fromUrl && ['table','card'].includes(fromUrl)) initialView = fromUrl;

        applyMlView(initialView, false);
    }

    // ── REQUESTS VIEW TOGGLE ─────────────────────────────────────────────
    function setupRequestsViewToggle() {
        const RQ_KEY = 'print-masterlist-rq-view';
        const rqInput = document.getElementById('rq-view-input');
        if (!rqInput) return;

        function applyRqView(view, persist = true) {
            const tableEl = document.getElementById('rq-table-view');
            const cardEl  = document.getElementById('rq-card-view');
            if (!tableEl || !cardEl) return;

            if (view === 'card') {
                tableEl.classList.add('hidden');
                cardEl.classList.remove('hidden');
            } else {
                cardEl.classList.add('hidden');
                tableEl.classList.remove('hidden');
            }

            document.querySelectorAll('.rq-view-toggle-btn').forEach(btn => {
                const isActive = btn.getAttribute('data-rq-view') === view;
                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('shadow', isActive);
                btn.classList.toggle('text-blue-600', isActive);
                btn.classList.toggle('text-gray-500', !isActive);
                btn.classList.toggle('hover:text-gray-700', !isActive);
            });

            if (rqInput) rqInput.value = view;

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('rq_view', view);
                history.replaceState(null, '', url.toString());
            } catch(e) {}

            if (persist) {
                try { localStorage.setItem(RQ_KEY, view); } catch(e) {}
            }

            attachViewBtnListeners();
        }

        document.querySelectorAll('.rq-view-toggle-btn').forEach(btn => {
            btn.removeEventListener('click', btn._rqHandler);
            btn._rqHandler = () => applyRqView(btn.getAttribute('data-rq-view'));
            btn.addEventListener('click', btn._rqHandler);
        });

        let initialView = 'table';
        const params = new URLSearchParams(window.location.search);
        const fromUrl = params.get('rq_view');
        if (fromUrl && ['table','card'].includes(fromUrl)) initialView = fromUrl;

        applyRqView(initialView, false);
    }

    // ── DELETE CONFIRMATION ──────────────────────────────────────────────
    function attachDeleteListeners() {
        document.querySelectorAll('[data-delete-btn]').forEach(btn => {
            if (btn.dataset.deleteBound) return;
            btn.dataset.deleteBound = '1';
            btn.addEventListener('click', function () {
                const form  = btn.closest('[data-delete-form]');
                const title = form?.dataset.title ?? 'this resource';
                if (confirm(`Are you sure you want to delete "${title}"?\n\nThis will permanently remove the resource and its cover image. The title and authors will NOT be deleted.`)) {
                    form.submit();
                }
            });
        });
    }
    attachDeleteListeners();

    closeViewBtn    && closeViewBtn.addEventListener('click', closeViewModalFn);
    closeViewFooter && closeViewFooter.addEventListener('click', closeViewModalFn);
    viewBackdrop    && viewBackdrop.addEventListener('click', closeViewModalFn);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !viewModal.classList.contains('hidden')) closeViewModalFn();
    });

    // ── AUTHOR TAGS (edit form only) ─────────────────────────────────────
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
    const saveBtn     = document.getElementById('savePrintBtn');
    const saveText    = document.getElementById('savePrintText');
    const saveLoading = document.getElementById('savePrintLoading');
    const editFormEl  = document.getElementById('editForm');
    if (editFormEl && saveBtn) {
        editFormEl.addEventListener('submit', () => {
            saveBtn.disabled = true;
            if (saveText)    saveText.classList.add('hidden');
            if (saveLoading) saveLoading.classList.remove('hidden');
        });
    }

    // ── AJAX PARTIAL TABLE RELOAD ────────────────────────────────────────
    (function () {
        let currentController = null;

        function setLoading(tabId, loading) {
            const tab = document.getElementById(tabId);
            if (!tab) return;

            const skeleton = window.ResourceLoadingSkeleton;
            if (skeleton) {
                loading ? skeleton.show(tab) : skeleton.hide(tab);
            }
        }

        function rehydrateTab(tabId) {
            attachViewBtnListeners();
            attachDeleteListeners();
            attachPaginationListeners();
            attachSearchFormListeners();

            // Re-setup toggles after AJAX
            if (tabId === 'tab-masterlist') {
                setupMasterlistViewToggle();
            }
            if (tabId === 'tab-requests') {
                setupRequestsViewToggle();
            }

            const tab = document.getElementById(tabId);
            if (!tab) return;

            tab.querySelectorAll('a[href*="masterlist"]').forEach(link => {
                if (link.href.includes('/edit') || link.dataset.ajaxBound) return;
                link.dataset.ajaxBound = '1';
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    ajaxFetch(this.href, tabId);
                });
            });
        }
                // ── PER PAGE CHANGE (AJAX — only reloads the table) ─────────────────────
        window.handlePerPageChange = function(selectEl, tabId, perPageParam, viewInputId) {
            const viewInput = document.getElementById(viewInputId);
            const currentView = viewInput ? viewInput.value : 'table';

            const url = new URL(window.location.href);
            
            url.searchParams.set(perPageParam, selectEl.value);
            url.searchParams.set('active_tab', tabId);
            
            if (tabId === 'tab-masterlist') {
                url.searchParams.set('ml_view', currentView);
            } else if (tabId === 'tab-requests') {
                url.searchParams.set('rq_view', currentView);
            }

            // Use AJAX to reload only the tab content
            if (typeof ajaxFetch === 'function') {
                ajaxFetch(url.toString(), tabId);
            } else {
                console.warn('ajaxFetch not found - falling back');
                window.location.href = url.toString();
            }
        };

        function ajaxFetch(url, tabId) {
            if (currentController) currentController.abort();
            const controller = new AbortController();
            currentController = controller;

            setLoading(tabId, true);
            history.pushState({ tabId }, '', url);

            fetch(url, {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                }
            })
            .then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc    = parser.parseFromString(html, 'text/html');
                const newTab = doc.getElementById(tabId);
                const oldTab = document.getElementById(tabId);
                if (newTab && oldTab) {
                    newTab.classList.remove('hidden');
                    oldTab.replaceWith(newTab);
                    rehydrateTab(tabId);
                }
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                console.error('AJAX fetch failed, falling back:', err);
                window.location.href = url;
            })
            .finally(() => {
                if (currentController === controller) {
                    setLoading(tabId, false);
                    currentController = null;
                }
            });
        }

        function attachPaginationListeners() {
            ['tab-masterlist', 'tab-requests'].forEach(tabId => {
                const tab = document.getElementById(tabId);
                if (!tab) return;
                tab.querySelectorAll('nav[role="navigation"] a, .pagination a').forEach(link => {
                    if (link.dataset.ajaxBound) return;
                    link.dataset.ajaxBound = '1';
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const url = new URL(this.href, window.location.origin);
                        url.searchParams.set('active_tab', tabId);
                        // Carry view and per-page so server echoes them back into the hidden inputs
                        if (tabId === 'tab-masterlist') {
                            const mlInput = document.getElementById('ml-view-input');
                            if (mlInput) url.searchParams.set('ml_view', mlInput.value || 'table');
                            const mlPp = document.getElementById('ml-per-page-select');
                            if (mlPp) url.searchParams.set('ml_per_page', mlPp.value);
                        } else if (tabId === 'tab-requests') {
                            const rqInput = document.getElementById('rq-view-input');
                            if (rqInput) url.searchParams.set('rq_view', rqInput.value || 'table');
                            const rqPp = document.getElementById('rq-per-page-select');
                            if (rqPp) url.searchParams.set('rq_per_page', rqPp.value);
                        }
                        ajaxFetch(url.toString(), tabId);
                    });
                });
            });
        }

        function attachSearchFormListeners() {
            const configs = [
                { tabId: 'tab-masterlist', searchParam: 'ml_search' },
                { tabId: 'tab-requests',   searchParam: 'rq_search'  },
            ];
            configs.forEach(({ tabId, searchParam }) => {
                const tab = document.getElementById(tabId);
                if (!tab) return;
                const form = tab.querySelector('form');
                if (!form || form.dataset.ajaxBound) return;
                form.dataset.ajaxBound = '1';
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const url = new URL(form.action, window.location.origin);
                    url.searchParams.delete(searchParam);
                    const val = form.querySelector(`input[name="${searchParam}"]`)?.value?.trim();
                    if (val) url.searchParams.set(searchParam, val);
                    url.searchParams.set('active_tab', tabId);
                    ajaxFetch(url.toString(), tabId);
                });
            });
        }

        window.addEventListener('popstate', function (e) {
            if (isEditing) return;
            const params = new URLSearchParams(window.location.search);
            const tabId  = e.state?.tabId || params.get('active_tab') || 'tab-masterlist';
            activatePageTab(tabId);
            ajaxFetch(window.location.href, tabId);
        });

        attachSearchFormListeners();
        attachPaginationListeners();

    })();

})();

function toggleVerificationTimeline(btn) {
    const panel   = btn.closest('.mt-3').querySelector('.timeline-panel');
    const chevron = btn.querySelector('.timeline-chevron');
    if (!panel) return;
    const isOpen = !panel.classList.contains('hidden');
    panel.classList.toggle('hidden', isOpen);
    chevron.style.transform = isOpen ? '' : 'rotate(90deg)';
}

function showImageModal(imageUrl, caption) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    
    modalImage.src = imageUrl;
    modalCaption.textContent = caption;
    modal.classList.remove('hidden');
    
    // Prevent body scrolling when modal is open
    document.body.style.overflow = 'hidden';
}

function hideImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    
    // Restore body scrolling
    document.body.style.overflow = '';
}
// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('imageModal');
        if (modal && !modal.classList.contains('hidden')) {
            hideImageModal();
        }
    }
});
</script>

@endsection
