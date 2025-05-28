let oeePie, qualityPie, performancePie, availabilityPie;
let oeeLine;

function initPieCharts(data) {
  if (oeePie) oeePie.destroy(); // If already exists
  oeePie = new Chart(document.getElementById("oeePieChart"), {
    type: 'doughnut',
    data: {
      labels: ['OEE', 'Loss'],
      datasets: [{
        data: [data.oee, 100 - data.oee],
        backgroundColor: ['#00FF99', '#FF3366']
      }]
    },
    options: { cutout: '70%' }
  });

  qualityPie?.destroy();
  qualityPie = new Chart(document.getElementById("qualityPieChart"), {
    type: 'doughnut',
    data: {
      labels: ['Good', 'Reject'],
      datasets: [{
        data: [data.quality, 100 - data.quality],
        backgroundColor: ['#00FF99', '#FF3366']
      }]
    },
    options: { cutout: '70%' }
  });

  performancePie?.destroy();
  performancePie = new Chart(document.getElementById("performancePieChart"), {
    type: 'doughnut',
    data: {
      labels: ['Actual', 'Loss'],
      datasets: [{
        data: [data.performance, 100 - data.performance],
        backgroundColor: ['#00FF99', '#FF3366']
      }]
    },
    options: { cutout: '70%' }
  });

  availabilityPie?.destroy();
  availabilityPie = new Chart(document.getElementById("availabilityPieChart"), {
    type: 'doughnut',
    data: {
      labels: ['Run Time', 'Stop Time'],
      datasets: [{
        data: [data.availability, 100 - data.availability],
        backgroundColor: ['#00FF99', '#FF3366']
      }]
    },
    options: { cutout: '70%' }
  });
}

function initOeeLineChart(labels, values) {
  if (oeeLine) oeeLine.destroy();
  oeeLine = new Chart(document.getElementById("oeeLineChart"), {
    type: 'line',
    data: {
      labels: labels, // ["10/05", "11/05", ...]
      datasets: [{
        label: "OEE %",
        data: values,
        fill: false,
        borderColor: '#00FFFF',
        tension: 0.1
      }]
    },
    options: {
      scales: {
        y: { beginAtZero: true, max: 100 }
      }
    }
  });
}

function fetchOeeSummary() {
  const date = new Date().toISOString().split("T")[0];
  const shift = "A";

  fetch(`../api/get_oee_summary.php?log_date=${date}&shift=${shift}`)
    .then(res => res.json())
    .then(data => {
      initPieCharts(data);

      // You can also fetch 7-day line chart data here
      fetch("../api/get_oee_trend.php")
        .then(res => res.json())
        .then(trend => {
          const labels = trend.map(row => row.log_date);
          const values = trend.map(row => row.oee);
          initOeeLineChart(labels, values);
        });
    });
}

setInterval(fetchOeeSummary, 60000);
window.addEventListener("load", () => {
    fetchOeeSummary();
    //setInterval(fetchAndRenderLineCharts, 60000);
});

//window.onload = fetchOeeSummary;