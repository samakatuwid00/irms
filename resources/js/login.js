/**
 * login.js
 * Vite-ready ES module — single entry point for the Login page.
 *
 * Replace the @push('scripts') block in your Blade with:
 *
 *   <script>
 *     window.__LOGIN__ = {
 *       hasError   : {{ $errors->any() ? 'true' : 'false' }},
 *       hasSuccess : {{ session('success') ? 'true' : 'false' }},
 *     };
 *   </script>
 *   @vite('resources/js/login.js')
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Server flags injected by Blade ─────────────────────────────────────
    const { hasError, hasSuccess } = window.__LOGIN__ ?? {};

    // ── Elements ───────────────────────────────────────────────────────────
    const toggle        = document.getElementById('passwordToggle');
    const passwordField = document.getElementById('password');

    const loginForm     = document.getElementById('loginForm');
    const loginButton   = document.getElementById('loginButton');
    const buttonText    = document.getElementById('buttonText');
    const buttonLoading = document.getElementById('buttonLoading');

    const systemTabs    = document.querySelectorAll('.system-tab');
    const systemInput   = document.getElementById('systemInput');
    const systemTitle   = document.getElementById('systemTitle');
    const systemDesc    = document.getElementById('systemDescription');

    const leftPanel  = document.querySelector('.left-panel');
    const mainLogo   = document.getElementById('mainLogo');
    const panelTitle = document.getElementById('panelTitle');
    const panelDesc  = document.getElementById('panelDesc');

    // ── Password Toggle ───────────────────────────────────────────────────

    toggle?.addEventListener('click', () => {
        const isPassword = passwordField.getAttribute('type') === 'password';
        passwordField.setAttribute('type', isPassword ? 'text' : 'password');
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    });

    // ── Auto-dismiss Alerts ───────────────────────────────────────────────

    const dismissAlert = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, 8000);
    };

    dismissAlert('error-alert');
    dismissAlert('success-alert');

    // ── Login Form — Loading State ─────────────────────────────────────────

    loginForm?.addEventListener('submit', function () {
        loginButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonLoading.classList.remove('hidden');
    });

    // ── System / Tab Config ────────────────────────────────────────────────

    const systemInfo = {
        inventory: {
            title        : 'Sign In',
            description  : 'Track and manage inventory',
            activeClass  : 'bg-orange-500 text-white shadow-md',
            inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200',
            panelTheme   : 'theme-inventory',
            welcomeTitle : 'Inventory System',
            welcomeDesc  : 'Centrally track and manage resources across schools and divisions with real-time data, full transparency, and integrated learning resource needs analysis.',
        },
        library: {
            title        : 'Sign In',
            description  : 'Access books and manage resources',
            activeClass  : 'bg-blue-500 text-white shadow-md',
            inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200',
            panelTheme   : 'theme-library',
            welcomeTitle : 'Library System',
            welcomeDesc  : 'Manage, catalog, and provide seamless access to books and learning materials across all schools in Region V.',
        },
        allocation: {
            title        : 'Sign In',
            description  : 'Manage resource distribution',
            activeClass  : 'bg-green-500 text-white shadow-md',
            inactiveClass: 'bg-transparent text-gray-600 hover:bg-gray-200',
            panelTheme   : 'theme-allocation',
            welcomeTitle : 'Allocation and Distribution System',
            welcomeDesc  : 'Plan, distribute, and monitor the equitable allocation of learning resources across all divisions and schools.',
        },
    };

    let currentSystem = 'inventory';

    // ── Helpers ───────────────────────────────────────────────────────────

    const updatePanelText = (title, desc) => {
        panelTitle.classList.add('panel-text-hidden');
        panelDesc.classList.add('panel-text-hidden');

        setTimeout(() => {
            panelTitle.textContent = title;
            panelDesc.textContent  = desc;
            panelTitle.classList.remove('panel-text-hidden');
            panelDesc.classList.remove('panel-text-hidden');
        }, 300);
    };

    const updateTabs = (activeSystem) => {
        systemTabs.forEach(tab => {
            const s = tab.getAttribute('data-system');
            tab.className = `system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[s].inactiveClass}`;
            tab.classList.remove('active');
        });

        const activeTab = document.querySelector(`.system-tab[data-system="${activeSystem}"]`);
        if (activeTab) {
            activeTab.className = `system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[activeSystem].activeClass}`;
            activeTab.classList.add('active');
        }
    };

    // ── Switch System ─────────────────────────────────────────────────────

    const switchSystem = (system) => {
        if (system === currentSystem) return;
        const info = systemInfo[system];

        // Shape layers
        const prevShapes = document.getElementById('shapes-' + currentSystem);
        const nextShapes = document.getElementById('shapes-' + system);

        if (prevShapes) {
            prevShapes.classList.remove('active');
            prevShapes.classList.add('exit');
            setTimeout(() => prevShapes.classList.remove('exit'), 600);
        }
        if (nextShapes) {
            nextShapes.style.transform = 'scale(0.97)';
            nextShapes.classList.add('active');
            setTimeout(() => { nextShapes.style.transform = ''; }, 50);
        }

        // Panel background
        leftPanel.classList.remove('theme-inventory', 'theme-library', 'theme-allocation');
        leftPanel.classList.add(info.panelTheme);

        // Logo bounce
        mainLogo.style.transform = 'scale(1.15) rotate(-5deg)';
        mainLogo.classList.add('logo-pulse');
        setTimeout(() => {
            mainLogo.style.transform = '';
            mainLogo.classList.remove('logo-pulse');
        }, 600);

        // Tabs
        updateTabs(system);

        // Form labels
        systemInput.value    = system;
        systemTitle.textContent = info.title;
        systemDesc.textContent  = info.description;

        // Panel welcome text
        updatePanelText(info.welcomeTitle, info.welcomeDesc);

        currentSystem = system;
    };

    // ── Tab Click Events ──────────────────────────────────────────────────

    systemTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            switchSystem(this.getAttribute('data-system'));
        });
    });

    // ── Set Initial Active Tab Styling ────────────────────────────────────

    const initialTab = document.querySelector('.system-tab.active');
    if (initialTab) {
        const system = initialTab.getAttribute('data-system');
        initialTab.className = `system-tab flex-1 py-2 sm:py-2.5 px-2 sm:px-3 rounded-md font-medium transition-all duration-300 text-xs ${systemInfo[system].activeClass}`;
    }

});
