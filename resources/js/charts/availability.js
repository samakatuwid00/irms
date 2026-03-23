// availability.js

// Key Stage grade index ranges (based on grade_level array order from backend)
const KEY_STAGE_RANGES = {
    'K1': { label: 'Kindergarten', grades: ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'] },
    'K2': { label: 'Key Stage 2',  grades: ['Grade 4', 'Grade 5', 'Grade 6'] },
    'JH': { label: 'Junior High',  grades: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'] },
    'SH': { label: 'Senior High',  grades: ['Grade 11', 'Grade 12'] },
};

// Store original full data globally so filter can re-slice without re-fetching
let _availabilityFullData = null;
let _availabilityChart = null;

function filterAndRenderChart(keyStage) {
    if (!_availabilityFullData || !_availabilityChart) return;

    const { grade_level, series } = _availabilityFullData;
    const allowedGrades = KEY_STAGE_RANGES[keyStage]?.grades ?? grade_level;

    // Find indices of grades that belong to the selected key stage
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
    }, /* notMerge: */ false);
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

        const response = await fetch('/chart/lr-availability', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error(`Failed to load chart data: ${response.status}`);

        const result = await response.json();
        _availabilityFullData = result; // ← cache full data

        const { grade_level, series } = result;

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

        const finalSeries = series.map(s =>
            s.type === 'bar' ? { ...s, label: labelOption } : s
        );

        // Store labelled series back so filterAndRenderChart uses them too
        _availabilityFullData = { ...result, series: finalSeries };

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
            grid: { left: '5%', right: '5%', containLabel: true },
            xAxis: [{
                type: 'category',
                axisTick: { show: false },
                data: grade_level,
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

        myChart.setOption(option);

        // ── Apply the currently selected key stage on first load ──
        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderChart(ksSelect.value); // K1 is selected by default

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderChart(e.target.value);
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
        console.warn('Chart container #chart not found — lazy loading skipped');
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