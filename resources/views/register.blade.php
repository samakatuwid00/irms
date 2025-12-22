@extends('public-layout.layout')

@section('title', 'Register')

@section('content')
<div class="w-full flex justify-center px-4">
    <div id="flashContainer" class="fixed top-4 right-4 z-50 space-y-4 max-w-md w-full pointer-events-none">
        @if(session('success'))
            <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg flex items-center justify-between pointer-events-auto animate-fade-in">
                <span>{{ session('success') }}</span>
                <button type="button" onclick="closeFlash('successMessage')" class="ml-4 text-green-800 hover:text-green-900 font-bold text-xl">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg flex items-center justify-between pointer-events-auto animate-fade-in">
                <span>{{ session('error') }}</span>
                <button type="button" onclick="closeFlash('errorMessage')" class="ml-4 text-red-800 hover:text-red-900 font-bold text-xl">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div id="validationErrors" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg pointer-events-auto animate-fade-in">
                <div class="flex items-center justify-between mb-2">
                    <strong>Please fix the following errors:</strong>
                    <button type="button" onclick="closeFlash('validationErrors')" class="text-red-800 hover:text-red-900 font-bold text-xl">&times;</button>
                </div>
                <ul class="list-disc pl-6 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <div class="bg-white border-2 border-custom-teal rounded-lg p-8 shadow-lg w-full max-w-5xl">
        <div class="flex justify-end">
            <a href="{{ route('index') }}"
            class="inline-flex items-center text-custom-teal hover:text-custom-dark transition">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-1"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7" />
                </svg>
                <span class="text-sm font-medium">Back</span>
            </a>
        </div>

        <h2 class="text-2xl font-bold text-center mb-6 text-custom-dark">Create Account</h2>

        <form method="POST" action="{{ route('register.submit') }}" id="registerForm">
            @csrf
            <!-- Row 1: First | Last | Middle | Extension -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">First Name</label>
                    <input type="text" value="{{ old('firstname') }}" name="firstname" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Last Name</label>
                    <input type="text" value="{{ old('lastname') }}" name="lastname" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Middle Name</label>
                    <input type="text" value="{{ old('middlename') }}" name="middlename" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Extension</label>
                    <select name="extension_name" class="input-field">
                        <option value="" {{ old('extension_name') == '' ? 'selected' : '' }}>N/A</option>
                        <option value="Jr" {{ old('extension_name') == 'Jr' ? 'selected' : '' }}>Jr.</option>
                        <option value="Sr" {{ old('extension_name') == 'Sr' ? 'selected' : '' }}>Sr.</option>
                        <option value="II" {{ old('extension_name') == 'II' ? 'selected' : '' }}>II</option>
                        <option value="III" {{ old('extension_name') == 'III' ? 'selected' : '' }}>III</option>
                        <option value="IV" {{ old('extension_name') == 'IV' ? 'selected' : '' }}>IV</option>
                        <option value="V" {{ old('extension_name') == 'V' ? 'selected' : '' }}>V</option>
                        <option value="VI" {{ old('extension_name') == 'VI' ? 'selected' : '' }}>VI</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Gender | Birthday | Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Gender</label>
                        <div class="flex items-center space-x-4">
                        <label class="flex items-center"><input type="radio" name="gender" value="male" {{ old('gender') == 'male' ? 'checked' : '' }} class="mr-2"> Male</label>
                        <label class="flex items-center"><input type="radio" name="gender" value="female" {{ old('gender') == 'female' ? 'checked' : '' }} class="mr-2"> Female</label>
                        <label class="flex items-center"><input type="radio" name="gender" value="other" {{ old('gender') == 'other' ? 'checked' : '' }} class="mr-2"> Other</label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Birthday</label>
                    <input type="date" value="{{ old('birthday') }}" name="birthday" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Contact Number</label>
                    <input type="text"
                        name="contact_number"
                        id="contact_number"
                        placeholder="09xx-xxx-xxxx"
                        required
                        value = "{{ old('contact_number') }}"
                        class="input-field"
                        maxlength="13"
                        pattern="09\d{2}-\d{3}-\d{4}"
                        title="Enter valid Philippine mobile number: 09xx-xxx-xxxx">
                </div>
            </div>

            <!-- Row 3: Username -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required placeholder="Username" class="input-field w-full">
            </div>

            <!-- Row 4: Email -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="Email" class="input-field w-full">
            </div>

            <!-- Row 5: Password | Confirm Password with eye -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="relative password-container">
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required class="w-full px-3 py-2 pr-10 border border-custom-teal rounded-md focus:outline-none focus:ring-2 focus:ring-custom-teal">
                        <i id="passwordToggle" class="fas fa-eye password-toggle"></i>
                    </div>
                </div>
                <div class="relative password-container">
                    <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" id="confirmPassword" name="password_confirmation" required class="w-full px-3 py-2 pr-10 border border-custom-teal rounded-md focus:outline-none focus:ring-2 focus:ring-custom-teal">
                        <i id="confirmPasswordToggle" class="fas fa-eye password-toggle"></i>
                    </div>
                </div>
            </div>

            <!-- Row 6: Authority Level | User Type -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Authority Level</label>
                    <select name="authority_level" id="authority_level" class="input-field">
                        <option value="" selected disabled>Select Authority Level</option>
                        <option value="4" {{ old('authority_level') == '4' ? 'selected' : '' }}>Regional Account</option>
                        <option value="3" {{ old('authority_level') == '3' ? 'selected' : '' }}>Division Account</option>
                        <option value="2" {{ old('authority_level') == '2' ? 'selected' : '' }}>District Account</option>
                        <option value="1" {{ old('authority_level') == '1' ? 'selected' : '' }}>School Account</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">User Type</label>
                    <select name="usertype" class="input-field">
                        <option selected disabled>Select User Type</option>
                    </select>
                </div>
            </div>

            <!-- Row 7: Region | Division | District | School -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div id="regionWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">Region</label>
                    <select name="region" id="region" class="input-field">
                        <option selected disabled>Select Region</option>
                            @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ old('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->region_name }}
                            </option>
                            @endforeach
                    </select>
                </div>
                <div id="divisionWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">Division</label>
                    <select name="division" id="division" class="input-field">
                        <option selected disabled>Select Division</option>
                    </select>
                </div>
                <div id="districtWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">District</label>
                    <select name="district" id="district" class="input-field">
                        <option selected disabled>Select District</option>
                    </select>
                </div>
                <div id="schoolWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">School</label>
                    <select name="school" id="school" class="input-field">
                        <option selected disabled>Select School</option>
                    </select>
                </div>
            </div>

            <!-- Terms -->
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="agree" required class="mr-2">
                    I agree to the
                    <a href="javascript:void(0)" id="openPrivacyModal" class="text-custom-teal hover:underline ml-1">
                        Terms and Data Privacy
                    </a>
                </label>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                <button type="submit" id="submitBtn" class="w-full sm:w-1/2 bg-custom-yellow text-custom-dark py-2 rounded-md font-semibold hover:bg-custom-yellow-hover transition">Submit</button>
                <button type="reset" class="w-full sm:w-1/2 bg-gray-300 text-custom-dark py-2 rounded-md font-semibold hover:bg-gray-400 transition">Clear</button>
            </div>

            <!-- Already have an account -->
            <div class="mt-6 text-center">
                <span class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('index') }}" class="text-custom-teal font-semibold hover:underline">
                        Login
                    </a>
                </span>
            </div>
        </form>
    </div>
