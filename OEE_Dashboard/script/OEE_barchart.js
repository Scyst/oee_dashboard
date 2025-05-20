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

function showError(chartId, messageId) {
    document.getElementById(chartId).style.opacity = "1";
    document.getElementById(messageId).style.display = "block";
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
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
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

        const response = await fetch("api/get_bar_data.php"); // Adjust to your API
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

        if (scrapBarChartInstance) scrapBarChartInstance.destroy();
        if (stopCauseBarChartInstance) stopCauseBarChartInstance.destroy();

        showError("scrapBarChart", "scrapBarError");
        showError("stopCauseBarChart", "stopCauseBarError");
    }
}


window.addEventListener("load", () => {
    fetchAndRenderBarCharts();
    setInterval(fetchAndRenderBarCharts, 60000); // Optional auto-refresh
});
