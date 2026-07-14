const NEUTRAL_TEXT_COLORS = new Set(['#000', '#000000', '#333', '#444', '#555', '#666']);

export function chartTheme() {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        isDark,
        text: isDark ? '#e2e8f0' : '#334155',
        muted: isDark ? '#94a3b8' : '#64748b',
        border: isDark ? '#475569' : '#dbeafe',
        splitLine: isDark ? '#334155' : '#e5e7eb',
        surface: isDark ? '#1e293b' : '#ffffff',
        surfaceMuted: isDark ? '#0f172a' : '#f8fafc',
        tooltip: isDark ? '#0f172a' : '#ffffff',
        tooltipBorder: isDark ? '#475569' : '#e2e8f0',
        loadingMask: isDark ? 'rgba(15, 23, 42, 0.72)' : 'rgba(255, 255, 255, 0.72)',
    };
}

function normalizeAxis(axis, theme) {
    if (!axis) return axis;
    const axes = Array.isArray(axis) ? axis : [axis];

    axes.forEach((item) => {
        if (!item || typeof item !== 'object') return;
        item.axisLabel = { ...(item.axisLabel || {}), color: theme.muted };
        item.axisLine = { ...(item.axisLine || {}), lineStyle: { ...(item.axisLine?.lineStyle || {}), color: theme.border } };
        item.splitLine = { ...(item.splitLine || {}), lineStyle: { ...(item.splitLine?.lineStyle || {}), color: theme.splitLine } };
        item.nameTextStyle = { ...(item.nameTextStyle || {}), color: theme.text };
    });

    return Array.isArray(axis) ? axes : axes[0];
}

function cloneOption(value) {
    if (Array.isArray(value)) return value.map(cloneOption);
    if (!value || typeof value !== 'object') return value;

    const cloned = {};
    Object.entries(value).forEach(([key, child]) => {
        if (child === undefined) return;
        cloned[key] = cloneOption(child);
    });
    return cloned;
}

function normalizeTextNodes(value, theme, key = '') {
    if (!value || typeof value !== 'object') return;

    if (['textStyle', 'axisLabel', 'nameTextStyle', 'label'].includes(key)) {
        const color = String(value.color || value.fill || '').toLowerCase();
        if (!color || NEUTRAL_TEXT_COLORS.has(color)) {
            value.color = theme.text;
            if ('fill' in value) value.fill = theme.text;
        }
    }

    Object.entries(value).forEach(([childKey, childValue]) => {
        if (childKey === 'itemStyle' || childKey === 'lineStyle' || childKey === 'areaStyle') return;
        if (Array.isArray(childValue)) {
            childValue.forEach((entry) => normalizeTextNodes(entry, theme, childKey));
            return;
        }
        normalizeTextNodes(childValue, theme, childKey);
    });
}

export function applyChartTheme(option) {
    const theme = chartTheme();
    const themed = cloneOption(option);

    themed.backgroundColor = 'transparent';
    themed.textStyle = { ...(themed.textStyle || {}), color: theme.text };
    themed.tooltip = {
        ...(themed.tooltip || {}),
        backgroundColor: theme.tooltip,
        borderColor: theme.tooltipBorder,
        textStyle: { ...(themed.tooltip?.textStyle || {}), color: theme.text },
    };
    if (themed.legend) {
        themed.legend = {
            ...themed.legend,
            textStyle: { ...(themed.legend.textStyle || {}), color: theme.text },
        };
    }
    themed.toolbox = {
        ...(themed.toolbox || {}),
        iconStyle: { ...(themed.toolbox?.iconStyle || {}), borderColor: theme.muted },
        emphasis: {
            ...(themed.toolbox?.emphasis || {}),
            iconStyle: { ...(themed.toolbox?.emphasis?.iconStyle || {}), borderColor: theme.text },
        },
    };
    themed.xAxis = normalizeAxis(themed.xAxis, theme);
    themed.yAxis = normalizeAxis(themed.yAxis, theme);

    if (themed.dataZoom) {
        const zooms = Array.isArray(themed.dataZoom) ? themed.dataZoom : [themed.dataZoom];
        zooms.forEach((zoom) => {
            zoom.textStyle = { ...(zoom.textStyle || {}), color: theme.muted };
            zoom.borderColor = zoom.borderColor === 'transparent' ? 'transparent' : theme.border;
        });
    }

    if (themed.visualMap) {
        themed.visualMap.textStyle = { ...(themed.visualMap.textStyle || {}), color: theme.text };
    }

    normalizeTextNodes(themed, theme);
    return themed;
}

export function chartLoadingOptions(text = 'Loading...') {
    return {
        text,
        color: '#60a5fa',
        textColor: chartTheme().text,
        maskColor: chartTheme().loadingMask,
    };
}

export function chartFullscreenBackground() {
    return chartTheme().surface;
}

export function themedNoDataHtml(message = 'No data available') {
    return `<div style="padding:20px;color:${chartTheme().muted};">${message}</div>`;
}

export function themedErrorHtml(message = 'Failed to load chart') {
    return `<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#f87171;font-size:1.1rem;">${message}</div>`;
}

export function themedDataViewStyles(tableId) {
    const theme = chartTheme();
    return `
        <style>
            #${tableId} {
                width: 100%;
                border-collapse: collapse;
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 14px;
                margin: 10px 0;
                color: ${theme.text};
                background: ${theme.surface};
                box-shadow: 0 2px 8px rgba(0,0,0,${theme.isDark ? '0.35' : '0.1'});
            }
            #${tableId} th, #${tableId} td {
                border: 1px solid ${theme.border};
                padding: 10px 12px;
                text-align: center;
                white-space: nowrap;
            }
            #${tableId} th {
                background: ${theme.surfaceMuted};
                font-weight: bold;
                color: ${theme.text};
                position: sticky;
                top: 0;
                z-index: 1;
            }
            #${tableId} tr:nth-child(even) {
                background-color: ${theme.isDark ? '#172033' : '#f9fafb'};
            }
            #${tableId} td:first-child {
                font-weight: bold;
                background-color: ${theme.surfaceMuted};
                text-align: left;
            }
        </style>`;
}

export function bindChartTheme(chart, refresh) {
    const handler = () => {
        refresh?.();
        chart?.resize?.();
    };

    window.addEventListener('lrmis:themechange', handler);
    window.addEventListener('beforeunload', () => {
        window.removeEventListener('lrmis:themechange', handler);
    }, { once: true });
}
