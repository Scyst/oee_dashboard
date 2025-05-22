async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Table headers
    const headers = [["Date", "Time", "Type", "Part No.", "Value"]];

    // Filtered visible rows
    const rows = Array.from(document.querySelectorAll("#partTable tbody tr"))
        .filter(row => row.style.display !== "none")
        .map(row => Array.from(row.cells).slice(0, 5).map(cell => cell.innerText));

    // Title
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

    doc.save("Filtered_Production_History.pdf");
}


function exportToExcel() {
    const table = document.getElementById("partTable");
    const rows = Array.from(table.querySelectorAll("tbody tr"))
        .filter(row => row.style.display !== "none")
        .map(row => Array.from(row.cells).slice(0, 5).map(cell => cell.innerText)); // Only first 5 columns (skip Actions)

    const headers = ["Date", "Time", "Type", "Part No.", "Value"];
    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "FilteredData");

    XLSX.writeFile(workbook, "Filtered_Production_History.xlsx");
}
