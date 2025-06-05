let oeeChart, qualityChart, performanceChart, availabilityChart;

function LineshowError(chartId, messageId) {
    document.getElementById(chartId).style.opacity = "1";
    document.getElementById(messageId).style.display = "none"; // testRun
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

function renderSimplePieChart(chartRef, ctx, label, value, mainColor) {
    if (chartRef) chartRef.destroy();

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [label, 'Loss'],
            datasets: [{
                data: [value, 100 - value],
                backgroundColor: [mainColor, '#e0e0e0'],
                cutout: '80%',
                borderWidth: 0.5
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: `${label}: ${value.toFixed(1)}%`,
                    font: { size: 14 }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${context.formattedValue}%`
                    }
                }
            }
        }
    });
}

async function fetchAndRenderCharts() {
    try {
        hideErrors();

        const params = new URLSearchParams({
            startDate: document.getElementById("startDate")?.value || '',
            endDate: document.getElementById("endDate")?.value || '',
            line: document.getElementById("lineFilter")?.value || '',
            model: document.getElementById("modelFilter")?.value || ''
        });

        const response = await fetch(`../api/OEE_Dashboard/get_oee_summary.php?${params.toString()}`);
        const data = await response.json();

        if (!data.success) throw new Error("Invalid data");

        oeeChart = renderSimplePieChart(
            oeeChart,
            document.getElementById("oeePieChart").getContext("2d"),
            'OEE',
            data.oee,
            '#00BF63'
        );

        qualityChart = renderSimplePieChart(
            qualityChart,
            document.getElementById("qualityPieChart").getContext("2d"),
            'Quality',
            data.quality,
            '#66bb6a'
        );

        performanceChart = renderSimplePieChart(
            performanceChart,
            document.getElementById("performancePieChart").getContext("2d"),
            'Performance',
            data.performance,
            '#ffa726'
        );

        availabilityChart = renderSimplePieChart(
            availabilityChart,
            document.getElementById("availabilityPieChart").getContext("2d"),
            'Availability',
            data.availability,
            '#42a5f5'
        );

    } catch (err) {
        console.error("Chart fetch failed:", err);
        LineshowError("oeePieChart", "oeeError");
        LineshowError("qualityPieChart", "qualityError");
        LineshowError("performancePieChart", "performanceError");
        LineshowError("availabilityPieChart", "availabilityError");
    }
}

window.addEventListener("load", () => {
    ["startDate", "endDate", "lineFilter", "modelFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", fetchAndRenderCharts);
    });

    fetchAndRenderCharts();
    setInterval(fetchAndRenderCharts, 60000);
});
