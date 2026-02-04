<?php
require_once '../connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();

// 1. เช็ค Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 2. เช็ค Admin (ต้องเป็น admin เท่านั้น)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}

// --- ส่วนดึงข้อมูล (Query) ---

// 1. สถิติ User (ใช้ is_active)
$sql_users = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
              FROM nutritionists";
$stmt_users = $conn->query($sql_users);
$stat_users = $stmt_users->fetch(PDO::FETCH_ASSOC);

// 2. สถิติการคัดกรอง (ใช้ created_at)
$sql_screen = "SELECT 
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) 
                          AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month
               FROM nutrition_screening";
$stmt_screen = $conn->query($sql_screen);
$stat_screen = $stmt_screen->fetch(PDO::FETCH_ASSOC);

// 3. สถิติการประเมินผล (ใช้ created_at)
$sql_assess = "SELECT count(*) as total_month 
               FROM nutrition_assessment 
               WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
$stmt_assess = $conn->query($sql_assess);
$stat_assess = $stmt_assess->fetch(PDO::FETCH_ASSOC);

// 4. แจ้งเตือนเคสตกค้าง (Screening แล้วเสี่ยง แต่ยังไม่ Assessment)
$sql_pending = "SELECT count(*) as pending_count
                FROM nutrition_screening
                WHERE screening_result = 'มีความเสี่ยง' 
                AND has_assessment = 0";
$stmt_pending = $conn->query($sql_pending);
$alert_pending = $stmt_pending->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - KPH Nutrition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .card-stat {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .card-stat:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            color: white;
        }

        .nav-link {
            color: #bdc3c7;
        }

        .nav-link.active {
            color: white;
            background-color: #34495e;
            font-weight: bold;
        }

        .nav-link:hover {
            color: white;
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <div class="sidebar p-3 d-flex flex-column" style="width: 250px;">
            <h4 class="mb-4 text-center"><i class="fas fa-user-shield"></i> Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link active"><i class="fas fa-home me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link"><i class="fas fa-users me-2"></i> จัดการผู้ใช้</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link"><i class="fas fa-file-alt me-2"></i> รายงานระบบ</a>
                </li>
                <li class="nav-item mt-auto">
                    <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a>
                </li>
            </ul>
        </div>

        <div class="container-fluid p-4 bg-light">

            <h2 class="mb-4">ภาพรวมระบบ (System Health)</h2>

            <div class="row g-3 mb-4">

                <div class="col-md-3">
                    <div class="card card-stat bg-primary text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">นักโภชนาการทั้งหมด</h6>
                                <h2 class="mb-0"><?php echo $stat_users['total']; ?></h2>
                                <small>Active: <?php echo $stat_users['active_users']; ?> | Inactive: <?php echo $stat_users['inactive_users']; ?></small>
                            </div>
                            <div class="icon-box"><i class="fas fa-user-md"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-success text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">การคัดกรอง (วันนี้)</h6>
                                <h2 class="mb-0"><?php echo $stat_screen['today']; ?></h2>
                                <small>เดือนนี้: <?php echo $stat_screen['this_month']; ?> รายการ</small>
                            </div>
                            <div class="icon-box"><i class="fas fa-clipboard-check"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat bg-info text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">บันทึก Assess (เดือนนี้)</h6>
                                <h2 class="mb-0"><?php echo $stat_assess['total_month']; ?></h2>
                                <small>ใบประเมินฉบับสมบูรณ์</small>
                            </div>
                            <div class="icon-box"><i class="fas fa-file-medical"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-stat <?php echo ($alert_pending['pending_count'] > 0) ? 'bg-danger' : 'bg-secondary'; ?> text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">เคสเสี่ยงที่รอประเมิน</h6>
                                <h2 class="mb-0"><?php echo $alert_pending['pending_count']; ?></h2>
                                <small>Screened แต่ยังไม่ Assessed</small>
                            </div>
                            <div class="icon-box"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0 text-secondary"><i class="fas fa-bell"></i> การแจ้งเตือนระบบ (System Alerts)</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
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
                                            <td><span class="badge bg-danger">Critical</span></td>
                                            <td>งานค้าง (Unassessed)</td>
                                            <td>มีผู้ป่วย <?php echo $alert_pending['pending_count']; ?> ราย ที่ผลคัดกรองมีความเสี่ยง แต่ยังไม่ออกใบประเมิน</td>
                                            <td><span class="text-danger">รอการดำเนินการ</span></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-success">
                                                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                                ระบบปกติ ไม่พบปัญหาเร่งด่วน
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php
                                    // ดึงข้อมูล User Inactive ที่มีการใช้งานใน 7 วันล่าสุด
                                    $sql_ghost = "SELECT n.nut_fullname, ns.created_at 
                                                  FROM nutrition_screening ns 
                                                  JOIN nutritionists n ON ns.nut_id = n.nut_id 
                                                  WHERE n.is_active = 0 AND DATE(ns.created_at) > DATE_SUB(NOW(), INTERVAL 7 DAY)";
                                    $stmt_ghost = $conn->query($sql_ghost);

                                    while ($ghost = $stmt_ghost->fetch(PDO::FETCH_ASSOC)) {
                                        // แก้ไขจุดที่เคย Error: เปลี่ยนชื่อตัวแปรให้ตรงกับ SQL
                                        $ghost_name = htmlspecialchars($ghost['nut_fullname']);
                                        $ghost_time = htmlspecialchars($ghost['created_at']);

                                        echo "<tr>
                                            <td><span class='badge bg-warning text-dark'>Suspicious</span></td>
                                            <td>Inactive User Activity</td>
                                            <td>พบการบันทึกข้อมูลโดย User ที่ถูกระงับ: {$ghost_name} เมื่อ {$ghost_time}</td>
                                            <td>ตรวจสอบด่วน</td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>