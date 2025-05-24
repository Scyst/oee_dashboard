async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Ensure autoTable is defined
    if (!doc.autoTable) {
        alert("jsPDF AutoTable plugin not loaded.");
        return;
    }

    const headers = [["Date", "Time", "Type", "Part No.", "Value"]];

    const rows = Array.from(document.querySelectorAll("#partTable tbody tr"))
        .filter(row => row.style.display !== "none")
        .map(row => [
            row.children[1].innerText,
            row.children[2].innerText,
            row.children[7].innerText,
            row.children[5].innerText,
            row.children[6].innerText
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

    doc.save("Filtered_Production_History.pdf");
}


function exportToExcel() {
    const table = document.getElementById("partTable");

    // Get visible rows
    const rows = Array.from(table.querySelectorAll("tbody tr"))
        .filter(row => row.style.display !== "none");

    // Get headers excluding "Actions"
    const headers = Array.from(table.querySelectorAll("thead th"))
        .slice(0, -1)
        .map(th => th.innerText);

    // Extract visible data rows
    const data = rows.map(row =>
        Array.from(row.cells)
            .slice(0, -1)
            .map(cell => cell.innerText)
    );

    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...data]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "FilteredData");

    XLSX.writeFile(workbook, "Filtered_Production_History.xlsx");
}
