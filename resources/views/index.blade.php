@extends('public-layout.layout')

@section('title', 'Login')

@php
$headerLink = [
    'url' => route('register'),
    'text' => 'Sign Up'
];
@endphp

@section('content')
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg p-8 border">
            <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Login</h2>

            <form id="loginForm">
                <div class="mb-6">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Username or Email
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow">
                        <i id="passwordToggle" class="fas fa-eye password-toggle"></i>
                    </div>
                </div>

                <div class="mb-6 flex items-center">
                    <input type="checkbox" id="notRobot" name="notRobot" required class="mr-3 h-4 w-4">
                    <label for="notRobot" class="text-sm text-gray-600">I'm not a robot</label>
                </div>

                <button type="submit"
                        class="w-full bg-custom-yellow text-gray-800 font-semibold py-3 rounded-lg hover:bg-custom-yellow-hover transition duration-300">
                    Login
                </button>
            </form>

            <div class="mt-6 text-center space-y-2">
                <a href="#" class="text-custom-teal text-sm hover:underline">Forgot Password?</a>
                <p class="text-sm text-gray-600">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-custom-teal font-medium hover:underline">Sign up here</a>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const toggle = document.getElementById('passwordToggle');
    const password = document.getElementById('password');

    toggle.addEventListener('click', () => {
        password.type = password.type === 'password' ? 'text' : 'password';
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    });
</script>
@endpush
