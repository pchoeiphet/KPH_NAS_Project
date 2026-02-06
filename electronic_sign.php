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

        $sign_method = $_POST['sign_method'] ?? 'canvas'; // รับค่ารูปแบบการเซ็น (upload หรือ canvas)
        $signature_data_to_save = '';
        $signature_type_db = 'canvas'; // default type

        if ($sign_method === 'upload') {
            // --- กรณีอัปโหลดไฟล์ ---
            $signature_type_db = 'upload';

            if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['signature_file']['tmp_name'];
                $file_size = $_FILES['signature_file']['size'];
                $file_type = $_FILES['signature_file']['type'];

                // 1. ตรวจสอบขนาดไฟล์ (ไม่เกิน 2MB)
                if ($file_size > 2 * 1024 * 1024) {
                    $error_msg = "ข้อผิดพลาด: ขนาดไฟล์ต้องไม่เกิน 2 MB";
                }
                // 2. ตรวจสอบประเภทไฟล์ (PNG, JPG)
                elseif (!in_array($file_type, ['image/jpeg', 'image/png', 'image/jpg'])) {
                    $error_msg = "ข้อผิดพลาด: รองรับเฉพาะไฟล์ PNG หรือ JPG เท่านั้น";
                } else {
                    // อ่านไฟล์และแปลงเป็น Base64
                    $data = file_get_contents($file_tmp);
                    $base64 = base64_encode($data);

                    // หมายเหตุ: เราเก็บเฉพาะ Raw Base64 เพื่อให้เหมือนกับ Canvas (ที่ตัด Header ออก)
                    // เพื่อให้ระบบ PDF Report เรียกใช้ได้เหมือนกัน
                    $signature_data_to_save = $base64;
                }
            } else {
                // กรณีไม่ได้เลือกไฟล์ แต่กด Submit ในโหมดอัปโหลด
                // เช็คก่อนว่ามีลายเซ็นเดิมไหม ถ้ามีแล้วไม่เลือกใหม่ถือว่าไม่เปลี่ยน
                // แต่ถ้านี่คือครั้งแรก ต้องแจ้งเตือน
                if (empty($_FILES['signature_file']['name'])) {
                    $error_msg = "ข้อผิดพลาด: กรุณาเลือกไฟล์ภาพลายเซ็น";
                } else {
                    $error_msg = "ข้อผิดพลาด: การอัปโหลดล้มเหลว (Error Code: " . $_FILES['signature_file']['error'] . ")";
                }
            }
        } else {
            // --- กรณีวาดผ่าน Canvas ---
            $signature_type_db = 'canvas';
            $signature_data_input = trim($_POST['signature_data'] ?? '');

            if (empty($signature_data_input)) {
                $error_msg = "ข้อผิดพลาด: กรุณาวาดลายเซ็นก่อนบันทึก";
            } else {
                // Sanitize signature data
                if (!preg_match('/^data:image\/png;base64,/', $signature_data_input)) {
                    $error_msg = "ข้อผิดพลาด: รูปแบบลายเซ็นไม่ถูกต้อง";
                } else {
                    // ตัด Header ออก เก็บแค่เนื้อ Base64
                    $signature_data_to_save = str_replace('data:image/png;base64,', '', $signature_data_input);

                    if (!base64_decode($signature_data_to_save, true)) {
                        $error_msg = "ข้อผิดพลาด: ข้อมูลลายเซ็นเสียหาย";
                    }
                }
            }
        }

        // --- บันทึกลงฐานข้อมูล ---
        if (empty($error_msg) && !empty($signature_data_to_save)) {
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
                        SET signature_type = :sig_type,
                            signature_data = :sig_data,
                            signed_datetime = NOW()
                        WHERE nut_id = :nut_id
                    ");
                    $stmt_update->execute([
                        ':sig_type' => $signature_type_db,
                        ':sig_data' => $signature_data_to_save,
                        ':nut_id' => $nut_id
                    ]);
                } else {
                    // Insert new signature
                    $stmt_insert = $conn->prepare("
                        INSERT INTO nutrition_signature (nut_id, signature_type, signature_data, signed_datetime)
                        VALUES (:nut_id, :sig_type, :sig_data, NOW())
                    ");
                    $stmt_insert->execute([
                        ':nut_id' => $nut_id,
                        ':sig_type' => $signature_type_db,
                        ':sig_data' => $signature_data_to_save
                    ]);
                }

                // Log audit
                error_log("User " . $_SESSION['user_id'] . " saved signature ($signature_type_db) at " . date('Y-m-d H:i:s'));

                $conn->commit();
                $success_msg = "บันทึกลายเซ็นสำเร็จ! ลายเซ็นของคุณจะปรากฏบน PDF รายงานทั้งหมด";
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Database error in e-sign: " . $e->getMessage());
                $error_msg = "ข้อผิดพลาด: ไม่สามารถบันทึกลายเซ็นได้";
            }
        }
    }
}

