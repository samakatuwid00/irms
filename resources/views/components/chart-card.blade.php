@props(['title' => null])

<div data-chart-card {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-br from-blue-50/60 to-cyan-50/40 rounded-xl shadow p-4 md:p-6 dark:border dark:border-slate-700 dark:bg-slate-900 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 dark:shadow-black/30']) }}>
    @isset($actions)
        <div data-chart-card-actions class="absolute right-4 top-4 z-20 flex max-w-[calc(100%-2rem)] justify-end transition-opacity duration-200 ease-out md:right-6 md:top-6 md:max-w-[calc(100%-3rem)]">
            {{ $actions }}
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
             class="w-full min-h-[320px] min-w-0 translate-y-0 opacity-100 transition-all duration-300 ease-out">
            {{ $slot }}
        </div>
    </div>
</div>
