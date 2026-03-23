// heatmap.js

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _heatmapFullData = null;
let _heatmapChart    = null;

function filterAndRenderHeatmapChart(keyStage) {
    if (!_heatmapFullData || !_heatmapChart) return;

    const { subjects, gradeLevels, rawData, maxValue } = _heatmapFullData;
    const allowedGrades = KEY_STAGE_RANGES[keyStage] ?? gradeLevels;

    // Find which y-axis indices belong to the selected key stage
    const allowedGradeIndices = gradeLevels
        .map((g, i) => ({ g, i }))
        .filter(({ g }) => allowedGrades.includes(g))
        .map(({ i }) => i);

    const filteredGradeLevels = allowedGradeIndices.map(i => gradeLevels[i]);

    // Re-map series data: keep only rows whose gradeIdx is in the allowed set,
    // then remap the gradeIdx to the new filtered y-axis position
    const gradeIndexMap = new Map(
        allowedGradeIndices.map((origIdx, newIdx) => [origIdx, newIdx])
    );

    const filteredData = rawData
        .filter(([subjIdx, gradeIdx]) => gradeIndexMap.has(gradeIdx))
        .map(([subjIdx, gradeIdx, qty]) => [subjIdx, gradeIndexMap.get(gradeIdx), qty]);

    // Recalculate max for the filtered slice
    const filteredMax = filteredData.length
        ? Math.max(...filteredData.map(([,, qty]) => qty))
        : maxValue;

    _heatmapChart.setOption({
        yAxis:     { data: filteredGradeLevels },
        visualMap: { max: filteredMax },
        series:    [{ data: filteredData }]
    });
}

async function initHeatmapChart() {
    const chartDom = document.getElementById('heatmap');
    if (!chartDom) {
        console.warn('Chart container #heatmap not found');
        return;
    }

    let myChart;

    try {
        const echarts = await import('echarts');
        myChart = echarts.init(chartDom);
        _heatmapChart = myChart;

        const response = await fetch('/chart/heatmap', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        const result = await response.json();

        if (!result.x_axis || !result.y_axis || !Array.isArray(result.series_data)) {
            console.warn('Invalid heatmap data format from server', result);
            myChart.setOption({
                title: { text: 'No data available', left: 'center', top: 'center' },
            });
            return;
        }

        const subjects    = result.x_axis;
        const gradeLevels = result.y_axis;
        const rawData     = result.series_data;
        const maxValue    = result.max_value || 100;

        // Cache full dataset
        _heatmapFullData = { subjects, gradeLevels, rawData, maxValue };

        const option = {
            tooltip: {
                position: 'top',
                formatter: function (params) {
                    const subj  = subjects[params.data[0]];
                    const grade = gradeLevels[params.data[1]];
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
                    dataView: { show: true, readOnly: false },
                    magicType: { show: true, type: ['line', 'bar', 'stack'] },
                    restore: { show: true },
                    saveAsImage: { show: true },
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

        myChart.setOption(option);

        // ── Apply default key stage filter on first load ──
        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderHeatmapChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderHeatmapChart(e.target.value);
            });
        }

        if (window.registerChart) window.registerChart('heatmap', myChart);

        const resizeObserver = new ResizeObserver(() => myChart.resize());
        resizeObserver.observe(chartDom);

        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

    } catch (err) {
        console.error('Failed to initialize Heatmap chart:', err);
        myChart?.setOption({
            title: {
                text: 'Error loading chart data',
                subtext: err.message,
                left: 'center',
                top: 'center'
            }
        });
    }
}

initHeatmapChart();