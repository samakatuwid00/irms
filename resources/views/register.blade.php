@extends('public-layout.layout')

@section('title', 'Register')

@section('content')
<div class="w-full flex justify-center px-4">
    <div class="bg-white border-2 border-custom-teal rounded-lg p-8 shadow-lg w-full max-w-5xl">
        <h2 class="text-2xl font-bold text-center mb-6 text-custom-dark">Create Account</h2>

        <form method="POST" action="{{ route('register') }}" id="registerForm">
            @csrf

            <!-- Row 1: First | Last | Middle | Extension -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">First Name</label>
                    <input type="text" name="firstname" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Last Name</label>
                    <input type="text" name="lastname" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Middle Name</label>
                    <input type="text" name="middlename" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Extension</label>
                    <select name="extension_name" class="input-field">
                        <option value="" selected disabled>Select</option>
                        <option value="Jr">Jr.</option>
                        <option value="Sr">Sr.</option>
                        <option value="II">II</option>
                        <option value="III">III</option>
                        <option value="IV">IV</option>
                        <option value="V">V</option>
                        <option value="VI">VI</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Gender | Birthday | Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Gender</label>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="gender" value="male" class="mr-1"> Male
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="gender" value="female" class="mr-1"> Female
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="gender" value="other" class="mr-1"> Other
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Birthday</label>
                    <input type="date" name="birthday" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Contact Number</label>
                    <input type="text"
                        name="contact_number"
                        id="contact_number"
                        placeholder="09xx-xxx-xxxx"
                        required
                        class="input-field"
                        maxlength="13"
                        pattern="09\d{2}-\d{3}-\d{4}"
                        title="Enter valid Philippine mobile number: 09xx-xxx-xxxx">
                </div>
            </div>

            <!-- Row 3: Username -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" required placeholder="Username" class="input-field w-full">
            </div>

            <!-- Row 4: Email -->
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" required placeholder="Email" class="input-field w-full">
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
                        <option value="regional">Regional Account</option>
                        <option value="division">Division Account</option>
                        <option value="district">District Account</option>
                        <option value="school">School Account</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">User Type</label>
                    <select name="usertype" class="input-field">
                        <option value="">Select User Type</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
            </div>

            <!-- Row 7: Region | Division | District | School -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div id="regionWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">Region</label>
                    <select name="region" id="region" class="input-field">
                        <option value="">Select Region</option>
                        <option value="region1">Region 1</option>
                        <option value="region2">Region 2</option>
                    </select>
                </div>
                <div id="divisionWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">Division</label>
                    <select name="division" id="division" class="input-field">
                        <option value="">Select Division</option>
                        <option value="division1">Division 1</option>
                        <option value="division2">Division 2</option>
                    </select>
                </div>
                <div id="districtWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">District</label>
                    <select name="district" id="district" class="input-field">
                        <option value="">Select District</option>
                        <option value="district1">District 1</option>
                        <option value="district2">District 2</option>
                    </select>
                </div>
                <div id="schoolWrapper" class="hidden">
                    <label class="block text-sm font-medium mb-1">School</label>
                    <select name="school" id="school" class="input-field">
                        <option value="">Select School</option>
                        <option value="school1">School 1</option>
                        <option value="school2">School 2</option>
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
                <button type="submit" class="w-full sm:w-1/2 bg-custom-yellow text-custom-dark py-2 rounded-md font-semibold hover:bg-custom-yellow-hover transition">Submit</button>
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

<!-- Privacy & Terms Modal -->
<div id="privacyModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-2xl font-bold text-custom-dark">Terms and Data Privacy</h3>
            <button id="closePrivacyModal" class="text-gray-500 hover:text-gray-700 text-3xl leading-none">
                &times;
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex border-b">
            <button id="privacyTab" class="flex-1 py-4 text-center font-medium text-custom-teal border-b-4 border-custom-teal">
                Privacy Policy
            </button>
            <button id="termsTab" class="flex-1 py-4 text-center font-medium text-gray-600 hover:text-custom-teal transition">
                Terms of Service
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto flex-1">
            <!-- Privacy Policy Content -->
            <div id="privacyContent">
                <h4 class="text-xl font-semibold mb-4">Privacy Policy</h4>
                <p class="text-sm text-gray-700 mb-4">
                    Your privacy is important to us. This Privacy Policy explains how the Learning Resource Management System collects, uses, and protects your personal information.
                </p>
                <h5 class="font-semibold mb-2">Information We Collect</h5>
                <ul class="list-disc pl-6 text-sm text-gray-700 mb-4 space-y-1">
                    <li>Personal details (name, email, contact number, etc.)</li>
                    <li>Account credentials</li>
                    <li>Usage data and activity logs</li>
                </ul>
                <h5 class="font-semibold mb-2">How We Use Your Information</h5>
                <p class="text-sm text-gray-700 mb-4">
                    We use your information to provide and improve our services, communicate with you, and ensure system security.
                </p>
                <p class="text-sm text-gray-700 mb-4">
                    We do not sell your personal data to third parties. Data is stored securely and access is restricted to authorized personnel only.
                </p>
            </div>

            <!-- Terms of Service Content -->
            <div id="termsContent" class="hidden">
                <h4 class="text-xl font-semibold mb-4">Terms of Service</h4>
                <p class="text-sm text-gray-700 mb-4">
                    By creating an account, you agree to abide by the following terms:
                </p>
                <ul class="list-disc pl-6 text-sm text-gray-700 mb-4 space-y-1">
                    <li>Use the system responsibly and for educational purposes only.</li>
                    <li>Do not share your account credentials.</li>
                    <li>Respect intellectual property rights of shared resources.</li>
                    <li>We reserve the right to suspend accounts violating these terms.</li>
                </ul>
                <p class="text-sm text-gray-700">
                    These terms may be updated periodically. Continued use constitutes acceptance of changes.
                </p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t text-center">
            <button id="closePrivacyModalBottom" class="bg-custom-yellow text-custom-dark px-8 py-3 rounded-md font-semibold hover:bg-custom-yellow-hover transition">
                Close
            </button>
        </div>
    </div>
