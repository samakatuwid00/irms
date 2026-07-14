document.addEventListener('DOMContentLoaded', () => {
    initSchoolNecEditor();
    initBosyStatus();
    // setInterval(initBosyStatus, 300000); // uncomment for auto-refresh every 5 min
});

let bosyAllItems = [];
let currentBosyLevel = 'region'; // 'region' or 'division'
let bosySearchTerm = '';
let bosySearchDebounceTimer = null;

// Cache for universal search at division level (all schools across all districts)
let bosyFullDivisionItems = null;
let bosyFullDivisionPrintType = null;
let bosyFullDivisionFetching = false;
let schoolNecEditor = null;

function initBosyStatus() {
    const regionSelect = document.getElementById('regionFilter');
    const districtSelect = document.getElementById('divisionFilter'); // level 3 district dropdown
    const printTypeSelect = document.getElementById('bosyPrintTypeFilter');

    // Initial load
    const initialHubFilter = regionSelect ? regionSelect.value : '';
    const initialDistrictFilter = districtSelect ? districtSelect.value : '';
    const initialPrintType = printTypeSelect ? printTypeSelect.value : '';
    fetchBosyStatus(true, initialHubFilter, initialDistrictFilter, initialPrintType);

    // Level 4+: hub filter changes
    if (regionSelect) {
        regionSelect.addEventListener('change', () => {
            const printType = printTypeSelect ? printTypeSelect.value : '';
            fetchBosyStatus(true, regionSelect.value, '', printType);
        });
    }

    // Level 3: district filter changes
    if (districtSelect) {
        districtSelect.addEventListener('change', () => {
            const printType = printTypeSelect ? printTypeSelect.value : '';
            fetchBosyStatus(true, '', districtSelect.value, printType);
        });
    }

    // Print type filter changes
    if (printTypeSelect) {
        printTypeSelect.addEventListener('change', () => {
            const hubFilter = regionSelect ? regionSelect.value : '';
            const distFilter = districtSelect ? districtSelect.value : '';
            fetchBosyStatus(true, hubFilter, distFilter, printTypeSelect.value);
        });
    }

    // BOSY Search bar
    const searchInput = document.getElementById('bosySearchInput');
    const searchClear = document.getElementById('bosySearchClear');
    const searchCount = document.getElementById('bosySearchCount');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(bosySearchDebounceTimer);
            bosySearchDebounceTimer = setTimeout(() => {
                bosySearchTerm = searchInput.value.trim().toLowerCase();
                toggleSearchClear(searchInput, searchClear);

                // Universal search at division level: fetch all schools if needed
                if (bosySearchTerm && currentBosyLevel === 'division') {
                    const currentPrintType = printTypeSelect ? printTypeSelect.value : '';
                    ensureFullDivisionData(currentPrintType, () => {
                        const container = document.getElementById('bosy-divisions-container');
                        if (container) renderAllItems(container);
                        updateSearchCount(searchCount);
                    });
                    return;
                }

                const container = document.getElementById('bosy-divisions-container');
                if (container) renderAllItems(container);
                updateSearchCount(searchCount);
            }, 300);
        });

        // Clear button
        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                bosySearchTerm = '';
                toggleSearchClear(searchInput, searchClear);
                const container = document.getElementById('bosy-divisions-container');
                if (container) renderAllItems(container);
                updateSearchCount(searchCount);
                searchInput.focus();
            });
        }
    }
}

