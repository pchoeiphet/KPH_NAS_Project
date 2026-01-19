<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

$hn = $_GET['hn'] ?? '';

if (empty($hn)) {
    die("Error: ไม่พบข้อมูล HN");
}

try {
    // ดึงข้อมูลผู้ป่วย
    $sql_patient = "
        SELECT 
            patients.patients_hn, 
            patients.patients_firstname, 
            patients.patients_lastname, 
            patients.patients_dob, 
            patients.patients_phone, 
            patients.patients_congenital_disease,
            admissions.admissions_an, 
            admissions.admit_datetime, 
            admissions.bed_number, 
            wards.ward_name, 
            doctor.doctor_name,
            health_insurance.health_insurance_name
        FROM patients
        JOIN admissions ON patients.patients_id = admissions.patients_id
        LEFT JOIN wards ON admissions.ward_id = wards.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        LEFT JOIN health_insurance ON admissions.health_insurance_id = health_insurance.health_insurance_id
        WHERE patients.patients_hn = :hn
        ORDER BY admissions.admit_datetime DESC 
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql_patient);
    $stmt->execute([':hn' => $hn]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) die("ไม่พบข้อมูลผู้ป่วยในระบบ");

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
        $admit_date = $dt->format('d/m/') . $thai_year . ' ' . $dt->format('H:i') . ' น.';
    }

    // ดึงประวัติ (SPENT)
    $sql_spent = "
        SELECT *, 'SPENT' as form_type, screening_datetime as action_datetime 
        FROM nutrition_screening 
        WHERE patients_hn = :hn 
        ORDER BY screening_datetime DESC
    ";
    $stmt_spent = $conn->prepare($sql_spent);
    $stmt_spent->execute([':hn' => $hn]);
    $spent_list = $stmt_spent->fetchAll(PDO::FETCH_ASSOC);

    // ดึงประวัติ (NAF)
    $sql_naf = "
        SELECT *, 'NAF' as form_type, assessment_datetime as action_datetime 
        FROM nutrition_assessment 
        WHERE patients_hn = :hn 
        ORDER BY assessment_datetime DESC
    ";
    $stmt_naf = $conn->prepare($sql_naf);
    $stmt_naf->execute([':hn' => $hn]);
    $naf_list = $stmt_naf->fetchAll(PDO::FETCH_ASSOC);

    // รวมข้อมูลและเรียงลำดับตามเวลาล่าสุด
    $history_list = array_merge($spent_list, $naf_list);

    // เรียงลำดับ array ตาม action_datetime จากมากไปน้อย (ล่าสุดขึ้นก่อน)
    usort($history_list, function ($a, $b) {
        return strtotime($b['action_datetime']) - strtotime($a['action_datetime']);
    });
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// ฟังก์ชันแปลงวันที่
function thaiDate($datetime)
{
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $thai_year = date('Y', $time) + 543;
    return date('d/m/', $time) . $thai_year . ' ' . date('H:i', $time) . ' น.';
}

// หากมีประวัติ ให้ดึงข้อมูลล่าสุด
$latest_activity = $history_list[0] ?? null; // กิจกรรมล่าสุด (รวม SPENT/NAF)
$latest_screening = $spent_list[0] ?? null; // SPENT ล่าสุด (สำหรับดูสถานะปัจจุบัน)
$target_ref_doc = ''; // ตัวแปรสำหรับเก็บเลขที่เอกสารที่จะส่งไปหน้า NAF

// ลองหาใบที่เสี่ยงล่าสุดก่อน
$latest_risky_screening = null;
if (!empty($spent_list)) {
    foreach ($spent_list as $scr) {
        $sc = ($scr['q1_weight_loss'] + $scr['q2_eat_less'] + $scr['q3_bmi_abnormal'] + $scr['q4_critical']);
        if ($sc >= 2) {
            $latest_risky_screening = $scr;
            break; // เจอใบเสี่ยงที่ใหม่ที่สุดแล้ว หยุดหา
        }
    }
}

// กำหนดเลขเอกสารที่จะส่งไป
if ($latest_risky_screening) {
    $target_ref_doc = $latest_risky_screening['doc_no'];
} elseif ($latest_screening) {
    $target_ref_doc = $latest_screening['doc_no'];
}

