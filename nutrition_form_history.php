<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// สร้าง CSRF token หากไม่มี
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$my_id = $_SESSION['user_id'];

// ฟังก์ชันวันที่แบบทางการ
function thaiDateOfficial($datetime)
{
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $thai_months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    $day = date('j', $time);
    $month = $thai_months[date('n', $time)];
    $year = date('Y', $time) + 543;
    $hour = date('H:i', $time);
    return "$day $month $year <br><span class='text-muted' style='font-size:0.9em;'>เวลา $hour น.</span>";
}

try {
    $sql_spent = "
        SELECT 
            nutrition_screening.*, 
            patients.patients_firstname, 
            patients.patients_lastname
        FROM nutrition_screening
        JOIN patients ON nutrition_screening.patients_hn = patients.patients_hn
        WHERE nutrition_screening.nut_id = :uid 
        ORDER BY nutrition_screening.screening_datetime DESC
    ";
    $stmt = $conn->prepare($sql_spent);
    $stmt->execute([':uid' => $my_id]);
    $history_spent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql_naf = "
        SELECT 
            nutrition_assessment.*, 
            patients.patients_firstname, 
            patients.patients_lastname
        FROM nutrition_assessment
        JOIN patients ON nutrition_assessment.patients_hn = patients.patients_hn
        WHERE nutrition_assessment.nut_id = :uid 
        ORDER BY nutrition_assessment.assessment_datetime DESC
    ";
    $stmt = $conn->prepare($sql_naf);
    $stmt->execute([':uid' => $my_id]);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="css/nutrition_form_history.css">
</head>

<body>

    <nav class="navbar navbar-expand-md navbar-light fixed-top navbar-custom border-bottom">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
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
                            <a class="dropdown-item py-2 rounded mb-1" href="nutrition_form_history.php">
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

        <div class="card-formal">
            <div class="card-formal">

                <ul class="nav nav-tabs nav-tabs-formal" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="spent-tab" data-toggle="tab" href="#spent" role="tab" aria-controls="spent" aria-selected="true">
                            <i class="fas fa-clipboard-list mr-2"></i> ประวัติการคัดกรอง (SPENT)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="naf-tab" data-toggle="tab" href="#naf" role="tab" aria-controls="naf" aria-selected="false">
                            <i class="fas fa-file-medical mr-2"></i> ประวัติการประเมิน (NAF)
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
                                        <th width="20%">วัน-เวลา ที่บันทึก</th>
                                        <th width="25%">ชื่อ-นามสกุล</th>
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
                                                <td><span class="doc-badge"><?php echo htmlspecialchars($row['doc_no']); ?></span></td>
                                                <td><?php echo thaiDateOfficial($row['screening_datetime']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['patients_firstname']) . ' ' . htmlspecialchars($row['patients_lastname']); ?></strong><br>
                                                    <span class="text-muted small">HN: <?php echo htmlspecialchars($row['patients_hn']); ?> | AN: <?php echo htmlspecialchars($row['admissions_an'] ?: '-'); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="status-label <?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?> (<?php echo $score; ?>)
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="nutrition_screening_form_view.php?doc_no=<?php echo $row['doc_no']; ?>"
                                                        target="_blank" class="btn btn-sm btn-info text-white mb-1 mb-md-0 mr-1" title="ดูรายละเอียด">
                                                        <i class="fas fa-search"></i> ดูข้อมูล
                                                    </a>
                                                    <a href="nutrition_screening_form_report.php?doc_no=<?php echo $row['doc_no']; ?>"
                                                        target="_blank" class="btn btn-sm btn-outline-secondary" title="สั่งพิมพ์">
                                                        <i class="fas fa-print"></i> พิมพ์ PDF
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">-- ไม่พบข้อมูลการคัดกรอง --</td>
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
                                        <th width="20%">วัน-เวลา ที่บันทึก</th>
                                        <th width="25%">ชื่อ-นามสกุล</th>
                                        <th width="20%" class="text-center">ผลการประเมิน</th>
                                        <th width="15%" class="text-center">การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($history_naf) > 0): ?>
                                        <?php foreach ($history_naf as $row): ?>
                                            <?php
                                            $naf_level = $row['naf_level'];
                                            $bgClass = 'status-normal';
                                            if ($naf_level == 'NAF B') $bgClass = 'status-risk';
                                            if ($naf_level == 'NAF C') $bgClass = 'status-severe';
                                            ?>
                                            <tr>
                                                <td><span class="doc-badge"><?php echo htmlspecialchars($row['doc_no']); ?></span></td>
                                                <td><?php echo thaiDateOfficial($row['assessment_datetime']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['patients_firstname']) . ' ' . htmlspecialchars($row['patients_lastname']); ?></strong><br>
                                                    <span class="text-muted small">HN: <?php echo htmlspecialchars($row['patients_hn']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="status-label <?php echo htmlspecialchars($bgClass); ?>">
                                                        <?php echo htmlspecialchars($naf_level); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="nutrition_alert_form_view.php?doc_no=<?php echo $row['doc_no']; ?>"
                                                        target="_blank" class="btn btn-sm btn-info text-white mb-1 mb-md-0 mr-1" title="ดูรายละเอียด">
                                                        <i class="fas fa-search"></i> ดูข้อมูล
                                                    </a>
                                                    <a href="nutrition_alert_form_report.php?doc_no=<?php echo $row['doc_no']; ?>"
                                                        target="_blank" class="btn btn-sm btn-outline-secondary" title="สั่งพิมพ์">
                                                        <i class="fas fa-print"></i> พิมพ์ PDF
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">-- ไม่พบข้อมูลการประเมิน --</td>
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
        </script>
</body>

</html>