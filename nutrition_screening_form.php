<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

$hn = $_GET['hn'] ?? '';
$an = $_GET['an'] ?? '';

if (empty($hn) || empty($an)) {
    die("Error: ไม่พบข้อมูล HN หรือ AN");
}

try {
    $sql_patient = "
        SELECT 
            patients.patients_hn, 
            patients.patients_firstname, 
            patients.patients_lastname, 
            patients.patients_dob, 
            patients.patients_phone, 
            patients.patients_congenital_disease,
            admissions.admit_datetime, 
            admissions.admissions_an, 
            admissions.bed_number, 
            wards.ward_name, 
            doctor.doctor_name,
            health_insurance.health_insurance_name
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

    $age = '-';
    if (!empty($patient['patients_dob'])) {
        $dob = new DateTime($patient['patients_dob']);
        $now = new DateTime();
        $diff = $now->diff($dob);
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน ' . $diff->d . ' วัน';
    }

    $admit_date = '-';
    if (!empty($patient['admit_datetime'])) {
        $dt = new DateTime($patient['admit_datetime']);
        $thai_year = $dt->format('Y') + 543;
        // แสดงผล: 12/04/2567 10:30 น.
        $admit_date = $dt->format('d/m/') . $thai_year . ' ' . $dt->format('H:i') . ' น.';
    }

    $stmt_seq = $conn->prepare("SELECT MAX(screening_seq) as max_seq FROM nutrition_screening WHERE admissions_an = :an");
    $stmt_seq->execute([':an' => $an]);
    $next_seq = ($stmt_seq->fetch(PDO::FETCH_ASSOC)['max_seq'] ?? 0) + 1;

    $doc_no_show = 'SPENT-' . $patient['patients_hn'] . '-' . str_pad($next_seq, 3, '0', STR_PAD_LEFT);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบคัดกรองภาวะโภชนาการ (SPENT) | โรงพยาบาลกำแพงเพชร</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="css/nutrition_screening_form.css">
</head>

<body>
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

    <div class="container-fluid px-lg-5 mt-4">

        <form id="mainForm" method="POST" action="nutrition_screening_save.php">
            <input type="hidden" name="hn" value="<?= htmlspecialchars($hn) ?>">
            <input type="hidden" name="an" value="<?= htmlspecialchars($an) ?>">
            <input type="hidden" name="redirect_to_naf" id="redirect_to_naf" value="false">

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
                                <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">HN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['patients_hn'] ?></span></div>
                                <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">AN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['admissions_an'] ?></span></div>
                                <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">ชื่อ - นามสกุล</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['patients_firstname'] . ' ' . $patient['patients_lastname'] ?></span></div>
                                <div class="col-6 col-md-4 col-lg-2 mb-3"><small class="text-muted d-block" style="font-size: 0.95rem;">อายุ</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $age ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">หอผู้ป่วย</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['ward_name'] ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เตียง</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['bed_number'] ?></span></div>

                                <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">แพทย์เจ้าของไข้</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['doctor_name'] ?: '-' ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">วันที่ Admit</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $admit_date ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เบอร์โทรศัพท์</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['patients_phone'] ?: '-' ?></span></div>

                                <div class="col-12 col-md-6 col-lg-2 mb-3">
                                    <small class="text-muted d-block">โรคประจำตัว</small>
                                    <span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['patients_congenital_disease'] ?: '-' ?></span>
                                </div>

                                <div class="col-12 col-md-6 col-lg-4 mb-3">
                                    <small class="text-muted d-block">สิทธิการรักษา</small>
                                    <span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['health_insurance_name'] ?: '-' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-secondary btn-sm shadow-sm" style="border-radius: 4px;" onclick="window.location.href='patient_profile.php?hn=<?= htmlspecialchars($hn) ?>&an=<?= htmlspecialchars($an) ?>'">
                    <i class="fa-solid fa-chevron-left mr-1"></i> ย้อนกลับ
                </button>
            </div>

            <div class="card mb-5 border-0 shadow-sm" style="border-top: 5px solid #0d47a1 !important;">
                <div class="form-header-box">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark">แบบคัดกรองภาวะโภชนาการ (SPENT)</h4>
                            <small class="text-muted">Nutrition Screening Tool for Hospitalized Patients</small>
                        </div>
                        <div class="text-right">
                            <span id="docIdBadge" class="badge badge-info p-2" style="font-size: 0.9rem;">No.: <?= htmlspecialchars($doc_no_show) ?></span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-2 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ครั้งที่</span></div><input type="text" class="form-control text-center font-weight-bold text-primary" value="<?= htmlspecialchars($next_seq) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">วันที่</span></div><input type="text" class="form-control text-center" value="<?= date('d/m/') . (date('Y') + 543) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">เวลา</span></div><input type="text" class="form-control text-center" value="<?= date('H:i') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ผู้คัดกรอง</span></div><input type="text" class="form-control text-center text-primary" name="assessor_name" value="เพชรลดา เชยเพ็ชร" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">

                    <div class="form-group mb-4">
                        <label class="section-label">1. การวินิจฉัยโรค (Diagnosis)</label>
                        <input type="text" class="form-control" id="diagnosis" name="initial_diagnosis" placeholder="ระบุการวินิจฉัยโรค..." required>
                    </div>

                    <hr class="my-4" style="border-top: 1px dashed #dee2e6;">

                    <div class="mb-4">
                        <label class="section-label">2. ข้อมูลสัดส่วนร่างกาย (Anthropometry)</label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">น้ำหนักปัจจุบัน</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" class="form-control" id="currentWeight" name="present_weight" placeholder="0.0" oninput="calculateBMI()" required>
                                        <div class="input-group-append"><span class="input-group-text input-unit">กก.</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">ส่วนสูง</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="heightVal" name="height" placeholder="0" oninput="calculateBMI()" required>
                                        <div class="input-group-append"><span class="input-group-text input-unit">ซม.</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">น้ำหนักปกติ (ถ้าทราบ)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.1" class="form-control" name="normal_weight" placeholder="0.0">
                                        <div class="input-group-append"><span class="input-group-text input-unit">กก.</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">ดัชนีมวลกาย (BMI)</label>
                                    <input type="text" class="form-control bmi-display" id="bmiVal" name="bmi" placeholder="-" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="d-flex align-items-center bg-light p-2 rounded border">
                                    <span class="mr-3 font-weight-bold text-secondary small">ที่มาของน้ำหนัก:</span>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="method1" name="weightMethod" class="custom-control-input" value="ชั่งจริง" checked>
                                        <label class="custom-control-label" for="method1">ชั่งจริง</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="method2" name="weightMethod" class="custom-control-input" value="ซักถาม">
                                        <label class="custom-control-label" for="method2">ซักถาม</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="method3" name="weightMethod" class="custom-control-input" value="กะประมาณ">
                                        <label class="custom-control-label" for="method3">กะประมาณ</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4" style="border-top: 1px dashed #dee2e6;">

                    <div class="mb-4">
                        <label class="section-label">3. แบบคัดกรอง (Screening Questions)</label>
                        <table class="table table-bordered table-screening mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 70%; border-bottom: 2px solid #dee2e6;">ประเด็นคำถาม</th>
                                    <th class="text-center text-success" style="width: 15%; border-bottom: 2px solid #dee2e6;">ใช่ (1)</th>
                                    <th class="text-center text-muted" style="width: 15%; border-bottom: 2px solid #dee2e6;">ไม่ใช่ (0)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="align-middle">1. ผู้ป่วยน้ำหนักตัวลดลง โดยไม่ได้ตั้งใจ (ในช่วง 6 เดือนที่ผ่านมา)</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q1_yes" name="q1" class="custom-control-input score-radio" value="1"><label class="custom-control-label" for="q1_yes"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q1_no" name="q1" class="custom-control-input score-radio" value="0" checked><label class="custom-control-label" for="q1_no"></label></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle">2. ผู้ป่วยได้รับอาหารน้อยกว่าที่เคยได้ (> 7 วัน)</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q2_yes" name="q2" class="custom-control-input score-radio" value="1"><label class="custom-control-label" for="q2_yes"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q2_no" name="q2" class="custom-control-input score-radio" value="0" checked><label class="custom-control-label" for="q2_no"></label></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle">3. BMI < 18.5 หรือ ≥ 25.0 กก./ม.² หรือไม่</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q3_yes" name="q3" class="custom-control-input score-radio" value="1"><label class="custom-control-label" for="q3_yes"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q3_no" name="q3" class="custom-control-input score-radio" value="0" checked><label class="custom-control-label" for="q3_no"></label></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle">4. ผู้ป่วยมีภาวะโรควิกฤต หรือกึ่งวิกฤต</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q4_yes" name="q4" class="custom-control-input score-radio" value="1"><label class="custom-control-label" for="q4_yes"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" id="q4_no" name="q4" class="custom-control-input score-radio" value="0" checked><label class="custom-control-label" for="q4_no"></label></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label class="section-label">4. หมายเหตุ / ข้อสังเกตเพิ่มเติม (Optional)</label>
                        <textarea class="form-control" id="screenNotes" name="notes" rows="3" placeholder="ระบุข้อมูลเพิ่มเติมทางคลินิก (ถ้ามี)..."></textarea>
                    </div>

                    <div class="form-row mt-4" id="saveScreeningRow">
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-info shadow-sm px-4" onclick="processScreening()">
                                <i class="fa-solid fa-floppy-disk mr-2"></i> ประมวลผลและบันทึก
                            </button>
                        </div>
                    </div>

                    <div id="resultBox" class="result-box d-none">
                        <div class="mb-3">
                            <i id="resultIcon" class="fa-solid fa-circle-exclamation fa-4x"></i>
                        </div>

                        <h3 class="font-weight-bold mb-2" id="resultTitle">...</h3>
                        <p class="mb-2" id="resultDesc" style="font-size: 0.95rem;">...</p>

                        <div id="nextActionHint"></div>

                        <div id="highRiskOptions" class="d-none">
                            <div class="d-flex justify-content-center mt-4">
                                <button type="button" class="btn btn-outline-secondary mr-3 px-4 py-2" onclick="saveLater(true)">
                                    <i class="fa-regular fa-clock mr-2"></i> ไว้ทำทีหลัง
                                </button>
                                <button type="button" class="btn btn-danger px-4 py-2 shadow-sm" onclick="openAssessment(true)">
                                    <i class="fa-solid fa-user-doctor mr-2"></i> ประเมิน NAF ต่อทันที
                                </button>
                            </div>
                        </div>

                        <div id="normalOptions" class="d-none">
                            <div class="d-flex justify-content-center mt-4">
                                <button type="button" class="btn btn-success px-5 py-2 shadow-sm" onclick="saveLater(false)">
                                    <i class="fa-solid fa-check mr-2"></i> บันทึกและกลับหน้าหลัก
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateBMI() {
            const w = parseFloat(document.getElementById('currentWeight').value);
            const h = parseFloat(document.getElementById('heightVal').value);

            if (w > 0 && h > 0) {
                const h_m = h / 100;
                const bmi = w / (h_m * h_m);
                document.getElementById('bmiVal').value = bmi.toFixed(2);

                if (bmi < 18.5 || bmi >= 25.0) {
                    document.getElementById('q3_yes').checked = true;
                } else {
                    document.getElementById('q3_no').checked = true;
                }
            }
        }

        function processScreening() {
            if (!document.getElementById('currentWeight').value || !document.getElementById('heightVal').value) {
                alert('กรุณากรอกน้ำหนักและส่วนสูงก่อนประมวลผล');
                return;
            }

            let score = 0;
            document.querySelectorAll('.score-radio:checked').forEach(r => {
                if (r.value === '1') {
                    score++;
                }
            });

            document.getElementById('saveScreeningRow').classList.add('d-none');
            const box = document.getElementById('resultBox');
            box.classList.remove('d-none');
            box.classList.remove('result-normal', 'result-risk');

            const title = document.getElementById('resultTitle');
            const desc = document.getElementById('resultDesc');
            const icon = document.getElementById('resultIcon');
            const nextActionHint = document.getElementById('nextActionHint');
            const highRiskDiv = document.getElementById('highRiskOptions');
            const normalDiv = document.getElementById('normalOptions');

            nextActionHint.className = '';

            if (score >= 2) {
                box.classList.add('result-risk');

                icon.className = 'fa-solid fa-triangle-exclamation fa-4x text-danger';
                title.innerText = 'มีความเสี่ยง (At Risk)';
                title.className = 'font-weight-bold mb-2 text-danger';
                desc.innerText = `คะแนนรวม: ${score} คะแนน - ผู้ป่วยมีความเสี่ยงต่อภาวะขาดสารอาหาร`;

                nextActionHint.classList.add('hint-risk');
                nextActionHint.innerHTML = `<i class="far fa-bell mr-2"></i> <strong>ข้อแนะนำ:</strong> ทำการประเมินภาวะโภชนาการต่อ หรือปรึกษานักกำหนดอาหาร`;

                highRiskDiv.classList.remove('d-none');
                normalDiv.classList.add('d-none');

            } else {
                box.classList.add('result-normal');

                icon.className = 'fa-solid fa-circle-check fa-4x text-success';
                title.innerText = 'ไม่พบความเสี่ยง (Normal)';
                title.className = 'font-weight-bold mb-2 text-success';
                desc.innerText = `คะแนนรวม: ${score} คะแนน - ควรคัดกรองซ้ำใน 7 วัน`;

                const nextWeek = new Date();
                nextWeek.setDate(nextWeek.getDate() + 7);
                const nextDateStr = nextWeek.toLocaleDateString('th-TH', {
                    day: 'numeric',
                    month: 'short',
                    year: '2-digit'
                });

                nextActionHint.classList.add('hint-normal');
                nextActionHint.innerHTML = `<i class="far fa-calendar-check mr-2"></i> <strong>ข้อแนะนำ:</strong> คัดกรองซ้ำในอีก 7 วัน (${nextDateStr})`;

                normalDiv.classList.remove('d-none');
                highRiskDiv.classList.add('d-none');
            }
        }

        function saveLater(isRisk) {
            submitForm(false);
        }

        function openAssessment(isRisk) {
            submitForm(true);
        }

        function submitForm(goToNaf) {
            document.getElementById('redirect_to_naf').value = goToNaf ? 'true' : 'false';
            document.getElementById('mainForm').submit();
        }

        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) {
                window.location.href = 'index.php';
            }
        }
    </script>
</body>

</html>