$link_start_spent = "nutrition_screening_form.php?hn=" . $patient['patients_hn'] . "&an=" . $patient['admissions_an'];

// กำหนดค่า Default
$latest_activity = $history_list[0] ?? null;
$cur_title = 'ยังไม่มีข้อมูลการคัดกรอง';
$cur_desc = 'ผู้ป่วยรายนี้ยังไม่เคยได้รับการคัดกรอง';
$cur_score = '-';
$cur_date = '-';
$cur_assessor = '-';
$cur_color_class = 'text-muted';
$status_label = 'ผลการคัดกรอง (SPENT)';

// สร้าง Link ไปหน้า NAF โดยตรง (กรณีไม่ทราบน้ำหนัก)
$link_direct_naf = "nutrition_alert_form.php?hn=" . $patient['patients_hn'] . "&an=" . $patient['admissions_an'];

// [แก้ไขใหม่] เพิ่มปุ่ม "กรณีไม่ทราบน้ำหนัก" ไว้คู่กัน
$next_action_html = '<div class="alert alert-secondary mb-0 p-3 text-center" style="background-color: #f8f9fa; border: 1px dashed #ced4da;">
    <h6 class="font-weight-bold mb-2 text-secondary"><i class="fa-solid fa-circle-info mr-2"></i>ยังไม่มีข้อมูล</h6>
    
    <div class="d-flex justify-content-center">
        <a href="' . $link_start_spent . '" class="btn btn-sm btn-primary px-3 shadow-sm mr-2">
            <i class="fa-solid fa-play mr-1"></i> เริ่มคัดกรอง SPENT
        </a>

        <a href="' . $link_direct_naf . '" class="btn btn-sm btn-danger px-3 shadow-sm" title="ไปประเมิน NAF ทันที">
            <i class="fa-solid fa-weight-scale mr-1"></i> ไม่ทราบน้ำหนักผู้ป่วย
        </a>
    </div>
    
    <div class="mt-2 text-muted" style="font-size: 0.75rem;">
        *หากไม่สามารถชั่งน้ำหนักได้ ให้เลือกปุ่มสีแดง
    </div>
</div>';

