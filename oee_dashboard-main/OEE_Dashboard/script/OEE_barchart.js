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
    document.getElementById(chartId).style.opacity = "0.4";
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
                barThickness: 25,
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: false,
                    text: label,
                    font: { size: 16 }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#333' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: '#333' }
                }
            }
        }
    });
}

async function fetchAndRenderBarCharts() {
    try {
        hideErrors();

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
        );

    } catch (err) {
        console.error("Bar chart fetch failed:", err);

        hideErrors(); // still hide any old errors

        // 🧪 Simulation data for fallback
        const simulatedScrap = {
            labels: ["Crack", "Dent", "Scratch", "Warp"],
            values: [10, 7, 5, 3]
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
    }
}

window.addEventListener("load", () => {
    fetchAndRenderBarCharts();
    setInterval(fetchAndRenderBarCharts, 60000); // Optional auto-refresh
});
