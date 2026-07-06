<template id="resource-loading-skeleton-template">
    <div data-resource-loading-skeleton
         class="translate-y-1 py-2 opacity-0 transition-all duration-200 ease-out"
         role="status"
         aria-live="polite">
        <span class="sr-only">Loading resources...</span>

        <div data-skeleton-card class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 animate-pulse" aria-hidden="true">
            @for ($card = 0; $card < 5; $card++)
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="aspect-[3/4] bg-gray-200"></div>
                    <div class="space-y-3 p-3">
                        <div class="h-3 w-5/6 rounded-full bg-gray-200"></div>
                        <div class="h-3 w-2/3 rounded-full bg-gray-200"></div>
                        <div class="h-2.5 w-1/2 rounded-full bg-gray-100"></div>
                    </div>
                </div>
            @endfor
        </div>

        <div data-skeleton-table class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white animate-pulse" aria-hidden="true">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3"><div class="h-3 w-10 rounded-full bg-gray-300"></div></th>
                            @for ($column = 0; $column < 5; $column++)
                                <th class="px-4 py-3"><div class="h-3 w-20 rounded-full bg-gray-300"></div></th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @for ($row = 0; $row < 6; $row++)
                            <tr>
                                <td class="px-4 py-3"><div class="h-12 w-10 rounded bg-gray-200"></div></td>
                                <td class="px-4 py-3"><div class="h-3 w-40 rounded-full bg-gray-200"></div></td>
                                <td class="px-4 py-3"><div class="h-3 w-28 rounded-full bg-gray-200"></div></td>
                                <td class="px-4 py-3"><div class="h-3 w-20 rounded-full bg-gray-200"></div></td>
                                <td class="px-4 py-3"><div class="h-3 w-24 rounded-full bg-gray-200"></div></td>
                                <td class="px-4 py-3"><div class="h-7 w-16 rounded-lg bg-gray-200"></div></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

@once
    <script>
        window.ResourceLoadingSkeleton = window.ResourceLoadingSkeleton || (function () {
            function activeView(target) {
                const cardViews = target.querySelectorAll('[id$="-card-view"], #card-view');
                return Array.from(cardViews).some((view) => !view.classList.contains('hidden'))
                    ? 'card'
                    : 'table';
            }

            function show(target, view) {
                const template = document.getElementById('resource-loading-skeleton-template');
                if (!target || !template || target.querySelector(':scope > [data-resource-loading-skeleton]')) return;

                const skeleton = template.content.firstElementChild.cloneNode(true);
                const selectedView = view || activeView(target);
                skeleton.querySelector(`[data-skeleton-${selectedView}]`)?.classList.remove('hidden');

                Array.from(target.children).forEach((child) => {
                    child.dataset.skeletonDisplay = child.style.getPropertyValue('display');
                    child.dataset.skeletonDisplayPriority = child.style.getPropertyPriority('display');
                    child.dataset.skeletonAriaHidden = child.hasAttribute('aria-hidden')
                        ? child.getAttribute('aria-hidden')
                        : '__missing__';
                    child.dataset.skeletonHidden = 'true';
                    child.style.setProperty('display', 'none', 'important');
                    child.setAttribute('aria-hidden', 'true');
                });

                target.prepend(skeleton);
                target.setAttribute('aria-busy', 'true');

                requestAnimationFrame(() => {
                    skeleton.classList.remove('translate-y-1', 'opacity-0');
                    skeleton.classList.add('translate-y-0', 'opacity-100');
                });
            }

            function hide(target) {
                if (!target) return;

                target.querySelector(':scope > [data-resource-loading-skeleton]')?.remove();
                target.querySelectorAll(':scope > [data-skeleton-hidden="true"]').forEach((child) => {
                    const display = child.dataset.skeletonDisplay || '';
                    const priority = child.dataset.skeletonDisplayPriority || '';
                    const ariaHidden = child.dataset.skeletonAriaHidden;

                    if (display) {
                        child.style.setProperty('display', display, priority);
                    } else {
                        child.style.removeProperty('display');
                    }

                    if (ariaHidden === '__missing__') {
                        child.removeAttribute('aria-hidden');
                    } else if (ariaHidden !== undefined) {
                        child.setAttribute('aria-hidden', ariaHidden);
                    }

                    delete child.dataset.skeletonDisplay;
                    delete child.dataset.skeletonDisplayPriority;
                    delete child.dataset.skeletonAriaHidden;
                    delete child.dataset.skeletonHidden;
                });
                target.removeAttribute('aria-busy');

                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    target.animate(
                        [{ opacity: 0.55, transform: 'translateY(4px)' }, { opacity: 1, transform: 'translateY(0)' }],
                        { duration: 180, easing: 'ease-out' }
                    );
                }
            }

            return { show, hide };
        })();
    </script>
@endonce
