<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

// 1. รับค่าทั่วไป
$hn = $_POST['hn'] ?? '';
$an = $_POST['an'] ?? '';
$doc_no = $_POST['doc_no'] ?? '';
$naf_seq = $_POST['naf_seq'] ?? 1;
$screening_id = !empty($_POST['screening_id']) ? $_POST['screening_id'] : NULL;
$ref_screening_doc = $_POST['ref_screening_doc'] ?? '';
$assessor_name = $_POST['assessor_name'] ?? '';

// 2. รับค่า Foreign Keys (ID) จากฟอร์ม
// (ถ้าไม่ได้เลือก ส่งค่า NULL หรือ 0)
$weight_option_id = !empty($_POST['weight_option_id']) ? $_POST['weight_option_id'] : NULL;
$weight_change_4_week_id = !empty($_POST['weight_change_4_week_id']) ? $_POST['weight_change_4_week_id'] : NULL;
$food_amount_id = !empty($_POST['food_amount_id']) ? $_POST['food_amount_id'] : NULL;
$patient_shape_id = !empty($_POST['patient_shape_id']) ? $_POST['patient_shape_id'] : NULL; // ถ้ามีในฟอร์ม
$food_type_id = !empty($_POST['food_type_id']) ? $_POST['food_type_id'] : NULL;         // ถ้ามีในฟอร์ม
$food_access_id = !empty($_POST['food_access_id']) ? $_POST['food_access_id'] : NULL;     // ถ้ามีในฟอร์ม

$b_severity = $_POST['b_severity'] ?? 0;

$total_score = 0;

// Function ช่วยดึงคะแนน
function getScore($conn, $table, $col_id, $id)
{
    if (!$id) return 0;
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $col_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    // สมมติชื่อคอลัมน์คะแนนคือ ..._score
    foreach ($row as $key => $val) {
        if (strpos($key, '_score') !== false) return intval($val);
    }
    return 0;
}

if ($weight_option_id) $total_score += getScore($conn, 'weight_option', 'weight_option_id', $weight_option_id);
if ($weight_change_4_week_id) $total_score += getScore($conn, 'weight_change_4_week', 'weight_change_4_week_id', $weight_change_4_week_id);
if ($food_amount_id) $total_score += getScore($conn, 'food_amount', 'food_amount_id', $food_amount_id);
$total_score += intval($b_severity);

// 4. ประเมินระดับ
$naf_level = '';
if ($total_score <= 5) $naf_level = 'Low Risk';
elseif ($total_score <= 11) $naf_level = 'Moderate Risk';
else $naf_level = 'High Risk';

try {
    // 5. บันทึกข้อมูล (เพิ่มคอลัมน์ FK ใหม่ลงไป)
    $sql_insert = "
        INSERT INTO nutrition_assessment (
            doc_no, naf_seq, admissions_an, patients_hn, assessment_datetime,
            
            weight_option_id, 
            weight_change_4_week_id, 
            food_amount_id, 
            patient_shape_id, 
            food_type_id, 
            food_access_id,
            
            b_severity, total_score, naf_level, assessor_name, 
            ref_screening_doc_no, screening_id
        ) VALUES (
            :doc_no, :naf_seq, :an, :hn, NOW(),
            
            :w_opt, :w_chg, :f_amt, :p_shp, :f_typ, :f_acc,
            
            :b, :total, :level, :assessor, 
            :ref_doc, :screen_id
        )
    ";

    $stmt = $conn->prepare($sql_insert);
    $stmt->execute([
        ':doc_no'   => $doc_no,
        ':naf_seq'  => $naf_seq,
        ':an'       => $an,
        ':hn'       => $hn,
        ':w_opt'    => $weight_option_id,
        ':w_chg'    => $weight_change_4_week_id,
        ':f_amt'    => $food_amount_id,
        ':p_shp'    => $patient_shape_id,
        ':f_typ'    => $food_type_id,
        ':f_acc'    => $food_access_id,
        ':b'        => $b_severity,
        ':total'    => $total_score,
        ':level'    => $naf_level,
        ':assessor' => $assessor_name,
        ':ref_doc'  => $ref_screening_doc,
        ':screen_id' => $screening_id
    ]);

    // อัปเดตสถานะ SPENT
    if (!empty($ref_screening_doc)) {
        $stmt_upd = $conn->prepare("UPDATE nutrition_screening SET screening_status='ประเมินต่อแล้ว', has_assessment=1, assessment_doc_no=:naf_doc WHERE doc_no=:ref");
        $stmt_upd->execute([':naf_doc' => $doc_no, ':ref' => $ref_screening_doc]);
    }

    header("Location: patient_profile.php?hn=" . urlencode($hn));
    exit;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
