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
    if (errorMsg) errorMsg.style.display = "none";
}

function renderCombinedLineChart(labels, datasets) {
    const canvas = document.getElementById("oeeLineChart");
    const ctx = canvas.getContext("2d");
    if (oeeLineChart) oeeLineChart.destroy();

    oeeLineChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,          // ✅ allows scaling
            maintainAspectRatio: false, // ✅ allows full height/width
            plugins: {
                title: {
                    display: false,
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
                    grid: { color: "#444" }
                }
            },
            layout: { padding: 10 }
        }
    });
}

async function fetchAndRenderLineCharts() {
    try {
        hideErrors();

        const startDate = document.getElementById("startDate")?.value || '';
        const endDate   = document.getElementById("endDate")?.value || '';
        const line      = document.getElementById("lineFilter")?.value || '';
        const model     = document.getElementById("modelFilter")?.value || '';

        const params = new URLSearchParams({
            startDate,
            endDate,
            line,
            model
        });

        // ⏎ Update browser URL (preserve filters on refresh)
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);

        const response = await fetch(`../api/OEE_Dashboard/get_oee_linechart.php?${params.toString()}`);
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
    const params = new URLSearchParams(window.location.search);

    const startDate = params.get("startDate");
    const endDate   = params.get("endDate");
    const line      = params.get("line");
    const model     = params.get("model");

    if (startDate) document.getElementById("startDate").value = startDate;
    if (endDate)   document.getElementById("endDate").value   = endDate;
    if (line)      document.getElementById("lineFilter").value = line;
    if (model)     document.getElementById("modelFilter").value = model;

    fetchAndRenderLineCharts();
    setInterval(fetchAndRenderLineCharts, 60000);
});

// 🔄 Auto update on any filter change
["startDate", "endDate", "lineFilter", "modelFilter"].forEach(id => {
    document.getElementById(id)?.addEventListener("change", fetchAndRenderLineCharts);
});

