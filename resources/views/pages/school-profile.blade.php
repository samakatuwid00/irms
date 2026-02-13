@extends('pages.layout.layout')

@section('title', 'School Profile')
@section('page-title', 'School Profile')

@section('header-title', $school->school_name)
@section('header-subtitle', 'Manage school profile information')
@section('breadcrumb', 'School Profile')

@section('content')

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
                <form id="schoolForm" class="bg-white rounded-xl shadow p-6" action="{{ route('profile.update') }}"
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
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Student Population</h3>
            <p class="text-sm text-gray-600 mb-6">Manage student population data per school year</p>

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
                            ];
                        @endphp

                        @foreach($grades as $key => $label)
                            @php
                                $isOffered = $gradeOffering && $gradeOffering->{$key} === 'yes';
                                $maleField = $key === 'K' ? 'k_m' : strtolower($key) . '_m';
                                $femaleField = $key === 'K' ? 'k_f' : strtolower($key) . '_f';
                                $totalField = $key === 'K' ? 'k_total' : strtolower($key) . '_total';
                            @endphp

                            @if($isOffered)
                                <div class="population-row grid grid-cols-1 md:grid-cols-12 gap-4 items-center p-4 bg-gray-50 rounded-lg">
                                    {{-- Grade Label --}}
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-semibold text-gray-700">{{ $label }}</label>
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

    <!-- ================= SCRIPTS ================= -->
    <script>
        const form = document.getElementById('schoolForm');
        const saveBtn = document.getElementById('saveBtn');
        const modal = document.getElementById('confirmModal');
        const changedList = document.getElementById('changedFields');
        const alertBox = document.getElementById('alertBox');
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logoPreview');
        const logoSubmitBtn = document.getElementById('logoSubmitBtn');

        const originalData = Object.fromEntries(new FormData(form).entries());

        // Enable/disable Save button
        form.addEventListener('input', toggleSaveButton);

        function hasChanges() {
            const current = new FormData(form);
            return [...current.entries()].some(([k, v]) => v !== (originalData[k] ?? ''));
        }

        function toggleSaveButton() {
            const dirty = hasChanges();
            saveBtn.disabled = !dirty;
            saveBtn.classList.toggle('opacity-50', !dirty);
            saveBtn.classList.toggle('cursor-not-allowed', !dirty);
        }

        // Modal logic
        function openConfirmModal() {
            changedList.innerHTML = '';
            getChangedFields().forEach(f => {
                changedList.innerHTML += `<li>${f}</li>`;
            });
            modal.classList.remove('hidden');
        }

        function closeConfirmModal() {
            modal.classList.add('hidden');
        }

        function getChangedFields() {
            const current = new FormData(form);
            return [...current.entries()]
                .filter(([k, v]) => v !== (originalData[k] ?? ''))
                .map(([k]) => k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
        }

        // Submit form via normal POST (reload page)
        function submitForm() {
            form.submit();
        }

        // Flash message
        let alertTimeout = null;

        function closeAlert() {
            clearTimeout(alertTimeout);
            if (alertBox) alertBox.classList.add('hidden');
        }
        if (alertBox) {
            alertTimeout = setTimeout(() => {
                alertBox.classList.add('hidden');
            }, 6000);
        }

        // Logo preview and submit button enable
        function previewLogo(event) {
            if (event.target.files && event.target.files[0]) {
                const file = event.target.files[0];

                // Validate file size (2MB max)
                if (file.size > 2048 * 1024) {
                    alert('File size must be less than 2MB');
                    event.target.value = '';
                    logoSubmitBtn.disabled = true;
                    logoSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG or PNG)');
                    event.target.value = '';
                    logoSubmitBtn.disabled = true;
                    logoSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    return;
                }

                // Preview image
                logoPreview.src = URL.createObjectURL(file);

                // Enable submit button
                logoSubmitBtn.disabled = false;
                logoSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        if (logoInput) {
            logoInput.addEventListener('change', previewLogo);
        }

        // ================= DATE ESTABLISHED SWITCH =================
        function switchToDate() {
            document.getElementById('date_display').classList.add('hidden');
            const dateInput = document.getElementById('date_input');
            dateInput.classList.remove('hidden');
            dateInput.focus();
        }

        function switchToText() {
            const dateInput = document.getElementById('date_input');
            const dateDisplay = document.getElementById('date_display');
            dateInput.classList.add('hidden');
            dateDisplay.classList.remove('hidden');
            updateReadableDate();
        }

        function updateReadableDate() {
            const dateInput = document.getElementById('date_input');
            const display = document.getElementById('date_display');
            if (dateInput.value) {
                const date = new Date(dateInput.value);
                display.value = date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } else {
                display.value = '';
            }
        }

        // ================= GRADE OFFERINGS LOGIC =================
        const gradesForm = document.getElementById('gradeOfferingsForm');
        const saveGradesBtn = document.getElementById('saveGradesBtn');
        const gradesModal = document.getElementById('gradesConfirmModal');
        const selectedGradesList = document.getElementById('selectedGradesList');
        const gradeCheckboxes = document.querySelectorAll('.grade-checkbox');

        // Store original grade selections
        const originalGrades = {};
        gradeCheckboxes.forEach(cb => {
            originalGrades[cb.name] = cb.checked;
        });

        // Enable/disable Save button for grades
        gradesForm.addEventListener('change', toggleSaveGradesButton);

        function hasGradeChanges() {
            return Array.from(gradeCheckboxes).some(cb => cb.checked !== originalGrades[cb.name]);
        }

        function toggleSaveGradesButton() {
            const dirty = hasGradeChanges();
            saveGradesBtn.disabled = !dirty;
            saveGradesBtn.classList.toggle('opacity-50', !dirty);
            saveGradesBtn.classList.toggle('cursor-not-allowed', !dirty);
        }

        // Select/Deselect all grades
        function selectAllGrades() {
            gradeCheckboxes.forEach(cb => cb.checked = true);
            toggleSaveGradesButton();
        }

        function deselectAllGrades() {
            gradeCheckboxes.forEach(cb => cb.checked = false);
            toggleSaveGradesButton();
        }

        // Grades modal logic
        function openGradesConfirmModal() {
            const selected = Array.from(gradeCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => {
                    if (cb.name === 'K') return 'Kindergarten';
                    return `Grade ${cb.name.substring(1)}`;
                });

            if (selected.length === 0) {
                selectedGradesList.innerHTML = '<span class="text-gray-500 italic">No grades selected</span>';
            } else {
                selectedGradesList.innerHTML = selected.join(', ');
            }

            gradesModal.classList.remove('hidden');
        }

        function closeGradesConfirmModal() {
            gradesModal.classList.add('hidden');
        }

        function submitGradesForm() {
            gradesForm.submit();
        }

        // ================= POPULATION LOGIC =================
        const schoolYearSelect = document.getElementById('schoolYearSelect');
        const populationFormContainer = document.getElementById('populationFormContainer');
        const populationForm = document.getElementById('populationForm');
        const savePopulationBtn = document.getElementById('savePopulationBtn');
        const populationModal = document.getElementById('populationConfirmModal');
        const populationInputs = document.querySelectorAll('.population-input');

        // Store original population data
        const originalPopulation = {};
        populationInputs.forEach(input => {
            originalPopulation[input.name] = input.value;
        });

        // School year selection change
        schoolYearSelect.addEventListener('change', function() {
            const syId = this.value;
            if (syId) {
                // Redirect to reload with selected school year
                window.location.href = `{{ route('school-profile') }}?sy_id=${syId}`;
            } else {
                populationFormContainer.classList.add('hidden');
            }
        });

        // Calculate totals per grade
        function calculateGradeTotal(grade) {
            const maleInput = document.querySelector(`input[data-grade="${grade}"][data-type="male"]`);
            const femaleInput = document.querySelector(`input[data-grade="${grade}"][data-type="female"]`);
            const totalInput = document.querySelector(`input[data-grade="${grade}"].grade-total`);

            if (maleInput && femaleInput && totalInput) {
                const male = parseInt(maleInput.value) || 0;
                const female = parseInt(femaleInput.value) || 0;
                totalInput.value = male + female;
            }
        }

        // Calculate overall totals
        function calculateOverallTotals() {
            let totalMale = 0;
            let totalFemale = 0;

            document.querySelectorAll('.population-input[data-type="male"]').forEach(input => {
                totalMale += parseInt(input.value) || 0;
            });

            document.querySelectorAll('.population-input[data-type="female"]').forEach(input => {
                totalFemale += parseInt(input.value) || 0;
            });

            const grandTotal = totalMale + totalFemale;

            document.getElementById('totalMale').value = totalMale;
            document.getElementById('totalFemale').value = totalFemale;
            document.getElementById('grandTotal').value = grandTotal;

            return { totalMale, totalFemale, grandTotal };
        }

        // Update totals on input change
        populationInputs.forEach(input => {
            input.addEventListener('input', function() {
                const grade = this.getAttribute('data-grade');
                calculateGradeTotal(grade);
                calculateOverallTotals();
                toggleSavePopulationButton();
            });
        });

        // Check if population data has changed
        function hasPopulationChanges() {
            return Array.from(populationInputs).some(input => {
                return input.value !== (originalPopulation[input.name] || '0');
            });
        }

        // Toggle save button
        function toggleSavePopulationButton() {
            const dirty = hasPopulationChanges();
            savePopulationBtn.disabled = !dirty;
            savePopulationBtn.classList.toggle('opacity-50', !dirty);
            savePopulationBtn.classList.toggle('cursor-not-allowed', !dirty);
        }

        // Open population confirmation modal
        function openPopulationConfirmModal() {
            const totals = calculateOverallTotals();
            document.getElementById('confirmTotalMale').textContent = totals.totalMale;
            document.getElementById('confirmTotalFemale').textContent = totals.totalFemale;
            document.getElementById('confirmGrandTotal').textContent = totals.grandTotal;
            populationModal.classList.remove('hidden');
        }

        function closePopulationConfirmModal() {
            populationModal.classList.add('hidden');
        }

        function submitPopulationForm() {
            populationForm.submit();
        }

        // Initialize totals on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate totals for each grade
            const grades = ['K', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'g8', 'g9', 'g10', 'g11', 'g12'];
            grades.forEach(grade => calculateGradeTotal(grade));

            // Calculate overall totals
            calculateOverallTotals();
        });
    </script>
@endsection
