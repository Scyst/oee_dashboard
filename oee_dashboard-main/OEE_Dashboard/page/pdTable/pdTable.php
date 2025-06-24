<?php 
    include_once("../../auth/check_auth.php"); 

    if (!hasRole(['supervisor', 'admin', 'creator'])) {
        header("Location: ../OEE_Dashboard/OEE_Dashboard.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - PRODUCTION HISTORY</title>
    <script src="../../utils/libs/jspdf.umd.min.js"></script>
    <script src="../../utils/libs/jspdf.plugin.autotable.js"></script>
    <script src="../../utils/libs/xlsx.full.min.js"></script>
    <script src="../../utils/libs/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/dropdown.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/pdTable.css">
</head>

<body style="width: 100vw; height: fit-content; min-width: fit-content;">
    <?php include('../components/nav_dropdown.php'); ?>

    <div style="height: calc(100vh - 20px);">
        <div class="Header">
            <div class="OEE-head">
                <h2>PRODUCTION HISTORY</h2>
                <div style="display: flex; justify-content: center; gap: 5px; align-items: center; margin:0 auto; width: fit-content;">
                    
                    <input list="partNoList" id="filterPartNo" placeholder="Part No.">
                    <datalist id="partNoList"></datalist>

                    <input list="lotList" id="filterLotNo" placeholder="Lot No.">
                    <datalist id="lotList"></datalist>

                    <input list="lineList" id="filterLine" placeholder="Line">
                    <datalist id="lineList"></datalist>

                    <input list="modelList" id="filterModel" placeholder="Model">
                    <datalist id="modelList"></datalist>

                    <select id="filterCountType">
                        <option value="">All Types</option>
                        <option value="FG">FG</option><option value="NG">NG</option><option value="HOLD">HOLD</option>
                        <option value="REWORK">REWORK</option><option value="SCRAP">SCRAP</option><option value="ETC.">ETC.</option>
                    </select>     

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

        <div class="production-history">
            <div class="action-bar"  style="display: flex; justify-content: space-between; padding: 2px 10px; margin-top: 5px;">
                <div id="grandSummary" class="summary-text" style="font-weight: bold;"></div>
                <div>
                    <button onclick="openSummaryModal()">Show Detailed Summary</button>
                    <button onclick="exportToExcel()">Export to Excel</button>
                    <button onclick="openModal('addPartModal')">Add</button>
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
                            <th>Quantity</th>
                            <th style="width: 150px;">Type</th>
                            <th style="width: 250px;">Note</th>
                            <th style="width: 175px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="partTableBody"></tbody>
                </table>
            </div>

            <div class="pagination-container" style="display: flex; gap: 20px; justify-content: center; margin: 10px auto;">
                <button id="prevPageBtn">Previous</button>
                <span id="pagination-info"></span>
                <button id="nextPageBtn">Next</button>
            </div>
        </div>

    </div>

    <div id="toast"></div>

    <?php include('components/addModal.php'); ?>
    <?php include('components/editModal.php'); ?>
    <?php include('components/summaryModal.php'); ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const now = new Date();
            const dateStr = now.toISOString().split('T')[0];
            const savedStart = localStorage.getItem('oee_startDate');
            const savedEnd = localStorage.getItem('oee_endDate');
            const startInput = document.getElementById("filterStartDate");
            const endInput = document.getElementById("filterEndDate");
            if (startInput) startInput.value = savedStart || dateStr;
            if (endInput) endInput.value = savedEnd || dateStr;
        });
    </script>
    
    <script src="../components/auto_logout.js"></script>
    <script src="../components/datetime.js"></script>
    <script src="../components/toast.js"></script>
    <script src="script/paginationTable.js"></script>
    <script src="script/export_data.js"></script>
    <script src="script/modal_handler.js"></script> 
</body>
</html>