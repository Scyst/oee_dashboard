let oeeChart, qualityChart, performanceChart, availabilityChart;

function showError(chartId, messageId) {
    document.getElementById(chartId).style.opacity = "0.4"; // optional visual effect
    document.getElementById(messageId).style.display = "block";
}

function hideErrors() {
    ["oeeError", "qualityError", "performanceError", "availabilityError"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    });
    ["oeePieChart", "qualityPieChart", "performancePieChart", "availabilityPieChart"].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) canvas.style.opacity = "1";
    });
}

function createEmptyChart(ctx, message) {
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Error'],
            datasets: [{
                data: [1],
                backgroundColor: ['#f44336'],
                cutout: '80%',
                borderColor: false,
                borderWidth: 0
            }]
        },
        options: {
            plugins: {
                /*title: {
                    display: true,
                    text: message,
                    color: '#f44336',
                    font: { size: 16 }
                },*/
                legend: { display: false }
            }
        }
    });
}

function renderPieChart(chartRef, ctx, label, value, color) {
    if (chartRef) chartRef.destroy();
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [label, 'Remaining'],
            datasets: [{
                data: [value, 100 - value],
                backgroundColor: [color, '#e0e0e0'],
                cutout: '80%',
                borderColor: false,
                borderWidth: 0
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: `${label}: ${value.toFixed(1)}%`,
                    font: { size: 16 }
                },
                legend: { display: false }
            }
        }
    });
}

async function fetchAndRenderCharts() {
    try {
        hideErrors();
        const response = await fetch("api/get_oee_summary.php?log_date=<?=date('Y-m-d')?>&shift=A");
        const data = await response.json();
        if (!data.success) throw new Error("Invalid data");

        oeeChart = renderPieChart(oeeChart, document.getElementById("oeePieChart").getContext("2d"), 'OEE', data.oee, '#42a5f5');
        qualityChart = renderPieChart(qualityChart, document.getElementById("qualityPieChart").getContext("2d"), 'Quality', data.quality, '#66bb6a');
        performanceChart = renderPieChart(performanceChart, document.getElementById("performancePieChart").getContext("2d"), 'Performance', data.performance, '#ffa726');
        availabilityChart = renderPieChart(availabilityChart, document.getElementById("availabilityPieChart").getContext("2d"), 'Availability', data.availability, '#ab47bc');

    } catch (err) {
        console.error("Chart fetch failed:", err);

        if (oeeChart) oeeChart.destroy();
        if (qualityChart) qualityChart.destroy();
        if (performanceChart) performanceChart.destroy();
        if (availabilityChart) availabilityChart.destroy();

        oeeChart = createEmptyChart(document.getElementById("oeePieChart").getContext("2d"), "Can't fetch OEE");
        qualityChart = createEmptyChart(document.getElementById("qualityPieChart").getContext("2d"), "Can't fetch Quality");
        performanceChart = createEmptyChart(document.getElementById("performancePieChart").getContext("2d"), "Can't fetch Performance");
        availabilityChart = createEmptyChart(document.getElementById("availabilityPieChart").getContext("2d"), "Can't fetch Availability");

        showError("oeePieChart", "oeeError");
        showError("qualityPieChart", "qualityError");
        showError("performancePieChart", "performanceError");
        showError("availabilityPieChart", "availabilityError");

    }
}

window.addEventListener("load", () => {
    fetchAndRenderCharts();
    setInterval(fetchAndRenderCharts, 60000);
});
