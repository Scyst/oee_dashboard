// --- Helper Functions ---
function getFilterParams() {
    return new URLSearchParams({
        action: 'get_stop',
        startDate: document.getElementById("filterStartDate")?.value,
        endDate: document.getElementById("filterEndDate")?.value,
        line: document.getElementById("filterLine")?.value.trim() || '',
        machine: document.getElementById("filterMachine")?.value.trim() || '',
        cause: document.getElementById("filterCause")?.value.trim() || '',
        page: 1,
        limit: 100000 // A large number to get all records for export
    });
}

function formatDuration(totalMinutes) {
    if (isNaN(totalMinutes) || totalMinutes === null) return '0h 0m';
    const h = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;
    return `${h}h ${m}m`;
}

// --- Main Export Functions ---
async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    if (!doc.autoTable) {
        alert("jsPDF AutoTable plugin not loaded.");
        return;
    }
    
    alert("Preparing PDF export... Please wait.");

    try {
        const response = await fetch(`${API_URL}?${getFilterParams().toString()}`);
        const result = await response.json();

        if (!result.success || result.data.length === 0) {
            alert("Failed to export data or no data found.");
            return;
        }

        const headers = [["Date", "Start", "End", "Duration (m)", "Line", "Machine", "Cause", "Recovered By", "Note"]];
        const rows = result.data.map(row => [
            row.log_date,
            row.stop_begin,
            row.stop_end,
            row.duration,
            row.line,
            row.machine,
            row.cause,
            row.recovered_by,
            row.note || ''
        ]);

        doc.setFontSize(16);
        doc.text("Filtered Stop Cause History", 14, 16);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 20,
            headStyles: { fillColor: [220, 53, 69], halign: 'center' }, // Red theme
            bodyStyles: { halign: 'center' },
            theme: 'striped',
        });

        doc.save(`Stop_Cause_History_${new Date().toISOString().split('T')[0]}.pdf`);
    } catch (error) {
        console.error("PDF Export failed:", error);
        alert("An error occurred during PDF export.");
    }
}

async function exportToExcel() {
    alert("Preparing Excel export... Please wait.");
    
    try {
        const response = await fetch(`${API_URL}?${getFilterParams().toString()}`);
        const result = await response.json();

        if (!result.success || result.data.length === 0) {
            alert("No data to export.");
            return;
        }

        // --- Summary Sheet ---
        const grandTotalRow = { 
            "Line": "Grand Total", 
            "Occurrences": result.summary.reduce((acc, curr) => acc + curr.count, 0),
            "Total Duration": formatDuration(result.grand_total_minutes)
        };
        const summaryData = result.summary.map(row => ({
            "Line": row.line || 'N/A',
            "Occurrences": row.count,
            "Total Duration": formatDuration(row.total_minutes)
        }));
        const summarySheet = XLSX.utils.json_to_sheet([grandTotalRow, ...summaryData]);

        // --- Raw Data Sheet ---
        const rawData = result.data.map(row => ({
            "ID": row.id,
            "Date": row.log_date,
            "Start": row.stop_begin,
            "End": row.stop_end,
            "Duration (min)": row.duration,
            "Line": row.line,
            "Machine/Station": row.machine,
            "Cause": row.cause,
            "Recovered By": row.recovered_by,
            "Note": row.note || ''
        }));
        const rawDataSheet = XLSX.utils.json_to_sheet(rawData);

        // --- Create Workbook ---
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, summarySheet, "Stop Cause Summary");
        XLSX.utils.book_append_sheet(workbook, rawDataSheet, "Raw Data");

        XLSX.writeFile(workbook, `Stop_Cause_History_${new Date().toISOString().split('T')[0]}.xlsx`);

    } catch (error) {
        console.error('Excel Export failed:', error);
        alert('Failed to export data.');
    }
}