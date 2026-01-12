<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่ามีการส่งข้อมูลแบบ POST มาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. รับค่าพื้นฐาน
        $hn = $_POST['hn'] ?? '';
        $an = $_POST['an'] ?? '';
        $redirect_to_naf = $_POST['redirect_to_naf'] ?? 'false';

        if (empty($hn) || empty($an)) {
            die("Error: ไม่พบข้อมูล HN หรือ AN");
        }

        // 2. คำนวณลำดับที่ (Sequence) ใหม่เพื่อความแม่นยำ
        $stmt_seq = $conn->prepare("SELECT MAX(screening_seq) as max_seq FROM nutrition_screening WHERE admissions_an = :an");
        $stmt_seq->execute([':an' => $an]);
        $next_seq = ($stmt_seq->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0) + 1;

        // 3. สร้างเลข Doc No. (Format: SPENT-{HN}-{SEQ})
        // ใช้ str_pad เพื่อไม่ต้องพึ่งค่าจากหน้าฟอร์ม กันความผิดพลาด
        $doc_no = 'SPENT-' . $hn . '-' . $next_seq;

        // 4. คำนวณผลประเมิน
        $total_score = ($_POST['q1'] ?? 0) + ($_POST['q2'] ?? 0) + ($_POST['q3'] ?? 0) + ($_POST['q4'] ?? 0);
        $result_text = ($total_score >= 2) ? 'มีความเสี่ยง' : 'ปกติ';

        // 5. SQL Insert
        $sql_insert = "
            INSERT INTO nutrition_screening (
                doc_no, admissions_an, patients_hn, screening_datetime, screening_seq,
                initial_diagnosis, present_weight, normal_weight, height, bmi, weight_method,
                q1_weight_loss, q2_eat_less, q3_bmi_abnormal, q4_critical,
                nutrition_screening_result, notes, assessor_name
            ) VALUES (
                :doc_no, :an, :hn, NOW(), :seq,
                :diagnosis, :weight, :normal_weight, :height, :bmi, :method,
                :q1, :q2, :q3, :q4,
                :result, :notes, :assessor
            )
        ";

        $stmt_ins = $conn->prepare($sql_insert);
        $stmt_ins->execute([
            ':doc_no' => $doc_no,
            ':an' => $an,
            ':hn' => $hn,
            ':seq' => $next_seq,
            ':diagnosis' => $_POST['initial_diagnosis'],
            ':weight' => $_POST['present_weight'],
            ':normal_weight' => !empty($_POST['normal_weight']) ? $_POST['normal_weight'] : null,
            ':height' => $_POST['height'],
            ':bmi' => $_POST['bmi'],
            ':method' => $_POST['weightMethod'],
            ':q1' => $_POST['q1'],
            ':q2' => $_POST['q2'],
            ':q3' => $_POST['q3'],
            ':q4' => $_POST['q4'],
            ':result' => $result_text,
            ':notes' => $_POST['notes'],
            ':assessor' => $_POST['assessor_name']
        ]);

        // 6. การ Redirect (ขึ้นอยู่กับปุ่มที่กดมา)
        if ($redirect_to_naf === 'true') {
            // ถ้ากด "ประเมิน NAF ต่อทันที"
            header("Location: nutrition_assessment_form.php?hn=$hn&an=$an");
            exit();
        } else {
            // ถ้ากด "บันทึกและกลับหน้าหลัก" หรือ "ไว้ทำทีหลัง"
            echo "<script>
                alert('บันทึกข้อมูลการคัดกรองเรียบร้อย');
                window.location.href = 'patient_profile.php?hn=$hn';
            </script>";
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    // ถ้าเข้าไฟล์นี้โดยตรง (ไม่ใช่ POST) ให้ดีดกลับ
    header("Location: index.php");
    exit();
}
