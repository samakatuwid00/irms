<div {{ $attributes->merge(['class' => 'bg-gradient-to-br from-blue-50/60 to-cyan-50/40 rounded-xl shadow p-4 md:p-6']) }}>
    @isset($title)
        <div class="flex items-center justify-between mb-4 md:mb-5">
            <h2 class="text-base md:text-lg font-semibold text-gray-800">
                {{ $title }}
            </h2>
            {{ $actions ?? '' }}
        </div>
    @endisset

    <!-- Important: remove h-[600px] – let content decide height -->
    <div class="w-full min-h-[320px]">   <!-- ← fallback minimum height -->
        {{ $slot }}
    </div>
</div>