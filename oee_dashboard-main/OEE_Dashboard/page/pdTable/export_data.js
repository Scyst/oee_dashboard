async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    if (!doc.autoTable) {
        alert("jsPDF AutoTable plugin not loaded.");
        return;
    }

    const params = new URLSearchParams({
        action: 'get_parts',
        startDate: document.getElementById("filterStartDate").value,
        endDate: document.getElementById("filterEndDate").value,
        line: document.getElementById("filterLine")?.value.trim() || '',
        model: document.getElementById("filterModel")?.value.trim() || '',
        part_no: document.getElementById("filterPartNo")?.value.trim() || '',
        lot_no: document.getElementById("filterLotNo")?.value.trim() || '',
        count_type: document.getElementById("filterCountType")?.value.trim() || '',
        page: 1,
        limit: 100000
    });

    // --- การเปลี่ยนแปลง ---
    const response = await fetch(`../api/pdTableManage.php?${params.toString()}`);
    const result = await response.json();

    if (!result.success || result.data.length === 0) {
        alert("Failed to export data or no data found.");
        return;
    }

    const headers = [["Date", "Time", "Line", "Model", "Part No.", "Lot No.", "Quantity", "Type"]];
    const rows = result.data.map(row => [
        row.log_date,
        row.log_time,
        row.line,
        row.model,
        row.part_no,
        row.lot_no,
        row.count_value,
        row.count_type,
    ]);

    doc.setFontSize(16);
    doc.text("Filtered Production History", 14, 16);

    doc.autoTable({
        head: headers,
        body: rows,
        startY: 20,
        headStyles: { fillColor: [0, 123, 255], halign: 'center' },
        bodyStyles: { halign: 'center' },
        theme: 'striped',
    });

    doc.save("Production_History.pdf");
}

async function exportToExcel() {
    const params = new URLSearchParams({
        action: 'get_parts',
        startDate: document.getElementById("filterStartDate").value,
        endDate: document.getElementById("filterEndDate").value,
        line: document.getElementById("filterLine")?.value.trim() || '',
        model: document.getElementById("filterModel")?.value.trim() || '',
        part_no: document.getElementById("filterPartNo")?.value.trim() || '',
        lot_no: document.getElementById("filterLotNo")?.value.trim() || '',
        count_type: document.getElementById("filterCountType")?.value.trim() || '',
        page: 1,
        limit: 100000
    });
    
    alert('Exporting data... Please wait.');

    try {
        // --- การเปลี่ยนแปลง ---
        const response = await fetch(`../api/pdTableManage.php?${params.toString()}`);
        const result = await response.json();

        if (!result.success || result.data.length === 0) {
            alert("No data to export.");
            return;
        }

        const dataToExport = result.data.map(row => ({
            ID: row.id,
            Date: row.log_date,
            Time: row.log_time,
            Line: row.line,
            Model: row.model,
            'Part No': row.part_no,
            'Lot No': row.lot_no,
            Quantity: row.count_value,
            Type: row.count_type,
            Note: row.note
        }));

        const summaryData = result.summary.map(row => ({
            Model: row.model,
            'Part No': row.part_no,
            'Lot No': row.lot_no || '',
            FG: row.FG || 0,
            NG: row.NG || 0,
            HOLD: row.HOLD || 0,
            REWORK: row.REWORK || 0,
            SCRAP: row.SCRAP || 0,
            ETC: row.ETC || 0
        }));

        const grandTotal = [{
            " ": "Grand Total",
            FG: result.grand_total.FG || 0,
            NG: result.grand_total.NG || 0,
            HOLD: result.grand_total.HOLD || 0,
            REWORK: result.grand_total.REWORK || 0,
            SCRAP: result.grand_total.SCRAP || 0,
            ETC: result.grand_total.ETC || 0
        }];

        const ws1 = XLSX.utils.json_to_sheet(dataToExport);
        const ws2 = XLSX.utils.json_to_sheet(summaryData);
        const ws3 = XLSX.utils.json_to_sheet(grandTotal);

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws1, "Raw Data");
        XLSX.utils.book_append_sheet(wb, ws2, "Summary by Lot");
        XLSX.utils.book_append_sheet(wb, ws3, "Grand Total");
        
        const fileName = `Production_History_${new Date().toISOString().split('T')[0]}.xlsx`;
        XLSX.writeFile(wb, fileName);

    } catch (error) {
        console.error('Export to Excel failed:', error);
        alert('Failed to export data. Please check the console for errors.');
    }
}