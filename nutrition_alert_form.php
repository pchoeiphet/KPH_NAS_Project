<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_destroy();
    error_log("Session timeout for user: " . $_SESSION['user_id']);
    die("ข้อผิดพลาด: หมดเวลาการใช้งาน");
}
$_SESSION['last_activity'] = time();

// รับค่าจาก URL
$hn = trim($_GET['hn'] ?? '');
$an = trim($_GET['an'] ?? '');
$ref_screening_doc = trim($_GET['ref_screening'] ?? '');

// Input validation for HN and AN
if (empty($hn) || empty($an) || !preg_match('/^[A-Za-z0-9\-]+$/', $hn) || !preg_match('/^[A-Za-z0-9\-]+$/', $an)) {
    error_log("Invalid HN or AN parameters: HN=$hn, AN=$an");
    die("ข้อผิดพลาด: พารามิเตอร์ไม่ถูกต้อง");
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ถ้ามีเลขที่เอกสารส่งมา ให้ตรวจสอบและไปค้นหา ID ในฐานข้อมูล
if (!empty($ref_screening_doc) && preg_match('/^[A-Z]+-[A-Za-z0-9\-]+$/', $ref_screening_doc)) {
    try {
        // Query ค้นหาข้อมูลจากตาราง nutrition_screening ด้วยเลขที่เอกสาร
        $stmt_find = $conn->prepare("SELECT * FROM nutrition_screening WHERE doc_no = :ref_doc LIMIT 1");
        $stmt_find->execute([':ref_doc' => $ref_screening_doc]);
        $result = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $screening_data = $result;
        }
    } catch (Exception $e) {
        error_log("Error fetching screening data: " . $e->getMessage());
    }
} else {
    $screening_data = [];
}

$val_diagnosis = '';
$val_weight = '';
$val_height = '';
$val_bmi = '';

$default_date = date('Y-m-d');
$default_time = date('H:i');

if (!empty($screening_data)) {
    $val_diagnosis = $screening_data['initial_diagnosis'] ?? '';
    $val_weight = $screening_data['present_weight'] ?? '';
    $val_height = $screening_data['height'] ?? '';
    $val_bmi = $screening_data['bmi'] ?? '';

    // ถ้ามีข้อมูลการคัดกรอง ให้ใช้วันที่คัดกรองเป็นค่าเริ่มต้น
    if (!empty($screening_data['screening_datetime'])) {
        $sc_timestamp = strtotime($screening_data['screening_datetime']);
        $default_date = date('Y-m-d', $sc_timestamp);
        $default_time = date('H:i', $sc_timestamp);
    }
}

