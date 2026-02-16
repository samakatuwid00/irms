// exdef.js
async function initExdefChart() {
    const chartDom = document.getElementById('exdef');
    if (!chartDom) {
        console.warn('Chart container #exdef not found');
        return;
    }

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom, null, { renderer: 'canvas' });

        // Fetch real data
        const response = await fetch('/chart/exdef');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.error) {
            console.error('Backend error:', result.error);
            myChart.setOption({
                title: { text: 'Error loading data', left: 'center', top: 'middle' },
                graphic: [{
                    type: 'text',
                    left: 'center',
                    top: 'middle',
                    style: { text: 'Failed to load data', fontSize: 16, fill: '#e74c3c' }
                }]
            });
            return;
        }

        // Format data
        const tableData = result.table_data || [];
        const exdefDataFormatted = tableData
            .map(item => ({
                subject: item.subject,
                grade: item.grade,
                exdef: item.difference ?? 0
            }))
            .sort((a, b) => b.exdef - a.exdef); // descending

        const totalItems = exdefDataFormatted.length;
        const visibleRatio = totalItems > 20 ? 0.20 : 0.35; // show more when few items
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
            grid: {
                left: '4%',
                right: '5%',
                bottom: '18%',          // balanced for tilted labels + dataZoom
                top: '12%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                data: exdefDataFormatted.map(item => `${item.subject} - ${item.grade}`),
                axisTick: { show: false },
                axisLabel: {
                    interval: 0,
                    rotate: 55,               // slightly less aggressive than 60°
                    fontSize: 11,
                    margin: 12,
                    color: '#555',
                    align: 'right',
                    verticalAlign: 'middle',
                    overflow: 'truncate',     // safety net
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
                {
                    type: 'inside',
                    start: startPercent,
                    end: 100
                },
                {
                    type: 'slider',
                    start: startPercent,
                    end: 100,
                    // IMPORTANT: no fixed bottom value → auto positions below grid
                    height: 26,
                    bottom: 'auto',           // explicit auto (optional)
                    fillerColor: 'rgba(76, 175, 80, 0.18)',
                    borderColor: 'transparent',
                    handleStyle: { color: '#4CAF50' },
                    textStyle: { color: '#444', fontSize: 11 }
                }
            ],
            series: [
                {
                    name: 'ExDef',
                    type: 'bar',
                    data: exdefDataFormatted.map(item => ({
                        value: item.exdef,
                        itemStyle: {
                            color: item.exdef >= 0 ? '#4CAF50' : '#F44336'
                        }
                    })),
                    barWidth: '58%',
                    itemStyle: {
                        borderRadius: [4, 4, 0, 0]
                    }
                }
            ]
        };

        myChart.setOption(option, true);

        // Register for global resize handling if you have it
        if (window.registerChart) {
            window.registerChart('exdef', myChart);
        }

        // Responsive resize
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
        console.error('Failed to initialize ExDef chart:', err);
        chartDom.innerHTML = `
            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#e74c3c; font-size:1.1rem;">
                Failed to load chart
            </div>
        `;
    }
}

// Run immediately (or move to DOMContentLoaded if preferred)
initExdefChart();