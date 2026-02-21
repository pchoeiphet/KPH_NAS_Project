<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();

$currentPage = basename($_SERVER['PHP_SELF']); // ดึงชื่อไฟล์ปัจจุบัน

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// สร้าง CSRF token หากไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$my_id = $_SESSION['user_id'];

// รับค่าจาก Form ค้นหา
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$ward_id    = isset($_GET['ward_id']) ? $_GET['ward_id'] : '';

// ดึงข้อมูลหอผู้ป่วยสำหรับ Dropdown
$ward = [];
try {
    $stmt_ward = $conn->query("SELECT ward_id, ward_name FROM ward ORDER BY ward_name ASC");
    $ward = $stmt_ward->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching ward: " . $e->getMessage());
}

// ฟังก์ชันวันที่แบบทางการ
function thaiDateOfficial($datetime)
{
    if (!$datetime) {
        return '-';
    }
    $time = strtotime($datetime);

    // ดึงวันที่ เดือน (แบบมีเลข 0 นำหน้า) และปี (+543 เป็น พ.ศ.)
    $day = date('d', $time);
    $month = date('m', $time);
    $year = date('Y', $time) + 543;
    $hour = date('H:i', $time);

    // นำมาประกอบกันเป็น 20/02/2569
    return $day . "/" . $month . "/" . $year . " <br><span class='text-muted' style='font-size:0.9em;'>เวลา " . $hour . " น.</span>";
}

