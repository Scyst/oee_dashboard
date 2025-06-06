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

function renderSimplePieChart(chartRef, ctx, label, rawValue, mainColor) {
    if (chartRef) chartRef.destroy();

    const value = Math.min(rawValue, 100); // Clamp to 100
    const loss = Math.max(0, 100 - value);

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [label, 'Loss'],
            datasets: [{
                data: [value, loss],
                backgroundColor: [mainColor, '#9E9E9E'],
                cutout: '80%',
                borderWidth: 0.5
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.label}: ${context.parsed.toFixed(1)}%`
                    }
                },
                title: { display: false },
                centerText: {
                    display: true,
                    text: `${rawValue.toFixed(1)}%`
                }
            }
        },
        plugins: [{
            id: 'centerText',
            beforeDraw(chart) {
                const { width, height } = chart;
                const ctx = chart.ctx;
                ctx.restore();
                const fontSize = (height / 150).toFixed(2);
                ctx.font = `${fontSize}em sans-serif`;
                ctx.textBaseline = "middle";
                const text = chart.options.plugins.centerText.text;
                const textX = Math.round((width - ctx.measureText(text).width) / 2);
                const textY = height / 2;
                ctx.fillStyle = "#ffffff";
                ctx.fillText(text, textX, textY);
                ctx.save();
            }
        }]
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

        // ✅ Update URL for memory
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);

        const response = await fetch(`../api/OEE_Dashboard/get_oee_piechart.php?${params.toString()}`);
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
            '#ab47bc'
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
        document.getElementById("oeeInfo").innerHTML = `
            <small>
                FG: ${data.fg.toLocaleString()} pcs<br>
                Defects: ${data.defects.toLocaleString()} pcs<br>
                Total: ${data.actual_output.toLocaleString()} pcs
            </small>
        `;

        document.getElementById("qualityInfo").innerHTML = `
            <small>
                FG: ${data.fg.toLocaleString()} pcs<br>
                Defects: ${data.defects.toLocaleString()} pcs
            </small>
        `;

        document.getElementById("performanceInfo").innerHTML = `
            <small>
                Actual: ${data.actual_output.toLocaleString()} pcs<br>
                Planned: ${data.planned_output.toLocaleString()} pcs
            </small>
        `;

        document.getElementById("availabilityInfo").innerHTML = `
            <small>
                Planned: ${formatMinutes(data.planned_time)}<br>
                Downtime: ${formatMinutes(data.downtime)}<br>
                Runtime: ${formatMinutes(data.runtime)}
            </small>
        `;

    } catch (err) {
        console.error("Pie chart fetch failed:", err);
        LineshowError("oeePieChart", "oeeError");
        LineshowError("qualityPieChart", "qualityError");
        LineshowError("performancePieChart", "performanceError");
        LineshowError("availabilityPieChart", "availabilityError");
    }
}
