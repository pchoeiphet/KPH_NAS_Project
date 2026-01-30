<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nut_id = $_SESSION['user_id'];

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_destroy();
    error_log("Session timeout for user: " . $_SESSION['user_id']);
    die("ข้อผิดพลาด: หมดเวลาการใช้งาน");
}
$_SESSION['last_activity'] = time();

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_msg = '';
$success_msg = '';
$current_doc = null;

// Handle signature submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token validation failed for user: " . $_SESSION['user_id']);
        $error_msg = "ข้อผิดพลาด: โทเคนไม่ถูกต้อง";
    } else {
        // Validate input
        $signature_type = 'canvas'; // ใช้แค่ canvas เท่านั้น
        $signature_data = trim($_POST['signature_data'] ?? '');

        // Input validation
        if (empty($signature_data)) {
            $error_msg = "ข้อผิดพลาด: กรุณาลงนามก่อนบันทึก";
        } else {
            try {
                    // Sanitize signature data
                    if (!preg_match('/^data:image\/png;base64,/', $signature_data)) {
                        $error_msg = "ข้อผิดพลาด: รูปแบบลายเซ็นไม่ถูกต้อง";
                    } else {
                        $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
                        if (!base64_decode($signature_data, true)) {
                            $error_msg = "ข้อผิดพลาด: ข้อมูลลายเซ็นเสียหาย";
                        }
                    }

                    // If no validation error, save signature
                    if (empty($error_msg)) {
                        try {
                            $conn->beginTransaction();

                            // Check if signature already exists for this nutritionist
                            $stmt_check_sig = $conn->prepare("SELECT signature_id FROM nutrition_signature WHERE nut_id = :nut_id");
                            $stmt_check_sig->execute([':nut_id' => $nut_id]);
                            $existing_sig = $stmt_check_sig->fetch(PDO::FETCH_ASSOC);

                            if ($existing_sig) {
                                // Update existing signature
                                $stmt_update = $conn->prepare("
                                    UPDATE nutrition_signature 
                                    SET signature_type = 'canvas',
                                        signature_data = :sig_data,
                                        signed_datetime = NOW()
                                    WHERE nut_id = :nut_id
                                ");
                                $stmt_update->execute([
                                    ':sig_data' => $signature_data,
                                    ':nut_id' => $nut_id
                                ]);
                            } else {
                                // Insert new signature
                                $stmt_insert = $conn->prepare("
                                    INSERT INTO nutrition_signature (nut_id, signature_type, signature_data, signed_datetime)
                                    VALUES (:nut_id, 'canvas', :sig_data, NOW())
                                ");
                                $stmt_insert->execute([
                                    ':nut_id' => $nut_id,
                                    ':sig_data' => $signature_data
                                ]);
                            }

                            // Log audit
                            error_log("User " . $_SESSION['user_id'] . " saved canvas signature at " . date('Y-m-d H:i:s'));

                            $conn->commit();
                            $success_msg = "บันทึกลายเซ็นสำเร็จ! ลายเซ็นของคุณจะปรากฏบน PDF รายงานทั้งหมด";

                            // Clear form
                            $signature_data = '';
                        } catch (PDOException $e) {
                            $conn->rollBack();
                            error_log("Database error in e-sign: " . $e->getMessage());
                            $error_msg = "ข้อผิดพลาด: ไม่สามารถบันทึกลายเซ็นได้";
                        }
                    }
            } catch (PDOException $e) {
                error_log("Database error in e-sign: " . $e->getMessage());
                $error_msg = "ข้อผิดพลาด: เกิดข้อผิดพลาดในฐานข้อมูล";
            }
        }
    }
}

// Get list of SPENT documents for current user
$spent_docs = [];
$naf_docs = [];
try {
    $stmt_spent = $conn->prepare("
        SELECT 
            nutrition_screening.nutrition_screening_id, nutrition_screening.doc_no, nutrition_screening.screening_seq, nutrition_screening.patients_hn, nutrition_screening.admissions_an,
            patients.patients_firstname, patients.patients_lastname,
            nutrition_screening.screening_datetime, admissions.ward_id
        FROM nutrition_screening
        JOIN patients ON nutrition_screening.patients_hn = patients.patients_hn
        LEFT JOIN admissions ON nutrition_screening.admissions_an = admissions.admissions_an
        WHERE nutrition_screening.nut_id = :nut_id 
        ORDER BY nutrition_screening.screening_datetime DESC
        LIMIT 50
    ");
    $stmt_spent->execute([':nut_id' => $_SESSION['user_id']]);
    $spent_docs = $stmt_spent->fetchAll(PDO::FETCH_ASSOC);

    // Get list of NAF documents
    $stmt_naf = $conn->prepare("
        SELECT 
            nutrition_assessment.nutrition_assessment_id, nutrition_assessment.doc_no, nutrition_assessment.naf_seq, nutrition_assessment.patients_hn, nutrition_assessment.admissions_an,
            patients.patients_firstname, patients.patients_lastname,
            nutrition_assessment.assessment_datetime, admissions.ward_id
        FROM nutrition_assessment
        JOIN patients ON nutrition_assessment.patients_hn = patients.patients_hn
        LEFT JOIN admissions ON nutrition_assessment.admissions_an = admissions.admissions_an
        WHERE nutrition_assessment.nut_id = :nut_id 
        ORDER BY nutrition_assessment.assessment_datetime DESC
        LIMIT 50
    ");
    $stmt_naf->execute([':nut_id' => $_SESSION['user_id']]);
    $naf_docs = $stmt_naf->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching documents for e-sign: " . $e->getMessage());
}

// Check if current user has signature
$has_signature = false;
try {
    $stmt_sig = $conn->prepare("SELECT signature_id FROM nutrition_signature WHERE nut_id = :nut_id LIMIT 1");
    $stmt_sig->execute([':nut_id' => $_SESSION['user_id']]);
    $sig_data = $stmt_sig->fetch(PDO::FETCH_ASSOC);
    $has_signature = !empty($sig_data);
} catch (PDOException $e) {
    error_log("Error checking signature: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | โรงพยาบาลกำแพงเพชร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/eletronic_sign.css">
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
                                <span><i class="fa-solid fa-clock-rotate-left mr-2" style="width:20px;"></i>
                                    ประวัติการประเมินของฉัน</span>
                            </a>

                            <a class="dropdown-item py-2 rounded" href="electronic_sign.php">
                                <span><i class="fa-solid fa-file-signature mr-2" style="width:20px;"></i>
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
                <i class="fas fa-pen-fancy mr-2"></i>ลายเซ็นอิเล็กทรอนิกส์
            </h3>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="close" data-dismiss="alert">×</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="close" data-dismiss="alert">×</button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-pen-nib"></i> ลงนามลายเซ็น</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="signatureForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="signature_data" id="signatureData" value="">

                            <div class="form-group">
                                <label><strong>สถานะลายเซ็น</strong></label>
                                <div class="alert <?= $has_signature ? 'alert-success' : 'alert-warning' ?> mb-0">
                                    <i class="fas <?= $has_signature ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
                                    <?= $has_signature ? 'คุณได้บันทึกลายเซ็นแล้ว' : 'คุณยังไม่ได้บันทึกลายเซ็น' ?>
                                </div>
                            </div>

                            <!-- Canvas Signature Section -->
                            <div class="signature-section" style="margin-top: 20px;">
                                <label><i class="fas fa-paint-brush"></i> <strong>ลงนามในพื้นที่ด้านล่าง</strong></label>
                                <canvas id="signatureCanvas" width="500" height="180"></canvas>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCanvas()">
                                        <i class="fas fa-eraser"></i> ล้างข้อมูล
                                    </button>
                                </div>
                                <small class="form-text text-muted d-block mt-2">คลิกและลากเพื่อลงนาม (ใช้เมาส์)</small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-block font-weight-bold">
                                    <i class="fas fa-check-double"></i> บันทึกลายเซ็น
                                </button>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i> <strong>หมายเหตุ:</strong> 
                                ลายเซ็นของคุณจะปรากฏบน PDF รายงานทั้งหมด (SPENT และ NAF) โดยอัตโนมัติ
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Canvas Signature Handler
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        }

        function draw(e) {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.stroke();
        }

        function stopDrawing() {
            isDrawing = false;
        }

        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        // Signature method switch
        document.querySelectorAll('input[name="signMethod"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'canvas') {
                    document.getElementById('canvasSection').style.display = 'block';
                    document.getElementById('typedSection').style.display = 'none';
                } else {
                    document.getElementById('canvasSection').style.display = 'none';
                    document.getElementById('typedSection').style.display = 'block';
                }
            });
        });

        // Form submission
        document.getElementById('signatureForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const signatureData = canvas.toDataURL('image/png');
            if (signatureData === 'data:,') {
                alert('กรุณาลงนามในพื้นที่ก่อนบันทึก');
                return;
            }

            document.getElementById('signatureData').value = signatureData;
            this.submit();
        });

        // Logout confirmation
        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'logout.php';
                
                const token = document.createElement('input');
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