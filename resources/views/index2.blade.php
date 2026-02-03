<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to iRIMS-V</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .card:nth-child(2) {
            animation-delay: 0.3s;
        }

        .card:nth-child(3) {
            animation-delay: 0.5s;
        }

        .card:hover .icon-container {
            animation: float 2s ease-in-out infinite;
        }

        .card:hover {
            transform: translateY(-8px);
        }

        .card {
            transition: all 0.3s ease;
        }

        .modal {
            display: none;
            animation: slideDown 0.3s ease-out;
        }

        .modal.active {
            display: flex;
        }

        .backdrop-blur {
            backdrop-filter: blur(8px);
        }

        /* Background transitions */
        .bg-transition {
            transition: background 0.8s ease-in-out;
        }

        /* System-specific backgrounds */
        .bg-library {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-inventory {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        }

        .bg-allocation {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        .bg-default {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Input focus styles */
        .input-field:focus {
            outline: none;
            ring: 2px;
            ring-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen bg-transition bg-default flex items-center justify-center p-4" id="mainBody">

    <!-- Header -->
    <div class="absolute top-0 left-0 right-0 p-6">
        <h1 class="text-4xl font-bold text-white text-center drop-shadow-lg">iRIMS-V</h1>
        <p class="text-white text-center mt-2 text-lg opacity-90">Integrated Resource Information Management System</p>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl w-full mt-16">
        <!-- Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <!-- Library System Card -->
            <div class="card bg-white rounded-2xl shadow-lg p-8 cursor-pointer hover:shadow-2xl" onclick="openModal('library')">
                <div class="icon-container bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3 text-center">Library System</h2>
                <p class="text-gray-600 text-center mb-6">Manage books, members, and borrowing records efficiently</p>
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Access System
                </button>
            </div>

            <!-- Inventory Card -->
            <div class="card bg-white rounded-2xl shadow-lg p-8 cursor-pointer hover:shadow-2xl" onclick="openModal('inventory')">
                <div class="icon-container bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3 text-center">Inventory</h2>
                <p class="text-gray-600 text-center mb-6">Track and manage your stock levels and supplies</p>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Access System
                </button>
            </div>

            <!-- Allocation System Card -->
            <div class="card bg-white rounded-2xl shadow-lg p-8 cursor-pointer hover:shadow-2xl" onclick="openModal('allocation')">
                <div class="icon-container bg-purple-100 w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3 text-center">Allocation System</h2>
                <p class="text-gray-600 text-center mb-6">Optimize resource distribution and assignments</p>
                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    Access System
                </button>
            </div>

        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal fixed inset-0 bg-black bg-opacity-50 backdrop-blur items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 relative">
            <!-- Close Button -->
            <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Modal Icon -->
            <div id="modalIcon" class="w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto">
                <!-- Icon will be inserted here -->
            </div>

            <!-- Modal Title -->
            <h2 id="modalTitle" class="text-3xl font-bold text-gray-800 mb-2 text-center">Login</h2>
            <p id="modalSubtitle" class="text-gray-600 text-center mb-8">Enter your credentials to access the system</p>

            <!-- Login Form -->
            <form onsubmit="handleLogin(event)" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your username"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your password"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-700">Forgot password?</a>
                </div>

                <button
                    type="submit"
                    id="loginButton"
                    class="w-full text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105"
                >
                    Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account?
                    <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Contact Administrator</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentSystem = '';

        const systemConfig = {
            library: {
                title: 'Library System Login',
                subtitle: 'Access your library management dashboard',
                iconBg: 'bg-blue-100',
                iconColor: 'text-blue-600',
                buttonColor: 'bg-blue-600 hover:bg-blue-700',
                ringColor: 'focus:ring-blue-500',
                background: 'bg-library',
                icon: `<svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>`
            },
            inventory: {
                title: 'Inventory System Login',
                subtitle: 'Manage your inventory and stock levels',
                iconBg: 'bg-green-100',
                iconColor: 'text-green-600',
                buttonColor: 'bg-green-600 hover:bg-green-700',
                ringColor: 'focus:ring-green-500',
                background: 'bg-inventory',
                icon: `<svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>`
            },
            allocation: {
                title: 'Allocation System Login',
                subtitle: 'Optimize your resource distribution',
                iconBg: 'bg-purple-100',
                iconColor: 'text-purple-600',
                buttonColor: 'bg-purple-600 hover:bg-purple-700',
                ringColor: 'focus:ring-purple-500',
                background: 'bg-allocation',
                icon: `<svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>`
            }
        };

        function openModal(system) {
            currentSystem = system;
            const config = systemConfig[system];
            const modal = document.getElementById('loginModal');
            const body = document.getElementById('mainBody');
            const modalIcon = document.getElementById('modalIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalSubtitle = document.getElementById('modalSubtitle');
            const loginButton = document.getElementById('loginButton');

            // Update modal content
            modalTitle.textContent = config.title;
            modalSubtitle.textContent = config.subtitle;

            // Update icon
            modalIcon.className = `${config.iconBg} w-20 h-20 rounded-full flex items-center justify-center mb-6 mx-auto`;
            modalIcon.innerHTML = config.icon;

            // Update button color
            loginButton.className = `w-full ${config.buttonColor} text-white font-semibold py-3 px-6 rounded-lg transition duration-300 transform hover:scale-105`;

            // Change background
            body.className = `min-h-screen bg-transition ${config.background} flex items-center justify-center p-4`;

            // Show modal
            modal.classList.add('active');
        }

        function closeModal() {
            const modal = document.getElementById('loginModal');
            const body = document.getElementById('mainBody');

            modal.classList.remove('active');

            // Reset to default background
            body.className = 'min-h-screen bg-transition bg-default flex items-center justify-center p-4';
        }

        function handleLogin(event) {
            event.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            // Here you would typically handle the authentication
            console.log(`Logging into ${currentSystem} system`);
            console.log('Username:', username);
            console.log('Password:', password);

            // For demonstration purposes
            alert(`Logging into ${currentSystem.charAt(0).toUpperCase() + currentSystem.slice(1)} System...\nUsername: ${username}`);

            // You can redirect to the actual system here
            // window.location.href = `/systems/${currentSystem}/dashboard`;
        }

        // Close modal when clicking outside
        document.getElementById('loginModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
