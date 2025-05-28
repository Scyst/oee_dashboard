let scrapChart, stopCauseChart;

function fetchBarCharts() {
  const start = document.getElementById("startDate").value;
  const end = document.getElementById("endDate").value;
  const shift = document.getElementById("shift").value;
  const line = document.getElementById("line").value;
  const machine = document.getElementById("machine").value;

  const query = `start=${start}&end=${end}&shift=${shift}&line=${line}&machine=${machine}`;

  fetch(`..\api\get_scrap_by_part.php?${query}`)
    .then(res => res.json())
    .then(data => {
      currentScrapData = data;
      const labels = data.map(item => item.part_no);
      const values = data.map(item => item.total_scrap);
      initScrapBarChart(labels, values);
    });

  fetch(`..\api\get_stop_causes.php?${query}`)
    .then(res => res.json())
    .then(data => {
      const labels = data.map(item => item.cause);
      const values = data.map(item => item.total_time);
      initStopCauseBarChart(labels, values);
    });
}

function initScrapBarChart(labels, data) {
  const ctx = document.getElementById('scrapChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Scrap by Part', data, backgroundColor: 'magenta' }]
    }
  });
}

function initStopCauseBarChart(labels, data) {
  const ctx = document.getElementById('stopCauseChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Stop Causes', data, backgroundColor: 'skyblue' }]
    }
  });
}

function initStopCauseBarChart(labels, values) {
  if (stopCauseChart) stopCauseChart.destroy();

  stopCauseChart = new Chart(document.getElementById("stopCauseBarChart"), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: "Stop Time (mins)",
        data: values,
        backgroundColor: '#ff66cc'
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: "Time (mins)" }
        }
      }
    }
  });
}


setInterval(fetchBarCharts, 60000);