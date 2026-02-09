@extends('public-layout.layout')

@section('title', 'Login')

@section('content')
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            {{-- System Tabs --}}
            <div class="mb-2">
                <div class="flex justify-center mb-2">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" class="h-24 w-auto">
                </div>

                <div class="bg-gray-100 p-1 rounded-lg flex gap-1">
                    <button type="button" class="system-tab flex-1 py-2.5 px-3 rounded-md font-medium transition-all duration-300 text-xs active"
                            data-system="inventory">
                        <div class="flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Inventory</span>
                        </div>
                    </button>
                    <button type="button" class="system-tab flex-1 py-2.5 px-3 rounded-md font-medium transition-all duration-300 text-xs"
                            data-system="library">
                        <div class="flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>Library</span>
                        </div>
                    </button>
                    <button type="button" class="system-tab flex-1 py-2.5 px-3 rounded-md font-medium transition-all duration-300 text-xs"
                            data-system="allocation">
                        <div class="flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span>Allocation</span>
                        </div>
                    </button>
                </div>

                <div class="mt-4 text-center">
                    <h2 class="text-2xl font-bold text-gray-800" id="systemTitle">Inventory System Login</h2>
                    <p class="text-sm text-gray-500 mt-1" id="systemDescription">Track and manage inventory</p>
                </div>
            </div>

            {{-- Alerts --}}
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

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <input type="hidden" name="system" id="systemInput" value="inventory">

                <div class="mb-6 relative">
                    <input type="text" id="username" name="username" placeholder=" " required value="{{ old('username') }}"
                           class="w-full px-3 py-2 pt-4 pb-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent peer @error('username') border-red-500 @enderror">
                    <label for="username"
                           class="absolute left-4 top-3 text-gray-500 pointer-events-none transition-all duration-200 peer-focus:-top-2.5 peer-focus:left-3 peer-focus:text-xs peer-focus:bg-white peer-focus:px-2 peer-focus:text-custom-yellow peer-valid:-top-2.5 peer-valid:left-3 peer-valid:text-xs peer-valid:bg-white peer-valid:px-2 peer-valid:text-custom-yellow">
                        Username or Email
                    </label>
                </div>

                <div class="mb-6 relative">
                    <input type="password" id="password" name="password" placeholder=" " required
                           class="w-full px-3 py-2 pt-4 pb-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent peer">
                    <label for="password"
                           class="absolute left-4 top-3 text-gray-500 pointer-events-none transition-all duration-200 peer-focus:-top-2.5 peer-focus:left-3 peer-focus:text-xs peer-focus:bg-white peer-focus:px-2 peer-focus:text-custom-yellow peer-valid:-top-2.5 peer-valid:left-3 peer-valid:text-xs peer-valid:bg-white peer-valid:px-2 peer-valid:text-custom-yellow">
                        Password
                    </label>
                    <i id="passwordToggle" class="fas fa-eye absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700"></i>
                </div>

                <div class="mb-6 flex items-center">
                    <input type="checkbox" id="notRobot" name="notRobot" required class="mr-3 h-4 w-4">
                    <label for="notRobot" class="text-sm text-gray-600">I'm not a robot</label>
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
                <a href="" class="text-custom-teal text-sm font-medium hover:underline flex items-center justify-center gap-1 mt-2">
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

        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        const buttonLoading = document.getElementById('buttonLoading');

        loginForm.addEventListener('submit', function(e) {
            loginButton.disabled = true;
            buttonText.classList.add('hidden');
            buttonLoading.classList.remove('hidden');
        });

        // Tab System Logic
        const systemTabs = document.querySelectorAll('.system-tab');
        const systemInput = document.getElementById('systemInput');
        const systemTitle = document.getElementById('systemTitle');
        const systemDescription = document.getElementById('systemDescription');

        const systemInfo = {
            inventory: {
                title: 'Inventory System Login',
                description: 'Track and manage inventory',
                activeClass: 'bg-orange-500 text-white shadow-md',
                inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200'
            },
            library: {
                title: 'Library System Login',
                description: 'Access books and manage resources',
                activeClass: 'bg-blue-500 text-white shadow-md',
                inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200'
            },
            allocation: {
                title: 'Allocation System Login',
                description: 'Manage resource distribution',
                activeClass: 'bg-green-500 text-white shadow-md',
                inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200'
            }
        };

        systemTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const system = this.getAttribute('data-system');
                const info = systemInfo[system];

                // Update active states
                systemTabs.forEach(t => {
                    const tSystem = t.getAttribute('data-system');
                    t.className = `system-tab flex-1 py-2.5 px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[tSystem].inactiveClass}`;
                    t.classList.remove('active');
                });

                this.className = `system-tab flex-1 py-2.5 px-3 rounded-md font-medium transition-all duration-300 text-xs ${info.activeClass}`;
                this.classList.add('active');

                // Update content
                systemInput.value = system;
                systemTitle.textContent = info.title;
                systemDescription.textContent = info.description;
            });
        });

        // Set initial state (Inventory as default)
        const initialTab = document.querySelector('.system-tab.active');
        if (initialTab) {
            const system = initialTab.getAttribute('data-system');
            initialTab.className = `system-tab flex-1 py-2.5 px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[system].activeClass}`;
        }
    });
</script>
@endpush
