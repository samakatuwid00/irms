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

async function fetchAvailabilityData() {
    const printTypeSelect = document.getElementById('printTypeFilter');
    const printTypeId = printTypeSelect ? printTypeSelect.value : '';

    const url = new URL('/chart/lr-availability', window.location.origin);
    if (printTypeId) url.searchParams.set('print_type_id', printTypeId);

    const response = await fetch(url.toString(), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    });

    if (!response.ok) throw new Error(`Failed to load chart data: ${response.status}`);
    return await response.json();
}

/**
 * Build the complete ECharts option object from fresh data.
 * Both init and reload use this so setOption always receives a full
 * option (yAxis, grid, toolbox included), preventing the
 * "yAxis '0' not found" error that happens with partial notMerge updates.
 */
function buildChartOption(result, chartDom, myChart) {
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

    const finalSeries = result.series.map(s =>
        s.type === 'bar' ? { ...s, label: labelOption } : s
    );

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
            data: result.grade_level,
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

    return { option, finalSeries };
}

async function reloadAvailabilityChart() {
    if (!_availabilityChart) return;

    // showLoading must be called before the async work — never mid-render.
    _availabilityChart.showLoading({ text: 'Loading\u2026', maskColor: 'rgba(255,255,255,0.7)' });

    try {
        const result = await fetchAvailabilityData();
        const chartDom = document.getElementById('chart');
        const { option, finalSeries } = buildChartOption(result, chartDom, _availabilityChart);

        _availabilityFullData = { ...result, series: finalSeries };

        // Defer both setOption calls to the next task so they never run
        // inside an ECharts render cycle ("setOption during main process").
        setTimeout(() => {
            // Full replace (notMerge:true) ensures yAxis/grid are always present.
            _availabilityChart.setOption(option, /* notMerge */ true);
            _availabilityChart.hideLoading();

            // Re-apply the current key-stage slice on top of the fresh data.
            const ksSelect = document.getElementById('schoolYearFilter');
            if (ksSelect) filterAndRenderChart(ksSelect.value);
        }, 0);

    } catch (err) {
        console.error('Failed to reload LR Availability chart:', err);
        _availabilityChart.hideLoading();
    }
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

        const result = await fetchAvailabilityData();
        const { option, finalSeries } = buildChartOption(result, chartDom, myChart);

        // Cache full labelled data for key-stage slicing
        _availabilityFullData = { ...result, series: finalSeries };

        myChart.setOption(option);

        // Apply the currently selected key stage on first load
        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderChart(e.target.value);
            });
        }

        // Re-fetch when print type filter changes
        const printTypeSelect = document.getElementById('printTypeFilter');
        if (printTypeSelect) {
            printTypeSelect.addEventListener('change', () => {
                reloadAvailabilityChart();
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
        console.warn('Chart container #chart not found - lazy loading skipped');
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