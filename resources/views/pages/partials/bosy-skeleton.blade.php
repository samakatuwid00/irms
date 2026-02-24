{{-- resources/views/partials/bosy-skeleton.blade.php --}}
@for ($i = 0; $i < 5; $i++)
<div class="flex items-center gap-4 animate-pulse bosy-skeleton-item" style="height: 96px; contain: content;">
    <div class="w-16 h-16 rounded-full bg-gray-200 flex-shrink-0"></div>
    <div class="flex-1 grid grid-cols-12 gap-4 items-center">
        <div class="col-span-3 sm:col-span-2">
            <div class="h-5 bg-gray-200 rounded w-20 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
        <div class="col-span-5 sm:col-span-6">
            <div class="h-6 bg-gray-200 rounded-full w-full"></div>
        </div>
        <div class="col-span-2">
            <div class="h-4 bg-gray-200 rounded w-12 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-8"></div>
        </div>
        <div class="col-span-2">
            <div class="h-8 bg-gray-200 rounded w-20"></div>
        </div>
    </div>
</div>
@endfor