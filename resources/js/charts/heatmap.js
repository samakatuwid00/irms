// heatmap.js

import {
    applyChartTheme,
    bindChartTheme,
    chartTheme,
    chartFullscreenBackground,
    chartLoadingOptions,
    themedDataViewStyles,
    themedNoDataHtml,
} from './theme';
import { applySchoolOnlyParam, bindDivisionHubToggle } from './source-filter';

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _heatmapFullData = null;
let _heatmapChart    = null;

function normalizeHeatmapResult(result) {
    const subjects = Array.isArray(result?.x_axis) ? result.x_axis : [];
    const gradeLevels = Array.isArray(result?.y_axis) ? result.y_axis : [];
    const rawData = (Array.isArray(result?.series_data) ? result.series_data : [])
        .map(([subjectIndex, gradeIndex, quantity]) => [
            Number(subjectIndex),
            Number(gradeIndex),
            Number(quantity),
        ])
        .filter(([subjectIndex, gradeIndex, quantity]) => (
            Number.isInteger(subjectIndex)
            && Number.isInteger(gradeIndex)
            && Number.isFinite(quantity)
            && subjectIndex >= 0
            && subjectIndex < subjects.length
            && gradeIndex >= 0
            && gradeIndex < gradeLevels.length
        ));

    const dataMax = rawData.length
        ? Math.max(...rawData.map(([, , quantity]) => quantity))
        : 0;

    return {
        subjects,
        gradeLevels,
        rawData,
        maxValue: Math.max(1, Number(result?.max_value) || 0, dataMax),
    };
}

function resizeHeatmapAfterRender() {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => _heatmapChart?.resize());
    });
}

// Get currently visible (filtered by Key Stage) data
function getCurrentHeatmapData() {
    if (!_heatmapFullData || !_heatmapChart) return null;

    const { subjects, gradeLevels, rawData, maxValue } = _heatmapFullData;
    const ksSelect = document.getElementById('schoolYearFilter');
    const keyStage = ksSelect ? ksSelect.value : null;

    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? gradeLevels;

    const allowedGradeIndices = gradeLevels
        .map((g, i) => ({ g, i }))
        .filter(({ g }) => allowedGrades.includes(g))
        .map(({ i }) => i);

    const filteredGradeLevels = allowedGradeIndices.map(i => gradeLevels[i]);

    const gradeIndexMap = new Map(
        allowedGradeIndices.map((origIdx, newIdx) => [origIdx, newIdx])
    );

    const visibleSubjectIndices = new Set(
        rawData
            .filter(([, gradeIdx]) => gradeIndexMap.has(gradeIdx))
            .map(([subjIdx]) => subjIdx)
    );

    const filteredSubjects = subjects.filter((_, index) => visibleSubjectIndices.has(index));

    const filteredRawData = rawData
        .filter(([subjIdx, gradeIdx]) => gradeIndexMap.has(gradeIdx) && visibleSubjectIndices.has(subjIdx))
        .map(([subjIdx, gradeIdx, qty]) => ({
            subject: subjects[subjIdx],
            grade: gradeLevels[gradeIdx],
            qty: qty
        }));

    return {
        subjects: filteredSubjects,
        grades: filteredGradeLevels,
        data: filteredRawData,
        maxValue
    };
}

// Export current filtered data to real Excel (.xlsx)
function exportToExcel() {
    const data = getCurrentHeatmapData();
    if (!data || !data.data.length) {
        alert('No chart data available');
        return;
    }

    if (typeof XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.onload = () => performHeatmapExcelExport(data);
        script.onerror = () => alert('Failed to load Excel library');
        document.head.appendChild(script);
        return;
    }

    performHeatmapExcelExport(data);
}

