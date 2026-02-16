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

// 4. แจ้งเตือนเคสตกค้าง
$sql_pending = "SELECT count(*) as pending_count
                FROM nutrition_screening ns
                JOIN admissions a ON ns.admissions_an = a.admissions_an
                WHERE ns.screening_result = 'มีความเสี่ยง' 
                AND ns.has_assessment = 0
                AND (a.discharge_datetime IS NULL OR a.discharge_datetime = '' OR a.discharge_datetime = '0000-00-00 00:00:00')";
$stmt_pending = $conn->query($sql_pending);
$alert_pending = $stmt_pending->fetch(PDO::FETCH_ASSOC);

// แจ้งเตือนเคสรอคัดกรองซ้ำ
$sql_rescreen = "SELECT count(*) as rescreen_count
FROM (
    SELECT ns.patients_hn, ns.screening_result, MAX(ns.created_at) as last_screen
    FROM nutrition_screening ns
    JOIN admissions a ON ns.admissions_an = a.admissions_an
    WHERE (a.discharge_datetime IS NULL OR a.discharge_datetime = '' OR a.discharge_datetime = '0000-00-00 00:00:00')
    GROUP BY ns.patients_hn
) as latest_screen
WHERE latest_screen.screening_result = 'ปกติ' 
AND latest_screen.last_screen < DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt_rescreen = $conn->query($sql_rescreen);
$alert_rescreen = $stmt_rescreen->fetch(PDO::FETCH_ASSOC);