function fetchBosyStatus(isFullRefresh = false, hubFilter = '', districtFilter = '', printTypeId = '') {
    const container = document.getElementById('bosy-divisions-container');
    if (!container) return;

    if (isFullRefresh) {
        showSkeletonLoaders(container);
        bosyAllItems = [];
        // Invalidate full division cache on new fetch
        bosyFullDivisionItems = null;
        bosyFullDivisionPrintType = null;
        bosyFullDivisionFetching = false;
        // Reset search on new data fetch
        bosySearchTerm = '';
        const searchInput = document.getElementById('bosySearchInput');
        const searchClear = document.getElementById('bosySearchClear');
        const searchCount = document.getElementById('bosySearchCount');
        if (searchInput) searchInput.value = '';
        if (searchClear) searchClear.classList.add('hidden');
        if (searchCount) { searchCount.classList.add('hidden'); searchCount.textContent = ''; }
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    // Build URL with all query params
    const params = new URLSearchParams();
    if (hubFilter) params.set('hub_filter', hubFilter);
    if (districtFilter) params.set('district_filter', districtFilter);
    if (printTypeId) params.set('print_type_id', printTypeId);

    const url = `/dashboard/bosy-status?${params.toString()}`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token || ''
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.error) {
                showError(container, data.error);
                return;
            }

            if (data.period) updateBosyPeriod(data.period);

            currentBosyLevel = data.level || 'region';
            bosyAllItems = data.items || [];

            // Update title based on level
            updateBosyTitle(currentBosyLevel, data.station_name, data.district_name);

            // Update summary if available
            if (data.summary) {
                updateBosySummary(data.summary, currentBosyLevel);
            }

            renderAllItems(container);
            animateBosyContent(container);
        })
        .catch(err => {
            console.error('BOSY fetch failed:', err);
            showError(container, err.message || 'Failed to load BOSY status');
        });
}

function updateBosyTitle(level, stationName, districtName) {
    const titleElement = document.querySelector('#bosy-status .card-title');
    if (!titleElement) return;

    if (level === 'school') {
        titleElement.textContent = `${stationName || 'School'} - Users BOSY Status`;
    } else if (level === 'district') {
        titleElement.textContent = `${stationName || 'District'} - Schools BOSY Status`;
    } else if (level === 'division') {
        titleElement.textContent = districtName
            ? `${districtName} - Schools BOSY Status`
            : `${stationName || 'Division'} - Schools BOSY Status`;
    } else {
        titleElement.textContent = 'BOSY Status - Divisions';
    }
}

function updateBosySummary(summary, level) {
    // You can add a summary section above the list if desired
    const summaryContainer = document.getElementById('bosy-summary');
    if (!summaryContainer) return;

    const itemLabel = {
        school: 'Users',
        district: 'Schools',
        division: 'Schools',
        region: 'Divisions',
    }[level] || 'Items';
    const necSummary = level === 'school'
        ? ''
        : `
                <div>
                    <span class="text-gray-600">Total NEC:</span>
                    <span class="font-semibold ml-1">${formatNumber(summary.net_expected_count || 0)}</span>
                </div>`;

    summaryContainer.innerHTML = `
        <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm">
            <div class="flex flex-wrap gap-4 justify-between items-center">
                <div>
                    <span class="text-gray-600">Total ${itemLabel}:</span>
                    <span class="font-semibold ml-1">${summary.total_items}</span>
                </div>
                <div>
                    <span class="text-gray-600">Total Libraries:</span>
                    <span class="font-semibold ml-1">${formatNumber(summary.total_libraries)}</span>
                </div>
                <div>
                    <span class="text-gray-600">Total LR:</span>
                    <span class="font-semibold ml-1">${formatNumber(summary.total_lr)}</span>
                </div>
                ${necSummary}
                <div>
                    <span class="text-gray-600">Progress:</span>
                    <span class="font-semibold ml-1 ${summary.color.replace('bg-', 'text-')}">${summary.overall_percentage}%</span>
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded text-xs">${summary.status}</span>
                </div>
            </div>
        </div>
    `;
}

function renderAllItems(container) {
    container.innerHTML = '';

    const labelMap = {
        school: 'users',
        district: 'schools',
        division: 'schools',
        region: 'divisions',
    };
    const label = labelMap[currentBosyLevel] || 'items';

    if (bosyAllItems.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 text-gray-500">
                No ${label} found for this period.
            </div>
        `;
        return;
    }

    // For universal search at division level, use the full cached dataset
    const sourceItems = (bosySearchTerm && currentBosyLevel === 'division' && bosyFullDivisionItems)
        ? bosyFullDivisionItems
        : bosyAllItems;

    // Filter items by search term
    const filteredItems = bosySearchTerm
        ? sourceItems.filter(item => matchesBosySearch(item, bosySearchTerm))
        : bosyAllItems;

    if (filteredItems.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 text-gray-400">
                <svg class="mx-auto h-10 w-10 mb-3 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <p class="font-medium">No ${label} match "<span class="text-gray-600">${escapeHtml(bosySearchTerm)}</span>"</p>
                <p class="text-xs mt-1">Try a different search term</p>
            </div>
        `;
        return;
    }

    const fragment = document.createDocumentFragment();
    filteredItems.forEach(item => {
        fragment.appendChild(createItemElement(item));
    });
    container.appendChild(fragment);
    void container.offsetHeight;
}


