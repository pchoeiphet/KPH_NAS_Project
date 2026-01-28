<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. รับค่าจากฟอร์ม
$hn = $_POST['hn'] ?? '';
$an = $_POST['an'] ?? '';
$redirect_to_naf = $_POST['redirect_to_naf'] ?? 'false';

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
    // 2. สร้างเลขที่เอกสาร
    $stmt_seq = $conn->prepare("SELECT MAX(screening_seq) as max_seq FROM nutrition_screening WHERE admissions_an = :an");
    $stmt_seq->execute([':an' => $an]);
    $row_seq = $stmt_seq->fetch(PDO::FETCH_ASSOC);
    $next_seq = ($row_seq['max_seq'] ?? 0) + 1;

    $doc_no = 'SPENT-' . $hn . '-' . str_pad($next_seq, 3, '0', STR_PAD_LEFT);

    // 3. บันทึกลงฐานข้อมูล
    // ** แก้ไข SQL: เปลี่ยน assessor_name เป็น nut_id **
    $sql = "INSERT INTO nutrition_screening (
                doc_no, admissions_an, patients_hn, screening_datetime, screening_seq,
                initial_diagnosis, present_weight, normal_weight, height, bmi, weight_method,
                q1_weight_loss, q2_eat_less, q3_bmi_abnormal, q4_critical,
                screening_result, notes, nut_id, 
                screening_status, has_assessment
            ) VALUES (
                :doc_no, :an, :hn, NOW(), :seq,
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
        ':seq' => $next_seq,
        ':diagnosis' => $_POST['initial_diagnosis'] ?? '',
        ':weight' => $_POST['present_weight'] ?? 0,
        ':normal_weight' => $_POST['normal_weight'] ?? 0,
        ':height' => $_POST['height'] ?? 0,
        ':bmi' => $_POST['bmi'] ?? 0,
        ':method' => $_POST['weightMethod'] ?? '',
        ':q1' => $q1,
        ':q2' => $q2,
        ':q3' => $q3,
        ':q4' => $q4,
        ':result' => $screening_result,
        ':notes' => $_POST['notes'] ?? '',

        // ** แก้ไขค่าที่ส่ง: ใช้ ID จาก Session **
        ':nut_id' => $_SESSION['user_id'],

        ':status' => $screening_status,
        ':has_assess' => $has_assessment
    ]);

    // 4. การ Redirect
    if ($redirect_to_naf === 'true' && $total_score >= 2) {
        header("Location: nutrition_alert_form.php?hn=$hn&an=$an&ref_screening=$doc_no");
    } else {
        header("Location: patient_profile.php?hn=$hn&an=$an");
    }
    exit;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
