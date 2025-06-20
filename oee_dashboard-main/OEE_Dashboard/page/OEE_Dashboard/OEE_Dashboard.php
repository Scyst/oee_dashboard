<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - DASHBOARD</title>
    <script src="../../utils/libs/chart.umd.js"></script>
    <script src="../../utils/libs/chartjs-plugin-zoom.min.js"></script>
    <script src="../../utils/libs/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/dropdown.css">
    <link rel="stylesheet" href="../../style/piechart.css">
    <link rel="stylesheet" href="../../style/linechart.css">
    <link rel="stylesheet" href="../../style/barchart.css">
</head>

<body>
    <?php include('../components/nav_dropdown.php'); ?>

    <div style="height: calc(100vh - 20px);">
        <div class="Header">

            <div class="OEE-head">
                <h2>OEE DASHBOARD</h2>        
                <!-- Filter Row -->
                <div style="display: flex; justify-content: center; gap: 10px; align-items: center; margin:0 auto; width: fit-content;">
                    <select id="lineFilter">
                        <option value="">All Lines</option>
                    </select>

                    <select id="modelFilter">
                        <option value="">All Models</option>
                    </select>
                    <input type="date" id="startDate">
                    <p style="text-align: center; align-content: center;"> - </p>
                    <input type="date" id="endDate">
                </div>
            </div>

            <div class="assis-tool">
                <p id="date"></p>
                <p id="time"></p>
            </div>
        </div>

        <div class="oee-piechart">
            <div style="display: block;width: 100%">
                <div class="pie-chart">
                    <fieldset>
                        <div>
                            <h4>OEE</h4>
                            <div class="chart-info" id="oeeInfo"></div>
                        </div>
                        <div class="align_legend">
                            <div class="piechart-wrapper">
                                <canvas id="oeePieChart"></canvas>
                                <div class="error-message" id="oeeError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <div>
                            <h4>Quality</h4>
                            <div class="chart-info" id="qualityInfo"></div>
                        </div>
                        <div class="align_legend">
                            <div class="piechart-wrapper">
                                <canvas id="qualityPieChart"></canvas>
                                <div class="error-message" id="qualityError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <div>
                            <h4>Performance</h4>
                            <div class="chart-info" id="performanceInfo"></div>
                        </div>
                        <div class="align_legend">
                            <div class="piechart-wrapper">
                                <canvas id="performancePieChart"></canvas>
                                <div class="error-message" id="performanceError">⚠️</div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <div>
                            <h4>Availability</h4>
                            <div class="chart-info" id="availabilityInfo"></div>
                        </div>
                        <div class="align_legend">
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
                    <div class="linechart-wrapper">
                        <canvas id="oeeLineChart"></canvas>
                    </div>
                    <!--div id="oeeLineError" class="chart-error">Fetch Failed</!--div-->
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

    <script src="../datetime.js"></script>
    <script src="OEE_piechart.js"></script>
    <script src="OEE_linechart.js"></script>
    <script src="OEE_barchart.js"></script>
    <script src="filterManager.js"></script>
</body>
</html>
