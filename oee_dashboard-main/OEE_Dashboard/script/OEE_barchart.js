let partsBarChartInstance, stopCauseBarChartInstance;

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
    document.getElementById(chartId).style.opacity = "1"; //0.4
   //document.getElementById(messageId).style.display = "block"; //default
    document.getElementById(messageId).style.display = "none"; //testRun
}

function renderBarChart(chartInstance, ctx, labels, valuesOrDatasets, labelOrOptions, color = "#42a5f5") {
    if (chartInstance) chartInstance.destroy();

    // Detect if multiple datasets passed
    const isMulti = Array.isArray(valuesOrDatasets) && valuesOrDatasets[0]?.data;

    const datasets = isMulti
        ? valuesOrDatasets
        : [{
            label: labelOrOptions,
            data: valuesOrDatasets,
            backgroundColor: color,
            borderRadius: 4
        }];

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true },
                title: {
                    display: false,
                    text: isMulti ? '' : labelOrOptions,
                    font: { size: 16 }
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
}

async function fetchAndRenderBarCharts() {
    try {
        hideErrors();

        const startDate = document.getElementById("startDate")?.value || '';
        const endDate = document.getElementById("endDate")?.value || '';

        const params = new URLSearchParams({
            startDate,
            endDate
        });

        const response = await fetch(`../api/OEE_dashboard/get_stop_causes.php?${params.toString()}`);
        const data = await response.json();

        // Ensure at least 7 bars for scrap
        //const paddedScrap = padBarData(data.scrap.labels, data.scrap.values, 7);
        partsBarChartInstance = renderBarChart(
            partsBarChartInstance,
            document.getElementById("partsBarChart").getContext("2d"),
            data.parts.labels,
            [
                {
                    label: "Good Jobs",
                    data: data.parts.good,
                    backgroundColor: "#00C853"
                },
                {
                    label: "Bad Jobs",
                    data: data.parts.bad,
                    backgroundColor: "#FF5252"
                }
            ]
        );

        // Ensure at least 7 bars for stop cause
        const paddedStopCause = padBarData(data.stopCause.labels, data.stopCause.values, 7);
        stopCauseBarChartInstance = renderBarChart(
            stopCauseBarChartInstance,
            document.getElementById("stopCauseBarChart").getContext("2d"),
            paddedStopCause.labels,
            paddedStopCause.values,
            "Stop Causes",
            "#42a5f5"
        );

    } catch (err) {
        console.error("Bar chart fetch failed:", err);
        hideErrors();
        // ... fallback simulated data ...
    }
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


window.addEventListener("load", () => {
    fetchAndRenderBarCharts();
    setInterval(fetchAndRenderBarCharts, 60000); // Optional auto-refresh
});

document.getElementById("startDate")?.addEventListener("change", fetchAndRenderBarCharts);
document.getElementById("endDate")?.addEventListener("change", fetchAndRenderBarCharts);