function createItemElement(item) {
    const div = document.createElement('div');
    div.className = 'flex items-center gap-3 bosy-item';
    div.dataset.itemId = String(item.id);

    const statusClass = getStatusClass(item.status);
    const statusLabel = item.status || 'Unknown';
    const subtitleHtml = buildSubtitleHtml(item);

    // Determine initial logo source
    const logoSource = item.logo || '/assets/images/no_image.jpg';

    // Build a clickable link to Print Resources for division/district/region-level items
    const isSchoolItem = (currentBosyLevel === 'division' || currentBosyLevel === 'district')
        && item.district && item.district.id;
    const isDivisionItem = currentBosyLevel === 'region';

    let schoolNameHtml;
    if (isSchoolItem) {
        // Division account: school name → Print Resources school tab, pre-filtered (blue)
        schoolNameHtml = `<a href="/print-resources?tab=school&district=${encodeURIComponent(item.district.id)}&school=${encodeURIComponent(item.id)}"
              class="text-sm sm:text-base font-semibold text-gray-900 hover:text-blue-600 truncate block transition-colors cursor-pointer"
              title="View ${escapeHtml(item.name)} resources in Print Resources">
               ${escapeHtml(item.shortname || item.name)}
           </a>`;
    } else if (isDivisionItem) {
        // Region account: division name → Print Resources, pre-filtered by division (black)
        schoolNameHtml = `<a href="/print-resources?tab=library-hub&hub_view=card&per_page=10&hub_search=&hub_division=${encodeURIComponent(item.id)}&hub_library=all"
              class="text-sm sm:text-base font-semibold text-gray-900 hover:text-blue-600 truncate block transition-colors cursor-pointer"
              title="View ${escapeHtml(item.name)} resources in Print Resources">
               ${escapeHtml(item.shortname || item.name)}
           </a>`;
    } else {
        schoolNameHtml = `<h3 class="text-sm sm:text-base font-semibold text-gray-900 truncate" title="${escapeHtml(item.name)}">
               ${escapeHtml(item.shortname || item.name)}
           </h3>`;
    }

    div.innerHTML = `
        <div class="w-12 h-12 sm:w-14 sm:h-14 flex-shrink-0">
            <img
                src="${logoSource}"
                alt="${escapeHtml(item.name)}"
                class="bosy-logo w-full h-full rounded-full object-cover bg-gray-100 p-1 shadow-sm border border-gray-200"
                loading="lazy"
                decoding="async"
                onload="this.classList.add('bosy-logo-loaded')"
                onerror="this.onerror=null; this.src='/assets/images/no_image.jpg'; this.classList.add('bosy-logo-loaded');">
        </div>
        <div class="flex-1 grid items-center bosy-row-grid gap-2 sm:gap-3 min-w-0">

            <!-- Name + subtitle -->
            <div class="bosy-col-name min-w-0">
                ${schoolNameHtml}
                ${subtitleHtml}
            </div>

            <!-- Progress bar -->
            <div class="bosy-col-bar">
                <div class="w-full bg-gray-200 rounded-full h-4 sm:h-5 overflow-hidden">
                    <div class="${item.color || 'bg-blue-500'} h-full rounded-full origin-left transition-transform duration-300 ease-out"
                         style="--progress: ${item.percentage / 100}; transform: scaleX(var(--progress));">
                    </div>
                </div>
            </div>

            <!-- Count + percentage -->
            <div class="bosy-col-pct text-center">
                <p class="text-xs font-medium text-gray-600 leading-none">${formatNumber(item.total_lr)}</p>
                <p class="text-xs sm:text-sm font-bold text-gray-900 mt-0.5">${item.percentage}%</p>
            </div>

            <!-- Status badge -->
            <div class="bosy-col-status flex justify-end items-center">
                <span
                    class="status-badge ${statusClass}"
                    title="${statusLabel}"
                    aria-label="${statusLabel}">
                    <span class="status-label">${statusLabel}</span>
                </span>
            </div>

        </div>
    `;

    const editNecButton = div.querySelector('[data-edit-school-nec]');
    if (editNecButton) {
        editNecButton.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            openSchoolNecModal(item);
        });
    }

    return div;
}

