<?php 
    include_once("../../auth/check_auth.php"); 

    $canManage = hasRole(['admin', 'creator']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <title>Parameter Manager</title>
    <script src="../../utils/libs/xlsx.full.min.js"></script>
    <script src="../../utils/libs/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/dropdown.css">
    <link rel="stylesheet" href="../../style/paraManageUI.css">
</head>

<body class="bg-dark text-white p-4">
    <?php include('../components/nav_dropdown.php'); ?>
    
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Parameter Manager</h2>
        </div>

        <div class="row mb-3 align-items-center sticky-bar">
            <div class="col-md-5    ">
                <div class="filter-controls-wrapper">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search parameters...">
                </div>
            </div>

            <div class="col-md-4"></div>

            <div class="col-md-3">
                <div class="d-flex justify-content-end gap-2 btn-group-equal">
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
                        <?php if ($canManage): // ใช้ $canManage ?>
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
    
    <div id="toast"></div>

    <?php if ($canManage) { // ใช้ $canManage
        include('components/addModal.php');
        include('components/editModal.php');
    } ?>

    <script>
        // --- START: การแก้ไขที่สำคัญ ---
        // เปลี่ยนชื่อตัวแปรเป็น canManage
        const canManage = <?php echo json_encode($canManage ?? false); ?>;
        // --- END: การแก้ไขที่สำคัญ ---
    </script>

    <script src="../components/auto_logout.js"></script>
    <script src="../components/toast.js"></script>
    <script src="script/modal_handler.js"></script>
    <script src="script/export_data.js"></script>
    <script src="script/paraManage.js"></script>
</body>
</html>