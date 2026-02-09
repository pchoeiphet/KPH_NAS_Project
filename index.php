<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();

$currentPage = basename($_SERVER['PHP_SELF']); // ดึงชื่อไฟล์ปัจจุบัน

// ตรวจสอบว่ามี Session user_id หรือไม่ (ถ้าไม่มีให้ดีดกลับไปหน้า login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ฟังก์ชันแปลงวันที่เป็นภาษาไทย
function thaiDate($datetime)
{
    if (!$datetime) return '-';
    $time = strtotime($datetime);

    $day = date('d', $time);
    $month = date('m', $time);
    $year = date('Y', $time) + 543;
    $time = date('H:i', $time);

    return "$day/$month/$year $time";
}

// ฟังก์ชันคำนวณอายุ
function calculateAge($dob)
{
    if (!$dob) return 0;
    try {
        $date = new DateTime($dob);
        $now = new DateTime();
        $interval = $now->diff($date);
        return $interval->y;
    } catch (Exception $e) {
        return 0;
    }
}

$ward_options = [];
$doctor_options = [];

try {
    // ดึงรายชื่อหอผู้ป่วย (เฉพาะที่มีใน Admissions และยังไม่จำหน่าย)
    $sql_ward_options = "
        SELECT DISTINCT wards.ward_name 
        FROM admissions 
        JOIN wards ON admissions.ward_id = wards.ward_id 
        WHERE admissions.discharge_datetime IS NULL 
        ORDER BY wards.ward_name ASC
    ";
    $stmt_w = $conn->prepare($sql_ward_options);
    $stmt_w->execute();
    $ward_options = $stmt_w->fetchAll(PDO::FETCH_ASSOC);

    // ดึงรายชื่อแพทย์ (เฉพาะที่มีใน Admissions และยังไม่จำหน่าย)
    $sql_doctor_options = "
        SELECT DISTINCT doctor.doctor_name 
        FROM admissions 
        JOIN doctor ON admissions.doctor_id = doctor.doctor_id 
        WHERE admissions.discharge_datetime IS NULL 
        ORDER BY doctor.doctor_name ASC
    ";
    $stmt_d = $conn->prepare($sql_doctor_options);
    $stmt_d->execute();
    $doctor_options = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

    // ตัวแปรเก็บข้อมูลผู้ป่วยเพื่อส่งให้ JS
    $patients_data = [];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

try {
    // 1. ดึงข้อมูลผู้ป่วยที่กำลัง Admit อยู่ (แก้ไขบรรทัดนี้แล้ว)
    $sql = "
        SELECT 
            patients.patients_id, patients.patients_hn, patients.patients_firstname, patients.patients_lastname, 
            patients.patients_dob, patients.patients_congenital_disease,
            admissions.admissions_an, admissions.admit_datetime, admissions.bed_number,
            wards.ward_name,
            doctor.doctor_name
        FROM admissions
        JOIN patients ON admissions.patients_id = patients.patients_id
        LEFT JOIN wards ON admissions.ward_id = wards.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        WHERE admissions.discharge_datetime IS NULL
        ORDER BY admissions.ward_id, admissions.bed_number ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($patients as $row) {
        $hn = $row['patients_hn'];
        $an = $row['admissions_an'];

        // หาข้อมูลการคัดกรอง (SPENT) ล่าสุด
        $stmt_spent = $conn->prepare("SELECT * FROM nutrition_screening WHERE patients_hn = :hn ORDER BY screening_datetime DESC LIMIT 1");
        $stmt_spent->execute([':hn' => $hn]);
        $spent = $stmt_spent->fetch(PDO::FETCH_ASSOC);

        // หาข้อมูลการประเมิน (NAF) ล่าสุด
        $stmt_naf = $conn->prepare("SELECT * FROM nutrition_assessment WHERE patients_hn = :hn ORDER BY assessment_datetime DESC LIMIT 1");
        $stmt_naf->execute([':hn' => $hn]);
        $naf = $stmt_naf->fetch(PDO::FETCH_ASSOC);

        // คำนวณสถานะ
        $status = 'wait_screen';
        $screenDate = '-';
        $assessDate = '-';
        $scoreVal = null;
        $nafScore = null;

        // ตัวแปรสำหรับเก็บเลขที่เอกสารที่จะส่งไป
        $target_ref_doc = '';

        // คำนวณคะแนน SPENT
        $spentScore = 0;
        if ($spent) {
            $spentScore = (intval($spent['q1_weight_loss']) + intval($spent['q2_eat_less']) + intval($spent['q3_bmi_abnormal']) + intval($spent['q4_critical']));
            $screenDate = thaiDate($spent['screening_datetime']);

            // เก็บเลขที่เอกสาร (doc_no) จากใบ SPENT ล่าสุด
            $target_ref_doc = $spent['doc_no'];
        }

        // Determine Status logic
        if ($naf) {
            $status = 'assessed';
            $assessDate = thaiDate($naf['assessment_datetime']);
            $scoreVal = $naf['total_score'];
            $nafScore = $naf['total_score'];
            if ($spent) $screenDate = thaiDate($spent['screening_datetime']);
        } elseif ($spent) {
            $scoreVal = $spentScore;
            if ($spentScore >= 2) {
                $status = 'wait_assess';
            } else {
                $status = 'normal';
            }
        }

        $patients_data[] = [
            'id' => $row['patients_id'],
            'hn' => $row['patients_hn'],
            'an' => $row['admissions_an'],
            'name' => $row['patients_firstname'] . ' ' . $row['patients_lastname'],
            'age' => calculateAge($row['patients_dob']),
            'bed' => $row['bed_number'],
            'ward' => $row['ward_name'],
            'departmentText' => $row['ward_name'],
            'underlying' => !empty($row['patients_congenital_disease']) ? $row['patients_congenital_disease'] : 'ปฏิเสธโรคประจำตัว',
            'doctor' => $row['doctor_name'],
            'admitDate' => thaiDate($row['admit_datetime']),
            'screenDate' => $screenDate,
            'screenCount' => 0,
            'assessDate' => $assessDate,
            'status' => $status,
            'scoreVal' => $scoreVal,
            'nafScore' => $nafScore,
            // ส่งเลขที่เอกสารไปด้วย
            'target_doc_no' => $target_ref_doc
        ];
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>รายชื่อผู้ป่วยใน | โรงพยาบาลกำแพงเพชร</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <link rel="stylesheet" href="css/index.css">

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

    <div class="container-fluid px-lg-5 mt-4 mb-5">

        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4>
                        สวัสดี, คุณ <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋
                    </h4>
                    <p>
                        <i class="fa-regular fa-calendar px-1"></i>
                        วันนี้ <?php echo thaiDate(date('Y-m-d H:i:s')); ?>
                        | <span class="text-info"><?php echo htmlspecialchars($_SESSION['user_position']); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-md-right d-none d-md-block">
                    <i class="fa-solid fa-user-doctor bg-icon-deco"></i>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="form-row align-items-end">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <label class="font-weight-bold small text-muted">ค้นหาผู้ป่วย</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control border-left-0" id="searchInput"
                                placeholder="ชื่อ-สกุล, HN, AN...">
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <label class="font-weight-bold small text-muted">
                            <i class="fa-regular fa-building"></i> แผนก/หอผู้ป่วย
                        </label>
                        <select class="custom-select" id="wardFilter">
                            <option value="all" selected>ทั้งหมด</option>

                            <?php foreach ($ward_options as $w): ?>
                                <option value="<?= htmlspecialchars($w['ward_name']) ?>">
                                    <?= htmlspecialchars($w['ward_name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="font-weight-bold small text-muted">
                            <i class="fas fa-user-md"></i> แพทย์เจ้าของไข้
                        </label>
                        <select class="custom-select" id="doctorFilter">
                            <option value="all" selected>ทั้งหมด</option>

                            <?php foreach ($doctor_options as $d): ?>
                                <option value="<?= htmlspecialchars($d['doctor_name']) ?>">
                                    <?= htmlspecialchars($d['doctor_name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="card border shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;" onclick="handleSort('id')">
                                    # <i class="fas fa-sort sort-icon" id="sort-id"></i>
                                </th>
                                <th class="text-center" style="width: 80px;" onclick="handleSort('bed')">
                                    เตียง <i class="fas fa-sort sort-icon" id="sort-bed"></i>
                                </th>
                                <th class="text-center" onclick="handleSort('an')">AN <i class="fas fa-sort sort-icon" id="sort-an"></i>
                                </th>
                                <th class="text-center" onclick="handleSort('hn')">HN <i class="fas fa-sort sort-icon" id="sort-hn"></i>
                                </th>
                                <th class="text-center" style="width: 12%;" onclick="handleSort('name')">
                                    ชื่อ-นามสกุล <i class="fas fa-sort sort-icon" id="sort-name"></i>
                                </th>
                                <th class="text-center" onclick="handleSort('age')">อายุ <i
                                        class="fas fa-sort sort-icon" id="sort-age"></i></th>

                                <th style="width: 12%;" onclick="handleSort('doctor')">แพทย์เจ้าของไข้ <i
                                        class="fas fa-sort sort-icon" id="sort-doctor"></i></th>
                                <th style="width: 12%;" onclick="handleSort('admitDate')">วัน/เวลาที่ Admit <i class="fas fa-sort sort-icon"
                                        id="sort-admitDate"></i></th>

                                <th class="text-center" style="width: 13%;">
                                    สถานะทางโภชนาการ
                                </th>
                                <th class="text-center" style="width: 12%;">
                                    การดำเนินการ
                                </th>

                                <th class="text-center no-sort" style="width: 130px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="patientTableBody">
                        </tbody>
                    </table>
                </div>

                <div id="noResults" class="text-center py-5 d-none">
                    <i class="fas fa-search fa-3x text-light mb-3" style="color: #dee2e6 !important;"></i>
                    <p class="text-muted">ไม่พบข้อมูลผู้ป่วยที่ค้นหา</p>
                </div>
            </div>
        </div>

    </div>

    <div aria-live="polite" aria-atomic="true" style="position: relative; z-index: 10000;">
        <div id="toast-container" style="position: fixed; bottom: 20px; right: 20px;"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <script>
        const TODAY = new Date();

        let patients = <?php echo json_encode($patients_data); ?>;

        // ป้องกันกรณีไม่มีข้อมูล (เป็น null) ให้กำหนดเป็น array ว่าง
        if (!patients) {
            patients = [];
        }

        let currentSort = {
            column: null,
            direction: 'asc'
        };

        document.addEventListener('DOMContentLoaded', function() {
            renderTable(patients);

            const filterIds = ['searchInput', 'wardFilter', 'doctorFilter'];
            filterIds.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener(id === 'searchInput' ? 'input' : 'change', filterPatients);
                }
            });
        });

        // --- Helper Functions ---
        function handleSort(column) {
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }
            updateSortIcons(column, currentSort.direction);
            filterPatients();
        }

        function updateSortIcons(activeColumn, direction) {
            document.querySelectorAll('.sort-icon').forEach(icon => {
                icon.className = 'fas fa-sort sort-icon';
                icon.parentElement.classList.remove('active-sort');
            });
            const targetIcon = document.getElementById(`sort-${activeColumn}`);
            if (targetIcon) {
                targetIcon.className = direction === 'asc' ? 'fas fa-sort-up sort-icon' : 'fas fa-sort-down sort-icon';
                targetIcon.parentElement.classList.add('active-sort');
            }
        }

        function parseThaiDateForSort(dateStr) {
            if (!dateStr || dateStr === '-' || dateStr === '') return 0;

            const parts = dateStr.split(' '); 
            if (parts.length < 2) return 0;

            const dateParts = parts[0].split('/'); 
            if (dateParts.length < 3) return 0;

            const d = parseInt(dateParts[0], 10);
            const m = parseInt(dateParts[1], 10) - 1; 
            const y = parseInt(dateParts[2], 10) - 543;

            const timeParts = parts[1].split(':');
            const h = parseInt(timeParts[0], 10) || 0;
            const min = parseInt(timeParts[1], 10) || 0;

            return new Date(y, m, d, h, min).getTime();
        }

        function getDaysDiff(dateStr) {
            if (!dateStr || dateStr === '-') return 0;

            const parts = dateStr.split(' ');
            const dateParts = parts[0].split('/');

            if (dateParts.length < 3) return 0;

            const d = parseInt(dateParts[0], 10);
            const m = parseInt(dateParts[1], 10) - 1;
            const y = parseInt(dateParts[2], 10) - 543;

            const screenDate = new Date(y, m, d);
            const today = new Date();

            screenDate.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);

            const diffTime = Math.abs(today - screenDate);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }

        function renderTable(data) {
            const tbody = document.getElementById('patientTableBody');
            const noResults = document.getElementById('noResults');
            if (!tbody) return;

            tbody.innerHTML = '';

            if (!data || data.length === 0) {
                if (noResults) noResults.classList.remove('d-none');
                return;
            } else {
                if (noResults) noResults.classList.add('d-none');
            }

            data.forEach((p, index) => {
                let actionBtn = '';

                // จัดการวันเวลา Admit
                let admitDateShow = p.admitDate;
                let admitTimeShow = '';
                if (p.admitDate && p.admitDate !== '-') {
                    const splitAdmit = p.admitDate.split(' ');
                    if (splitAdmit.length >= 2) {
                        admitDateShow = splitAdmit[0];
                        admitTimeShow = splitAdmit[1];
                    }
                }

                const daysSinceScreen = getDaysDiff(p.screenDate);
                const daysRemaining = 7 - daysSinceScreen;

                // Logic Status
                let statusDisplay = '';
                let dateDisplay = '';

                const dateStyle = 'class="text-muted mt-1" style="font-size: 0.8rem;"';

                if (p.status === 'wait_screen') {
                    statusDisplay = `<span class="badge badge-formal badge-formal-wait">รอคัดกรอง</span>`;
                } else if (p.status === 'wait_assess') {
                    statusDisplay = `<span class="badge badge-formal badge-formal-risk">มีความเสี่ยง (SPENT: ${p.scoreVal})</span>`;
                    dateDisplay = `<div ${dateStyle}>คัดกรอง: ${p.screenDate}</div>`;
                } else if (p.status === 'normal') {
                    statusDisplay = `<span class="badge badge-formal badge-formal-normal">ปกติ (SPENT: ${p.scoreVal})</span>`;
                    dateDisplay = `<div ${dateStyle}>คัดกรอง: ${p.screenDate}</div>`;
                } else if (p.status === 'assessed') {
                    let badgeClass = 'badge-formal-assessed';
                    let statusText = `ประเมินแล้ว`;
                    const nafScore = parseInt(p.scoreVal) || 0;

                    if (nafScore >= 11) {
                        badgeClass = 'badge-formal-severe';
                        statusText = `NAF C - Severe (${nafScore})`;
                    } else if (nafScore >= 6) {
                        badgeClass = 'badge-formal-risk';
                        statusText = `NAF B - Moderate (${nafScore})`;
                    } else {
                        badgeClass = 'badge-formal-normal';
                        statusText = `NAF A - Normal (${nafScore})`;
                    }
                    statusDisplay = `<span class="badge badge-formal ${badgeClass}">${statusText}</span>`;
                    dateDisplay = `<div ${dateStyle}>ประเมิน: ${p.assessDate}</div>`;
                }

                // Logic Action
                let nextActionDisplay = '';
                let nextActionClass = 'text-action-normal text-center';
                let countdownDisplay = '';

                if (p.status === 'wait_screen') {
                    nextActionDisplay = 'คัดกรองเบื้องต้น';
                    actionBtn = `<button class="btn btn-sm btn-outline-primary" style="min-width: 100px;" onclick="window.location.href='nutrition_screening_form.php?hn=${p.hn}&an=${p.an}'"><i class="fas fa-clipboard-check"></i> คัดกรอง</button>`;
                } else if (p.status === 'wait_assess') {
                    nextActionDisplay = 'ต้องประเมิน NAF';
                    nextActionClass = 'text-action-urgent text-center';
                    actionBtn = `<button class="btn btn-sm btn-warning" style="min-width: 100px;" onclick="window.location.href='nutrition_alert_form.php?hn=${p.hn}&an=${p.an}&ref_screening=${p.target_doc_no}'"><i class="fas fa-user-md"></i> ประเมิน</button>`;
                } else if (p.status === 'normal') {
                    if (daysRemaining < 0) {
                        nextActionDisplay = 'คัดกรองซ้ำ (เกินกำหนด)';
                        nextActionClass = 'text-action-urgentม text-center';
                        countdownDisplay = `<div class="text-danger font-weight-bold">เกินกำหนด ${Math.abs(daysRemaining)} วัน</div>`;
                        actionBtn = `<button class="btn btn-sm btn-outline-danger" style="min-width: 100px;" onclick="window.location.href='nutrition_screening_form.php?hn=${p.hn}&an=${p.an}'"><i class="fas fa-redo"></i> กรองซ้ำ</button>`;
                    } else if (daysRemaining === 0) {
                        nextActionDisplay = 'คัดกรองซ้ำ (วันนี้)';
                        nextActionClass = 'text-action-warning text-center';
                        countdownDisplay = `<div class="text-danger font-weight-bold">ครบกำหนดวันนี้</div>`;
                        actionBtn = `<button class="btn btn-sm btn-outline-danger" style="min-width: 100px;" onclick="window.location.href='nutrition_screening_form.php?hn=${p.hn}&an=${p.an}'"><i class="fas fa-redo"></i> กรองซ้ำ</button>`;
                    } else {
                        nextActionDisplay = 'ติดตามผล';
                        nextActionClass = 'text-action-muted text-center';
                        countdownDisplay = `<div class="text-muted">คัดกรองซ้ำ ใน ${daysRemaining} วัน</div>`;
                        actionBtn = `<button class="btn btn-sm btn-light border" style="min-width: 100px;" onclick="window.location.href='patient_profile.php?hn=${p.hn}&an=${p.an}'"><i class="fas fa-search"></i> ดูข้อมูล</button>`;
                    }
                } else if (p.status === 'assessed') {
                    nextActionDisplay = 'ติดตามผล';
                    nextActionClass = 'text-action-muted text-center';
                    actionBtn = `<button class="btn btn-sm btn-light border" style="min-width: 100px;" onclick="window.location.href='patient_profile.php?hn=${p.hn}&an=${p.an}'"><i class="fas fa-search"></i> ดูข้อมูล</button>`;
                }

                // สร้าง HTML แถว
                const row = `
                        <tr>
                            <td class="text-center text-muted">${index + 1}</td>
        
                            <td class="text-center font-weight-bold text-dark">${p.bed}</td>
        
                            <td><span class="text-muted">${p.an}</span></td>
        
                            <td class="font-weight-bold text-dark">${p.hn}</td>
        
                            <td><a href="patient_profile.php?hn=${p.hn}&an=${p.an}" class="patient-link">${p.name}</a></td>
        
                            <td class="text-center">${p.age} ปี</td>
        
                            <td><span class="text-secondary">${p.doctor}</span></td>
        
                            <td>
                                 <span class="font-weight-bold text-dark">${admitDateShow}</span>
                                <span class="text-muted pl-1">${admitTimeShow}</span>
                            </td>
                                                
                            <td>
                                ${statusDisplay}
                                ${dateDisplay}
                            </td>
    
                            <td class="${nextActionClass}">
                                <span class="font-weight-bold">${nextActionDisplay}</span>
                                ${countdownDisplay}
                            </td>
    
                            <td class="text-center">${actionBtn}</td>
                        </tr>
                    `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        }

        function filterPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const ward = document.getElementById('wardFilter').value;
            const doctor = document.getElementById('doctorFilter').value;

            // กรองข้อมูลผู้ป่วยตามเงื่อนไข
            let result = patients.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(searchTerm) || p.hn.includes(searchTerm) || p.an.includes(searchTerm) || p.underlying.toLowerCase().includes(searchTerm);

                // กรองหอผู้ป่วย
                const matchesWard = ward === 'all' || p.ward === ward;

                // กรองแพทย์เจ้าของไข้
                const matchesDoctor = doctor === 'all' || p.doctor === doctor;
                return matchesSearch && matchesWard && matchesDoctor;
            });

            if (currentSort.column) {
                result.sort((a, b) => {
                    let valA = a[currentSort.column];
                    let valB = b[currentSort.column];

                    if (['admitDate', 'screenDate', 'assessDate'].includes(currentSort.column)) {
                        valA = parseThaiDateForSort(valA);
                        valB = parseThaiDateForSort(valB);
                    } else if (['age', 'id'].includes(currentSort.column)) {
                        valA = Number(valA);
                        valB = Number(valB);
                    } else {
                        valA = valA ? valA.toString().toLowerCase() : '';
                        valB = valB ? valB.toString().toLowerCase() : '';
                    }

                    if (valA < valB) return currentSort.direction === 'asc' ? -1 : 1;
                    if (valA > valB) return currentSort.direction === 'asc' ? 1 : -1;
                    return 0;
                });
            }
            renderTable(result);
        }

        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>

</html>