function buildSubtitleHtml(item) {
    if (currentBosyLevel === 'school') {
        return item.role
            ? `<p class="text-xs text-gray-500 mt-0.5 truncate">${escapeHtml(item.role)}</p>`
            : '';
    }

    const nec = item.net_expected_count || 0;
    const role = item.role
        ? `<span class="text-gray-500">${escapeHtml(item.role)} | </span>`
        : '';

    const editButton = canEditSchoolNec() && currentBosyLevel === 'division' && item.district?.id
        ? `<button type="button" data-edit-school-nec
                   class="ml-1 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded text-indigo-500 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   title="Edit validated NEC for ${escapeHtml(String(item.name || 'school'))}"
                   aria-label="Edit validated NEC for ${escapeHtml(String(item.name || 'school'))}">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 8.5-8.5z"/>
                </svg>
           </button>`
        : '';

    return `<div class="mt-0.5 flex min-w-0 items-center">
                <p class="truncate text-xs text-indigo-500">${role}Net Expected Count: ${formatNumber(nec)}</p>
                ${editButton}
            </div>`;
}

function canEditSchoolNec() {
    return document.getElementById('bosy-divisions-container')?.dataset.canEditSchoolNec === '1';
}

function initSchoolNecEditor() {
    const modal = document.getElementById('schoolNecModal');
    const form = document.getElementById('schoolNecForm');

    if (!modal || !form) return;

    schoolNecEditor = {
        modal,
        form,
        schoolName: document.getElementById('schoolNecSchoolName'),
        input: document.getElementById('schoolNecInput'),
        alert: document.getElementById('schoolNecAlert'),
        saveButton: document.getElementById('saveSchoolNecBtn'),
    };

    document.getElementById('closeSchoolNecModalBtn')?.addEventListener('click', closeSchoolNecModal);
    document.getElementById('cancelSchoolNecModalBtn')?.addEventListener('click', closeSchoolNecModal);

    modal.addEventListener('click', event => {
        if (event.target === modal) closeSchoolNecModal();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeSchoolNecModal();
        }
    });

    form.addEventListener('submit', saveSchoolNec);
}

function openSchoolNecModal(item) {
    if (!schoolNecEditor || !canEditSchoolNec()) return;

    schoolNecEditor.modal.dataset.schoolId = String(item.id);
    schoolNecEditor.schoolName.textContent = item.name || 'Selected school';
    schoolNecEditor.input.value = Number(item.net_expected_count || 0);
    hideSchoolNecAlert();
    setSchoolNecSaving(false);
    schoolNecEditor.modal.classList.remove('hidden');
    schoolNecEditor.modal.classList.add('flex');

    setTimeout(() => {
        schoolNecEditor.input.focus();
        schoolNecEditor.input.select();
    }, 0);
}

function closeSchoolNecModal() {
    if (!schoolNecEditor) return;

    schoolNecEditor.modal.classList.add('hidden');
    schoolNecEditor.modal.classList.remove('flex');
    delete schoolNecEditor.modal.dataset.schoolId;
    hideSchoolNecAlert();
    setSchoolNecSaving(false);
}

