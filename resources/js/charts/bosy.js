document.addEventListener('DOMContentLoaded', () => {
    initBosyStatus();
    // setInterval(initBosyStatus, 300000); // uncomment for auto-refresh every 5 min
});

let bosyAllItems = [];
let currentBosyLevel = 'region'; // 'region' or 'division'

function initBosyStatus() {
    const regionSelect = document.getElementById('regionFilter');
    const districtSelect = document.getElementById('divisionFilter'); // level 3 district dropdown

    // Initial load
    const initialHubFilter = regionSelect ? regionSelect.value : '';
    const initialDistrictFilter = districtSelect ? districtSelect.value : '';
    fetchBosyStatus(true, initialHubFilter, initialDistrictFilter);

    // Level 4+: hub filter changes
    if (regionSelect) {
        regionSelect.addEventListener('change', () => {
            fetchBosyStatus(true, regionSelect.value, '');
        });
    }

    // Level 3: district filter changes
    if (districtSelect) {
        districtSelect.addEventListener('change', () => {
            fetchBosyStatus(true, '', districtSelect.value);
        });
    }
}

function fetchBosyStatus(isFullRefresh = false, hubFilter = '', districtFilter = '') {
    const container = document.getElementById('bosy-divisions-container');
    if (!container) return;

    if (isFullRefresh) {
        showSkeletonLoaders(container);
        bosyAllItems = [];
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    // Build URL with both query params
    const params = new URLSearchParams();
    if (hubFilter) params.set('hub_filter', hubFilter);
    if (districtFilter) params.set('district_filter', districtFilter);

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

            setTimeout(() => {
                container.style.opacity = '1';
            }, 100);
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

    const itemLabel = level === 'division' ? 'Schools' : 'Divisions';

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

    if (bosyAllItems.length === 0) {
        const labelMap = {
            school: 'users',
            district: 'schools',
            division: 'schools',
            region: 'divisions',
        };
        const label = labelMap[currentBosyLevel] || 'items';
        container.innerHTML = `
            <div class="text-center py-12 text-gray-500">
                No ${label} found for this period.
            </div>
        `;
        return;
    }

    const fragment = document.createDocumentFragment();
    bosyAllItems.forEach(item => {
        fragment.appendChild(createItemElement(item));
    });
    container.appendChild(fragment);
    void container.offsetHeight;
}


function createItemElement(item) {
    const div = document.createElement('div');
    div.className = 'flex items-center gap-3 bosy-item';

    const statusClass = getStatusClass(item.status);
    const statusLabel = item.status || 'Unknown';

    // For school level, show role under name instead of pre-inventory count
    const subtitleHtml = item.role
        ? `<p class="text-xs text-indigo-500 mt-0.5 truncate">${escapeHtml(item.role)}</p>`
        : `<p class="text-xs text-indigo-500 mt-0.5 truncate">Pre-Inventory: ${formatNumber(item.estimated_resource || 0)}</p>`;

    // Determine initial logo source
    const logoSource = item.logo || '/assets/images/no_image.jpg';

    div.innerHTML = `
        <div class="w-12 h-12 sm:w-14 sm:h-14 flex-shrink-0">
            <img
                src="${logoSource}"
                alt="${item.name}"
                class="w-full h-full rounded-full object-cover bg-white p-1 shadow-sm border border-gray-200"
                loading="lazy"
                decoding="async"
                onerror="this.src='/assets/images/no_image.jpg'; this.onerror=null;">
        </div>
        <div class="flex-1 grid items-center bosy-row-grid gap-2 sm:gap-3 min-w-0">

            <!-- Name + subtitle -->
            <div class="bosy-col-name min-w-0">
                <h3 class="text-sm sm:text-base font-semibold text-gray-900 truncate" title="${item.name}">
                    ${escapeHtml(item.shortname || item.name)}
                </h3>
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

    return div;
}

function getStatusClass(status) {
    const s = (status || '').toLowerCase();
    if (s.includes('complete')) return 'status-complete';
    if (s.includes('in-review')) return 'status-review';
    if (s.includes('advanced')) return 'status-advanced';
    if (s.includes('in-progress')) return 'status-progress';
    if (s.includes('partial')) return 'status-partial';
    if (s.includes('not started')) return 'status-pending';
    return 'status-default';
}

function showSkeletonLoaders(container) {
    container.innerHTML = '';
    for (let i = 0; i < 6; i++) {
        const skeleton = document.createElement('div');
        skeleton.className = 'flex items-center gap-3 animate-pulse py-2';
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
}

function escapeHtml(unsafe) {
    return unsafe.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}
