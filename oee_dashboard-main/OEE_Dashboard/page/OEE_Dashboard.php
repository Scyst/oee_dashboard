<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE API Test Form</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/jspdf-thai@1.0.0/thsarabunnew.js"></script>

    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/piechart.css">
    <link rel="stylesheet" href="../style/linechart.css">
    <link rel="stylesheet" href="../style/barchart.css">
</head>

<body>
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

        <div class="oee-piechart">
            <div style="display: block;width: 100%">
                <div class="pie-chart">
                    <fieldset>
                        <h4>OEE</h4>
                        <div class="align_legend">
                            <div id="customLegend" class="legend-container">
                                <div class="legend-item"><span style="background: #00BF63"></span>OEE</div>
                                <div class="legend-item"><span style="background: #65A6FA"></span>Man</div>
                                <div class="legend-item"><span style="background: #BB109D"></span>Machine</div>
                                <div class="legend-item"><span style="background: #FF914D"></span>Method</div>
                                <div class="legend-item"><span style="background: #FFDE59"></span>Material</div>
                                <div class="legend-item"><span style="background: #D9D9D9"></span>Other</div>
                            </div>
                            <div class="piechart-wrapper">
                                <canvas id="oeePieChart"></canvas>
                                <div class="error-message" id="oeeError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <h4>Quality</h4>
                        <div class="align_legend">
                            <div id="customLegend" class="legend-container">
                                <div class="legend-item"><span style="background: #00BF63"></span>Quality</div>
                            </div>
                            <div class="piechart-wrapper">
                                <canvas id="qualityPieChart"></canvas>
                                <div class="error-message" id="qualityError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <h4>Performance</h4>
                        <div class="align_legend">
                            <div id="customLegend" class="legend-container">
                                <div class="legend-item"><span style="background: #00BF63"></span>Performance</div>
                            </div>
                            <div class="piechart-wrapper">
                                <canvas id="performancePieChart"></canvas>
                                <div class="error-message" id="performanceError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <h4>Availability</h4>
                        <div class="align_legend">
                            <div id="customLegend" class="legend-container">
                                <div class="legend-item"><span style="background: #00BF63"></span>Availability</div>
                            </div>
                            <div class="piechart-wrapper">
                                <canvas id="availabilityPieChart"></canvas>
                                <div class="error-message" id="availabilityError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>  
        </div>

        <div class="oee-linechart">
            <div style="display: block;width: 100%">
                <div class="line-chart">
                    <fieldset>
                        <h4>OEE</h4>
                        <div class="linechart-wrapper">
                            <canvas id="oeeLineChart"></canvas>
                        </div>
                        <div id="oeeLineError" class="chart-error">Fetch Failed</div>
                    </fieldset>
                    <fieldset>
                        <h4>Quality</h4>
                        <div class="linechart-wrapper">
                            <canvas id="qualityLineChart"></canvas>
                        </div>
                        <div id="qualityLineError" class="chart-error">Fetch Failed</div>
                    </fieldset>
                    <fieldset>
                        <h4>Performance</h4>
                        <div class="linechart-wrapper">
                            <canvas id="performanceLineChart"></canvas>
                        </div>
                        <div id="performanceLineError" class="chart-error">Fetch Failed</div>
                    </fieldset>
                    <fieldset>
                        <h4>Availability</h4>
                        <div class="linechart-wrapper">
                            <canvas id="availabilityLineChart"></canvas>
                        </div>
                        <div id="availabilityLineError" class="chart-error">Fetch Failed</div>
                    </fieldset>
                </div>
            </div>
        </div>

        <div class="stop_scarp-barchart">
            <div style="display: block; width: 100%;">
                <div class="bar-chart">
                    <fieldset>
                        <h4>Stop Cause</h4>
                        <div class="barchart-wrapper">
                            <canvas id="stopCauseBarChart"></canvas>
                        </div>
                        <div id="stopCauseBarError">Error loading scrap data</div>
                    </fieldset>
                    <fieldset>
                        <h4>Scrap</h4>
                        <div class="barchart-wrapper">
                            <canvas id="scrapBarChart"></canvas>
                        </div>
                        <div id="scrapBarError">Error loading stop cause data</div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Scrap -->
    <div id="scrapModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('scrapModal')">&times;</span>
            <h2>Add Scrap</h2>
            <form action="../api/OEE_Dashboard/add_scrap.php" method="POST">
                <input type="date" name="log_date" required><br>
                <input type="time" placeholder="Time" name="log_time" required><br>
                <input type="text" placeholder="Part No" name="part_no" required><br>
                <input type="number" placeholder="Scrap Count" name="scrap_count"><br>
                <button type="submit">Submit Scrap</button>
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
    <script src="../script/fetch_line&barchart.js"></script>
    <script src="../script/fetch_piechart.js"></script>
    <script src="../script/OEE_piechart.js"></script>
    <script src="../script/OEE_linechart.js"></script>
    <script src="../script/OEE_barchart.js"></script>

</body>
</html>
