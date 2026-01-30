<?php
session_start();

// ตรวจสอบว่า user เข้าสู่ระบบจริง ๆ หรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ตรวจสอบ CSRF token สำหรับ POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token mismatch on logout");
        header("Location: login.php");
        exit;
    }
}

// บันทึก logout activity (optional)
try {
    error_log("User {$_SESSION['user_id']} logged out at " . date('Y-m-d H:i:s'));
} catch (Exception $e) {
    // Silent fail
}

// ล้าง session อย่างปลอดภัย
$_SESSION = array();

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit;
?>