<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

// 1. รับค่าจาก URL
$hn = $_GET['hn'] ?? '';
$an = $_GET['an'] ?? '';
$ref_screening = $_GET['ref_screening'] ?? '';

// 2. ตรวจสอบค่าว่าง
if (empty($hn) || empty($an)) {
    die("Error: ไม่พบข้อมูล HN หรือ AN");
}

try {
    // 3. ดึงข้อมูลผู้ป่วย
    $sql_patient = "
        SELECT 
            patients.patients_hn, patients.patients_firstname, patients.patients_lastname, 
            patients.patients_dob, patients.patients_congenital_disease, patients.patients_phone,
            admissions.admissions_an, admissions.admit_datetime, admissions.bed_number,
            wards.ward_name, doctor.doctor_name, health_insurance.health_insurance_name
        FROM patients
        JOIN admissions ON patients.patients_id = admissions.patients_id
        LEFT JOIN wards ON admissions.ward_id = wards.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        LEFT JOIN health_insurance ON admissions.health_insurance_id = health_insurance.health_insurance_id
        WHERE patients.patients_hn = :hn AND admissions.admissions_an = :an
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql_patient);
    $stmt->execute([':hn' => $hn, ':an' => $an]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) die("ไม่พบข้อมูลผู้ป่วย");

    // 4. คำนวณอายุ
    $age = '-';
    if (!empty($patient['patients_dob'])) {
        $dob = new DateTime($patient['patients_dob']);
        $now = new DateTime();
        $diff = $now->diff($dob);
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน';
    }

    // 5. แปลงวันที่ Admit (เพิ่มเวลา)
    $admit_date = '-';
    if (!empty($patient['admit_datetime'])) {
        $dt = new DateTime($patient['admit_datetime']);
        $thai_year = $dt->format('Y') + 543;
        // แสดงผล: 12/04/2567 10:30 น.
        $admit_date = $dt->format('d/m/') . $thai_year . ' ' . $dt->format('H:i') . ' น.';
    }

    // 6. สร้างเลขที่เอกสาร NAF (Running Number)
    $stmt_seq = $conn->prepare("SELECT COUNT(*) as count FROM nutrition_assessment WHERE patients_hn = :hn");
    $stmt_seq->execute([':hn' => $hn]);
    $count = $stmt_seq->fetch(PDO::FETCH_ASSOC)['count'];
    $naf_seq = $count + 1;
    $doc_no_show = 'NAF-' . $patient['patients_hn'] . '-' . str_pad($naf_seq, 3, '0', STR_PAD_LEFT);

    // ---------------------------------------------------------
    // 7. ดึง Master Data (สำหรับตัวเลือกต่างๆ)
    // ---------------------------------------------------------
    function fetchMasterData($conn, $table, $id_col)
    {
        try {
            $sql = "SELECT * FROM $table ORDER BY $id_col ASC";
            return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    $patient_shapes = fetchMasterData($conn, 'patient_shape', 'patient_shape_id');
    $weight_changes = fetchMasterData($conn, 'weight_change_4_weeks', 'weight_change_4_weeks_id');
    $food_types = fetchMasterData($conn, 'food_type', 'food_type_id');
    $food_amounts = fetchMasterData($conn, 'food_amount', 'food_amount_id');
    $symptoms = fetchMasterData($conn, 'symptom_problem', 'symptom_problem_id');
    $weight_options = fetchMasterData($conn, 'weight_option', 'weight_option_id');
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบประเมินภาวะโภชนาการ (NAF) | โรงพยาบาลกำแพงเพชร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nutrition_alert_form.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-md navbar-light fixed-top navbar-custom border-bottom">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="img/logo_kph.jpg" class="brand-logo mr-2 d-none d-sm-block" alt="Logo"
                    onerror="this.style.display='none'">
                <div class="brand-text">
                    <h1>ระบบประเมินภาวะโภชนาการ</h1>
                    <small>Nutrition Alert System (NAS)</small>
                </div>
            </a>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link p-0" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false" style="min-width: 250px;">
                        <div class="user-profile-btn">
                            <div class="user-avatar">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                            <div class="user-info d-none d-md-block" style="flex-grow: 1;">
                                <div class="user-name">เพชรลดา เชยเพ็ชร</div>
                                <div class="user-role">นักโภชนาการ</div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-muted mr-2" style="font-size: 0.8rem;"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2" aria-labelledby="userDropdown"
                        style="border-radius: 12px; min-width: 250px;">

                        <div class="dropdown-header bg-light border-bottom py-3">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar mr-3" style="width: 42px; height: 42px; font-size: 1.2rem;">
                                    <i class="fa-solid fa-user-doctor"></i>
                                </div>
                                <div style="line-height: 1.3;">
                                    <strong class="text-dark d-block" style="font-size: 0.95rem;">เพชรลดา
                                        เชยเพ็ชร</strong>
                                    <small class="text-muted">นักโภชนาการชำนาญการ</small>
                                    <br>
                                    <span class="badge badge-info mt-1"
                                        style="font-weight: normal; font-size: 0.7rem;">License: DT-66099</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-2">
                            <h6 class="dropdown-header text-uppercase text-muted small font-weight-bold pl-2 mb-1">
                                งานของฉัน</h6>
                            <a class="dropdown-item py-2 rounded d-flex justify-content-between align-items-center"
                                href="#">
                                <span><i class="fa-solid fa-clipboard-user mr-2 text-primary"
                                        style="width:20px; text-align:center;"></i> ผู้ป่วยที่รับผิดชอบ</span>
                                <span class="badge badge-danger badge-pill">5</span>
                            </a>
                            <a class="dropdown-item py-2 rounded" href="#">
                                <span><i class="fa-solid fa-comment-medical mr-2 text-success"
                                        style="width:20px; text-align:center;"></i> จัดการข้อความด่วน</span>
                            </a>
                            <a class="dropdown-item py-2 rounded" href="#">
                                <span><i class="fa-solid fa-clock-rotate-left mr-2 text-secondary"
                                        style="width:20px; text-align:center;"></i> ประวัติการประเมิน</span>
                            </a>
                        </div>

                        <div class="dropdown-divider m-0"></div>

                        <div class="p-2">
                            <a class="dropdown-item py-2 rounded" href="#">
                                <i class="fa-solid fa-file-signature mr-2 text-warning"
                                    style="width:20px; text-align:center;"></i> ตั้งค่าลายเซ็น (E-Sign)
                            </a>
                        </div>

                        <div class="dropdown-divider m-0"></div>

                        <div class="p-2">
                            <a class="dropdown-item py-2 rounded text-danger" href="#" onclick="confirmLogout()">
                                <i class="fa-solid fa-right-from-bracket mr-2"
                                    style="width:20px; text-align:center;"></i>
                                ออกจากระบบ
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid mt-3 pt-5 pb-5 px-lg-5">

        <form id="nafForm" method="POST" action="nutrition_assessment_save.php">
            <input type="hidden" name="hn" value="<?= htmlspecialchars($hn) ?>">
            <input type="hidden" name="an" value="<?= htmlspecialchars($an) ?>">
            <input type="hidden" name="doc_no" value="<?= htmlspecialchars($doc_no_show) ?>">
            <input type="hidden" name="naf_seq" value="<?= htmlspecialchars($naf_seq) ?>">
            <input type="hidden" name="ref_screening_doc" value="<?= htmlspecialchars($ref_screening) ?>">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-auto">
                            <div class="patient-icon-box"><i class="fa-solid fa-user-injured"></i></div>
                        </div>
                        <div class="col">
                            <h5 class="text-primary-custom font-weight-bold mb-3 border-bottom pb-2 d-inline-block">
                                <i class="fa-solid fa-hospital-user mr-2"></i>ข้อมูลผู้ป่วย
                            </h5>
                            <div class="row">
                                <div class="col-6 col-md-3 col-lg-2 mb-3">
                                    <small class="text-muted d-block">HN</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($patient['patients_hn'] ?? '-') ?></span>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2 mb-3">
                                    <small class="text-muted d-block">AN</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($patient['admissions_an'] ?? '-') ?></span>
                                </div>
                                <div class="col-12 col-md-6 col-lg-2 mb-3">
                                    <small class="text-muted d-block">ชื่อ-สกุล</small>
                                    <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">
                                        <?php
                                        $fname = $patient['patients_firstname'] ?? '';
                                        $lname = $patient['patients_lastname'] ?? '';
                                        echo htmlspecialchars($fname . ' ' . $lname);
                                        ?>
                                    </span>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2 mb-3">
                                    <small class="text-muted d-block">อายุ</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($age) ?></span>
                                </div>
                                <div class="col-6 col-md-8 col-lg-2 mb-3">
                                    <small class="text-muted d-block">สิทธิ</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($patient['health_insurance_name'] ?? '-') ?></span>
                                </div>
                                <div class="col-12 col-md-6 col-lg-2 mb-3">
                                    <small class="text-muted d-block">แพทย์</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($patient['doctor_name'] ?? '-') ?></span>
                                </div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3">
                                    <small class="text-muted d-block">หอผู้ป่วย / เตียง</small>
                                    <span class="font-weight-bold">
                                        <?php
                                        $ward = $patient['ward_name'] ?? '-';
                                        $bed = $patient['bed_number'] ?? '-';
                                        echo htmlspecialchars($ward . ' / ' . $bed);
                                        ?>
                                    </span>
                                </div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3">
                                    <small class="text-muted d-block">วันที่ Admit</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($admit_date) ?></span>
                                </div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3">
                                    <small class="text-muted d-block">เบอร์โทรศัพท์</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($patient['patients_phone'] ?? '-') ?></span>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3 mb-3">
                                    <small class="text-muted d-block">โรคประจำตัว</small>
                                    <span class="font-weight-bold"><?= htmlspecialchars($patient['patients_congenital_disease'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" onclick="window.location.href='patient_profile.php?hn=<?= htmlspecialchars($hn) ?>'">
                    <i class="fa-solid fa-chevron-left mr-1"></i> ย้อนกลับ
                </button>
            </div>

            <div class="card form-card mb-5">
                <div class="form-header-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark" style="color: #33691e;">แบบประเมินภาวะโภชนาการ (NAF)</h4>
                            <small class="text-muted">Nutrition Alert Form (กรมอนามัย)</small>
                        </div>
                        <div class="text-right">
                            <span class="badge p-2" style="background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; font-weight: 500; font-size: 0.85rem;">
                                No.: <?= htmlspecialchars($doc_no_show) ?>
                            </span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-2 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ครั้งที่</span></div>
                                <input type="text" class="form-control text-center font-weight-bold text-primary" value="<?= htmlspecialchars($naf_seq) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">วันที่</span></div>
                                <input type="text" class="form-control text-center" value="<?= date('d/m/') . (date('Y') + 543) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">เวลา</span></div>
                                <input type="text" class="form-control text-center" value="<?= date('H:i') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ผู้ประเมิน</span></div>
                                <input type="text" class="form-control text-center text-primary" name="assessor_name" value="เพชรลดา เชยเพ็ชร" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">

                    <div class="form-group mb-4">
                        <label class="section-label">1. การวินิจฉัยเบื้องต้น (Provisional Diagnosis)</label>
                        <input type="text" class="form-control" name="initial_diagnosis" placeholder="ระบุการวินิจฉัยโรค...">
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <label class="section-label">2. ข้อมูลได้จาก (Source of Information)</label>
                        <div class="d-flex align-items-center">
                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="source1" name="infoSource" class="custom-control-input"
                                    value="patient" checked onchange="toggleOtherSource()">
                                <label class="custom-control-label" for="source1">ผู้ป่วย</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="source2" name="infoSource" class="custom-control-input"
                                    value="relative" onchange="toggleOtherSource()">
                                <label class="custom-control-label" for="source2">ญาติ</label>
                            </div>
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="custom-control custom-radio custom-control-inline mr-2">
                                    <input type="radio" id="source3" name="infoSource" class="custom-control-input"
                                        value="other" onchange="toggleOtherSource()">
                                    <label class="custom-control-label" for="source3">อื่นๆ</label>
                                </div>
                                <input type="text" class="form-control form-control-sm" id="otherSourceText"
                                    placeholder="ระบุอื่นๆ..." disabled style="max-width: 300px;">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <label class="section-label">3. สัดส่วนร่างกาย (Anthropometry)</label>
                        <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>
                            กรุณากรอกข้อมูลส่วนสูง/ความยาวตัว
                            อย่างน้อย 1 ช่อง</p>
                        <div class="row" id="anthroSection">
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">ส่วนสูง (Height)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input" id="anthroHeight"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">วัดความยาวตัว (Length)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input" id="anthroLength"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">Arm Span</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input" id="anthroArmSpan"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">ญาติบอก (Reported)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input" id="anthroReported"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>
                            <div id="anthroAlert" class="alert alert-danger py-2 d-none" role="alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i> กรุณาระบุข้อมูลส่วนสูง/ความยาวตัว อย่างน้อย
                                1 ช่อง
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="form-group mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="section-label mb-0">4. น้ำหนักและค่าดัชนีมวลกาย (Weight & BMI)</label>

                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="unknownWeight"
                                        onchange="toggleWeightMode()">
                                    <label class="custom-control-label text-danger font-weight-bold" for="unknownWeight">
                                        ไม่ทราบน้ำหนัก (ประเมินด้วยผลเลือด)
                                    </label>
                                </div>
                            </div>

                            <div id="standardWeightSection" class="fade-in">
                                <div class="row">
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <label class="small text-muted font-weight-bold">น้ำหนัก (Weight)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" class="form-control" id="currentWeight"
                                                placeholder="0.0" oninput="calculateBMI()">
                                            <div class="input-group-append"><span class="input-group-text">กก.</span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <label class="small text-muted font-weight-bold">ดัชนีมวลกาย (BMI)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bmi-display-box" id="bmiValue" value="-"
                                                readonly>
                                            <div class="input-group-append">
                                                <span class="input-group-text small" id="bmiScoreText"
                                                    style="background-color: #e9ecef; font-weight: 500;">Score: 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="small text-muted font-weight-bold mb-1">วิธีการชั่งน้ำหนัก (Method)</label>
                                    <div class="radio-group-container">
                                        <?php if (!empty($weight_options)): ?>
                                            <?php foreach ($weight_options as $wo): ?>
                                                <div class="custom-control custom-radio custom-control-inline mr-4">
                                                    <input type="radio"
                                                        id="wo_<?= $wo['weight_option_id'] ?>"
                                                        name="weight_option_id"
                                                        class="custom-control-input score-calc"
                                                        value="<?= $wo['weight_option_id'] ?>"
                                                        data-score="<?= $wo['weight_option_score'] ?? 0 ?>"
                                                        onchange="calculateScore()">
                                                    <label class="custom-control-label" for="wo_<?= $wo['weight_option_id'] ?>">
                                                        <?= htmlspecialchars($wo['weight_option_label'] ?? $wo['weight_option_name']) ?>
                                                        <span class="text-muted small">(<?= $wo['weight_option_score'] ?? 0 ?> คะแนน)</span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-danger small">ไม่พบข้อมูลตัวเลือก (weight_option)</p>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="fas fa-info-circle mr-1"></i> <strong>เกณฑ์ BMI:</strong> &lt; 17 (2 คะแนน), 17-18 (1 คะแนน), 18.1-29.9 (0 คะแนน), &gt; 30 (1 คะแนน)
                                    </small>
                                </div>
                            </div>

                            <div id="labSection" class="hidden-section fade-in">

                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="lab-choice-card inactive" id="cardAlbumin"
                                            onclick="selectLab('albumin')">

                                            <input type="radio" id="useAlbumin" name="labChoice" value="albumin"
                                                class="d-none" onchange="toggleLabInputs()">

                                            <div class="lab-header">
                                                <i class="fas fa-vial text-primary mr-2"></i> 1. Albumin
                                                <span class="lab-unit">(g/dl)</span>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="small text-muted mb-1">ระบุค่าผลเลือด:</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.1" class="form-control" id="valAlbumin"
                                                        placeholder="เช่น 3.2" disabled oninput="calculateLabScore()"
                                                        onclick="event.stopPropagation()">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text bg-white text-muted">g/dl</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <table class="ref-table-clean">
                                                    <thead>
                                                        <tr>
                                                            <th>เกณฑ์ (Criteria)</th>
                                                            <th class="text-right">คะแนน</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>&le; 2.5</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-danger">3</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>2.6 - 2.9</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-warning">2</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>3.0 - 3.5</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-primary">1</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>&gt; 3.5</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-muted">0</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="lab-choice-card inactive" id="cardTLC" onclick="selectLab('tlc')">

                                            <input type="radio" id="useTLC" name="labChoice" value="tlc" class="d-none"
                                                onchange="toggleLabInputs()">

                                            <div class="lab-header">
                                                <i class="fas fa-microscope text-primary mr-2"></i> 2. TLC
                                                <span class="lab-unit">(cells/mm³)</span>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="small text-muted mb-1">ระบุค่าผลเลือด:</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="valTLC"
                                                        placeholder="เช่น 1200" disabled oninput="calculateLabScore()"
                                                        onclick="event.stopPropagation()">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text bg-white text-muted">cells</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <table class="ref-table-clean">
                                                    <thead>
                                                        <tr>
                                                            <th>เกณฑ์ (Criteria)</th>
                                                            <th class="text-right">คะแนน</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>&le; 1,000</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-danger">3</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>1,001 - 1,200</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-warning">2</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>1,201 - 1,500</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-primary">1</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td>&gt; 1,500</td>
                                                            <td class="text-right"><span
                                                                    class="score-badge text-muted">0</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12 text-right">
                                        <div class="d-inline-block px-3 py-2 bg-white border rounded shadow-sm">
                                            <small class="text-muted mr-2">คะแนนจากผลเลือด (Lab Score):</small>
                                            <span class="font-weight-bold text-primary h5 m-0" id="labScoreText">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="section-label">5. รูปร่างของผู้ป่วย (Body Shape)</label>
                                <div class="radio-group-container">
                                    <?php if (!empty($patient_shapes)): ?>
                                        <?php foreach ($patient_shapes as $row): ?>
                                            <div class="custom-control custom-radio custom-control-inline mr-4 mb-2">
                                                <input type="radio"
                                                    id="shape_<?= $row['patient_shape_id'] ?>"
                                                    name="patient_shape_id"
                                                    value="<?= $row['patient_shape_id'] ?>"
                                                    data-score="<?= $row['patient_shape_score'] ?>"
                                                    class="custom-control-input score-calc" onchange="calculateScore()">
                                                <label class="custom-control-label" for="shape_<?= $row['patient_shape_id'] ?>">
                                                    <?= htmlspecialchars($row['patient_shape_label']) ?>
                                                    <span class="text-muted small">(<?= $row['patient_shape_score'] ?> คะแนน)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-danger small">ไม่พบข้อมูลตัวเลือกในตาราง patient_shape</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="form-group mb-4">
                                <label class="section-label">6. น้ำหนักเปลี่ยนใน 4 สัปดาห์ (Weight Change)</label>
                                <div class="radio-group-container">
                                    <?php if (!empty($weight_changes)): ?>
                                        <?php foreach ($weight_changes as $row): ?>
                                            <div class="custom-control custom-radio custom-control-inline mr-4 mb-2">
                                                <input type="radio"
                                                    id="wc_<?= $row['weight_change_4_weeks_id'] ?>"
                                                    name="weight_change_4_week_id"
                                                    value="<?= $row['weight_change_4_weeks_id'] ?>"
                                                    data-score="<?= $row['weight_change_4_weeks_score'] ?>"
                                                    class="custom-control-input score-calc" onchange="calculateScore()">
                                                <label class="custom-control-label" for="wc_<?= $row['weight_change_4_weeks_id'] ?>">
                                                    <?= htmlspecialchars($row['weight_change_4_weeks_label']) ?>
                                                    <span class="text-muted small">(<?= $row['weight_change_4_weeks_score'] ?> คะแนน)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="form-group mb-4">
                                <label class="section-label">7. อาหารที่กินในช่วง 2 สัปดาห์ที่ผ่านมา</label>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-secondary font-weight-bold mb-2">7.1 ลักษณะของอาหาร (Type)</h6>
                                        <div class="radio-group-container">
                                            <?php if (!empty($food_types)): ?>
                                                <?php foreach ($food_types as $row): ?>
                                                    <div class="custom-control custom-radio mb-2">
                                                        <input type="radio"
                                                            id="ft_<?= $row['food_type_id'] ?>"
                                                            name="food_type_id"
                                                            value="<?= $row['food_type_id'] ?>"
                                                            data-score="<?= $row['food_type_score'] ?>"
                                                            class="custom-control-input score-calc" onchange="calculateScore()">
                                                        <label class="custom-control-label radio-label" for="ft_<?= $row['food_type_id'] ?>">
                                                            <?= htmlspecialchars($row['food_type_label']) ?>
                                                            <span class="radio-score">(<?= $row['food_type_score'] ?> คะแนน)</span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-secondary font-weight-bold mb-2">7.2 ปริมาณอาหารที่กิน (Amount)</h6>
                                        <div class="radio-group-container">
                                            <?php if (!empty($food_amounts)): ?>
                                                <?php foreach ($food_amounts as $row): ?>
                                                    <div class="custom-control custom-radio mb-2">
                                                        <input type="radio"
                                                            id="fa_<?= $row['food_amount_id'] ?>"
                                                            name="food_amount_id"
                                                            value="<?= $row['food_amount_id'] ?>"
                                                            data-score="<?= $row['food_amount_score'] ?>"
                                                            class="custom-control-input score-calc" onchange="calculateScore()">
                                                        <label class="custom-control-label radio-label" for="fa_<?= $row['food_amount_id'] ?>">
                                                            <?= htmlspecialchars($row['food_amount_label']) ?>
                                                            <span class="radio-score">(<?= $row['food_amount_score'] ?> คะแนน)</span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="form-group mb-4">
                                <label class="section-label">8. อาการต่อเนื่อง > 2 สัปดาห์ที่ผ่านมา </label>
                                <p class="text-muted small mb-2"><i class="fas fa-check-square mr-1"></i> เลือกได้มากกว่า 1 ข้อ
                                    (Select all that
                                    apply)</p>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="symptom-box h-100">
                                            <div class="symptom-category-title">8.1 ปัญหาทางการเคี้ยว/กลืน</div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check" id="symChoke"
                                                    value="2" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symChoke">สำลัก <span
                                                        class="symptom-score">(2 คะแนน)</span></label>
                                            </div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check" id="symDiff"
                                                    value="2" onchange="calculateScore()">
                                                <label class="custom-control-label w-100"
                                                    for="symDiff">เคี้ยว/กลืนลำบาก/ได้อาหารทางสายยาง <span
                                                        class="symptom-score">(2 คะแนน)</span></label>
                                            </div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check"
                                                    id="symNormalSwallow" value="0" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symNormalSwallow">ปกติ <span
                                                        class="symptom-score">(0 คะแนน)</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="symptom-box h-100">
                                            <div class="symptom-category-title">8.2 ปัญหาทางเดินอาหาร</div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check"
                                                    id="symDiarrhea" value="2" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symDiarrhea">ท้องเสีย <span
                                                        class="symptom-score">(2 คะแนน)</span></label>
                                            </div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check" id="symPain"
                                                    value="2" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symPain">ปวดท้อง <span
                                                        class="symptom-score">(2 คะแนน)</span></label>
                                            </div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check"
                                                    id="symNormalGI" value="0" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symNormalGI">ปกติ <span
                                                        class="symptom-score">(0 คะแนน)</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="symptom-box h-100">
                                            <div class="symptom-category-title">8.3 ปัญหาระหว่างกินอาหาร</div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check" id="symVomit"
                                                    value="2" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symVomit">อาเจียน <span
                                                        class="symptom-score">(2 คะแนน)</span></label>
                                            </div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check" id="symNausea"
                                                    value="2" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symNausea">คลื่นไส้ <span
                                                        class="symptom-score">(2 คะแนน)</span></label>
                                            </div>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox" class="custom-control-input symptom-check"
                                                    id="symNormalEat" value="0" onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="symNormalEat">ปกติ <span
                                                        class="symptom-score">(0 คะแนน)</span></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="form-group mb-4">
                                <label class="section-label text-success">ส่วนที่ 2: ความรุนแรงของโรค (Severity of Disease)</label>
                                <div class="card bg-white border">
                                    <div class="card-body">
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="sev0" name="b_severity" value="0" data-score="0" class="custom-control-input score-calc" onchange="calculateScore()">
                                            <label class="custom-control-label" for="sev0">ปกติ / เล็กน้อย (0 คะแนน)</label>
                                        </div>
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="sev1" name="b_severity" value="1" data-score="1" class="custom-control-input score-calc" onchange="calculateScore()">
                                            <label class="custom-control-label" for="sev1">ปานกลาง (1 คะแนน)</label>
                                        </div>
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="sev2" name="b_severity" value="2" data-score="2" class="custom-control-input score-calc" onchange="calculateScore()">
                                            <label class="custom-control-label" for="sev2">รุนแรง (2 คะแนน)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card text-center mb-5 border-0 shadow-sm" style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                                <div class="card-body py-5">
                                    <h5 class="text-muted mb-3">ผลการประเมินรวม (Total Score)</h5>
                                    <div class="d-flex justify-content-center align-items-center mb-3">
                                        <div id="scoreCircle" class="score-circle-big shadow bg-white d-flex align-items-center justify-content-center"
                                            style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem; font-weight: bold; color: #6c757d;">
                                            0
                                        </div>
                                    </div>
                                    <h3 id="resultText" class="font-weight-bold text-muted">รอการประเมิน</h3>
                                </div>
                            </div>

                            <div class="form-row justify-content-center mt-4">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary shadow-sm px-5 py-2">
                                        <i class="fa-solid fa-save mr-2"></i> บันทึกผลการประเมิน
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
        </form>
    </div>

    <script>
        function toggleOtherSource() {
            // อ้างอิง Element
            const otherRadio = document.getElementById('source3');
            const otherInput = document.getElementById('otherSourceText');

            // ตรวจสอบว่า Radio "อื่นๆ" ถูกเลือกอยู่หรือไม่
            if (otherRadio.checked) {
                otherInput.disabled = false; // เปิดใช้งานช่องกรอก
                otherInput.focus(); // นำเคอร์เซอร์ไปวางรอพิมพ์ทันที
            } else {
                otherInput.disabled = true; // ปิดการใช้งาน
                otherInput.value = ''; // ล้างค่าที่เคยกรอกไว้ (ถ้าต้องการ)
            }
        }
        // ฟังก์ชันคำนวณ BMI
        function calculateBMI() {
            // 1. ดึงค่าน้ำหนัก
            const weight = parseFloat(document.getElementById('currentWeight').value) || 0;

            // 2. ดึงค่าส่วนสูงจากทุกช่อง (ถ้าช่องไหนว่าง หรือไม่ใช่ตัวเลข จะได้ค่า 0)
            const h1 = parseFloat(document.getElementById('anthroHeight').value) || 0;
            const h2 = parseFloat(document.getElementById('anthroLength').value) || 0;
            const h3 = parseFloat(document.getElementById('anthroArmSpan').value) || 0;
            const h4 = parseFloat(document.getElementById('anthroReported').value) || 0;

            // 3. หาค่าส่วนสูงที่มากที่สุด (Max Value) เพื่อใช้คำนวณ
            const maxHeight = Math.max(h1, h2, h3, h4);

            // 4. คำนวณ BMI
            // ต้องมีน้ำหนัก และ ส่วนสูงอย่างน้อย 1 ช่อง (ค่ามากสุด > 0)
            if (weight > 0 && maxHeight > 0) {
                // แปลง ซม. เป็น เมตร
                const heightInMeters = maxHeight / 100;

                // สูตร: น้ำหนัก / (ส่วนสูงเมตร ^ 2)
                const bmi = weight / (heightInMeters * heightInMeters);

                // แสดงผลทศนิยม 2 ตำแหน่ง ที่ช่อง id="bmiValue"
                document.getElementById('bmiValue').value = bmi.toFixed(2);

                // คำนวณคะแนนต่อทันที
                calculateScore();
            } else {
                // ถ้าข้อมูลไม่ครบ ให้เคลียร์ค่า
                document.getElementById('bmiValue').value = "-";
                document.getElementById('bmiScoreText').innerText = "Score: 0";
                calculateScore(); // อัปเดตคะแนนรวมใหม่ (กรณีลบเลขออก)
            }
        }

        // 1. ฟังก์ชันสลับโหมด (น้ำหนักปกติ <-> ผลเลือด)
        function toggleWeightMode() {
            const isUnknown = document.getElementById('unknownWeight').checked;
            const weightSection = document.getElementById('standardWeightSection');
            const labSection = document.getElementById('labSection');

            if (isUnknown) {
                // โหมดไม่ทราบน้ำหนัก -> ซ่อนส่วนน้ำหนัก, แสดงส่วน Lab
                weightSection.style.display = 'none'; // หรือใช้ class 'd-none'
                labSection.classList.remove('hidden-section');
                labSection.classList.add('fade-in'); // เพิ่ม effect ถ้ามี class นี้

                // รีเซ็ตค่าคะแนนจากส่วนน้ำหนักเป็น 0 (เพื่อไม่ให้คะแนนซ้อนกัน)
                // (อาจต้องเคลียร์ค่าใน input น้ำหนัก/ส่วนสูง ด้วยถ้าต้องการ)
            } else {
                // โหมดปกติ -> แสดงส่วนน้ำหนัก, ซ่อนส่วน Lab
                weightSection.style.display = 'block';
                labSection.classList.add('hidden-section');
                labSection.classList.remove('fade-in');

                // รีเซ็ตคะแนน Lab เป็น 0
                document.getElementById('labScoreText').innerText = "0";
                // เคลียร์ค่า input ของ Lab
                document.getElementById('valAlbumin').value = '';
                document.getElementById('valTLC').value = '';
            }

            calculateScore(); // คำนวณคะแนนรวมใหม่ทันที
        }

        // 2. ฟังก์ชันเลือกประเภท Lab (Albumin / TLC)
        function selectLab(type) {
            // อัปเดต UI ของ Card (คลิกแล้วมีกรอบสี/เงา)
            document.querySelectorAll('.lab-choice-card').forEach(card => {
                card.classList.remove('active', 'border-primary');
                card.classList.add('inactive');
            });

            const selectedCard = (type === 'albumin') ? document.getElementById('cardAlbumin') : document.getElementById('cardTLC');
            selectedCard.classList.remove('inactive');
            selectedCard.classList.add('active', 'border-primary');

            // สั่ง check radio button ที่ซ่อนอยู่
            if (type === 'albumin') {
                document.getElementById('useAlbumin').checked = true;
            } else {
                document.getElementById('useTLC').checked = true;
            }

            toggleLabInputs(); // เปิดช่องกรอก
        }

        // 3. ฟังก์ชันเปิด/ปิดช่องกรอกตาม Radio ที่เลือก
        function toggleLabInputs() {
            const useAlb = document.getElementById('useAlbumin').checked;
            const useTLC = document.getElementById('useTLC').checked;

            const inpAlb = document.getElementById('valAlbumin');
            const inpTLC = document.getElementById('valTLC');

            if (useAlb) {
                inpAlb.disabled = false;
                inpAlb.focus();
                inpTLC.disabled = true;
                inpTLC.value = ''; // เคลียร์ค่าอีกช่อง
            } else if (useTLC) {
                inpTLC.disabled = false;
                inpTLC.focus();
                inpAlb.disabled = true;
                inpAlb.value = '';
            }

            calculateLabScore(); // คำนวณคะแนนใหม่
        }

        // 4. ฟังก์ชันคำนวณคะแนนจากผลเลือด
        function calculateLabScore() {
            let labScore = 0;
            const useAlb = document.getElementById('useAlbumin').checked;
            const useTLC = document.getElementById('useTLC').checked;

            if (useAlb) {
                const val = parseFloat(document.getElementById('valAlbumin').value);
                if (!isNaN(val)) {
                    if (val <= 2.5) labScore = 3;
                    else if (val <= 2.9) labScore = 2;
                    else if (val <= 3.5) labScore = 1;
                    else labScore = 0; // > 3.5
                }
            } else if (useTLC) {
                const val = parseFloat(document.getElementById('valTLC').value);
                if (!isNaN(val)) {
                    if (val <= 1000) labScore = 3;
                    else if (val <= 1200) labScore = 2;
                    else if (val <= 1500) labScore = 1;
                    else labScore = 0; // > 1500
                }
            }

            // แสดงคะแนน Lab
            document.getElementById('labScoreText').innerText = labScore;

            // เรียกคำนวณคะแนนรวมใหญ่ (ต้องปรับแก้ calculateScore ด้วย)
            calculateScore();
        }

        function calculateScore() {
            let total = 0;

            // --- ส่วนที่ 1: คะแนนจากตัวเลือกอื่นๆ (คงเดิม) ---
            const inputs = document.querySelectorAll('.score-calc:checked');
            inputs.forEach(el => {
                total += parseInt(el.getAttribute('data-score')) || 0;
            });

            // --- ส่วนที่ 2: เลือกคิดคะแนนจาก (BMI) หรือ (Lab) ---
            // เช็คว่า User เลือกโหมดไหน
            const isUnknownWeight = document.getElementById('unknownWeight').checked;

            if (isUnknownWeight) {
                // [กรณีไม่ทราบน้ำหนัก] -> เอาคะแนนจาก Lab
                const labScore = parseInt(document.getElementById('labScoreText').innerText) || 0;
                total += labScore;
            } else {
                // [กรณีปกติ] -> เอาคะแนนจาก BMI
                const bmiField = document.getElementById('bmiValue');
                const bmiVal = (bmiField && bmiField.value !== "-") ? parseFloat(bmiField.value) : 0;
                let bmiScore = 0;
                if (bmiVal > 0) {
                    if (bmiVal < 17) bmiScore = 2;
                    else if (bmiVal <= 18) bmiScore = 1; // แก้ range ให้ถูกต้อง
                    else if (bmiVal < 30) bmiScore = 0;
                    else bmiScore = 1;
                }

                // แสดงคะแนน BMI เล็กๆ (ถ้ามี)
                const bmiScoreText = document.getElementById('bmiScoreText');
                if (bmiScoreText) bmiScoreText.innerText = `Score: ${bmiScore}`;

                total += bmiScore;
            }

            // --- ส่วนที่ 3: แสดงผลรวม (คงเดิม) ---
            const scoreCircle = document.getElementById('scoreCircle');
            const resultText = document.getElementById('resultText');

            if (scoreCircle) {
                scoreCircle.innerText = total;
                // ... (Logic เปลี่ยนสี/ข้อความ เหมือนเดิม) ...
                scoreCircle.className = 'score-circle-big shadow bg-white d-flex align-items-center justify-content-center';
                resultText.className = 'font-weight-bold';

                if (total <= 5) {
                    scoreCircle.style.color = '#28a745';
                    scoreCircle.style.border = '4px solid #28a745';
                    resultText.innerText = 'Low Risk (ความเสี่ยงต่ำ)';
                    resultText.classList.add('text-success');
                    resultText.classList.remove('text-warning', 'text-danger');
                } else if (total <= 11) {
                    scoreCircle.style.color = '#ffc107';
                    scoreCircle.style.border = '4px solid #ffc107';
                    resultText.innerText = 'Moderate Risk (ความเสี่ยงปานกลาง)';
                    resultText.classList.add('text-warning');
                    resultText.classList.remove('text-success', 'text-danger');
                } else {
                    scoreCircle.style.color = '#dc3545';
                    scoreCircle.style.border = '4px solid #dc3545';
                    resultText.innerText = 'High Risk (ความเสี่ยงสูง)';
                    resultText.classList.add('text-danger');
                    resultText.classList.remove('text-success', 'text-warning');
                }
            }
        }

        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) {
                window.location.href = 'index.php';
            }
        }
    </script>
</body>

</html>