try {
    // Query สำหรับประวัติการคัดกรอง (SPENT)
    $sql_spent = "
        SELECT 
            ns.*, 
            p.patient_firstname, 
            p.patient_lastname,
            w.ward_name
        FROM nutrition_screening ns
        JOIN patient p ON ns.patient_hn = p.patient_hn
        LEFT JOIN admissions a ON ns.admissions_an = a.admissions_an
        LEFT JOIN ward w ON a.ward_id = w.ward_id
        WHERE ns.nutritionist_id = :uid 
    ";

    $params_spent = [':uid' => $my_id];

    // เงื่อนไขการค้นหา SPENT
    if ($search !== '') {
        $sql_spent .= " AND (p.patient_hn LIKE :search OR ns.admissions_an LIKE :search OR p.patient_firstname LIKE :search OR p.patient_lastname LIKE :search)";
        $params_spent[':search'] = "%" . $search . "%";
    }
    if ($start_date !== '') {
        $sql_spent .= " AND DATE(ns.nutrition_screening_datetime) >= :start_date";
        $params_spent[':start_date'] = $start_date;
    }
    if ($end_date !== '') {
        $sql_spent .= " AND DATE(ns.nutrition_screening_datetime) <= :end_date";
        $params_spent[':end_date'] = $end_date;
    }
    if ($ward_id !== '') {
        $sql_spent .= " AND a.ward_id = :ward_id";
        $params_spent[':ward_id'] = $ward_id;
    }

    $sql_spent .= " ORDER BY ns.nutrition_screening_datetime DESC";
    $stmt = $conn->prepare($sql_spent);
    $stmt->execute($params_spent);
    $history_spent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query สำหรับประวัติการประเมิน (NAF)
    $sql_naf = "
        SELECT 
            na.*, 
            p.patient_firstname, 
            p.patient_lastname,
            w.ward_name
        FROM nutrition_assessment na
        JOIN patient p ON na.patient_hn = p.patient_hn
        LEFT JOIN admissions a ON na.admissions_an = a.admissions_an
        LEFT JOIN ward w ON a.ward_id = w.ward_id
        WHERE na.nutritionist_id = :uid 
    ";

    $params_naf = [':uid' => $my_id];

    // เงื่อนไขการค้นหา NAF
    if ($search !== '') {
        $sql_naf .= " AND (p.patient_hn LIKE :search OR na.admissions_an LIKE :search OR p.patient_firstname LIKE :search OR p.patient_lastname LIKE :search)";
        $params_naf[':search'] = "%" . $search . "%";
    }
    if ($start_date !== '') {
        $sql_naf .= " AND DATE(na.nutrition_assessment_datetime) >= :start_date";
        $params_naf[':start_date'] = $start_date;
    }
    if ($end_date !== '') {
        $sql_naf .= " AND DATE(na.nutrition_assessment_datetime) <= :end_date";
        $params_naf[':end_date'] = $end_date;
    }
    if ($ward_id !== '') {
        $sql_naf .= " AND a.ward_id = :ward_id";
        $params_naf[':ward_id'] = $ward_id;
    }

    $sql_naf .= " ORDER BY na.nutrition_assessment_datetime DESC";
    $stmt = $conn->prepare($sql_naf);
    $stmt->execute($params_naf);
    $history_naf = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $history_spent = [];
    $history_naf = [];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ประวัติการทำงานของฉัน | โรงพยาบาลกำแพงเพชร</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nutrition_form_history.css">
    <style>
        .filter-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #eaeaea;
        }

        .filter-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-md navbar-light fixed-top navbar-custom border-bottom">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="img/logo_kph.jpg" class="brand-logo mr-2 d-none d-sm-block" alt="Logo" onerror="this.style.display='none'">
                <div class="brand-text">
                    <h1>ระบบประเมินภาวะโภชนาการ</h1>
                    <small>Nutrition Assessment System (NAS)</small>
                </div>
            </a>

            <ul class="navbar-nav ml-auto align-items-center d-none d-md-flex">
                <li class="nav-item mx-1">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'index.php') ? 'active text-primary' : 'text-dark'; ?>" href="index.php">
                        <i class="fa-solid fa-home mr-1"></i> รายชื่อผู้ป่วยใน
                    </a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link px-3 <?php echo ($currentPage == 'nutrition_form_history.php') ? 'active text-primary' : 'text-dark'; ?>" href="nutrition_form_history.php">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> ประวัติการทำงานของฉัน
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link p-0" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="min-width: 290px;">
                        <div class="user-profile-btn">
                            <div class="user-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                            <div class="user-info d-none d-md-block" style="flex-grow: 1;">
                                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($_SESSION['user_position']); ?></div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-muted mr-2" style="font-size: 0.8rem;"></i>
                        </div>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2 pb-0" aria-labelledby="userDropdown" style="border-radius: 12px; min-width: 250px; overflow: hidden;">
                        <div class="dropdown-header bg-light border-bottom py-3">
                            <div class="d-flex align-items-center px-2">
                                <div class="user-avatar mr-3 bg-white border" style="width: 45px; height: 45px; font-size: 1.3rem; color: #2c3e50;">
                                    <i class="fa-solid fa-user-doctor"></i>
                                </div>
                                <div style="line-height: 1.3;">
                                    <h6 class="font-weight-bold text-dark mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                                    <small class="text-muted d-block"><?php echo isset($_SESSION['hospital']) ? htmlspecialchars($_SESSION['hospital']) : 'โรงพยาบาลกำแพงเพชร'; ?></small>
                                    <span class="badge badge-info mt-1 font-weight-normal px-2">License: <?php echo isset($_SESSION['user_code']) ? htmlspecialchars($_SESSION['user_code']) : '-'; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="p-2">
                            <a class="dropdown-item py-2 rounded mb-1" href="nutrition_form_history.php">
                                <span><i class="fa-solid fa-clock-rotate-left mr-2 text-primary" style="width:20px;"></i> ประวัติการทำงานของฉัน</span>
                            </a>
                            <a class="dropdown-item py-2 rounded" href="electronic_sign.php">
                                <span><i class="fa-solid fa-file-signature mr-2 text-success" style="width:20px;"></i> ลายเซ็นอิเล็กทรอนิกส์ (E-Sign)</span>
                            </a>
                        </div>
                        <div class="bg-light border-top p-2">
                            <a class="dropdown-item py-2 rounded text-danger font-weight-bold" href="#" onclick="confirmLogout()">
                                <i class="fa-solid fa-right-from-bracket mr-2" style="width:20px;"></i> ออกจากระบบ
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid px-lg-5 mt-4 mb-5">

        <div class="d-flex justify-content-center align-items-center mb-3 position-relative">
            <a href="index.php" class="btn btn-sm btn-outline-secondary position-absolute" style="left: 0;" title="ย้อนกลับ">
                <i class="fas fa-arrow-left mr-2"></i> ย้อนกลับ
            </a>
            <h3 class="text-dark font-weight-bold mb-0">
                ประวัติการทำงานของ <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </h3>
            <div class="text-muted small position-absolute" style="right: 0;">
                ข้อมูล ณ วันที่: <?php echo date("d/m/") . (date("Y") + 543); ?>
            </div>
        </div>

        <div class="filter-card p-3 mb-4">
            <form method="GET" action="nutrition_form_history.php">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="filter-label">ค้นหาข้อมูล</label>
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="HN, AN, ชื่อ, นามสกุล" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="filter-label">หอผู้ป่วย/แผนก</label>
                        <select name="ward_id" class="form-control form-control-sm">
                            <option value="">-- ทุกหอผู้ป่วย --</option>
                            <?php foreach ($ward as $w): ?>
                                <option value="<?php echo htmlspecialchars($w['ward_id']); ?>" <?php echo ($ward_id == $w['ward_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($w['ward_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="filter-label">ตั้งแต่วันที่</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="filter-label">ถึงวันที่</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="filter-label d-none d-md-block">&nbsp;</label>
                        <div class="d-flex">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1 mr-1"><i class="fas fa-search"></i> ค้นหา</button>
                            <a href="nutrition_form_history.php" class="btn btn-sm btn-light border flex-grow-1"><i class="fas fa-undo"></i> ล้าง</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-formal">
            <div class="card-formal">

                <ul class="nav nav-tabs nav-tabs-formal" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="spent-tab" data-toggle="tab" href="#spent" role="tab" aria-controls="spent" aria-selected="true">
                            <i class="fas fa-clipboard-list mr-2"></i> ประวัติการคัดกรอง (SPENT)
                            <span class="badge badge-primary ml-1"><?php echo count($history_spent); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="naf-tab" data-toggle="tab" href="#naf" role="tab" aria-controls="naf" aria-selected="false">
                            <i class="fas fa-file-medical mr-2"></i> ประวัติการประเมิน (NAF)
                            <span class="badge badge-primary ml-1"><?php echo count($history_naf); ?></span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content p-4" id="myTabContent">

                    <div class="tab-pane fade show active" id="spent" role="tabpanel" aria-labelledby="spent-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-formal">
                                <thead>
                                    <tr>
                                        <th width="15%">เลขที่เอกสาร</th>
                                        <th width="15%">วัน-เวลา ที่คัดกรอง</th>
                                        <th width="20%">ชื่อ-นามสกุล</th>
                                        <th width="15%">หอผู้ป่วย</th>
                                        <th width="20%" class="text-center">ผลการคัดกรอง</th>
                                        <th width="15%" class="text-center">การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($history_spent) > 0): ?>
                                        <?php foreach ($history_spent as $row): ?>
                                            <?php
                                            $score = $row['q1_weight_loss'] + $row['q2_eat_less'] + $row['q3_bmi_abnormal'] + $row['q4_critical'];
                                            $statusClass = ($score >= 2) ? 'status-risk' : 'status-normal';
                                            $statusText = ($score >= 2) ? 'มีความเสี่ยง' : 'ปกติ';
                                            ?>
                                            <tr>
                                                <td class="align-middle"><span class="doc-badge"><?php echo htmlspecialchars($row['doc_no']); ?></span></td>
                                                <td class="align-middle"><?php echo thaiDateOfficial($row['nutrition_screening_datetime']); ?></td>
                                                <td class="align-middle">
                                                    <strong><?php echo htmlspecialchars($row['patient_firstname']) . ' ' . htmlspecialchars($row['patient_lastname']); ?></strong><br>
                                                    <span class="text-muted small">HN: <?php echo htmlspecialchars($row['patient_hn']); ?> | AN: <?php echo htmlspecialchars($row['admissions_an'] ? $row['admissions_an'] : '-'); ?></span>
                                                </td>
                                                <td class="align-middle text-info font-weight-bold">
                                                    <?php echo htmlspecialchars(isset($row['ward_name']) ? $row['ward_name'] : '-'); ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <span class="status-label <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?> (<?php echo $score; ?>)
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <a href="nutrition_screening_form_view.php?doc_no=<?php echo htmlspecialchars($row['doc_no']); ?>" target="_blank" class="btn btn-sm btn-info text-white mb-1 mb-xl-0" title="ดูรายละเอียด">
                                                        <i class="fas fa-search"></i>
                                                    </a>
                                                    <a href="nutrition_screening_form_report.php?doc_no=<?php echo htmlspecialchars($row['doc_no']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="สั่งพิมพ์">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">-- ไม่พบข้อมูลการคัดกรองตามเงื่อนไข --</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="naf" role="tabpanel" aria-labelledby="naf-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-formal">
                                <thead>
                                    <tr>
                                        <th width="15%">เลขที่เอกสาร</th>
                                        <th width="15%">วัน-เวลา ที่ประเมิน</th>
                                        <th width="20%">ชื่อ-นามสกุล</th>
                                        <th width="15%">หอผู้ป่วย</th>
                                        <th width="20%" class="text-center">ผลการประเมิน</th>
                                        <th width="15%" class="text-center">การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($history_naf) > 0): ?>
                                        <?php foreach ($history_naf as $row): ?>
                                            <?php
                                            $naf_level = isset($row['naf_level']) ? $row['naf_level'] : '-';
                                            $bgClass = 'status-normal';
                                            $naf_desc = 'Normal - Mild Malnutrition';

                                            if (strpos($naf_level, 'NAF B') !== false) {
                                                $bgClass = 'status-risk';
                                                $naf_desc = 'Moderate Malnutrition';
                                            } elseif (strpos($naf_level, 'NAF C') !== false) {
                                                $bgClass = 'status-severe';
                                                $naf_desc = 'Severe Malnutrition';
                                            }
                                            ?>
                                            <tr>
                                                <td class="align-middle text-center">
                                                    <span class="doc-badge"><?php echo htmlspecialchars($row['doc_no']); ?></span>
                                                </td>
                                                <td class="align-middle"><?php echo thaiDateOfficial($row['nutrition_assessment_datetime']); ?></td>
                                                <td class="align-middle">
                                                    <strong><?php echo htmlspecialchars($row['patient_firstname']) . ' ' . htmlspecialchars($row['patient_lastname']); ?></strong><br>
                                                    <span class="text-muted small">HN: <?php echo htmlspecialchars($row['patient_hn']); ?> | AN: <?php echo htmlspecialchars($row['admissions_an'] ? $row['admissions_an'] : '-'); ?></span>
                                                </td>
                                                <td class="align-middle text-info font-weight-bold">
                                                    <?php echo htmlspecialchars(isset($row['ward_name']) ? $row['ward_name'] : '-'); ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <span class="status-label <?php echo $bgClass; ?>" style="display:inline-block; min-width:160px; padding: 5px 10px; border-radius: 20px;">
                                                        <strong><?php echo htmlspecialchars($naf_level); ?></strong>
                                                        <div style="font-size: 0.75rem; font-weight: normal; margin-top: 2px; opacity: 0.9;">
                                                            (<?php echo htmlspecialchars($naf_desc); ?>)
                                                        </div>
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <a href="nutrition_alert_form_view.php?doc_no=<?php echo htmlspecialchars($row['doc_no']); ?>" target="_blank" class="btn btn-sm btn-info text-white mb-1 mb-xl-0" title="ดูข้อมูล">
                                                        <i class="fas fa-search"></i>
                                                    </a>
                                                    <a href="nutrition_alert_form_report.php?doc_no=<?php echo htmlspecialchars($row['doc_no']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="สั่งพิมพ์">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">-- ไม่พบข้อมูลการประเมินตามเงื่อนไข --</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // ยืนยันการออกจากระบบ
            function confirmLogout() {
                if (confirm('ยืนยันการออกจากระบบ?')) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'logout.php';
                    var token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = 'csrf_token';
                    token.value = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
                    form.appendChild(token);
                    document.body.appendChild(form);
                    form.submit();
                }
            }

            // ช่วยจำ Tab ล่าสุดที่กด หากมีการ Submit Form ค้นหา จะได้ไม่ต้องเด้งกลับไป Tab แรกเสมอ
            $(document).ready(function() {
                $('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
                    localStorage.setItem('activeTabHistory', $(e.target).attr('href'));
                });
                var activeTab = localStorage.getItem('activeTabHistory');
                if (activeTab) {
                    $('#myTab a[href="' + activeTab + '"]').tab('show');
                }
            });
        </script>
</body>

</html>