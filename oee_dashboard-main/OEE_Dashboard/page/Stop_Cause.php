<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - STOP CAUSE HISTORY</title>
    <script src="../utils/libs/jspdf.umd.min.js"></script>
    <script src="../utils/libs/jspdf.plugin.autotable.js"></script>
    <script src="../utils/libs/xlsx.full.min.js"></script>

    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/Stop_Cause.css">
</head>

<body style="width: 100vw; height: fit-content; min-width: fit-content;">

    <div style="height: calc(100vh - 20px);">
        <div class="Header">
            <div class="OEE-head">
                <h1>STOPS & CAUSES</h1>
                <!--h2 style="font-size: 2em;">Assembly Line</!--h2-->
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

            
            <div style="display: flex; justify-content: space-between; padding: 2px 10px; margin-top: 5px;">
                
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
                    <!--button onclick="exportToPDF()">Export to PDF</!--button-->
                    <button onclick="openModal('stopModal')">Add</button>
                </div>
            </div>

            <div style="display: flex; height: 50px; align-items: center; max-width: calc(100vw - 40px); min-width: calc(100% - 20px); height: 40px;">
                <!--h3 style="margin-left: 10px; min-width: fit-content;">Stop Cause History</h3-->
                <div id="causeSummary" style="font-weight: bold; white-space: nowrap; overflow-x: auto; margin-left: 15px; border: solid 1px honeydew; padding: 5px 10px;"></div>
            </div>

            <div class="table-wrapper">
                <table id="stopTable" border="1">
                    <thead>
                        <tr>
                            <th style="width: 100px; text-align: center;">ID</th>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
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
    </div>

    <!-- Modal background and form for Part -->
    <div id="stopModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('stopModal')">&times;</span>
            <h2>Add Stop Cause</h2>
            <form id="addStopForm">
                <input type="date" name="log_date" required><br>
                <input type="time" name="stop_begin" step="1" required><br>
                <input type="time" name="stop_end" step="1" required><br>

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
                
                <input list="recoverList" name="recovered_by" placeholder="Recovered By" required>
                <datalist id="recoverList">
                    <?php include '../api/Stop_Cause/get_recovered_by.php'; ?>
                </datalist><br>

                <input type="text" placeholder="Note" name="note"><br>

                <button type="submit">Submit Part</button>
            </form>

        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editStopModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editStopModal')">&times;</span>
            <h2>Edit Stop Cause</h2>
            <form id="editStopForm">
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
                <input list="editCauseList" name="cause" id="edit_cause" placeholder="Stop Cause" required>
                <datalist id="editCauseList">
                    <?php include '../api/Stop_Cause/get_cause.php'; ?>
                </datalist><br>

                <input list="editrecoverList" name="recovered_by" id="edit_recovered_by" placeholder="Recovered By" required>
                    <datalist id="editrecoverList">
                        <?php include '../api/Stop_Cause/get_recovered_by.php'; ?>
                </datalist><br>

                <input type="text" placeholder="Note" name="note" id="edit_note"><br>

                <button type="submit">Update Stop Cause</button>
            </form>
        </div>
    </div>

    <script>
        window.addEventListener("load", () => {
            const now = new Date();

            const dateStr = now.toISOString().split('T')[0];
            const timeStr = now.toTimeString().split(':').slice(0, 3).join(':'); // HH:mm:ss

            document.querySelectorAll('input[type="date"]').forEach(input => input.value ||= dateStr);
            document.querySelectorAll('input[type="time"]').forEach(input => input.value ||= timeStr);

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

            const now = new Date();

            const dateStr = now.toISOString().split('T')[0];

            // Format function: HH:mm:ss
            const formatTime = date => date.toTimeString().split(':').slice(0, 3).join(':');

            const stopBeginStr = formatTime(now);

            // Add 5 minutes for stop_end
            const endTime = new Date(now.getTime() + 5 * 60 * 1000);
            const stopEndStr = formatTime(endTime);

            // Set all date fields inside the modal
            modal.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) input.value = dateStr;
            });

            // Set specific time inputs
            const stopBeginInput = modal.querySelector('input[name="stop_begin"]');
            const stopEndInput = modal.querySelector('input[name="stop_end"]');

            if (stopBeginInput) stopBeginInput.value = stopBeginStr;
            if (stopEndInput) stopEndInput.value = stopEndStr;
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
    <script src="../script/Stop_Cause/export_data.js"></script>
    <script src="../script/Stop_Cause/paginationTable.js"></script>

</body>
</html>

