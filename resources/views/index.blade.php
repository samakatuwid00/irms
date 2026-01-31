@extends('public-layout.layout')

@section('title', 'Login')

@section('content')
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-200">
            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-md relative transition-opacity duration-300">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="font-medium">{{ $errors->first() }}</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.parentElement.remove(), 300);"
                                class="text-red-500 hover:text-red-700 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Error Message --}}
            @if(session('error'))
                <div id="error-alert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-md relative transition-opacity duration-300">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="font-medium">{{ session('error') }}</p>
                        </div>

                        <button onclick="this.parentElement.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.parentElement.remove(), 300);"
                                class="text-red-500 hover:text-red-700 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Success Message --}}
            @if(session('success'))
                <div id="success-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-8 shadow-md relative transition-opacity duration-300">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-3 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>

                        <button onclick="this.parentElement.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.parentElement.remove(), 300);"
                                class="text-green-500 hover:text-green-700 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
            <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Login</h2>

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <!-- Username / Email Field -->
                <div class="mb-6 relative">
                    <input type="text" id="username" name="username" placeholder=" " required value="{{ old('username') }}"
                           class="w-full px-3 py-2 pt-4 pb-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent peer @error('username') border-red-500 @enderror">
                    <label for="username"
                           class="absolute left-4 top-3 text-gray-500 pointer-events-none transition-all duration-200 peer-focus:-top-2.5 peer-focus:left-3 peer-focus:text-xs peer-focus:bg-white peer-focus:px-2 peer-focus:text-custom-yellow peer-valid:-top-2.5 peer-valid:left-3 peer-valid:text-xs peer-valid:bg-white peer-valid:px-2 peer-valid:text-custom-yellow">
                        Username or Email
                    </label>
                </div>

                <!-- Password Field -->
                <div class="mb-6 relative">
                    <input type="password" id="password" name="password" placeholder=" " required
                           class="w-full px-3  py-2 pt-4 pb-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent peer">
                    <label for="password"
                           class="absolute left-4 top-3 text-gray-500 pointer-events-none transition-all duration-200 peer-focus:-top-2.5 peer-focus:left-3 peer-focus:text-xs peer-focus:bg-white peer-focus:px-2 peer-focus:text-custom-yellow peer-valid:-top-2.5 peer-valid:left-3 peer-valid:text-xs peer-valid:bg-white peer-valid:px-2 peer-valid:text-custom-yellow">
                        Password
                    </label>
                    <i id="passwordToggle" class="fas fa-eye absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700"></i>
                </div>

                <div class="mb-6 flex items-center">
                    <input type="checkbox" id="notRobot" name="notRobot" required class="mr-3 h-4 w-4">
                    <label for="notRobot" class="text-sm text-gray-600">I'm not a robot</label>
                    @error('notRobot')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" id="loginButton"
                        class="w-full bg-custom-yellow text-gray-800 font-semibold py-3 rounded-lg hover:bg-custom-yellow-hover transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="buttonText">Login</span>
                    <span id="buttonLoading" class="hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Logging in...
                    </span>
                </button>
            </form>

            <div class="mt-6 text-center space-y-2">
                <a href="#" class="text-custom-teal text-sm hover:underline">Forgot Password?</a>
                <p class="text-sm text-gray-600">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-custom-teal font-medium hover:underline">Sign up here</a>
                </p>
                <a href=""
                    class="text-custom-teal text-sm font-medium hover:underline flex items-center justify-center gap-1 mt-2">
                        <i class="fas fa-question-circle"></i>
                        Frequently Asked Questions
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const toggle = document.getElementById('passwordToggle');
    const password = document.getElementById('password');

    toggle.addEventListener('click', () => {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    });

    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('#error-alert, #success-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 8000);
        });

        // Login button loading state
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        const buttonLoading = document.getElementById('buttonLoading');

        loginForm.addEventListener('submit', function(e) {
            // Disable the button
            loginButton.disabled = true;

            // Hide "Login" text and show "Logging in..." text
            buttonText.classList.add('hidden');
            buttonLoading.classList.remove('hidden');
        });
    });
</script>
@endpush
