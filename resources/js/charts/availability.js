// availability.js
async function initAvailabilityChart() {
    const chartDom = document.getElementById('chart');
    if (!chartDom) {
        console.warn('Chart container #chart not found');
        return;
    }

    try {
        const echarts = await import('echarts');
        const myChart = echarts.init(chartDom);

        // Fetch real data from Laravel
        const response = await fetch('/chart/lr-availability', {
            headers: {
                'Accept': 'application/json',
                // Uncomment if you need CSRF token
                // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Failed to load chart data: ${response.status}`);
        }

        const result = await response.json();
        const { grade_level, series } = result;

        // Prepare label style (keeping your original)
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

        // Apply label only to bar series
        const finalSeries = series.map(s => {
            if (s.type === 'bar') {
                return { ...s, label: labelOption };
            }
            return s;
        });

        const option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' }
            },
            legend: {
                data: finalSeries.map(s => s.name)
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
                left: '5%',
                right: '5%',
                containLabel: true       // important for rotated labels
            },
            xAxis: [{
                type: 'category',
                axisTick: { show: false },
                data: grade_level,
                axisLabel: {
                    interval: 0,              // show ALL grade levels
                    rotate: 60,               // tilted for readability
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

        myChart.setOption(option);

        // Optional: global chart registry
        if (window.registerChart) {
            window.registerChart('chart', myChart);
        }

        // Resize handling
        const resizeObserver = new ResizeObserver(() => myChart.resize());
        resizeObserver.observe(chartDom);

        // Cleanup
        window.addEventListener('beforeunload', () => {
            resizeObserver.disconnect();
            myChart.dispose?.();
        });

    } catch (err) {
        console.error('Failed to initialize LR Availability chart:', err);
        if (chartDom) {
            chartDom.innerHTML = '<div style="text-align:center; padding:60px; color:#888;">Failed to load availability chart</div>';
        }
    }
}

// ────────────────────────────────────────────────
// Lazy loading with IntersectionObserver
// ────────────────────────────────────────────────
function setupLazyAvailabilityChart() {
    const chartContainer = document.getElementById('chart');
    if (!chartContainer) {
        console.warn('Chart container #chart not found — lazy loading skipped');
        return;
    }

    if (!('IntersectionObserver' in window)) {
        console.warn('IntersectionObserver not supported — loading chart immediately');
        initAvailabilityChart();
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            if (entries[0].isIntersecting) {
                console.log('Chart container is visible → initializing LR Availability chart');
                initAvailabilityChart();
                obs.disconnect(); // only load once
            }
        },
        {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        }
    );

    observer.observe(chartContainer);
}

// Kick off lazy loading
setupLazyAvailabilityChart();