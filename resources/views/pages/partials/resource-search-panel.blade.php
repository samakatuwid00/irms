@php
    $method = $method ?? 'GET';
    $action = $action ?? url()->current();
    $searchName = $searchName ?? 'search';
    $searchValue = $searchValue ?? request($searchName);
    $placeholder = $placeholder ?? 'Search by Title, Author, ISBN, Publisher, Grade, Subject...';
    $buttonLabel = $buttonLabel ?? 'Search';
    $resetLabel = $resetLabel ?? 'Reset';
    $resetHref = $resetHref ?? null;
    $formId = $formId ?? null;
    $showReset = $showReset ?? true;
@endphp

<form method="{{ $method }}" action="{{ $action }}" data-ajax id="{{ $formId }}" class="bg-white rounded-xl shadow p-3 sm:p-4 space-y-4">
    {{ $hidden ?? '' }}

    {{ $notice ?? '' }}

    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1 min-w-0">
            <input type="text"
                   name="{{ $searchName }}"
                   placeholder="{{ $placeholder }}"
                   value="{{ $searchValue }}"
                   class="w-full h-11 rounded-lg border border-gray-300 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400"
                 xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
        </div>

        <button type="submit" class="h-11 w-full rounded-lg bg-blue-600 px-5 text-sm font-medium text-white transition-colors hover:bg-blue-700 sm:w-auto">
            {{ $buttonLabel }}
        </button>

        @if ($showReset)
            @if ($resetHref)
                <a href="{{ $resetHref }}" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-gray-200 px-5 text-sm font-medium text-gray-800 transition-colors hover:bg-gray-300 sm:w-auto">
                    {{ $resetLabel }}
                </a>
            @else
                <button type="button" id="{{ $resetId ?? 'resetFilters' }}" class="h-11 w-full rounded-lg bg-gray-200 px-5 text-sm font-medium text-gray-800 transition-colors hover:bg-gray-300 sm:w-auto">
                    {{ $resetLabel }}
                </button>
            @endif
        @endif
    </div>

    {{ $filters ?? '' }}
</form>
