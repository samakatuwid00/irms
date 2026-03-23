@props([
    'id',
    'label',
    'options'   => [],   // array of ['value' => '', 'label' => ''] or ['value' => '', 'label' => '', 'selected' => true]
    'name'      => null,
    'maxWidth'  => '300px',
    'class'     => '',
])

<div class="relative mb-4 {{ $class }}" style="max-width: {{ $maxWidth }}">

    {{-- Floating label --}}
    <label for="{{ $id }}"
        class="absolute left-3 -top-2 px-2 bg-gray-100 text-xs font-semibold text-gray-600 tracking-wide z-10">
        {{ $label }}
    </label>

    {{-- Select --}}
    <select
        id="{{ $id }}"
        name="{{ $name ?? $id }}"
        {{ $attributes->merge([
            'class' => 'w-full px-3 py-2 text-sm bg-gray-100 border border-black rounded-lg
                        focus:ring-2 focus:ring-indigo-400 focus:border-black
                        hover:border-gray-700 transition appearance-none cursor-pointer pr-9'
        ]) }}
    >
        {{-- Named slot: lets callers write raw <option> tags if preferred --}}
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            @foreach ($options as $opt)
                <option
                    value="{{ $opt['value'] }}"
                    {{ ($opt['selected'] ?? false) ? 'selected' : '' }}
                >
                    {{ $opt['label'] }}
                </option>
            @endforeach
        @endif
    </select>

    {{-- Chevron arrow --}}
    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </span>

</div>
