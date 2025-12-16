<?php
require 'connect.php';

$patients_hn = $_POST['patients_hn'];
$screening_id = $_POST['screening_id'];

$height = $_POST['height'];
$weight = $_POST['weight'];
$bmi = $_POST['bmi'];
$bmi_score = $_POST['bmi_score'];

$patient_shape = $_POST['patient_shape'];
$weight_change = $_POST['weight_change'];
$food_type = $_POST['food_type'];
$food_amount = $_POST['food_amount'];
$food_access = $_POST['food_access'];

$total_score = $_POST['total_score'];
$risk_level = $_POST['risk_level'];

// บันทึกข้อมูลแบบประเมิน
$stmt = $conn->prepare("
    INSERT INTO nutrition_assessment_history
    (patients_hn, nutrition_screening_history_id, height_measure, weight, bmi, bmi_score,
     patient_shape_id, weight_change_id, food_type_id, food_amount_id, food_access_id,
     total_score, risk_level)
    VALUES
    (:hn, :sid, :h, :w, :bmi, :bmis, :shape, :chg, :ft, :fa, :acc, :t, :rl)
");

$stmt->execute([
    ':hn' => $patients_hn,
    ':sid' => $screening_id,
    ':h' => $height,
    ':w' => $weight,
    ':bmi' => $bmi,
    ':bmis' => $bmi_score,
    ':shape' => $patient_shape,
    ':chg' => $weight_change,
    ':ft' => $food_type,
    ':fa' => $food_amount,
    ':acc' => $food_access,
    ':t' => $total_score,
    ':rl' => $risk_level
]);

$assessment_id = $conn->lastInsertId();

/* ---------------- Symptoms ---------------- */
if (!empty($_POST['symptoms'])) {
    foreach ($_POST['symptoms'] as $sid) {
        $stmt = $conn->prepare("
            INSERT INTO assessment_symptom
            (nutrition_assessment_history_id, symptom_catalog_id)
            VALUES (?,?)
        ");
        $stmt->execute([$assessment_id, $sid]);
    }
}

/* ---------------- Diseases ---------------- */
if (!empty($_POST['diseases'])) {
    foreach ($_POST['diseases'] as $did) {
        $stmt = $conn->prepare("
            INSERT INTO assessment_diseases
            (nutrition_assessment_history_id, disease_id, disease_score)
            VALUES (?,?,?)
        ");

        // moderate = 3, severe = 6
        $score = ($did[0] == "M") ? 3 : 6;

        $stmt->execute([$assessment_id, $did, $score]);
    }
}

/* ---------------- Redirect ---------------- */
header("Location: patient_profile.php?patientId=" . $patients_hn);
exit;
