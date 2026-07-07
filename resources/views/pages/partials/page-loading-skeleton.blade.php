@php
    $skeletonType = match (true) {
        request()->routeIs('dashboard') => 'dashboard',
        request()->routeIs(
            'print-resource.create',
            'nonprint-resource.create'
        ) => 'resource-search',
        request()->routeIs(
            'print-resources',
            'nonprint-resources'
        ) => 'resource-gallery',
        request()->routeIs(
            'masterlist.index',
            'nonprint-masterlist.index'
        ) => 'masterlist-gallery',
        request()->routeIs(
            'edit-resource',
            'masterlist.edit',
            'nonprint-masterlist.edit',
            'import.sf6.*'
        ) => 'form',
        request()->routeIs(
            'profile',
            'region-profile',
            'division-profile',
            'district-profile',
            'school-profile'
        ) => 'profile',
        default => 'listing',
    };
@endphp

<div id="content-skeleton" data-page-skeleton="{{ $skeletonType }}" aria-hidden="true">
    @if ($skeletonType === 'dashboard')
        <div class="mb-6 space-y-2">
            <div class="page-skeleton-block h-7 w-64"></div>
            <div class="page-skeleton-block h-4 w-80 max-w-full"></div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="page-skeleton-block h-4 w-28"></div>
                        <div class="page-skeleton-block size-9 rounded-full"></div>
                    </div>
                    <div class="page-skeleton-block mb-5 h-7 w-20"></div>
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div class="page-skeleton-block h-3 w-16"></div>
                            <div class="page-skeleton-block h-3 w-10"></div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="page-skeleton-block h-3 w-16"></div>
                            <div class="page-skeleton-block h-3 w-10"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="space-y-1.5">
                    <div class="page-skeleton-block h-3 w-24"></div>
                    <div class="page-skeleton-block h-10 w-full rounded-lg"></div>
                </div>
            @endfor
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="mb-6 flex items-start justify-between">
                <div class="space-y-2">
                    <div class="page-skeleton-block h-3 w-32"></div>
                    <div class="page-skeleton-block h-5 w-56"></div>
                </div>
                <div class="flex gap-2">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="page-skeleton-block size-5 rounded"></div>
                    @endfor
                </div>
            </div>
            <div class="flex h-72 items-end gap-4 px-3">
                @foreach ([15, 45, 90, 60, 35, 20, 82, 55, 30, 78, 48, 25, 20, 15, 65] as $height)
                    <div class="page-skeleton-block flex-1 rounded-t-md" style="height: {{ $height }}%"></div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-center gap-3 border-t border-gray-100 pt-4">
                @foreach ([12, 10, 14, 12, 16, 12, 10, 14, 8, 20] as $width)
                    <div class="page-skeleton-block h-3" style="width: {{ $width }}%"></div>
                @endforeach
            </div>
        </div>
    @elseif ($skeletonType === 'resource-search')
        <div class="mb-6">
            <div class="page-skeleton-block h-8 w-64"></div>
        </div>

        <div class="mb-6 border-b border-gray-100">
            <div class="page-skeleton-block mb-3 h-4 w-32"></div>
        </div>

        <div class="mb-5 space-y-2">
            <div class="page-skeleton-block h-4 w-64"></div>
            <div class="page-skeleton-block h-3.5 w-full max-w-2xl"></div>
        </div>

        <div class="mb-3 flex flex-col gap-3 sm:flex-row">
            <div class="page-skeleton-block h-12 flex-1 rounded-lg"></div>
            <div class="page-skeleton-block h-12 w-full rounded-lg sm:w-28"></div>
        </div>
        <div class="mb-16">
            <div class="page-skeleton-block h-3 w-40"></div>
        </div>

        <div class="flex flex-col items-center justify-center gap-4 py-16">
            <div class="page-skeleton-block size-14 rounded-full"></div>
            <div class="page-skeleton-block h-4 w-56"></div>
        </div>
    @elseif ($skeletonType === 'resource-gallery')
        <div class="mb-6">
            <div class="page-skeleton-block h-8 w-72"></div>
        </div>

        <div class="mb-6 flex gap-6 border-b border-gray-100">
            <div class="page-skeleton-block mb-3 h-4 w-16"></div>
            <div class="page-skeleton-block mb-3 h-4 w-16"></div>
        </div>

        <div class="mb-5 flex flex-col gap-3 sm:flex-row">
            <div class="page-skeleton-block h-12 flex-1 rounded-lg"></div>
            <div class="page-skeleton-block h-12 w-full rounded-lg sm:w-28"></div>
            <div class="page-skeleton-block h-12 w-full rounded-lg sm:w-24"></div>
        </div>

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="page-skeleton-block h-10 w-36 rounded-lg"></div>
            <div class="flex items-center gap-3">
                <div class="page-skeleton-block h-4 w-24"></div>
                <div class="page-skeleton-block h-9 w-16 rounded-lg"></div>
                <div class="page-skeleton-block h-9 w-20 rounded-lg"></div>
                <div class="page-skeleton-block h-9 w-20 rounded-lg"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-5">
            @for ($i = 0; $i < 10; $i++)
                <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="page-skeleton-block aspect-[3/4] w-full rounded-none"></div>
                    <div class="space-y-2 p-3">
                        <div class="page-skeleton-block h-3.5 w-full"></div>
                        <div class="page-skeleton-block h-3.5 w-2/3"></div>
                        <div class="page-skeleton-block h-3 w-1/2"></div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="page-skeleton-block h-4 w-10 rounded"></div>
                            <div class="page-skeleton-block h-3 w-14"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    @elseif ($skeletonType === 'masterlist-gallery')
        <div class="mb-6">
            <div class="page-skeleton-block h-8 w-56"></div>
        </div>

        <div class="mb-6 flex items-center gap-6 border-b border-gray-100">
            <div class="page-skeleton-block mb-3 h-4 w-20"></div>
            <div class="mb-3 flex items-center gap-2">
                <div class="page-skeleton-block h-4 w-28"></div>
                <div class="page-skeleton-block size-5 rounded-full"></div>
            </div>
        </div>

        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="page-skeleton-block h-5 w-52"></div>
                <div class="page-skeleton-block h-3.5 w-72 max-w-full"></div>
            </div>
            <div class="flex w-full gap-3 lg:w-auto">
                <div class="page-skeleton-block h-11 flex-1 rounded-lg lg:w-80"></div>
                <div class="page-skeleton-block h-11 w-24 shrink-0 rounded-lg"></div>
            </div>
        </div>

        <div class="mb-5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="page-skeleton-block h-4 w-20"></div>
                <div class="page-skeleton-block h-9 w-16 rounded-lg"></div>
            </div>
            <div class="flex items-center gap-3">
                <div class="page-skeleton-block h-9 w-20 rounded-lg"></div>
                <div class="page-skeleton-block h-9 w-20 rounded-lg"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-5">
            @for ($i = 0; $i < 10; $i++)
                <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="page-skeleton-block aspect-[3/4] w-full rounded-none"></div>
                    <div class="space-y-2 p-3">
                        <div class="flex items-start gap-1.5">
                            <div class="page-skeleton-block mt-0.5 size-3.5 shrink-0 rounded-full"></div>
                            <div class="page-skeleton-block h-3.5 w-full"></div>
                        </div>
                        <div class="page-skeleton-block h-3 w-2/3"></div>
                        <div class="page-skeleton-block h-3 w-1/2"></div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="page-skeleton-block h-3 w-10"></div>
                            <div class="page-skeleton-block h-7 w-16 rounded-lg"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    @elseif ($skeletonType === 'form')
        <div class="mb-6 space-y-2">
            <div class="page-skeleton-block h-7 w-52"></div>
            <div class="page-skeleton-block h-4 w-80 max-w-full"></div>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4">
                <div class="page-skeleton-block size-9 rounded-lg"></div>
                <div class="page-skeleton-block h-5 w-40"></div>
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="page-skeleton-block h-56 rounded-xl lg:h-64"></div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:col-span-2">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="space-y-2">
                            <div class="page-skeleton-block h-3 w-24"></div>
                            <div class="page-skeleton-block h-10 w-full rounded-lg"></div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="mt-7 flex justify-end gap-3 border-t border-gray-100 pt-5">
                <div class="page-skeleton-block h-10 w-24 rounded-lg"></div>
                <div class="page-skeleton-block h-10 w-32 rounded-lg"></div>
            </div>
        </div>
    @elseif ($skeletonType === 'profile')
        <div class="mb-6 space-y-2">
            <div class="page-skeleton-block h-7 w-44"></div>
            <div class="page-skeleton-block h-4 w-72 max-w-full"></div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="page-skeleton-block mx-auto mb-5 size-28 rounded-full"></div>
                <div class="page-skeleton-block mx-auto mb-3 h-5 w-36"></div>
                <div class="page-skeleton-block mx-auto mb-7 h-3 w-24"></div>
                <div class="page-skeleton-block h-10 w-full rounded-lg"></div>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="page-skeleton-block mb-6 h-5 w-40"></div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @for ($i = 0; $i < 6; $i++)
                        <div class="space-y-2">
                            <div class="page-skeleton-block h-3 w-20"></div>
                            <div class="page-skeleton-block h-10 w-full rounded-lg"></div>
                        </div>
                    @endfor
                </div>
                <div class="mt-6 flex justify-end">
                    <div class="page-skeleton-block h-10 w-28 rounded-lg"></div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div class="space-y-2">
                <div class="page-skeleton-block h-7 w-48"></div>
                <div class="page-skeleton-block h-4 w-72 max-w-full"></div>
            </div>
            <div class="page-skeleton-block h-10 w-32 rounded-lg"></div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center">
                <div class="page-skeleton-block h-10 flex-1 rounded-lg"></div>
                <div class="page-skeleton-block h-10 w-full rounded-lg sm:w-32"></div>
                <div class="page-skeleton-block h-10 w-full rounded-lg sm:w-28"></div>
            </div>
            <div class="hidden grid-cols-12 gap-4 bg-gray-50 px-5 py-3 sm:grid">
                @foreach ([2, 3, 2, 2, 2, 1] as $span)
                    <div class="page-skeleton-block h-3" style="grid-column: span {{ $span }} / span {{ $span }}"></div>
                @endforeach
            </div>
            <div class="divide-y divide-gray-100">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="page-skeleton-block size-9 shrink-0 rounded-lg"></div>
                        <div class="flex-1 space-y-2">
                            <div class="page-skeleton-block h-3.5 w-2/3"></div>
                            <div class="page-skeleton-block h-3 w-2/5"></div>
                        </div>
                        <div class="page-skeleton-block hidden h-6 w-20 rounded-full sm:block"></div>
                        <div class="page-skeleton-block h-8 w-16 rounded-lg"></div>
                    </div>
                @endfor
            </div>
        </div>
    @endif
</div>