// ratio.js

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _ratioFullData   = null;
let _ratioChart      = null;

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
            {
                name: 'Total LR',
                data: filteredDirect
            },
            {
                name: 'Population',
                data: filteredPop
            },
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

async function reloadRatioChart() {
    if (!_ratioChart) return;

    // showLoading before async work — never mid-render
    _ratioChart.showLoading({ text: 'Loading\u2026', maskColor: 'rgba(255,255,255,0.7)' });

    try {
        const chartData = await fetchRatioData();
        if (chartData.message) console.warn(chartData.message);

        const chartDom = document.getElementById('main');
        const { option, grades, population, directData, mailData } = buildRatioOption(chartData, chartDom, _ratioChart);

        _ratioFullData = { grades, population, directData, mailData };

        // Defer setOption to next task — avoids "setOption during main process"
        setTimeout(() => {
            _ratioChart.setOption(option, /* notMerge */ true);
            _ratioChart.hideLoading();

            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) filterAndRenderRatioChart(ksSelect.value);
        }, 0);

    } catch (err) {
        console.error('Failed to reload LR Ratio chart:', err);
        _ratioChart.hideLoading();
    }
}

async function initRatioChart() {
    const chartDom = document.getElementById('main');
    if (!chartDom) {
        console.warn('Chart container #main not found');
        return;
    }

    try {
        const chartData = await fetchRatioData();
        if (chartData.message) console.warn(chartData.message);

        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);
        _ratioChart = myChart;

        const { option, grades, population, directData, mailData } = buildRatioOption(chartData, chartDom, myChart);

        // Cache full dataset for key-stage slicing
        _ratioFullData = { grades, population, directData, mailData };

        myChart.setOption(option);
        window.ratioChart = myChart;

        // Apply default key stage filter on first load
        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderRatioChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderRatioChart(e.target.value);
            });
        }

        // Re-fetch when print type filter changes
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

    } catch (err) {
        console.error('Failed to initialize LR Ratio chart:', err);
        if (chartDom) {
            chartDom.innerHTML = '<div style="text-align:center; padding:50px;">Failed to load chart data</div>';
        }
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