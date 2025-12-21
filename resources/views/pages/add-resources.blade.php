@extends('pages.layout.layout')

@section('title', 'Add Resource')

@section('page-title', 'Add Resource')

@section('content')
    <div class="p-6 space-y-6">
        <!-- ================= HEADER ================= -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Welcome, Admin</h1>
                <p class="text-gray-500 text-sm mt-1">Add learning resource.</p>
            </div>
            <div class="text-sm text-gray-500">
                <nav class="flex gap-1">
                    <a href="#" class="hover:underline">Home</a> /
                    <span>Add Resource</span>
                </nav>
            </div>
        </div>

        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-xl font-semibold mb-4">Add Resource</h2>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6">
                <button type="button"
                        class="resource-tab-btn px-6 py-3 text-sm font-medium border-b-2 transition-colors
                               border-blue-600 text-blue-600"
                        data-tab="print">
                    Print Resource
                </button>
                <button type="button"
                        class="resource-tab-btn px-6 py-3 text-sm font-medium border-b-2 border-transparent
                               text-gray-600 hover:text-blue-600 hover:border-gray-300"
                        data-tab="nonprint">
                    Non-Print Resource
                </button>
            </div>

            <!-- Forms Container -->
            <div>
                <!-- Print Resource Form (Visible by default) -->
                <div id="print-form">
                    @include('pages.components.add-print-resource')
                </div>

                <!-- Non-Print Resource Form (Hidden by default) -->
                <div id="nonprint-form" class="hidden">
                    @include('pages.components.add-nonprint-resource')
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('.resource-tab-btn');
            const printForm = document.getElementById('print-form');
            const nonprintForm = document.getElementById('nonprint-form');

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Update active tab styling
                    tabButtons.forEach(b => {
                        b.classList.remove('border-blue-600', 'text-blue-600');
                        b.classList.add('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
                    });
                    btn.classList.remove('border-transparent', 'text-gray-600', 'hover:text-blue-600', 'hover:border-gray-300');
                    btn.classList.add('border-blue-600', 'text-blue-600');

                    // Show/hide forms
                    if (btn.dataset.tab === 'print') {
                        printForm.classList.remove('hidden');
                        nonprintForm.classList.add('hidden');
                    } else {
                        nonprintForm.classList.remove('hidden');
                        printForm.classList.add('hidden');
                    }
                });
            });

            // Ensure Print is default on load (already set in HTML, but reinforce)
            printForm.classList.remove('hidden');
            nonprintForm.classList.add('hidden');
        });
    </script>
@endsection
