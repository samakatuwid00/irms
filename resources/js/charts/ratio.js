// Main chart initialization function
async function initRatioChart() {
    const chartDom = document.getElementById('main');
    if (!chartDom) {
        console.warn('Chart container #main not found');
        return;
    }

    try {
        // Fetch live data (scoped to user level + station)
        const response = await fetch('/chart/lr-ratio');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const chartData = await response.json();

        if (chartData.message) {
            console.warn(chartData.message);
        }

        const grades     = chartData.grades     || [];
        const population = chartData.population || {};
        const directData = chartData.directData || [];
        const mailData   = chartData.mailData   || [];

        // Debug: check what we actually received

        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);

        const option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: function (params) {
                    let txt = params[0].name + '<br/>';
                    let totalLR = 0;

                    params.forEach(p => {
                        if (p.seriesName === 'Total Ratio') return;
                        totalLR += p.value || 0;
                        // Add comma separator
                        const valueWithCommas = (p.value || 0).toLocaleString();
                        txt += `${p.marker} ${p.seriesName}: ${valueWithCommas}<br/>`;
                    });

                    const pop = population[params[0].name] || 0;

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
                                document.exitFullscreen()
                                    .then(() => {
                                        chartDom.style.backgroundColor = chartDom.dataset.originalBg || '';
                                        myChart.resize();
                                    });
                            }
                        }
                    }
                }
            },

            legend: {
                data: ['Total LR', 'Population']
            },

            xAxis: {
                type: 'value'
            },

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
                        formatter: function (params) {
                            return (params.value || 0).toLocaleString(); // comma separator
                        },
                        color: '#fff'
                    },
                    data: directData,
                    itemStyle: { color: '#5470c6' }
                },
                {
                    name: 'Population',
                    type: 'bar',
                    stack: 'total',
                    barMinWidth: 10,   // 👈 ensures small values are visible
                    label: {
                        show: true,
                        position: 'inside',
                        formatter: function (params) {
                            return (params.value || 0).toLocaleString();
                        },
                        color: '#fff'
                    },
                    data: grades.map(grade => population[grade] || 0),
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
                            const idx = params.dataIndex;
                            const category = grades[idx];
                            const lrCount = (directData[idx] || 0) + (mailData[idx] || 0);
                            const pop = population[category] || 0;

                            if (lrCount <= 0) return 'N/A';
                            if (pop <= 0) return 'N/A';

                            const peoplePerLR = pop / lrCount;
                            if (peoplePerLR >= 1) {
                                return `${Math.round(peoplePerLR).toLocaleString()} : 1`;
                            } else {
                                const lrPerPerson = Math.round(lrCount / pop);
                                return `${lrPerPerson.toLocaleString()} : 1`;
                            }
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

        myChart.setOption(option);
        window.ratioChart = myChart;

        // Resize handling
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

// ────────────────────────────────────────────────
// Lazy loading setup – chart loads only when visible
// ────────────────────────────────────────────────
function setupLazyChartLoading() {
    const chartContainer = document.getElementById('main');
    if (!chartContainer) {
        console.warn('Chart container #main not found');
        return;
    }

    // Fallback for browsers without IntersectionObserver
    if (!('IntersectionObserver' in window)) {
        console.warn('IntersectionObserver not supported, loading chart immediately');
        initRatioChart();
        return;
    }

    const observer = new IntersectionObserver(
        (entries, observer) => {
            if (entries[0].isIntersecting) {
                initRatioChart();
                observer.disconnect(); // load only once
            }
        },
        {
            root: null,           // use viewport
            rootMargin: '0px',    // can be '100px' or '20%' to load earlier
            threshold: 0.1        // trigger when 10% of the element is visible
        }
    );

    observer.observe(chartContainer);
}

// Start lazy loading
setupLazyChartLoading();