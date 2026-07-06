<div data-chart-card {{ $attributes->merge(['class' => 'bg-gradient-to-br from-blue-50/60 to-cyan-50/40 rounded-xl shadow p-4 md:p-6']) }}>
    @isset($title)
        <div class="flex items-center justify-between mb-4 md:mb-5">
            <h2 class="card-title text-base md:text-lg font-semibold text-gray-800">
                {{ $title }}
            </h2>
            {{ $actions ?? '' }}
        </div>
    @endisset

    @php
        $chartSkeletonVariant = match ($attributes->get('id')) {
            'lr-availability' => 'availability',
            'lr-ratio' => 'ratio',
            'lr-exdef' => 'exdef',
            'lr-heatmap' => 'heatmap',
            default => 'generic',
        };
    @endphp

    <div class="relative w-full min-h-[320px]">
        @include('pages.partials.chart-skeleton', ['variant' => $chartSkeletonVariant])

        <div data-chart-card-content
             class="w-full min-h-[320px] translate-y-0 opacity-100 transition-all duration-300 ease-out">
            {{ $slot }}
        </div>
    </div>
</div>
