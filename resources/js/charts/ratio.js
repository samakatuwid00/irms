// ratio.js

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _ratioFullData   = null;
let _ratioChart      = null;

// Get currently visible (filtered) data for Data View & Excel Export
function getCurrentRatioData() {
    if (!_ratioFullData || !_ratioChart) return null;

    const ksSelect = document.getElementById('schoolYearFilter');
    const keyStage = ksSelect ? ksSelect.value : null;
    
    const { grades, population, directData, mailData } = _ratioFullData;
    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? grades;

    const indices = grades
        .map((g, i) => ({ g, i }))
        .filter(({ g }) => allowedGrades.includes(g))
        .map(({ i }) => i);

    const filteredGrades = indices.map(i => grades[i]);
    const filteredDirect = indices.map(i => directData[i]);
    const filteredMail   = indices.map(i => mailData[i]);
    const filteredPop    = filteredGrades.map(g => population[g] || 0);

    return {
        grades: filteredGrades,
        directData: filteredDirect,
        mailData: filteredMail,
        population: filteredPop
    };
}

// Export current filtered data to real Excel (.xlsx)
function exportToExcel() {
    const data = getCurrentRatioData();
    if (!data) {
        alert('No chart data available');
        return;
    }

    if (typeof XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.onload = () => performRatioExcelExport(data);
        script.onerror = () => alert('Failed to load Excel library');
        document.head.appendChild(script);
        return;
    }

    performRatioExcelExport(data);
}

