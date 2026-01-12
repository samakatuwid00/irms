@extends('pages.layout.layout')

@section('title', 'Non-Print Resources')
@section('page-title', 'Non-Print Resources')
@section('header-title', 'Welcome, '.Auth::user()->firstname.' '.Auth::user()->lastname)
@section('header-subtitle', '')
@section('breadcrumb', 'Non-Print Resource')
@section('content')

    @php
        $level = Auth::user()->userType?->level ?? 0;
    @endphp

    @include('pages.partials.page-header')

    <div class="space-y-4">

        <!-- ================= HEADER ================= -->
        <div class="flex justify-end items-center">
            @if($level == 1 || $level == 3)
                <a href="{{ route('add-resources') }}"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    + Add Resource
                </a>
            @endif
        </div>

        <!-- ===================================================== -->
        <!-- LEVEL 1: School account -->
        <!-- ===================================================== -->
        @if($level == 1)
            @include('pages.components.nonprint-resource-school-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 2: District account -->
        <!-- ===================================================== -->
        @if($level == 2)
            @include('pages.components.nonprint-resource-district-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 3: Division account -->
        <!-- ===================================================== -->
        @if($level == 3)
            @include('pages.components.nonprint-resource-division-account')
        @endif

        <!-- ===================================================== -->
        <!-- LEVEL 4: Region account - filter on demand          -->
        <!-- ===================================================== -->
        @if($level == 4)
            @include('pages.components.nonprint-resource-region-account')
        @endif

    </div>

    <script>
        const level = {{ $level }};

        // Persisted selected values from request
        const selectedDivision = "{{ request('division') }}";
        const selectedDistrict = "{{ request('district') }}";
        const selectedSchool   = "{{ request('school') }}";

        /* =====================================================
        LEVEL 3: TAB SWITCHING
        ===================================================== */
        if (level === 3) {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            // Check for active tab from URL parameter or default to 'division'
            const activeTab = new URLSearchParams(window.location.search).get('tab') || 'division';

            // Function to switch tabs
            function switchTab(tabName) {
                // Update buttons
                tabButtons.forEach(btn => {
                    if (btn.dataset.tab === tabName) {
                        btn.classList.add('border-blue-600', 'text-blue-600');
                        btn.classList.remove('border-transparent', 'text-gray-600');
                    } else {
                        btn.classList.remove('border-blue-600', 'text-blue-600');
                        btn.classList.add('border-transparent', 'text-gray-600');
                    }
                });

                // Update content
                tabContents.forEach(content => {
                    if (content.id === `${tabName}-tab`) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });
            }

            // Initialize with active tab
            switchTab(activeTab);

            // Add click handlers
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    switchTab(btn.dataset.tab);
                });
            });

            // District → School cascade for School Library tab
            const allSchools = @json($allSchools ?? []);
            const districtSelect = document.getElementById('district');
            const schoolSelect   = document.getElementById('school');

            if (districtSelect && schoolSelect) {
                const updateSchools = () => {
                    const districtId = districtSelect.value;
                    schoolSelect.innerHTML = '<option value="all">All Schools</option>';

                    if (!districtId || districtId === 'all') return;

                    allSchools
                        .filter(s => s.district_id == districtId)
                        .forEach(s => {
                            const selected = s.id == selectedSchool ? 'selected' : '';
                            schoolSelect.insertAdjacentHTML(
                                'beforeend',
                                `<option value="${s.id}" ${selected}>${s.school_name}</option>`
                            );
                        });
                };

                // Restore previous selection
                if (selectedDistrict) {
                    districtSelect.value = selectedDistrict;
                    updateSchools();
                }

                districtSelect.addEventListener('change', () => {
                    schoolSelect.value = 'all';
                    updateSchools();
                });
            }

            // Reset buttons
            document.querySelector('.reset-division')?.addEventListener('click', function() {
                window.location.href = window.location.pathname + '?tab=division';
            });

            document.querySelector('.reset-school')?.addEventListener('click', function() {
                window.location.href = window.location.pathname + '?tab=school';
            });
        }

        /* =====================================================
        LEVEL 4 : DIVISION → DISTRICT → SCHOOL
        ===================================================== */
        if (level === 4) {
            const allDistricts = @json($allDistricts ?? []);
            const allSchools   = @json($allSchools ?? []);

            const divisionSelect = document.getElementById('division');
            const districtSelect = document.getElementById('district');
            const schoolSelect   = document.getElementById('school');

            if (divisionSelect && districtSelect && schoolSelect) {

                const updateDistricts = () => {
                    const divisionId = divisionSelect.value;

                    districtSelect.innerHTML = '<option value="all">All Districts</option>';
                    schoolSelect.innerHTML   = '<option value="all">All Schools</option>';

                    if (!divisionId || divisionId === 'all') return;

                    allDistricts
                        .filter(d => d.division_id == divisionId)
                        .forEach(d => {
                            const selected = d.id == selectedDistrict ? 'selected' : '';
                            districtSelect.insertAdjacentHTML(
                                'beforeend',
                                `<option value="${d.id}" ${selected}>${d.district_name}</option>`
                            );
                        });

                    updateSchools();
                };

                const updateSchools = () => {
                    const districtId = districtSelect.value;

                    schoolSelect.innerHTML = '<option value="all">All Schools</option>';

                    if (!districtId || districtId === 'all') return;

                    allSchools
                        .filter(s => s.district_id == districtId)
                        .forEach(s => {
                            const selected = s.id == selectedSchool ? 'selected' : '';
                            schoolSelect.insertAdjacentHTML(
                                'beforeend',
                                `<option value="${s.id}" ${selected}>${s.school_name}</option>`
                            );
                        });
                };

                // Restore previous selections
                if (selectedDivision) {
                    divisionSelect.value = selectedDivision;
                    updateDistricts();
                }

                divisionSelect.addEventListener('change', () => {
                    districtSelect.value = 'all';
                    schoolSelect.value   = 'all';
                    updateDistricts();
                });

                districtSelect.addEventListener('change', () => {
                    schoolSelect.value = 'all';
                    updateSchools();
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const levelSelect     = document.getElementById('level');
            const divisionSelect  = document.getElementById('division');
            const districtSelect  = document.getElementById('district');
            const schoolSelect    = document.getElementById('school');
            const searchInput     = document.getElementById('search');
            const resetBtn        = document.getElementById('resetFilters');

            if (resetBtn) {
                resetBtn.addEventListener('click', () => {

                    if (levelSelect)    levelSelect.value    = 'all';
                    if (divisionSelect) divisionSelect.value = 'all';
                    if (districtSelect) districtSelect.value = 'all';
                    if (schoolSelect)   schoolSelect.value   = 'all';

                    if (searchInput) searchInput.value = '';

                    if (typeof updateDistricts === 'function') updateDistricts();
                    if (typeof updateSchools === 'function')   updateSchools();

                    window.location.href = window.location.pathname;
                });
            }
        });
    </script>

    @include('pages.modals.view-nonprint-modal')
@endsection
