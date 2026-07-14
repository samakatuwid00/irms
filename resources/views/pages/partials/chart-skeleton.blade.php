<div data-chart-skeleton
     data-chart-skeleton-variant="{{ $variant }}"
     class="pointer-events-none absolute inset-0 z-10 hidden translate-y-1 overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 via-white to-cyan-50 opacity-0 transition-all duration-300 ease-out dark:from-slate-800 dark:via-slate-900 dark:to-slate-800"
     aria-hidden="true">
    <div class="flex h-full min-h-[320px] flex-col p-4 md:p-6 animate-pulse">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="h-3 w-28 rounded-full bg-blue-100"></div>
                <div class="h-4 w-48 max-w-[60vw] rounded-full bg-gray-200"></div>
            </div>
            <div class="flex h-10 items-center gap-2 rounded-lg border border-blue-100/70 bg-white/70 px-3 shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                <div class="h-7 w-7 rounded-md bg-blue-100 dark:bg-slate-700"></div>
                <div class="hidden h-3 w-20 rounded-full bg-gray-200 sm:block"></div>
            </div>
        </div>

        @if ($variant === 'availability')
            {{-- Grouped vertical bars, matching LR Availability. --}}
            <div class="relative flex min-h-[230px] flex-1 items-end gap-3 overflow-hidden rounded-xl border border-blue-100/70 bg-white/70 px-5 pb-5 pt-8">
                <div class="absolute inset-x-5 top-1/4 border-t border-gray-200"></div>
                <div class="absolute inset-x-5 top-1/2 border-t border-gray-200"></div>
                <div class="absolute inset-x-5 top-3/4 border-t border-gray-200"></div>
                @foreach ([[42, 68, 55], [65, 82, 48], [52, 71, 62], [75, 90, 58], [48, 66, 78], [62, 80, 50]] as $group)
                    <div class="relative flex h-full flex-1 items-end justify-center gap-1">
                        @foreach ($group as $index => $height)
                            <div class="w-1/4 rounded-t-sm {{ ['bg-blue-200', 'bg-cyan-200', 'bg-indigo-200'][$index] }}"
                                 style="height: {{ $height }}%"></div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-center gap-5">
                <div class="h-3 w-20 rounded-full bg-blue-200"></div>
                <div class="h-3 w-20 rounded-full bg-cyan-200"></div>
                <div class="h-3 w-20 rounded-full bg-indigo-200"></div>
            </div>

        @elseif ($variant === 'ratio')
            {{-- Horizontal stacked rows, matching LR-to-learner Ratio. --}}
            <div class="flex min-h-[250px] flex-1 gap-4 rounded-xl border border-blue-100/70 bg-white/70 p-5">
                <div class="flex w-20 flex-col justify-around gap-3">
                    @for ($row = 0; $row < 7; $row++)
                        <div class="h-3 rounded-full bg-gray-200"></div>
                    @endfor
                </div>
                <div class="relative flex flex-1 flex-col justify-around gap-3 border-l border-gray-200 pl-3">
                    <div class="absolute inset-y-0 left-1/3 border-l border-gray-100"></div>
                    <div class="absolute inset-y-0 left-2/3 border-l border-gray-100"></div>
                    @foreach ([78, 56, 88, 67, 48, 74, 61] as $width)
                        <div class="relative flex h-5 overflow-hidden rounded-r-full bg-gray-100" style="width: {{ $width }}%">
                            <div class="h-full w-3/5 bg-blue-200"></div>
                            <div class="h-full flex-1 bg-cyan-200"></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-4 flex justify-center gap-5">
                <div class="h-3 w-20 rounded-full bg-blue-200"></div>
                <div class="h-3 w-20 rounded-full bg-cyan-200"></div>
            </div>

        @elseif ($variant === 'exdef')
            {{-- Positive/negative columns around a zero axis, matching ExDef. --}}
            <div class="relative flex min-h-[250px] flex-1 gap-3 overflow-hidden rounded-xl border border-blue-100/70 bg-white/70 px-5 py-5">
                <div class="absolute inset-x-5 top-1/2 border-t-2 border-gray-300"></div>
                <div class="absolute inset-x-5 top-1/4 border-t border-gray-100"></div>
                <div class="absolute inset-x-5 top-3/4 border-t border-gray-100"></div>
                @foreach ([[74, 0], [48, 0], [0, 62], [86, 0], [0, 45], [58, 0], [0, 72], [68, 0]] as [$positive, $negative])
                    <div class="relative grid flex-1 grid-rows-[1fr_2px_1fr]">
                        <div class="flex items-end justify-center">
                            @if ($positive)
                                <div class="w-3/5 rounded-t bg-emerald-200" style="height: {{ $positive }}%"></div>
                            @endif
                        </div>
                        <div></div>
                        <div class="flex items-start justify-center">
                            @if ($negative)
                                <div class="w-3/5 rounded-b bg-rose-200" style="height: {{ $negative }}%"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-center gap-5">
                <div class="h-3 w-20 rounded-full bg-emerald-200"></div>
                <div class="h-3 w-20 rounded-full bg-rose-200"></div>
            </div>

        @elseif ($variant === 'heatmap')
            {{-- Cell matrix and intensity scale, matching Equitable Distribution. --}}
            <div class="flex min-h-[250px] flex-1 gap-3 rounded-xl border border-blue-100/70 bg-white/70 p-5">
                <div class="grid w-16 grid-rows-6 gap-1.5 py-0.5">
                    @for ($row = 0; $row < 6; $row++)
                        <div class="my-auto h-3 rounded-full bg-gray-200"></div>
                    @endfor
                </div>
                <div class="grid flex-1 grid-cols-8 grid-rows-6 gap-1.5">
                    @for ($cell = 0; $cell < 48; $cell++)
                        <div class="rounded-sm bg-blue-500"
                             style="opacity: {{ 0.16 + (($cell * 7) % 8) * 0.09 }}"></div>
                    @endfor
                </div>
                <div class="w-3 rounded-full bg-gradient-to-t from-blue-100 via-blue-300 to-blue-600"></div>
            </div>
            <div class="mt-4 grid grid-cols-8 gap-2 px-8 sm:px-20">
                @for ($label = 0; $label < 8; $label++)
                    <div class="h-2.5 rounded-full bg-gray-200"></div>
                @endfor
            </div>

        @else
            <div class="relative flex min-h-[230px] flex-1 items-end gap-3 overflow-hidden rounded-xl border border-blue-100/70 bg-white/70 px-5 pb-5 pt-8">
                @foreach ([45, 70, 55, 86, 64, 76, 50, 68] as $height)
                    <div class="relative flex-1 rounded-t-md bg-gradient-to-t from-blue-200 to-cyan-100"
                         style="height: {{ $height }}%"></div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@once
    <script>
        window.DashboardChartLoading = window.DashboardChartLoading || (function () {
            const hideTimers = new WeakMap();

            function resolveCard(target) {
                if (typeof target === 'string') target = document.getElementById(target);
                return target?.matches?.('[data-chart-card]')
                    ? target
                    : target?.closest?.('[data-chart-card]');
            }

            function show(target) {
                const card = resolveCard(target);
                const skeleton = card?.querySelector('[data-chart-skeleton]');
                const content = card?.querySelector('[data-chart-card-content]');
                const actions = card?.querySelector('[data-chart-card-actions]');
                if (!card || !skeleton || !content) return;

                window.clearTimeout(hideTimers.get(card));
                card.setAttribute('aria-busy', 'true');
                content.classList.add('pointer-events-none', 'opacity-0', 'translate-y-1');
                actions?.classList.add('pointer-events-none', 'opacity-0');
                actions?.setAttribute('aria-hidden', 'true');
                skeleton.classList.remove('hidden');

                requestAnimationFrame(() => {
                    skeleton.classList.remove('opacity-0', 'translate-y-1');
                    skeleton.classList.add('opacity-100', 'translate-y-0');
                });
            }

            function hide(target) {
                const card = resolveCard(target);
                const skeleton = card?.querySelector('[data-chart-skeleton]');
                const content = card?.querySelector('[data-chart-card-content]');
                const actions = card?.querySelector('[data-chart-card-actions]');
                if (!card || !skeleton || !content) return;

                skeleton.classList.remove('opacity-100', 'translate-y-0');
                skeleton.classList.add('opacity-0', 'translate-y-1');
                content.classList.remove('pointer-events-none', 'opacity-0', 'translate-y-1');
                content.classList.add('opacity-100', 'translate-y-0');
                actions?.classList.remove('pointer-events-none', 'opacity-0');
                actions?.removeAttribute('aria-hidden');
                card.removeAttribute('aria-busy');
                card.dataset.chartReady = 'true';

                const timer = window.setTimeout(() => skeleton.classList.add('hidden'), 300);
                hideTimers.set(card, timer);
            }

            function transition(target, update, delay = 140) {
                show(target);
                window.setTimeout(() => {
                    try {
                        update();
                    } finally {
                        requestAnimationFrame(() => hide(target));
                    }
                }, delay);
            }

            return { show, hide, transition };
        })();
    </script>
@endonce
