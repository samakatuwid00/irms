@extends('pages.layout.layout')

@section('title', 'School Profile')
@section('page-title', 'School Profile')

@section('header-title', $school->school_name)
@section('header-subtitle', 'Manage school profile information')
@section('breadcrumb', 'School Profile')

@section('content')

    @if ($populationRequired)
        <div id="populationRequiredModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 px-4"
            role="dialog" aria-modal="true" aria-labelledby="populationRequiredTitle">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-2xl">
                <h2 id="populationRequiredTitle" class="text-xl font-semibold text-gray-900">
                    School Population Required
                </h2>
                <p class="mt-3 text-sm leading-6 text-gray-600">
                    {{ \App\Services\SchoolPopulationRequirementService::NOTICE }}
                </p>
                <div class="mt-6 flex justify-end">
                    <button type="button" id="updatePopulationNow" class="btn-primary">
                        Update Now
                    </button>
                </div>
            </div>
        </div>
    @endif

    @include('pages.partials.page-header')

    {{-- ================== FLASH MESSAGE ================== --}}
    @if (session('success') || session('info'))
        <div id="alertBox"
            class="mb-4 rounded-lg px-4 py-3 relative {{ session('success') ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
            <span>{{ session('success') ?? session('info') }}</span>
            <button onclick="closeAlert()" class="absolute top-1 right-1 text-gray-500 hover:text-gray-800">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-100 text-red-800 px-4 py-3">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="p-6 space-y-6">

        <!-- ================= SCHOOL PROFILE ================= -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- ================= LEFT: LOGO ================= --}}
            <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center text-center gap-4">

                {{-- Logo Preview --}}
                <div class="relative">
                    <img src="{{ $school->logo ? asset('storage/' . $school->logo) : asset('assets/images/default.jpg') }}"
                        alt="School Logo"
                        class="w-64 h-64 rounded-xl object-cover border-2 border-dashed border-gray-300 bg-gray-50"
                        id="logoPreview">
                </div>

                {{-- Logo Upload Form --}}
                <form id="logoForm" action="{{ route('school.logo.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <label for="logo"
                        class="block w-full cursor-pointer border-2 border-dashed border-gray-300 rounded-lg px-4 py-3 text-sm text-gray-500 hover:border-blue-400 hover:bg-blue-50 transition">
                        <span class="font-medium text-gray-700">Choose logo</span>
                        <span class="block text-xs text-gray-400 mt-1">PNG or JPG (Max 2MB)</span>
                    </label>
                    <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg" class="hidden">

                    <button type="submit" id="logoSubmitBtn"
                        class="w-full px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow mt-2 transition opacity-50 cursor-not-allowed"
                        disabled>
                        Update Logo
                    </button>
                </form>

                {{-- School Name --}}
                <div class="mt-2">
                    <h2 class="text-lg font-semibold text-gray-800">{{ $school->school_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $school->shortname }}</p>
                </div>

            </div>

            {{-- ================= RIGHT: SCHOOL INFO ================= --}}
            <div class="md:col-span-2">

                {{-- School Information Form --}}
                <form id="schoolForm" class="bg-white rounded-xl shadow p-6" action="{{ route('school.profile.update') }}"
                    method="POST">
                    @csrf
                    @method('PUT')

                    <h3 class="text-lg font-semibold mb-6">School Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- School Name --}}
                        <div>
                            <label class="text-xs text-gray-500">School Name *</label>
                            <input type="text" name="school_name" value="{{ $school->school_name }}" class="input"
                                required>
                            <p class="error"></p>
                        </div>

                        {{-- Short Name --}}
                        <div>
                            <label class="text-xs text-gray-500">Short Name</label>
                            <input type="text" name="shortname" value="{{ $school->shortname }}" class="input">
                            <p class="error"></p>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="text-xs text-gray-500">Email *</label>
                            <input type="email" name="email" value="{{ $school->email }}" class="input" required>
                            <p class="error"></p>
                        </div>

                        {{-- Contact Number --}}
                        <div>
                            <label class="text-xs text-gray-500">Contact Number</label>
                            <input type="text" name="contact_number" value="{{ $school->contact_number }}"
                                class="input">
                            <p class="error"></p>
                        </div>

                        {{-- Legislative District --}}
                        <div>
                            <label class="text-xs text-gray-500">Legislative District</label>
                            <input type="text" name="legislative_district" value="{{ $school->legislative_district }}"
                                class="input">
                            <p class="error"></p>
                        </div>

                        {{-- Date Established --}}
                        <div>
                            <label class="text-xs text-gray-500">Date Established</label>

                            {{-- Readable date input --}}
                            <input type="text" id="date_display"
                                value="{{ $school->date_establish ? \Carbon\Carbon::parse($school->date_establish)->format('F d, Y') : '' }}"
                                readonly
                                class="mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm text-gray-700 cursor-pointer focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                                onclick="switchToDate()">

                            {{-- Actual date input (hidden) --}}
                            <input type="date" name="date_establish" id="date_input"
                                value="{{ $school->date_establish }}"
                                class="hidden mt-1 w-full rounded-lg border-2 border-dashed border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:bg-blue-50 transition"
                                onchange="updateReadableDate()" onblur="switchToText()">
                            <p class="error"></p>
                        </div>

                        {{-- Address --}}
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-500">Address</label>
                            <textarea name="address" class="input" rows="3">{{ $school->address }}</textarea>
                            <p class="error"></p>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="button" id="saveBtn" onclick="openConfirmModal()"
                            class="btn-primary opacity-50 cursor-not-allowed" disabled>
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- ================= SUBJECT GRADE OFFERINGS ================= --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Subject Grade Offerings</h3>
            <p class="text-sm text-gray-600 mb-6">Select the grade levels your school offers</p>

            <form id="gradeOfferingsForm" action="{{ route('school.grades.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 lg:grid-cols-14 gap-4 mb-6">
                    {{-- Kindergarten --}}
                    <div class="flex flex-col items-center">
                        <label class="flex flex-col items-center cursor-pointer group">
                            <input type="checkbox" name="K" value="yes"
                                {{ old('K', $gradeOffering?->K ?? 'no') === 'yes' ? 'checked' : '' }}
                                class="grade-checkbox w-5 h-5 rounded border-2 border-gray-300 text-blue-600
                                       focus:ring-2 focus:ring-blue-500 transition">
                            <span class="mt-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition">Kinder</span>
                        </label>
                    </div>

                    {{-- Grades 1-12 --}}
                    @for ($i = 1; $i <= 12; $i++)
                        <div class="flex flex-col items-center">
                            <label class="flex flex-col items-center cursor-pointer group">
                                <input type="checkbox" name="g{{ $i }}" value="yes"
                                    {{ old('g'.$i, $gradeOffering?->{'g'.$i} ?? 'no') === 'yes' ? 'checked' : '' }}
                                    class="grade-checkbox w-5 h-5 rounded border-2 border-gray-300 text-blue-600
                                           focus:ring-2 focus:ring-blue-500 transition">
                                <span class="mt-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition">
                                    Grade {{ $i }}
                                </span>
                            </label>
                        </div>
                    @endfor

                    {{-- Non-Graded (flexible — only shown/saved if school has it) --}}
                    <div class="flex flex-col items-center">
                        <label class="flex flex-col items-center cursor-pointer group">
                            <input type="checkbox" name="ng" value="yes"
                                {{ old('ng', $gradeOffering?->ng ?? 'no') === 'yes' ? 'checked' : '' }}
                                class="grade-checkbox w-5 h-5 rounded border-2 border-gray-300 text-blue-600
                                       focus:ring-2 focus:ring-blue-500 transition">
                            <span class="mt-2 text-sm font-medium text-gray-700 group-hover:text-blue-600 transition text-center leading-tight">
                                Non-Graded
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <div class="flex gap-2">
                        <button type="button" onclick="selectAllGrades()"
                            class="text-sm text-blue-600 hover:text-blue-700 hover:underline transition">
                            Select All
                        </button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="deselectAllGrades()"
                            class="text-sm text-blue-600 hover:text-blue-700 hover:underline transition">
                            Deselect All
                        </button>
                    </div>

                    <button type="button" id="saveGradesBtn" onclick="openGradesConfirmModal()"
                        class="btn-primary opacity-50 cursor-not-allowed" disabled>
                        Save Grade Offerings
                    </button>
                </div>
            </form>
        </div>

        {{-- ================= STUDENT POPULATION ================= --}}
        <div id="studentPopulation" class="bg-white rounded-xl shadow p-6" tabindex="-1">

            {{-- Section header with Import SF6 button --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-semibold">Student Population</h3>
                    <p class="text-sm text-gray-600">Manage student population data per school year</p>
                </div>
                <a href="{{ route('import.sf6.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600
                          text-white text-sm font-medium hover:bg-emerald-700 shadow transition
                          whitespace-nowrap self-start sm:self-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 3v13m0 0l-4-4m4 4l4-4"/>
                    </svg>
                    Import SF6
                </a>
            </div>

            {{-- School Year Selection --}}
            <div class="mb-6">
                <label class="text-sm font-medium text-gray-700 mb-2 block">Select School Year</label>
                <select id="schoolYearSelect"
                    class="w-full md:w-1/3 px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none transition">
                    <option value="">-- Select School Year --</option>
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy->id }}" {{ $selectedSyId == $sy->id ? 'selected' : '' }}>
                            {{ $sy->year_start }} - {{ $sy->year_end }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Population Form (shown when school year is selected) --}}
            <div id="populationFormContainer" class="{{ $selectedSyId ? '' : 'hidden' }}">
                <form id="populationForm" action="{{ route('school.population.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="sy_id" id="sy_id" value="{{ $selectedSyId }}">

                    {{-- Instructions --}}
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> Only grades offered by your school will be shown below.
                            Update your grade offerings above if you need to add or remove grades.
                        </p>
                    </div>

                    {{-- Population Inputs Grid --}}
                    <div class="space-y-4">
                        @php
                            $grades = [
                                'K' => 'Kindergarten',
                                'g1' => 'Grade 1',
                                'g2' => 'Grade 2',
                                'g3' => 'Grade 3',
                                'g4' => 'Grade 4',
                                'g5' => 'Grade 5',
                                'g6' => 'Grade 6',
                                'g7' => 'Grade 7',
                                'g8' => 'Grade 8',
                                'g9' => 'Grade 9',
                                'g10' => 'Grade 10',
                                'g11' => 'Grade 11',
                                'g12' => 'Grade 12',
                                'ng'  => 'Non-Graded',
                            ];
                        @endphp

                        @foreach($grades as $key => $label)
                            @php
                                $isOffered = $gradeOffering && $gradeOffering->{$key} === 'yes';
                                if ($key === 'K') {
                                    $maleField   = 'k_m';
                                    $femaleField = 'k_f';
                                    $totalField  = 'k_total';
                                } elseif ($key === 'ng') {
                                    $maleField   = 'ng_m';
                                    $femaleField = 'ng_f';
                                    $totalField  = 'ng_total';
                                } else {
                                    $maleField   = strtolower($key) . '_m';
                                    $femaleField = strtolower($key) . '_f';
                                    $totalField  = strtolower($key) . '_total';
                                }
                                // Non-Graded row gets a distinct amber background to stand out
                                $rowBg = $key === 'ng' ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50';
                            @endphp

                            @if($isOffered)
                                <div class="population-row grid grid-cols-1 md:grid-cols-12 gap-4 items-center p-4 {{ $rowBg }} rounded-lg">
                                    {{-- Grade Label --}}
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-semibold {{ $key === 'ng' ? 'text-amber-700' : 'text-gray-700' }}">
                                            {{ $label }}
                                            @if($key === 'ng')
                                                <span class="ml-1 text-xs font-normal text-amber-500">(Non-Graded)</span>
                                            @endif
                                        </label>
                                    </div>

                                    {{-- Male Input --}}
                                    <div class="md:col-span-3">
                                        <label class="text-xs text-gray-500 mb-1 block">Male</label>
                                        <input type="number"
                                            name="{{ $maleField }}"
                                            value="{{ old($maleField, $population->{$maleField} ?? 0) }}"
                                            min="0"
                                            class="population-input input"
                                            data-grade="{{ $key }}"
                                            data-type="male">
                                    </div>

                                    {{-- Female Input --}}
                                    <div class="md:col-span-3">
                                        <label class="text-xs text-gray-500 mb-1 block">Female</label>
                                        <input type="number"
                                            name="{{ $femaleField }}"
                                            value="{{ old($femaleField, $population->{$femaleField} ?? 0) }}"
                                            min="0"
                                            class="population-input input"
                                            data-grade="{{ $key }}"
                                            data-type="female">
                                    </div>

                                    {{-- Total (Read-only) --}}
                                    <div class="md:col-span-3">
                                        <label class="text-xs text-gray-500 mb-1 block">Total</label>
                                        <input type="number"
                                            name="{{ $totalField }}"
                                            value="{{ old($totalField, $population->{$totalField} ?? 0) }}"
                                            readonly
                                            class="grade-total input bg-gray-100 font-semibold"
                                            data-grade="{{ $key }}">
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        {{-- Overall Total --}}
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                            <div class="md:col-span-3">
                                <label class="text-sm font-bold text-blue-800">TOTAL POPULATION</label>
                            </div>
                            <div class="md:col-span-3">
                                <input type="number"
                                    id="totalMale"
                                    readonly
                                    class="input bg-blue-100 text-blue-800 font-bold text-center">
                            </div>
                            <div class="md:col-span-3">
                                <input type="number"
                                    id="totalFemale"
                                    readonly
                                    class="input bg-blue-100 text-blue-800 font-bold text-center">
                            </div>
                            <div class="md:col-span-3">
                                <input type="number"
                                    id="grandTotal"
                                    readonly
                                    class="input bg-blue-100 text-blue-800 font-bold text-center">
                            </div>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="flex justify-end mt-6">
                        <button type="button" id="savePopulationBtn" onclick="openPopulationConfirmModal()"
                            class="btn-primary opacity-50 cursor-not-allowed" disabled>
                            Save Population Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= CONFIRM MODAL ================= --}}
        <div id="confirmModal" class="fixed inset-0 hidden z-50 bg-black/50 flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-2">Confirm Save</h3>

                <p class="text-sm text-gray-600 mb-3">You changed the following:</p>
                <ul id="changedFields" class="text-sm text-gray-700 list-disc pl-5 mb-5"></ul>

                <div class="flex justify-end gap-3">
                    <button onclick="closeConfirmModal()" class="btn-secondary">Cancel</button>
                    <button type="button" onclick="submitForm()" id="confirmBtn" class="btn-primary">Yes, Save</button>
                </div>
            </div>
        </div>

        {{-- ================= GRADES CONFIRM MODAL ================= --}}
        <div id="gradesConfirmModal" class="fixed inset-0 hidden z-50 bg-black/50 flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-2">Confirm Grade Offerings</h3>
                <p class="text-sm text-gray-600 mb-3">You have selected the following grades:</p>
                <div id="selectedGradesList" class="text-sm text-gray-700 mb-5 p-3 bg-gray-50 rounded-lg"></div>
                <!-- Warning Section -->
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-5">
                    <p class="font-semibold mb-1">⚠ Important Notice</p>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        <li>Changes in grade offerings will immediately affect student allocations, enrollments and reports.</li>
                        <li>These changes may also affect system-generated statistics and historical data consistency.</li>
                    </ul>
                </div>
                <div class="flex justify-end gap-3">
                    <button onclick="closeGradesConfirmModal()" class="btn-secondary">Cancel</button>
                    <button type="button" onclick="submitGradesForm()" class="btn-primary">Yes, Save</button>
                </div>
            </div>
        </div>

        {{-- ================= POPULATION CONFIRM MODAL ================= --}}
        <div id="populationConfirmModal" class="fixed inset-0 hidden z-50 bg-black/50 flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-2">Confirm Population Data</h3>
                <p class="text-sm text-gray-600 mb-3">You are about to save population data with the following totals:</p>
                <div class="bg-gray-50 p-4 rounded-lg mb-5 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">Total Male:</span>
                        <span id="confirmTotalMale" class="font-semibold text-blue-600">0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700">Total Female:</span>
                        <span id="confirmTotalFemale" class="font-semibold text-pink-600">0</span>
                    </div>
                    <div class="flex justify-between text-sm border-t pt-2">
                        <span class="text-gray-800 font-semibold">Grand Total:</span>
                        <span id="confirmGrandTotal" class="font-bold text-green-600">0</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button onclick="closePopulationConfirmModal()" class="btn-secondary">Cancel</button>
                    <button type="button" onclick="submitPopulationForm()" class="btn-primary">Yes, Save</button>
                </div>
            </div>
        </div>

    </div>

    {{-- ================= STYLES ================= --}}
    <style>
        .input {
            width: 100%;
            border: 2px dashed #d1d5db;
            border-radius: .5rem;
            padding: .5rem .75rem;
        }

        .error {
            font-size: .75rem;
            color: #dc2626;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
            padding: .5rem 1.25rem;
            border-radius: .5rem;
        }

        .btn-secondary {
            border: 1px solid #d1d5db;
            padding: .5rem 1.25rem;
            border-radius: .5rem;
        }
    </style>

    @vite('resources/js/school-profile.js')
@endsection
