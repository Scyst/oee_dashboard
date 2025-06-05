<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - DASHBOARD</title>
    <script src="../utils/libs/chart.umd.js"></script>
    <script src="../utils/libs/chartjs-plugin-zoom.min.js"></script>

    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/piechart.css">
    <link rel="stylesheet" href="../style/linechart.css">
    <link rel="stylesheet" href="../style/barchart.css">
</head>

<body>
    <div style="height: calc(100vh - 20px);">
        <div class="Header">
            <div class="OEE-head">
                <h2>OEE DASHBOARD</h2>        
                <!-- Filter Row -->
                <div style="display: flex; justify-content: center; gap: 5px; align-items: center; margin:0 auto; width: fit-content;">
                    <select id="lineFilter">
                        <option value="">All Lines</option>
                    </select>

                    <select id="modelFilter">
                        <option value="">All Models</option>
                    </select>
                    <input type="date" id="startDate" onchange="fetchAndRenderBarCharts()">
                    <p style="text-align: center; align-content: center;"> - </p>
                    <input type="date" id="endDate" onchange="fetchAndRenderBarCharts()">
                </div>
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
            <div style="width: 100%">
                <fieldset>
                    <h4>OEE / Quality / Performance / Availability</h4>
                    <div class="linechart-wrapper">
                        <canvas id="oeeLineChart"></canvas>
                    </div>
                    <div id="oeeLineError" class="chart-error">Fetch Failed</div>
                </fieldset>
            </div>
        </div>

        <div class="stop_scarp-barchart">
            <div style="display: block; width: 100%;">
                <div class="bar-chart">
                    <fieldset>
                        <h4>Stop & Cause</h4>
                        <div class="barchart-wrapper">
                            <canvas id="stopCauseBarChart"></canvas>
                        </div>
                        <div id="stopCauseBarError">Error loading scrap data</div>
                    </fieldset>
                    <fieldset>
                        <h4>Production Results</h4>
                        <div class="barchart-wrapper">
                            <canvas id="partsBarChart"></canvas>
                        </div>
                        <div id="partsBarError">Error loading part summary</div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <script>
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
    </script>

    <script src="../script/datetime.js"></script>
    <script src="../script/OEE_Dashboard/OEE_piechart.js"></script>
    <!--script src="../script/OEE_Dashboard/OEE_linechart.js"></!--script-->
    <script src="../script/OEE_Dashboard/OEE_barchart.js"></script>
</body>
</html>
