// Division Profile Main Entry Point
import { initDivisionInfoForm } from './station-modules/division-info-form.js';
import { initLogoUpload } from './station-modules/logo-upload.js';
import { initDateEstablished } from './station-modules/date-established.js';
import { initFlashMessage } from './station-modules/flash-message.js';
import { initLibraryHubs } from './station-modules/library-hubs.js';

// Initialize all modules when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initFlashMessage();
    initDivisionInfoForm();
    initLogoUpload();
    initDateEstablished();
    initLibraryHubs();
});
