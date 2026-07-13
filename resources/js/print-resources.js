// Print Resources Main Entry Point
import { initLevel3TabSwitching } from './resource-table-modules/level3-tabs.js';
import { initLevel3DistrictSchoolCascade } from './resource-table-modules/level3-cascade.js';
import { initLevel4Cascade } from './resource-table-modules/level4-cascade.js';
import { initResetFilters } from './resource-table-modules/reset-filters.js';

// Get level from the page
const levelElement = document.querySelector('[data-user-level]');
const level = levelElement ? parseInt(levelElement.dataset.userLevel) : 0;

let exportInProgress = false;

function setExportLinksDisabled(disabled) {
    document.querySelectorAll('#print-resources-wrapper a[href*="/print-resources/export"]').forEach((link) => {
        link.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        link.classList.toggle('opacity-60', disabled);
        link.classList.toggle('cursor-not-allowed', disabled);
    });
}

function getExportFilename(response) {
    const disposition = response.headers.get('Content-Disposition') || '';
    const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    const basicMatch = disposition.match(/filename="?([^";]+)"?/i);
    const encodedName = utf8Match?.[1] || basicMatch?.[1];

    if (!encodedName) return 'Print_Resources.xlsx';

    try {
        return decodeURIComponent(encodedName);
    } catch (_) {
        return encodedName;
    }
}

function updateExportProgress(progress, title, message) {
    const panel = document.getElementById('print-export-progress');
    const bar = document.getElementById('print-export-progress-bar');
    const percent = document.getElementById('print-export-percent');
    const titleElement = document.getElementById('print-export-title');
    const messageElement = document.getElementById('print-export-message');

    if (!panel || !bar || !percent || !titleElement || !messageElement) return;

    const safeProgress = Math.max(0, Math.min(100, Math.round(progress)));
    bar.style.width = `${safeProgress}%`;
    percent.textContent = `${safeProgress}%`;
    titleElement.textContent = title;
    messageElement.textContent = message;
}

function wait(milliseconds) {
    return new Promise((resolve) => window.setTimeout(resolve, milliseconds));
}

async function downloadPrintExport(url) {
    const panel = document.getElementById('print-export-progress');
    const bar = document.getElementById('print-export-progress-bar');

    if (!panel || !bar) {
        window.location.href = url;
        return;
    }

    exportInProgress = true;
    setExportLinksDisabled(true);
    panel.classList.remove('hidden');
    panel.setAttribute('aria-hidden', 'false');
    bar.classList.remove('bg-red-600');
    bar.classList.add('bg-green-600');
    updateExportProgress(5, 'Preparing Excel export', 'Please wait. Your download will start automatically.');

    let progress = 5;
    const progressTimer = window.setInterval(() => {
        progress = Math.min(85, progress + Math.max(1, Math.round((85 - progress) * 0.08)));
        updateExportProgress(progress, 'Preparing Excel export', 'Collecting the selected print resources...');
    }, 450);

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
        });

        if (!response.ok) {
            throw new Error(`Export failed with status ${response.status}`);
        }

        const contentType = response.headers.get('Content-Type') || '';
        const disposition = response.headers.get('Content-Disposition') || '';
        if (!contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') &&
            !disposition.toLowerCase().includes('attachment')) {
            throw new Error('The server did not return an Excel file.');
        }

        window.clearInterval(progressTimer);
        updateExportProgress(Math.max(progress, 90), 'Downloading Excel file', 'The export is ready and is being downloaded...');
        const blob = await response.blob();
        const objectUrl = URL.createObjectURL(blob);
        const downloadLink = document.createElement('a');
        downloadLink.href = objectUrl;
        downloadLink.download = getExportFilename(response);
        document.body.appendChild(downloadLink);
        downloadLink.click();
        downloadLink.remove();
        window.setTimeout(() => URL.revokeObjectURL(objectUrl), 30000);

        updateExportProgress(100, 'Excel download started', 'Your print resources export is downloading.');
        await wait(1400);
    } catch (error) {
        console.error('Print resource export failed:', error);
        bar.classList.remove('bg-green-600');
        bar.classList.add('bg-red-600');
        updateExportProgress(100, 'Export failed', 'The Excel file could not be downloaded. Please try again.');
        await wait(3000);
    } finally {
        window.clearInterval(progressTimer);
        exportInProgress = false;
        setExportLinksDisabled(false);
        panel.classList.add('hidden');
        panel.setAttribute('aria-hidden', 'true');
        updateExportProgress(0, 'Preparing Excel export', 'Please wait. Your download will start automatically.');
    }
}

