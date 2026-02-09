@extends('public-layout.layout')

@section('title', 'Login')

@section('content')
    <div class="flex w-full max-w-6xl mx-auto overflow-hidden rounded-2xl shadow-4xl" style="min-height: 600px;">
        {{-- Left Section - Welcome Message --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden
                    bg-gradient-to-br from-[#70C8E0] via-[#4A6B99] to-[#C44F3A] rounded-l-2xl">

            {{-- Vector Shapes --}}
            {{-- Large Circle - Top Right --}}
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-yellow-300/20"></div>

            {{-- Medium Circle - Bottom Left --}}
            <div class="absolute -bottom-20 -left-20 w-72 h-72 rounded-full bg-white/15"></div>

            {{-- Small Circle - Top Left (subtle accent) --}}
            <div class="absolute top-4 left-4 w-24 h-24 rounded-full bg-white/20"></div>

            {{-- Triangles --}}
            <svg class="absolute top-32 right-40 opacity-15" width="120" height="120" viewBox="0 0 120 120">
                <polygon points="60,10 110,100 10,100" fill="white"/>
            </svg>
            <svg class="absolute bottom-32 left-32 opacity-10" width="100" height="100" viewBox="0 0 100 100">
                <polygon points="50,10 90,90 10,90" fill="white"/>
            </svg>

            {{-- Rectangle - Rotated --}}
            <div class="absolute top-1/3 right-24 w-24 h-40 bg-white/10 transform rotate-12 rounded-lg"></div>

            {{-- Small Squares --}}
            <div class="absolute bottom-1/4 right-16 w-16 h-16 bg-yellow-300/15 rounded-lg transform rotate-45"></div>
            <div class="absolute top-1/2 left-12 w-12 h-12 bg-white/10 rounded"></div>

            {{-- Curved Lines/Waves --}}
            <svg class="absolute top-0 left-0 w-full h-full opacity-20" viewBox="0 0 400 600" preserveAspectRatio="none">
                <path d="M0,100 Q100,150 200,100 T400,100" stroke="white" stroke-width="3" fill="none"/>
                <path d="M0,300 Q100,250 200,300 T400,300" stroke="white" stroke-width="3" fill="none"/>
                <path d="M0,500 Q100,450 200,500 T400,500" stroke="white" stroke-width="2" fill="none"/>
            </svg>

            <div class="relative z-10 flex flex-col h-full w-full px-16 text-white">

                <!-- Main Content (Centered) -->
                <div class="flex flex-col flex-1 justify-center items-center text-center">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Main Logo" class="h-36 w-auto rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                    <h2 class="text-4xl font-bold tracking-wide">
                        <span class="text-[#b5e2ff]">i</span><span class="text-[#1A3263]">RIMS-</span><span class="text-[#DA3D20]">V</span>
                    </h2>

                    <h1 class="text-5xl font-bold mb-2 leading-tight drop-shadow-md">Welcome back!</h1>
                    <p class="text-base text-white/95 max-w-lg drop-shadow leading-relaxed">
                        An innovative, ICT-enabled platform that centralizes and tracks learning resources in Region V, providing real-time mapping, monitoring, and management across schools and divisions to enhance efficiency, transparency, and data-driven decision-making in Learning Resource Management.
                    </p>
                </div>

                <!-- Footer Logos -->
                <div class="flex justify-center items-center gap-6 pb-8">
                    <img src="{{ asset('assets/images/rov.png') }}" alt="Logo"
                        class="h-20 w-20 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                    <img src="{{ asset('assets/images/deped.png') }}" alt="Logo"
                        class="h-20 w-20 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                    <img src="{{ asset('assets/images/bp.png') }}" alt="Logo"
                        class="h-20 w-20 rounded-full opacity-100 drop-shadow-[0_0_20px_rgba(255,255,255,1.0)]">
                </div>
            </div>
        </div>

        {{-- Right Section - Login Form --}}
        <div class="w-full lg:w-1/2 bg-white p-8 lg:p-12 flex flex-col justify-center">
            <div class="w-full max-w-md mx-auto">
                {{-- System Tabs --}}
                <div class="mb-6">
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

                    <div class="mt-6 text-center">
                        <h2 class="text-3xl font-bold text-gray-800" id="systemTitle">Sign In</h2>
                        <p class="text-sm text-gray-500 mt-1" id="systemDescription">Track and manage inventory</p>
                    </div>
                </div>

                {{-- Alerts --}}
                @if($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 shadow-md relative transition-opacity duration-300">
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
                    <div id="error-alert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 shadow-md relative transition-opacity duration-300">
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
                    <div id="success-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 shadow-md relative transition-opacity duration-300">
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

                    <div class="mb-5 relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" placeholder="Username or email" required value="{{ old('username') }}"
                               class="w-full pl-12 pr-4 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent @error('username') border-red-500 @enderror">
                    </div>

                    <div class="mb-5 relative">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" placeholder="Password" required
                               class="w-full pl-12 pr-12 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent">
                        <i id="passwordToggle" class="fas fa-eye absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600"></i>
                    </div>

                    <div class="mb-6 flex items-center justify-between text-sm">
                        <div class="flex items-center">
                            <input type="checkbox" id="notRobot" name="notRobot" class="mr-2 h-4 w-4 rounded border-gray-300 text-custom-yellow focus:ring-custom-yellow">
                            <label for="notRobot" class="text-gray-600">Remember me</label>
                        </div>
                        <a href="#" class="text-custom-teal hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit" id="loginButton"
                            class="w-full bg-custom-yellow text-gray-800 font-semibold py-3.5 rounded-lg hover:bg-custom-yellow-hover transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg">
                        <span id="buttonText">Sign In</span>
                        <span id="buttonLoading" class="hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Logging in...
                        </span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        New here?
                        <a href="{{ route('register') }}" class="text-custom-teal font-medium hover:underline">Create an Account</a>
                    </p>
                </div>
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
                title: 'Sign In',
                description: 'Track and manage inventory',
                activeClass: 'bg-orange-500 text-white shadow-md',
                inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200'
            },
            library: {
                title: 'Sign In',
                description: 'Access books and manage resources',
                activeClass: 'bg-blue-500 text-white shadow-md',
                inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200'
            },
            allocation: {
                title: 'Sign In',
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
