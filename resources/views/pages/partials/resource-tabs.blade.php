@php
    $tabs = $tabs ?? [];
@endphp

<div class="border-b border-gray-200">
    <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="{{ $ariaLabel ?? 'Resource tabs' }}">
        @foreach ($tabs as $tab)
            @php
                $isActive = (bool) ($tab['active'] ?? false);
                $baseClass = 'tab-button shrink-0 whitespace-nowrap py-4 px-1 text-sm font-medium border-b-2 transition-colors';
                $stateClass = $isActive
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
            @endphp
            <button
                type="button"
                class="{{ $baseClass }} {{ $stateClass }} {{ $tab['class'] ?? '' }}"
                {!! $tab['attributes'] ?? '' !!}>
                {{ $tab['label'] }}
            </button>
        @endforeach
    </nav>
</div>
