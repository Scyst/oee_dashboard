<?php 
    include_once("../../auth/check_auth.php"); 
    
    if (!hasRole(['admin', 'creator'])) { 
        header("Location: ../OEE_Dashboard/OEE_Dashboard.php"); 
        exit; 
    }

    $canManage = hasRole(['admin', 'creator']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>System Parameters</title>
    <script src="../../utils/libs/xlsx.full.min.js"></script>
    <script src="../../utils/libs/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../style/style.css">
</head>

<body class="bg-dark text-white p-4">
    <?php include('../components/nav_dropdown.php'); ?>
    
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">System Parameters</h2>
        </div>

        <ul class="nav nav-tabs" id="paramTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="standard-params-tab" data-bs-toggle="tab" data-bs-target="#standardParamsPane" type="button" role="tab">
                    <i class="fas fa-cogs"></i> Standard Parameters
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="schedules-tab" data-bs-toggle="tab" data-bs-target="#lineSchedulesPane" type="button" role="tab">
                    <i class="fas fa-calendar-alt"></i> Line Schedules
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-warning" id="health-check-tab" data-bs-toggle="tab" data-bs-target="#healthCheckPane" type="button" role="tab">
                    <i class="fas fa-heartbeat"></i> Data Health Check
                </button>
            </li>
        </ul>

        <div class="tab-content pt-3" id="paramTabContent">

            <div class="tab-pane fade show active" id="standardParamsPane" role="tabpanel">
                <div class="row mb-3 align-items-center sticky-bar">
                    <div class="col-md-5">
                        <div class="filter-controls-wrapper">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search parameters...">
                        </div>
                    </div>
                    <div class="col-md-4"></div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-end gap-2 btn-group-equal my-3">
                            <?php if ($canManage): ?>
                                <button class="btn btn-info flex-fill" onclick="triggerImport()">Import</button>
                            <?php endif; ?>
                            <button class="btn btn-primary flex-fill" onclick="exportToExcel()">Export</button>
                            <?php if ($canManage): ?>
                                <button class="btn btn-success flex-fill" onclick="openModal('addParamModal')">Add</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="importFile" accept=".csv, .xlsx, .xls" class="d-none">
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Model</th>
                                <th>Part No.</th>
                                <th>SAP No.</th>
                                <th>Planned Output</th>
                                <th>Updated At</th>
                                <?php if ($canManage): ?>
                                    <th style="width: 150px; text-align: center;">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="paramTableBody"></tbody>
                    </table>
                </div>
                <nav class="sticky-bottom">
                    <ul class="pagination justify-content-center" id="paginationControls"></ul>
                </nav>
            </div>

            <div class="tab-pane fade" id="lineSchedulesPane" role="tabpanel">
                 <div class="row my-3 align-items-center">
                    <div class="col-md-9"></div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-end">
                            <?php if ($canManage): ?>
                                <button class="btn btn-success flex-fill" onclick="openModal('addScheduleModal')">Add New Schedule</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-hover">
                         <thead>
                            <tr>
                                <th>Line</th>
                                <th>Shift Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Break (min)</th>
                                <th>Status</th>
                                <?php if ($canManage): ?>
                                    <th style="width: 150px; text-align: center;">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="schedulesTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="healthCheckPane" role="tabpanel">
                 <div class="alert alert-info mt-3">
                    <h4><i class="fas fa-info-circle"></i> Parts Requiring Attention</h4>
                    <p class="mb-0">The following parts have been produced but are missing standard time data (Planned Output). Please add them in the 'Standard Parameters' tab to ensure accurate OEE Performance calculation.</p>
                </div>
                <div class="table-responsive">
                     <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Model</th>
                                <th>Part No.</th>
                            </tr>
                        </thead>
                        <tbody id="missingParamsList"></tbody>
                    </table>
                </div>
                <nav class="sticky-bottom mt-3">
                    <ul class="pagination justify-content-center" id="healthCheckPaginationControls"></ul>
                </nav>
            </div>

        </div>
    </div>
    
    <div id="toast"></div>

    <?php if ($canManage) {
        include('components/addParamModal.php');
        include('components/editParamModal.php');
        include('components/addScheduleModal.php');
        include('components/editScheduleModal.php');
    } ?>

    <script>
        const canManage = <?php echo json_encode($canManage ?? false); ?>;
    </script>
    
    <script src="../components/datetime.js"></script>
    <script src="../components/auto_logout.js"></script>
    <script src="../components/toast.js"></script>
    <script src="script/modal_handler.js"></script>
    <script src="script/paraManage.js"></script>
</body>
</html>