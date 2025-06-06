async function applyFiltersAndInitCharts() {
    const params = new URLSearchParams(window.location.search);
    const startDate = params.get("startDate");
    const endDate = params.get("endDate");
    const line = params.get("line");
    const model = params.get("model");

    // Populate dropdowns with selected value
    await Promise.all([
        populateDropdown("lineFilter", "../api/OEE_Dashboard/get_lines.php", line),
        populateDropdown("modelFilter", "../api/OEE_Dashboard/get_models.php", model)
    ]);

    // Set start/end dates
    if (startDate) document.getElementById("startDate").value = startDate;
    if (endDate) document.getElementById("endDate").value = endDate;

    // Initial chart render
    fetchAndRenderCharts?.();
    fetchAndRenderLineCharts?.();
    fetchAndRenderBarCharts?.();

    /*setInterval(() => {
        fetchAndRenderCharts?.();
        fetchAndRenderLineCharts?.();
        fetchAndRenderBarCharts?.();
    }, 60000); // Optional auto-refresh*/
}

function handleFilterChange() {
    const startDate = document.getElementById("startDate")?.value || '';
    const endDate = document.getElementById("endDate")?.value || '';
    const line = document.getElementById("lineFilter")?.value || '';
    const model = document.getElementById("modelFilter")?.value || '';

    const params = new URLSearchParams({ startDate, endDate, line, model });
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, '', newUrl);

    fetchAndRenderCharts?.();
    fetchAndRenderLineCharts?.();
    fetchAndRenderBarCharts?.();
}

// Attach events globally
["startDate", "endDate", "lineFilter", "modelFilter"].forEach(id => {
    document.getElementById(id)?.addEventListener("change", handleFilterChange);
});

// Support script dependency
async function populateDropdown(id, url, selectedValue = "") {
    const select = document.getElementById(id);
    if (!select) return;

    const res = await fetch(url);
    const data = await res.json();

    // Reset current options
    select.innerHTML = `<option value="">All ${id === "lineFilter" ? "Lines" : "Models"}</option>`;
    data.forEach(option => {
        const opt = document.createElement("option");
        opt.value = option;
        opt.textContent = option;
        if (option === selectedValue) opt.selected = true;
        select.appendChild(opt);
    });
}

// Call when page loads
window.addEventListener("load", applyFiltersAndInitCharts);
window.addEventListener("load", () => {
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().split(':').slice(0, 2).join(':');

    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (!input.value) input.value = dateStr;
    });

    document.querySelectorAll('input[type="time"]').forEach(input => {
        if (!input.value) input.value = timeStr;
    });

    const startInput = document.getElementById("startDate");
    const endInput = document.getElementById("endDate");

    if (startInput && !startInput.value) startInput.value = dateStr;
    if (endInput && !endInput.value) endInput.value = dateStr;
});