function initExportProgress() {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('#print-resources-wrapper a[href*="/print-resources/export"]');
        if (!link) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        if (exportInProgress) return;

        downloadPrintExport(link.href);
    }, true);
}

function setDataLoading(container, loading) {
    const skeleton = window.ResourceLoadingSkeleton;
    if (!skeleton) return;

    if (loading) {
        skeleton.show(container);
    } else {
        skeleton.hide(container);
    }
}

function getFormContainerId(form) {
    const tab = form.querySelector('input[name="tab"]')?.value;

    if (tab === 'library-hub') return 'hub-results-container';
    if (tab === 'division') return 'division-results-container';
    if (tab === 'school') return level >= 3 ? 'school-results-container' : 'table-results-container';

    return 'table-results-container';
}

function getContextForm(context) {
    const tabByContext = {
        hub: 'library-hub',
        division: 'division',
        school: 'school',
    };
    const tab = tabByContext[context];

    if (!tab) return document.querySelector('#print-resources-wrapper form[data-ajax]');

    return Array.from(document.querySelectorAll('#print-resources-wrapper form[data-ajax]'))
        .find((form) => form.querySelector('input[name="tab"]')?.value === tab) || null;
}

// ─────────────────────────────────────────────
// AJAX Table Loader
// ─────────────────────────────────────────────

async function loadTableAjax(url, containerId = 'table-results-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    setDataLoading(container, true);
    container.style.pointerEvents = 'none';

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (!response.ok) throw new Error(`Server error: ${response.status}`);

        const html = await response.text();

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContainer = doc.getElementById(containerId);

        if (newContainer) {
            container.innerHTML = newContainer.innerHTML;
        } else {
            console.error('AJAX partial not found in response. Full page was returned instead.');
            container.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-6 text-center mt-4">
                    Failed to load data. Please try again or refresh the page.
                </div>`;
        }

        // Update browser URL bar without a full page reload
        history.pushState({ url, containerId }, '', url);

        // Re-initialize Alpine.js on the newly injected content
        if (window.Alpine) {
            window.Alpine.initTree(container);
        }

        // Re-attach cascade/filter listeners since the DOM was replaced
        reinitModules();

        // Initialize fade-in for any newly swapped cover images
        if (typeof initCoverImages === 'function') {
            initCoverImages();
        }

    } catch (err) {
        console.error('Failed to load table data:', err);
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-6 text-center mt-4">
                Failed to load data. Please try again or refresh the page.
            </div>`;
    } finally {
        setDataLoading(container, false);
        container.style.pointerEvents = '';
    }
}

// ─────────────────────────────────────────────
// Form Interception
// ─────────────────────────────────────────────

