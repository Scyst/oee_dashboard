<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OEE - DASHBOARD</title>
    <script src="../utils/libs/chart.umd.js"></script>
    <script src="../utils/libs/chartjs-plugin-zoom.min.js"></script>
    <script src="../utils/libs/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="../style/dropdown.css">
    <link rel="stylesheet" href="../style/piechart.css">
    <link rel="stylesheet" href="../style/linechart.css">
    <link rel="stylesheet" href="../style/barchart.css">
</head>

<body>
    <div class="dropdown dropdown-menu-wrapper">
        <button class="dropdown-toggle-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../icons/menu.png" alt="Menu" width="32" height="32">
        </button>
        <ul class="dropdown-menu dropdown-menu-end custom-dropdown">
        <li>
            <a class="dropdown-item-icon" href="OEE_Dashboard.php" title="Dashboard">
            <img src="../icons/dashboard.png" alt="Dashboard">
            <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item-icon" href="pdTable.php" title="Parts Table">
            <img src="../icons/db.png" alt="Parts Table">
            <span>Parts</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item-icon" href="Stop_Cause.php" title="Stop Cause">
            <img src="../icons/clipart2496353.png" alt="Stop Cause">
            <span>Stop Cause</span>
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <?php if (isset($_SESSION['user'])): ?>
            <li>
            <a class="dropdown-item-icon" href="../auth/logout_to_dashboard.php" title="Logout">
                <img src="../icons/logout.png" alt="Logout">
                <span>Logout</span>
            </a>
            </li>
        <?php else: ?>
            <li>
            <a class="dropdown-item-icon" href="../auth/login_form.php" title="Login">
                <img src="../icons/user.png" alt="Login">
                <span>Login</span>
            </a>
            </li>
        <?php endif; ?>
        </ul>
    </div>

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

    <script src="../script/datetime.js"></script>
    <script src="../script/OEE_Dashboard/OEE_piechart.js"></script>
    <script src="../script/OEE_Dashboard/OEE_linechart.js"></script>
    <script src="../script/OEE_Dashboard/OEE_barchart.js"></script>
    <script src="../script/OEE_Dashboard/filterManager.js"></script>
</body>
</html>
