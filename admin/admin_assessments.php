<?php
require_once '../connect_db.php';
session_start();
date_default_timezone_set('Asia/Bangkok');

// ---------------------------------------------------------------------------
// 1. ตรวจสอบสิทธิ์ Admin
// ---------------------------------------------------------------------------
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// ---------------------------------------------------------------------------
// 2. รับค่าตัวกรอง
// ---------------------------------------------------------------------------
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$nut_id     = isset($_GET['nut_id']) ? $_GET['nut_id'] : '';
$risk_level = isset($_GET['risk_level']) ? $_GET['risk_level'] : '';


$sql = "SELECT 
            -- ข้อมูลการประเมินหลัก
            nutrition_assessment.nutrition_assessment_id,
            nutrition_assessment.doc_no,
            nutrition_assessment.created_at AS assessment_date,
            nutrition_assessment.patients_hn AS hn,
            nutrition_assessment.admissions_an AS an,

            -- ข้อมูลผู้ป่วย
            patients.patients_firstname,
            patients.patients_lastname,
            patients.patients_gender,
            patients.patients_dob,

            -- ข้อมูลการรับผู้ป่วย (Admission)
            (
                SELECT created_at 
                FROM nutrition_screening 
                WHERE nutrition_screening.admissions_an = nutrition_assessment.admissions_an 
                ORDER BY nutrition_screening.created_at DESC 
                LIMIT 1
            ) AS screening_date_time,
            
            -- ข้อมูลผู้ประเมิน
            nutritionists.nut_fullname AS assessor_name,

            -- [แก้ไข 1] ดึงชื่อโรคจากตาราง disease_saved (One-to-Many)
            (
                SELECT GROUP_CONCAT(disease.disease_name SEPARATOR ', ')
                FROM disease_saved
                JOIN disease ON disease_saved.disease_id = disease.disease_id
                WHERE disease_saved.nutrition_assessment_id = nutrition_assessment.nutrition_assessment_id
            ) AS disease_list,

            -- [แก้ไข 2] รายการอาการ (แก้ Alias ให้ถูกต้อง)
            (
                SELECT GROUP_CONCAT(symptom_problem.symptom_problem_name SEPARATOR ', ')
                FROM symptom_problem_saved
                JOIN symptom_problem ON symptom_problem_saved.symptom_problem_id = symptom_problem.symptom_problem_id
                WHERE symptom_problem_saved.nutrition_assessment_id = nutrition_assessment.nutrition_assessment_id
            ) AS symptom_list,

            -- พื้นที่คำนวณคะแนน (CALCULATION ZONE)
            (
                -- ประเภทอาหาร
                COALESCE(food_type.food_type_score, 0) + 
                
                -- ปริมาณอาหาร
                COALESCE(food_amount.food_amount_score, 0) + 
                
                -- รูปร่าง
                COALESCE(patient_shape.patient_shape_score, 0) + 
                
                -- น้ำหนักเปลี่ยนแปลง 4 สัปดาห์
                COALESCE(weight_change_4_weeks.weight_change_4_weeks_score, 0) + 
                
                -- วิธีการชั่งน้ำหนัก
                COALESCE(weight_option.weight_option_score, 0) + 
                
                -- การเข้าถึงอาหาร
                COALESCE(food_access.food_access_score, 0) +
                
                -- [แก้ไข 3] คะแนนจาก โรค (Disease) ต้องใช้ Subquery Sum
                COALESCE(
                    (
                        SELECT SUM(disease.disease_score)
                        FROM disease_saved
                        JOIN disease ON disease_saved.disease_id = disease.disease_id
                        WHERE disease_saved.nutrition_assessment_id = nutrition_assessment.nutrition_assessment_id
                    ), 0
                ) +

                -- [แก้ไข 4] คะแนนจาก อาการ (Symptoms) แก้ Alias na ให้เป็นชื่อเต็ม
                COALESCE(
                    (
                        SELECT SUM(symptom_problem.symptom_problem_score)
                        FROM symptom_problem_saved
                        JOIN symptom_problem ON symptom_problem_saved.symptom_problem_id = symptom_problem.symptom_problem_id
                        WHERE symptom_problem_saved.nutrition_assessment_id = nutrition_assessment.nutrition_assessment_id
                    ), 0
                )
            ) AS total_score,

            food_type.food_type_label,
            food_amount.food_amount_label,
            patient_shape.patient_shape_label,
            weight_change_4_weeks.weight_change_4_weeks_label

        FROM nutrition_assessment
        
        -- ตารางข้อมูลหลัก
        LEFT JOIN patients ON nutrition_assessment.patients_hn = patients.patients_hn
        LEFT JOIN admissions ON nutrition_assessment.admissions_an = admissions.admissions_an
        LEFT JOIN nutritionists ON nutrition_assessment.nut_id = nutritionists.nut_id
        
        -- ตาราง Master Data
        LEFT JOIN food_type ON nutrition_assessment.food_type_id = food_type.food_type_id
        LEFT JOIN food_amount ON nutrition_assessment.food_amount_id = food_amount.food_amount_id
        LEFT JOIN patient_shape ON nutrition_assessment.patient_shape_id = patient_shape.patient_shape_id
        LEFT JOIN weight_change_4_weeks ON nutrition_assessment.weight_change_4_weeks_id = weight_change_4_weeks.weight_change_4_weeks_id
        LEFT JOIN weight_option ON nutrition_assessment.weight_option_id = weight_option.weight_option_id
        LEFT JOIN food_access ON nutrition_assessment.food_access_id = food_access.food_access_id

        WHERE 1=1 ";

