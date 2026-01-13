@extends('pages.layout.layout')

@section('title', 'Edit Resource')

@section('page-title', 'Edit Resource')

@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Edit Resource')

@section('content')
    <div class="p-6 space-y-6">
        <!-- ================= HEADER ================= -->
        @include('pages.partials.page-header')
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-xl font-semibold mb-4">Edit Resource</h2>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button type="button"
                        class="resource-tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                        data-tab="print"
                        id="print-tab">
                    Print Resource
                </button>
                <button type="button"
                        class="resource-tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                        data-tab="nonprint"
                        id="nonprint-tab">
                    Non-Print Resource
                </button>
            </div>

            <!-- Forms Container -->
            <div>
                <!-- Print Resource Form -->
                <div id="print-form" class="hidden">
                    @include('pages.components.edit-print-resource')
                </div>

                <!-- Non-Print Resource Form -->
                <div id="nonprint-form" class="hidden">
                    @include('pages.components.edit-nonprint-resource')
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('.resource-tab-btn');
            const printForm = document.getElementById('print-form');
            const nonprintForm = document.getElementById('nonprint-form');
            const printTab = document.getElementById('print-tab');
            const nonprintTab = document.getElementById('nonprint-tab');

            // Get current resource ID from URL
            const resourceId = '{{ $printResource->id ?? $nonprintResource->id ?? "" }}';
            const storageKey = `activeResourceTab_${resourceId}`;

            // Function to activate a specific tab
            function activateTab(tabType) {
                // Reset all tabs
                tabButtons.forEach(b => {
                    b.classList.remove('border-blue-600', 'text-blue-600');
                    b.classList.add('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
                });

                // Hide all forms
                printForm.classList.add('hidden');
                nonprintForm.classList.add('hidden');

                // Activate the selected tab
                if (tabType === 'nonprint') {
                    nonprintTab.classList.remove('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
                    nonprintTab.classList.add('border-blue-600', 'text-blue-600');
                    nonprintForm.classList.remove('hidden');
                    // Store active tab in localStorage with resource ID
                    sessionStorage.setItem(storageKey, 'nonprint');
                } else {
                    printTab.classList.remove('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
                    printTab.classList.add('border-blue-600', 'text-blue-600');
                    printForm.classList.remove('hidden');
                    // Store active tab in localStorage with resource ID
                    sessionStorage.setItem(storageKey, 'print');
                }
            }

            // Tab click handlers
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    activateTab(btn.dataset.tab);
                });
            });

            // Check URL parameter first, then sessionStorage for this specific resource, then resource availability
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            const storedTab = sessionStorage.getItem(storageKey);
            const hasPrintResource = {{ $printResource ? 'true' : 'false' }};
            const hasNonprintResource = {{ $nonprintResource ? 'true' : 'false' }};

            // Determine which tab to show
            let initialTab = 'print'; // default

            if (tabParam && (tabParam === 'print' || tabParam === 'nonprint')) {
                // URL parameter takes highest priority
                initialTab = tabParam;
            } else if (storedTab && (storedTab === 'print' || storedTab === 'nonprint')) {
                // Use stored tab if available for this specific resource
                initialTab = storedTab;
            } else if (!hasPrintResource && hasNonprintResource) {
                // If only nonprint resource exists, show nonprint tab
                initialTab = 'nonprint';
            } else if (hasPrintResource && !hasNonprintResource) {
                // If only print resource exists, show print tab
                initialTab = 'print';
            }

            // Activate the determined tab
            activateTab(initialTab);
        });
    </script>
@endsection