// Ghost User
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
    <style>
        :root {
            --primary-color: #007bff;
            --hospital-blue: #2c3e50;
            --hospital-light: #f4f7f6;
            --success-green: #28a745;
            --danger-red: #e74c3c;
            --warning-orange: #f39c12;
        }

        body {
            font-family: "Sarabun", sans-serif;
            background-color: var(--hospital-light);
            color: #444;
        }

        /* Sidebar Styling (คงที่เหมือนกันทุกหน้า) */
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
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
            background: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .nav-bottom {
            margin-top: auto;
            margin-bottom: 20px;
        }

        /* Card & UI Styling */
        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background: white;
        }

        .card-stat:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            border-radius: 12px;
        }

        .main-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .border-left-primary {
            border-left: 5px solid var(--primary-color);
        }

        .border-left-success {
            border-left: 5px solid var(--success-green);
        }

        .border-left-info {
            border-left: 5px solid #17a2b8;
        }

        .border-left-danger {
            border-left: 5px solid var(--danger-red);
        }

        .border-left-warning {
            border-left: 5px solid var(--warning-orange);
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
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-th-large mr-2"></i> แผงควบคุม</a>
                </li>
                <li class="nav-item"><a href="admin_screenings.php" class="nav-link"><i class="fas fa-search mr-2"></i> รายงานการคัดกรอง</a></li>
                <li class="nav-item">
                    <a href="admin_assessments.php" class="nav-link"><i class="fas fa-user-check mr-2"></i> รายงานการประเมิน</a>
                </li>
                <li class="nav-item">
                    <a href="admin_users.php" class="nav-link"><i class="fas fa-user-cog mr-2"></i> จัดการผู้ใช้</a>
                </li>
                <li class="nav-item">
                    <a href="admin_master_data.php" class="nav-link"><i class="fas fa-layer-group mr-2"></i> ข้อมูลมาตรฐาน</a>
                </li>
            </ul>

            <ul class="nav flex-column nav-bottom">
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link text-danger" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')">
                        <i class="fas fa-power-off mr-2"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>

        <div class="main-content">
            <div class="main-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-bold mb-1 text-dark">ภาพรวมระบบ (System Health)</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb small">
                            <li class="breadcrumb-item"><a href="#">Admin</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
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

            <div class="row">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-primary h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-box bg-primary text-white mr-3 shadow-sm">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small">นักโภชนาการทั้งหมด</h6>
                                <h3 class="font-weight-bold mb-0"><?php echo $stat_users['total']; ?></h3>
                                <div class="mt-2">
                                    <span class="badge badge-light border text-success font-weight-normal">
                                        <i class="fas fa-circle mr-1" style="font-size: 8px;"></i> ปกติ <?php echo $stat_users['active_users']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-success h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-box bg-success text-white mr-3 shadow-sm">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small">คัดกรองวันนี้</h6>
                                <h3 class="font-weight-bold mb-0 text-success"><?php echo $stat_screen['today']; ?></h3>
                                <p class="text-muted small mb-0 mt-1">สะสมเดือนนี้: <?php echo $stat_screen['this_month']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-info h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-box bg-info text-white mr-3 shadow-sm">
                                <i class="fas fa-file-waveform"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small">ประเมินผลเดือนนี้</h6>
                                <h3 class="font-weight-bold mb-0 text-info"><?php echo $stat_assess['total_month']; ?></h3>
                                <p class="text-muted small mb-0 mt-1">ใบประเมินสมบูรณ์</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <h5 class="font-weight-bold mb-3 text-dark">งานที่เร่งด่วน</h5>
                    <div class="card card-stat mb-3 <?php echo ($alert_pending['pending_count'] > 0) ? 'border-left-danger bg-light' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">เคสเสี่ยงรอประเมิน</p>
                                    <h3 class="font-weight-bold mb-0 <?php echo ($alert_pending['pending_count'] > 0) ? 'text-danger' : ''; ?>">
                                        <?php echo $alert_pending['pending_count']; ?>
                                    </h3>
                                </div>
                                <i class="fas fa-fire-alt fa-2x <?php echo ($alert_pending['pending_count'] > 0) ? 'text-danger' : 'text-light'; ?>"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card card-stat mb-3 <?php echo ($alert_rescreen['rescreen_count'] > 0) ? 'border-left-warning' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted mb-1 small">ครบกำหนดคัดกรองซ้ำ</p>
                                    <h3 class="font-weight-bold mb-0 <?php echo ($alert_rescreen['rescreen_count'] > 0) ? 'text-warning' : ''; ?>">
                                        <?php echo $alert_rescreen['rescreen_count']; ?>
                                    </h3>
                                </div>
                                <i class="fas fa-clock-rotate-left fa-2x <?php echo ($alert_rescreen['rescreen_count'] > 0) ? 'text-warning' : 'text-light'; ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-stat h-100">
                        <div class="card-header bg-white border-0 py-4 d-flex justify-content-between">
                            <h5 class="font-weight-bold mb-0 text-dark">
                                <i class="fas fa-bell text-warning mr-2"></i>การแจ้งเตือนและเหตุการณ์
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>ความรุนแรง</th>
                                            <th>หัวข้อ</th>
                                            <th>รายละเอียด</th>
                                            <th>สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($alert_pending['pending_count'] > 0): ?>
                                            <tr>
                                                <td><span class="badge badge-pill badge-danger">CRITICAL</span></td>
                                                <td class="font-weight-bold">เสี่ยงสูงตกค้าง</td>
                                                <td class="small text-muted">พบผู้ป่วย <?php echo $alert_pending['pending_count']; ?> ราย รอประเมินละเอียด</td>
                                                <td><i class="fas fa-spinner fa-spin mr-1 text-danger"></i> <span class="text-danger small">เร่งด่วน</span></td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php if ($alert_rescreen['rescreen_count'] > 0): ?>
                                            <tr>
                                                <td><span class="badge badge-pill badge-warning">WARNING</span></td>
                                                <td class="font-weight-bold">เลยกำหนดเวลา</td>
                                                <td class="small text-muted">ครบ 7 วันสำหรับผู้ป่วยคัดกรองปกติ</td>
                                                <td><span class="text-warning small font-weight-bold">รอตรวจสอบ</span></td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php
                                        $stmt_ghost = $conn->query($sql_ghost);
                                        $has_ghost = false;
                                        while ($ghost = $stmt_ghost->fetch(PDO::FETCH_ASSOC)) {
                                            $has_ghost = true;
                                            echo "<tr>
                                                <td><span class='badge badge-pill badge-dark'>SECURITY</span></td>
                                                <td class='font-weight-bold'>Inactive Activity</td>
                                                <td class='small text-muted'>ผู้ใช้ '{$ghost['nut_fullname']}' เข้าใช้งานระบบ</td>
                                                <td><span class='badge badge-light border text-dark small'>ตรวจสอบสิทธิ์</span></td>
                                            </tr>";
                                        }
                                        ?>

                                        <?php if ($alert_pending['pending_count'] == 0 && $alert_rescreen['rescreen_count'] == 0 && !$has_ghost): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <img src="https://cdn-icons-png.flaticon.com/512/4436/4436481.png" width="60" class="mb-3" style="filter: grayscale(1); opacity: 0.3;">
                                                    <p class="text-muted">ระบบทำงานปกติ ไม่พบข้อผิดพลาดหรือเคสตกค้าง</p>
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