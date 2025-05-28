let oeeChart, qualityChart, performanceChart, availabilityChart;

function LineshowError(chartId, messageId) {
    document.getElementById(chartId).style.opacity = "1"; // optional visual effect 0.4
    document.getElementById(messageId).style.display = "block"; //default
    document.getElementById(messageId).style.display = "none";//testRun
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
                legend: {
                    display: true,
                    position: 'left'
                }
            }
        }
    });
}

function renderPieChartWithLoss(chartRef, ctx, label, value, lossBreakdown, mainColor) {
    if (chartRef) chartRef.destroy();

    const labels = [label];
    const data = [value];
    const backgroundColor = [mainColor];

    const lossColors = {
        Man: '#65A6FA',
        Machine: '#BB109D',
        Method: '#FF914D',
        Material: '#FFDE59',
        Other: '#D9D9D9'
    };

    for (const [lossType, val] of Object.entries(lossBreakdown)) {
        labels.push(lossType);
        data.push(val);
        backgroundColor.push(lossColors[lossType] || '#ccc');
    }

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColor,
                cutout: '80%',
                borderColor: true,
                borderWidth: 0.5,
                hoverOffset: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: false,
                    text: `${label}: ${value.toFixed(1)}%`,
                    font: { size: 16 }
                },
                legend: {
                    display: false,
                    position: 'left'
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
        const response = await fetch("../api/get_oee_summary.php?log_date=<?=date('Y-m-d')?>&shift=A");
        const data = await response.json();
        if (!data.success) throw new Error("Invalid data");

        oeeChart = renderPieChartWithLoss(
            oeeChart,
            document.getElementById("oeePieChart").getContext("2d"),
            'OEE',
            data.oee,
            data.loss_breakdown,
            '#00BF63'
        );

        qualityChart = renderPieChartWithLoss(
            qualityChart,
            document.getElementById("qualityPieChart").getContext("2d"),
            'Quality',
            data.quality,
            data.quality_loss_breakdown,
            '#00BF63'
        );

        performanceChart = renderPieChartWithLoss(
            performanceChart,
            document.getElementById("performancePieChart").getContext("2d"),
            'Performance',
            data.performance,
            data.performance_loss_breakdown,
            '#00BF63'
        );

        availabilityChart = renderPieChartWithLoss(
            availabilityChart,
            document.getElementById("availabilityPieChart").getContext("2d"),
            'Availability',
            data.availability,
            data.availability_loss_breakdown,
            '#00BF63'
        );


    } catch (err) {
        console.error("Chart fetch failed:", err);

        if (oeeChart) oeeChart.destroy();
        if (qualityChart) qualityChart.destroy();
        if (performanceChart) performanceChart.destroy();
        if (availabilityChart) availabilityChart.destroy();

        // Simulated fallback values
        const simulated = {
            oee: 65.5,
            quality: 92.3,
            performance: 78.6,
            availability: 88.4,
            loss_breakdown: {
                Man: 10, Machine: 12, Method: 6, Material: 5, Other: 1
            },
            quality_loss_breakdown: {
                Man: 3, Machine: 2, Method: 1, Material: 1, Other: 1
            },
            performance_loss_breakdown: {
                Man: 8, Machine: 7, Method: 3, Material: 1, Other: 2
            },
            availability_loss_breakdown: {
                Man: 6, Machine: 4, Method: 3, Material: 2, Other: 2
            }
        };

        oeeChart = renderPieChartWithLoss(
            oeeChart,
            document.getElementById("oeePieChart").getContext("2d"),
            'OEE',
            simulated.oee,
            simulated.loss_breakdown,
            '#00BF63'
        );

        qualityChart = renderPieChartWithLoss(
            qualityChart,
            document.getElementById("qualityPieChart").getContext("2d"),
            'Quality',
            simulated.quality,
            simulated.quality_loss_breakdown,
            '#00BF63'
        );

        performanceChart = renderPieChartWithLoss(
            performanceChart,
            document.getElementById("performancePieChart").getContext("2d"),
            'Performance',
            simulated.performance,
            simulated.performance_loss_breakdown,
            '#00BF63'
        );

        availabilityChart = renderPieChartWithLoss(
            availabilityChart,
            document.getElementById("availabilityPieChart").getContext("2d"),
            'Availability',
            simulated.availability,
            simulated.availability_loss_breakdown,
            '#00BF63'
        );

        // Optional visual cue for simulated data
        LineshowError("oeePieChart", "oeeError");
        LineshowError("qualityPieChart", "qualityError");
        LineshowError("performancePieChart", "performanceError");
        LineshowError("availabilityPieChart", "availabilityError");
    }

}

window.addEventListener("load", () => {
    fetchAndRenderCharts();
    setInterval(fetchAndRenderCharts, 60000);
});