if ($latest_activity) {
    $cur_date = thaiDate($latest_activity['action_datetime']);
    $cur_assessor = $latest_activity['assessor_name'];

    // กรณีล่าสุดเป็น SPENT
    if ($latest_activity['form_type'] == 'SPENT') {

        $status_label = 'ผลการคัดกรอง (SPENT)';

        $cur_score_val = ($latest_activity['q1_weight_loss'] + $latest_activity['q2_eat_less'] + $latest_activity['q3_bmi_abnormal'] + $latest_activity['q4_critical']);
        $cur_score = $cur_score_val;
        $cur_status_db = $latest_activity['screening_status'] ?? '';

        if ($cur_score_val >= 2) {
            $cur_title = 'มีความเสี่ยง (At Risk)';
            $cur_desc = 'ผู้ป่วยมีคะแนน SPENT ≥ 2 ควรได้รับการประเมิน NAF';
            $cur_color_class = 'text-danger';

            if (strpos($cur_status_db, 'ประเมินต่อแล้ว') !== false || !empty($latest_activity['assessment_doc_no'])) {
                $next_action_html = '<div class="alert alert-info mb-0 p-3" style="border-left: 4px solid #17a2b8;"><h6 class="font-weight-bold mb-1 text-info"><i class="fa-solid fa-clipboard-check mr-2"></i>ประเมิน NAF แล้ว</h6><small class="text-muted">ติดตามผลการประเมินภาวะโภชนาการตามแผนการรักษา</small></div>';
            } else {
                $link_naf = "nutrition_alert_form.php?hn=" . $patient['patients_hn'] . "&an=" . $patient['admissions_an'] . "&ref_screening=" . $target_ref_doc;

                $next_action_html = '<div class="alert alert-warning mb-0 p-3 shadow-sm" style="border-left: 4px solid #ffc107; background-color: #fff3cd;">
                    <h6 class="font-weight-bold mb-1 text-danger"><i class="fa-solid fa-triangle-exclamation mr-2"></i>ต้องดำเนินการ</h6>
                    <p class="mb-2 small text-dark">ควรประเมินภาวะโภชนาการ (NAF) ต่อทันที</p>
                    <a href="' . $link_naf . '" class="btn btn-sm btn-danger px-3 shadow-sm">
                        <i class="fa-solid fa-arrow-right mr-1"></i> ไปที่แบบประเมิน NAF
                    </a>
                </div>';
            }
        } else {
            $cur_title = 'ภาวะโภชนาการปกติ (Normal)';
            $cur_desc = 'ไม่พบความเสี่ยงในขณะนี้';
            $cur_color_class = 'text-success';
            $next_rescreen_ts = strtotime($latest_activity['action_datetime']) + (7 * 24 * 60 * 60);
            $next_rescreen_date = date('d/m/', $next_rescreen_ts) . (date('Y', $next_rescreen_ts) + 543);
            $next_action_html = '<div class="alert alert-success mb-0 p-3" style="border-left: 4px solid #28a745; background-color: #f0fff4;"><h6 class="font-weight-bold mb-1 text-success"><i class="fa-regular fa-calendar-check mr-2"></i>ข้อแนะนำถัดไป</h6><small class="text-dark">ควรทำการคัดกรองซ้ำในอีก 7 วัน</small><br><strong class="text-success" style="font-size: 0.9rem;">(วันที่ ' . $next_rescreen_date . ')</strong></div>';
        }
    }
    // กรณีล่าสุดเป็น NAF
    elseif ($latest_activity['form_type'] == 'NAF') {

        $status_label = 'ผลการประเมิน (NAF)';
        $cur_score = $latest_activity['total_score'];
        $naf_level = $latest_activity['naf_level'];

        if ($naf_level == 'NAF C') {
            $cur_title = 'NAF C: Severe Malnutrition';
            $cur_desc = 'ภาวะทุพโภชนาการระดับรุนแรง (Severe Malnutrition)';
            $cur_color_class = 'text-danger';
            $next_action_html = '<div class="alert alert-danger mb-0 p-3" style="border-left: 4px solid #dc3545; background-color: #ffebee;"><h6 class="font-weight-bold mb-1 text-danger"><i class="fa-solid fa-user-doctor mr-2"></i>การจัดการเร่งด่วน</h6><small class="text-dark">แจ้งแพทย์/นักโภชนาการเพื่อดูแลภายใน 24 ชม.</small></div>';
        } elseif ($naf_level == 'NAF B') {
            $cur_title = 'NAF B: Moderate Malnutrition';
            $cur_desc = 'ภาวะทุพโภชนาการระดับปานกลาง (Moderate Malnutrition)';
            $cur_color_class = 'text-warning';
            $next_action_html = '<div class="alert alert-warning mb-0 p-3" style="border-left: 4px solid #ffc107; background-color: #fff3cd;"><h6 class="font-weight-bold mb-1 text-dark"><i class="fa-solid fa-user-nurse mr-2"></i>การจัดการ</h6><small class="text-dark">แจ้งแพทย์/นักโภชนาการเพื่อดูแลภายใน 3 วัน</small></div>';
        } else {
            $cur_title = 'NAF A: Normal-Mild Malnutrition';
            $cur_desc = 'ภาวะโภชนาการปกติ หรือเสี่ยงต่ำ (Normal/Mild)';
            $cur_color_class = 'text-success';
            $next_action_html = '<div class="alert alert-success mb-0 p-3" style="border-left: 4px solid #28a745; background-color: #f0fff4;"><h6 class="font-weight-bold mb-1 text-success"><i class="fa-regular fa-calendar-check mr-2"></i>ข้อแนะนำ</h6><small class="text-dark">ประเมินซ้ำตามระยะเวลาที่กำหนด (7 วัน)</small></div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลผู้ป่วย | โรงพยาบาลกำแพงเพชร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/patient_profile.css">
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

                            <div class="col-6 col-md-8 col-lg-2 mb-3"><small class="text-muted d-block">สิทธิการรักษา</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['health_insurance_name'] ?: '-' ?></span></div>
                            <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">แพทย์เจ้าของไข้</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['doctor_name'] ?: '-' ?></span></div>

                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">วันที่ Admit</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $admit_date ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เบอร์โทรศัพท์</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['patients_phone'] ?: '-' ?></span></div>
                            <div class="col-12 col-md-6 col-lg-3 mb-3"><small class="text-muted d-block">โรคประจำตัว</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $patient['patients_congenital_disease'] ?: '-' ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="index.php" class="btn btn-secondary btn-sm" style="border-radius: 4px;">
                <i class="fa-solid fa-chevron-left mr-1"></i> กลับหน้าหลัก
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-primary px-4 shadow-sm dropdown-toggle" data-toggle="dropdown"
                    style="background-color: #0d47a1; border-color: #0d47a1; border-radius: 4px; height: 45px; font-size: 1rem;">
                    <i class="fa-solid fa-folder-plus mr-2"></i> เพิ่มไฟล์เอกสารใหม่
                </button>
                <div class="dropdown-menu dropdown-menu-right border shadow-sm p-0 mt-1" style="min-width: 380px; border-radius: 4px;">
                    <div class="bg-light px-3 py-2 border-bottom">
                        <small class="text-uppercase text-muted font-weight-bold" style="font-size: 0.75rem;">เลือกประเภทเอกสาร</small>
                    </div>
                    <a href="nutrition_screening_form.php?hn=<?= $patient['patients_hn'] ?>&an=<?= $patient['admissions_an'] ?>" class="dropdown-item py-3 px-3 menu-action-link border-bottom">
                        <div class="d-flex">
                            <div class="mr-3 d-flex align-items-center justify-content-center icon-box" style="width: 45px; height: 45px; background-color: #f1f8ff; border: 1px solid #d0e2f5; border-radius: 4px; color: #0d47a1;"><i class="fa-solid fa-file-medical fa-lg"></i></div>
                            <div class="w-100">
                                <span class="font-weight-bold text-dark title-text" style="font-size: 1rem;">แบบคัดกรองภาวะโภชนาการ</span>
                                <small class="text-muted sub-text d-block mb-1">SPENT Nutrition Screening Tool</small>
                            </div>
                        </div>
                    </a>
                    <a href="nutrition_alert_form.php?hn=<?= $patient['patients_hn'] ?>&an=<?= $patient['admissions_an'] ?>&ref_screening=<?= $latest_screening['doc_no'] ?? '' ?>" class="dropdown-item py-3 px-3 menu-action-link border-bottom">
                        <div class="d-flex">
                            <div class="mr-3 d-flex align-items-center justify-content-center icon-box" style="width: 45px; height: 45px; background-color: #f1f8ff; border: 1px solid #d0e2f5; border-radius: 4px; color: #0d47a1;">
                                <i class="fa-solid fa-clipboard-user fa-lg"></i>
                            </div>
                            <div class="w-100">
                                <span class="font-weight-bold text-dark title-text" style="font-size: 1rem;">แบบประเมินภาวะโภชนาการ</span>
                                <small class="text-muted sub-text d-block mb-1">Nutrition Alert Form (NAF)</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div id="currentStatusCard" class="card border shadow-sm mb-4 overflow-hidden">
                    <div class="row no-gutters">
                        <div class="col-md-8 p-4 d-flex flex-column border-right-md">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <h6 class="font-weight-bold m-0 text-primary-custom"><i class="fa-solid fa-clipboard-check mr-2"></i>สถานะโภชนาการปัจจุบัน</h6>
                                <span class="badge badge-light border text-muted font-weight-normal px-2">ข้อมูลล่าสุด</span>
                            </div>
                            <div class="d-flex align-items-start mb-3">
                                <div class="flex-grow-1 pr-3">
                                    <small class="text-uppercase text-muted font-weight-bold" style="font-size: 0.75rem;">
                                        <?= $status_label ?>
                                    </small>
                                    <h4 class="mt-1 mb-2 <?= $cur_color_class ?>" style="font-size: 1.5rem; font-weight: 700;"><?= $cur_title ?></h4>
                                    <p class="text-muted" style="font-size: 0.95rem; line-height: 1.5;"><?= $cur_desc ?></p>
                                </div>
                                <div class="text-center pl-3 border-left">
                                    <div class="rounded-circle shadow-sm bg-white mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.6rem; border: 3px solid #f8f9fa;">
                                        <span class="<?= $cur_color_class ?> font-weight-bold"><?= $cur_score ?></span>
                                    </div>
                                    <small class="text-muted font-weight-bold" style="font-size: 0.7rem;">คะแนนรวม</small>
                                </div>
                            </div>
                            <div class="mt-auto">
                                <div class="d-flex align-items-center bg-light rounded p-2" style="font-size: 0.85rem;">
                                    <div class="mr-4 d-flex align-items-center"><i class="fa-regular fa-calendar text-muted mr-2"></i><span class="font-weight-medium text-dark"><?= $cur_date ?></span></div>
                                    <div class="d-flex align-items-center"><i class="fa-solid fa-user-nurse text-muted mr-2"></i><span class="font-weight-medium text-dark"><?= $cur_assessor ?></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 p-4 d-flex flex-column justify-content-center bg-white" style="background-color: #fafbfc;">
                            <div class="w-100">
                                <h6 class="text-muted font-weight-bold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">สิ่งดำเนินการถัดไป (Next Action)</h6>
                                <?= $next_action_html ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border shadow-sm mb-5">
                    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary-custom">
                            <i class="fa-solid fa-clock-rotate-left mr-2"></i> ประวัติการบันทึกข้อมูลทั้งหมด
                        </h6>
                        <div class="form-inline mt-2 mt-md-0">
                            <!-- <button type="button" class="btn btn-outline-danger btn-sm mr-3" onclick="alert('ฟังก์ชันนี้ยังไม่เปิดใช้งาน')"><i class="fa-solid fa-rotate-right mr-1"></i> เริ่มต้นใหม่ (Reset)</button> -->
                            <label class="small mr-2 text-muted">ตัวกรอง:</label>
                            <select class="custom-select custom-select-sm" id="typeFilter" onchange="filterHistory()">
                                <option value="all">ทั้งหมด (All)</option>
                                <option value="SPENT">แบบคัดกรองภาวะโภชนาการ (SPENT)</option>
                                <option value="NAF">แบบประเมินภาวะโภชนาการ (NAF)</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom mb-0">
                                <thead class="bg-light text-secondary">
                                    <tr>
                                        <th style="width: 250px;">รายการบันทึก</th>
                                        <th style="width: 80px;" class="text-center">ประเภท</th>
                                        <th style="width: 60px;" class="text-center">ครั้งที่</th>
                                        <th style="width: 60px;" class="text-center">คะแนน</th>
                                        <th style="width: 100px" class="text-center">ผลการคัดกรอง (SPENT)</th>
                                        <th style="width: 100px" class="text-center">ผลการประเมิน (NAF)</th>
                                        <th style="width: 100px">ผู้ประเมิน</th>
                                        <th style="width: 100px">วัน/เวลาที่บันทึก</th>
                                        <th style="width: 120px" class="text-center">สถานะเอกสาร</th>
                                        <th style="width: 90px;" class="text-center">ไฟล์ PDF</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <?php if (count($history_list) > 0): ?>
                                        <?php foreach ($history_list as $row): ?>

                                            <?php
                                            // --- กรณี SPENT (สีน้ำเงิน) ---
                                            if ($row['form_type'] == 'SPENT'):
                                                $score = ($row['q1_weight_loss'] + $row['q2_eat_less'] + $row['q3_bmi_abnormal'] + $row['q4_critical']);

                                                // สีผลลัพธ์
                                                $res_class = 'text-muted';
                                                if ($row['screening_result'] == 'มีความเสี่ยง') $res_class = 'text-danger font-weight-bold';
                                                elseif ($row['screening_result'] == 'ปกติ') $res_class = 'text-success font-weight-bold';

                                                // สถานะ Badge
                                                $status_badge = 'badge-secondary';
                                                if (strpos($row['screening_status'] ?? '', 'ปกติ') !== false) $status_badge = 'badge-success';
                                                elseif (strpos($row['screening_status'] ?? '', 'เสี่ยง') !== false) $status_badge = 'badge-danger';
                                                elseif (strpos($row['screening_status'] ?? '', 'ประเมินต่อ') !== false) $status_badge = 'badge-info';
                                            ?>
                                                <tr data-type="SPENT">
                                                    <td>
                                                        <a href="nutrition_screening_view.php?doc_no=<?= $row['doc_no'] ?>" class="doc-link text-decoration-none">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fa-solid fa-file-medical fa-lg mr-2 icon-spent"></i>
                                                                <div>
                                                                    <span class="font-weight-bold text-dark" style="font-size: 0.95rem;">แบบคัดกรองภาวะโภชนาการ (SPENT)</span>
                                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">เลขที่เอกสาร: <?= $row['doc_no'] ?></small>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-pill badge-spent px-3 py-1">SPENT</span>
                                                    </td>
                                                    <td class="text-center align-middle text-muted"><?= $row['screening_seq'] ?></td>
                                                    <td class="text-center align-middle font-weight-bold"><?= $score ?></td>
                                                    <td class="text-center align-middle <?= $res_class ?>"><?= $row['screening_result'] ?></td>
                                                    <td class="text-center align-middle text-muted">-</td>
                                                    <td class="align-middle"><small><?= $row['assessor_name'] ?></small></td>
                                                    <td class="align-middle"><small><?= thaiDate($row['action_datetime']) ?></small></td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge <?= $status_badge ?> font-weight-normal px-2"><?= $row['screening_status'] ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <a href="nutrition_screening_report.php?doc_no=<?= htmlspecialchars($row['doc_no']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary border-0 text-danger">
                                                            <i class="fa-solid fa-file-pdf fa-lg"></i>
                                                        </a>
                                                    </td>
                                                </tr>

                                            <?php
                                            // --- กรณี NAF (สีเขียว) ---
                                            elseif ($row['form_type'] == 'NAF'):
                                                $score = $row['total_score'];
                                                $naf_level = $row['naf_level']; // e.g., "NAF A", "NAF B"

                                                // สีผลลัพธ์ NAF (Severity Colors)
                                                $naf_res_html = '<span class="text-muted">-</span>';
                                                if ($naf_level == 'NAF A') {
                                                    $naf_res_html = '<span class="text-success font-weight-bold"><i class="fa-solid fa-circle-check mr-1"></i>NAF A</span>';
                                                } elseif ($naf_level == 'NAF B') {
                                                    $naf_res_html = '<span class="text-warning font-weight-bold" style="color: #f57f17 !important;"><i class="fa-solid fa-triangle-exclamation mr-1"></i>NAF B</span>';
                                                } elseif ($naf_level == 'NAF C') {
                                                    $naf_res_html = '<span class="text-danger font-weight-bold"><i class="fa-solid fa-circle-exclamation mr-1"></i>NAF C</span>';
                                                }
                                            ?>
                                                <tr data-type="NAF" style="background-color: #f9fff9;">
                                                    <td>
                                                        <a href="nutrition_alert_form_view.php?doc_no=<?= $row['doc_no'] ?>" class="doc-link text-decoration-none">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fa-solid fa-clipboard-user fa-lg mr-2 icon-naf"></i>
                                                                <div>
                                                                    <span class="font-weight-bold text-dark" style="font-size: 0.95rem;">แบบประเมินภาวะโภชนาการ (NAF)</span>
                                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">เลขที่เอกสาร: <?= $row['doc_no'] ?></small>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-pill badge-naf px-3 py-1">NAF</span>
                                                    </td>
                                                    <td class="text-center align-middle text-muted"><?= $row['naf_seq'] ?></td>
                                                    <td class="text-center align-middle font-weight-bold"><?= $score ?></td>
                                                    <td class="text-center align-middle text-muted"><small>-</small></td>
                                                    <td class="text-center align-middle"><?= $naf_res_html ?></td>
                                                    <td class="align-middle"><small><?= $row['assessor_name'] ?></small></td>
                                                    <td class="align-middle"><small><?= thaiDate($row['action_datetime']) ?></small></td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-success font-weight-normal px-2">ประเมินเสร็จสิ้น</span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <a href="nutrition_alert_form_report.php?doc_no=<?= htmlspecialchars($row['doc_no']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary border-0 text-danger">
                                                            <i class="fa-solid fa-file-pdf fa-lg"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>

                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                <i class="fa-solid fa-folder-open mb-2" style="font-size: 2rem; opacity: 0.5;"></i><br>
                                                ยังไม่มีประวัติการบันทึกข้อมูล
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterHistory() {
            const filter = document.getElementById('typeFilter').value;
            const rows = document.getElementById('historyTableBody').getElementsByTagName('tr');
            for (let i = 0; i < rows.length; i++) {
                const type = rows[i].getAttribute('data-type');
                if (!type) continue;
                if (filter === 'all' || type === filter) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
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