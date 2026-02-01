
<!-- Privacy & Terms Modal -->
<div id="privacyModal" class="fixed inset-0  bg-black/50 z-50 hidden flex items-center justify-center px-4">
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

<script>
    const openModalBtn = document.getElementById('openPrivacyModal');
    const modal = document.getElementById('privacyModal');
    const closeBtns = document.querySelectorAll(
        '#closePrivacyModal, #closePrivacyModalBottom'
    );

    const privacyTab = document.getElementById('privacyTab');
    const termsTab = document.getElementById('termsTab');
    const privacyContent = document.getElementById('privacyContent');
    const termsContent = document.getElementById('termsContent');

    // Open modal
    openModalBtn?.addEventListener('click', () => {
        modal.classList.remove('hidden');
    });

    // Close modal buttons
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // Tabs logic
    privacyTab.addEventListener('click', () => {
        privacyTab.classList.add(
            'text-custom-teal', 'border-custom-teal', 'border-b-4'
        );
        privacyTab.classList.remove('text-gray-600');

        termsTab.classList.remove(
            'text-custom-teal', 'border-custom-teal', 'border-b-4'
        );
        termsTab.classList.add('text-gray-600');

        privacyContent.classList.remove('hidden');
        termsContent.classList.add('hidden');
    });

    termsTab.addEventListener('click', () => {
        termsTab.classList.add(
            'text-custom-teal', 'border-custom-teal', 'border-b-4'
        );
        termsTab.classList.remove('text-gray-600');

        privacyTab.classList.remove(
            'text-custom-teal', 'border-custom-teal', 'border-b-4'
        );
        privacyTab.classList.add('text-gray-600');

        termsContent.classList.remove('hidden');
        privacyContent.classList.add('hidden');
    });
</script>