$params = [];

// Apply Filters
if (!empty($search)) {
    $sql .= " AND (nutrition_assessment.patients_hn LIKE ? OR nutrition_assessment.admissions_an LIKE ? OR patients.patients_firstname LIKE ? OR patients.patients_lastname LIKE ? OR nutrition_assessment.doc_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($start_date)) {
    $sql .= " AND DATE(nutrition_assessment.created_at) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $sql .= " AND DATE(nutrition_assessment.created_at) <= ?";
    $params[] = $end_date;
}

if (!empty($nut_id)) {
    $sql .= " AND nutrition_assessment.nut_id = ?";
    $params[] = $nut_id;
}

$sql .= " GROUP BY nutrition_assessment.nutrition_assessment_id ";

if (!empty($risk_level)) {
    if ($risk_level == 'high') {
        $sql .= " HAVING total_score >= 6 ";
    } elseif ($risk_level == 'low') {
        $sql .= " HAVING total_score < 6 ";
    }
}

$sql .= " ORDER BY nutrition_assessment.created_at DESC";

// ... (ส่วน Export CSV และ HTML ด้านล่างใช้ของเดิมได้เลยครับ) ...

// ---------------------------------------------------------------------------
// 4. Export CSV Logic
// ---------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $filename = 'Assessment_Report_' . date('Ymd_Hi') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'เลขที่เอกสาร',
        'วันที่ประเมิน',
        'วันที่คัดกรอง',
        'HN',
        'AN',
        'ชื่อ-นามสกุล',
        'เพศ',
        'อายุ(ปี)',
        'โรคประจำตัว',
        'อาการ/ปัญหา',
        'ประเภทอาหาร',
        'ปริมาณอาหาร',
        'รูปร่าง',
        'คะแนนรวม',
        'ผลการประเมิน',
        'ผู้ประเมิน'
    ]);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $risk_text = ($row['total_score'] >= 6) ? 'เสี่ยงภาวะทุพโภชนาการ' : 'ปกติ';
        $fullname = $row['patients_firstname'] . ' ' . $row['patients_lastname'];

        $age = '-';
        if (!empty($row['patients_dob'])) {
            $dob = new DateTime($row['patients_dob']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
        }

        fputcsv($output, [
            $row['doc_no'],
            date('d/m/Y H:i', strtotime($row['assessment_date'])),
            (!empty($row['screening_date_time'])) ? date('d/m/Y H:i', strtotime($row['screening_date_time'])) : '-',
            $row['hn'],
            $row['an'],
            $fullname,
            $row['patients_gender'],
            $age,
            $row['disease_list'],
            $row['symptom_list'],
            $row['food_type_label'],
            $row['food_amount_label'],
            $row['patient_shape_label'],
            $row['total_score'],
            $risk_text,
            $row['assessor_name']
        ]);
    }
    fclose($output);
    exit;
}

