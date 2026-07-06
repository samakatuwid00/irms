// heatmap.js

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _heatmapFullData = null;
let _heatmapChart    = null;

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
        ? Math.max(...filteredData.map(([,, qty]) => qty))
        : maxValue;

    _heatmapChart.setOption({
        xAxis:     { data: filteredSubjects },
        yAxis:     { data: filteredGradeLevels },
        visualMap: { max: filteredMax },
        series:    [{ data: filteredData }]
    });
}

async function fetchHeatmapData(printTypeId = null) {
    let url = '/chart/heatmap';
    const params = new URLSearchParams();
    if (printTypeId) params.append('print_type_id', printTypeId);
    if (params.toString()) url += '?' + params.toString();

    const response = await fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
    });

    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    return response.json();
}

function buildHeatmapOption(result) {
    const subjects    = result.x_axis;
    const gradeLevels = result.y_axis;
    const rawData     = result.series_data;
    const maxValue    = result.max_value || 100;

    return {
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
                            return '<div style="padding:20px;color:#666;">No data available</div>';
                        }

                        let tableHTML = `
                            <style>
                                #heatmap-excel-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-family: 'Segoe UI', Arial, sans-serif;
                                    font-size: 14px;
                                    margin: 10px 0;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                }
                                #heatmap-excel-table th, #heatmap-excel-table td {
                                    border: 1px solid #999;
                                    padding: 10px 12px;
                                    text-align: center;
                                    white-space: nowrap;
                                }
                                #heatmap-excel-table th {
                                    background: linear-gradient(#f8f8f8, #e8e8e8);
                                    font-weight: bold;
                                    color: #333;
                                    position: sticky;
                                    top: 0;
                                    z-index: 1;
                                }
                                #heatmap-excel-table tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                #heatmap-excel-table td:first-child {
                                    text-align: left;
                                    font-weight: bold;
                                }
                            </style>
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
                            chartDom.style.backgroundColor = '#ffffff';
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
            splitArea: { show: true },
            axisLabel: { rotate: 55, fontSize: 11, width: 95, interval: 0 }
        },

        yAxis: {
            type: 'category',
            data: gradeLevels,
            splitArea: { show: true },
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
                color: [
                    '#e6f3ff', '#cce6ff', '#b3d9ff', '#99ccff', '#80bfff',
                    '#66b3ff', '#4da6ff', '#3399ff', '#1a8cff', '#0066cc'
                ]
            }
        },

        series: [{
            name: 'Learning Resources Quantity',
            type: 'heatmap',
            data: rawData,
            label: {
                show: true,
                color: '#000',
                fontWeight: '500',
                fontSize: 10
            },
            emphasis: {
                itemStyle: {
                    shadowBlur: 12,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
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
    };
}

function reloadHeatmapChart() {
    if (!_heatmapChart) return;

    const chartDom = document.getElementById('heatmap');
    window.DashboardChartLoading?.show(chartDom);
    _heatmapChart.showLoading();

    const printTypeId = document.getElementById('printTypeFilter')?.value || null;

    fetchHeatmapData(printTypeId)
        .then(result => {
            if (!result.x_axis || !result.y_axis || !Array.isArray(result.series_data)) {
                throw new Error('Invalid heatmap data format from server');
            }

            const subjects    = result.x_axis;
            const gradeLevels = result.y_axis;
            const rawData     = result.series_data;
            const maxValue    = result.max_value || 100;

            _heatmapFullData = { subjects, gradeLevels, rawData, maxValue };

            const option = buildHeatmapOption(result);
            _heatmapChart.setOption(option, true);

            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) {
                filterAndRenderHeatmapChart(ksSelect.value);
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

    window.DashboardChartLoading?.show(chartDom);

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);
        _heatmapChart = myChart;

        const result = await fetchHeatmapData();

        _heatmapFullData = {
            subjects: result.x_axis,
            gradeLevels: result.y_axis,
            rawData: result.series_data,
            maxValue: result.max_value || 100
        };

        const option = buildHeatmapOption(result);
        myChart.setOption(option);

        // Filter listeners
        const printFilter = document.getElementById('printTypeFilter');
        if (printFilter) {
            printFilter.addEventListener('change', reloadHeatmapChart);
        }

        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderHeatmapChart(ksSelect.value);
            ksSelect.addEventListener('change', (e) => {
                window.DashboardChartLoading?.transition(
                    chartDom,
                    () => filterAndRenderHeatmapChart(e.target.value)
                );
            });
        }

        if (window.registerChart) window.registerChart('heatmap', myChart);

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

function setupLazyHeatmapChartLoading() {
    const chartContainer = document.getElementById('heatmap');
    if (!chartContainer) {
        console.warn('Chart container #heatmap not found');
        return;
    }

    if (!('IntersectionObserver' in window)) {
        initHeatmapChart();
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            if (entries[0].isIntersecting) {
                initHeatmapChart();
                obs.disconnect();
            }
        },
        { root: null, rootMargin: '0px', threshold: 0.1 }
    );

    observer.observe(chartContainer);
}

setupLazyHeatmapChartLoading();
