<?php
// ตรวจสอบว่า session ถูกเริ่มแล้วหรือยัง ถ้ายังให้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามีการล็อกอินหรือยัง
if (!isset($_SESSION['user'])) {
    // ถ้ายังไม่ได้ล็อกอิน ให้ redirect ไปหน้า login พร้อมกับส่งหน้าที่พยายามจะเข้าถึงไปด้วย
    $redirect_url = str_replace('/oee_dashboard/oee_dashboard-main/OEE_Dashboard', '', $_SERVER['REQUEST_URI']);
    header("Location: ../auth/login_form.php?redirect=" . urlencode($redirect_url));
    exit;
}

/**
 * ฟังก์ชันสำหรับตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่มี Role ที่ต้องการหรือไม่
 * @param array|string $roles Role ที่ต้องการตรวจสอบ (สามารถเป็น string เดียว หรือ array ของ role)
 * @return bool
 */
function hasRole($roles): bool {
    if (empty($_SESSION['user']['role'])) {
        return false;
    }

    $userRole = $_SESSION['user']['role'];
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}
?>