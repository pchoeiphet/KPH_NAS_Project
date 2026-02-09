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
    die("ข้อผิดพลาด: หมดเวลากำรใช้งาน");
}
$_SESSION['last_activity'] = time();

// รับค่าเลขที่เอกสาร
$doc_no = trim($_GET['doc_no'] ?? '');
if (empty($doc_no) || !preg_match('/^[A-Z]+-[A-Za-z0-9\-]+$/', $doc_no)) {
    error_log("Invalid doc_no parameter: $doc_no");
    die("ข้อผิดพลาด: พารามิเตอร์ไม่ถูกต้อง");
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// เตรียมตัวแปร Master Data
$weight_options = [];
$patient_shapes = [];
$weight_changes = [];
$food_types = [];
$food_amounts = [];
$symptoms_grouped = [];

// ตัวแปรสำหรับเก็บข้อมูลที่บันทึกไว้
$data = [];
$saved_symptoms = [];

try {
    // --- (A) ดึง Master Data ต่างๆ ---
    $stmt = $conn->query("SELECT * FROM weight_option ORDER BY weight_option_id");
    $weight_options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM patient_shape ORDER BY patient_shape_id");
    $patient_shapes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM weight_change_4_weeks ORDER BY weight_change_4_weeks_id");
    $weight_changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM food_type ORDER BY food_type_id");
    $food_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM food_amount ORDER BY food_amount_id");
    $food_amounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM food_access ORDER BY food_access_id");
    $food_access_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM symptom_problem ORDER BY symptom_problem_id");
    $all_symptoms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_symptoms as $sym) {
        $type = $sym['symptom_problem_type'];
        $symptoms_grouped[$type][] = $sym;
    }

    $sql_main = "
        SELECT 
            nutrition_assessment.*, 
            nutritionists.nut_fullname,   -- 1. เพิ่มบรรทัดนี้ เพื่อดึงชื่อจริง
            patients.patients_firstname, 
            patients.patients_lastname, 
            patients.patients_hn, 
            patients.patients_dob, 
            patients.patients_phone, 
            patients.patients_congenital_disease,
            admissions.admissions_an, 
            admissions.bed_number, 
            admissions.admit_datetime,
            wards.ward_name, 
            doctor.doctor_name, 
            health_insurance.health_insurance_name
        FROM nutrition_assessment
        LEFT JOIN nutritionists ON nutrition_assessment.nut_id = nutritionists.nut_id

        JOIN patients ON nutrition_assessment.patients_hn = patients.patients_hn
        JOIN admissions ON nutrition_assessment.admissions_an = admissions.admissions_an
        LEFT JOIN wards ON admissions.ward_id = wards.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        LEFT JOIN health_insurance ON admissions.health_insurance_id = health_insurance.health_insurance_id
        WHERE nutrition_assessment.doc_no = :doc_no 
        LIMIT 1
    ";
    $stmt_main = $conn->prepare($sql_main);
    $stmt_main->execute([':doc_no' => $doc_no]);
    $data = $stmt_main->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        error_log("NAF form not found: doc_no=$doc_no, user=" . $_SESSION['user_id']);
        die("ข้อผิดพลาด: ไม่พบข้อมูลเอกสาร");
    }

    // คำนวณอายุ
    $age = '-';
    if (!empty($data['patients_dob'])) {
        $diff = date_diff(date_create($data['patients_dob']), date_create('today'));
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน ' . $diff->d . ' วัน';
    }

    // วันที่ Admit
    $admit_date = '-';
    if (!empty($data['admit_datetime'])) {
        $dt = new DateTime($data['admit_datetime']);
        $admit_date = $dt->format('d/m/') . ($dt->format('Y') + 543) . ' ' . $dt->format('H:i') . ' น.';
    }

    if (!empty($data['nutrition_assessment_id'])) {
        $sql_sym = "SELECT symptom_problem_id FROM symptom_problem_saved WHERE nutrition_assessment_id = :id";
        $stmt_sym = $conn->prepare($sql_sym);
        $stmt_sym->execute([':id' => $data['nutrition_assessment_id']]);

        // ดึงออกมาเป็น Array ของ ID (เช่น [1, 5, 8])
        $saved_symptoms = $stmt_sym->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $saved_symptoms = [];
    }

    $saved_disease_ids = [];
    $saved_disease_other_mild = '';
    $saved_disease_other_severe = '';

    if (!empty($data['nutrition_assessment_id'])) {
        $sql_dis = "SELECT * FROM disease_saved WHERE nutrition_assessment_id = :id";
        $stmt_dis = $conn->prepare($sql_dis);
        $stmt_dis->execute([':id' => $data['nutrition_assessment_id']]);
        $saved_diseases_rows = $stmt_dis->fetchAll(PDO::FETCH_ASSOC);

        foreach ($saved_diseases_rows as $row) {
            if (!empty($row['disease_id'])) {
                $saved_disease_ids[] = $row['disease_id'];
            }
            if (!empty($row['disease_other_name'])) {
                if ($row['disease_type'] == 'โรคที่มีความรุนแรงน้อยถึงปานกลาง') {
                    $saved_disease_other_mild = $row['disease_other_name'];
                } elseif ($row['disease_type'] == 'โรคที่มีความรุนแรงมาก') {
                    $saved_disease_other_severe = $row['disease_other_name'];
                }
            }
        }
    }

    // Also fetch Master Data for Diseases if not already done
    $stmt = $conn->query("SELECT * FROM disease ORDER BY disease_id");
    $all_diseases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $diseases_grouped = [];
    foreach ($all_diseases as $d) {
        $diseases_grouped[$d['disease_type']][] = $d;
    }

    $total_score = isset($data['total_score']) ? (int)$data['total_score'] : 0;

    // กำหนดตัวแปรสำหรับแสดงผลตามเงื่อนไขที่ให้มา
    if ($total_score <= 5) {
        // NAF A
        $box_bg = '#e8f5e9';
        $border_color = '#28a745';
        $text_color = '#28a745';
        $naf_title = 'NAF A (Normal-Mild Malnutrition)';
        $naf_desc = 'ไม่พบความเสี่ยงต่อการเกิดภาวะทุพโภชนาการ พยาบาลจะทำหน้าที่ประเมินภาวะโภชนาการซ้ำภายใน 7 วัน';
    } elseif ($total_score <= 10) {
        // NAF B
        $box_bg = '#fff3cd';
        $border_color = '#ffc107';
        $text_color = '#ffc107';
        $naf_title = 'NAF B (Moderate Malnutrition)';
        $naf_desc = 'กรุณาแจ้งให้แพทย์และนักกำหนดอาหาร/นักโภชนาการทราบผลทันที พบความเสี่ยงต่อการเกิดภาวะโภชนาการ ให้นักกำหนดอาหาร/นักโภชนาการ ทำการประเมินภาวะโภชนาการและให้แพทย์ทำการดูแลรักษาภายใน 3 วัน';
    } else {
        // NAF C (คะแนน 11 ขึ้นไป)
        $box_bg = '#ffebee';
        $border_color = '#dc3545';
        $text_color = '#dc3545';
        $naf_title = 'NAF C (Severe Malnutrition)';
        $naf_desc = 'กรุณาแจ้งให้แพทย์และนักกำหนดอาหาร/นักโภชนาการทราบผลทันทีมีภาวะทุพโภชนาการ ให้นักกำหนดอาหาร/นักโภชนาการทำการประเมินภาวะโภชนาการ และให้แพทย์ทำการดูแลรักษาภายใน 24 ชั่วโมง';
    }
} catch (PDOException $e) {
    error_log("Database error in nutrition_alert_form_view.php: " . $e->getMessage());
    die("ข้อผิดพลาด: ไม่สามารถดึงข้อมูลได้");
}

