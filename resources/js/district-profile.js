// District Profile Main Entry Point
import { initDistrictInfoForm } from './station-modules/district-info-form.js';
import { initLogoUpload } from './station-modules/logo-upload.js';
import { initDateEstablished } from './station-modules/date-established.js';
import { initFlashMessage } from './station-modules/flash-message.js';

// Initialize all modules when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initFlashMessage();
    initDistrictInfoForm();
    initLogoUpload();
    initDateEstablished();
});
