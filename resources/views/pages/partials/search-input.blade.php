@props([
    'name' => 'search',
    'placeholder' => 'Search title, author, ISBN...',
    'value' => '',
    'id' => null,
])

<input type="text"
    name="{{ $name }}"
    id="{{ $id ?? $name }}"
    placeholder="{{ $placeholder }}"
    value="{{ $value }}"
    autocomplete="off"
    {{ $attributes->merge(['class' => 'w-full md:w-[300px] lg:w-[400px] xl:w-[500px] 2xl:w-[600px] border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition']) }}>
