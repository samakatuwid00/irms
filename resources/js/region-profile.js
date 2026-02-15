// Region Profile Main Entry Point
import { initRegionInfoForm } from './station-modules/region-info-form.js';
import { initLogoUpload } from './station-modules/logo-upload.js';
import { initDateEstablished } from './station-modules/date-established.js';
import { initFlashMessage } from './station-modules/flash-message.js';

// Initialize all modules when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initFlashMessage();
    initRegionInfoForm();
    initLogoUpload();
    initDateEstablished();
});
