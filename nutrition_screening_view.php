<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$doc_no = $_GET['doc_no'] ?? '';

if (empty($doc_no)) {
    die("<div class='alert alert-danger text-center mt-5'>Error: ไม่พบเลขที่เอกสาร</div>");
}

try {
    // แก้ไข SQL: ไม่ใช้ตัวย่อ (Alias) ใช้ชื่อตารางเต็ม
    $sql = "
        SELECT 
            nutrition_screening.*, 
            patients.patients_firstname, 
            patients.patients_lastname, 
            patients.patients_hn, 
            patients.patients_dob,
            patients.patients_phone, 
            patients.patients_congenital_disease,
            admissions.bed_number, 
            admissions.admit_datetime,
            wards.ward_name, 
            doctor.doctor_name, 
            health_insurance.health_insurance_name
        FROM nutrition_screening
        JOIN patients ON nutrition_screening.patients_hn = patients.patients_hn
        JOIN admissions ON nutrition_screening.admissions_an = admissions.admissions_an
        LEFT JOIN wards ON admissions.ward_id = wards.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        LEFT JOIN health_insurance ON admissions.health_insurance_id = health_insurance.health_insurance_id
        WHERE nutrition_screening.doc_no = :doc_no
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_no' => $doc_no]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("<div class='alert alert-danger text-center mt-5'>ไม่พบข้อมูลเอกสาร</div>");

    // คำนวณอายุ
    $age = '-';
    if (!empty($data['patients_dob'])) {
        $dob = new DateTime($data['patients_dob']);
        $now = new DateTime();
        $diff = $now->diff($dob);
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน ' . $diff->d . ' วัน';
    }

    // ฟังก์ชันแปลงวันที่ไทย
    $admit_date = '-';
    if (!empty($data['admit_datetime'])) {
        $dt = new DateTime($data['admit_datetime']);
        $thai_year = $dt->format('Y') + 543;
        // แสดงผล: 12/04/2567 10:30 น.
        $admit_date = $dt->format('d/m/') . $thai_year . ' ' . $dt->format('H:i') . ' น.';
    }

    // คำนวณคะแนนรวม
    $score = intval($data['q1_weight_loss'] ?? 0) + intval($data['q2_eat_less'] ?? 0) + intval($data['q3_bmi_abnormal'] ?? 0) + intval($data['q4_critical'] ?? 0);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการคัดกรองภาวะโภชนาการ | โรงพยาบาลกำแพงเพชร</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

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
                            <a class="dropdown-item py-2 rounded mb-1" href="#">
                                <span><i class="fa-solid fa-clock-rotate-left mr-2 text-primary" style="width:20px;"></i>
                                    ประวัติการประเมินของฉัน</span>
                            </a>

                            <a class="dropdown-item py-2 rounded" href="#">
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
                            <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">HN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['patients_hn'] ?></span></div>
                            <div class="col-6 col-md-3 col-lg-2 mb-3"><small class="text-muted d-block">AN</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['admissions_an'] ?></span></div>
                            <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">ชื่อ - นามสกุล</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['patients_firstname'] . ' ' . $data['patients_lastname'] ?></span></div>
                            <div class="col-6 col-md-4 col-lg-2 mb-3"><small class="text-muted d-block" style="font-size: 0.95rem;">อายุ</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $age ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">หอผู้ป่วย</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['ward_name'] ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เตียง</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['bed_number'] ?></span></div>

                            <div class="col-12 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">แพทย์เจ้าของไข้</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['doctor_name'] ?: '-' ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">วันที่ Admit</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $admit_date ?></span></div>
                            <div class="col-6 col-md-6 col-lg-2 mb-3"><small class="text-muted d-block">เบอร์โทรศัพท์</small><span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['patients_phone'] ?: '-' ?></span></div>

                            <div class="col-12 col-md-6 col-lg-2 mb-3">
                                <small class="text-muted d-block">โรคประจำตัว</small>
                                <span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['patients_congenital_disease'] ?: '-' ?></span>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <small class="text-muted d-block">สิทธิการรักษา</small>
                                <span class="font-weight-bold" style="font-size: 0.95rem;"><?= $data['health_insurance_name'] ?: '-' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 no-print">

            <div>
                <button type="button" class="btn btn-secondary btn-sm shadow-sm" style="border-radius: 4px;" onclick="window.location.href='patient_profile.php?hn=<?= htmlspecialchars($data['patients_hn'] ?? '') ?>&an=<?= htmlspecialchars($data['admissions_an'] ?? '') ?>';">
                    <i class="fa-solid fa-chevron-left mr-1"></i> ย้อนกลับ
                </button>
            </div>

            <div>
                <div class="alert alert-warning py-1 px-3 d-inline-block mb-0 mr-2 shadow-sm" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-eye mr-1"></i> โหมดดูประวัติ (Read Only)
                </div>

                <a href="nutrition_screening_report.php?doc_no=<?= htmlspecialchars($data['doc_no'] ?? '') ?>"
                    target="_blank"
                    class="btn btn-info btn-sm shadow-sm px-3">
                    <i class="fas fa-file-pdf mr-1"></i> ดาวน์โหลด PDF
                </a>
            </div>

        </div>

        <div class="card mb-5 border-0 shadow-sm" style="border-top: 5px solid #0d47a1 !important;">

            <div class="card-header bg-white p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="mb-1 font-weight-bold text-dark">แบบคัดกรองภาวะโภชนาการ (SPENT)</h4>
                        <small class="text-muted">Nutrition Screening Tool for Hospitalized Patients</small>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-info p-2" style="font-size: 0.9rem;">No.: <?= htmlspecialchars($data['doc_no'] ?? '-') ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-2 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ครั้งที่</span></div>
                            <input type="text" class="form-control text-center font-weight-bold text-primary" value="<?= htmlspecialchars($data['screening_seq'] ?? '-') ?>" disabled>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">วันที่</span></div>
                            <input type="text" class="form-control text-center"
                                value="<?= isset($data['screening_datetime']) ? date('d/m/', strtotime($data['screening_datetime'])) . (date('Y', strtotime($data['screening_datetime'])) + 543) : '-' ?>"
                                disabled>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">เวลา</span></div>
                            <input type="text" class="form-control text-center" value="<?= isset($data['screening_datetime']) ? date('H:i', strtotime($data['screening_datetime'])) : '-' ?>" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text bg-white text-muted">ผู้คัดกรอง</span></div>
                            <input type="text" class="form-control text-center text-primary" value="<?= htmlspecialchars($data['assessor_name'] ?? '-') ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form>
                    <div class="form-group mb-4">
                        <label class="section-label">1. การวินิจฉัยโรค (Diagnosis)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['initial_diagnosis'] ?? '') ?>" disabled>
                    </div>

                    <hr class="my-4" style="border-top: 1px dashed #dee2e6;">

                    <div class="mb-4">
                        <label class="section-label">2. ข้อมูลสัดส่วนร่างกาย (Anthropometry)</label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">น้ำหนักปัจจุบัน</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['present_weight'] ?? '') ?>" disabled>
                                        <div class="input-group-append"><span class="input-group-text bg-light text-muted">กก.</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">ส่วนสูง</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['height'] ?? '') ?>" disabled>
                                        <div class="input-group-append"><span class="input-group-text bg-light text-muted">ซม.</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">น้ำหนักปกติ (ถ้าทราบ)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['normal_weight'] ?? '') ?>" disabled>
                                        <div class="input-group-append"><span class="input-group-text bg-light text-muted">กก.</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="text-muted small mb-1">ดัชนีมวลกาย (BMI)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['bmi'] ?? '') ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="d-flex align-items-center bg-light p-2 rounded border">
                                    <span class="mr-3 font-weight-bold text-secondary small">ที่มาของน้ำหนัก:</span>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" <?= (($data['weight_method'] ?? '') == 'ชั่งจริง') ? 'checked' : '' ?> disabled>
                                        <label class="custom-control-label">ชั่งจริง</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" <?= (($data['weight_method'] ?? '') == 'ซักถาม') ? 'checked' : '' ?> disabled>
                                        <label class="custom-control-label">ซักถาม</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" <?= (($data['weight_method'] ?? '') == 'กะประมาณ') ? 'checked' : '' ?> disabled>
                                        <label class="custom-control-label">กะประมาณ</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4" style="border-top: 1px dashed #dee2e6;">

                    <div class="mb-4">
                        <label class="section-label">3. แบบคัดกรอง (Screening Questions)</label>
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 70%; border-bottom: 2px solid #dee2e6;">ประเด็นคำถาม</th>
                                    <th class="text-center text-success" style="width: 15%; border-bottom: 2px solid #dee2e6;">ใช่ (1)</th>
                                    <th class="text-center text-muted" style="width: 15%; border-bottom: 2px solid #dee2e6;">ไม่ใช่ (0)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="align-middle">1. ผู้ป่วยน้ำหนักตัวลดลง โดยไม่ได้ตั้งใจ (ในช่วง 6 เดือนที่ผ่านมา)</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q1_weight_loss'] ?? -1) == 1) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q1_weight_loss'] ?? -1) == 0) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle">2. ผู้ป่วยได้รับอาหารน้อยกว่าที่เคยได้ (> 7 วัน)</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q2_eat_less'] ?? -1) == 1) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q2_eat_less'] ?? -1) == 0) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle">3. BMI < 18.5 หรือ ≥ 25.0 กก./ม.² หรือไม่</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q3_bmi_abnormal'] ?? -1) == 1) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q3_bmi_abnormal'] ?? -1) == 0) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle">4. ผู้ป่วยมีภาวะโรควิกฤต หรือกึ่งวิกฤต</td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q4_critical'] ?? -1) == 1) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" <?= (($data['q4_critical'] ?? -1) == 0) ? 'checked' : '' ?> disabled><label class="custom-control-label"></label></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label class="section-label">4. หมายเหตุ / ข้อสังเกตเพิ่มเติม (Optional)</label>
                        <textarea class="form-control" rows="3" disabled><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
                    </div>

                    <?php
                    $isRisk = ($score >= 2);
                    $boxClass = $isRisk ? 'alert-danger' : 'alert-success';
                    $borderStyle = $isRisk ? '2px dashed #dc3545' : '2px dashed #28a745';
                    $bgColor = $isRisk ? '#fff5f5' : '#f0fff4';
                    $iconClass = $isRisk ? 'fa-triangle-exclamation text-danger' : 'fa-circle-check text-success';
                    $titleText = $isRisk ? 'มีความเสี่ยง (At Risk)' : 'ไม่พบความเสี่ยง (Normal)';
                    $titleColor = $isRisk ? 'text-danger' : 'text-success';
                    $descText = $isRisk
                        ? "คะแนนรวม: {$score} คะแนน - ผู้ป่วยมีความเสี่ยงต่อภาวะขาดสารอาหาร"
                        : "คะแนนรวม: {$score} คะแนน - ควรคัดกรองซ้ำใน 7 วัน";

                    $recommendIcon = $isRisk ? 'fa-bell' : 'fa-calendar-check';
                    $recommendText = $isRisk
                        ? 'ทำการประเมินภาวะโภชนาการต่อ หรือปรึกษานักกำหนดอาหาร'
                        : 'คัดกรองซ้ำในอีก 7 วัน';
                    ?>

                    <div class="mt-4 p-4 rounded text-center" style="border: <?= $borderStyle ?>; background-color: <?= $bgColor ?>;">
                        <div class="mb-3">
                            <i class="fa-solid <?= $iconClass ?> fa-4x"></i>
                        </div>

                        <h3 class="font-weight-bold mb-2 <?= $titleColor ?>"><?= $titleText ?></h3>
                        <p class="mb-2" style="font-size: 0.95rem;"><?= $descText ?></p>

                        <div class="mt-3 p-2 rounded d-inline-block" style="background-color: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.1);">
                            <i class="far <?= $recommendIcon ?> mr-2"></i>
                            <strong>ข้อแนะนำ:</strong> <?= $recommendText ?>
                        </div>

                        <?php if ($isRisk): ?>
                            <div class="mt-3">
                                <span class="badge badge-warning p-2">สถานะ: <?= htmlspecialchars($data['screening_status'] ?? '-') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) {
                window.location.href = 'index.php';
            }
        }
    </script>
</body>

</html>