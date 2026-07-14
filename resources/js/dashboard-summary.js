import { bindDivisionHubToggle, isDivisionHubHidden } from './charts/source-filter';

const SUMMARY_DATA_ID = 'dashboardSummaryData';
const SUMMARY_ROOT_SELECTOR = '[data-dashboard-summary]';
const TOTAL_LR_CARD_SELECTOR = '[data-total-lr-card]';
const LR_SOURCE_BUTTON_SELECTOR = '[data-total-lr-source-prev], [data-total-lr-source-next]';

const numberFormatter = new Intl.NumberFormat();
const sourceLabels = {
    all: 'All',
    division: 'Division Hub',
    school: 'School',
};

function onReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
}

function parseSummaryData() {
    const script = document.getElementById(SUMMARY_DATA_ID);
    const json = script?.textContent?.trim();

    if (!json) return null;

    try {
        return JSON.parse(json);
    } catch (error) {
        console.warn('Unable to parse dashboard summary data.', error);
        return null;
    }
}

function formatNumber(value) {
    const number = Number(value ?? 0);
    return numberFormatter.format(Number.isFinite(number) ? number : 0);
}

function setSummaryValue(root, key, value) {
    root.querySelectorAll(`[data-summary-value="${key}"]`).forEach(element => {
        element.textContent = value;
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderNeedsList(root, needs) {
    const list = root.querySelector('[data-lr-needs-list]');
    if (!list) return;

    if (!Array.isArray(needs) || needs.length === 0) {
        list.innerHTML = `
            <div class="text-green-600 font-medium text-center py-3 text-sm">
                No LR needs
            </div>
        `;
        return;
    }

    list.innerHTML = needs.map(need => {
        const subjectGrade = escapeHtml(need.subject_grade);
        const needed = formatNumber(need.needed);

        return `
            <div class="group relative flex justify-between text-gray-600">
                <span class="truncate pr-3">${subjectGrade}</span>
                <span class="font-semibold text-red-700 whitespace-nowrap">${needed}</span>

                <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block z-20 pointer-events-none">
                    <div class="bg-gray-800 text-white text-xs rounded py-1.5 px-2.5 max-w-[240px] whitespace-normal break-words shadow-lg">
                        ${subjectGrade}
                    </div>
                    <div class="w-2 h-2 bg-gray-800 rotate-45 -mt-1 ml-auto mr-3"></div>
                </div>
            </div>
        `;
    }).join('');
}

function getTotalLrData(summaryData, source) {
    const allData = summaryData?.all?.total_lr ?? {};
    const schoolData = summaryData?.school?.total_lr ?? {};

    if (source === 'division') {
        return {
            ...allData,
            total: allData.division_lr_hub ?? 0,
        };
    }

    if (source === 'school') {
        return {
            ...allData,
            ...schoolData,
            total: schoolData.total ?? allData.school_lr ?? 0,
            print: schoolData.print ?? allData.print ?? 0,
            non_print: schoolData.non_print ?? allData.non_print ?? 0,
        };
    }

    return allData;
}

function setTotalLrSourceLabel(totalLrCard, source) {
    totalLrCard?.querySelectorAll('[data-summary-source-label]').forEach(label => {
        label.textContent = sourceLabels[source] ?? sourceLabels.all;
    });
}

function setTotalLrSourceLock(totalLrCard, locked) {
    totalLrCard?.querySelectorAll(LR_SOURCE_BUTTON_SELECTOR).forEach(button => {
        button.disabled = locked;
        button.setAttribute('aria-disabled', locked ? 'true' : 'false');
    });
}

function initDashboardSummary() {
    const root = document.querySelector(SUMMARY_ROOT_SELECTOR);
    const summaryData = parseSummaryData();

    if (!root || !summaryData?.all) return;

    const totalLrCard = root.querySelector(TOTAL_LR_CARD_SELECTOR);
    const sourceOrder = ['all', 'division', 'school'];
    let schoolOnlyMode = Boolean(summaryData.school) && isDivisionHubHidden();
    let freeSource = totalLrCard?.dataset.totalLrSource === 'division'
        ? 'division'
        : 'all';

    function applySummary(mode) {
        const summaryMode = summaryData[mode] ? mode : 'all';
        const forcedSchool = summaryMode === 'school';
        const source = forcedSchool ? 'school' : (totalLrCard?.dataset.totalLrSource || freeSource || 'all');
        const summary = summaryData[summaryMode] ?? summaryData.all;
        const totalLr = getTotalLrData(summaryData, source);

        root.dataset.summaryMode = summaryMode;

        if (totalLrCard) {
            totalLrCard.dataset.totalLrSource = source;
            setTotalLrSourceLabel(totalLrCard, source);
            setTotalLrSourceLock(totalLrCard, forcedSchool);
        }

        setSummaryValue(root, 'total_lr.total', formatNumber(totalLr.total));
        setSummaryValue(root, 'total_lr.print', formatNumber(totalLr.print));
        setSummaryValue(root, 'total_lr.non_print', formatNumber(totalLr.non_print));
        setSummaryValue(root, 'overall_ratio.ratio_display', summary.overall_ratio?.ratio_display ?? 'N/A');
        setSummaryValue(root, 'overall_ratio.total_lr', formatNumber(summary.overall_ratio?.total_lr));
        setSummaryValue(root, 'overall_ratio.total_population', formatNumber(summary.overall_ratio?.total_population));
        setSummaryValue(root, 'lr_needs.total_needs', formatNumber(summary.lr_needs?.total_needs));
        renderNeedsList(root, summary.lr_needs?.needs);
    }

    function applySchoolOnly(hidden) {
        schoolOnlyMode = Boolean(summaryData.school) && hidden;

        if (schoolOnlyMode) {
            const currentSource = totalLrCard?.dataset.totalLrSource;
            if (currentSource && currentSource !== 'school') {
                freeSource = currentSource;
            }
            applySummary('school');
            return;
        }

        if (totalLrCard) {
            totalLrCard.dataset.totalLrSource = freeSource || 'all';
        }
        applySummary('all');
    }

    function cycleTotalLrSource(direction) {
        if (schoolOnlyMode || !totalLrCard) return;

        const current = totalLrCard.dataset.totalLrSource || freeSource || 'all';
        const currentIndex = Math.max(sourceOrder.indexOf(current), 0);
        const nextIndex = (currentIndex + direction + sourceOrder.length) % sourceOrder.length;
        const nextSource = sourceOrder[nextIndex];

        freeSource = nextSource;
        totalLrCard.dataset.totalLrSource = nextSource;
        applySummary('all');
    }

    root.querySelector('[data-total-lr-source-prev]')?.addEventListener('click', () => {
        cycleTotalLrSource(-1);
    });

    root.querySelector('[data-total-lr-source-next]')?.addEventListener('click', () => {
        cycleTotalLrSource(1);
    });

    bindDivisionHubToggle(applySchoolOnly);
    applySchoolOnly(isDivisionHubHidden());
}

onReady(initDashboardSummary);
