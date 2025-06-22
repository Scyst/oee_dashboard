<?php
header('Content-Type: application/json');
require_once '../db.php';

// โครงสร้าง Response ที่ JavaScript ใหม่ของคุณคาดหวัง
$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
    'data' => []
];

$sql = "SELECT DISTINCT line FROM IOT_TOOLBOX_PARTS WHERE line IS NOT NULL AND line != '' ORDER BY line ASC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    $lines = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // ดึงข้อมูลใส่ array ปกติ
        $lines[] = $row['line'];
    }
    
    // เมื่อสำเร็จ กำหนด 'success' เป็น true และใส่ข้อมูลลงใน 'data'
    $response['success'] = true;
    $response['message'] = 'Lines fetched successfully.';
    $response['data'] = $lines;

} else {
    $errors = sqlsrv_errors();
    // (Optional) สามารถ log error ไว้ดูเองได้
    // error_log(json_encode($errors)); 
    $response['message'] = 'Failed to retrieve lines from the database.';
}

echo json_encode($response);
exit();
?>