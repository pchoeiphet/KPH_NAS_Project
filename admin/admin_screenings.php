<?php
require_once '../connect_db.php';
session_start();
date_default_timezone_set('Asia/Bangkok');

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// 2. รับค่าตัวกรอง
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// 3. เตรียม SQL Query (ปรับปรุงให้ตรงกับโครงสร้าง nutrition_screening จริง)
$sql = "SELECT 
            ns.nutrition_screening_id, 
            ns.doc_no, 
            ns.created_at AS screening_date,
            ns.patients_hn AS hn, 
            ns.admissions_an AS an,
            p.patients_firstname, 
            p.patients_lastname,
            n.nut_fullname AS assessor_name,
            -- คำนวณคะแนนรวมจาก q1 - q4
            (COALESCE(ns.q1_weight_loss, 0) + 
             COALESCE(ns.q2_eat_less, 0) + 
             COALESCE(ns.q3_bmi_abnormal, 0) + 
             COALESCE(ns.q4_critical, 0)) AS total_score,
            ns.screening_result,
            ns.screening_status
        FROM nutrition_screening ns
        LEFT JOIN patients p ON ns.patients_hn = p.patients_hn
        LEFT JOIN nutritionists n ON ns.nut_id = n.nut_id
        WHERE 1=1 ";

$params = [];
if (!empty($search)) {
    $sql .= " AND (ns.patients_hn LIKE ? 
                OR ns.admissions_an LIKE ? 
                OR p.patients_firstname LIKE ? 
                OR p.patients_lastname LIKE ?)";
    $s_param = "%$search%";
    $params = array_merge($params, [$s_param, $s_param, $s_param, $s_param]);
}

if (!empty($start_date)) {
    $sql .= " AND DATE(ns.created_at) >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $sql .= " AND DATE(ns.created_at) <= ?";
    $params[] = $end_date;
}

$sql .= " ORDER BY ns.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$screenings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานการคัดกรอง - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --hospital-blue: #2c3e50;
            --hospital-light: #f4f7f6;
        }

        body {
            font-family: "Sarabun", sans-serif;
            background-color: var(--hospital-light);
            color: #444;
        }

        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
        }

        .nav-link {
            color: #bdc3c7;
            padding: 14px 20px;
            border-radius: 8px;
            margin: 4px 10px;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .nav-link.active {
            background: var(--primary-color) !important;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .nav-bottom {
            margin-top: auto;
            margin-bottom: 20px;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            background: white;
        }

        .main-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .score-badge {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 0;
        }
    </style>
</head>

<body>

    <div class="d-flex">
        <div class="sidebar">
            <div class="p-4 text-center">
                <i class="fas fa-hospital-symbol fa-2x mb-2 text-info"></i>
                <h4 class="font-weight-bold">NAS ADMIN</h4>
                <p class="small text-muted mb-0">โรงพยาบาลกำแพงเพชร</p>
            </div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-th-large mr-2"></i> แผงควบคุม</a></li>
                <li class="nav-item"><a href="admin_screenings.php" class="nav-link active"><i class="fas fa-search mr-2"></i> รายงานการคัดกรอง</a></li>
                <li class="nav-item"><a href="admin_assessments.php" class="nav-link"><i class="fas fa-user-check mr-2"></i> รายงานการประเมิน</a></li>
                <li class="nav-item"><a href="admin_users.php" class="nav-link"><i class="fas fa-user-cog mr-2"></i> จัดการผู้ใช้</a></li>
                <li class="nav-item"><a href="admin_master_data.php" class="nav-link"><i class="fas fa-layer-group mr-2"></i> ข้อมูลมาตรฐาน</a></li>
            </ul>
            <ul class="nav flex-column nav-bottom">
                <li class="nav-item"><a href="../logout.php" class="nav-link text-danger" onclick="return confirm('ออกจากระบบ?')"><i class="fas fa-power-off mr-2"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="main-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-bold mb-1">รายงานการคัดกรอง (SPENT Nutrition Screening Tool Reports)</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb small">
                            <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin</a></li>
                            <li class="breadcrumb-item active">Screening Reports</li>
                        </ol>
                    </nav>
                </div>
                <div class="text-right">
                    <span class="badge badge-light p-2 text-muted border">
                        <i class="far fa-calendar-alt mr-1"></i> <?php echo date('d/m/Y H:i'); ?>
                    </span>
                    <button onclick="window.location.reload()" class="btn btn-light border btn-sm ml-2">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="small font-weight-bold">ค้นหาข้อมูลผู้ป่วย</label>
                            <input type="text" name="search" class="form-control" placeholder="ชื่อ, HN, AN..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small font-weight-bold">จากวันที่</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small font-weight-bold">ถึงวันที่</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info btn-block shadow-sm"><i class="fas fa-search"></i> กรองข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-custom">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">วันที่คัดกรอง</th>
                                    <th class="border-0">HN / AN</th>
                                    <th class="border-0">ชื่อ-นามสกุล</th>
                                    <th class="border-0 text-center">คะแนน</th>
                                    <th class="border-0 text-center">ผลการคัดกรอง</th>
                                    <th class="border-0">ผู้ประเมิน</th>
                                    <th class="border-0 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($screenings): foreach ($screenings as $row):
                                        $is_risk = ($row['screening_result'] == 'มีความเสี่ยง');
                                ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold"><?= date('d/m/Y', strtotime($row['screening_date'])) ?></div>
                                                <div class="small text-muted"><?= date('H:i', strtotime($row['screening_date'])) ?> น.</div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border">HN: <?= $row['hn'] ?></span>
                                                <div class="small text-muted">AN: <?= $row['an'] ?></div>
                                            </td>
                                            <td class="font-weight-bold text-dark"><?= $row['patients_firstname'] . ' ' . $row['patients_lastname'] ?></td>
                                            <td class="text-center">
                                                <div class="score-badge <?= $is_risk ? 'bg-danger' : 'bg-success' ?>">
                                                    <?= $row['total_score'] ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-pill <?= $is_risk ? 'badge-danger' : 'badge-success' ?> px-3 py-2">
                                                    <?= htmlspecialchars($row['screening_result']) ?>
                                                </span>
                                            </td>
                                            <td class="small"><?= $row['assessor_name'] ?></td>
                                            <td class="text-center">
                                                <a href="../print_screening.php?id=<?= $row['nutrition_screening_id'] ?>" target="_blank" class="btn btn-sm btn-outline-info shadow-sm">
                                                    <i class="fas fa-print"></i> พิมพ์
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลการคัดกรอง</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>