let oeeLineChart;

function hideErrors() {
    const el = document.getElementById("oeeLineError");
    if (el) el.style.display = "none";
    const canvas = document.getElementById("oeeLineChart");
    if (canvas) canvas.style.opacity = "1";
}

function showError(chartId, messageId) {
    const canvas = document.getElementById(chartId);
    const errorMsg = document.getElementById(messageId);
    if (canvas) canvas.style.opacity = "1";
    if (errorMsg) errorMsg.style.display = "block";
}

function renderCombinedLineChart(labels, datasets) {
    const ctx = document.getElementById("oeeLineChart").getContext("2d");
    if (oeeLineChart) oeeLineChart.destroy();

    oeeLineChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2.5,
            plugins: {
                title: {
                    display: true,
                    text: "OEE Trends",
                    font: { size: 16, weight: "bold" },
                    color: "#fff"
                },
                legend: {
                    display: true,
                    labels: { color: '#ccc' }
                },
                tooltip: {
                    backgroundColor: "#333",
                    titleColor: "#fff",
                    bodyColor: "#fff"
                }
            },
            scales: {
                x: {
                    ticks: { color: "#ccc", font: { size: 10 } },
                    grid: { display: false, color: "#444" }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { color: "#ccc", font: { size: 10 } },
                    grid: { color: "#444" },
                    title: { display: false }
                }
            },
            layout: { padding: 10 }
        }
    });
}

async function fetchAndRenderLineCharts() {
    try {
        hideErrors();

        const response = await fetch("../api/OEE_Dashboard/get_oee_linechart.php");
        const data = await response.json();

        if (!data.success) throw new Error("Data error");

        const labels = data.records.map(r => r.date);
        const datasets = [
            {
                label: "OEE (%)",
                data: data.records.map(r => r.oee),
                borderColor: "#42a5f5",
                backgroundColor: "#42a5f533",
                tension: 0.1,
                fill: false,
                pointRadius: 3,
                pointBackgroundColor: "#42a5f5"
            },
            {
                label: "Quality (%)",
                data: data.records.map(r => r.quality),
                borderColor: "#66bb6a",
                backgroundColor: "#66bb6a33",
                tension: 0.1,
                fill: false,
                pointRadius: 3,
                pointBackgroundColor: "#66bb6a"
            },
            {
                label: "Performance (%)",
                data: data.records.map(r => r.performance),
                borderColor: "#ffa726",
                backgroundColor: "#ffa72633",
                tension: 0.1,
                fill: false,
                pointRadius: 3,
                pointBackgroundColor: "#ffa726"
            },
            {
                label: "Availability (%)",
                data: data.records.map(r => r.availability),
                borderColor: "#ab47bc",
                backgroundColor: "#ab47bc33",
                tension: 0.1,
                fill: false,
                pointRadius: 3,
                pointBackgroundColor: "#ab47bc"
            }
        ];

        renderCombinedLineChart(labels, datasets);

    } catch (err) {
        console.error("Line chart fetch failed:", err);

        showError("oeeLineChart", "oeeLineError");
    }
}

window.addEventListener("load", () => {
    fetchAndRenderLineCharts();
    setInterval(fetchAndRenderLineCharts, 60000);
});
