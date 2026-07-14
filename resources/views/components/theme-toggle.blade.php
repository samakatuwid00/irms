@props([
    'compact' => false,
    'class' => '',
])

<button
    type="button"
    data-theme-toggle
    aria-pressed="false"
    title="Toggle dark mode"
    {{ $attributes->merge([
        'class' => trim(
            'theme-toggle inline-flex h-10 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900 ' . $class
        ),
    ]) }}
>
    <span class="theme-toggle-icon" aria-hidden="true">
        <span data-theme-toggle-light-icon class="theme-icon-sun">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
            </svg>
        </span>
        <span data-theme-toggle-dark-icon class="theme-icon-moon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path>
            </svg>
        </span>
    </span>
    @unless ($compact)
        <span data-theme-toggle-label>Dark mode</span>
    @endunless
</button>
