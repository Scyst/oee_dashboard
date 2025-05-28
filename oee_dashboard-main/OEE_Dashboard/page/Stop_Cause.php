<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE API Test Form</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/jspdf-thai@1.0.0/thsarabunnew.js"></script>

    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/Stop_Cause.css">
</head>

<body style="width: 100vw; height: fit-content; min-width: fit-content;">

    <div style="height: calc(100vh - 20px);">
        <div class="Header">
            <div class="OEE-head">
                <h1 style="font-size: 2.5em;">Overall Equipment Effectiveness</h1>
                <h2 style="font-size: 2em;">Assembly Line</h2>
            </div>
            <div class="assis-tool">
                <p id="date"></p>
                <p id="time"></p>

                <div class="tool-buttons">
                    <a href="OEE_Dashboard.php">
                        <button>
                            <img src="../icons/reports-icon.png" alt="Save">
                        </button>
                    </a>
                    <a href="pdTable.php">
                        <button>
                            <img src="../icons/db.png" alt="Database">
                        </button>
                    </a>
                    <a href="Stop_Cause.php">
                        <button>
                            <img src="../icons/clipart2496353.png" alt="Settings">
                        </button>
                    </a>
                </div>
            </div>
        </div>

        <div class="stop-cause">

            <h3 style="margin-left: 10px">Stop Cause History</h3>
            <div style="display: flex; justify-content: space-between; padding: 0px 10px;">

                <!-- Filter -->
                <div style="display: flex; gap: 5px; justify-content: center;">
                    <input list="searchlist" id="searchInput" placeholder="Search Stop Cause" oninput="fetchPaginatedParts(1)" />
                    <datalist id="searchlist">
                        <?php include '../api/Stop_Cause/get_cause.php'; ?>
                    </datalist><br>

                    <input list="lineList" id="lineInput" placeholder="Line" oninput="fetchPaginatedParts(1)">
                    <datalist id="lineList">
                        <?php include '../api/Stop_Cause/get_lines.php'; ?>
                    </datalist>

                    <input list="machineList" id="machineInput" placeholder="Machine/Station" oninput="fetchPaginatedParts(1)">
                    <datalist id="machineList">
                        <?php include '../api/Stop_Cause/get_machine.php'; ?>
                    </datalist>   
                    
                    <input type="date" id="startDate" onchange="applyDateRangeFilter()">
                    <p style="text-align: center; align-content: center;"> - </p>
                    <input type="date" id="endDate" onchange="applyDateRangeFilter()">

                </div>

                <div>
                    <button onclick="exportToExcel()">Export to Excel</button>
                    <button onclick="exportToPDF()">Export to PDF</button>
                    <button onclick="openModal('stopModal')">Add</button>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="stopTable" border="1">
                    <thead>
                        <tr>
                            <th style="width: 100px; text-align: center;">ID</th>
                            <th>Date</th>
                            <th>Stop Began</th>
                            <th>Stop End</th>
                            <th>Line</th>
                            <th>Machine/Station</th>
                            <th>Cause</th>
                            <th>Recovered By</th>
                            <th style="width: 250px;">Note</th>                        
                            <th style="width: 175px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stopTableBody">
                    </tbody>
                </table>
            </div>

            <div style="display: flex; gap: 20px; justify-content: center; margin: 10px auto;">
                <button id="prevPageBtn" style="width: fit-content; margin: 0;">Previous</button>
                <span id="pagination-info" style="text-align: center; align-content: center;"></span>
                <button id="nextPageBtn" style="width: fit-content; margin: 0;">Next</button>

            </div>

        </div>

            <!-- Modal background and form for Part -->
        <div id="stopModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('stopModal')">&times;</span>
                <h2>Add Part</h2>
                <form id="addStopForm">
                    <input type="date" name="log_date" required value="<?= date('Y-m-d') ?>"><br>
                    <input type="time" name="stop_begin" step="1" required value="<?= date('H:i:s') ?>"><br>
                    <input type="time" name="stop_end" step="1" required value="<?= date('H:i:s') ?>"><br>

                    <!-- Line input with datalist -->
                    <input list="lineList" name="line" placeholder="Line" required>
                    <datalist id="lineList">
                        <?php include '../api/Stop_Cause/get_lines.php'; ?>
                    </datalist><br>

                    <!-- Model input with datalist -->
                    <input list="machineList" name="machine" placeholder="Machine/Station" required>
                    <datalist id="machineList">
                        <?php include '../api/Stop_Cause/get_machine.php'; ?>
                    </datalist><br>

                    <!-- Part No. input with datalist -->
                    <input list="causeList" name="cause" placeholder="Stop Cause" required>
                    <datalist id="causeList">
                        <?php include '../api/Stop_Cause/get_cause.php'; ?>
                    </datalist><br>
                    
                    <input type="text" placeholder="Note" name="note"><br>

                    <input list="recoverList" name="recovered_by" placeholder="Recovered By" required>
                    <datalist id="recoverList">
                        <?php include '../api/Stop_Cause/get_recovered_by.php'; ?>
                    </datalist><br>

                    <button type="submit">Submit Part</button>
                </form>

            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editStopModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editStopModal')">&times;</span>
            <h2>Edit Part</h2>
            <form id="editPartForm">
                <input type="hidden" name="id" id="edit_id">

                <input type="date" name="log_date" id="edit_date" required><br>
                
                <input type="time" name="stop_begin" id="edit_stopBegin" step="1" required><br>

                <input type="time" name="stop_end" id="edit_stopEnd" step="1" required><br>

                <!-- Line (datalist or text input) -->
                <input list="editLineList" name="line" id="edit_line" placeholder="Line" required>
                <datalist id="editLineList">
                    <?php include '../api/Stop_Cause/get_lines.php'; ?>
                </datalist><br>

                <!-- Model -->
                <input list="editMachineList" name="machine" id="edit_machine" placeholder="Machine/Station" required>
                <datalist id="editMachineList">
                    <?php include '../api/Stop_Cause/get_machine.php'; ?>
                </datalist><br>

                <!-- Part No -->
                <input list="editPartList" name="part_no" id="edit_part_no" placeholder="Part No." required>
                <datalist id="editPartList">
                    <?php include '../api/Stop_Cause/get_part_nos.php'; ?>
                </datalist><br>

                <!-- Count Value -->
                <input type="number" name="count_value" id="edit_value" placeholder="Quantity" required><br>

                <input type="text" placeholder="Note" name="note" id="edit_note"><br>

                <input list="editrecoverList" name="recovered_by" id="edit_recovered_by" placeholder="Recovered By" required>
                    <datalist id="editrecoverList">
                        <?php include '../api/Stop_Cause/get_recovered_by.php'; ?>
                </datalist><br>

                <button type="submit">Update Part</button>
            </form>
        </div>
    </div>

    <script>
        window.addEventListener("load", () => {
            const now = new Date();

            // Format as yyyy-mm-dd
            const dateStr = now.toISOString().split('T')[0];

            // Format time as hh:mm
            const timeStr = now.toTimeString().split(':').slice(0, 2).join(':');

            // Set all date and time fields
            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) input.value = dateStr;
            });

            document.querySelectorAll('input[type="time"]').forEach(input => {
                if (!input.value) input.value = timeStr;
            });

            // Special handling: set only startDate and endDate filter defaults to today
            const startInput = document.getElementById("startDate");
            const endInput = document.getElementById("endDate");

            if (startInput && !startInput.value) startInput.value = dateStr;
            if (endInput && !endInput.value) endInput.value = dateStr;
        });
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

        // Optional: close modal if clicking outside the content
        window.onclick = function (event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        }
    </script>

    <script src="../script/datetime.js"></script>
    <script src="../script/pdTable/export_data.js"></script>
    <script src="../script/pdTable/pdTable.js"></script>
    <script src="../script/pdTable/paginationTable.js"></script>

</body>
</html>

