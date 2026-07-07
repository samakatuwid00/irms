<template id="resource-loading-skeleton-template" data-default-view="{{ $defaultView ?? 'card' }}">
    <div data-resource-loading-skeleton
         class="translate-y-1 py-2 opacity-0 transition-all duration-200 ease-out"
         role="status"
         aria-live="polite">
        <span class="sr-only">Loading resources...</span>

        <div data-skeleton-card class="hidden grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5" aria-hidden="true">
            @for ($card = 0; $card < 10; $card++)
                <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="page-skeleton-block aspect-[3/4] rounded-none"></div>
                    <div class="space-y-3 p-3">
                        <div class="page-skeleton-block h-3.5 w-5/6"></div>
                        <div class="page-skeleton-block h-3 w-2/3"></div>
                        <div class="page-skeleton-block h-2.5 w-1/2"></div>
                    </div>
                </div>
            @endfor
        </div>

        <div data-skeleton-table class="hidden overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm" aria-hidden="true">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3"><div class="page-skeleton-block h-3 w-10"></div></th>
                            @for ($column = 0; $column < 5; $column++)
                                <th class="px-4 py-3"><div class="page-skeleton-block h-3 w-20"></div></th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @for ($row = 0; $row < 6; $row++)
                            <tr>
                                <td class="px-4 py-3"><div class="page-skeleton-block h-12 w-10"></div></td>
                                <td class="px-4 py-3"><div class="page-skeleton-block h-3 w-40"></div></td>
                                <td class="px-4 py-3"><div class="page-skeleton-block h-3 w-28"></div></td>
                                <td class="px-4 py-3"><div class="page-skeleton-block h-3 w-20"></div></td>
                                <td class="px-4 py-3"><div class="page-skeleton-block h-3 w-24"></div></td>
                                <td class="px-4 py-3"><div class="page-skeleton-block h-7 w-16 rounded-lg"></div></td>
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
                if (Array.from(cardViews).some((view) => !view.classList.contains('hidden'))) {
                    return 'card';
                }

                const tableViews = target.querySelectorAll('[id$="-table-view"], #table-view');
                if (Array.from(tableViews).some((view) => !view.classList.contains('hidden'))) {
                    return 'table';
                }

                return null;
            }

            function show(target, view) {
                const template = document.getElementById('resource-loading-skeleton-template');
                if (!target || !template || target.querySelector(':scope > [data-resource-loading-skeleton]')) return;

                const skeleton = template.content.firstElementChild.cloneNode(true);
                const selectedView = view || activeView(target) || template.dataset.defaultView || 'card';
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
