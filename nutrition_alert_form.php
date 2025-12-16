<?php
require_once 'connect.php';

function h($string)
{
    return htmlspecialchars($string ?? "", ENT_QUOTES, 'UTF-8');
}

$hn = isset($_GET['hn']) ? $_GET['hn'] : '';
if (empty($hn)) die("ไม่พบรหัสผู้ป่วย (HN)");

// ดึงข้อมูลผู้ป่วย (Code เดิม)
try {
    $sql = "SELECT * FROM patients WHERE patients_hn = :hn";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':hn' => $hn]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) die("ไม่พบข้อมูลผู้ป่วย");

    // คำนวณอายุและวันที่ (เหมือนไฟล์แรก)
    $dob = new DateTime($patient['patients_dob']);
    $now = new DateTime();
    $age = $now->diff($dob);
    $age_text = $age->y . " ปี " . $age->m . " เดือน";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แบบประเมินภาวะโภชนาการ (Assessment)</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/nutrition_alert_form.css">
</head>

<body>
    <nav class="navbar">
        <div class="navbar-left">
            <a class="navbar-brand" href="index.php">
                <img src="img/logo_kph.jpg" class="brand-logo" alt="KPH Logo" onerror="this.style.display='none'">
                <div class="brand-text">
                    <span class="brand-title">ระบบประเมินภาวะโภชนาการ</span>
                    <span class="brand-subtitle">Nutrition Alert Form - โรงพยาบาลกำแพงเพชร</span>
                </div>
            </a>
        </div>
        <div class="navbar-right">
            <div class="user-profile" id="userDropdownTrigger">
                <div class="user-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="user-info">
                    <span class="user-name">เพชรลดา เชยเพ็ชร</span>
                    <span class="user-role">นักโภชนาการ</span>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem; color:#999; margin-left: 5px;"></i>
            </div>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-user-header"><small>ผู้ใช้งานระบบ</small><br><strong>เพชรลดา เชยเพ็ชร</strong>
                </div>
                <a href="#" class="dropdown-item"><i class="fa-regular fa-id-card"></i> ข้อมูลผู้ใช้งาน</a>
                <a href="#" class="dropdown-item"><i class="fa-solid fa-sliders"></i> ตั้งค่าระบบ</a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item text-danger" onclick="confirmLogout()"><i
                        class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="patient-banner">
        <div class="patient-container">
            <div class="patient-icon-box"><i class="fa-solid fa-user"></i></div>
            <div class="patient-details">
                <div class="section-header"><i class="fa-solid fa-hospital-user"></i> ข้อมูลผู้ป่วย</div>
                <div class="patient-grid">
                    <div class="info-item">
                        <span class="info-label">HN</span>
                        <span class="info-value"><?php echo h($patient['patients_hn']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">AN</span>
                        <span class="info-value"><?php echo h($patient['patients_an']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">ชื่อ - นามสกุล</span>
                        <span class="info-value"><?php echo h($patient['patients_name']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">อายุ</span>
                        <span class="info-value"><?php echo h($age_text); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">สิทธิการรักษา</span>
                        <span class="info-value"><?php echo h($patient['medical_rights']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">หอผู้ป่วย / เตียง</span>
                        <span class="info-value">
                            <?php echo h($patient['ward_name']); ?> /
                            <strong>เตียง <?php echo h($patient['bed_number']); ?></strong>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">วันที่ Admit</span>
                        <span class="info-value"><?php echo h($admit_date); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">เบอร์โทรศัพท์</span>
                        <span class="info-value"><?php echo h($patient['patients_phone']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content" style="padding: 20px; max-width: 1200px; margin: 0 auto;">

        <form class="form-card" id="assessmentForm" onsubmit="event.preventDefault(); saveAssessment();">
            <div class="section-title"><i class="fa-regular fa-id-card"></i> 1. ข้อมูลทั่วไปของผู้ป่วย</div>
            <div class="form-row">
                <div class="col-4">
                    <div class="form-group"><label class="form-label">ชื่อ - นามสกุล</label><input type="text"
                            class="form-control" value="นายสมชาย รักสุขภาพ" readonly></div>
                </div>
                <div class="col-2">
                    <div class="form-group"><label class="form-label">เพศ</label><select class="form-control">
                            <option>ชาย</option>
                            <option>หญิง</option>
                        </select></div>
                </div>
                <div class="col-2">
                    <div class="form-group"><label class="form-label">อายุ (ปี)</label><input type="text"
                            class="form-control" value="54" readonly></div>
                </div>
                <div class="col-4">
                    <div class="form-group"><label class="form-label">HN</label><input type="text"
                            class="form-control" value="6612345" readonly></div>
                </div>
            </div>
            <div class="form-row">
                <div class="col-5">
                    <div class="form-group">
                        <label class="form-label">วันที่รับ (Date of Admission)</label>
                        <input type="date" class="form-control" value="2023-10-10" readonly>
                    </div>
                </div>

                <div class="col-7">
                    <div class="form-group">
                        <label class="form-label">การวินิจฉัยโรคเบื้องต้น (Diagnosis)</label>
                        <input type="text" class="form-control" placeholder="ระบุการวินิจฉัยโรค..."
                            value="Pneumonia">
                    </div>
                </div>
            </div>

            <div class="form-row mt-3">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">ข้อมูลจาก (Source of Information)</label>
                        <div class="radio-group-container">
                            <div class="radio-wrapper-compact"
                                style="display: flex; align-items: center; gap: 15px;">

                                <label class="radio-label" style="margin:0; cursor: pointer;">
                                    <input type="radio" name="source_info" value="patient" checked
                                        onchange="toggleSourceInput()">
                                    ผู้ป่วย
                                </label>

                                <label class="radio-label" style="margin:0; cursor: pointer;">
                                    <input type="radio" name="source_info" value="relative"
                                        onchange="toggleSourceInput()">
                                    ญาติ
                                </label>

                                <label class="radio-label"
                                    style="margin:0; cursor: pointer; display: flex; align-items: center;">
                                    <input type="radio" name="source_info" value="other" id="sourceOther"
                                        onchange="toggleSourceInput()">
                                    &nbsp;อื่นๆ&nbsp;
                                    <input type="text" id="sourceOtherText" class="form-control input-inline-small"
                                        style="width: 200px; height: 30px;" placeholder="ระบุ..." disabled>
                                </label>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-divider"></div>

            <div class="section-title"><i class="fa-solid fa-ruler-vertical"></i> 2. ส่วนสูง/ ความยาวตัว</div>
            <div class="form-row">
                <div class="col-3">
                    <div class="form-group"><label class="form-label">1. วัดส่วนสูง (ซม.)</label><input
                            type="number" id="assessHeight" class="form-control" placeholder="ระบุ..."
                            oninput="calcAssessBMI()"></div>
                </div>
                <div class="col-3">
                    <div class="form-group"><label class="form-label">2. วัดความยาวตัว (ซม.)</label><input
                            type="number" class="form-control" placeholder="ระบุ..."></div>
                </div>
                <div class="col-3">
                    <div class="form-group"><label class="form-label">3. Arm span (ซม.)</label><input type="number"
                            class="form-control" placeholder="ระบุ..."></div>
                </div>
                <div class="col-3">
                    <div class="form-group"><label class="form-label">4. ญาติบอก (ซม.)</label><input type="number"
                            class="form-control" placeholder="ระบุ..."></div>
                </div>
            </div>
            <div class="section-divider"></div>

            <div class="section-title"><i class="fa-solid fa-weight-scale"></i> 3. น้ำหนักและค่าดัชนีมวลกาย</div>
            <div class="form-row">
                <div class="col-4">
                    <div class="form-group"><label class="form-label">น้ำหนัก (กก.)</label><input type="number"
                            id="assessWeight" class="form-control" placeholder="ระบุ..." oninput="calcAssessBMI()">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group"><label class="form-label">ดัชนีมวลกาย (BMI)</label><input type="text"
                            id="assessBMI" class="form-control bmi-box" readonly placeholder="รอคำนวณ..."></div>
                </div>
                <div class="col-4"></div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <div class="form-group"><label class="form-label">วิธีชั่งน้ำหนัก</label>
                        <div class="radio-group-container">
                            <div class="radio-wrapper"><label class="radio-label"><input type="radio"
                                        name="assessWeightMethod" value="lying"> ชั่งในท่านอน</label><label
                                    class="radio-label"><input type="radio" name="assessWeightMethod"
                                        value="standing"> ชั่งในท่ายืน</label><label class="radio-label"><input
                                        type="radio" name="assessWeightMethod" value="cannot">
                                    ชั่งไม่ได้</label><label class="radio-label"><input type="radio"
                                        name="assessWeightMethod" value="relative"> ญาติบอก</label></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <div class="reference-note"><strong>เกณฑ์การให้คะแนน BMI:</strong>
                        < 17.0 (2 คะแนน) | 17.0 - 18.0 (1 คะแนน) | 18.1 - 29.9 (0 คะแนน) | ≥ 30.0 (1 คะแนน) </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-person"></i> 4. รูปร่างของผู้ป่วย</div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="form-group"><label class="form-label">ลักษณะรูปร่าง</label>
                            <div class="radio-group-container">
                                <div class="radio-wrapper"><label class="radio-label"><input type="radio"
                                            name="bodyShape" value="2"> ผอมมาก</label><label
                                        class="radio-label"><input type="radio" name="bodyShape" value="1">
                                        ผอม</label><label class="radio-label"><input type="radio" name="bodyShape"
                                            value="1"> อ้วนมาก</label><label class="radio-label"><input type="radio"
                                            name="bodyShape" value="0"> ปกติ-อ้วนปานกลาง</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="reference-note"><strong>เกณฑ์คะแนน:</strong> ผอมมาก (2 คะแนน) | ผอม, อ้วนมาก (1
                            คะแนน) | ปกติ-อ้วนปานกลาง (0 คะแนน)</div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-arrow-trend-down"></i> 5. น้ำหนักเปลี่ยนใน 4
                    สัปดาห์</div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="form-group"><label class="form-label">ในช่วง 4 สัปดาห์ที่ผ่านมา
                                น้ำหนักมีการเปลี่ยนแปลงอย่างไร?</label>
                            <div class="radio-group-container">
                                <div class="radio-wrapper"><label class="radio-label"><input type="radio"
                                            name="weightChange" value="2"> ลดลง / ผอมลง</label><label
                                        class="radio-label"><input type="radio" name="weightChange" value="1">
                                        เพิ่มขึ้น / อ้วนขึ้น</label><label class="radio-label"><input type="radio"
                                            name="weightChange" value="0"> ไม่ทราบ</label><label
                                        class="radio-label"><input type="radio" name="weightChange" value="0">
                                        คงเดิม</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="reference-note"><strong>เกณฑ์คะแนน:</strong> ลดลง (2 คะแนน) | เพิ่มขึ้น (1
                            คะแนน) | ไม่ทราบ, คงเดิม (0 คะแนน)</div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-utensils"></i> 6. อาหารที่กินในช่วง 2
                    สัปดาห์ที่ผ่านมา</div>
                <div class="form-row">
                    <div class="col-6">
                        <div class="form-group"><label class="form-label">6.1 ลักษณะอาหาร</label>
                            <div class="radio-group-container" style="height: auto; padding: 10px 15px;">
                                <div class="radio-wrapper"
                                    style="flex-direction: column; gap: 10px; align-items: flex-start;"><label
                                        class="radio-label"><input type="radio" name="foodType" value="2"> อาหารน้ำๆ
                                        (2 คะแนน)</label><label class="radio-label"><input type="radio"
                                            name="foodType" value="2"> อาหารเหลวๆ (2 คะแนน)</label><label
                                        class="radio-label"><input type="radio" name="foodType" value="1">
                                        อาหารนุ่มกว่าปกติ (1 คะแนน)</label><label class="radio-label"><input
                                            type="radio" name="foodType" value="0"> อาหารเหมือนปกติ (0
                                        คะแนน)</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group"><label class="form-label">6.2 ปริมาณที่กิน</label>
                            <div class="radio-group-container" style="height: auto; padding: 10px 15px;">
                                <div class="radio-wrapper"
                                    style="flex-direction: column; gap: 10px; align-items: flex-start;"><label
                                        class="radio-label"><input type="radio" name="foodQty" value="2"> กินน้อยมาก
                                        (2 คะแนน)</label><label class="radio-label"><input type="radio"
                                            name="foodQty" value="1"> กินน้อยลง (1 คะแนน)</label><label
                                        class="radio-label"><input type="radio" name="foodQty" value="0"> กินมากขึ้น
                                        (0 คะแนน)</label><label class="radio-label"><input type="radio"
                                            name="foodQty" value="0"> กินเท่าปกติ (0 คะแนน)</label></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-wheelchair"></i> 7. ความสามารถในการเข้าถึงอาหาร
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="form-group"><label class="form-label">สถานะการเคลื่อนไหว</label>
                            <div class="radio-group-container">
                                <div class="radio-wrapper"><label class="radio-label"><input type="radio"
                                            name="foodAccess" value="2"> นอนติดเตียง</label><label
                                        class="radio-label"><input type="radio" name="foodAccess" value="1">
                                        ต้องมีผู้ช่วยบ้าง</label><label class="radio-label"><input type="radio"
                                            name="foodAccess" value="0"> นั่งๆ นอนๆ</label><label
                                        class="radio-label"><input type="radio" name="foodAccess" value="0">
                                        ปกติ</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="reference-note"><strong>เกณฑ์คะแนน:</strong> นอนติดเตียง (2) | ต้องมีผู้ช่วย (1)
                            | นั่งๆ นอนๆ, ปกติ (0)</div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-notes-medical"></i> 8. อาการต่อเนื่อง > 2 สัปดาห์
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <div class="form-group"
                            style="margin-bottom: 20px; border-bottom: 1px dashed #ddd; padding-bottom: 15px;">
                            <label class="form-label"
                                style="font-weight: 700; color: #0D47A1;">ปัญหาทางการเคี้ยว/กลืนอาหาร</label>
                            <div class="radio-group-container" style="min-height: 0;">
                                <div class="radio-wrapper" style="flex-direction: column; align-items: flex-start;">
                                    <label class="radio-label"><input type="checkbox" name="symptom_choke_assess"
                                            value="2"> สำลัก (2 คะแนน)</label>
                                    <label class="radio-label"><input type="checkbox" name="symptom_swallow_assess"
                                            value="2"> เคี้ยว/กลืนลำบาก/ได้อาหารทางสายยาง (2 คะแนน)</label>
                                    <label class="radio-label"><input type="checkbox" name="symptom_swallow_normal"
                                            value="0"> กลืนได้ปกติ (0 คะแนน)</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group"
                            style="margin-bottom: 20px; border-bottom: 1px dashed #ddd; padding-bottom: 15px;">
                            <label class="form-label"
                                style="font-weight: 700; color: #0D47A1;">ปัญหาระบบทางเดินอาหาร</label>
                            <div class="radio-group-container" style="min-height: 0;">
                                <div class="radio-wrapper" style="flex-direction: column; align-items: flex-start;">
                                    <label class="radio-label"><input type="checkbox" name="symptom_diarrhea_assess"
                                            value="2"> ท้องเสีย (2 คะแนน)</label>
                                    <label class="radio-label"><input type="checkbox" name="symptom_pain_assess"
                                            value="2"> ปวดท้อง (2 คะแนน)</label>
                                    <label class="radio-label"><input type="checkbox"
                                            name="symptom_digestive_normal" value="0"> ปกติ (0 คะแนน)</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label"
                                style="font-weight: 700; color: #0D47A1;">ปัญหาระหว่างกินอาหาร</label>
                            <div class="radio-group-container" style="min-height: 0;">
                                <div class="radio-wrapper" style="flex-direction: column; align-items: flex-start;">
                                    <label class="radio-label"><input type="checkbox" name="symptom_vomit_assess"
                                            value="2"> อาเจียน (2 คะแนน)</label>
                                    <label class="radio-label"><input type="checkbox" name="symptom_nausea_assess"
                                            value="2"> คลื่นไส้ (2 คะแนน)</label>
                                    <label class="radio-label"><input type="checkbox" name="symptom_eating_normal"
                                            value="0"> ปกติ (0 คะแนน)</label>
                                </div>
                            </div>
                        </div>
                        <div class="reference-note" style="margin-top: 10px;">* หมายเหตุ: เลือกได้หลายข้อ
                            หากมีอาการใดอาการหนึ่ง จะได้คะแนนตามเกณฑ์</div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-bed-pulse"></i> 9. โรคที่เป็นอยู่ (เลือกได้หลายข้อ)
                </div>
                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <div class="form-group h-100">
                            <label class="form-label"
                                style="color: #F57C00; border-bottom: 2px solid #FFE0B2; padding-bottom: 5px; margin-bottom: 15px;"><i
                                    class="fa-solid fa-circle-exclamation"></i> โรคที่มีความรุนแรงน้อยถึงปานกลาง (3
                                คะแนน)</label>
                            <div class="checkbox-list">
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="dm"> DM (เบาหวาน)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="ckd"> CKD-ESRD (ไตเรื้อรัง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="chf"> Chronic heart failure (หัวใจล้มเหลวเรื้อรัง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="copd"> COPD (ปอดอุดกั้นเรื้อรัง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="septicemia"> Septicemia (ติดเชื้อในกระแสเลือด)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="cancer"> Solid cancer (มะเร็งทั่วไป)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="hip_fracture"> Hip fracture (ข้อสะโพกหัก)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="burn"> >= 2 of burn (แผลไฟไหม้ระดับ 2 ขึ้นไป)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="liver"> CLD/Cirrhosis/Hepatic encaph (ตับเรื้อรัง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate"
                                        value="head_injury"> Severe head injury (บาดเจ็บที่ศีรษะรุนแรง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_moderate_other"
                                        value="other" id="diseaseModOther"
                                        onchange="toggleDiseaseInput('diseaseModOther', 'diseaseModOtherText')">
                                    อื่นๆ (ระบุ) <input type="text" id="diseaseModOtherText"
                                        class="form-control input-inline-small" style="width: 150px!important;"
                                        disabled></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group h-100">
                            <label class="form-label"
                                style="color: #c62828; border-bottom: 2px solid #ffcdd2; padding-bottom: 5px; margin-bottom: 15px;"><i
                                    class="fa-solid fa-triangle-exclamation"></i> โรคที่มีความรุนแรงมาก (6
                                คะแนน)</label>
                            <div class="checkbox-list">
                                <label class="checkbox-item"><input type="checkbox" name="disease_severe"
                                        value="pneumonia"> Severe pneumonia (ปอดบวมขั้นรุนแรง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_severe"
                                        value="critical"> Critically Ill (ผู้ป่วยวิกฤต)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_severe"
                                        value="fracture"> Multiple fracture (กระดูกหักหลายตำแหน่ง)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_severe"
                                        value="stroke"> Stroke/CVA (อัมพาต)</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_severe"
                                        value="hematologic"> Malignant hematologic disease/Bone marrow
                                    transplant</label>
                                <label class="checkbox-item"><input type="checkbox" name="disease_severe_other"
                                        value="other" id="diseaseSevOther"
                                        onchange="toggleDiseaseInput('diseaseSevOther', 'diseaseSevOtherText')">
                                    อื่นๆ (ระบุ) <input type="text" id="diseaseSevOtherText"
                                        class="form-control input-inline-small" style="width: 150px!important;"
                                        disabled></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="section-title"><i class="fa-solid fa-square-poll-vertical"></i> สรุปผลการประเมิน (NAF
                    Score)</div>
                <div class="summary-card-container">
                    <div class="summary-card risk-color-neutral" id="scoreCardBox">
                        <div class="summary-card-title">คะแนนรวม</div>
                        <div class="summary-card-value" id="realTimeScore">0</div>
                        <div class="summary-card-desc">คะแนน</div>
                    </div>
                    <div class="summary-card risk-color-neutral" id="riskCardBox">
                        <div class="summary-card-title">ระดับความเสี่ยง</div>
                        <div class="summary-card-value" style="font-size: 2rem;" id="realTimeRiskLabel">-</div>
                        <div class="summary-card-desc" id="realTimeRiskGroup">รอผลการประเมิน</div>
                    </div>
                </div>
                <div
                    style="background-color: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 25px;">
                    <strong>คำแนะนำ:</strong> <span
                        id="realTimeRiskDetail">กรุณากรอกข้อมูลให้ครบถ้วนเพื่อดูผลลัพธ์</span>
                </div>

                <div class="form-row">
                    <div class="col-12" style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 15px;">
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-check-circle"></i> บันทึกและเสร็จสิ้น
                            </button>
                            <button type="button" class="btn-outline" onclick="window.print()">
                                <i class="fa-solid fa-file-pdf"></i> พิมพ์ PDF
                            </button>
                        </div>
                    </div>
                </div>
        </form>
    </div>

    <script>
        // โค้ด JS คำนวณคะแนน (ยกมาจากไฟล์เดิม)
        document.addEventListener('DOMContentLoaded', initRealTimeCalculation);

        function initRealTimeCalculation() {
            const allInputs = document.querySelectorAll('#assessmentForm input');
            allInputs.forEach(input => {
                input.addEventListener('change', updateRealTimeScore);
                input.addEventListener('input', updateRealTimeScore);
            });
        }

        function calcAssessBMI() {
            const weight = parseFloat(document.getElementById('assessWeight').value);
            const height = parseFloat(document.getElementById('assessHeight').value);
            const bmiBox = document.getElementById('assessBMI');
            if (weight > 0 && height > 0) {
                const hM = height / 100;
                const bmi = weight / (hM * hM);
                bmiBox.value = bmi.toFixed(2);
            } else {
                bmiBox.value = '';
            }
            updateRealTimeScore();
        }

        function getScore() {
            let totalScore = 0;
            // 1. BMI Logic
            const bmiVal = parseFloat(document.getElementById('assessBMI').value);
            if (!isNaN(bmiVal)) {
                if (bmiVal < 17.0) totalScore += 2;
                else if (bmiVal >= 17.0 && bmiVal <= 18.0) totalScore += 1;
                else if (bmiVal >= 30.0) totalScore += 1;
            }
            // ... (ใส่ Logic ส่วนอื่นๆ ตามไฟล์เดิม) ...
            return totalScore;
        }

        function updateRealTimeScore() {
            const score = getScore();
            document.getElementById('realTimeScore').innerText = score;

            const label = document.getElementById('realTimeRiskLabel');
            if (score <= 5) label.innerText = "NAF A (Normal)";
            else if (score <= 10) label.innerText = "NAF B (Moderate)";
            else label.innerText = "NAF C (Severe)";
        }

        function saveAssessment() {
            // ส่งข้อมูลบันทึก (AJAX)
            alert('บันทึกข้อมูลการประเมินเรียบร้อย');
            window.location.href = 'index.php'; // หรือกลับหน้า Profile
        }
    </script>
</body>

</html>