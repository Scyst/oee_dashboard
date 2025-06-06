// ---- Utility: Populate dropdown from API ----
async function populateDropdown(id, url, selectedValue = "") {
    const select = document.getElementById(id);
    if (!select) return;

    try {
        const res = await fetch(url);
        const data = await res.json();

        const label = id === "lineFilter" ? "Lines" : "Models";
        select.innerHTML = `<option value="">All ${label}</option>`;

        data.forEach(option => {
            const opt = document.createElement("option");
            opt.value = option;
            opt.textContent = option;
            if (option === selectedValue) opt.selected = true;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error(`Failed to populate ${id}:`, err);
    }
}

// ---- Sync filters from URL → UI, and trigger chart rendering ----
async function applyFiltersAndInitCharts() {
    const params = new URLSearchParams(window.location.search);
    const startDate = params.get("startDate");
    const endDate = params.get("endDate");
    const line = params.get("line");
    const model = params.get("model");

    // Populate dropdowns first
    await Promise.all([
        populateDropdown("lineFilter", "../api/OEE_Dashboard/get_lines.php", line),
        populateDropdown("modelFilter", "../api/OEE_Dashboard/get_models.php", model)
    ]);

    // Set date inputs
    if (startDate) document.getElementById("startDate").value = startDate;
    if (endDate)   document.getElementById("endDate").value   = endDate;

    // Initial chart rendering
    fetchAndRenderCharts?.();
    fetchAndRenderLineCharts?.();
    fetchAndRenderBarCharts?.();

    // Optional auto-refresh every 60 seconds
    // setInterval(() => {
    //     fetchAndRenderCharts?.();
    //     fetchAndRenderLineCharts?.();
    //     fetchAndRenderBarCharts?.();
    // }, 60000);
}

// ---- On filter change: update URL and refresh charts ----
function handleFilterChange() {
    const startDate = document.getElementById("startDate")?.value || '';
    const endDate   = document.getElementById("endDate")?.value || '';
    const line      = document.getElementById("lineFilter")?.value || '';
    const model     = document.getElementById("modelFilter")?.value || '';

    const params = new URLSearchParams({ startDate, endDate, line, model });
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, '', newUrl);

    fetchAndRenderCharts?.();
    fetchAndRenderLineCharts?.();
    fetchAndRenderBarCharts?.();
}

// ---- Fallback to today’s date if empty ----
function ensureDefaultDateInputs() {
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().split(':').slice(0, 2).join(':');

    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (!input.value) input.value = dateStr;
    });

    document.querySelectorAll('input[type="time"]').forEach(input => {
        if (!input.value) input.value = timeStr;
    });
}

// ---- Entry point ----
window.addEventListener("load", () => {
    ensureDefaultDateInputs();

    // Add event listeners AFTER page is loaded
    ["startDate", "endDate", "lineFilter", "modelFilter"].forEach(id => {
        document.getElementById(id)?.addEventListener("change", handleFilterChange);
    });

    applyFiltersAndInitCharts(); // initial load
});
