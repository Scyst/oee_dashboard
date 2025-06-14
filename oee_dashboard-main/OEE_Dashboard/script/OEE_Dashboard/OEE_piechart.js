// OEE_piechart.js (Improved Version)

// เก็บ reference ของ chart เพื่อการอัปเดต
const charts = {
    oee: null,
    quality: null,
    performance: null,
    availability: null
};

/**
 * ซ่อนข้อความ Error ทั้งหมดและทำให้ chart แสดงผล
 */
function hideErrors() {
    ['oee', 'quality', 'performance', 'availability'].forEach(type => {
        document.getElementById(`${type}Error`).style.display = 'none';
        document.getElementById(`${type}PieChart`).style.opacity = '1';
    });
}

/**
 * แสดงข้อความ Error สำหรับ chart ที่ระบุ
 */
function showError(type) {
    document.getElementById(`${type}Error`).style.display = 'block';
    document.getElementById(`${type}PieChart`).style.opacity = '0.2';
}

/**
 * อัปเดตกล่องข้อมูลด้วยเนื้อหาที่กำหนด
 * @param {string} elementId - ID ของ element ที่จะอัปเดต
 * @param {string[]} lines - Array ของข้อความที่จะแสดงผล
 */
function updateInfoBox(elementId, lines) {
    const content = lines.map(line => `<span>${line}</span>`).join('<br>');
    document.getElementById(elementId).innerHTML = `<small>${content}</small>`;
}

/**
 * ฟังก์ชันสำหรับ Render หรือ Update Pie Chart
 */
function renderSimplePieChart(chartName, ctx, label, rawValue, mainColor) {
    if (charts[chartName]) {
        charts[chartName].destroy();
    }

    const value = Math.max(0, Math.min(rawValue, 100)); // Clamp value between 0 and 100
    const loss = Math.max(0, 100 - value);;

    charts[chartName] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [label, 'Loss'],
            datasets: [{
                data: [value, loss],
                backgroundColor: [mainColor, '#424242'],
                cutout: '80%',
                borderWidth: 0.5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                const { width, height, ctx } = chart;
                ctx.restore();
                const fontSize = (height / 150).toFixed(2);
                ctx.font = `bold ${fontSize}em sans-serif`;
                ctx.textBaseline = "middle";
                const text = `${rawValue.toFixed(1)}%`;
                const textX = Math.round((width - ctx.measureText(text).width) / 2);
                const textY = height / 2; // Adjust vertical position
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

        // Update URL
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newUrl);

        const response = await fetch(`../api/OEE_Dashboard/get_oee_piechart.php?${params.toString()}`);
        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);

        const data = await response.json();
        if (!data.success) throw new Error(data.message || "API returned an error.");

        // --- Render Charts ---
        renderSimplePieChart('oee', document.getElementById("oeePieChart").getContext("2d"), 'OEE', data.oee || 0, '#00BF63');
        renderSimplePieChart('quality', document.getElementById("qualityPieChart").getContext("2d"), 'Quality', data.quality || 0, '#ab47bc');
        renderSimplePieChart('performance', document.getElementById("performancePieChart").getContext("2d"), 'Performance', data.performance || 0, '#ffa726');
        renderSimplePieChart('availability', document.getElementById("availabilityPieChart").getContext("2d"), 'Availability', data.availability || 0, '#42a5f5');

        // --- Update Info Boxes ---
        // Ensuring these values are numbers before calling toLocaleString()
        updateInfoBox("oeeInfo", [ // Assuming "oeeInfo" is the target ID for overall OEE stats
            `OEE : <b>${(parseFloat(data.oee) || 0).toLocaleString()}</b> %`, // Changed pcs to % for OEE
            `Quality : <b>${(parseFloat(data.quality) || 0).toLocaleString()}</b> %`, // Changed pcs to % for Quality
            `Performance : <b>${(parseFloat(data.performance) || 0).toLocaleString()}</b> %`, // Changed pcs to % for Performance
            `Availability : <b>${(parseFloat(data.availability) || 0).toLocaleString()}</b> %` // Changed pcs to % for Availability
        ]);

        const { oee, quality, performance, availability } = data; // Destructure after the initial info box update for consistency

        // ป้องกันการหารด้วยศูนย์ หากไม่มี loss เลย
        if ((oee || 0) < 100) { // Added (oee || 0) to handle null/undefined oee
            const totalLoss = 100 - (oee || 0);

            // คำนวณ Loss ของแต่ละตัวแปร (A, P, Q)
            // นี่เป็นหนึ่งในวิธีการคำนวณเพื่อแจกแจงสัดส่วน Loss ซึ่งเป็นวิธีที่เข้าใจง่าย
            const qualityLossRatio = 1 - ((quality || 0) / 100);
            const performanceLossRatio = 1 - ((performance || 0) / 100);
            const availabilityLossRatio = 1 - ((availability || 0) / 100);

            const totalRatio = qualityLossRatio + performanceLossRatio + availabilityLossRatio;

            // ป้องกันการหารด้วยศูนย์อีกชั้น
            const qualityLoss = totalRatio > 0 ? totalLoss * (qualityLossRatio / totalRatio) : 0;
            const performanceLoss = totalRatio > 0 ? totalLoss * (performanceLossRatio / totalRatio) : 0;
            const availabilityLoss = totalRatio > 0 ? totalLoss * (availabilityLossRatio / totalRatio) : 0;

            // This block updates the 'oeeInfo' box with loss details if OEE is not 100.
            // If you want to show *both* the main OEE stats and the loss breakdown simultaneously,
            // you might need a separate div for the loss breakdown, or a more dynamic update of 'oeeInfo'.
            // For now, it will overwrite the previous content of 'oeeInfo'.
            updateInfoBox("oeeInfo", [
                `Total OEE Loss: <b>${totalLoss.toFixed(1)}%</b>`, // Added total OEE loss for clarity
                `Q Loss: <b>${qualityLoss.toFixed(1)}%</b>`,
                `P Loss: <b>${performanceLoss.toFixed(1)}%</b>`,
                `A Loss: <b>${availabilityLoss.toFixed(1)}%</b>`
            ]);

        } else {
            // กรณีที่ OEE 100% หรือมากกว่า
            updateInfoBox("oeeInfo", [
                `Total OEE Loss: <b>0.0%</b>`
            ]);
        }

        updateInfoBox("qualityInfo", [
            `FG : <b>${(parseFloat(data.fg) || 0).toLocaleString()}</b> pcs`,
            `Defects : <b>${(parseFloat(data.defects) || 0).toLocaleString()}</b> pcs`
        ]);

        updateInfoBox("performanceInfo", [
            `Actual : <b>${(parseFloat(data.actual_output) || 0).toLocaleString()}</b> pcs`,
            `Theo. Time : <b>${formatMinutes(data.debug_info?.total_theoretical_minutes || 0)}</b>`
        ]);

        updateInfoBox("availabilityInfo", [
            `Planned : <b>${formatMinutes(data.planned_time || 0)}</b>`,
            `Downtime : <b>${formatMinutes(data.downtime || 0)}</b>`,
            `Runtime : <b>${formatMinutes(data.runtime || 0)}</b>`
        ]);

        // You would need to add this formatMinutes function if it's not globally available
        function formatMinutes(minutes) {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return `${h}h ${m}m`;
        }

    } catch (err) {
        console.error("Pie chart update failed:", err);
        // Show error messages on the UI
        ['oee', 'quality', 'performance', 'availability'].forEach(showError);
    }
}