function performHeatmapExcelExport(data) {
    const wb = XLSX.utils.book_new();
    
    const rows = [];
    const header = ['Subject', 'Grade', 'Quantity'];
    rows.push(header);

    const sortedData = [...data.data].sort((a, b) => {
        if (a.subject !== b.subject) return a.subject.localeCompare(b.subject);
        return a.grade.localeCompare(b.grade);
    });

    sortedData.forEach(item => {
        rows.push([item.subject, item.grade, item.qty]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [
        { wch: 30 },  // Subject
        { wch: 18 },  // Grade
        { wch: 15 }   // Quantity
    ];

    XLSX.utils.book_append_sheet(wb, ws, "Heatmap");

    const ksSelect = document.getElementById('schoolYearFilter');
    const suffix = ksSelect && ksSelect.value ? `_${ksSelect.value}` : '';
    XLSX.writeFile(wb, `LR_Heatmap${suffix}_${new Date().toISOString().slice(0,10)}.xlsx`);
}

// Filter and re-render chart based on Key Stage
function filterAndRenderHeatmapChart(keyStage) {
    if (!_heatmapFullData || !_heatmapChart) return;

    const { subjects, gradeLevels, rawData, maxValue } = _heatmapFullData;
    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? gradeLevels;

    const allowedGradeIndices = gradeLevels
        .map((g, i) => ({ g, i }))
        .filter(({ g }) => allowedGrades.includes(g))
        .map(({ i }) => i);

    const filteredGradeLevels = allowedGradeIndices.map(i => gradeLevels[i]);

    const gradeIndexMap = new Map(
        allowedGradeIndices.map((origIdx, newIdx) => [origIdx, newIdx])
    );

    const visibleSubjectIndices = [...new Set(
        rawData
            .filter(([, gradeIdx]) => gradeIndexMap.has(gradeIdx))
            .map(([subjIdx]) => subjIdx)
    )];

    const subjectIndexMap = new Map(
        visibleSubjectIndices.map((origIdx, newIdx) => [origIdx, newIdx])
    );

    const filteredSubjects = visibleSubjectIndices.map(index => subjects[index]);

    const filteredData = rawData
        .filter(([subjIdx, gradeIdx]) => subjectIndexMap.has(subjIdx) && gradeIndexMap.has(gradeIdx))
        .map(([subjIdx, gradeIdx, qty]) => [
            subjectIndexMap.get(subjIdx),
            gradeIndexMap.get(gradeIdx),
            qty
        ]);

    const filteredMax = filteredData.length
        ? Math.max(1, ...filteredData.map(([,, qty]) => qty))
        : Math.max(1, maxValue);

    _heatmapChart.setOption({
        xAxis:     { data: filteredSubjects },
        yAxis:     { data: filteredGradeLevels },
        visualMap: { max: filteredMax },
        series:    [{ data: filteredData }]
    });

    resizeHeatmapAfterRender();
}

async function fetchHeatmapData(printTypeId = null) {
    const url = applySchoolOnlyParam(new URL('/chart/heatmap', window.location.origin));
    const params = new URLSearchParams();
    if (printTypeId) params.append('print_type_id', printTypeId);
    params.forEach((value, key) => url.searchParams.set(key, value));

    const response = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
    });

    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
}

function buildHeatmapOption(result) {
    const { subjects, gradeLevels, rawData, maxValue } = normalizeHeatmapResult(result);
    const theme       = chartTheme();
    const heatColors  = theme.isDark
        ? ['#172033', '#1e3a5f', '#164e63', '#155e75', '#0e7490', '#0369a1', '#1d4ed8', '#2563eb']
        : [
            '#e6f3ff', '#cce6ff', '#b3d9ff', '#99ccff', '#80bfff',
            '#66b3ff', '#4da6ff', '#3399ff', '#1a8cff', '#0066cc'
        ];

    return applyChartTheme({
        title: {
            left: 'center',
            top: 10
        },

        tooltip: {
            position: 'top',
            formatter: function (params) {
                const currentOption = _heatmapChart?.getOption();
                const subj  = currentOption?.xAxis?.[0]?.data?.[params.data[0]] ?? subjects[params.data[0]];
                const grade = currentOption?.yAxis?.[0]?.data?.[params.data[1]] ?? gradeLevels[params.data[1]];
                const val   = params.data[2];
                return `${subj}<br>${grade}<br>Total Qty: ${val}`;
            }
        },

        toolbox: {
            show: true,
            orient: 'horizontal',
            top: 5,
            right: 10,
            feature: {
                mark: { show: true },
                dataView: {
                    show: true,
                    readOnly: false,
                    title: 'Data View',
                    lang: ['Data View', 'Close', 'Refresh'],
                    
                    // Excel-style Data View Table
                    optionToContent: function (opt) {
                        const data = getCurrentHeatmapData();
                        if (!data || !data.data.length) {
                            return themedNoDataHtml();
                        }

                        let tableHTML = `
                            ${themedDataViewStyles('heatmap-excel-table')}
                            <table id="heatmap-excel-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Grade</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                        const sorted = [...data.data].sort((a, b) => {
                            if (a.subject !== b.subject) return a.subject.localeCompare(b.subject);
                            return a.grade.localeCompare(b.grade);
                        });

                        sorted.forEach(item => {
                            tableHTML += `
                                <tr>
                                    <td>${item.subject}</td>
                                    <td>${item.grade}</td>
                                    <td>${item.qty}</td>
                                </tr>`;
                        });

                        tableHTML += `</tbody></table>`;
                        return tableHTML;
                    }
                },
                magicType: { show: true, type: ['line', 'bar', 'stack'] },
                restore: { show: true },
                saveAsImage: { show: true },

                // Dedicated Excel Export Button
                myExportExcel: {
                    show: true,
                    title: 'Export Excel',
                    icon: 'path://M704 64H256c-35.3 0-64 28.7-64 64v768c0 35.3 28.7 64 64 64h512c35.3 0 64-28.7 64-64V320L704 64zM704 160l64 64h-64v-64zM352 448h96l64 96 64-96h96L576 576l96 128h-96l-64-96-64 96h-96l96-128-96-128z',
                    onclick: function () {
                        exportToExcel();
                    }
                },

                // Fullscreen with white background (updated as requested)
                myFullScreen: {
                    show: true,
                    title: 'Fullscreen',
                    icon: 'path://M128 128h256v256H128z',
                    onclick: function () {
                        const chartDom = document.getElementById('heatmap');
                        if (!document.fullscreenElement) {
                            chartDom.dataset.originalBg = chartDom.style.backgroundColor || '';
                            chartDom.style.backgroundColor = chartFullscreenBackground();
                            chartDom.requestFullscreen()
                                .then(() => _heatmapChart.resize())
                                .catch(err => {
                                    console.error('Fullscreen failed:', err);
                                    chartDom.style.backgroundColor = chartDom.dataset.originalBg;
                                });
                        } else {
                            document.exitFullscreen().then(() => {
                                chartDom.style.backgroundColor = chartDom.dataset.originalBg || '';
                                _heatmapChart.resize();
                            });
                        }
                    }
                }
            }
        },

        xAxis: {
            type: 'category',
            data: subjects,
            splitArea: {
                show: true,
                areaStyle: theme.isDark ? { color: ['rgba(15, 23, 42, 0.94)', 'rgba(30, 41, 59, 0.80)'] } : undefined,
            },
            axisLabel: { rotate: 55, fontSize: 11, width: 95, interval: 0 }
        },

        yAxis: {
            type: 'category',
            data: gradeLevels,
            splitArea: {
                show: true,
                areaStyle: theme.isDark ? { color: ['rgba(15, 23, 42, 0.94)', 'rgba(30, 41, 59, 0.80)'] } : undefined,
            },
            axisLabel: { fontSize: 12, margin: 12 }
        },

        grid: {
            top: '12%',
            bottom: '18%',
            left: '8%',
            right: '5%',
            containLabel: true
        },

        visualMap: {
            type: 'continuous',
            min: 0,
            max: maxValue,
            calculable: true,
            orient: 'horizontal',
            left: 'center',
            bottom: '8%',
            text: ['High', 'Low'],
            itemWidth: 20,
            itemHeight: 140,
            textGap: 8,
            inRange: {
                color: heatColors
            }
        },

        series: [{
            name: 'Learning Resources Quantity',
            type: 'heatmap',
            data: rawData,
            label: {
                show: true,
                color: theme.isDark ? '#f8fafc' : '#000',
                fontWeight: '500',
                fontSize: 10
            },
            itemStyle: theme.isDark ? {
                borderColor: '#0f172a',
                borderWidth: 1,
            } : undefined,
            emphasis: {
                itemStyle: {
                    shadowBlur: 12,
                    shadowColor: theme.isDark ? 'rgba(96, 165, 250, 0.46)' : 'rgba(0, 0, 0, 0.5)',
                    borderColor: theme.isDark ? '#93c5fd' : undefined,
                }
            }
        }],

        media: [
            {
                query: { maxWidth: 900 },
                option: {
                    visualMap: { bottom: '10%', itemHeight: 110, itemWidth: 18 },
                    grid: { bottom: '18%' }
                }
            },
            {
                query: { maxWidth: 640 },
                option: {
                    visualMap: { bottom: '12%', itemHeight: 90, itemWidth: 16, textGap: 5 },
                    grid: { bottom: '24%', left: '12%', right: '5%' },
                    xAxis: { axisLabel: { rotate: 60, fontSize: 10 } }
                }
            }
        ]
    });
}

function reloadHeatmapChart() {
    if (!_heatmapChart) return;

    const chartDom = document.getElementById('heatmap');
    window.DashboardChartLoading?.show(chartDom);
    _heatmapChart.showLoading(chartLoadingOptions());

    const printTypeId = document.getElementById('printTypeFilter')?.value || null;

    fetchHeatmapData(printTypeId)
        .then(result => {
            if (!result.x_axis || !result.y_axis || !Array.isArray(result.series_data)) {
                throw new Error('Invalid heatmap data format from server');
            }

            const { subjects, gradeLevels, rawData, maxValue } = normalizeHeatmapResult(result);

            _heatmapFullData = { subjects, gradeLevels, rawData, maxValue };

            const option = buildHeatmapOption(result);
            _heatmapChart.setOption(option, true);

            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) {
                filterAndRenderHeatmapChart(ksSelect.value);
            } else {
                resizeHeatmapAfterRender();
            }
        })
        .catch(err => {
            console.error('Heatmap reload failed:', err);
            _heatmapChart.setOption({
                title: { text: 'Error loading heatmap data', left: 'center', top: 'center' }
            });
        })
        .finally(() => {
            _heatmapChart.hideLoading();
            window.DashboardChartLoading?.hide(chartDom);
        });
}

async function initHeatmapChart() {
    const chartDom = document.getElementById('heatmap');
    if (!chartDom) {
        console.warn('Chart container #heatmap not found');
        return;
    }

    // If the container is hidden (e.g. tab not yet active), defer until visible
    if (chartDom.offsetParent === null) {
        const card = chartDom.closest('[data-chart-card]');
        if (card) {
            const mo = new MutationObserver(() => {
                if (!card.classList.contains('hidden') && chartDom.offsetParent !== null) {
                    mo.disconnect();
                    initHeatmapChart();
                }
            });
            mo.observe(card, { attributes: true, attributeFilter: ['class'] });
        }
        return;
    }

    window.DashboardChartLoading?.show(chartDom);

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);
        _heatmapChart = myChart;

        const result = await fetchHeatmapData();

        const normalized = normalizeHeatmapResult(result);
        _heatmapFullData = normalized;

        const option = buildHeatmapOption({
            x_axis: normalized.subjects,
            y_axis: normalized.gradeLevels,
            series_data: normalized.rawData,
            max_value: normalized.maxValue,
        });
        myChart.setOption(option, true);

        bindChartTheme(myChart, () => {
            if (!_heatmapFullData) return;
            const rebuilt = buildHeatmapOption({
                x_axis: _heatmapFullData.subjects,
                y_axis: _heatmapFullData.gradeLevels,
                series_data: _heatmapFullData.rawData,
                max_value: _heatmapFullData.maxValue,
            });
            myChart.setOption(rebuilt, true);
            const currentKsSelect = document.getElementById('schoolYearFilter');
            if (currentKsSelect) filterAndRenderHeatmapChart(currentKsSelect.value);
        });

        // Filter listeners
        const printFilter = document.getElementById('printTypeFilter');
        if (printFilter) {
            printFilter.addEventListener('change', reloadHeatmapChart);
        }

        bindDivisionHubToggle(() => {
            reloadHeatmapChart();
        });

        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderHeatmapChart(ksSelect.value);
            ksSelect.addEventListener('change', (e) => {
                window.DashboardChartLoading?.transition(
                    chartDom,
                    () => filterAndRenderHeatmapChart(e.target.value)
                );
            });
        } else {
            resizeHeatmapAfterRender();
        }

        if (window.registerChart) window.registerChart('lr-heatmap', myChart);

        const resizeObserver = new ResizeObserver(() => myChart.resize());
        resizeObserver.observe(chartDom);

        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

        window.DashboardChartLoading?.hide(chartDom);

    } catch (err) {
        console.error('Failed to initialize Heatmap chart:', err);
        window.DashboardChartLoading?.hide(chartDom);
    }
}

// No lazy loading — init directly when the module loads.
// The @vite entry script runs after DOM is parsed, and by the time the user
// switches to the heatmap tab, the data fetch will have completed.
initHeatmapChart();
