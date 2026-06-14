// exdef.js

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _exdefFullData = null;
let _exdefChart    = null;

// Get currently visible (filtered by Key Stage) data
function getCurrentExdefData() {
    if (!_exdefFullData || !_exdefChart) return null;

    const ksSelect = document.getElementById('schoolYearFilter');
    const keyStage = ksSelect ? ksSelect.value : null;
    
    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? null;

    const filtered = allowedGrades
        ? _exdefFullData.filter(item => allowedGrades.includes(item.grade))
        : _exdefFullData;

    // Return sorted copy (same as chart)
    return [...filtered].sort((a, b) => b.exdef - a.exdef);
}

// Export current filtered data to real Excel (.xlsx)
function exportToExcel() {
    const data = getCurrentExdefData();
    if (!data || data.length === 0) {
        alert('No chart data available');
        return;
    }

    if (typeof XLSX === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
        script.onload = () => performExdefExcelExport(data);
        script.onerror = () => alert('Failed to load Excel library');
        document.head.appendChild(script);
        return;
    }

    performExdefExcelExport(data);
}

function performExdefExcelExport(data) {
    const wb = XLSX.utils.book_new();
    
    const rows = [];
    const header = ['Subject', 'Grade', 'ExDef (Difference)'];
    rows.push(header);

    data.forEach(item => {
        rows.push([
            item.subject,
            item.grade,
            item.exdef
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [
        { wch: 25 },  // Subject
        { wch: 15 },  // Grade
        { wch: 20 }   // ExDef
    ];

    XLSX.utils.book_append_sheet(wb, ws, "ExDef");

    const ksSelect = document.getElementById('schoolYearFilter');
    const suffix = ksSelect && ksSelect.value ? `_${ksSelect.value}` : '';
    XLSX.writeFile(wb, `LR_ExDef${suffix}_${new Date().toISOString().slice(0,10)}.xlsx`);
}

// Filter and re-render chart based on Key Stage
function filterAndRenderExdefChart(keyStage) {
    if (!_exdefFullData || !_exdefChart) return;

    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? null;

    const filtered = allowedGrades
        ? _exdefFullData.filter(item => allowedGrades.includes(item.grade))
        : _exdefFullData;

    const totalItems    = filtered.length;
    const visibleRatio  = totalItems > 20 ? 0.20 : 0.35;
    const startPercent  = Math.max(0, 100 - (visibleRatio * 100));

    _exdefChart.setOption({
        xAxis: {
            data: filtered.map(item => `${item.subject} - ${item.grade}`)
        },
        dataZoom: [
            { type: 'inside', start: startPercent, end: 100 },
            { type: 'slider', start: startPercent, end: 100 }
        ],
        series: [{
            name: 'ExDef',
            data: filtered.map(item => ({
                value: item.exdef,
                itemStyle: { color: item.exdef >= 0 ? '#4CAF50' : '#F44336' }
            }))
        }]
    });
}

async function fetchExdefData() {
    const printTypeSelect = document.getElementById('printTypeFilter');
    const printTypeId = printTypeSelect ? printTypeSelect.value : '';

    const url = new URL('/chart/exdef', window.location.origin);
    if (printTypeId) url.searchParams.set('print_type_id', printTypeId);

    const response = await fetch(url.toString(), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return await response.json();
}

function buildExdefOption(result, chartDom, myChart) {
    const fullData = (result.table_data || [])
        .map(item => ({
            subject: item.subject,
            grade:   item.grade,
            exdef:   item.difference ?? 0
        }))
        .sort((a, b) => b.exdef - a.exdef);

    const totalItems   = fullData.length;
    const visibleRatio = totalItems > 20 ? 0.20 : 0.35;
    const startPercent = Math.max(0, 100 - (visibleRatio * 100));

    const option = {
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            confine: true
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
                        const data = getCurrentExdefData();
                        if (!data || data.length === 0) {
                            return '<div style="padding:20px;color:#666;">No data available</div>';
                        }

                        let tableHTML = `
                            <style>
                                #exdef-excel-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-family: 'Segoe UI', Arial, sans-serif;
                                    font-size: 14px;
                                    margin: 10px 0;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                }
                                #exdef-excel-table th, #exdef-excel-table td {
                                    border: 1px solid #999;
                                    padding: 10px 12px;
                                    text-align: center;
                                    white-space: nowrap;
                                }
                                #exdef-excel-table th {
                                    background: linear-gradient(#f8f8f8, #e8e8e8);
                                    font-weight: bold;
                                    color: #333;
                                    position: sticky;
                                    top: 0;
                                    z-index: 1;
                                }
                                #exdef-excel-table tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                #exdef-excel-table td:first-child {
                                    text-align: left;
                                    font-weight: bold;
                                }
                            </style>
                            <table id="exdef-excel-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Grade</th>
                                        <th>ExDef (Difference)</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                        data.forEach(item => {
                            const color = item.exdef >= 0 ? '#4CAF50' : '#F44336';
                            tableHTML += `
                                <tr>
                                    <td>${item.subject}</td>
                                    <td>${item.grade}</td>
                                    <td style="color:${color}; font-weight:600;">
                                        ${item.exdef}
                                    </td>
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
        grid: {
            left: '4%',
            right: '5%',
            bottom: '18%',
            top: '12%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            data: fullData.map(item => `${item.subject} - ${item.grade}`),
            axisTick: { show: false },
            axisLabel: {
                interval: 0,
                rotate: 55,
                fontSize: 11,
                margin: 12,
                color: '#555',
                align: 'right',
                verticalAlign: 'middle',
                overflow: 'truncate',
                width: 120
            }
        },
        yAxis: {
            type: 'value',
            name: 'Difference (LR – Population)',
            nameLocation: 'middle',
            nameGap: 50,
            nameTextStyle: { color: '#666', fontWeight: 'bold' }
        },
        dataZoom: [
            { type: 'inside', start: startPercent, end: 100 },
            {
                type: 'slider',
                start: startPercent,
                end: 100,
                height: 26,
                bottom: 'auto',
                fillerColor: 'rgba(76, 175, 80, 0.18)',
                borderColor: 'transparent',
                handleStyle: { color: '#4CAF50' },
                textStyle: { color: '#444', fontSize: 11 }
            }
        ],
        series: [{
            name: 'ExDef',
            type: 'bar',
            data: fullData.map(item => ({
                value: item.exdef,
                itemStyle: { color: item.exdef >= 0 ? '#4CAF50' : '#F44336' }
            })),
            barWidth: '58%',
            itemStyle: { borderRadius: [4, 4, 0, 0] }
        }]
    };

    return { option, fullData };
}

async function reloadExdefChart() {
    if (!_exdefChart) return;

    _exdefChart.showLoading({ text: 'Loading…', maskColor: 'rgba(255,255,255,0.7)' });

    try {
        const result = await fetchExdefData();

        if (result.error) {
            console.error('Backend error:', result.error);
            _exdefChart.hideLoading();
            return;
        }

        const chartDom = document.getElementById('exdef');
        const { option, fullData } = buildExdefOption(result, chartDom, _exdefChart);

        _exdefFullData = fullData;

        setTimeout(() => {
            _exdefChart.setOption(option, true);
            _exdefChart.hideLoading();

            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) filterAndRenderExdefChart(ksSelect.value);
        }, 0);

    } catch (err) {
        console.error('Failed to reload ExDef chart:', err);
        _exdefChart.hideLoading();
    }
}

async function initExdefChart() {
    const chartDom = document.getElementById('exdef');
    if (!chartDom) {
        console.warn('Chart container #exdef not found');
        return;
    }

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom, null, { renderer: 'canvas' });
        _exdefChart = myChart;

        const result = await fetchExdefData();

        if (result.error) {
            console.error('Backend error:', result.error);
            myChart.setOption({
                graphic: [{
                    type: 'text',
                    left: 'center',
                    top: 'middle',
                    style: { text: 'Failed to load data', fontSize: 16, fill: '#e74c3c' }
                }]
            });
            return;
        }

        const { option, fullData } = buildExdefOption(result, chartDom, myChart);

        _exdefFullData = fullData;

        myChart.setOption(option, true);

        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderExdefChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderExdefChart(e.target.value);
            });
        }

        const printTypeSelect = document.getElementById('printTypeFilter');
        if (printTypeSelect) {
            printTypeSelect.addEventListener('change', () => {
                reloadExdefChart();
            });
        }

        if (window.registerChart) window.registerChart('exdef', myChart);

        const resizeObserver = new ResizeObserver(() => myChart.resize());
        resizeObserver.observe(chartDom);

        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

    } catch (err) {
        console.error('Failed to initialize ExDef chart:', err);
        chartDom.innerHTML = `
            <div style="display:flex; align-items:center; justify-content:center;
                        height:100%; color:#e74c3c; font-size:1.1rem;">
                Failed to load chart
            </div>
        `;
    }
}

initExdefChart();