try {
    // ดึงข้อมูลผู้ป่วย
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

    if (!$patient) {
        error_log("Patient not found: HN=$hn, AN=$an, user=" . $_SESSION['user_id']);
        die("ข้อผิดพลาด: ไม่พบข้อมูลผู้ป่วย");
    }

    // คำนวณอายุ
    $age = '-';
    if (!empty($patient['patients_dob'])) {
        $dob = new DateTime($patient['patients_dob']);
        $now = new DateTime();
        $diff = $now->diff($dob);
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน' . ' ' . $diff->d . ' วัน';
    }

    // แปลงวันที่ Admit
    $admit_date = '-';
    if (!empty($patient['admit_datetime'])) {
        $dt = new DateTime($patient['admit_datetime']);
        $thai_year = $dt->format('Y') + 543;
        // แสดงผล: 12/04/2567 10:30 น.
        $admit_date = $dt->format('d/m/') . $thai_year . ' ' . $dt->format('H:i') . ' น.';
    }

    // สร้างเลขที่เอกสาร NAF
    $stmt_seq = $conn->prepare("SELECT COUNT(*) as count FROM nutrition_assessment WHERE patients_hn = :hn");
    $stmt_seq->execute([':hn' => $hn]);
    $count = $stmt_seq->fetch(PDO::FETCH_ASSOC)['count'];
    $naf_seq = $count + 1;
    $doc_no_show = 'NAF-' . $patient['patients_hn'] . '-' . str_pad($naf_seq, 3, '0', STR_PAD_LEFT);

    // ดึง Master Data 
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

    $symptom_list = fetchMasterData($conn, 'symptom_problem', 'symptom_problem_id');
    $symptoms_grouped = [];

    // วนลูปเพื่อจัดกลุ่มตาม Type ที่ระบุในฐานข้อมูล
    foreach ($symptom_list as $sym) {
        $type = $sym['symptom_problem_type'];
        $symptoms_grouped[$type][] = $sym;
    }

    $food_access_list = fetchMasterData($conn, 'food_access', 'food_access_id');

    $disease_list = fetchMasterData($conn, 'disease', 'disease_id');
    $diseases_grouped = [];

    // จัดกลุ่มตาม disease_type
    foreach ($disease_list as $d) {
        $type = $d['disease_type'];
        $diseases_grouped[$type][] = $d;
    }

    $stmt_user = $conn->prepare("SELECT nut_fullname FROM nutritionists WHERE nut_id = :uid");
    $stmt_user->execute([':uid' => $_SESSION['user_id']]);
    $current_user_name = $stmt_user->fetchColumn();

    // ถ้าหาไม่เจอ ให้ใช้ชื่อจาก Session หรือขีด -
    if (empty($current_user_name)) {
        $current_user_name = $_SESSION['user_name'] ?? '-';
    }
} catch (PDOException $e) {
    error_log("Database error in nutrition_alert_form.php: " . $e->getMessage());
    die("ข้อผิดพลาด: ไม่สามารถดึงข้อมูลได้");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
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

            <ul class="navbar-nav ml-auto align-items-center d-none d-md-flex">

                <li class="nav-item mx-1">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'index.php') ? 'active text-primary' : 'text-dark'; ?>"
                        href="index.php">
                        <i class="fa-solid fa-home mr-1"></i> รายชื่อผู้ป่วยใน
                    </a>
                </li>

                <li class="nav-item mx-1">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'nutrition_form_history.php') ? 'active text-primary' : 'text-dark'; ?>"
                        href="nutrition_form_history.php">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> ประวัติการประเมินของฉัน
                    </a>
                </li>

            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link p-0" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false" style="min-width: 290px;">
                        <div class="user-profile-btn">
                            <div class="user-avatar">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                            <div class="user-info d-none d-md-block" style="flex-grow: 1;">
                                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($_SESSION['user_position']); ?></div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-muted mr-2" style="font-size: 0.8rem;"></i>
                        </div>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2 pb-0" aria-labelledby="userDropdown"
                        style="border-radius: 12px; min-width: 250px; overflow: hidden;">

                        <div class="dropdown-header bg-light border-bottom py-3">
                            <div class="d-flex align-items-center px-2">
                                <div class="user-avatar mr-3 bg-white border"
                                    style="width: 45px; height: 45px; font-size: 1.3rem; color: #2c3e50;">
                                    <i class="fa-solid fa-user-doctor"></i>
                                </div>
                                <div style="line-height: 1.3;">
                                    <h6 class="font-weight-bold text-dark mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($_SESSION['hospital']); ?></small>
                                    <span class="badge badge-info mt-1 font-weight-normal px-2">
                                        License: <?php echo htmlspecialchars($_SESSION['user_code'] ?? '-'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-2">
                            <a class="dropdown-item py-2 rounded mb-1" href="nutrition_form_history.php">
                                <span><i class="fa-solid fa-clock-rotate-left mr-2 text-primary" style="width:20px;"></i>
                                    ประวัติการประเมินของฉัน</span>
                            </a>

                            <a class="dropdown-item py-2 rounded" href="electronic_sign.php">
                                <span><i class="fa-solid fa-file-signature mr-2 text-success" style="width:20px;"></i>
                                    ลายเซ็นอิเล็กทรอนิกส์ (E-Sign)</span>
                            </a>
                        </div>

                        <div class="bg-light border-top p-2">
                            <a class="dropdown-item py-2 rounded text-danger font-weight-bold" href="#"
                                onclick="confirmLogout()">
                                <i class="fa-solid fa-right-from-bracket mr-2" style="width:20px;"></i> ออกจากระบบ
                            </a>
                        </div>

                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid px-lg-5 mt-4">

        <form id="nafForm" method="POST" action="nutrition_alert_form_save.php">
            <input type="hidden" name="hn" value="<?= htmlspecialchars($hn) ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="an" value="<?= htmlspecialchars($an) ?>">
            <input type="hidden" name="doc_no" value="<?= htmlspecialchars($doc_no_show) ?>">
            <input type="hidden" name="naf_seq" value="<?= htmlspecialchars($naf_seq) ?>">
            <input type="hidden" name="ref_screening_doc" value="<?= htmlspecialchars($ref_screening ?? $screening_data['doc_no'] ?? '') ?>">
            <input type="hidden" name="screening_id" value="<?= $screening_data['nutrition_screening_id'] ?? '' ?>">

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
                                <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">HN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['patients_hn']) ?></span></div>
                                <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">AN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['admissions_an']) ?></span></div>
                                <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">ชื่อ - นามสกุล</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['patients_firstname'] . ' ' . $patient['patients_lastname']) ?></span></div>
                                <div class="col-6 col-md-4 col-lg-2 mb-3"><small class="text-muted d-block" style="font-size: 0.95rem;">อายุ</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($age) ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">หอผู้ป่วย</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['ward_name'] ?? '-') ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เตียง</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['bed_number'] ?? '-') ?></span></div>

                                <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">แพทย์เจ้าของไข้</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['doctor_name'] ?? '-') ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">วันที่ Admit</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($admit_date) ?></span></div>
                                <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เบอร์โทรศัพท์</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($patient['patients_phone'] ?? '-') ?></span></div>

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
                <button type="button" class="btn btn-secondary btn-sm shadow-sm" onclick="window.location.href='patient_profile.php?hn=<?= htmlspecialchars($hn) ?>&an=<?= htmlspecialchars($an) ?>'">
                    <i class="fa-solid fa-chevron-left mr-1"></i> ย้อนกลับ
                </button>
            </div>

            <div class="card mb-5 border-0 shadow-sm" style="border-top: 5px solid #33691e!important;">
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
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white text-muted">วันที่</span>
                                </div>
                                <input type="date"
                                    class="form-control text-center text-dark"
                                    name="assessment_date"
                                    value="<?= $default_date ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white text-muted">เวลา</span>
                                </div>
                                <input type="time"
                                    class="form-control text-center text-dark"
                                    name="assessment_time"
                                    value="<?= $default_time ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ผู้ประเมิน</span></div>
                                <input type="text"
                                    class="form-control text-center text-primary"
                                    name="assessor_name_display"
                                    value="<?php echo htmlspecialchars($current_user_name); ?>"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">

                    <div class="form-group mb-4">
                        <label class="section-label">1. การวินิจฉัยเบื้องต้น (Provisional Diagnosis)</label>
                        <input type="text" class="form-control" name="initial_diagnosis"
                            placeholder="ระบุการวินิจฉัยโรค..."
                            value="<?= htmlspecialchars($val_diagnosis) ?>"> <small class="text-muted">ดึงข้อมูลอัตโนมัติจากแบบคัดกรอง (แก้ไขได้)</small>
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <label class="section-label">2. ข้อมูลได้จาก (Source of Information)</label>
                        <div class="d-flex align-items-center">
                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="source1" name="info_source" class="custom-control-input"
                                    value="ผู้ป่วย" checked onchange="toggleOtherSource()">
                                <label class="custom-control-label" for="source1">ผู้ป่วย</label>
                            </div>

                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="source2" name="info_source" class="custom-control-input"
                                    value="ญาติ" onchange="toggleOtherSource()">
                                <label class="custom-control-label" for="source2">ญาติ</label>
                            </div>

                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="custom-control custom-radio custom-control-inline mr-2">
                                    <input type="radio" id="source3" name="info_source" class="custom-control-input"
                                        value="อื่นๆ" onchange="toggleOtherSource()">
                                    <label class="custom-control-label" for="source3">อื่นๆ</label>
                                </div>
                                <input type="text" class="form-control form-control-sm" id="otherSourceText"
                                    name="other_source"
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
                                    <input type="number" step="0.1" class="form-control anthro-input"
                                        id="anthroHeight"
                                        name="height_measure"
                                        placeholder="0.0" oninput="calculateBMI()"
                                        value="<?= htmlspecialchars($val_height) ?>">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">วัดความยาวตัว (Length)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input"
                                        id="anthroLength"
                                        name="body_length"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">Arm Span</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input"
                                        id="anthroArmSpan"
                                        name="arm_span"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="small text-muted font-weight-bold">ญาติบอก (Reported)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control anthro-input"
                                        id="anthroReported"
                                        name="height_relative"
                                        placeholder="0.0" oninput="calculateBMI()">
                                    <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                                </div>
                            </div>

                            <div id="anthroAlert" class="alert alert-danger py-2 d-none" role="alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i> กรุณาระบุข้อมูลส่วนสูง/ความยาวตัว อย่างน้อย
                                1 ช่อง
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="section-label mb-0">4. น้ำหนักและค่าดัชนีมวลกาย (Weight & BMI)</label>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="unknownWeight"
                                    name="is_no_weight" value="1"
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
                                            name="weight"
                                            placeholder="0.0" oninput="calculateBMI()"
                                            value="<?= htmlspecialchars($val_weight) ?>">
                                        <div class="input-group-append"><span class="input-group-text">กก.</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <label class="small text-muted font-weight-bold">ดัชนีมวลกาย (BMI)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control bmi-display-box"
                                            id="bmiValue"
                                            value="<?= htmlspecialchars($val_bmi) ?>"
                                            readonly>

                                        <div class="input-group-append">
                                            <span class="input-group-text small" id="bmiScoreText"
                                                style="background-color: #e9ecef; font-weight: 500;">Score: 0</span>
                                        </div>
                                    </div>

                                    <input type="hidden" name="bmi" id="hidden_bmi" value="<?= htmlspecialchars($val_bmi) ?>">
                                    <input type="hidden" name="bmi_score" id="hidden_bmi_score" value="0">
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
                                    <i class="fas fa-info-circle mr-1"></i> <strong>เกณฑ์ BMI:</strong> &lt; 17.0 (2 คะแนน), 17.0-18.1 (1 คะแนน), 18.1-29.9 (0 คะแนน), &gt;= 30 (1 คะแนน)
                                </small>
                            </div>
                        </div>

                        <div id="labSection" class="hidden-section fade-in">
                            <input type="hidden" name="lab_score" id="hidden_lab_score" value="0">

                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="lab-choice-card inactive" id="cardAlbumin" onclick="selectLab('albumin')">

                                        <input type="radio" id="useAlbumin" name="lab_method" value="Albumin"
                                            class="d-none" onchange="toggleLabInputs()">

                                        <div class="lab-header">
                                            <i class="fas fa-vial text-primary mr-2"></i> 1. Albumin
                                            <span class="lab-unit">(g/dl)</span>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="small text-muted mb-1">ระบุค่าผลเลือด:</label>
                                            <div class="input-group">
                                                <input type="number" step="0.1" class="form-control" id="valAlbumin"
                                                    name="albumin_val"
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
                                                        <td class="text-right"><span class="score-badge text-danger">3</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>2.6 - 2.9</td>
                                                        <td class="text-right"><span class="score-badge text-warning">2</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>3.0 - 3.5</td>
                                                        <td class="text-right"><span class="score-badge text-primary">1</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>&gt; 3.5</td>
                                                        <td class="text-right"><span class="score-badge text-muted">0</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="lab-choice-card inactive" id="cardTLC" onclick="selectLab('tlc')">

                                        <input type="radio" id="useTLC" name="lab_method" value="TLC" class="d-none"
                                            onchange="toggleLabInputs()">

                                        <div class="lab-header">
                                            <i class="fas fa-microscope text-primary mr-2"></i> 2. TLC
                                            <span class="lab-unit">(cells/mm³)</span>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="small text-muted mb-1">ระบุค่าผลเลือด:</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="valTLC"
                                                    name="tlc_val"
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
                                                        <td class="text-right"><span class="score-badge text-danger">3</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>1,001 - 1,200</td>
                                                        <td class="text-right"><span class="score-badge text-warning">2</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>1,201 - 1,500</td>
                                                        <td class="text-right"><span class="score-badge text-primary">1</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>&gt; 1,500</td>
                                                        <td class="text-right"><span class="score-badge text-muted">0</span></td>
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
                        <label class="section-label">8. อาการต่อเนื่อง > 2 สัปดาห์ที่ผ่านมา</label>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-check-square mr-1"></i> เลือกได้มากกว่า 1 ข้อ (Select all that apply)
                        </p>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="symptom-box h-100">
                                    <div class="symptom-category-title">8.1 ปัญหาทางการเคี้ยว/กลืน</div>
                                    <?php
                                    $group1 = 'ปัญหาทางการเคี้ยว/กลืนอาหาร';
                                    if (!empty($symptoms_grouped[$group1])):
                                        foreach ($symptoms_grouped[$group1] as $item):
                                    ?>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox"
                                                    class="custom-control-input score-calc"
                                                    id="sym_<?= $item['symptom_problem_id'] ?>"
                                                    name="symptom_ids[]"
                                                    value="<?= $item['symptom_problem_id'] ?>"
                                                    data-score="<?= $item['symptom_problem_score'] ?>"
                                                    onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="sym_<?= $item['symptom_problem_id'] ?>">
                                                    <?= htmlspecialchars($item['symptom_problem_name']) ?>
                                                    <span class="symptom-score">(<?= $item['symptom_problem_score'] ?> คะแนน)</span>
                                                </label>
                                            </div>
                                        <?php endforeach;
                                    else: ?>
                                        <div class="text-muted small p-2">- ไม่มีข้อมูล -</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="symptom-box h-100">
                                    <div class="symptom-category-title">8.2 ปัญหาทางเดินอาหาร</div>
                                    <?php
                                    $group2 = 'ปัญหาระบบทางเดินอาหาร';
                                    if (!empty($symptoms_grouped[$group2])):
                                        foreach ($symptoms_grouped[$group2] as $item):
                                    ?>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox"
                                                    class="custom-control-input score-calc"
                                                    id="sym_<?= $item['symptom_problem_id'] ?>"
                                                    name="symptom_ids[]"
                                                    value="<?= $item['symptom_problem_id'] ?>"
                                                    data-score="<?= $item['symptom_problem_score'] ?>"
                                                    onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="sym_<?= $item['symptom_problem_id'] ?>">
                                                    <?= htmlspecialchars($item['symptom_problem_name']) ?>
                                                    <span class="symptom-score">(<?= $item['symptom_problem_score'] ?> คะแนน)</span>
                                                </label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="symptom-box h-100">
                                    <div class="symptom-category-title">8.3 ปัญหาระหว่างกินอาหาร</div>
                                    <?php
                                    $group3 = 'ปัญหาระหว่างกินอาหาร';
                                    if (!empty($symptoms_grouped[$group3])):
                                        foreach ($symptoms_grouped[$group3] as $item):
                                    ?>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox"
                                                    class="custom-control-input score-calc"
                                                    id="sym_<?= $item['symptom_problem_id'] ?>"
                                                    name="symptom_ids[]"
                                                    value="<?= $item['symptom_problem_id'] ?>"
                                                    data-score="<?= $item['symptom_problem_score'] ?>"
                                                    onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="sym_<?= $item['symptom_problem_id'] ?>">
                                                    <?= htmlspecialchars($item['symptom_problem_name']) ?>
                                                    <span class="symptom-score">(<?= $item['symptom_problem_score'] ?> คะแนน)</span>
                                                </label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <label class="section-label">9. ความสามารถในการเข้าถึงอาหาร (Functional Capacity)</label>
                        <div class="radio-group-container" style="flex-direction: row; flex-wrap: wrap; gap: 15px;">

                            <?php if (!empty($food_access_list)): ?>
                                <?php foreach ($food_access_list as $fa): ?>
                                    <?php
                                    $fa_id = $fa['food_access_id'];
                                    $unique_id = "f_access_" . $fa_id;

                                    $fa_text = $fa['food_access_label'] ?? '-';
                                    $fa_score = $fa['food_access_score'] ?? 0;
                                    ?>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio"
                                            id="<?= $unique_id ?>"
                                            name="food_access_id"
                                            class="custom-control-input score-calc"
                                            value="<?= $fa_id ?>"
                                            data-score="<?= $fa_score ?>"
                                            onchange="calculateScore()">

                                        <label class="custom-control-label" for="<?= $unique_id ?>" style="cursor: pointer;">
                                            <?= htmlspecialchars($fa_text) ?>
                                            <span class="text-muted small">(<?= $fa_score ?> คะแนน)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-danger small">ไม่พบข้อมูล (ตาราง food_access ว่างเปล่า)</p>
                            <?php endif; ?>

                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="form-group mb-4">
                        <label class="section-label">10. โรคที่เป็นอยู่ (Underlying Disease)</label>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-check-square mr-1"></i> เลือกได้มากกว่า 1 ข้อ (Select all that apply)
                        </p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="symptom-box h-100" style="border-left: 4px solid #ffc107;">
                                    <div class="symptom-category-title text-warning text-dark font-weight-bold">
                                        10.1 โรคที่มีความรุนแรงน้อยถึงปานกลาง (3 คะแนน)
                                    </div>

                                    <?php
                                    $type_mild = 'โรคที่มีความรุนแรงน้อยถึงปานกลาง';
                                    if (!empty($diseases_grouped[$type_mild])):
                                        foreach ($diseases_grouped[$type_mild] as $d):
                                    ?>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox"
                                                    class="custom-control-input score-calc"
                                                    id="dis_<?= $d['disease_id'] ?>"
                                                    name="disease_ids[]"
                                                    value="<?= $d['disease_id'] ?>"
                                                    data-score="<?= $d['disease_score'] ?>"
                                                    onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="dis_<?= $d['disease_id'] ?>">
                                                    <?= htmlspecialchars($d['disease_name']) ?>
                                                </label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>

                                    <div class="custom-control custom-checkbox symptom-item">
                                        <input type="checkbox"
                                            class="custom-control-input score-calc"
                                            id="disOtherMod"
                                            name="check_other_mild"
                                            value="other_mild"
                                            data-score="3"
                                            onchange="toggleOtherDisease(this, 'disOtherModText'); calculateScore()">
                                        <label class="custom-control-label w-100" for="disOtherMod">อื่นๆ (Other)</label>

                                        <input type="text" class="form-control form-control-sm mt-1"
                                            id="disOtherModText"
                                            name="disease_other_mild"
                                            placeholder="ระบุ..." disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="symptom-box h-100" style="border-left: 4px solid #dc3545;">
                                    <div class="symptom-category-title text-danger font-weight-bold">
                                        10.2 โรคที่มีความรุนแรงมาก (6 คะแนน)
                                    </div>

                                    <?php
                                    $type_severe = 'โรคที่มีความรุนแรงมาก';
                                    if (!empty($diseases_grouped[$type_severe])):
                                        foreach ($diseases_grouped[$type_severe] as $d):
                                    ?>
                                            <div class="custom-control custom-checkbox symptom-item">
                                                <input type="checkbox"
                                                    class="custom-control-input score-calc"
                                                    id="dis_<?= $d['disease_id'] ?>"
                                                    name="disease_ids[]"
                                                    value="<?= $d['disease_id'] ?>"
                                                    data-score="<?= $d['disease_score'] ?>"
                                                    onchange="calculateScore()">
                                                <label class="custom-control-label w-100" for="dis_<?= $d['disease_id'] ?>">
                                                    <?= htmlspecialchars($d['disease_name']) ?>
                                                </label>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>

                                    <div class="custom-control custom-checkbox symptom-item">
                                        <input type="checkbox"
                                            class="custom-control-input score-calc"
                                            id="disOtherSev"
                                            name="check_other_severe"
                                            value="other_severe"
                                            data-score="6"
                                            onchange="toggleOtherDisease(this, 'disOtherSevText'); calculateScore()">
                                        <label class="custom-control-label w-100" for="disOtherSev">อื่นๆ (Other)</label>

                                        <input type="text" class="form-control form-control-sm mt-1"
                                            id="disOtherSevText"
                                            name="disease_other_severe"
                                            placeholder="ระบุ..." disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-lg mb-4 overflow-hidden">
                        <div class="row no-gutters">
                            <div class="col-md-4 bg-light d-flex flex-column justify-content-center align-items-center p-4 border-right">
                                <h6 class="text-muted font-weight-bold text-uppercase mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">
                                    TOTAL SCORE
                                </h6>
                                <div class="d-flex align-items-baseline">
                                    <h1 class="display-3 font-weight-bold text-dark mb-0" id="totalScore" style="line-height: 1;">0</h1>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div id="nafResultBox" class="h-100 p-4 d-flex flex-column justify-content-center transition-bg" style="background-color: #e8f5e9; border-left: 5px solid #28a745;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-circle mr-2 status-dot" style="font-size: 0.8rem; color: #28a745;"></i>
                                        <h5 class="font-weight-bold mb-0 text-success" id="nafLevel">NAF A (Normal-Mild malnutrition)</h5>
                                    </div>
                                    <p class="mb-0 text-dark" id="nafDesc" style="opacity: 0.85; line-height: 1.6;">
                                        ไม่พบความเสี่ยงต่อการเกิดภาวะทุพโภชนาการ พยาบาลจะทำหน้าที่ประเมินภาวะโภชนาการซ้ำภายใน 7 วัน
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions-box d-flex justify-content-between mt-4 mb-5">
                        <button type="button" class="btn btn-secondary shadow-sm px-4"
                            onclick="window.location.href='patient_profile.php?hn=<?= htmlspecialchars($hn) ?>&an=<?= htmlspecialchars($an) ?>'">
                            <i class="fa-solid fa-chevron-left mr-2"></i> ยกเลิก / ย้อนกลับ
                        </button>
                        <button type="button" class="btn btn-success shadow-sm px-4" style="background-color: #2e7d32; border: none;" onclick="saveData()">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> บันทึกการประเมิน
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ฟังก์ชันเปิด/ปิด ช่องระบุอื่นๆ
        function toggleOtherSource() {
            const otherRadio = document.getElementById('source3');
            const otherInput = document.getElementById('otherSourceText');
            if (otherRadio && otherInput) {
                otherInput.disabled = !otherRadio.checked;
                if (otherRadio.checked) otherInput.focus();
                else otherInput.value = '';
            }
        }

        function toggleOtherDisease(checkbox, inputId) {
            const inputField = document.getElementById(inputId);
            if (inputField) {
                inputField.disabled = !checkbox.checked;
                if (checkbox.checked) inputField.focus();
                else inputField.value = '';
            }
        }

        // ฟังก์ชันคำนวณ BMI และให้คะแนนอัตโนมัติ
        function calculateBMI() {
            // ดึงค่า
            const weight = parseFloat(document.getElementById('currentWeight').value) || 0;
            const h1 = parseFloat(document.getElementById('anthroHeight').value) || 0;
            const h2 = parseFloat(document.getElementById('anthroLength').value) || 0;
            const h3 = parseFloat(document.getElementById('anthroArmSpan').value) || 0;
            const h4 = parseFloat(document.getElementById('anthroReported').value) || 0;

            // ใช้ค่าส่วนสูงที่มากที่สุดที่มีการกรอก
            const maxHeight = Math.max(h1, h2, h3, h4);

            if (weight > 0 && maxHeight > 0) {
                const heightInMeters = maxHeight / 100;
                const bmi = weight / (heightInMeters * heightInMeters);

                // แสดงผล BMI
                document.getElementById('bmiValue').value = bmi.toFixed(2);

                // อัปเดตลง Hidden Input สำหรับส่งเข้า Database
                document.getElementById('hidden_bmi').value = bmi.toFixed(2);

                // คำนวณคะแนน BMI ตามเกณฑ์
                // < 17 (2 คะแนน), 17-18 (1 คะแนน), 18.1-29.9 (0 คะแนน), > 30 (1 คะแนน)
                let bmiScore = 0;
                if (bmi < 17.0) {
                    bmiScore = 2;
                } else if (bmi <= 18.0) {
                    bmiScore = 1;
                } else if (bmi >= 30.0) {
                    bmiScore = 1;
                } else {
                    bmiScore = 0; // 18.1 - 29.9 (รวมถึง 30 เป๊ะๆ ถ้าอิงตามตรรกะ >30)
                }

                // แสดงคะแนนและอัปเดต Hidden Input
                document.getElementById('bmiScoreText').innerText = "Score: " + bmiScore;
                document.getElementById('hidden_bmi_score').value = bmiScore;

            } else {
                // กรณีข้อมูลไม่ครบ
                document.getElementById('bmiValue').value = "-";
                document.getElementById('hidden_bmi').value = "";
                document.getElementById('bmiScoreText').innerText = "Score: 0";
                document.getElementById('hidden_bmi_score').value = 0;
            }

            // เรียกคำนวณคะแนนรวมใหม่
            calculateScore();
        }

        // สลับโหมด น้ำหนัก vs Lab
        function toggleWeightMode() {
            const isUnknown = document.getElementById('unknownWeight').checked;
            const weightSec = document.getElementById('standardWeightSection');
            const labSec = document.getElementById('labSection');

            if (isUnknown) {
                // โหมดไม่รู้น้ำหนัก -> ใช้ Lab
                weightSec.style.display = 'none';
                labSec.classList.remove('hidden-section');

                // Reset คะแนน BMI เป็น 0
                document.getElementById('hidden_bmi_score').value = 0;
                document.querySelectorAll('input[name="weight_option_id"]').forEach(el => el.checked = false);

            } else {
                // โหมดรู้น้ำหนัก -> ใช้ BMI
                weightSec.style.display = 'block';
                labSec.classList.add('hidden-section');

                // Reset คะแนน Lab เป็น 0
                document.getElementById('hidden_lab_score').value = 0;
                calculateBMI();
            }
            calculateScore();
        }

        // เลือก Lab (Albumin / TLC)
        function selectLab(type) {
            // Reset Card Styles
            document.querySelectorAll('.lab-choice-card').forEach(card => {
                card.classList.remove('active', 'border-primary');
                card.classList.add('inactive');
            });

            const selectedCard = (type === 'albumin') ? document.getElementById('cardAlbumin') : document.getElementById('cardTLC');
            selectedCard.classList.remove('inactive');
            selectedCard.classList.add('active', 'border-primary');

            // Check Radio Button
            if (type === 'albumin') {
                document.getElementById('useAlbumin').checked = true;
            } else {
                document.getElementById('useTLC').checked = true;
            }
            toggleLabInputs();
        }

        function toggleLabInputs() {
            const useAlb = document.getElementById('useAlbumin').checked;
            const useTLC = document.getElementById('useTLC').checked;
            const inpAlb = document.getElementById('valAlbumin');
            const inpTLC = document.getElementById('valTLC');

            inpAlb.disabled = !useAlb;
            inpTLC.disabled = !useTLC;

            if (useAlb) {
                inpAlb.focus();
                inpTLC.value = '';
            }
            if (useTLC) {
                inpTLC.focus();
                inpAlb.value = '';
            }

            calculateLabScore();
        }

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
                    else labScore = 0;
                }
            } else if (useTLC) {
                const val = parseFloat(document.getElementById('valTLC').value);
                if (!isNaN(val)) {
                    if (val <= 1000) labScore = 3;
                    else if (val <= 1200) labScore = 2;
                    else if (val <= 1500) labScore = 1;
                    else labScore = 0;
                }
            }

            // แสดงผลคะแนน Lab
            document.getElementById('labScoreText').innerText = labScore;

            // อัปเดตลง Hidden Input
            document.getElementById('hidden_lab_score').value = labScore;

            calculateScore();
        }

        //คำนวณคะแนนรวม
        function calculateScore() {
            let total = 0;

            // รวมคะแนนจาก Radio/Checkbox
            const inputs = document.querySelectorAll('.score-calc:checked');
            inputs.forEach(el => {
                const sc = parseInt(el.getAttribute('data-score'));
                if (!isNaN(sc)) total += sc;
            });

            // ตรวจสอบโหมด (น้ำหนัก หรือ Lab)
            const isUnknownWeight = document.getElementById('unknownWeight').checked;

            if (isUnknownWeight) {
                // กรณีใช้ Lab: บวกคะแนน Lab
                const labScore = parseInt(document.getElementById('hidden_lab_score').value) || 0;
                total += labScore;
            } else {
                // กรณีใช้น้ำหนัก: บวกคะแนน BMI
                const bmiScore = parseInt(document.getElementById('hidden_bmi_score').value) || 0;
                total += bmiScore;
            }

            // แสดงผลรวม
            document.getElementById('totalScore').innerText = total;
            updateNafResult(total);
        }

        // อัปเดต UI ผลลัพธ์ (NAF Level)
        function updateNafResult(score) {
            const box = document.getElementById('nafResultBox');
            const level = document.getElementById('nafLevel');
            const desc = document.getElementById('nafDesc');
            const dot = box.querySelector('.status-dot');

            if (score <= 5) {
                // NAF A
                box.style.backgroundColor = '#e8f5e9';
                box.style.borderLeft = '5px solid #28a745';
                level.innerText = 'NAF A (Normal-Mild malnutrition)';
                level.className = 'font-weight-bold mb-0 text-success';
                desc.innerText = 'ไม่พบความเสี่ยงต่อการเกิดภาวะทุพโภชนาการ พยาบาลจะทำหน้าที่ประเมินภาวะโภชนาการซ้ำภายใน 7 วัน';
                dot.style.color = '#28a745';
            } else if (score <= 10) {
                // NAF B
                box.style.backgroundColor = '#fff3cd';
                box.style.borderLeft = '5px solid #ffc107';
                level.innerText = 'NAF B (Moderate malnutrition)';
                level.className = 'font-weight-bold mb-0 text-warning';
                desc.innerText = 'กรุณาแจ้งให้แพทย์และนักกำหนดอาหาร/นักโภชนาการทราบผลทันที พบความเสี่ยงต่อการเกิดภาวะโภชนาการ ให้นักกำหนดอาหาร/นักโภชนาการ ทำการประเมินภาวะโภชนาการและให้แพทย์ทำการดูแลรักษาภายใน 3 วัน';
                dot.style.color = '#ffc107';
            } else {
                // NAF C
                box.style.backgroundColor = '#ffebee';
                box.style.borderLeft = '5px solid #dc3545';
                level.innerText = 'NAF C (Severe malnutrition)';
                level.className = 'font-weight-bold mb-0 text-danger';
                desc.innerText = 'กรุณาแจ้งให้แพทย์และนักกำหนดอาหาร/นักโภชนาการทราบผลทันทีมีภาวะทุพโภชนาการ ให้นักกำหนดอาหาร/นักโภชนาการทำการประเมินภาวะโภชนาการ และให้แพทย์ทำการดูแลรักษาภายใน 24 ชั่วโมง';
                dot.style.color = '#dc3545';
            }
        }

        // บันทึกข้อมูล
        function saveData() {
            // ตรวจสอบขั้นพื้นฐาน (Validation)
            const isUnknownWeight = document.getElementById('unknownWeight').checked;
            if (!isUnknownWeight) {
                // ถ้ารู้น้ำหนัก แต่ยังไม่กรอกน้ำหนักหรือส่วนสูง (ทำให้ BMI หาไม่ได้)
                const weight = document.getElementById('currentWeight').value;
                const hiddenBmi = document.getElementById('hidden_bmi').value;
                if (!weight || !hiddenBmi) {
                    alert('กรุณาระบุน้ำหนักและส่วนสูงให้ครบถ้วน');
                    document.getElementById('currentWeight').focus();
                    return;
                }
            } else {
                // ถ้าไม่รู้น้ำหนัก ต้องเลือก Lab อย่างใดอย่างหนึ่ง
                const useAlb = document.getElementById('useAlbumin').checked;
                const useTLC = document.getElementById('useTLC').checked;
                if (!useAlb && !useTLC) {
                    alert('กรุณาเลือกและระบุค่าผลเลือด (Albumin หรือ TLC)');
                    return;
                }
            }

            if (confirm('ยืนยันการบันทึกข้อมูลการประเมิน?')) {
                document.getElementById('nafForm').submit();
            }
        }

        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) {
                // Create form to POST to logout
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'logout.php';

                // Add CSRF token
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = 'csrf_token';
                token.value = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
                form.appendChild(token);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>