// heatmap.js
async function initHeatmapChart() {
    const chartDom = document.getElementById('heatmap');
    if (!chartDom) {
        console.warn('Chart container #heatmap not found');
        return;
    }

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);

        // ── Fetch real data from your Laravel endpoint ──────────────────────────────
        // Adjust the URL if your route is different (e.g. /api/lr-heatmap or similar)
        const response = await fetch('/chart/heatmap', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                // If you use Laravel Sanctum or similar, you may need:
                // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const result = await response.json();

        // Check if we got valid data
        if (!result.x_axis || !result.y_axis || !Array.isArray(result.series_data)) {
            console.warn('Invalid heatmap data format from server', result);
            // You can show a message in the chart or fallback here
            myChart.setOption({
                title: { text: 'No data available', left: 'center', top: 'center' },
            });
            return;
        }

        // Use real data from backend
        const subjects     = result.x_axis;       // e.g. ['Mathematics', 'Science', ...]
        const gradeLevels  = result.y_axis;       // e.g. ['Kindergarten', 'Grade 1', ...]
        const rawData      = result.series_data;  // [ [subjIdx, gradeIdx, qty], ... ]
        const maxValue     = result.max_value || 100;

        // Optional: log for debugging
        console.log('Heatmap data loaded:', { subjectsCount: subjects.length, gradesCount: gradeLevels.length, entries: rawData.length });

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

            xAxis: {
                type: 'category',
                data: subjects,
                splitArea: { show: true },
                axisLabel: {
                    rotate: 55,
                    fontSize: 11,
                    width: 95,
                    interval: 0
                }
            },

            yAxis: {
                type: 'category',
                data: gradeLevels,
                splitArea: { show: true },
                axisLabel: {
                    fontSize: 12,
                    margin: 12
                }
            },
            grid: {
    top: '12%',           // give title/subtitle some space
    bottom: '18%',        // important – give visualMap + x labels space
    left: '8%',
    right: '5%',
    containLabel: true    // ← crucial when labels are rotated!
},

visualMap: {
    type: 'continuous',
    min: 0,
    max: maxValue,
    calculable: true,
    orient: 'horizontal',
    left: 'center',
    bottom: '8%',           // ← key change: percentage of canvas height
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

            series: [
                {
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
                }
            ],

            // Responsive adjustments
media: [
    {
        query: { maxWidth: 900 },
        option: {
            visualMap: {
                bottom: '10%',     // give a bit more breathing room
                itemHeight: 110,
                itemWidth: 18
            },
            grid: {
                bottom: '18%'      // ← make grid leave more space at bottom
            }
        }
    },
    {
        query: { maxWidth: 640 },   // tighter mobile
        option: {
            visualMap: {
                bottom: '12%',
                itemHeight: 90,
                itemWidth: 16,
                textGap: 5,
                // Optional: make it vertical on very small screens
                // orient: 'vertical',
                // right: 10,
                // bottom: 'center'
            },
            grid: {
                bottom: '24%',
                left: '12%',
                right: '5%'
            },
            xAxis: {
                axisLabel: {
                    rotate: 60,
                    fontSize: 10
                }
            }
        }
    }
]
        };

        myChart.setOption(option);

        // Register chart (if you're using some global chart manager)
        if (window.registerChart) {
            window.registerChart('heatmap', myChart);
        }

        // Handle resize
        const resizeObserver = new ResizeObserver(() => {
            myChart.resize();
        });
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