let partsBarChartInstance, stopCauseBarChartInstance;
Chart.register(ChartZoom);

function hideErrors() {
    ["partsBarError", "stopCauseBarError"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    });

    ["partsBarChart", "stopCauseBarChart"].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) canvas.style.opacity = "1";
    });
}

function BarshowError(chartId, messageId) {
    document.getElementById(chartId).style.opacity = "1";
    document.getElementById(messageId).style.display = "none";
}

function renderBarChart(chartInstance, ctx, labels, valuesOrDatasets, labelOrOptions, color = "#42a5f5") {
    if (chartInstance) chartInstance.destroy();

    const isMulti = Array.isArray(valuesOrDatasets) && valuesOrDatasets[0]?.data;
    const labelString = typeof labelOrOptions === 'string' ? labelOrOptions : '';
    const tooltipInfo = typeof labelOrOptions === 'object' ? labelOrOptions.tooltipInfo : null;

    const datasets = isMulti
        ? valuesOrDatasets
        : [{
            label: labelString,
            data: valuesOrDatasets,
            backgroundColor: color,
            borderRadius: 4
        }];

    const chart = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true },
                title: { display: false },
                tooltip: {
                    callbacks: {
                        afterBody: function (context) {
                            const index = context[0].dataIndex;
                            const lineName = context[0].chart.data.labels[index];
                            const tooltipInfo = context[0].chart.options.plugins.tooltip.tooltipInfo;
                            return tooltipInfo?.[lineName] ? `Total: ${tooltipInfo[lineName]}` : '';
                        }
                    }
                },
                zoom: {
                    pan: { enabled: true, mode: 'x' },
                    zoom: {
                        wheel: { enabled: true },
                        pinch: { enabled: true },
                        mode: 'x'
                    }
                }
            },
            layout: { padding: 10 },
            scales: {
                x: {
                    stacked: isMulti,
                    ticks: { color: '#ccc', autoSkip: false },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    stacked: isMulti,
                    ticks: { color: '#ccc' },
                    grid: { drawBorder: false, color: '#444' }
                }
            }
        }
    });

    if (isMulti && labelOrOptions?.tooltipInfo) {
    chart.options.plugins.tooltip.tooltipInfo = labelOrOptions.tooltipInfo;
    }

    return chart;
}

function padBarData(labels, values, minCount) {
    const paddedLabels = [...labels];
    const paddedValues = [...values];

    while (paddedLabels.length < minCount) {
        paddedLabels.push("N/A");
        paddedValues.push(0);
    }

    return { labels: paddedLabels, values: paddedValues };
}

async function fetchAndRenderBarCharts() {
    try {
        hideErrors();

        const startDate = document.getElementById("startDate")?.value || '';
        const endDate = document.getElementById("endDate")?.value || '';
        const line = document.getElementById("lineFilter")?.value || '';
        const model = document.getElementById("modelFilter")?.value || '';

        const params = new URLSearchParams({ startDate, endDate, line, model });

        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);

        const response = await fetch(`../api/OEE_Dashboard/get_oee_barchart.php?${params.toString()}`);
        const data = await response.json();

        // Part Bar Chart
        const partLabels = data.parts.labels;
        const countTypes = {
            FG:     { label: "Good",    color: "#00C853" },
            NG:     { label: "NG",      color: "#FF5252" },
            HOLD:   { label: "Hold",    color: "#FFD600" },
            REWORK: { label: "Rework",  color: "#2979FF" },
            SCRAP:  { label: "Scrap",   color: "#9E9E9E" },
            ETC:    { label: "ETC",     color: "#AA00FF" }
        };

        const partDatasets = Object.entries(countTypes).map(([type, { label, color }]) => {
            return data.parts[type]
                ? { label, data: data.parts[type], backgroundColor: color }
                : null;
        }).filter(Boolean);

        partsBarChartInstance = renderBarChart(
            partsBarChartInstance,
            document.getElementById("partsBarChart").getContext("2d"),
            partLabels,
            partDatasets
        );

        // Stop Cause Bar Chart
        const stopCauseLabels = data.stopCause.labels; // ← x-axis: production lines
        const tooltipInfo = data.stopCause.tooltipInfo || {};

        const stopCauseDatasets = data.stopCause.datasets.map(causeSet => ({
            label: causeSet.label,                      // ← each dataset = cause
            data: causeSet.data,                        // ← time (mins) for each line
            backgroundColor: causeSet.backgroundColor || getRandomColor(),
            borderRadius: 1
        }));

        stopCauseBarChartInstance = renderBarChart(
            stopCauseBarChartInstance,
            document.getElementById("stopCauseBarChart").getContext("2d"),
            stopCauseLabels,
            stopCauseDatasets,
            { tooltipInfo } // ✅ pass total time per line
        );

    } catch (err) {
        console.error("Bar chart fetch failed:", err);
        hideErrors();
    }
}

function getRandomColor() {
    return `hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`;
}

function updateURLParamsFromFilters() {
    const params = new URLSearchParams();
    const startDate = document.getElementById("startDate")?.value;
    const endDate = document.getElementById("endDate")?.value;
    const line = document.getElementById("lineFilter")?.value;
    const model = document.getElementById("modelFilter")?.value;

    if (startDate) params.set("startDate", startDate);
    if (endDate) params.set("endDate", endDate);
    if (line) params.set("line", line);
    if (model) params.set("model", model);

    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, '', newUrl);
}

async function populateDropdown(selectId, apiPath, selectedValue = '') {
    try {
        const res = await fetch(apiPath);
        const data = await res.json();
        const select = document.getElementById(selectId);
        if (!select) return;

        const label = selectId === 'lineFilter' ? 'Lines' : 'Models';
        select.innerHTML = `<option value="">All ${label}</option>`;

        data.forEach(item => {
            const option = document.createElement("option");
            option.value = item;
            option.textContent = item;
            select.appendChild(option);
        });

        if (selectedValue) select.value = selectedValue;

    } catch (err) {
        console.error(`Failed to load ${selectId} options:`, err);
    }
}
