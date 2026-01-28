<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// รับค่าจากฟอร์ม
$hn = $_POST['hn'] ?? '';
$an = $_POST['an'] ?? '';
$doc_no = $_POST['doc_no'] ?? '';
$naf_seq = $_POST['naf_seq'] ?? 1;
$screening_id = !empty($_POST['screening_id']) ? $_POST['screening_id'] : NULL;
$ref_screening_doc = $_POST['ref_screening_doc'] ?? '';
$current_user_id = $_SESSION['user_id'];

// ข้อมูลทั่วไป
$initial_diagnosis = $_POST['initial_diagnosis'] ?? NULL;
$info_source = $_POST['info_source'] ?? 'ผู้ป่วย';
$other_source = $_POST['other_source'] ?? NULL;

$weight = !empty($_POST['weight']) ? $_POST['weight'] : NULL;
$height_measure = !empty($_POST['height_measure']) ? $_POST['height_measure'] : NULL;
$body_length = !empty($_POST['body_length']) ? $_POST['body_length'] : NULL;
$arm_span = !empty($_POST['arm_span']) ? $_POST['arm_span'] : NULL;
$height_relative = !empty($_POST['height_relative']) ? $_POST['height_relative'] : NULL;

$bmi = !empty($_POST['bmi']) ? $_POST['bmi'] : NULL;
$bmi_score = !empty($_POST['bmi_score']) ? $_POST['bmi_score'] : 0;
$is_no_weight = isset($_POST['is_no_weight']) ? 1 : 0;

// ข้อมูล Lab
$lab_method = !empty($_POST['lab_method']) ? $_POST['lab_method'] : NULL;
$albumin_val = !empty($_POST['albumin_val']) ? $_POST['albumin_val'] : NULL;
$tlc_val = !empty($_POST['tlc_val']) ? $_POST['tlc_val'] : NULL;
$lab_score = !empty($_POST['lab_score']) ? $_POST['lab_score'] : 0;

// Foreign Keys
$weight_option_id = !empty($_POST['weight_option_id']) ? $_POST['weight_option_id'] : NULL;
$weight_change_4_weeks_id = !empty($_POST['weight_change_4_week_id']) ? $_POST['weight_change_4_week_id'] : NULL;
$food_amount_id = !empty($_POST['food_amount_id']) ? $_POST['food_amount_id'] : NULL;
$patient_shape_id = !empty($_POST['patient_shape_id']) ? $_POST['patient_shape_id'] : NULL;
$food_type_id = !empty($_POST['food_type_id']) ? $_POST['food_type_id'] : NULL;
$food_access_id = !empty($_POST['food_access_id']) ? $_POST['food_access_id'] : NULL;


