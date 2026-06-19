

document.addEventListener('DOMContentLoaded', function () {

    // ── Elements ───────────────────────────────────────────────────────────
    const toggle        = document.getElementById('passwordToggle');
    const passwordField = document.getElementById('password');

    const loginForm     = document.getElementById('loginForm');
    const loginButton   = document.getElementById('loginButton');
    const buttonText    = document.getElementById('buttonText');
    const buttonLoading = document.getElementById('buttonLoading');

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

});