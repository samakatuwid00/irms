// dashboard-controller.js
document.addEventListener('DOMContentLoaded', () => {
    const filter = document.getElementById('globalFilter');

    const containers = {
        'lr-availability': document.getElementById('lr-availability'),
        'lr-ratio': document.getElementById('lr-ratio'),
        'lr-per': document.getElementById('lr-per'),
        'lr-exdef': document.getElementById('lr-exdef'),
        'lr-heatmap': document.getElementById('lr-heatmap')
    };

    // 🔥 Vite indexes all chart modules at build time
    const modules = import.meta.glob('./*.js');

    const moduleMap = {
        'lr-availability': './availability.js',
        'lr-ratio': './ratio.js',
        'lr-per': './lr.js',
        'lr-exdef': './exdef.js',
        'lr-heatmap': './heatmap.js'
    };

    const loaded = new Set();
    const chartInstances = {};

    function disposeChart(key) {
        if (chartInstances[key]) {
            chartInstances[key].dispose?.();
            delete chartInstances[key];
        }
    }

    function forceResize(key) {
        if (chartInstances[key]) {
            setTimeout(() => {
                chartInstances[key].resize();
            }, 100);
        }
    }

    async function showChart(value) {
        Object.keys(containers).forEach(async (key) => {
            const el = containers[key];

            if (key === value) {
                el.classList.remove('hidden');

                if (!loaded.has(key)) {
                    loaded.add(key);

                    try {
                        const path = moduleMap[key];
                        if (modules[path]) {
                            await modules[path]();
                            console.log(`${key} loaded`);
                        } else {
                            console.error(`Module not found: ${path}`);
                        }
                    } catch (err) {
                        console.error(`Failed to load ${key}:`, err);
                    }
                }

                forceResize(key);

            } else {
                el.classList.add('hidden');
            }
        });
    }

    window.registerChart = (key, chart) => {
        chartInstances[key] = chart;
        forceResize(key);
    };

    showChart(filter.value);

    filter.addEventListener('change', (e) => {
        showChart(e.target.value);
    });

    window.addEventListener('resize', () => {
        Object.values(chartInstances).forEach(chart => chart?.resize?.());
    });
});
