<?php
session_start();
require_once 'connect_db.php'; // 1. เรียกไฟล์นี้จะได้ตัวแปร $conn มาใช้งาน

$error_msg = "";

// 2. ตรวจสอบว่ามีการกดปุ่ม Login หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $sql = "SELECT * FROM nutritionists WHERE nut_username = ? AND is_active = 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // ดึงข้อมูลแบบ Array ชื่อคอลัมน์

        // 4. ตรวจสอบผลลัพธ์
        if ($row) {
            // เจอ Username -> ตรวจสอบรหัสผ่าน
            if ($password == $row['nut_password']) {

                // --- ล็อกอินสำเร็จ ---
                $_SESSION['user_id'] = $row['nut_id'];
                $_SESSION['user_name'] = $row['nut_fullname'];
                $_SESSION['user_code'] = $row['nut_code'];
                $_SESSION['hospital'] = "Kamphaeng Phet Hospital";

                header("Location: index.php");
                exit;
            } else {
                $error_msg = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            // ไม่เจอ Username หรือ is_active ไม่ใช่ 1
            $error_msg = "ไม่พบชื่อผู้ใช้งานนี้ในระบบ";
        }
    } catch (PDOException $e) {
        $error_msg = "เกิดข้อผิดพลาดในระบบ: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>เข้าสู่ระบบ - ระบบประเมินภาวะโภชนาการ โรงพยาบาลกำแพงเพชร</title>

    <!-- Bootstrap 4 & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Google Fonts: Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #00695c;
            /* สีเขียวเข้ม แบบทางการ/การแพทย์ */
            --secondary-color: #00897b;
            --bg-color: #f0f2f5;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--bg-color);
            background-image: linear-gradient(135deg, #e0f2f1 0%, #eceff1 100%);
            /* พื้นหลังไล่สีอ่อนๆ */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-login {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            /* เงานุ่มนวล */
            overflow: hidden;
            background-color: #fff;
        }

        .login-header {
            background-color: #fff;
            padding: 30px 20px 10px 20px;
            text-align: center;
            border-bottom: 3px solid var(--primary-color);
            /* เส้นขีดสีเขียวด้านล่างหัวข้อ */
        }

        .hospital-logo {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .system-title {
            color: #333;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .hospital-name {
            color: #666;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-control {
            height: 45px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding-left: 15px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(0, 105, 92, 0.15);
            border-color: var(--secondary-color);
        }

        .input-group-text {
            background-color: #fff;
            border-right: none;
            border-color: #ced4da;
            color: #888;
        }

        /* ซ่อนเส้นขอบขวาของไอคอน เพื่อให้ดูเชื่อมกับ Input */
        .input-group-prepend .input-group-text {
            border-radius: 6px 0 0 6px;
        }

        .form-control {
            border-left: none;
            border-radius: 0 6px 6px 0;
        }

        .btn-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
            font-weight: 600;
            height: 45px;
            border-radius: 6px;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 105, 92, 0.2);
        }

        .btn-custom:hover {
            background-color: #004d40;
            border-color: #004d40;
            transform: translateY(-1px);
        }

        .footer-text {
            font-size: 0.8rem;
            color: #999;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card card-login">

                    <div class="login-header">
                        <div class="hospital-logo">
                            <img src="img/logo_kph.jpg" height="80">
                        </div>
                        <h5 class="system-title">ระบบประเมินภาวะโภชนาการ</h5>
                        <p class="hospital-name">โรงพยาบาลกำแพงเพชร</p>
                    </div>

                    <div class="card-body p-4 pt-3">

                        <?php if ($error_msg): ?>
                            <div class="alert alert-danger text-center fade show" role="alert" style="font-size: 0.9rem;">
                                <i class="fas fa-exclamation-triangle mr-2"></i><?= $error_msg ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="form-group mb-3">
                                <label class="small text-muted mb-1">ชื่อผู้ใช้งาน</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="username" placeholder="ระบุชื่อผู้ใช้งาน" required autofocus autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="small text-muted mb-1">รหัสผ่าน</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    </div>
                                    <input type="password" class="form-control" name="password" placeholder="ระบุรหัสผ่าน" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i> เข้าสู่ระบบ
                            </button>
                        </form>
                    </div>

                    <div class="card-footer text-center bg-white border-0 pb-4 pt-0">
                        <hr class="mt-0 mb-3 w-75 mx-auto">
                        <div class="footer-text">
                            &copy; <?= date("Y") ?> Kamphaeng Phet Hospital.<br>
                            กลุ่มงานโภชนศาสตร์ โรงพยาบาลกำแพงเพชร
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