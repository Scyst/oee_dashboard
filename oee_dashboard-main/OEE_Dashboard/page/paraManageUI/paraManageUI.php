<?php 
    include_once("../../auth/check_auth.php"); 
    $isAdmin = hasRole(['admin', 'creator']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
            <?php if ($isAdmin): ?>
                <button class="btn btn-success" onclick="openModal('addParamModal')">Add New Parameter</button>
            <?php endif; ?>
        </div>

        <div class="row mb-2 align-items-center sticky-bar">
            <div class="col-md-9">
                <input type="text" class="form-control" id="searchInput" placeholder="Search parameters...">
            </div>
            <div class="col-md-3 text-end">
                <?php if ($isAdmin): ?>
                    <button class="btn btn-sm btn-primary me-2" onclick="triggerImport()">Import</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-info" onclick="exportToExcel()">Export</button>
            </div>
            <input type="file" id="importFile" accept=".csv, .xlsx, .xls" class="d-none">
        </div>

        <div>
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr>
                        <th>Line</th><th>Model</th><th>Part No.</th><th>SAP No.</th><th>Planned Output</th><th>Updated At</th>
                        <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
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

    <?php if ($isAdmin) {
        include('components/addModal.php');
        include('components/editModal.php');
    } ?>

    <script>
        const isAdmin = <?php echo json_encode($isAdmin ?? false); ?>;
    </script>


    <script src="../components/auto_logout.js"></script>
    <script src="../components/toast.js"></script>
    <script src="script/modal_handler.js"></script>
    <script src="script/export_data.js"></script>
    <script src="script/paraManage.js"></script>
</body>
</html>