// availability.js

// Key Stage grade index ranges
const KEY_STAGE_RANGES = {
    'K1': { label: 'Kindergarten', grades: ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'] },
    'K2': { label: 'Key Stage 2',  grades: ['Grade 4', 'Grade 5', 'Grade 6'] },
    'JH': { label: 'Junior High',  grades: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'] },
    'SH': { label: 'Senior High',  grades: ['Grade 11', 'Grade 12'] },
};

// Store original full data globally
let _availabilityFullData = null;
let _availabilityChart = null;

// Get currently visible (filtered by Key Stage) data
function getCurrentChartData() {
    if (!_availabilityFullData || !_availabilityChart) return null;

    const ksSelect = document.getElementById('schoolYearFilter');
    const keyStage = ksSelect ? ksSelect.value : null;
    
    const { grade_level, series } = _availabilityFullData;
    let allowedGrades = grade_level;

    if (keyStage && KEY_STAGE_RANGES[keyStage]) {
        allowedGrades = KEY_STAGE_RANGES[keyStage].grades;
    }

    const indices = grade_level
        .map((g, i) => ({ grade: g, index: i }))
        .filter(({ grade }) => allowedGrades.includes(grade))
        .map(({ index }) => index);

    const filteredGrades = indices.map(i => grade_level[i]);

    const filteredSeries = series.map(s => ({
        name: s.name,
        data: indices.map(i => s.data[i])
    }));

    return { grades: filteredGrades, series: filteredSeries };
}

// Export current data to real Excel (.xlsx)
function exportToExcel() {
    const data = getCurrentChartData();
    if (!data) {
        alert('No chart data available');
        return;
    }

    if (typeof XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.onload = () => performExcelExport(data);
        script.onerror = () => alert('Failed to load Excel library');
        document.head.appendChild(script);
        return;
    }

    performExcelExport(data);
}

function performExcelExport(data) {
    const wb = XLSX.utils.book_new();
    
    const rows = [];
    const header = ['Grade', ...data.series.map(s => s.name)];
    rows.push(header);

    data.grades.forEach((grade, idx) => {
        const row = [grade];
        data.series.forEach(s => {
            row.push(s.data[idx] ?? '');
        });
        rows.push(row);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = header.map(() => ({ wch: 18 }));

    XLSX.utils.book_append_sheet(wb, ws, "Availability");

    const ksSelect = document.getElementById('schoolYearFilter');
    const suffix = ksSelect && ksSelect.value ? `_${ksSelect.value}` : '';
    XLSX.writeFile(wb, `LR_Availability${suffix}_${new Date().toISOString().slice(0,10)}.xlsx`);
}

// ==================== CHART FUNCTIONS ====================

function filterAndRenderChart(keyStage) {
    if (!_availabilityFullData || !_availabilityChart) return;

    const { grade_level, series } = _availabilityFullData;
    const allowedGrades = KEY_STAGE_RANGES[keyStage]?.grades ?? grade_level;

    const indices = grade_level
        .map((g, i) => ({ grade: g, index: i }))
        .filter(({ grade }) => allowedGrades.includes(grade))
        .map(({ index }) => index);

    const filteredGrades = indices.map(i => grade_level[i]);
    const filteredSeries = series.map(s => ({
        ...s,
        data: indices.map(i => s.data[i])
    }));

    _availabilityChart.setOption({
        xAxis: [{ data: filteredGrades }],
        series: filteredSeries,
        legend: { data: filteredSeries.map(s => s.name) }
    }, false);
}

async function fetchAvailabilityData() {
    const printTypeSelect = document.getElementById('printTypeFilter');
    const printTypeId = printTypeSelect ? printTypeSelect.value : '';

    const url = new URL('/chart/lr-availability', window.location.origin);
    if (printTypeId) url.searchParams.set('print_type_id', printTypeId);

    const response = await fetch(url.toString(), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    });

    if (!response.ok) throw new Error(`Failed to load chart data: ${response.status}`);
    return await response.json();
}

function buildChartOption(result, chartDom, myChart) {
    const labelOption = {
        show: false,
        position: 'insideBottom',
        distance: 15,
        align: 'left',
        verticalAlign: 'middle',
        rotate: 90,
        formatter: '{c} {name|{a}}',
        fontSize: 16,
        rich: { name: {} }
    };

    const finalSeries = result.series.map(s =>
        s.type === 'bar' ? { ...s, label: labelOption } : s
    );

    const option = {
        tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
        legend: { data: finalSeries.map(s => s.name) },
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
                    
                    // Excel-style table when Data View is opened
                    optionToContent: function (opt) {
                        const data = getCurrentChartData();
                        if (!data) return '<div style="padding:20px;color:#666;">No data available</div>';

                        let tableHTML = `
                            <style>
                                #excel-data-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-family: 'Segoe UI', Arial, sans-serif;
                                    font-size: 14px;
                                    margin: 10px 0;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                }
                                #excel-data-table th, #excel-data-table td {
                                    border: 1px solid #999;
                                    padding: 10px 12px;
                                    text-align: center;
                                    white-space: nowrap;
                                }
                                #excel-data-table th {
                                    background: linear-gradient(#f8f8f8, #e8e8e8);
                                    font-weight: bold;
                                    color: #333;
                                    position: sticky;
                                    top: 0;
                                    z-index: 1;
                                }
                                #excel-data-table tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                #excel-data-table td:first-child {
                                    font-weight: bold;
                                    background-color: #f0f0f0;
                                    text-align: left;
                                }
                            </style>
                            <table id="excel-data-table">
                                <thead>
                                    <tr>
                                        <th>Grade</th>`;

                        data.series.forEach(s => {
                            tableHTML += `<th>${s.name}</th>`;
                        });

                        tableHTML += `</tr></thead><tbody>`;

                        data.grades.forEach((grade, idx) => {
                            tableHTML += `<tr><td>${grade}</td>`;
                            data.series.forEach(s => {
                                const val = s.data[idx] !== null && s.data[idx] !== undefined 
                                    ? s.data[idx] 
                                    : '';
                                tableHTML += `<td>${val}</td>`;
                            });
                            tableHTML += `</tr>`;
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

                myFullScreen: {
                    show: true,
                    title: 'Fullscreen',
                    icon: 'path://M128 128h256v256H128z',
                    onclick: function () {
                        if (!document.fullscreenElement) {
                            chartDom.dataset.originalBg = chartDom.style.backgroundColor || '';
                            chartDom.style.backgroundColor = '#ffffff';
                            chartDom.requestFullscreen()
                                .then(() => myChart.resize())
                                .catch(err => {
                                    console.error('Fullscreen failed:', err);
                                    chartDom.style.backgroundColor = chartDom.dataset.originalBg;
                                });
                        } else {
                            document.exitFullscreen().then(() => {
                                chartDom.style.backgroundColor = chartDom.dataset.originalBg || '';
                                myChart.resize();
                            });
                        }
                    }
                }
            }
        },
        grid: { left: '5%', right: '5%', containLabel: true },
        xAxis: [{
            type: 'category',
            axisTick: { show: false },
            data: result.grade_level,
            axisLabel: {
                interval: 0,
                rotate: 60,
                fontSize: 12,
                margin: 14,
                color: '#555',
                align: 'right',
                verticalAlign: 'middle',
                overflow: 'truncate',
                width: 90,
                ellipsis: '...'
            }
        }],
        yAxis: [{ type: 'value' }],
        series: finalSeries
    };

    return { option, finalSeries };
}

async function reloadAvailabilityChart() {
    if (!_availabilityChart) return;

    _availabilityChart.showLoading({ text: 'Loading…', maskColor: 'rgba(255,255,255,0.7)' });

    try {
        const result = await fetchAvailabilityData();
        const chartDom = document.getElementById('chart');
        const { option, finalSeries } = buildChartOption(result, chartDom, _availabilityChart);

        _availabilityFullData = { ...result, series: finalSeries };

        setTimeout(() => {
            _availabilityChart.setOption(option, true);
            _availabilityChart.hideLoading();

            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) filterAndRenderChart(ksSelect.value);
        }, 0);

    } catch (err) {
        console.error('Failed to reload LR Availability chart:', err);
        _availabilityChart.hideLoading();
    }
}

async function initAvailabilityChart() {
    const chartDom = document.getElementById('chart');
    if (!chartDom) {
        console.warn('Chart container #chart not found');
        return;
    }

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);
        _availabilityChart = myChart;

        const result = await fetchAvailabilityData();
        const { option, finalSeries } = buildChartOption(result, chartDom, myChart);

        _availabilityFullData = { ...result, series: finalSeries };

        myChart.setOption(option);

        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderChart(e.target.value);
            });
        }

        const printTypeSelect = document.getElementById('printTypeFilter');
        if (printTypeSelect) {
            printTypeSelect.addEventListener('change', () => {
                reloadAvailabilityChart();
            });
        }

        if (window.registerChart) window.registerChart('chart', myChart);

        const resizeObserver = new ResizeObserver(() => myChart.resize());
        resizeObserver.observe(chartDom);

        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

    } catch (err) {
        console.error('Failed to initialize LR Availability chart:', err);
        if (chartDom) {
            chartDom.innerHTML = '<div style="text-align:center;padding:60px;color:#888;">Failed to load availability chart</div>';
        }
    }
}

function setupLazyAvailabilityChart() {
    const chartContainer = document.getElementById('chart');
    if (!chartContainer) {
        console.warn('Chart container #chart not found - lazy loading skipped');
        return;
    }

    if (!('IntersectionObserver' in window)) {
        initAvailabilityChart();
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            if (entries[0].isIntersecting) {
                initAvailabilityChart();
                obs.disconnect();
            }
        },
        { root: null, rootMargin: '0px', threshold: 0.1 }
    );

    observer.observe(chartContainer);
}

setupLazyAvailabilityChart();