async function saveSchoolNec(event) {
    event.preventDefault();

    if (!schoolNecEditor) return;

    const schoolId = schoolNecEditor.modal.dataset.schoolId;
    const estimatedResource = Number(schoolNecEditor.input.value);

    if (!schoolId || !Number.isInteger(estimatedResource) || estimatedResource < 0 || estimatedResource > 2147483647) {
        showSchoolNecAlert('Enter a whole number between 0 and 2,147,483,647.');
        return;
    }

    const container = document.getElementById('bosy-divisions-container');
    const updateBase = container?.dataset.schoolNecUpdateBase;

    if (!updateBase) {
        showSchoolNecAlert('The NEC update endpoint is unavailable. Please refresh the page.');
        return;
    }

    setSchoolNecSaving(true);
    hideSchoolNecAlert();

    try {
        const response = await fetch(`${updateBase}/${encodeURIComponent(schoolId)}/nec`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ estimated_resource: estimatedResource }),
        });

        const data = await response.json();

        if (!response.ok) {
            const validationMessage = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : null;
            showSchoolNecAlert(validationMessage || data.error || data.message || 'Unable to update the school NEC.');
            return;
        }

        closeSchoolNecModal();
        refreshCurrentBosyStatus();
    } catch (error) {
        console.error('School NEC update failed:', error);
        showSchoolNecAlert('A network error occurred. Please try again.');
    } finally {
        setSchoolNecSaving(false);
    }
}

function refreshCurrentBosyStatus() {
    const regionSelect = document.getElementById('regionFilter');
    const districtSelect = document.getElementById('divisionFilter');
    const printTypeSelect = document.getElementById('bosyPrintTypeFilter');

    fetchBosyStatus(
        true,
        regionSelect?.value || '',
        districtSelect?.value || '',
        printTypeSelect?.value || ''
    );
}

function showSchoolNecAlert(message) {
    if (!schoolNecEditor?.alert) return;

    schoolNecEditor.alert.textContent = message;
    schoolNecEditor.alert.className = 'rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700';
}

function hideSchoolNecAlert() {
    if (!schoolNecEditor?.alert) return;

    schoolNecEditor.alert.textContent = '';
    schoolNecEditor.alert.className = 'hidden rounded-lg px-3 py-2 text-sm';
}

function setSchoolNecSaving(isSaving) {
    if (!schoolNecEditor?.saveButton) return;

    schoolNecEditor.saveButton.disabled = isSaving;
    schoolNecEditor.saveButton.textContent = isSaving ? 'Saving...' : 'Save NEC';
}

function getStatusClass(status) {
    const s = (status || '').toLowerCase();
    if (s.includes('complete')) return 'status-complete';
    if (s.includes('in-review')) return 'status-review';
    if (s.includes('advanced')) return 'status-advanced';
    if (s.includes('in-progress')) return 'status-progress';
    if (s.includes('partial')) return 'status-partial';
    if (s.includes('no population')) return 'status-no-population';
    if (s.includes('not started')) return 'status-pending';
    return 'status-default';
}

function showSkeletonLoaders(container) {
    container.innerHTML = '';
    for (let i = 0; i < 6; i++) {
        const skeleton = document.createElement('div');
        skeleton.className = 'bosy-skeleton-item flex items-center gap-3 animate-pulse py-2';
        skeleton.innerHTML = `
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-200 flex-shrink-0"></div>
            <div class="flex-1 grid bosy-row-grid gap-2 sm:gap-3">
                <div class="bosy-col-name">
                    <div class="h-4 bg-gray-200 rounded w-20 mb-1.5"></div>
                    <div class="h-3 bg-gray-200 rounded w-14"></div>
                </div>
                <div class="bosy-col-bar">
                    <div class="h-4 sm:h-5 bg-gray-200 rounded-full w-full"></div>
                </div>
                <div class="bosy-col-pct">
                    <div class="h-3 bg-gray-200 rounded w-10 mb-1 mx-auto"></div>
                    <div class="h-4 bg-gray-200 rounded w-8 mx-auto"></div>
                </div>
                <div class="bosy-col-status flex justify-end">
                    <div class="h-7 bg-gray-200 rounded w-5 sm:w-20"></div>
                </div>
            </div>
        `;
        container.appendChild(skeleton);
    }

    animateBosyContent(container);
}