// ---------------------------------------------------------------------------
// 5. Query for Web Display
// ---------------------------------------------------------------------------
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อ Nutritionists เพื่อใส่ใน Dropdown Filter
$nut_stmt = $conn->query("SELECT nut_id, nut_fullname FROM nutritionists ORDER BY nut_fullname ASC");
$nutritionists = $nut_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการประเมิน - ระบบประเมินภาวะโภชนาการ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Sarabun", sans-serif;
            background-color: #f8f9fa;
        }

        .card-stat {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
            margin-bottom: 20px;
        }

        .card-stat:hover {
            transform: translateY(-5px);
        }

        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .nav-link.active {
            color: white;
            background-color: #34495e;
            font-weight: bold;
        }

        .nav-link:hover {
            color: white;
            background-color: #3e5871;
            text-decoration: none;
        }

        .card-custom {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table-custom thead th {
            background-color: #e9ecef;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .table-custom td {
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .badge-risk-high {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .badge-risk-low {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .patient-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .score-box {
            font-size: 1.2rem;
            font-weight: 700;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto;
        }

        .score-high {
            background-color: #dc3545;
            color: white;
        }

        .score-low {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <div class="sidebar p-3 d-flex flex-column" style="width: 250px; flex-shrink: 0;">
            <h4 class="mb-4 text-center py-2 border-bottom border-secondary">
                <i class="fas fa-user-shield"></i> Admin Panel
            </h4>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-home mr-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="admin_assessments.php" class="nav-link active"><i class="fas fa-clipboard-list mr-2"></i> รายงานการประเมิน</a></li>
                <li class="nav-item"><a href="admin_users.php" class="nav-link"><i class="fas fa-users mr-2"></i> จัดการผู้ใช้</a></li>
                <li class="nav-item"><a href="admin_master_data.php" class="nav-link"><i class="fas fa-database mr-2"></i> ข้อมูลมาตรฐาน</a></li>
                <li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="container-fluid p-4" style="height: 100vh; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-dark">📄 ข้อมูลการประเมินภาวะโภชนาการ (Assessment)</h2>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-row">
                            <div class="col-md-3 mb-3">
                                <label class="small font-weight-bold">ค้นหา (HN / ชื่อ / AN)</label>
                                <input type="text" name="search" class="form-control" placeholder="ระบุคำค้นหา..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="small font-weight-bold">วันที่เริ่มต้น</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="small font-weight-bold">วันที่สิ้นสุด</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="small font-weight-bold">ระดับความเสี่ยง</label>
                                <select name="risk_level" class="form-control">
                                    <option value="">-- ทั้งหมด --</option>
                                    <option value="high" <?php echo $risk_level == 'high' ? 'selected' : ''; ?>>ความเสี่ยงสูง (Score >= 6)</option>
                                    <option value="low" <?php echo $risk_level == 'low' ? 'selected' : ''; ?>>ปกติ (Score < 6)</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="small font-weight-bold">ผู้ประเมิน</label>
                                <select name="nut_id" class="form-control">
                                    <option value="">-- ทั้งหมด --</option>
                                    <?php foreach ($nutritionists as $nut): ?>
                                        <option value="<?php echo $nut['nut_id']; ?>" <?php echo $nut_id == $nut['nut_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nut['nut_fullname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 text-right">
                                <?php
                                $export_params = $_GET;
                                $export_params['export'] = 'csv';
                                ?>
                                <a href="?<?php echo http_build_query($export_params); ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-csv mr-1"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="pl-4">วันที่ประเมิน</th>
                                    <th>ผู้ป่วย (HN / AN)</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>โรค/อาการ</th>
                                    <th class="text-center">คะแนนรวม</th>
                                    <th class="text-center">ผลการประเมิน</th>
                                    <th>ผู้ประเมิน</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($assessments) > 0): ?>
                                    <?php foreach ($assessments as $row): ?>
                                        <?php
                                        $score = $row['total_score'];
                                        $is_high_risk = ($score >= 6);
                                        $score_class = $is_high_risk ? 'score-high' : 'score-low';
                                        $status_badge = $is_high_risk ? 'badge-risk-high' : 'badge-risk-low';
                                        $status_text = $is_high_risk ? 'เสี่ยงทุพโภชนาการ' : 'ภาวะปกติ';

                                        $fullname = $row['patients_firstname'] . ' ' . $row['patients_lastname'];
                                        ?>
                                        <tr>
                                            <td class="pl-4">
                                                <div class="font-weight-bold"><?php echo date('d/m/Y', strtotime($row['assessment_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['assessment_date'])); ?> น.</small>
                                            </td>
                                            <td>
                                                <span class="d-block text-primary font-weight-bold"><?php echo $row['hn']; ?></span>
                                                <span class="d-block text-secondary small">AN: <?php echo $row['an']; ?></span>
                                            </td>
                                            <td>
                                                <div class="patient-name"><?php echo $fullname; ?></div>
                                                <small class="text-muted">
                                                    เพศ: <?php echo $row['patients_gender']; ?>
                                                    <?php
                                                    if ($row['patients_dob']) {
                                                        $age = (int)date('Y') - (int)date('Y', strtotime($row['patients_dob']));
                                                        echo "| อายุ: " . $age . " ปี";
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px;">
                                                    <div class="text-truncate text-dark mb-1" title="<?php echo $row['disease_list']; ?>">
                                                        <i class="fas fa-stethoscope text-info mr-1"></i> <?php echo !empty($row['disease_list']) ? $row['disease_list'] : '-'; ?>
                                                    </div>
                                                    <div class="text-truncate small text-muted" title="<?php echo $row['symptom_list']; ?>">
                                                        <i class="fas fa-exclamation-circle text-warning mr-1"></i> <?php echo !empty($row['symptom_list']) ? $row['symptom_list'] : '-'; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="score-box <?php echo $score_class; ?> shadow-sm">
                                                    <?php echo $score; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="<?php echo $status_badge; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <i class="fas fa-user-md mr-1"></i> <?php echo htmlspecialchars($row['assessor_name']); ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="../print_assessment.php?id=<?php echo $row['nutrition_assessment_id']; ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-circle" title="พิมพ์รายงาน">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                <br>ไม่พบข้อมูลการประเมินตามเงื่อนไขที่เลือก
                                            </div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>