function interceptForms() {
    const wrapper = document.getElementById('print-resources-wrapper');
    if (!wrapper) return;

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('#print-resources-wrapper form[data-ajax]');
        if (!form) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        const params = new URLSearchParams(new FormData(form));

        const url = `${window.location.pathname}?${params.toString()}`;

        loadTableAjax(url, getFormContainerId(form));
    }, true);

    document.addEventListener('change', (e) => {
        const select = e.target.closest('#print-resources-wrapper .per-page-select');
        if (!select) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        const form = getContextForm(select.dataset.context);
        if (!form) return;

        form.querySelectorAll('.per-page-hidden-input').forEach((input) => {
            input.value = select.value;
        });

        try {
            localStorage.setItem('print-resources-per-page', select.value);
        } catch (_) {}

        const params = new URLSearchParams(new FormData(form));
        ['page', 'division_page', 'school_page', 'hub_page'].forEach((page) => params.delete(page));

        loadTableAjax(
            `${window.location.pathname}?${params.toString()}`,
            getFormContainerId(form)
        );
    }, true);

    // Auto-reset when all characters are cleared from a search input.
    // Uses 'input' so it fires on every keystroke — no need to press Search or Enter.
    document.addEventListener('input', (e) => {
        const input = e.target.closest('#print-resources-wrapper input[type="text"]');
        if (!input || input.value !== '') return;

        const form = input.closest('form[data-ajax]');
        if (!form) return;

        const tabInput = form.querySelector('input[name="tab"]');
        const tab = tabInput ? tabInput.value : null;

        // Build params from the form but strip the now-empty search field so the URL stays clean
        const params = new URLSearchParams(new FormData(form));
        params.delete(input.getAttribute('name'));

        const url = `${window.location.pathname}?${params.toString()}`;

        let containerId = 'table-results-container';
        if (tab === 'division') containerId = 'division-results-container';
        if (tab === 'school') containerId = level === 3 ? 'school-results-container' : 'table-results-container';

        loadTableAjax(url, containerId);
    });

    // Intercept pagination links inside any results container
    document.addEventListener('click', (e) => {
        const link = e.target.closest(
            '#table-results-container a[href],' +
            '#division-results-container a[href],' +
            '#school-results-container a[href],' +
            '#hub-results-container a[href]'
        );
        if (!link) return;

        // Export downloads have their own progress handler.
        if (link.href.includes('/print-resources/export')) return;

        const href = link.getAttribute('href');

        // Don't intercept links that navigate to a different page (e.g. Edit button).
        // Only AJAX-load links that stay on the same path (pagination, filters).
        if (href) {
            try {
                const linkPath = new URL(href, window.location.origin).pathname;
                if (linkPath !== window.location.pathname) {
                    // Different page — let the browser navigate normally
                    window.location.href = href;
                    return;
                }
            } catch (_) {
                // Malformed URL — fall through to normal navigation
                window.location.href = href;
                return;
            }
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        let containerId = 'table-results-container';
        if (link.closest('#division-results-container')) containerId = 'division-results-container';
        if (link.closest('#school-results-container'))   containerId = 'school-results-container';
        if (link.closest('#hub-results-container'))      containerId = 'hub-results-container';

        // Pagination links are absolute URLs — safe to use directly
        loadTableAjax(href, containerId);
    }, true);
}

// Handle browser back/forward navigation.
// Only reload via AJAX if we're still on the same page — if the state URL
// belongs to a different page (e.g. the user navigated to edit-resource and
// pressed Back), let the browser handle it as a normal navigation.
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.url) {
        const statePath = new URL(e.state.url, window.location.origin).pathname;
        if (statePath !== window.location.pathname) {
            // Different page — let the browser do a full load
            window.location.href = e.state.url;
            return;
        }
        loadTableAjax(e.state.url, e.state.containerId || 'table-results-container');
    }
});

// ─────────────────────────────────────────────
// Module Initialisation
// ─────────────────────────────────────────────

function reinitModules() {
    if (level === 3) {
        initLevel3TabSwitching();
        initLevel3DistrictSchoolCascade();
    }
    if (level === 4) {
        initLevel4Cascade();
    }
    initResetFilters();
}

document.addEventListener('DOMContentLoaded', () => {
    reinitModules();
    initExportProgress();
    interceptForms();

    history.replaceState({ url: window.location.href }, '', window.location.href);
});
