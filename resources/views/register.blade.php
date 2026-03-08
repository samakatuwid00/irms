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

        <form method="POST" action="{{ route('register.submit') }}" id="registerForm" autocomplete="on">
            @csrf
            <!-- Row 1: First | Last | Middle | Extension -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div>
                    {{-- FIX: added id + autocomplete --}}
                    <label for="firstname" class="block text-sm font-medium mb-1">First Name</label>
                    <input type="text" value="{{ old('firstname') }}" id="firstname" name="firstname" required autocomplete="given-name" class="input-field">
                </div>
                <div>
                    {{-- FIX: added id + autocomplete --}}
                    <label for="lastname" class="block text-sm font-medium mb-1">Last Name</label>
                    <input type="text" value="{{ old('lastname') }}" id="lastname" name="lastname" required autocomplete="family-name" class="input-field">
                </div>
                <div>
                    {{-- FIX: added id + autocomplete --}}
                    <label for="middlename" class="block text-sm font-medium mb-1">Middle Name</label>
                    <input type="text" value="{{ old('middlename') }}" id="middlename" name="middlename" autocomplete="additional-name" class="input-field">
                </div>
                <div>
                    {{-- FIX: added id + autocomplete --}}
                    <label for="extension_name" class="block text-sm font-medium mb-1">Extension</label>
                    <select name="extension_name" id="extension_name" autocomplete="honorific-suffix" class="input-field">
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
                    {{-- FIX: gender fieldset — radio inputs now have ids so labels use for= correctly --}}
                    <fieldset>
                        <legend class="block text-sm font-medium mb-1">Gender</legend>
                        <div class="flex items-center space-x-4">
                            <label for="gender_male" class="flex items-center">
                                <input type="radio" id="gender_male" name="gender" value="male" {{ old('gender') == 'male' ? 'checked' : '' }} class="mr-2"> Male
                            </label>
                            <label for="gender_female" class="flex items-center">
                                <input type="radio" id="gender_female" name="gender" value="female" {{ old('gender') == 'female' ? 'checked' : '' }} class="mr-2"> Female
                            </label>
                            <label for="gender_other" class="flex items-center">
                                <input type="radio" id="gender_other" name="gender" value="other" {{ old('gender') == 'other' ? 'checked' : '' }} class="mr-2"> Other
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div>
                    {{-- FIX: added id + autocomplete --}}
                    <label for="birthday" class="block text-sm font-medium mb-1">Birthday</label>
                    <input type="date" value="{{ old('birthday') }}" id="birthday" name="birthday" autocomplete="off" class="input-field">
                </div>
                <div>
                    {{-- FIX: autocomplete="tel" added (id was already present) --}}
                    <label for="contact_number" class="block text-sm font-medium mb-1">Contact Number</label>
                    <input type="text"
                        name="contact_number"
                        id="contact_number"
                        placeholder="09xx-xxx-xxxx"
                        required
                        value="{{ old('contact_number') }}"
                        autocomplete="tel"
                        class="input-field"
                        maxlength="13"
                        pattern="09\d{2}-\d{3}-\d{4}"
                        title="Enter valid Philippine mobile number: 09xx-xxx-xxxx">
                </div>
            </div>

            <!-- Row 3: Username -->
            <div class="mb-4">
                {{-- FIX: added id + autocomplete --}}
                <label for="username" class="block text-sm font-medium mb-1">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required placeholder="Username" autocomplete="username" class="input-field w-full">
            </div>

            <!-- Row 4: Email -->
            <div class="mb-4">
                {{-- FIX: added id + autocomplete --}}
                <label for="email" class="block text-sm font-medium mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Email" autocomplete="email" class="input-field w-full">
            </div>

            <!-- Row 5: Password | Confirm Password with eye -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="relative password-container">
                    {{-- FIX: id="password" already present; added autocomplete --}}
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required autocomplete="new-password" class="w-full px-3 py-2 pr-10 border border-custom-teal rounded-md focus:outline-none focus:ring-2 focus:ring-custom-teal">
                        <i id="passwordToggle" class="fas fa-eye password-toggle"></i>
                    </div>
                </div>
                <div class="relative password-container">
                    {{-- FIX: for= was pointing at name "password_confirmation"; now points at id "confirmPassword". Added autocomplete. --}}
                    <label for="confirmPassword" class="block text-sm font-medium mb-1">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" id="confirmPassword" name="password_confirmation" required autocomplete="new-password" class="w-full px-3 py-2 pr-10 border border-custom-teal rounded-md focus:outline-none focus:ring-2 focus:ring-custom-teal">
                        <i id="confirmPasswordToggle" class="fas fa-eye password-toggle"></i>
                    </div>
                </div>
            </div>

            <!-- Row 6: Authority Level | User Type -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    {{-- FIX: id already present; added autocomplete=off (app-specific field) --}}
                    <label for="authority_level" class="block text-sm font-medium mb-1">Authority Level</label>
                    <select name="authority_level" id="authority_level" autocomplete="off" class="input-field">
                        <option value="" selected disabled>Select Authority Level</option>
                        <option value="4" {{ old('authority_level') == '4' ? 'selected' : '' }}>Regional Account</option>
                        <option value="3" {{ old('authority_level') == '3' ? 'selected' : '' }}>Division Account</option>
                        <option value="2" {{ old('authority_level') == '2' ? 'selected' : '' }}>District Account</option>
                        <option value="1" {{ old('authority_level') == '1' ? 'selected' : '' }}>School Account</option>
                    </select>
                </div>
                <div>
                    {{-- FIX: added id + autocomplete=off --}}
                    <label for="usertype" class="block text-sm font-medium mb-1">User Type</label>
                    <select name="usertype" id="usertype" autocomplete="off" class="input-field">
                        <option selected disabled>Select User Type</option>
                    </select>
                </div>
            </div>

            <!-- Row 7: Region | Division | District | School -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
                <div id="regionWrapper" class="hidden">
                    {{-- FIX: added autocomplete=off --}}
                    <label for="region" class="block text-sm font-medium mb-1">Region</label>
                    <select name="region" id="region" autocomplete="off" class="input-field">
                        <option selected disabled>Select Region</option>
                            @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ old('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->region_name }}
                            </option>
                            @endforeach
                    </select>
                </div>
                <div id="divisionWrapper" class="hidden">
                    {{-- FIX: added autocomplete=off --}}
                    <label for="division" class="block text-sm font-medium mb-1">Division</label>
                    <select name="division" id="division" autocomplete="off" class="input-field">
                        <option selected disabled>Select Division</option>
                    </select>
                </div>
                <div id="districtWrapper" class="hidden">
                    {{-- FIX: added autocomplete=off --}}
                    <label for="district" class="block text-sm font-medium mb-1">District</label>
                    <select name="district" id="district" autocomplete="off" class="input-field">
                        <option selected disabled>Select District</option>
                    </select>
                </div>
                <div id="schoolWrapper" class="hidden">
                    {{-- FIX: added autocomplete=off --}}
                    <label for="school" class="block text-sm font-medium mb-1">School</label>
                    <select name="school" id="school" autocomplete="off" class="input-field">
                        <option selected disabled>Select School</option>
                    </select>
                </div>
            </div>

            <!-- Terms -->
            <div class="mb-4">
                {{-- FIX: added id + for= on label so it is properly associated --}}
                <label for="agree" class="flex items-center">
                    <input type="checkbox" id="agree" name="agree" required class="mr-2">
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
     window.__REGISTER__ = {
       divisions : @json($divisions),
       districts : @json($districts),
       schools   : @json($schools),
       usertypes : @json($usertypes),
       old: {
         authority_level : "{{ old('authority_level') }}",
         region          : "{{ old('region') }}",
         division        : "{{ old('division') }}",
         district        : "{{ old('district') }}",
         school          : "{{ old('school') }}",
         usertype        : "{{ old('usertype') }}",
         contact_number  : "{{ old('contact_number') }}",
       }
     };
   </script>
   @vite('resources/js/register.js')
@endpush