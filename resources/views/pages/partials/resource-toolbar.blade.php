@php
    $perPageOptions = $perPageOptions ?? [5, 10, 15, 20];
    $perPage = $perPage ?? request('per_page', 10);
    $target = $target ?? null;
    $cardActive = ($activeView ?? 'card') === 'card';
    $tableActive = ! $cardActive;
@endphp

<div class="flex flex-col gap-4 mt-4 sm:flex-row sm:items-center sm:justify-between">
    <a href="{{ $exportHref }}"
       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 sm:w-auto sm:justify-start">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span>Export to Excel</span>
    </a>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-300">
            <label class="whitespace-nowrap font-medium">Show entries:</label>
            <select class="per-page-select border border-gray-300 rounded-xl px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                data-context="{{ $context ?? 'default' }}">
                @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}" {{ (string) $perPage === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center justify-center rounded-xl bg-gray-100 p-1 dark:bg-slate-800">
            <button type="button"
                    class="view-toggle-btn flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $cardActive ? 'bg-white text-blue-600 shadow dark:bg-slate-700 dark:text-blue-300' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
                    @if($target) data-target="{{ $target }}" @endif data-view="card">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="hidden md:inline">Cards</span>
            </button>

            <button type="button"
                    class="view-toggle-btn flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $tableActive ? 'bg-white text-blue-600 shadow dark:bg-slate-700 dark:text-blue-300' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
                    @if($target) data-target="{{ $target }}" @endif data-view="table">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18" />
                </svg>
                <span class="hidden md:inline">Table</span>
            </button>
        </div>
    </div>
</div>
