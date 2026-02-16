<?php
require_once '../connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();

// 1. เช็ค Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 2. เช็ค Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}

// --- ส่วนดึงข้อมูล (Query) ---

// 1. สถิติ User
$sql_users = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
              FROM nutritionists";
$stmt_users = $conn->query($sql_users);
$stat_users = $stmt_users->fetch(PDO::FETCH_ASSOC);

// 2. สถิติการคัดกรองวันนี้
$sql_screen = "SELECT 
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) 
                          AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month
               FROM nutrition_screening";
$stmt_screen = $conn->query($sql_screen);
$stat_screen = $stmt_screen->fetch(PDO::FETCH_ASSOC);

// 3. สถิติการประเมินผลเดือนนี้
$sql_assess = "SELECT count(*) as total_month 
               FROM nutrition_assessment 
               WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
$stmt_assess = $conn->query($sql_assess);
$stat_assess = $stmt_assess->fetch(PDO::FETCH_ASSOC);

// 4. แจ้งเตือนเคสตกค้าง (Screening แล้วเสี่ยง แต่ยังไม่ Assessment + ยังนอน รพ.)
$sql_pending = "SELECT count(*) as pending_count
                FROM nutrition_screening ns
                JOIN admissions a ON ns.admissions_an = a.admissions_an
                WHERE ns.screening_result = 'มีความเสี่ยง' 
                AND ns.has_assessment = 0
                AND (a.discharge_datetime IS NULL OR a.discharge_datetime = '' OR a.discharge_datetime = '0000-00-00 00:00:00')";
$stmt_pending = $conn->query($sql_pending);
$alert_pending = $stmt_pending->fetch(PDO::FETCH_ASSOC);

// 5. แจ้งเตือนเคสรอคัดกรองซ้ำ (แก้ไข SQL ให้ถูกต้อง)
// Logic: หาการคัดกรองล่าสุดของ HN นั้นๆ ที่ยังนอน รพ. อยู่ และวันที่คัดกรองล่าสุด เกิน 7 วันแล้ว
$sql_rescreen = "SELECT count(*) as rescreen_count
FROM (
    SELECT ns.patients_hn, MAX(ns.created_at) as last_screen
    FROM nutrition_screening ns
    JOIN admissions a ON ns.admissions_an = a.admissions_an
    WHERE (a.discharge_datetime IS NULL OR a.discharge_datetime = '' OR a.discharge_datetime = '0000-00-00 00:00:00')
    GROUP BY ns.patients_hn
) as latest_screen
WHERE latest_screen.last_screen < DATE_SUB(NOW(), INTERVAL 7 DAY)";

// หมายเหตุ: ถ้าใน Database คุณใช้ชื่อคอลัมน์ screening_datetime ให้เปลี่ยน created_at เป็น screening_datetime ครับ
// แต่ถ้าดูตามมาตรฐานทั่วไปมักใช้ created_at

$stmt_rescreen = $conn->query($sql_rescreen);
$alert_rescreen = $stmt_rescreen->fetch(PDO::FETCH_ASSOC);

