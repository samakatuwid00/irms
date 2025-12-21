<header class="bg-custom-teal text-white py-3 shadow-md">
    <div class="container mx-auto px-6 md:px-12 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <img src="{{ asset('assets/images/rov.png') }}" class="h-20 w-20 rounded-full">
            <img src="{{ asset('assets/images/deped.png') }}" class="h-20 w-20">
            <img src="{{ asset('assets/images/bp.png') }}" class="h-20 w-20">
        </div>

        <div>
            @if (isset($headerLink))
                <a href="{{ $headerLink['url'] }}" class="font-semibold hover:text-custom-yellow">
                    {{ $headerLink['text'] }}
                </a>
            @endif
        </div>
    </div>
</header>
