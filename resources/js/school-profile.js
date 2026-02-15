// School Profile Main Entry Point
import { initSchoolInfoForm } from './school-profile/school-info-form.js';
import { initLogoUpload } from './school-profile/logo-upload.js';
import { initDateEstablished } from './school-profile/date-established.js';
import { initGradeOfferings } from './school-profile/grade-offerings.js';
import { initPopulation } from './school-profile/population.js';
import { initFlashMessage } from './school-profile/flash-message.js';

// Initialize all modules when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initFlashMessage();
    initSchoolInfoForm();
    initLogoUpload();
    initDateEstablished();
    initGradeOfferings();
    initPopulation();
});