// 6. Ghost User (User ที่ปิดใช้งานแต่มีการบันทึกข้อมูล)
$sql_ghost = "SELECT n.nut_fullname, ns.created_at 
              FROM nutrition_screening ns
              JOIN nutritionists n ON ns.nut_id = n.nut_id 
              WHERE n.is_active = 0 AND DATE(ns.created_at) > DATE_SUB(NOW(), INTERVAL 7 DAY)";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - ระบบประเมินภาวะโภชนาการ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<style>
    body {
        font-family: "Sarabun", sans-serif;
        background-color: #f8f9fa;
    }

    .card-stat {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: 0.3s;
        margin-bottom: 20px;
    }

    .card-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
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

    .icon-box {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .border-left-danger {
        border-left: 5px solid #dc3545 !important;
    }

    .border-left-warning {
        border-left: 5px solid #ffc107 !important;
    }

    .table td {
        vertical-align: middle;
    }
</style>

<body>

    <div class="d-flex">
        <div class="sidebar p-3 d-flex flex-column" style="width: 250px; flex-shrink: 0;">
            <h4 class="mb-4 text-center py-2 border-bottom border-secondary">
                <i class="fas fa-user-shield"></i> Admin Panel
            </h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-home mr-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="admin_assessments.php" class="nav-link"><i class="fas fa-clipboard-list mr-2"></i> รายงานการประเมิน</a>
                </li>
                <li class="nav-item">
                    <a href="admin_users.php" class="nav-link"><i class="fas fa-users mr-2"></i> จัดการผู้ใช้</a>
                </li>
                <li class="nav-item">
                    <a href="admin_master_data.php" class="nav-link"><i class="fas fa-database mr-2"></i> ข้อมูลมาตรฐาน</a>
                </li>
                <li class="nav-item mt-auto">
                    <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ</a>
                </li>
            </ul>
        </div>

        <div class="container-fluid p-4 bg-light">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-dark font-weight-bold">ภาพรวมระบบ (System Health)</h2>
                <span class="text-muted"><i class="far fa-clock"></i> ข้อมูล ณ วันที่ <?php echo date('d/m/Y H:i'); ?></span>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stat border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase mb-2">นักโภชนาการ</h6>
                                    <h2 class="mb-0 text-primary font-weight-bold"><?php echo $stat_users['total']; ?></h2>
                                </div>
                                <div class="icon-box text-primary bg-light rounded-circle p-3">
                                    <i class="fas fa-user-md"></i>
                                </div>
                            </div>
                            <small class="text-muted mt-3 d-block">
                                Active: <?php echo $stat_users['active_users']; ?> | Inactive: <?php echo $stat_users['inactive_users']; ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase mb-2">คัดกรองวันนี้</h6>
                                    <h2 class="mb-0 text-success font-weight-bold"><?php echo $stat_screen['today']; ?></h2>
                                </div>
                                <div class="icon-box text-success bg-light rounded-circle p-3">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                            </div>
                            <small class="text-muted mt-3 d-block">เดือนนี้: <?php echo $stat_screen['this_month']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted text-uppercase mb-2">ประเมินเดือนนี้</h6>
                                    <h2 class="mb-0 text-info font-weight-bold"><?php echo $stat_assess['total_month']; ?></h2>
                                </div>
                                <div class="icon-box text-info bg-light rounded-circle p-3">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                            </div>
                            <small class="text-muted mt-3 d-block">ใบประเมินฉบับสมบูรณ์</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3 text-secondary">งานที่ต้องดำเนินการ</h5>

                    <div class="card card-stat border-0 shadow-sm mb-3 <?php echo ($alert_pending['pending_count'] > 0) ? 'border-left-danger' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="font-weight-bold <?php echo ($alert_pending['pending_count'] > 0) ? 'text-danger' : 'text-dark'; ?>">เคสเสี่ยงรอประเมิน</h6>
                                    <h3 class="mb-0"><?php echo $alert_pending['pending_count']; ?></h3>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x <?php echo ($alert_pending['pending_count'] > 0) ? 'text-danger' : 'text-secondary'; ?>"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card card-stat border-0 shadow-sm mb-3 <?php echo ($alert_rescreen['rescreen_count'] > 0) ? 'border-left-warning' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="font-weight-bold <?php echo ($alert_rescreen['rescreen_count'] > 0) ? 'text-warning' : 'text-dark'; ?>">ครบกำหนดคัดกรองซ้ำ (>7 วัน)</h6>
                                    <h3 class="mb-0"><?php echo $alert_rescreen['rescreen_count']; ?></h3>
                                </div>
                                <i class="fas fa-history fa-2x <?php echo ($alert_rescreen['rescreen_count'] > 0) ? 'text-warning' : 'text-secondary'; ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 text-secondary"><i class="fas fa-bell mr-2"></i> การแจ้งเตือนระบบ</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>ระดับ</th>
                                            <th>เรื่อง</th>
                                            <th>รายละเอียด</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($alert_pending['pending_count'] > 0): ?>
                                            <tr>
                                                <td><span class="badge badge-danger px-3 py-2">Critical</span></td>
                                                <td class="font-weight-bold">งานค้าง</td>
                                                <td class="text-muted small">มีผู้ป่วย <?php echo $alert_pending['pending_count']; ?> ราย ผลเสี่ยงแต่ยังไม่ประเมิน</td>
                                                <td><span class="text-danger small font-weight-bold">รอ action</span></td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php if ($alert_rescreen['rescreen_count'] > 0): ?>
                                            <tr>
                                                <td><span class="badge badge-warning px-3 py-2">Warning</span></td>
                                                <td class="font-weight-bold">Overdue</td>
                                                <td class="text-muted small">ผู้ป่วย <?php echo $alert_rescreen['rescreen_count']; ?> ราย ครบกำหนดคัดกรองซ้ำ</td>
                                                <td><span class="text-warning small font-weight-bold">ตรวจสอบ</span></td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php
                                        $stmt_ghost = $conn->query($sql_ghost);
                                        $has_ghost = false;
                                        while ($ghost = $stmt_ghost->fetch(PDO::FETCH_ASSOC)) {
                                            $has_ghost = true;
                                            echo "<tr>
                                                <td><span class='badge badge-dark px-3 py-2'>Security</span></td>
                                                <td class='font-weight-bold'>Inactive User</td>
                                                <td class='text-muted small'>User '{$ghost['nut_fullname']}' มี Activity</td>
                                                <td><span class='text-dark small font-weight-bold'>Investigate</span></td>
                                            </tr>";
                                        }
                                        ?>

                                        <?php if ($alert_pending['pending_count'] == 0 && $alert_rescreen['rescreen_count'] == 0 && !$has_ghost): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                                                    <p>ระบบปกติ</p>
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
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>