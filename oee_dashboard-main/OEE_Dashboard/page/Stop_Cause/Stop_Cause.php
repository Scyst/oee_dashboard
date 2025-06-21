<?php include_once("../../auth/check_auth.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - STOP CAUSE HISTORY</title>
    <script src="../../utils/libs/xlsx.full.min.js"></script>
    <script src="../../utils/libs/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/dropdown.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/Stop_Cause.css">
</head>

<body>
    <?php include('../components/nav_dropdown.php'); ?>

    <div style="height: calc(100vh - 20px);">
        <div class="Header">
            <div class="OEE-head">
                <h2>STOPS & CAUSES</h2>
                <div style="display: flex; justify-content: center; gap: 5px; align-items: center; margin:0 auto; width: fit-content;">
                    
                    <input list="causeListFilter" id="filterCause" placeholder="Search Stop Cause" />
                    <datalist id="causeListFilter"></datalist>
                    
                    <input list="lineListFilter" id="filterLine" placeholder="Line">
                    <datalist id="lineListFilter"></datalist>
                    
                    <input list="machineListFilter" id="filterMachine" placeholder="Machine/Station">
                    <datalist id="machineListFilter"></datalist>
                    
                    <input type="date" id="filterStartDate">
                    <p style="text-align: center; align-content: center;"> - </p>
                    <input type="date" id="filterEndDate">
                </div>
            </div>
            <div class="assis-tool">
                <p id="date"></p>
                <p id="time"></p>
            </div>
        </div>
        
        <div class="stop-cause">
            <div style="display: flex; justify-content: space-between; padding: 2px 10px; margin-top: 5px; align-items: center;">
                <div id="causeSummary" style="font-weight: bold; white-space: nowrap; overflow-x: auto;"></div>
                <div>
                    <button onclick="exportToExcel()">Export to Excel</button>
                    <button onclick="openModal('addStopModal')">Add</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table id="stopTable" border="1">
                    <thead>
                        <tr>
                            <th>ID</th><th>Date</th><th>Start</th><th>End</th>
                            <th>Duration (m)</th><th>Line</th><th>Machine</th>
                            <th>Cause</th><th>Recovered By</th><th>Note</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stopTableBody"></tbody>
                </table>
            </div>

            <div style="display: flex; gap: 20px; justify-content: center; margin: 10px auto;">
                <button id="prevPageBtn">Previous</button>
                <span id="pagination-info"></span>
                <button id="nextPageBtn">Next</button>
            </div>
        </div>
    </div>

    <?php // include('components/stopCauseComponents/addModal.php'); ?>
    <?php // include('components/stopCauseComponents/editModal.php'); ?>

    <script src="../../page/auto_logout.js"></script>
    <script src="../../page/datetime.js"></script>
    <script src="export_data.js"></script>
    <script src="paginationTable.js"></script>
</body>
</html>