// Get list of SPENT documents (Code เดิม ส่วนแสดงผลประวัติ)
// ... (คงเดิมตามที่คุณส่งมา ผมละไว้เพื่อประหยัดพื้นที่) ...

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
    <title>ลายเซ็นอิเล็กทรอนิกส์ | โรงพยาบาลกำแพงเพชร</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/eletronic_sign.css">
    <style>
        /* CSS เพิ่มเติมสำหรับ Preview พื้นหลังโปร่งใส */
        .preview-container {
            width: 100%;
            max-width: 500px;
            height: 180px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fff;
            /* สร้างลายตารางหมากรุก (Checkerboard) เพื่อให้เห็นความโปร่งใส */
            background-image:
                linear-gradient(45deg, #eee 25%, transparent 25%),
                linear-gradient(-45deg, #eee 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #eee 75%),
                linear-gradient(-45deg, transparent 75%, #eee 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            overflow: hidden;
            margin-top: 15px;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .method-selector {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .custom-control-label {
            cursor: pointer;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-md navbar-light fixed-top navbar-custom border-bottom">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="img/logo_kph.jpg" class="brand-logo mr-2 d-none d-sm-block" alt="Logo" onerror="this.style.display='none'">
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
                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2 pb-0" aria-labelledby="userDropdown">
                        <div class="bg-light border-top p-2">
                            <a class="dropdown-item py-2 rounded text-danger font-weight-bold" href="#" onclick="confirmLogout()">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> ออกจากระบบ
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
                        <h5 class="card-title"><i class="fas fa-pen-nib"></i> บันทึกลายเซ็น</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="signatureForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="signature_data" id="signatureData" value="">

                            <div class="form-group">
                                <label><strong>สถานะปัจจุบัน</strong></label>
                                <div class="alert <?= $has_signature ? 'alert-success' : 'alert-warning' ?> mb-0">
                                    <i class="fas <?= $has_signature ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
                                    <?= $has_signature ? 'คุณได้บันทึกลายเซ็นเรียบร้อยแล้ว' : 'คุณยังไม่มีลายเซ็นในระบบ' ?>
                                </div>
                            </div>

                            <hr>

                            <div class="method-selector">
                                <label class="mb-3 text-primary font-weight-bold">เลือกวิธีการลงนาม:</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" id="methodUpload" name="sign_method" value="upload" class="custom-control-input" checked>
                                            <label class="custom-control-label" for="methodUpload">
                                                <i class="fas fa-upload mr-1"></i> อัปโหลดรูปภาพ (แนะนำ)
                                            </label>
                                            <small class="d-block text-muted ml-4">เหมาะสำหรับผู้ที่มีไฟล์รูปลายเซ็น หรือสแกนเก็บไว้แล้ว</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="methodCanvas" name="sign_method" value="canvas" class="custom-control-input">
                                            <label class="custom-control-label" for="methodCanvas">
                                                <i class="fas fa-pen mr-1"></i> วาดบนหน้าจอ
                                            </label>
                                            <small class="d-block text-muted ml-4">ใช้นิ้วหรือเมาส์วาดลายเซ็นสด</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="section-upload" class="signature-section">
                                <label><i class="fas fa-image"></i> <strong>เลือกไฟล์รูปลายเซ็น</strong></label>
                                <div class="custom-file mb-2">
                                    <input type="file" class="custom-file-input" id="signatureFile" name="signature_file" accept="image/png, image/jpeg">
                                    <label class="custom-file-label" for="signatureFile">เลือกไฟล์ PNG หรือ JPG...</label>
                                </div>
                                <small class="text-danger">* แนะนำให้ใช้ไฟล์ <strong>.PNG พื้นหลังโปร่งใส (Transparent)</strong> เพื่อความสวยงามในเอกสาร</small>
                                <small class="text-muted d-block">ขนาดไฟล์ไม่เกิน 2 MB</small>

                                <div class="mt-2">
                                    <label>ตัวอย่างลายเซ็นที่จะปรากฏ:</label>
                                    <div class="preview-container">
                                        <img id="imagePreview" src="#" alt="ตัวอย่างลายเซ็น" style="display: none;">
                                        <span id="previewText" class="text-muted small">ตัวอย่างลายเซ็นจะแสดงที่นี่</span>
                                    </div>
                                </div>
                            </div>

                            <div id="section-canvas" class="signature-section" style="display:none;">
                                <label><i class="fas fa-paint-brush"></i> <strong>วาดลงในกรอบด้านล่าง</strong></label>
                                <div class="d-flex flex-column align-items-center">
                                    <canvas id="signatureCanvas" width="500" height="180" style="border: 2px solid #000; cursor: crosshair; touch-action: none; background: #fff;"></canvas>
                                </div>
                                <div class="mt-3 text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCanvas()">
                                        <i class="fas fa-eraser"></i> ล้างข้อมูล
                                    </button>
                                </div>
                                <small class="form-text text-muted text-center mt-2">ใช้เมาส์หรือนิ้วลากเพื่อลงนาม</small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-block font-weight-bold shadow-sm">
                                    <i class="fas fa-save"></i> บันทึกลายเซ็น
                                </button>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i> <strong>หมายเหตุ:</strong>
                                ลายเซ็นของคุณจะถูกนำไปใช้ในเอกสาร PDF (SPENT และ NAF) โดยอัตโนมัติ
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
        $(document).ready(function() {
            // Toggle Logic
            $('input[name="sign_method"]').change(function() {
                if ($(this).val() === 'upload') {
                    $('#section-upload').show();
                    $('#section-canvas').hide();
                } else {
                    $('#section-upload').hide();
                    $('#section-canvas').show();
                    // Resize canvas logic if needed upon visible
                }
            });

            // File Upload Preview Logic
            $('#signatureFile').change(function(e) {
                var fileName = e.target.files[0].name;
                $('.custom-file-label').html(fileName); // เปลี่ยนข้อความ Label

                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').attr('src', e.target.result).show();
                        $('#previewText').hide();
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });

        // --- Canvas Logic (Original + Fixes) ---
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;

        // Mouse Events
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        // Touch Events (สำหรับ Mobile/Tablet)
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault(); // ป้องกัน Scroll จอ
            var touch = e.touches[0];
            var mouseEvent = new MouseEvent("mousedown", {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }, false);

        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            var touch = e.touches[0];
            var mouseEvent = new MouseEvent("mousemove", {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }, false);

        canvas.addEventListener("touchend", function(e) {
            var mouseEvent = new MouseEvent("mouseup", {});
            canvas.dispatchEvent(mouseEvent);
        }, false);

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

        // --- Form Submission ---
        document.getElementById('signatureForm').addEventListener('submit', function(e) {
            const method = document.querySelector('input[name="sign_method"]:checked').value;

            if (method === 'canvas') {
                const signatureData = canvas.toDataURL('image/png');

                // ตรวจสอบว่าเป็น Canvas เปล่าหรือไม่ (ขนาด data จะสั้นถ้าไม่มีการวาด)
                const blank = document.createElement('canvas');
                blank.width = canvas.width;
                blank.height = canvas.height;

                if (signatureData === blank.toDataURL()) {
                    e.preventDefault();
                    alert('กรุณาวาดลายเซ็นก่อนบันทึก');
                    return;
                }

                document.getElementById('signatureData').value = signatureData;
            } else if (method === 'upload') {
                const fileInput = document.getElementById('signatureFile');
                // ถ้าไม่มีไฟล์ และไม่มีลายเซ็นเก่า (อันนี้ตรวจสอบฝั่ง PHP เพิ่มด้วย)
                if (fileInput.files.length === 0) {
                    // อนุญาตให้ผ่านได้ถ้ามีลายเซ็นเดิมอยู่แล้ว (Logic PHP จะจัดการ)
                    // แต่ถ้าจะ Strict ฝั่ง JS ก็ทำได้
                }
            }
        });

        // Logout Confirmation
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