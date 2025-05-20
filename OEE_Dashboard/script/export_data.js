let currentScrapData = [];

async function exportToPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  const chartArea = document.getElementById("chart-area");
  const canvas = await html2canvas(chartArea);
  const imgData = canvas.toDataURL("image/png");
  doc.addImage(imgData, "PNG", 10, 10, 190, 0);
  doc.save("OEE_Report.pdf");
}

function exportToCSV() {
  let csv = "Part No,Total Scrap\n";
  currentScrapData.forEach(row => {
    csv += `${row.part_no},${row.total_scrap}\n`;
  });
  const blob = new Blob([csv], { type: "text/csv" });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = "Scrap_Report.csv";
  link.click();
}
