<?php
require_once __DIR__ . '/../db.php'; // ใช้ไฟล์เชื่อมต่อฐานข้อมูลของคุณ

try {
    // 1. รับค่า Parameters
    $startDate = $_GET['startDate'] ?? date('Y-m-d');
    $endDate   = $_GET['endDate'] ?? date('Y-m-d');
    $line      = !empty($_GET['line']) ? $_GET['line'] : null;
    $model     = !empty($_GET['model']) ? $_GET['model'] : null;

    // 2. เตรียมและเรียกใช้ Stored Procedure
    $sql = "EXEC dbo.sp_CalculateOEE_PieChart @StartDate = ?, @EndDate = ?, @Line = ?, @Model = ?";
    $stmt = $pdo->prepare($sql);

    // ส่งค่า parameters เข้าไป
    $stmt->bindParam(1, $startDate, PDO::PARAM_STR);
    $stmt->bindParam(2, $endDate, PDO::PARAM_STR);
    $stmt->bindParam(3, $line, PDO::PARAM_STR);
    $stmt->bindParam(4, $model, PDO::PARAM_STR);

    $stmt->execute();

    // 3. ดึงผลลัพธ์ (มีแค่แถวเดียว) และแปลงเป็น JSON
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // จัดรูปแบบข้อมูลสุดท้ายให้ตรงกับที่ Frontend ต้องการ
        $output = [
            "success" => true,
            "quality" => (float)$result['Quality'],
            "availability" => (float)$result['Availability'],
            "performance" => (float)$result['Performance'],
            "oee" => (float)$result['OEE'],
            "fg" => (int)$result['FG'],
            "defects" => (int)$result['Defects'],
            "runtime" => (int)$result['Runtime'],
            "planned_time" => (int)$result['PlannedTime'],
            "downtime" => (int)$result['Downtime'],
            "actual_output" => (int)$result['ActualOutput'],
            "debug_info" => [
                "total_theoretical_minutes" => (float)$result['TotalTheoreticalMinutes']
            ]
        ];
        echo json_encode($output);
    } else {
        // กรณี Stored Procedure ไม่คืนค่าอะไรกลับมา (ซึ่งไม่ควรจะเกิดขึ้น)
        throw new Exception("Stored procedure did not return a result.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    error_log("Error in get_oee_piechart.php: " . $e->getMessage());
}
?>