// Helper Functions
function isChecked($val, $db_val)
{
    return ($val == $db_val) ? 'checked' : '';
}

// ฟังก์ชันเช็ค Checkbox อาการ
function isSymChecked($id, $saved_array)
{
    if (empty($saved_array) || !is_array($saved_array)) {
        return '';
    }
    return in_array($id, $saved_array) ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการประเมินภาวะโภชนาการ | โรงพยาบาลกำแพงเพชร</title>
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
                            <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">HN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['patients_hn']) ?></span></div>
                            <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">AN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['admissions_an']) ?></span></div>
                            <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">ชื่อ - นามสกุล</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['patients_firstname'] . ' ' . $data['patients_lastname']) ?></span></div>
                            <div class="col-6 col-md-4 col-lg-2 mb-3"><small class="text-muted d-block" style="font-size: 0.95rem;">อายุ</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($age) ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">หอผู้ป่วย</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['ward_name'] ?? '-') ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เตียง</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['bed_number'] ?? '-') ?></span></div>

                            <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">แพทย์เจ้าของไข้</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['doctor_name'] ?? '-') ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">วันที่ Admit</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($admit_date) ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เบอร์โทรศัพท์</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['patients_phone'] ?? '-') ?></span></div>

                            <div class="col-12 col-md-6 col-lg-2 mb-3">
                                <small class="text-muted d-block">โรคประจำตัว</small>
                                <span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['patients_congenital_disease'] ?? '-') ?></span>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <small class="text-muted d-block">สิทธิการรักษา</small>
                                <span class="font-weight-bold" style="font-size: 0.95rem;"><?= htmlspecialchars($data['health_insurance_name'] ?? '-') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 no-print">

            <div>
                <button type="button" class="btn btn-secondary btn-sm shadow-sm"
                    style="border-radius: 6px;"
                    onclick="window.location.href='patient_profile.php?hn=<?= htmlspecialchars($data['patients_hn'] ?? '') ?>&an=<?= htmlspecialchars($data['admissions_an'] ?? '') ?>';">
                    <i class="fa-solid fa-chevron-left mr-1"></i> ย้อนกลับ
                </button>
            </div>

            <div class="d-flex align-items-center">

                <div class="alert alert-warning py-1 px-3 mb-0 mr-2 shadow-sm d-flex align-items-center"
                    style="font-size: 0.85rem; border-radius: 6px; height: 31px; border: 1px solid #ffeeba;">
                    <i class="fa-solid fa-eye mr-2"></i> โหมดดูประวัติ (Read Only)
                </div>

                <a href="nutrition_alert_form_report.php?doc_no=<?= htmlspecialchars($data['doc_no'] ?? '') ?>"
                    target="_blank"
                    class="btn btn-info btn-sm shadow-sm px-3"
                    style="border-radius: 6px;">
                    <i class="fas fa-file-pdf mr-1"></i> ดาวน์โหลด PDF
                </a>
            </div>

        </div>

        <div class="card mb-5 border-0 shadow-sm" style="border-top: 5px solid #33691e!important;">
            <div class="form-header-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-1 font-weight-bold text-dark" style="color: #33691e;">แบบประเมินภาวะโภชนาการ (NAF)</h4>
                        <small class="text-muted">Nutrition Alert Form - Read Only</small>
                    </div>
                    <div class="text-right">
                        <span class="badge p-2" style="background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; font-weight: 500; font-size: 0.85rem;">
                            No.: <?= htmlspecialchars($doc_no) ?>
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-2 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ครั้งที่</span></div>
                            <input type="text" class="form-control text-center font-weight-bold text-primary" value="<?= htmlspecialchars($data['naf_seq']) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">วันที่</span></div>
                            <input type="text" class="form-control text-center" value="<?= date('d/m/', strtotime($data['assessment_datetime'])) . (date('Y', strtotime($data['assessment_datetime'])) + 543) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">เวลา</span></div>
                            <input type="text" class="form-control text-center" value="<?= date('H:i', strtotime($data['assessment_datetime'])) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white text-muted">ผู้ประเมิน</span>
                            </div>
                            <input type="text" class="form-control text-center text-primary"
                                value="<?= htmlspecialchars(!empty($data['nut_fullname']) ? $data['nut_fullname'] : ($data['assessor_name'] ?? '-')) ?>"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">

                <div class="form-group mb-4">
                    <label class="section-label">1. การวินิจฉัยเบื้องต้น (Provisional Diagnosis)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($data['initial_diagnosis'] ?? '') ?>" disabled>
                </div>

                <hr class="my-4">

                <div class="form-group mb-4">
                    <label class="section-label">2. ข้อมูลได้จาก (Source of Information)</label>

                    <div class="d-flex align-items-center">
                        <div class="custom-control custom-radio custom-control-inline mr-4">
                            <input type="radio" class="custom-control-input"
                                id="source_view_1"
                                <?= isChecked(trim($data['info_source'] ?? ''), 'ผู้ป่วย') ?> disabled>
                            <label class="custom-control-label" for="source_view_1">ผู้ป่วย</label>
                        </div>

                        <div class="custom-control custom-radio custom-control-inline mr-4">
                            <input type="radio" class="custom-control-input"
                                id="source_view_2"
                                <?= isChecked(trim($data['info_source'] ?? ''), 'ญาติ') ?> disabled>
                            <label class="custom-control-label" for="source_view_2">ญาติ</label>
                        </div>

                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="custom-control custom-radio custom-control-inline mr-2">
                                <input type="radio" class="custom-control-input"
                                    id="source_view_3"
                                    <?= isChecked(trim($data['info_source'] ?? ''), 'อื่นๆ') ?> disabled>
                                <label class="custom-control-label" for="source_view_3">อื่นๆ</label>
                            </div>
                            <input type="text" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($data['other_source'] ?? '') ?>"
                                disabled style="max-width: 300px;">
                        </div>
                    </div>
                </div>
                <hr class="my-4">

                <div class="form-group mb-4">
                    <label class="section-label">3. สัดส่วนร่างกาย (Anthropometry)</label>
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="small text-muted font-weight-bold">ส่วนสูง (Height)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['height_measure'] ?? '') ?>" disabled>
                                <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="small text-muted font-weight-bold">วัดความยาวตัว (Length)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['body_length'] ?? '') ?>" disabled>
                                <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="small text-muted font-weight-bold">Arm Span</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['arm_span'] ?? '') ?>" disabled>
                                <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label class="small text-muted font-weight-bold">ญาติบอก (Reported)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['height_relative'] ?? '') ?>" disabled>
                                <div class="input-group-append"><span class="input-group-text small">ซม.</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="form-group mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="section-label mb-0">4. น้ำหนักและค่าดัชนีมวลกาย (Weight & BMI)</label>
                        <?php if (!empty($data['is_no_weight'])): ?>
                            <span class="badge badge-danger p-2"><i class="fas fa-exclamation-circle"></i> ผู้ป่วยไม่ทราบน้ำหนัก (ประเมินด้วยผลเลือด)</span>
                        <?php endif; ?>
                    </div>

                    <div id="standardWeightSection">
                        <div class="row">
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label class="small text-muted font-weight-bold">น้ำหนัก (Weight)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($data['weight'] ?? '') ?>" disabled>
                                    <div class="input-group-append"><span class="input-group-text">กก.</span></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <label class="small text-muted font-weight-bold">ดัชนีมวลกาย (BMI)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-weight-bold" value="<?= htmlspecialchars($data['bmi'] ?? '') ?>" disabled>
                                    <div class="input-group-append">
                                        <span class="input-group-text small font-weight-bold bg-light">Score: <?= htmlspecialchars($data['bmi_score'] ?? '') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted font-weight-bold mb-1">วิธีการชั่งน้ำหนัก (Method)</label>
                            <div class="radio-group-container">
                                <?php foreach ($weight_options as $wo): ?>
                                    <div class="custom-control custom-radio custom-control-inline mr-4">
                                        <input type="radio" class="custom-control-input"
                                            <?= isChecked($data['weight_option_id'], $wo['weight_option_id']) ?> disabled>
                                        <label class="custom-control-label">
                                            <?= htmlspecialchars($wo['weight_option_label'] ?? $wo['weight_option_name']) ?>
                                            <span class="text-muted small">(<?= $wo['weight_option_score'] ?> คะแนน)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <?php
                    $lab_class_alb = ($data['lab_method'] == 'Albumin') ? 'active' : 'inactive';
                    $lab_class_tlc = ($data['lab_method'] == 'TLC') ? 'active' : 'inactive';
                    ?>
                    <div id="labSection" class="mt-4">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="lab-choice-card <?= $lab_class_alb ?>">
                                    <div class="lab-header">
                                        <i class="fas fa-vial text-primary mr-2"></i> 1. Albumin (g/dl)
                                        <?php if ($data['lab_method'] == 'Albumin'): ?> <i class="fas fa-check-circle text-success float-right"></i> <?php endif; ?>
                                    </div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['albumin_val'] ?? '') ?>" disabled>
                                        <div class="input-group-append"><span class="input-group-text bg-white text-muted">g/dl</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="lab-choice-card <?= $lab_class_tlc ?>">
                                    <div class="lab-header">
                                        <i class="fas fa-microscope text-primary mr-2"></i> 2. TLC (cells/mm³)
                                        <?php if ($data['lab_method'] == 'TLC'): ?> <i class="fas fa-check-circle text-success float-right"></i> <?php endif; ?>
                                    </div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['tlc_val'] ?? '') ?>" disabled>
                                        <div class="input-group-append"><span class="input-group-text bg-white text-muted">cells</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($data['lab_score']) && $data['lab_score'] > 0): ?>
                            <div class="row mt-3">
                                <div class="col-12 text-right">
                                    <div class="d-inline-block px-3 py-2 bg-white border rounded shadow-sm">
                                        <small class="text-muted mr-2">คะแนนจากผลเลือด (Lab Score):</small>
                                        <span class="font-weight-bold text-primary h5 m-0"><?= htmlspecialchars($data['lab_score']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="section-label">5. รูปร่างของผู้ป่วย (Body Shape)</label>
                    <div class="radio-group-container">
                        <?php foreach ($patient_shapes as $row): ?>
                            <div class="custom-control custom-radio custom-control-inline mr-4 mb-2">
                                <input type="radio" class="custom-control-input"
                                    <?= isChecked($data['patient_shape_id'], $row['patient_shape_id']) ?> disabled>
                                <label class="custom-control-label">
                                    <?= htmlspecialchars($row['patient_shape_label']) ?>
                                    <span class="text-muted small">(<?= $row['patient_shape_score'] ?> คะแนน)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div class="form-group mb-4">
                    <label class="section-label">6. น้ำหนักเปลี่ยนใน 4 สัปดาห์ (Weight Change)</label>
                    <div class="radio-group-container">
                        <?php foreach ($weight_changes as $row): ?>
                            <div class="custom-control custom-radio custom-control-inline mr-4 mb-2">
                                <input type="radio" class="custom-control-input"
                                    <?= isChecked($data['weight_change_4_weeks_id'], $row['weight_change_4_weeks_id']) ?> disabled>
                                <label class="custom-control-label">
                                    <?= htmlspecialchars($row['weight_change_4_weeks_label']) ?>
                                    <span class="text-muted small">(<?= $row['weight_change_4_weeks_score'] ?> คะแนน)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div class="form-group mb-4">
                    <label class="section-label">7. อาหารที่กินในช่วง 2 สัปดาห์ที่ผ่านมา</label>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-secondary font-weight-bold mb-2">7.1 ลักษณะของอาหาร (Type)</h6>
                            <div class="radio-group-container">
                                <?php foreach ($food_types as $row): ?>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" class="custom-control-input"
                                            <?= isChecked($data['food_type_id'], $row['food_type_id']) ?> disabled>
                                        <label class="custom-control-label radio-label">
                                            <?= htmlspecialchars($row['food_type_label']) ?>
                                            <span class="radio-score">(<?= $row['food_type_score'] ?> คะแนน)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <h6 class="text-secondary font-weight-bold mb-2">7.2 ปริมาณอาหารที่กิน (Amount)</h6>
                            <div class="radio-group-container">
                                <?php foreach ($food_amounts as $row): ?>
                                    <div class="custom-control custom-radio mb-2">
                                        <input type="radio" class="custom-control-input"
                                            <?= isChecked($data['food_amount_id'], $row['food_amount_id']) ?> disabled>
                                        <label class="custom-control-label radio-label">
                                            <?= htmlspecialchars($row['food_amount_label']) ?>
                                            <span class="radio-score">(<?= $row['food_amount_score'] ?> คะแนน)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="form-group mb-4">
                    <label class="section-label">8. อาการต่อเนื่อง > 2 สัปดาห์ที่ผ่านมา</label>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="symptom-box h-100">
                                <div class="symptom-category-title">8.1 ปัญหาทางการเคี้ยว/กลืน</div>
                                <?php
                                $group1 = 'ปัญหาทางการเคี้ยว/กลืนอาหาร';
                                if (!empty($symptoms_grouped[$group1])):
                                    foreach ($symptoms_grouped[$group1] as $item): ?>
                                        <div class="custom-control custom-checkbox symptom-item">
                                            <input type="checkbox" class="custom-control-input"
                                                <?= isSymChecked($item['symptom_problem_id'], $saved_symptoms) ?>
                                                disabled>
                                            <label class="custom-control-label w-100">
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
                                <div class="symptom-category-title">8.2 ปัญหาทางเดินอาหาร</div>
                                <?php
                                $group2 = 'ปัญหาระบบทางเดินอาหาร';
                                if (!empty($symptoms_grouped[$group2])):
                                    foreach ($symptoms_grouped[$group2] as $item): ?>
                                        <div class="custom-control custom-checkbox symptom-item">
                                            <input type="checkbox" class="custom-control-input"
                                                <?= isSymChecked($item['symptom_problem_id'], $saved_symptoms) ?> disabled>
                                            <label class="custom-control-label w-100">
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
                                    foreach ($symptoms_grouped[$group3] as $item): ?>
                                        <div class="custom-control custom-checkbox symptom-item">
                                            <input type="checkbox" class="custom-control-input"
                                                <?= isSymChecked($item['symptom_problem_id'], $saved_symptoms) ?> disabled>
                                            <label class="custom-control-label w-100">
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

                    <div class="radio-group-container" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 15px;">

                        <?php if (!empty($food_access_list)): ?>
                            <?php foreach ($food_access_list as $fa): ?>
                                <?php
                                $fa_id = $fa['food_access_id'];
                                $unique_id = "f_access_view_" . $fa_id;
                                $fa_text = $fa['food_access_label'] ?? $fa['food_access_name'] ?? '-';
                                $fa_score = $fa['food_access_score'] ?? 0;
                                ?>

                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio"
                                        id="<?= $unique_id ?>"
                                        class="custom-control-input"
                                        name="food_access_id"
                                        <?= isChecked($data['food_access_id'] ?? '', $fa_id) ?>

                                        disabled> <label class="custom-control-label" for="<?= $unique_id ?>">
                                        <?= htmlspecialchars($fa_text) ?>
                                        <span class="text-muted small">(<?= $fa_score ?> คะแนน)</span>
                                    </label>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small">ไม่พบข้อมูลตัวเลือก (Food Access)</p>
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
                                                class="custom-control-input"
                                                id="dis_view_<?= $d['disease_id'] ?>"
                                                value="<?= $d['disease_id'] ?>"
                                                <?= isSymChecked($d['disease_id'], $saved_disease_ids) ?>
                                                disabled>
                                            <label class="custom-control-label w-100" for="dis_view_<?= $d['disease_id'] ?>">
                                                <?= htmlspecialchars($d['disease_name']) ?>
                                            </label>
                                        </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>

                                <div class="custom-control custom-checkbox symptom-item">
                                    <input type="checkbox"
                                        class="custom-control-input"
                                        id="disOtherMod"
                                        <?= !empty($saved_disease_other_mild) ? 'checked' : '' ?>
                                        disabled>
                                    <label class="custom-control-label w-100" for="disOtherMod">อื่นๆ (Other)</label>

                                    <input type="text" class="form-control form-control-sm mt-1"
                                        value="<?= htmlspecialchars($saved_disease_other_mild) ?>"
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
                                                class="custom-control-input"
                                                id="dis_view_<?= $d['disease_id'] ?>"
                                                value="<?= $d['disease_id'] ?>"
                                                <?= isSymChecked($d['disease_id'], $saved_disease_ids) ?>
                                                disabled>
                                            <label class="custom-control-label w-100" for="dis_view_<?= $d['disease_id'] ?>">
                                                <?= htmlspecialchars($d['disease_name']) ?>
                                            </label>
                                        </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>

                                <div class="custom-control custom-checkbox symptom-item">
                                    <input type="checkbox"
                                        class="custom-control-input"
                                        id="disOtherSev"
                                        <?= !empty($saved_disease_other_severe) ? 'checked' : '' ?>
                                        disabled>
                                    <label class="custom-control-label w-100" for="disOtherSev">อื่นๆ (Other)</label>

                                    <input type="text" class="form-control form-control-sm mt-1"
                                        value="<?= htmlspecialchars($saved_disease_other_severe) ?>"
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
                                <h1 class="display-3 font-weight-bold text-dark mb-0" style="line-height: 1;">
                                    <?= $total_score ?>
                                </h1>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="h-100 p-4 d-flex flex-column justify-content-center"
                                style="background-color: <?= $box_bg ?>; border-left: 5px solid <?= $border_color ?>;">

                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-circle mr-2" style="font-size: 0.8rem; color: <?= $text_color ?>;"></i>

                                    <h5 class="font-weight-bold mb-0" style="color: <?= $text_color ?>;">
                                        <?= htmlspecialchars($naf_title) ?>
                                    </h5>
                                </div>

                                <p class="mb-0 text-dark" style="opacity: 0.85; line-height: 1.6;">
                                    <?= htmlspecialchars($naf_desc) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <script>
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