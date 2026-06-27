// School Profile Main Entry Point
import { initSchoolInfoForm } from './station-modules/school-info-form.js';
import { initLogoUpload } from './station-modules/logo-upload.js';
import { initDateEstablished } from './station-modules/date-established.js';
import { initGradeOfferings } from './station-modules/grade-offerings.js';
import { initPopulation } from './station-modules/population.js';
import { initFlashMessage } from './station-modules/flash-message.js';

// Initialize all modules when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initFlashMessage();
    initSchoolInfoForm();
    initLogoUpload();
    initDateEstablished();
    initGradeOfferings();
    initPopulation();

    const populationRequiredModal = document.getElementById('populationRequiredModal');
    const updatePopulationNow = document.getElementById('updatePopulationNow');

    updatePopulationNow?.addEventListener('click', () => {
        populationRequiredModal?.classList.add('hidden');

        const populationSection = document.getElementById('studentPopulation');
        populationSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        populationSection?.focus({ preventScroll: true });
    });
});