function animateBosyContent(container) {
    if (!container || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    container.getAnimations().forEach(animation => animation.cancel());
    container.animate(
        [
            { opacity: 0, transform: 'translateY(6px)' },
            { opacity: 1, transform: 'translateY(0)' }
        ],
        { duration: 260, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
    );
}

function updateBosyPeriod(period) {
    const periodEl = document.querySelector('.period-display');
    const yearEl = document.querySelector('.year-display');
    if (periodEl && period.start && period.end) {
        periodEl.textContent = `${period.start} – ${period.end}`;
    }
    if (yearEl && period.year) {
        yearEl.textContent = `CY ${period.year}`;
    }
}

function formatNumber(num) {
    return Number(num).toLocaleString('en-US') || '0';
}

function showError(container, message) {
    container.innerHTML = `
        <div class="text-center py-16">
            <svg class="mx-auto h-14 w-14 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-4 text-red-600 font-medium">${escapeHtml(message)}</p>
            <button onclick="initBosyStatus()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                Try Again
            </button>
        </div>
    `;
    animateBosyContent(container);
}

function escapeHtml(unsafe) {
    return unsafe.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}

// ── BOSY Search Helpers ──

/**
 * Check if an item matches the search term.
 * Searches across: name, shortname, district name, parent (division) name.
 */
function matchesBosySearch(item, term) {
    const haystack = [
        item.name,
        item.shortname,
        item.district?.name,
        item.parent?.name,
        item.role,
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    // Support multi-word search: every word must appear somewhere
    const words = term.split(/\s+/).filter(Boolean);
    return words.every(word => haystack.includes(word));
}

/**
 * Show/hide the clear button based on input content.
 */
function toggleSearchClear(input, clearBtn) {
    if (!clearBtn) return;
    if (input.value.trim().length > 0) {
        clearBtn.classList.remove('hidden');
    } else {
        clearBtn.classList.add('hidden');
    }
}

/**
 * Update the search result count badge.
 */
function updateSearchCount(countEl) {
    if (!countEl) return;

    const searchInput  = document.getElementById('bosySearchInput');
    const searchClear  = document.getElementById('bosySearchClear');

    if (!bosySearchTerm) {
        countEl.classList.add('hidden');
        countEl.textContent = '';
        // Remove the spacing classes so clear button returns to default position
        if (searchClear)  searchClear.classList.remove('with-count');
        if (searchInput)  searchInput.classList.remove('has-count');
        return;
    }

    // Use the full division dataset when searching at division level
    const sourceItems = (currentBosyLevel === 'division' && bosyFullDivisionItems)
        ? bosyFullDivisionItems
        : bosyAllItems;

    const matched = sourceItems.filter(item => matchesBosySearch(item, bosySearchTerm)).length;
    const total = sourceItems.length;
    countEl.textContent = `${matched} / ${total}`;
    countEl.classList.remove('hidden');

    // Shift clear button left and widen input padding to avoid overlapping the count pill
    if (searchClear)  searchClear.classList.add('with-count');
    if (searchInput)  searchInput.classList.add('has-count');
}

/**
 * Ensure we have the full division dataset (all districts) for universal search.
 * Fetches lazily and caches. Calls the callback when data is ready.
 */
function ensureFullDivisionData(printTypeId, callback) {
    // Already cached and same print type → use cache immediately
    if (bosyFullDivisionItems && bosyFullDivisionPrintType === printTypeId) {
        callback();
        return;
    }

    // Already fetching → wait, the callback will fire when fetch completes via next input event
    if (bosyFullDivisionFetching) {
        return;
    }

    bosyFullDivisionFetching = true;

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const params = new URLSearchParams();
    // Intentionally omit district_filter to get ALL schools in the division
    if (printTypeId) params.set('print_type_id', printTypeId);

    const url = `/dashboard/bosy-status?${params.toString()}`;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token || ''
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (!data.error) {
                bosyFullDivisionItems = data.items || [];
                bosyFullDivisionPrintType = printTypeId;
            }
            bosyFullDivisionFetching = false;
            callback();
        })
        .catch(err => {
            console.error('Failed to fetch full division data for search:', err);
            bosyFullDivisionFetching = false;
            // Fall back to local items
            callback();
        });
}
