
let lastKnownUpdate = null;

function updateLastSeen(timeStr) {
  document.getElementById("last-update").textContent = timeStr;
}

function checkForDbChange() {
  fetch("/api/get_last_update.php")
    .then(res => res.json())
    .then(data => {
      if (data.last_update !== lastKnownUpdate) {
        lastKnownUpdate = data.last_update;
        updateLastSeen(data.last_update);
        fetchOeeSummary();
      }
    });
}

// Check every minute
setInterval(checkForDbChange, 60000); // 60,000 ms = 1 min