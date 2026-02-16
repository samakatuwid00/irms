// lr.js
async function initLRChart() {
    const chartDom = document.getElementById('lr');
    if (!chartDom) {
        console.warn('Chart container #lr not found');
        return;
    }

    try {
        // Lazy load echarts only when we need to initialize the chart
        const echarts = await import('echarts');

        const myChart = echarts.init(chartDom);

        // ── Your data and calculation logic ──
        const rawData = [
            [100, 302, 301, 334, 390, 330, 320], // Series 1
            [320, 132, 101, 134, 90, 230, 210]   // Series 2
        ];

        const totalData = [];
        for (let i = 0; i < rawData[0].length; ++i) {
            let sum = 0;
            for (let j = 0; j < rawData.length; ++j) {
                sum += rawData[j][i];
            }
            totalData.push(sum);
        }

        const series = ['Learning Resources', 'Population'].map((name, sid) => {
            return {
                name,
                type: 'bar',
                stack: 'total',
                barWidth: '60%',
                label: {
                    show: false // no numbers inside bars
                },
                emphasis: {
                    focus: 'series'
                },
                data: rawData[sid].map((d, did) =>
                    totalData[did] <= 0 ? 0 : d / totalData[did]
                )
            };
        });

        const option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                },
                formatter: (params) => {
                    let tooltipText = '';
                    params.forEach(p => {
                        tooltipText += `${p.seriesName}: ${Math.round(p.value * 100)}%<br/>`;
                    });
                    return tooltipText;
                }
            },
            toolbox: {
                show: true,
                orient: 'vertical',
                right: 10,
                top: 'center',
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
                selectedMode: true
            },
            yAxis: {
                type: 'value'
            },
            xAxis: {
                type: 'category',
                data: ['School 1', 'School 2', 'School 3', 'School 4', 'School 5', 'School 6', 'School 7']
            },
            series
        };

        myChart.setOption(option);

        // Register the chart instance so controller can resize it after show
        if (window.registerChart) {
            window.registerChart('lr', myChart);   // use 'lr' as key – adjust if your card id is different
        }

        // Resize handling with ResizeObserver
        const resizeObserver = new ResizeObserver(() => {
            myChart.resize();
        });
        resizeObserver.observe(chartDom);

        // Cleanup
        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

    } catch (err) {
        console.error('Failed to initialize LR chart:', err);
    }
}

// Auto-run when this module is imported
initLRChart();