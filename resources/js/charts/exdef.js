// exdef.js

const KEY_STAGE_RANGES = {
    'K1': ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3'],
    'K2': ['Grade 4', 'Grade 5', 'Grade 6'],
    'JH': ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    'SH': ['Grade 11', 'Grade 12'],
};

let _exdefFullData = null;
let _exdefChart    = null;

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

        const response = await fetch('/chart/exdef');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const result = await response.json();

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

        // Cache full sorted dataset
        _exdefFullData = (result.table_data || [])
            .map(item => ({
                subject: item.subject,
                grade:   item.grade,
                exdef:   item.difference ?? 0
            }))
            .sort((a, b) => b.exdef - a.exdef);

        const totalItems   = _exdefFullData.length;
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
            grid: {
                left: '4%',
                right: '5%',
                bottom: '18%',
                top: '12%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                data: _exdefFullData.map(item => `${item.subject} - ${item.grade}`),
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
                data: _exdefFullData.map(item => ({
                    value: item.exdef,
                    itemStyle: { color: item.exdef >= 0 ? '#4CAF50' : '#F44336' }
                })),
                barWidth: '58%',
                itemStyle: { borderRadius: [4, 4, 0, 0] }
            }]
        };

        myChart.setOption(option, true);

        // ── Apply default key stage filter on first load ──
        const ksSelect = document.getElementById('schoolYearFilter');
        if (ksSelect) {
            filterAndRenderExdefChart(ksSelect.value);

            ksSelect.addEventListener('change', (e) => {
                filterAndRenderExdefChart(e.target.value);
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