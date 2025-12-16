<?php
require 'connect.php';

$patients_hn = $_POST['patients_hn'];
$screen_date = $_POST['screen_date'];
$q1 = $_POST['q1'];
$q2 = $_POST['q2'];
$q3 = $_POST['q3'];
$q4 = $_POST['q4'];

$yes_count = 0;
if ($q1 == "yes") $yes_count++;
if ($q2 == "yes") $yes_count++;
if ($q3 == "yes") $yes_count++;
if ($q4 == "yes") $yes_count++;

$status = ($yes_count >= 2) ? "รอการประเมิน" : "ความเสี่ยงต่ำ";

try {
    $stmt = $conn->prepare("
        INSERT INTO nutrition_screening_history
        (patients_hn, screen_date, q1, q2, q3, q4, yes_count, screening_status)
        VALUES
        (:hn, :d, :q1, :q2, :q3, :q4, :yesc, :sts)
    ");

    $stmt->execute([
        ':hn' => $patients_hn,
        ':d' => $screen_date,
        ':q1' => $q1,
        ':q2' => $q2,
        ':q3' => $q3,
        ':q4' => $q4,
        ':yesc' => $yes_count,
        ':sts' => $status
    ]);

    $screenID = $conn->lastInsertId();

    if ($status == "รอการประเมิน") {
        header("Location: assessment_form.php?screeningId=" . $screenID);
    } else {
        header("Location: patient_profile.php?patientId=" . $patients_hn);
    }
    exit;
} catch (PDOException $e) {
    die("Error saving screening: " . $e->getMessage());
}
?>