</div>

@include('public-components.privacy-and-policy-modal')

@endsection

@push('styles')
<style>
    .input-field {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #127681;
        border-radius: 0.375rem;
        outline: none;
        transition: all 0.2s;
    }

    .input-field:focus {
        border-color: #127681;
        box-shadow: 0 0 0 2px rgba(18, 118, 129, 0.2);
    }
</style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /* ================= FLASH MESSAGES ================= */
            window.closeFlash = function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.style.transition = 'all 0.4s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-10px)';
                setTimeout(() => el.remove(), 400);
            };

            const successMsg = document.getElementById('successMessage');
            if (successMsg) {
                setTimeout(() => closeFlash('successMessage'), 6000);
            }

            /* ================= PASSWORD TOGGLE ================= */
            const togglePassword = (fieldId, toggleId) => {
                const field = document.getElementById(fieldId);
                const toggle = document.getElementById(toggleId);
                if (!field || !toggle) return;

                toggle.addEventListener('click', () => {
                    field.type = field.type === 'password' ? 'text' : 'password';
                    toggle.classList.toggle('fa-eye');
                    toggle.classList.toggle('fa-eye-slash');
                });
            };

            togglePassword('password', 'passwordToggle');
            togglePassword('confirmPassword', 'confirmPasswordToggle');

            /* ================= ELEMENTS ================= */
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');

            const authorityLevel = document.getElementById('authority_level');
            const usertypeSelect = document.querySelector('select[name="usertype"]');

            const regionWrapper   = document.getElementById('regionWrapper');
            const divisionWrapper = document.getElementById('divisionWrapper');
            const districtWrapper = document.getElementById('districtWrapper');
            const schoolWrapper   = document.getElementById('schoolWrapper');

            const regionSelect   = document.getElementById('region');
            const divisionSelect = document.getElementById('division');
            const districtSelect = document.getElementById('district');
            const schoolSelect   = document.getElementById('school');

            const contactField = document.getElementById('contact_number');

            /* ================= DATA FROM BLADE ================= */
            const divisions = @json($divisions);
            const districts = @json($districts);
            const schools   = @json($schools);
            const usertypes = @json($usertypes);

            const oldData = {
                authority_level: "{{ old('authority_level') }}",
                region: "{{ old('region') }}",
                division: "{{ old('division') }}",
                district: "{{ old('district') }}",
                school: "{{ old('school') }}",
                usertype: "{{ old('usertype') }}"
            };

            /* ================= HELPERS ================= */
            const hideAllWrappers = () => {
                [regionWrapper, divisionWrapper, districtWrapper, schoolWrapper]
                    .forEach(el => el && el.classList.add('hidden'));
            };

            const populateSelect = (select, items, textKey, valueKey) => {
                if (!select) return;
                const label = select.name.charAt(0).toUpperCase() + select.name.slice(1);
                select.innerHTML = `<option selected disabled>Select ${label}</option>`;
                items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item[valueKey];
                    opt.textContent = item[textKey];
                    select.appendChild(opt);
                });
            };

            const filterUserTypesByLevel = (level) => {
                const filtered = usertypes.filter(u => u.level == level);
                populateSelect(usertypeSelect, filtered, 'type_name', 'id');
            };

            const applyAuthorityLevel = (level) => {
                hideAllWrappers();

                if (level === '4') regionWrapper.classList.remove('hidden');
                if (level === '3') {
                    regionWrapper.classList.remove('hidden');
                    divisionWrapper.classList.remove('hidden');
                }
                if (level === '2') {
                    regionWrapper.classList.remove('hidden');
                    divisionWrapper.classList.remove('hidden');
                    districtWrapper.classList.remove('hidden');
                }
                if (level === '1') {
                    regionWrapper.classList.remove('hidden');
                    divisionWrapper.classList.remove('hidden');
                    districtWrapper.classList.remove('hidden');
                    schoolWrapper.classList.remove('hidden');
                }

                filterUserTypesByLevel(level);
            };

            /* ================= CONTACT NUMBER ================= */
            const formatContactNumber = (value) => {
                value = value.replace(/\D/g, '').slice(0, 11);

                if (value.length > 6) {
                    return value.replace(/(\d{4})(\d{3})(\d{0,4})/, '$1-$2-$3');
                }
                if (value.length > 3) {
                    return value.replace(/(\d{4})(\d{0,3})/, '$1-$2');
                }
                return value;
            };

            contactField?.addEventListener('input', function () {
                this.value = formatContactNumber(this.value);
            });

            /* ================= EVENTS ================= */
            authorityLevel?.addEventListener('change', function () {
                applyAuthorityLevel(this.value);
            });

            regionSelect?.addEventListener('change', () => {
                const filtered = divisions.filter(d => d.region_id == regionSelect.value);
                populateSelect(divisionSelect, filtered, 'division_name', 'id');
                districtSelect.innerHTML = '<option selected disabled>Select District</option>';
                schoolSelect.innerHTML = '<option selected disabled>Select School</option>';
            });

            divisionSelect?.addEventListener('change', () => {
                const filtered = districts.filter(d => d.division_id == divisionSelect.value);
                populateSelect(districtSelect, filtered, 'district_name', 'id');
                schoolSelect.innerHTML = '<option selected disabled>Select School</option>';
            });

            districtSelect?.addEventListener('change', () => {
                const filtered = schools.filter(s => s.district_id == districtSelect.value);
                populateSelect(schoolSelect, filtered, 'school_name', 'id');
            });

            /* ================= RESTORE OLD INPUT ================= */
            if (oldData.authority_level) {
                authorityLevel.value = oldData.authority_level;
                applyAuthorityLevel(oldData.authority_level);
            }

            if (oldData.region) {
                regionSelect.value = oldData.region;
                populateSelect(
                    divisionSelect,
                    divisions.filter(d => d.region_id == oldData.region),
                    'division_name',
                    'id'
                );
            }

            if (oldData.division) {
                divisionSelect.value = oldData.division;
                populateSelect(
                    districtSelect,
                    districts.filter(d => d.division_id == oldData.division),
                    'district_name',
                    'id'
                );
            }

            if (oldData.district) {
                districtSelect.value = oldData.district;
                populateSelect(
                    schoolSelect,
                    schools.filter(s => s.district_id == oldData.district),
                    'school_name',
                    'id'
                );
            }

            if (oldData.school) {
                schoolSelect.value = oldData.school;
            }

            if (oldData.usertype) {
                usertypeSelect.value = oldData.usertype;
            }

            // Restore formatted contact number after validation error
            if (contactField && contactField.value) {
                contactField.value = formatContactNumber(contactField.value);
            }

            /* ================= SUBMIT ================= */
            form?.addEventListener('submit', function (e) {

                if (!document.querySelector('input[name="agree"]').checked) {
                    e.preventDefault();
                    alert('You must agree to the Privacy and Terms to register.');
                    return;
                }

                if (!confirm('Are you sure you want to submit the registration?')) {
                    e.preventDefault();
                    return;
                }

                // 🔒 FORCE CLEAN NUMBER (NO DASHES)
                if (contactField) {
                    contactField.value = contactField.value.replace(/\D/g, '');
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            });

        });
    </script>
@endpush
