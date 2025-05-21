let oeeLineChart, qualityLineChart, performanceLineChart, availabilityLineChart;

function hideErrors() {
    ["oeeLineError", "qualityLineError", "performanceLineError", "availabilityLineError"].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none"; //default
       //if (el) el.style.display = "block";
    });
    ["oeeLineChart", "qualityLineChart", "performanceLineChart", "availabilityLineChart"].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) canvas.style.opacity = "1";
    });
}

function PieshowError(chartId, messageId) {
    const canvas = document.getElementById(chartId);
    const errorMsg = document.getElementById(messageId);
    if (canvas) canvas.style.opacity = "0.4";
    //if (errorMsg) errorMsg.style.display = "block"; //default
    if (errorMsg) errorMsg.style.display = "none"; //testRun
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
                fill: false,
                tension: 0.1,
                pointRadius: 3,
                pointBackgroundColor: color,
                pointBorderColor: "#fff",
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2.5,
            plugins: {
                title: {
                    display: false,
                    text: title,
                    font: { size: 16, weight: "bold" },
                    color: "#fff"
                },
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "#333",
                    titleColor: "#fff",
                    bodyColor: "#fff"
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: "#ccc",
                        font: { size: 10 }
                    },
                    grid: {
                        display: false,
                        color: "#444"
                    }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    //suggestedMin: 0,
                    //suggestedMax: 100,
                    ticks: {
                        color: "#ccc",
                        font: { size: 10 }
                    },
                    grid: {
                        color: "#444"
                    },
                    title: {
                        display: false
                    }
                }
            },
            layout: {
                padding: 10
            }
        }
    });
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

        const simulatedDates = Array.from({ length: 7 }, (_, i) => {
            const date = new Date();
            date.setDate(date.getDate() - (6 - i));
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            return `${day}/${month}`;
        });


        const simulatedData = {
            oee: [65, 67, 66, 68, 64, 66, 67],
            quality: [90, 91, 92, 90, 89, 91, 90],
            performance: [70, 72, 71, 69, 70, 71, 70],
            availability: [80, 82, 81, 83, 79, 80, 82]
        };

        oeeLineChart = renderLineChart("oeeLineChart", "OEE (%)", simulatedDates, simulatedData.oee, "#66bb6a", oeeLineChart);
        qualityLineChart = renderLineChart("qualityLineChart", "Quality (%)", simulatedDates, simulatedData.quality, "#66bb6a", qualityLineChart);
        performanceLineChart = renderLineChart("performanceLineChart", "Performance (%)", simulatedDates, simulatedData.performance, "#66bb6a", performanceLineChart);
        availabilityLineChart = renderLineChart("availabilityLineChart", "Availability (%)", simulatedDates, simulatedData.availability, "#66bb6a", availabilityLineChart);

        PieshowError("oeeLineChart", "oeeLineError");
        PieshowError("qualityLineChart", "qualityLineError");
        PieshowError("performanceLineChart", "performanceLineError");
        PieshowError("availabilityLineChart", "availabilityLineError")
    }

}


window.addEventListener("load", () => {
    fetchAndRenderLineCharts();
    setInterval(fetchAndRenderLineCharts, 60000); // Refresh every minute
});