function performRatioExcelExport(data) {
    const wb = XLSX.utils.book_new();
    
    const rows = [];
    const header = ['Grade', 'Total LR', 'Population', 'Total Ratio'];
    rows.push(header);

    data.grades.forEach((grade, idx) => {
        const lrCount = (data.directData[idx] || 0) + (data.mailData[idx] || 0);
        const pop = data.population[idx] || 0;
        let ratioText = 'N/A';
        
        if (lrCount > 0 && pop > 0) {
            const ppl = pop / lrCount;
            ratioText = ppl >= 1 
                ? `${Math.round(ppl).toLocaleString()} : 1` 
                : `${Math.round(lrCount / pop).toLocaleString()} : 1`;
        }

        rows.push([
            grade,
            lrCount,
            pop,
            ratioText
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = header.map(() => ({ wch: 20 }));

    XLSX.utils.book_append_sheet(wb, ws, "LR Ratio");

    const ksSelect = document.getElementById('schoolYearFilter');
    const suffix = ksSelect && ksSelect.value ? `_${ksSelect.value}` : '';
    XLSX.writeFile(wb, `LR_Ratio${suffix}_${new Date().toISOString().slice(0,10)}.xlsx`);
}

// Filter and re-render chart based on Key Stage
function filterAndRenderRatioChart(keyStage) {
    if (!_ratioFullData || !_ratioChart) return;

    const { grades, population, directData, mailData } = _ratioFullData;
    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? grades;

    const indices = grades
        .map((g, i) => ({ g, i }))
        .filter(({ g }) => allowedGrades.includes(g))
        .map(({ i }) => i);

    const filteredGrades    = indices.map(i => grades[i]);
    const filteredDirect    = indices.map(i => directData[i]);
    const filteredMail      = indices.map(i => mailData[i]);
    const filteredPop       = filteredGrades.map(g => population[g] || 0);
    const filteredRatioDummy = new Array(filteredGrades.length).fill(0);

    _ratioChart.setOption({
        yAxis: { data: filteredGrades },
        series: [
            { name: 'Total LR', data: filteredDirect },
            { name: 'Population', data: filteredPop },
            {
                name: 'Total Ratio',
                data: filteredRatioDummy,
                label: {
                    show: true,
                    position: 'right',
                    distance: 8,
                    formatter: function (params) {
                        const idx    = params.dataIndex;
                        const grade  = filteredGrades[idx];
                        const lrCount = (filteredDirect[idx] || 0) + (filteredMail[idx] || 0);
                        const pop    = population[grade] || 0;

                        if (lrCount <= 0 || pop <= 0) return 'N/A';

                        const peoplePerLR = pop / lrCount;
                        if (peoplePerLR >= 1) {
                            return `${Math.round(peoplePerLR).toLocaleString()} : 1`;
                        } else {
                            return `${Math.round(lrCount / pop).toLocaleString()} : 1`;
                        }
                    },
                    color: '#333',
                    fontSize: 13,
                    fontWeight: 'bold'
                }
            }
        ]
    });
}

async function fetchRatioData() {
    const printTypeSelect = document.getElementById('printTypeFilter');
    const printTypeId = printTypeSelect ? printTypeSelect.value : '';

    const url = new URL('/chart/lr-ratio', window.location.origin);
    if (printTypeId) url.searchParams.set('print_type_id', printTypeId);

    const response = await fetch(url.toString(), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return await response.json();
}

function buildRatioOption(chartData, chartDom, myChart) {
    const grades     = chartData.grades     || [];
    const population = chartData.population || {};
    const directData = chartData.directData || [];
    const mailData   = chartData.mailData   || [];

    const option = {
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: function (params) {
                let txt = params[0].name + '<br/>';
                params.forEach(p => {
                    if (p.seriesName === 'Total Ratio') return;
                    txt += `${p.marker} ${p.seriesName}: ${(p.value || 0).toLocaleString()}<br/>`;
                });
                return txt;
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
                    
                    // === Excel-style Table ===
                    optionToContent: function (opt) {
                        const data = getCurrentRatioData();
                        if (!data) return '<div style="padding:20px;color:#666;">No data available</div>';

                        let tableHTML = `
                            <style>
                                #ratio-excel-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-family: 'Segoe UI', Arial, sans-serif;
                                    font-size: 14px;
                                    margin: 10px 0;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                }
                                #ratio-excel-table th, #ratio-excel-table td {
                                    border: 1px solid #999;
                                    padding: 10px 12px;
                                    text-align: center;
                                    white-space: nowrap;
                                }
                                #ratio-excel-table th {
                                    background: linear-gradient(#f8f8f8, #e8e8e8);
                                    font-weight: bold;
                                    color: #333;
                                    position: sticky;
                                    top: 0;
                                    z-index: 1;
                                }
                                #ratio-excel-table tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                #ratio-excel-table td:first-child {
                                    font-weight: bold;
                                    background-color: #f0f0f0;
                                    text-align: left;
                                }
                            </style>
                            <table id="ratio-excel-table">
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th>Total LR</th>
                                        <th>Population</th>
                                        <th>Total Ratio</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                        data.grades.forEach((grade, idx) => {
                            const lrCount = (data.directData[idx] || 0) + (data.mailData[idx] || 0);
                            const pop = data.population[idx] || 0;
                            let ratioText = 'N/A';
                            
                            if (lrCount > 0 && pop > 0) {
                                const ppl = pop / lrCount;
                                ratioText = ppl >= 1 
                                    ? `${Math.round(ppl).toLocaleString()} : 1` 
                                    : `${Math.round(lrCount / pop).toLocaleString()} : 1`;
                            }

                            tableHTML += `
                                <tr>
                                    <td>${grade}</td>
                                    <td>${lrCount}</td>
                                    <td>${pop}</td>
                                    <td>${ratioText}</td>
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

        legend: { data: ['Total LR', 'Population'] },

        xAxis: { type: 'value' },

        yAxis: {
            type: 'category',
            data: grades
        },

        series: [
            {
                name: 'Total LR',
                type: 'bar',
                stack: 'total',
                label: {
                    show: true,
                    position: 'inside',
                    formatter: p => (p.value || 0).toLocaleString(),
                    color: '#fff'
                },
                data: directData,
                itemStyle: { color: '#5470c6' }
            },
            {
                name: 'Population',
                type: 'bar',
                stack: 'total',
                barMinWidth: 10,
                label: {
                    show: true,
                    position: 'inside',
                    formatter: p => (p.value || 0).toLocaleString(),
                    color: '#fff'
                },
                data: grades.map(g => population[g] || 0),
                itemStyle: { color: '#91cc75' }
            },
            {
                name: 'Total Ratio',
                type: 'bar',
                stack: 'total',
                silent: true,
                label: {
                    show: true,
                    position: 'right',
                    distance: 8,
                    formatter: function (params) {
                        const idx     = params.dataIndex;
                        const grade   = grades[idx];
                        const lrCount = (directData[idx] || 0) + (mailData[idx] || 0);
                        const pop     = population[grade] || 0;

                        if (lrCount <= 0 || pop <= 0) return 'N/A';
                        const ppl = pop / lrCount;
                        return ppl >= 1
                            ? `${Math.round(ppl).toLocaleString()} : 1`
                            : `${Math.round(lrCount / pop).toLocaleString()} : 1`;
                    },
                    color: '#333',
                    fontSize: 13,
                    fontWeight: 'bold'
                },
                itemStyle: { color: 'transparent' },
                data: new Array(grades.length).fill(0)
            }
        ]
    };

    return { option, grades, population, directData, mailData };
}

// The rest of the functions remain unchanged
async function reloadRatioChart() {
    if (!_ratioChart) return;

    const chartDom = document.getElementById('main');
    window.DashboardChartLoading?.show(chartDom);

    _ratioChart.showLoading({ text: 'Loading…', maskColor: 'rgba(255,255,255,0.7)' });

    try {
        const chartData = await fetchRatioData();
        if (chartData.message) console.warn(chartData.message);

        const { option, grades, population, directData, mailData } = buildRatioOption(chartData, chartDom, _ratioChart);

        _ratioFullData = { grades, population, directData, mailData };

        setTimeout(() => {
            _ratioChart.setOption(option, true);
            _ratioChart.hideLoading();

            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) filterAndRenderRatioChart(ksSelect.value);
            window.DashboardChartLoading?.hide(chartDom);
        }, 0);

    } catch (err) {
        console.error('Failed to reload LR Ratio chart:', err);
        _ratioChart.hideLoading();
        window.DashboardChartLoading?.hide(chartDom);
    }
}

async function initRatioChart() {
    const chartDom = document.getElementById('main');
    if (!chartDom) {
        console.warn('Chart container #main not found');
        return;
    }

    window.DashboardChartLoading?.show(chartDom);

    try {
        const chartData = await fetchRatioData();
        if (chartData.message) console.warn(chartData.message);

        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);
        _ratioChart = myChart;

        const { option, grades, population, directData, mailData } = buildRatioOption(chartData, chartDom, myChart);

        _ratioFullData = { grades, population, directData, mailData };

        myChart.setOption(option);
        window.ratioChart = myChart;

        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderRatioChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                window.DashboardChartLoading?.transition(
                    chartDom,
                    () => filterAndRenderRatioChart(e.target.value)
                );
            });
        }

        const printTypeSelect = document.getElementById('printTypeFilter');
        if (printTypeSelect) {
            printTypeSelect.addEventListener('change', () => {
                reloadRatioChart();
            });
        }

        const resizeObserver = new ResizeObserver(() => myChart.resize());
        resizeObserver.observe(chartDom);

        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

        window.DashboardChartLoading?.hide(chartDom);

    } catch (err) {
        console.error('Failed to initialize LR Ratio chart:', err);
        if (chartDom) {
            chartDom.innerHTML = '<div style="text-align:center; padding:50px;">Failed to load chart data</div>';
        }
        window.DashboardChartLoading?.hide(chartDom);
    }
}

function setupLazyChartLoading() {
    const chartContainer = document.getElementById('main');
    if (!chartContainer) {
        console.warn('Chart container #main not found');
        return;
    }

    if (!('IntersectionObserver' in window)) {
        initRatioChart();
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            if (entries[0].isIntersecting) {
                initRatioChart();
                obs.disconnect();
            }
        },
        { root: null, rootMargin: '0px', threshold: 0.1 }
    );

    observer.observe(chartContainer);
}

setupLazyChartLoading();
