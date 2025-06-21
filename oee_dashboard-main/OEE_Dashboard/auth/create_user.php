<?php
require_once __DIR__ . '/../api/db.php';

// เพิ่ม Creator เป็นคนแรกในรายการ
$users = [
    ['__creator__070676__33025__Scyst__', 'H2P[forever]', 'creator'],
    ['verymaron01', 'numthong01', 'admin'],
    ['SncToolbox', 'SncToolbox@2025', 'admin'],
    ['Assembly', 'Assembly@2025', 'supervisor'],
    ['Spot', 'Spot@2025', 'supervisor'],
    ['Bend', 'Bend@2025', 'supervisor'],
    ['Laser', 'Laser@2025', 'supervisor'],
    ['Paint', 'Paint@2025', 'supervisor'],
    ['Press', 'Press@2025', 'supervisor'],
];

// ใช้ <pre> เพื่อให้แสดงผลในเบราว์เซอร์ได้สวยงามขึ้น
echo "<pre style='font-family: monospace; background-color: #333; color: #fff; padding: 15px; border-radius: 5px;'>";
echo "Starting user creation/validation process...\n\n";

try {
    // เตรียมคำสั่ง SQL แค่ครั้งเดียวก่อนเข้า loop เพื่อประสิทธิภาพที่ดีกว่า
    $sql = "INSERT INTO IOT_TOOLBOX_USERS (username, password, role, created_at) VALUES (?, ?, ?, GETDATE())";
    $stmt = $pdo->prepare($sql);

    // วนลูปเพื่อสร้างผู้ใช้แต่ละคน
    foreach ($users as $userData) {
        $username = $userData[0];
        $plainPassword = $userData[1];
        $role = $userData[2];

        // เข้ารหัสรหัสผ่านด้วยวิธีที่ปลอดภัย
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        try {
            // สั่ง execute คำสั่งที่เตรียมไว้พร้อมกับข้อมูลของ user ปัจจุบัน
            $stmt->execute([$username, $hashedPassword, $role]);
            echo "SUCCESS: User '{$username}' (Role: {$role}) created.\n";
        } catch (PDOException $e) {
            // จัดการกรณีที่มีชื่อผู้ใช้นี้อยู่แล้ว (Duplicate entry)
            if ($e->getCode() == '23000') {
                echo "INFO: User '{$username}' already exists. Skipping.\n";
            } else {
                // แจ้ง Error อื่นๆ ที่อาจเกิดขึ้น
                echo "ERROR: Could not create user '{$username}'. Reason: " . $e->getMessage() . "\n";
            }
        }
    }

} catch (PDOException $e) {
    // จัดการ Error ร้ายแรงที่อาจเกิดตอนเตรียมคำสั่ง SQL
    die("FATAL ERROR: A database error occurred during preparation. " . $e->getMessage());
}

echo "\nProcess finished.\n";
echo "</pre>";
?>