function getScore($conn, $table, $id_col, $id)
{
    if (!$id) return 0;
    try {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE $id_col = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        foreach ($row as $key => $val) {
            // หา field ที่มีคำว่า score (เช่น weight_option_score, food_type_score)
            if (strpos($key, '_score') !== false) return intval($val);
        }
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}

function getDiseaseData($conn, $id)
{
    if (!$id) return ['score' => 0, 'type' => 'General'];
    try {
        $stmt = $conn->prepare("SELECT disease_score, disease_type FROM disease WHERE disease_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'score' => intval($row['disease_score'] ?? 0),
            'type'  => $row['disease_type'] ?? 'General'
        ];
    } catch (Exception $e) {
        return ['score' => 0, 'type' => 'General'];
    }
}


// คำนวณคะแนนรวม (Total Score)
$total_score = 0;

// คะแนนจากตัวเลือกพื้นฐาน
if ($weight_option_id) $total_score += getScore($conn, 'weight_option', 'weight_option_id', $weight_option_id);
if ($weight_change_4_weeks_id) $total_score += getScore($conn, 'weight_change_4_weeks', 'weight_change_4_weeks_id', $weight_change_4_weeks_id);
if ($food_amount_id) $total_score += getScore($conn, 'food_amount', 'food_amount_id', $food_amount_id);

if ($patient_shape_id) $total_score += getScore($conn, 'patient_shape', 'patient_shape_id', $patient_shape_id);
if ($food_type_id) $total_score += getScore($conn, 'food_type', 'food_type_id', $food_type_id);
if ($food_access_id) $total_score += getScore($conn, 'food_access', 'food_access_id', $food_access_id);

// คะแนน BMI / Lab
$total_score += intval($bmi_score);
$total_score += intval($lab_score);

// คะแนนอาการ
$symptom_ids = $_POST['symptom_ids'] ?? [];
if (!empty($symptom_ids) && is_array($symptom_ids)) {
    foreach ($symptom_ids as $sid) {
        if (!is_numeric($sid)) continue;
        $total_score += getScore($conn, 'symptom_problem', 'symptom_problem_id', $sid);
    }
}

// คะแนนโรค (Diseases)
$disease_ids = $_POST['disease_ids'] ?? [];
$check_other_mild = isset($_POST['check_other_mild']);
$check_other_severe = isset($_POST['check_other_severe']);
$disease_other_mild_text = $_POST['disease_other_mild'] ?? '';
$disease_other_severe_text = $_POST['disease_other_severe'] ?? '';

// วนลูปโรคทั่วไป
if (!empty($disease_ids) && is_array($disease_ids)) {
    foreach ($disease_ids as $did) {
        if (!is_numeric($did)) continue;
        $d_data = getDiseaseData($conn, $did);
        $total_score += $d_data['score'];
    }
}

// บวกคะแนนโรคอื่นๆ
if ($check_other_mild)   $total_score += 3;
if ($check_other_severe) $total_score += 6;


// ประเมินระดับ (NAF Level)
$naf_level = '';
if ($total_score <= 5) $naf_level = 'NAF A';
elseif ($total_score <= 11) $naf_level = 'NAF B';
else $naf_level = 'NAF C';


try {
    $conn->beginTransaction();

    // ---------------------------------------------------------
    // 4. INSERT ตารางหลัก
    // ---------------------------------------------------------
    $sql_main = "
        INSERT INTO nutrition_assessment (
            doc_no, naf_seq, admissions_an, patients_hn, assessment_datetime,
            initial_diagnosis, info_source, other_source,
            height_measure, body_length, arm_span, height_relative, 
            weight, bmi, bmi_score, is_no_weight,
            lab_method, albumin_val, tlc_val, lab_score,
            weight_option_id, patient_shape_id, weight_change_4_weeks_id, 
            food_type_id, food_amount_id, food_access_id,
            total_score, naf_level, nut_id,  -- << เปลี่ยนจาก assessor_name เป็น nut_id
            ref_screening_doc_no, nutrition_screening_id
        ) VALUES (
            :doc_no, :naf_seq, :an, :hn, NOW(),
            :diagnosis, :src, :oth_src,
            :h, :body_l, :arm, :h_rel, 
            :w, :bmi, :bmi_s, :no_w,
            :lab_m, :alb, :tlc, :lab_s,
            :w_opt, :p_shp, :w_chg, 
            :f_typ, :f_amt, :f_acc,
            :total, :level, :nut_id, -- << เปลี่ยน Placeholder
            :ref_doc, :screen_id
        )
    ";

    $stmt = $conn->prepare($sql_main);
    $stmt->execute([
        ':doc_no'   => $doc_no,
        ':naf_seq'  => $naf_seq,
        ':an'       => $an,
        ':hn'       => $hn,
        ':diagnosis' => $initial_diagnosis,
        ':src'      => $info_source,
        ':oth_src'  => $other_source,
        ':h'        => $height_measure,
        ':body_l'   => $body_length,
        ':arm'      => $arm_span,
        ':h_rel'    => $height_relative,
        ':w'        => $weight,
        ':bmi'      => $bmi,
        ':bmi_s'    => $bmi_score,
        ':no_w'     => $is_no_weight,
        ':lab_m'    => $lab_method,
        ':alb'      => $albumin_val,
        ':tlc'      => $tlc_val,
        ':lab_s'    => $lab_score,
        ':w_opt'    => $weight_option_id,
        ':p_shp'    => $patient_shape_id,
        ':w_chg'    => $weight_change_4_weeks_id,
        ':f_typ'    => $food_type_id,
        ':f_amt'    => $food_amount_id,
        ':f_acc'    => $food_access_id,
        ':total'    => $total_score,
        ':level'    => $naf_level,
        ':nut_id'   => $current_user_id,
        ':ref_doc'  => $ref_screening_doc,
        ':screen_id' => $screening_id
    ]);

    $nutrition_assessment_id = $conn->lastInsertId();

    // ---------------------------------------------------------
    // 5. INSERT ตารางลูก: disease_saved
    // ---------------------------------------------------------
    $sql_disease = "INSERT INTO disease_saved (nutrition_assessment_id, disease_id, disease_other_name, disease_type, disease_score) VALUES (:na_id, :d_id, :d_name, :d_type, :d_score)";
    $stmt_d = $conn->prepare($sql_disease);

    if (!empty($disease_ids) && is_array($disease_ids)) {
        foreach ($disease_ids as $did) {
            if (!is_numeric($did)) continue;
            $d_data = getDiseaseData($conn, $did);
            $stmt_d->execute([
                ':na_id' => $nutrition_assessment_id,
                ':d_id' => $did,
                ':d_name' => NULL,
                ':d_type' => $d_data['type'],
                ':d_score' => $d_data['score']
            ]);
        }
    }

    if ($check_other_mild) {
        $stmt_d->execute([':na_id' => $nutrition_assessment_id, ':d_id' => NULL, ':d_name' => $disease_other_mild_text, ':d_type' => 'โรคที่มีความรุนแรงน้อยถึงปานกลาง', ':d_score' => 3]);
    }

    if ($check_other_severe) {
        $stmt_d->execute([':na_id' => $nutrition_assessment_id, ':d_id' => NULL, ':d_name' => $disease_other_severe_text, ':d_type' => 'โรคที่มีความรุนแรงมาก', ':d_score' => 6]);
    }

    // ---------------------------------------------------------
    // 6. INSERT ตารางลูก: symptom
    // ---------------------------------------------------------
    if (!empty($symptom_ids) && is_array($symptom_ids)) {
        $sql_symptom = "INSERT INTO symptom_problem_saved (nutrition_assessment_id, symptom_problem_id, symptom_problem_score) VALUES (:na_id, :s_id, :s_score)";
        $stmt_s = $conn->prepare($sql_symptom);
        foreach ($symptom_ids as $sid) {
            if (!is_numeric($sid)) continue;
            $s_score = getScore($conn, 'symptom_problem', 'symptom_problem_id', $sid);
            $stmt_s->execute([':na_id' => $nutrition_assessment_id, ':s_id' => $sid, ':s_score' => $s_score]);
        }
    }

    // 7. Update Status
    if (!empty($ref_screening_doc)) {
        $stmt_upd = $conn->prepare("UPDATE nutrition_screening SET screening_status='ประเมินต่อแล้ว', has_assessment=1, assessment_doc_no=:naf_doc WHERE doc_no=:ref");
        $stmt_upd->execute([':naf_doc' => $doc_no, ':ref' => $ref_screening_doc]);
    }

    $conn->commit();
    header("Location: patient_profile.php?hn=" . urlencode($hn) . "&an=" . urlencode($an));
    exit;
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo "<h3>เกิดข้อผิดพลาดในการบันทึกข้อมูล:</h3>";
    echo "Error: " . $e->getMessage();
    echo "<br><br><a href='javascript:history.back()'>กลับไปแก้ไขข้อมูล</a>";
}
