let oeeLineChart, qualityLineChart, performanceLineChart, availabilityLineChart;

function hideErrors() {
    ["oeeLineError", "qualityLineError", "performanceLineError", "availabilityLineError"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    });
    ["oeeLineChart", "qualityLineChart", "performanceLineChart", "availabilityLineChart"].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) canvas.style.opacity = "1";
    });
}

function showError(chartId, messageId) {
    const canvas = document.getElementById(chartId);
    const errorMsg = document.getElementById(messageId);
    if (canvas) canvas.style.opacity = "0.4";
    if (errorMsg) errorMsg.style.display = "block";
}

function renderEmptyLineChart(chartRefVar, setRefCallback, canvasId, messageId) {
    const ctx = document.getElementById(canvasId).getContext("2d");

    if (chartRefVar) chartRefVar.destroy(); // 💥 prevent the reuse error

    const emptyChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: [],
            datasets: []
        },
        options: {
            plugins: {
                title: {
                    display: false,
                    text: "Can't fetch data",
                    color: '#f44336',
                    font: { size: 16 }
                },
                legend: { display: false }
            },
            scales: {
                x: { display: false },
                y: { display: false }
            }
        }
    });

    showError(canvasId, messageId);

    // Save reference so it can be destroyed later
    setRefCallback(emptyChart);
}


async function fetchAndRenderLineCharts() {
    try {
        hideErrors(); // clear previous errors
        const response = await fetch("api/get_oee_7day.php");
        const data = await response.json();

        if (!data.success) throw new Error("Data error");

        const labels = data.records.map(r => r.date);
        const oee = data.records.map(r => r.oee);
        const quality = data.records.map(r => r.quality);
        const performance = data.records.map(r => r.performance);
        const availability = data.records.map(r => r.availability);

        oeeLineChart = renderLineChart("oeeLineChart", "OEE (%)", labels, oee, "#42a5f5", oeeLineChart);
        qualityLineChart = renderLineChart("qualityLineChart", "Quality (%)", labels, quality, "#66bb6a", qualityLineChart);
        performanceLineChart = renderLineChart("performanceLineChart", "Performance (%)", labels, performance, "#ffa726", performanceLineChart);
        availabilityLineChart = renderLineChart("availabilityLineChart", "Availability (%)", labels, availability, "#ab47bc", availabilityLineChart);

    } catch (err) {
        console.error("Line chart fetch failed:", err);
        renderEmptyLineChart(oeeLineChart, chart => oeeLineChart = chart, "oeeLineChart", "oeeLineError");
        renderEmptyLineChart(qualityLineChart, chart => qualityLineChart = chart, "qualityLineChart", "qualityLineError");
        renderEmptyLineChart(performanceLineChart, chart => performanceLineChart = chart, "performanceLineChart", "performanceLineError");
        renderEmptyLineChart(availabilityLineChart, chart => availabilityLineChart = chart, "availabilityLineChart", "availabilityLineError");
    }
}

function renderLineChart(canvasId, title, labels, data, color, chartRef) {
    const ctx = document.getElementById(canvasId).getContext("2d");
    if (chartRef) chartRef.destroy();

    return new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: title,
                data: data,
                borderColor: color,
                backgroundColor: color + "33",
                fill: true,
                tension: 0.3,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            aspectRatio: 2.5,
            plugins: {
                title: {
                    display: true,
                    text: title,
                    font: { size: 18 }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: "Percentage (%)"
                    }
                }
            }
        }
    });
}

window.addEventListener("load", () => {
    fetchAndRenderLineCharts();
    setInterval(fetchAndRenderLineCharts, 60000); // Refresh every minute
});
