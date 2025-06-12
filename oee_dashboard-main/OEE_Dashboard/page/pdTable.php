<?php include_once("../auth/check_auth.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - PRODUCTION HISTORY</title>
    <script src="../utils/libs/jspdf.umd.min.js"></script>
    <script src="../utils/libs/jspdf.plugin.autotable.js"></script>
    <script src="../utils/libs/xlsx.full.min.js"></script>
    <script src="../utils/libs/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../style/dropdown.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/pdTable.css">
</head>

<body style="width: 100vw; height: fit-content; min-width: fit-content;">
    <?php include('components/nav_dropdown.php'); ?>

    <div style="height: calc(100vh - 20px);">
        <div class="Header">
            <div class="OEE-head">
                <h2>PRODUCTION HISTORY</h2>
                <!-- Filter -->
                <div style="display: flex; justify-content: center; gap: 5px; align-items: center; margin:0 auto; width: fit-content;">
                    <input list="searchlist" id="searchInput" placeholder="Search Part No." oninput="fetchPaginatedParts(1)" />
                    <datalist id="searchlist">
                        <?php include '../api/pdTable/get_part_nos.php'; ?>
                    </datalist><br>

                    <input list="lotList" id="lotInput" placeholder="Lot No." oninput="fetchPaginatedParts(1)">
                    <datalist id="lotList">
                        <?php include '../api/pdTable/get_lot_numbers.php'; ?>
                    </datalist>

                    <input list="lineList" id="lineInput" placeholder="Line" oninput="fetchPaginatedParts(1)">
                    <datalist id="lineList">
                        <?php include '../api/pdTable/get_lines.php'; ?>
                    </datalist>

                    <input list="modelList" id="modelInput" placeholder="Model" oninput="fetchPaginatedParts(1)">
                    <datalist id="modelList">
                        <?php include '../api/pdTable/get_models.php'; ?>
                    </datalist>

                    <select id="status" onchange="fetchPaginatedParts(1)">
                        <option value="">All Types</option>
                        <option value="FG">FG</option>
                        <option value="NG">NG</option>
                        <option value="HOLD">HOLD</option>
                        <option value="REWORK">REWORK</option>
                        <option value="SCRAP">SCRAP</option>
                        <option value="ETC.">ETC.</option>
                    </select>    

                    <input type="date" id="startDate" onchange="applyDateRangeFilter()">
                    <p style="text-align: center; align-content: center;"> - </p>
                    <input type="date" id="endDate" onchange="applyDateRangeFilter()">
                </div>
            </div>

            <div class="assis-tool">
                <p id="date"></p>
                <p id="time"></p>
            </div>
        </div>

        <div class="production-history">

            <div style="display: flex; justify-content: space-between; padding: 2px 10px; margin-top: 5px;">
                <div style="display: flex; justify-content: space-between; padding: 2px 10px;">
                    <div style="display: flex; align-items: center; margin-left: 5px;">
                        <div id="grandSummary" style="font-weight: bold;"></div>
                    </div>    
                </div>
                
                <div>
                    <button onclick="openSummaryModal()">Show Detailed Summary</button>
                    <button onclick="exportToExcel()">Export to Excel</button>
                    <button onclick="openModal('partModal')">Add</button>
                </div>

                <div id="partSummaryWrapper" style="display: none;">
                    <div id="grandSummary" style="font-weight: bold;"></div>
                    <div id="partSummary" style="overflow-x: auto;"></div>
                </div>
            </div>

            <div class="table-wrapper">
                <table id="partTable" border="1">
                    <thead>
                        <tr>
                            <th style="width: 100px; text-align: center;">ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Line</th>
                            <th>Model</th>
                            <th>Part No.</th>
                            <th>Lot No.</th>
                            <th>Quatity</th>
                            <th style="width: 150px;">Type</th>
                            <th style="width: 250px;">Note</th>                        
                            <th style="width: 175px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="partTableBody"></tbody>
                </table>
            </div>

            <div style="display: flex; gap: 20px; justify-content: center; margin: 10px auto;">
                <button id="prevPageBtn" style="width: fit-content; margin: 0;">Previous</button>
                <span id="pagination-info" style="text-align: center; align-content: center;"></span>
                <button id="nextPageBtn" style="width: fit-content; margin: 0;">Next</button>
            </div>

        </div>

        <?php include('components/pdComponents/addModal.php'); ?>
        <?php include('components/pdComponents/editModal.php'); ?>
        <?php include('components/pdComponents/summaryModal.php'); ?>

    </div>

    <script>
        window.addEventListener("load", () => {
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const timeStr = now.toTimeString().split(':').slice(0, 2).join(':');

            // Try to load saved values from localStorage
            const savedStart = localStorage.getItem('oee_startDate');
            const savedEnd = localStorage.getItem('oee_endDate');

            const startInput = document.getElementById("startDate");
            const endInput = document.getElementById("endDate");

            // Set saved values if found, otherwise set default (today)
            if (startInput) startInput.value = savedStart || dateStr;
            if (endInput) endInput.value = savedEnd || dateStr;

            // Set default time fields (if needed elsewhere)
            document.querySelectorAll('input[type="time"]').forEach(input => {
                if (!input.value) input.value = timeStr;
            });
        });

        function applyDateRangeFilter() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;

            localStorage.setItem('oee_startDate', start);
            localStorage.setItem('oee_endDate', end);

            fetchPaginatedParts(1);
        }
    </script>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = "block";

            // Set current date and time every time modal opens
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const timeStr = now.toTimeString().split(':').slice(0, 2).join(':');

            const dateInput = modal.querySelector('input[type="date"]');
            const timeInput = modal.querySelector('input[type="time"]');

            if (dateInput) dateInput.value = dateStr;
            if (timeInput) timeInput.value = timeStr;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // close modal if clicking outside the content
        window.onclick = function (event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        }
    </script>

    <script src="../script/datetime.js"></script>
    <script src="../script/auto_logout.js"></script>
    <script src="../script/pdTable/export_data.js"></script>
    <script src="../script/pdTable/paginationTable.js"></script>

</body>
</html>

