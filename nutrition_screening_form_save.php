<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();

// ตรวจสอบ session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ตรวจสอบ session timeout (30 นาที)
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// ตรวจสอบ REQUEST METHOD
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    error_log("Invalid request method for nutrition_screening_form_save.php");
    die("ข้อผิดพลาด: Invalid request");
}

// ตรวจสอบ CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF token mismatch for user " . $_SESSION['user_id']);
    die("ข้อผิดพลาด: ตรวจสอบความถูกต้องไม่ผ่าน");
}

// Validate และ sanitize input
$hn = trim($_POST['hn'] ?? '');
$an = trim($_POST['an'] ?? '');
$redirect_to_naf = trim($_POST['redirect_to_naf'] ?? 'false');

// รับค่าวันที่และเวลาจากฟอร์ม
$s_date = $_POST['screening_date'] ?? date('Y-m-d');
$s_time = $_POST['screening_time'] ?? date('H:i');

// รวมเป็น DateTime Format (YYYY-MM-DD HH:MM:SS) สำหรับบันทึกลง MySQL
$save_datetime = $s_date . ' ' . $s_time . ':00';

// ตรวจสอบ HN และ AN (อนุญาตเฉพาะตัวอักษร ตัวเลข - เท่านั้น)
if (empty($hn) || empty($an) || !preg_match('/^[A-Za-z0-9\-]+$/', $hn) || !preg_match('/^[A-Za-z0-9\-]+$/', $an)) {
    error_log("Invalid HN or AN in form save: HN=$hn, AN=$an");
    die("ข้อผิดพลาด: พารามิเตอร์ไม่ถูกต้อง");
}

// ตรวจสอบ redirect_to_naf (whitelist)
if (!in_array($redirect_to_naf, ['true', 'false'])) {
    $redirect_to_naf = 'false';
}

// รับค่าคะแนน
$q1 = intval($_POST['q1'] ?? 0);
$q2 = intval($_POST['q2'] ?? 0);
$q3 = intval($_POST['q3'] ?? 0);
$q4 = intval($_POST['q4'] ?? 0);

$total_score = $q1 + $q2 + $q3 + $q4;

$screening_status = '';
$has_assessment = 0;
$screening_result = '';

if ($total_score < 2) {
    $screening_status = 'ปกติ';
    $screening_result = 'ปกติ';
    $has_assessment = 0;
} else {
    $screening_result = 'มีความเสี่ยง';
    if ($redirect_to_naf === 'true') {
        $screening_status = 'กำลังประเมิน';
        $has_assessment = 1;
    } else {
        $screening_status = 'รอทำแบบประเมิน';
        $has_assessment = 0;
    }
}

try {
    // สร้างเลขที่เอกสาร
    $stmt_seq = $conn->prepare("SELECT MAX(screening_seq) as max_seq FROM nutrition_screening WHERE admissions_an = :an");
    $stmt_seq->execute([':an' => $an]);
    $row_seq = $stmt_seq->fetch(PDO::FETCH_ASSOC);
    $next_seq = ($row_seq['max_seq'] ?? 0) + 1;

    $doc_no = 'SPENT-' . $hn . '-' . str_pad($next_seq, 3, '0', STR_PAD_LEFT);

    // บันทึกข้อมูลลงฐานข้อมูล
    $sql = "INSERT INTO nutrition_screening (
                doc_no, admissions_an, patients_hn, screening_datetime, screening_seq,
                initial_diagnosis, present_weight, normal_weight, height, bmi, weight_method,
                q1_weight_loss, q2_eat_less, q3_bmi_abnormal, q4_critical,
                screening_result, notes, nut_id, 
                screening_status, has_assessment
            ) VALUES (
                :doc_no, :an, :hn, :screening_datetime, :seq,
                :diagnosis, :weight, :normal_weight, :height, :bmi, :method,
                :q1, :q2, :q3, :q4,
                :result, :notes, :nut_id,     
                :status, :has_assess
            )";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':doc_no' => $doc_no,
        ':an' => $an,
        ':hn' => $hn,
        ':screening_datetime' => $save_datetime,
        ':seq' => $next_seq,
        ':diagnosis' => trim($_POST['initial_diagnosis'] ?? ''),
        ':weight' => floatval($_POST['present_weight'] ?? 0),
        ':normal_weight' => floatval($_POST['normal_weight'] ?? 0),
        ':height' => intval($_POST['height'] ?? 0),
        ':bmi' => floatval($_POST['bmi'] ?? 0),
        ':method' => trim($_POST['weightMethod'] ?? ''),
        ':q1' => $q1,
        ':q2' => $q2,
        ':q3' => $q3,
        ':q4' => $q4,
        ':result' => $screening_result,
        ':notes' => trim($_POST['notes'] ?? ''),
        ':nut_id' => $_SESSION['user_id'],
        ':status' => $screening_status,
        ':has_assess' => $has_assessment
    ]);

    // Redirect ตามเงื่อนไข
    if ($redirect_to_naf === 'true' && $total_score >= 2) {
        header("Location: nutrition_alert_form.php?hn=" . urlencode($hn) . "&an=" . urlencode($an) . "&ref_screening=" . urlencode($doc_no));
    } else {
        header("Location: patient_profile.php?hn=" . urlencode($hn) . "&an=" . urlencode($an));
    }
    exit;
} catch (PDOException $e) {
    error_log("Database Error in nutrition_screening_form_save.php: " . $e->getMessage());
    die("ข้อผิดพลาดในระบบ");
}
