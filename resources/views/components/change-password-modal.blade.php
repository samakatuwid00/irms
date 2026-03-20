<!-- Change Password Modal Component -->
<div id="change-password-modal" class="fixed inset-0 w-screen h-screen bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Change Password for <span id="modal-user-name"></span></h3>
            
            <!-- Success Message Container -->
            <div id="password-success-message" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-md hidden">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span id="success-message-text">Password updated successfully!</span>
                </div>
            </div>
            
            <!-- Error Message Container -->
            <div id="password-error-message" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-md hidden">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span id="error-message-text"></span>
                </div>
            </div>
            
            <!-- Loading Spinner (inline) -->
            <div id="password-loading-spinner" class="hidden mb-4 p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded-md">
                <div class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span>Processing your request...</span>
                </div>
            </div>
            
            <!-- Form -->
            <form id="change-password-form"
                  method="POST"
                  class="space-y-4">
                @csrf
                @method('PUT')
                
                <input type="hidden" name="user_id" id="modal-user-id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        minlength="8">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeChangePasswordModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" id="update-password-btn"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Change Password Functionality
    let currentUserId = null;
    let isSubmitting = false;

    function openChangePasswordModal(userId, userName) {
        currentUserId = userId;
        document.getElementById('modal-user-id').value = userId;
        document.getElementById('modal-user-name').textContent = userName;
        
        // Clear previous password inputs
        document.getElementById('password').value = '';
        document.getElementById('password_confirmation').value = '';
        
        // Hide all messages
        document.getElementById('password-success-message').classList.add('hidden');
        document.getElementById('password-error-message').classList.add('hidden');
        document.getElementById('password-loading-spinner').classList.add('hidden');
        
        // Enable the submit button and remove any disabled styling
        const submitBtn = document.getElementById('update-password-btn');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        submitBtn.innerHTML = 'Update Password';
        
        // Reset submission flag
        isSubmitting = false;
        
        // Show modal
        document.getElementById('change-password-modal').classList.remove('hidden');
    }

    function closeChangePasswordModal() {
        document.getElementById('change-password-modal').classList.add('hidden');
        currentUserId = null;
        isSubmitting = false;
    }

    function showSuccessMessage(message) {
        const successMsg = document.getElementById('password-success-message');
        const errorMsg = document.getElementById('password-error-message');
        const loadingSpinner = document.getElementById('password-loading-spinner');
        const successText = document.getElementById('success-message-text');
        
        // Hide other messages
        errorMsg.classList.add('hidden');
        loadingSpinner.classList.add('hidden');
        
        // Show success message
        successText.textContent = message;
        successMsg.classList.remove('hidden');
        
        // Auto-hide after 2 seconds and close modal
        setTimeout(() => {
            successMsg.classList.add('hidden');
            closeChangePasswordModal();
        }, 2000);
    }

    function showErrorMessage(message) {
        const successMsg = document.getElementById('password-success-message');
        const errorMsg = document.getElementById('password-error-message');
        const loadingSpinner = document.getElementById('password-loading-spinner');
        const submitBtn = document.getElementById('update-password-btn');
        const errorText = document.getElementById('error-message-text');
        
        // Hide other messages
        successMsg.classList.add('hidden');
        loadingSpinner.classList.add('hidden');
        
        // Show error message
        errorText.textContent = message;
        errorMsg.classList.remove('hidden');
        
        // Re-enable the submit button on error
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        submitBtn.innerHTML = 'Update Password';
        isSubmitting = false;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            errorMsg.classList.add('hidden');
        }, 3000);
    }

    function disableSubmitButton() {
        const submitBtn = document.getElementById('update-password-btn');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        submitBtn.innerHTML = 'Updating...';
    }

    function showLoadingSpinner() {
        const loadingSpinner = document.getElementById('password-loading-spinner');
        loadingSpinner.classList.remove('hidden');
    }

    // Initialize change password functionality
    function initChangePassword() {
        // Close modal when clicking outside
        const modal = document.getElementById('change-password-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeChangePasswordModal();
                }
            });
        }
        
        // Handle form submission
        const form = document.getElementById('change-password-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Prevent multiple submissions
                if (isSubmitting) {
                    return;
                }
                
                if (!currentUserId) {
                    showErrorMessage('User ID is missing');
                    return;
                }
                
                // Get form data
                const formData = new FormData(this);
                const password = formData.get('password');
                const passwordConfirmation = formData.get('password_confirmation');
                
                // Basic validation
                if (password.length < 8) {
                    showErrorMessage('Password must be at least 8 characters long');
                    return;
                }
                
                if (password !== passwordConfirmation) {
                    showErrorMessage('Passwords do not match');
                    return;
                }
                
                // Set submitting flag and disable button
                isSubmitting = true;
                disableSubmitButton();
                showLoadingSpinner();
                
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                if (!csrfToken) {
                    showErrorMessage('CSRF token not found');
                    isSubmitting = false;
                    return;
                }
                
                // Send the request using fetch
                fetch(`/users/${currentUserId}/change-password`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        password: password,
                        password_confirmation: passwordConfirmation
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage(data.message);
                    } else {
                        showErrorMessage(data.message || 'Failed to update password');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while updating the password');
                });
            });
        }
    }

    // Initialize when DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChangePassword);
    } else {
        initChangePassword();
    }
</script>