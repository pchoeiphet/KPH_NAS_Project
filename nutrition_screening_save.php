<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

// 1. รับค่าจากฟอร์ม
$hn = $_POST['hn'] ?? '';
$an = $_POST['an'] ?? '';
$redirect_to_naf = $_POST['redirect_to_naf'] ?? 'false'; // 'true' หรือ 'false'

// รับค่าคะแนน (ถ้าไม่มีให้เป็น 0)
$q1 = intval($_POST['q1'] ?? 0);
$q2 = intval($_POST['q2'] ?? 0);
$q3 = intval($_POST['q3'] ?? 0);
$q4 = intval($_POST['q4'] ?? 0);

// คำนวณคะแนนรวม
$total_score = $q1 + $q2 + $q3 + $q4;

// --------------------------------------------------------
// ส่วนกำหนด STATUS ตามเงื่อนไขที่คุณต้องการ
// --------------------------------------------------------
$screening_status = '';
$has_assessment = 0;
$screening_result = ''; // เก็บผลลัพธ์ text เดิมด้วย

if ($total_score < 2) {
    // กรณีปกติ
    $screening_status = 'ปกติ';
    $screening_result = 'ปกติ';
    $has_assessment = 0;
} else {
    // กรณีมีความเสี่ยง (Score >= 2)
    $screening_result = 'มีความเสี่ยง';

    if ($redirect_to_naf === 'true') {
        // กด "ทำการประเมินต่อ"
        $screening_status = 'กำลังประเมิน'; // หรือสถานะ 'กำลังประเมิน' ตามบริบท
        $has_assessment = 1;
        // *หมายเหตุ: assessment_doc_no จะถูก update ภายหลังเมื่อบันทึกฟอร์ม NAF เสร็จ
    } else {
        // กด "บันทึกไว้ทำทีหลัง"
        $screening_status = 'รอทำแบบประเมิน';
        $has_assessment = 0;
    }
}
// --------------------------------------------------------

try {
    // 2. สร้างเลขที่เอกสาร (SPENT-HN-XXX)
    // หาลำดับล่าสุด
    $stmt_seq = $conn->prepare("SELECT MAX(screening_seq) as max_seq FROM nutrition_screening WHERE admissions_an = :an");
    $stmt_seq->execute([':an' => $an]);
    $row_seq = $stmt_seq->fetch(PDO::FETCH_ASSOC);
    $next_seq = ($row_seq['max_seq'] ?? 0) + 1;

    $doc_no = 'SPENT-' . $hn . '-' . str_pad($next_seq, 3, '0', STR_PAD_LEFT);

    // 3. บันทึกลงฐานข้อมูล
    $sql = "INSERT INTO nutrition_screening (
                doc_no, admissions_an, patients_hn, screening_datetime, screening_seq,
                initial_diagnosis, present_weight, normal_weight, height, bmi, weight_method,
                q1_weight_loss, q2_eat_less, q3_bmi_abnormal, q4_critical,
                screening_result, notes, assessor_name,
                screening_status, has_assessment
            ) VALUES (
                :doc_no, :an, :hn, NOW(), :seq,
                :diagnosis, :weight, :normal_weight, :height, :bmi, :method,
                :q1, :q2, :q3, :q4,
                :result, :notes, :assessor,
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
        ':assessor' => $_POST['assessor_name'] ?? '',
        ':status' => $screening_status,  
        ':has_assess' => $has_assessment      
    ]);

    // 4. การ Redirect
    if ($redirect_to_naf === 'true' && $total_score >= 2) {
        // ส่งไปหน้าประเมิน NAF (ส่ง doc_no ของ screening ไปอ้างอิงด้วย)
        header("Location: nutrition_assessment_form.php?hn=$hn&an=$an&ref_screening=$doc_no");
    } else {
        // กลับหน้าเดิม หรือหน้า Profile ผู้ป่วย
        header("Location: patient_profile.php?hn=$hn");
    }
    exit;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
