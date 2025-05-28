let scrapBarChartInstance, stopCauseBarChartInstance;

function hideErrors() {
    ["scrapBarError", "stopCauseBarError"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    });

    ["scrapBarChart", "stopCauseBarChart"].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) canvas.style.opacity = "1";
    });
}

function BarshowError(chartId, messageId) {
    document.getElementById(chartId).style.opacity = "1"; //0.4
   //document.getElementById(messageId).style.display = "block"; //default
    document.getElementById(messageId).style.display = "none"; //testRun
}

function renderBarChart(chartInstance, ctx, labels, values, label, color) {
    if (chartInstance) chartInstance.destroy();

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: values,
                backgroundColor: color,
                borderRadius: 4,
                // Remove fixed bar thickness to allow auto scaling
                // barThickness: 25 
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // This is important for filling container height
            plugins: {
                legend: { display: false },
                title: {
                    display: false,
                    text: label,
                    font: { size: 16 }
                }
            },
            layout: {
                padding: 10 // Optional: adds inner spacing
            },
            scales: {
                x: {
                    ticks: { color: '#ccc', autoSkip: false }, // Prevent label skipping
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: '#ccc' },
                    grid: {
                        drawBorder: false,
                        color: '#444' // Optional: subtle grid lines
                    }
                }
            }
        }
    });
}


async function fetchAndRenderBarCharts() {
    try {
        /*hideErrors();

        const response = await fetch("api/get_bar_data.php");
        const data = await response.json();

        scrapBarChartInstance = renderBarChart(
            scrapBarChartInstance,
            document.getElementById("scrapBarChart").getContext("2d"),
            data.scrap.labels,
            data.scrap.values,
            "Scrap Types",
            "#ef5350"
        );

        stopCauseBarChartInstance = renderBarChart(
            stopCauseBarChartInstance,
            document.getElementById("stopCauseBarChart").getContext("2d"),
            data.stopCause.labels,
            data.stopCause.values,
            "Stop Causes",
            "#42a5f5"
        );*/
        hideErrors();

        const response = await fetch("../api/get_stop_causes.php");
        const data = await response.json();

        // Ensure at least 7 bars for scrap
        const paddedScrap = padBarData(data.scrap.labels, data.scrap.values, 7);
        scrapBarChartInstance = renderBarChart(
            scrapBarChartInstance,
            document.getElementById("scrapBarChart").getContext("2d"),
            paddedScrap.labels,
            paddedScrap.values,
            "Scrap Types",
            "#ef5350"
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

        hideErrors(); // still hide any old errors

        // 🧪 Simulation data for fallback
        const simulatedScrap = {
            labels: ["Crack", "Dent", "Scratch", "Warp", "N/A", "N/A", "N/A", "N/A"],
            values: [51, 7, 5, 3, 0, 0, 0, 0]
        };

        const simulatedStops = {
            labels: ["Man", "Machine", "Material", "Method", "Other"],
            values: [4, 8, 2, 6, 1]
        };

        scrapBarChartInstance = renderBarChart(
            scrapBarChartInstance,
            document.getElementById("scrapBarChart").getContext("2d"),
            simulatedScrap.labels,
            simulatedScrap.values,
            "Scrap Types (Simulated)",
            "#ef5350"
        );

        stopCauseBarChartInstance = renderBarChart(
            stopCauseBarChartInstance,
            document.getElementById("stopCauseBarChart").getContext("2d"),
            simulatedStops.labels,
            simulatedStops.values,
            "Stop Causes (Simulated)",
            "#42a5f5"
        );
        BarshowError("stopCauseBarChart", "stopCauseBarError")
        BarshowError("scrapBarChart", "scrapBarError")
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