</div>
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
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordField = document.getElementById('password');
    if (passwordToggle && passwordField) {
        passwordToggle.addEventListener('click', () => {
            passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
            passwordToggle.classList.toggle('fa-eye');
            passwordToggle.classList.toggle('fa-eye-slash');
        });
    }

    const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
    const confirmPasswordField = document.getElementById('confirmPassword');
    if (confirmPasswordToggle && confirmPasswordField) {
        confirmPasswordToggle.addEventListener('click', () => {
            confirmPasswordField.type = confirmPasswordField.type === 'password' ? 'text' : 'password';
            confirmPasswordToggle.classList.toggle('fa-eye');
            confirmPasswordToggle.classList.toggle('fa-eye-slash');
        });
    }

    const authorityLevel = document.getElementById('authority_level');
    const regionWrapper = document.getElementById('regionWrapper');
    const divisionWrapper = document.getElementById('divisionWrapper');
    const districtWrapper = document.getElementById('districtWrapper');
    const schoolWrapper = document.getElementById('schoolWrapper');

    if (authorityLevel) {
        authorityLevel.addEventListener('change', function () {
            const value = this.value;
            [regionWrapper, divisionWrapper, districtWrapper, schoolWrapper].forEach(el => {
                if (el) el.classList.add('hidden');
            });

            if (value === 'regional' && regionWrapper) regionWrapper.classList.remove('hidden');
            if (value === 'division') {
                if (regionWrapper) regionWrapper.classList.remove('hidden');
                if (divisionWrapper) divisionWrapper.classList.remove('hidden');
            }
            if (value === 'district') {
                if (regionWrapper) regionWrapper.classList.remove('hidden');
                if (divisionWrapper) divisionWrapper.classList.remove('hidden');
                if (districtWrapper) districtWrapper.classList.remove('hidden');
            }
            if (value === 'school') {
                if (regionWrapper) regionWrapper.classList.remove('hidden');
                if (divisionWrapper) divisionWrapper.classList.remove('hidden');
                if (districtWrapper) districtWrapper.classList.remove('hidden');
                if (schoolWrapper) schoolWrapper.classList.remove('hidden');
            }
        });
    }

    const contactField = document.getElementById('contact_number');
    if (contactField) {
        contactField.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 6) {
                value = value.replace(/(\d{4})(\d{3})(\d{0,4})/, '$1-$2-$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{4})(\d{0,3})/, '$1-$2');
            }
            this.value = value;
        });

        document.getElementById('registerForm').addEventListener('submit', function () {
            contactField.value = contactField.value.replace(/\D/g, '');
        });
    }

    const openModalBtn = document.getElementById('openPrivacyModal');
    const modal = document.getElementById('privacyModal');
    const closeBtns = document.querySelectorAll('#closePrivacyModal, #closePrivacyModalBottom');
    const privacyTab = document.getElementById('privacyTab');
    const termsTab = document.getElementById('termsTab');
    const privacyContent = document.getElementById('privacyContent');
    const termsContent = document.getElementById('termsContent');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });
    }

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    if (privacyTab && termsTab) {
        privacyTab.addEventListener('click', () => {
            privacyTab.classList.add('text-custom-teal', 'border-custom-teal');
            privacyTab.classList.remove('text-gray-600');
            termsTab.classList.remove('text-custom-teal', 'border-custom-teal');
            termsTab.classList.add('text-gray-600');
            privacyContent.classList.remove('hidden');
            termsContent.classList.add('hidden');
        });

        termsTab.addEventListener('click', () => {
            termsTab.classList.add('text-custom-teal', 'border-custom-teal');
            termsTab.classList.remove('text-gray-600');
            privacyTab.classList.remove('text-custom-teal', 'border-custom-teal');
            privacyTab.classList.add('text-gray-600');
            termsContent.classList.remove('hidden');
            privacyContent.classList.add('hidden');
        });
    }
</script>
@endpush
