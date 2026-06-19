<span class="{{ $wrapperClass ?? 'inline-flex items-center gap-1.5' }}">
    @include('pages.components.verified-badge', [
        'verified' => $verified ?? false,
        'class' => $badgeClass ?? 'w-4 h-4 text-blue-600 shrink-0',
    ])
    <span>{{ $title }}</span>
</span>
