<?php
require_once '../connect_db.php';
date_default_timezone_set('Asia/Bangkok');
session_start();

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// จัดการรับค่า Form (เพิ่ม / แก้ไข)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    $nut_username = trim($_POST['nut_username']);
    $nut_fullname = trim($_POST['nut_fullname']);
    $nut_position = trim($_POST['nut_position']);
    $nut_email    = trim($_POST['nut_email']);
    $nut_phone    = trim($_POST['nut_phone']); // ใช้ nut_phone ตามโครงสร้าง DB
    $is_admin     = isset($_POST['is_admin']) ? 1 : 0;

    // เพิ่มผู้ใช้ใหม่
    if ($_POST['action'] == 'add') {
        $check = $conn->prepare("SELECT nut_id FROM nutritionists WHERE nut_username = ?");
        $check->execute([$nut_username]);
        if ($check->rowCount() > 0) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'><i class='fas fa-exclamation-circle mr-2'></i> Username นี้มีผู้ใช้งานแล้ว <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        } else {
            $password = password_hash($_POST['nut_password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO nutritionists (nut_username, nut_password, nut_fullname, nut_email, nut_phone, nut_position, is_admin, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$nut_username, $password, $nut_fullname, $nut_email, $nut_phone, $nut_position, $is_admin])) {
                $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle mr-2'></i> เพิ่มผู้ใช้งานสำเร็จ <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
            }
        }
    }
    // แก้ไขผู้ใช้
    elseif ($_POST['action'] == 'edit') {
        $nut_id = $_POST['nut_id'];

        if (!empty($_POST['nut_password'])) {
            $sql = "UPDATE nutritionists SET nut_fullname = ?, nut_email = ?, nut_phone = ?, nut_position = ?, is_admin = ?, nut_password = ? WHERE nut_id = ?";
            $params = [$nut_fullname, $nut_email, $nut_phone, $nut_position, $is_admin, password_hash($_POST['nut_password'], PASSWORD_DEFAULT), $nut_id];
        } else {
            $sql = "UPDATE nutritionists SET nut_fullname = ?, nut_email = ?, nut_phone = ?, nut_position = ?, is_admin = ? WHERE nut_id = ?";
            $params = [$nut_fullname, $nut_email, $nut_phone, $nut_position, $is_admin, $nut_id];
        }

        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle mr-2'></i> แก้ไขข้อมูลสำเร็จ <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        }
    }
}

// AJAX Toggle Active
if (isset($_GET['toggle_active']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $stmt = $conn->prepare("UPDATE nutritionists SET is_active = ? WHERE nut_id = ?");
    $stmt->execute([$status, $id]);
    exit;
}

$stmt = $conn->query("SELECT * FROM nutritionists ORDER BY is_active DESC, nut_id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getAvatarColor($char)
{
    $colors = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e', '#e67e22', '#e74c3c', '#95a5a6'];
    $index = ord(strtoupper($char)) % count($colors);
    return $colors[$index];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ใช้งาน - NAS ADMIN</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
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
            z-index: 1000;
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
            background: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .nav-bottom {
            margin-top: auto;
            margin-bottom: 20px;
        }

        .main-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .avatar-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .custom-switch .custom-control-label::before {
            height: 1.25rem;
            width: 2.25rem;
            border-radius: 1rem;
        }

        .custom-switch .custom-control-label::after {
            width: calc(1.25rem - 4px);
            height: calc(1.25rem - 4px);
            border-radius: 1rem;
        }

        .custom-switch .custom-control-input:checked~.custom-control-label::after {
            transform: translateX(1rem);
        }

        .table td {
            vertical-align: middle;
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
                <li class="nav-item"><a href="admin_screenings.php" class="nav-link"><i class="fas fa-search mr-2"></i> รายงานการคัดกรอง</a></li>
                <li class="nav-item"><a href="admin_assessments.php" class="nav-link"><i class="fas fa-user-check mr-2"></i> รายงานการประเมิน</a></li>
                <li class="nav-item"><a href="admin_users.php" class="nav-link active"><i class="fas fa-user-cog mr-2"></i> จัดการผู้ใช้</a></li>
                <li class="nav-item"><a href="admin_master_data.php" class="nav-link"><i class="fas fa-layer-group mr-2"></i> ข้อมูลมาตรฐาน</a></li>
            </ul>
            <ul class="nav flex-column nav-bottom">
                <li class="nav-item"><a href="../logout.php" class="nav-link text-danger" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')"><i class="fas fa-power-off mr-2"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="main-header d-flex justify-content-between align-items-center shadow-sm">
                <div>
                    <h3 class="font-weight-bold mb-1 text-dark">จัดการผู้ใช้งาน (Users Management)</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb small">
                            <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin</a></li>
                            <li class="breadcrumb-item active">User Management System</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-right mr-3 d-none d-sm-block">
                        <span class="badge badge-light p-2 text-muted border">
                            <i class="far fa-calendar-alt mr-1 text-primary"></i>
                            <?php echo date('d/m/Y'); ?>
                            <i class="far fa-clock ml-2 mr-1 text-primary"></i>
                            <span id="liveTime"><?php echo date('H:i'); ?></span>
                        </span>
                    </div>

                    <button class="btn btn-primary shadow-sm px-4" data-toggle="modal" data-target="#userModal" onclick="resetForm()">
                        <i class="fas fa-plus mr-2"></i> เพิ่มผู้ใช้งาน
                    </button>
                </div>
            </div>

            <?php echo $msg; ?>


            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        <table id="usersTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ชื่อ-นามสกุล / ติดต่อ</th>
                                    <th>Username</th>
                                    <th>ตำแหน่ง</th>
                                    <th>สิทธิ์</th>
                                    <th>สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $index => $u):
                                    $initial = mb_substr($u['nut_fullname'], 0, 1);
                                    $bgColor = getAvatarColor($initial);
                                ?>
                                    <tr class="<?php echo $u['is_active'] == 0 ? 'bg-light text-muted' : ''; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle mr-3 shadow-sm" style="background-color: <?php echo $bgColor; ?>;">
                                                    <?php echo $initial; ?>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($u['nut_fullname']); ?></div>
                                                    <div class="small text-muted">
                                                        <i class="fas fa-envelope fa-xs mr-1"></i><?php echo htmlspecialchars($u['nut_email'] ?: '-'); ?> |
                                                        <i class="fas fa-phone fa-xs mr-1"></i><?php echo htmlspecialchars($u['nut_phone'] ?: '-'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-primary font-weight-bold"><?php echo htmlspecialchars($u['nut_username']); ?></td>
                                        <td><span class="badge badge-light border"><?php echo htmlspecialchars($u['nut_position']); ?></span></td>
                                        <td>
                                            <?php if ($u['is_admin']): ?>
                                                <span class="badge badge-warning text-dark"><i class="fas fa-crown mr-1"></i> Admin</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input toggle-active"
                                                    id="switch_<?php echo $u['nut_id']; ?>"
                                                    data-id="<?php echo $u['nut_id']; ?>"
                                                    <?php echo $u['is_active'] ? 'checked' : ''; ?>>
                                                <label class="custom-control-label small font-weight-bold <?php echo $u['is_active'] ? 'text-success' : 'text-muted'; ?>" for="switch_<?php echo $u['nut_id']; ?>">
                                                    <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary edit-btn"
                                                data-toggle="modal" data-target="#userModal"
                                                data-id="<?php echo $u['nut_id']; ?>"
                                                data-fullname="<?php echo htmlspecialchars($u['nut_fullname']); ?>"
                                                data-username="<?php echo htmlspecialchars($u['nut_username']); ?>"
                                                data-email="<?php echo htmlspecialchars($u['nut_email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($u['nut_phone']); ?>"
                                                data-position="<?php echo htmlspecialchars($u['nut_position']); ?>"
                                                data-admin="<?php echo $u['is_admin']; ?>">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <form method="POST">
                    <div class="modal-header bg-dark text-white border-0 py-4">
                        <h5 class="modal-title font-weight-bold" id="modalTitle">จัดการผู้ใช้งาน</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="nut_id" id="nut_id">

                        <div class="form-group">
                            <label class="font-weight-bold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" name="nut_fullname" id="nut_fullname" class="form-control" required placeholder="เช่น นายสมชาย ใจดี">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">อีเมล</label>
                                <input type="email" name="nut_email" id="nut_email" class="form-control" placeholder="example@mail.com">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">เบอร์โทรศัพท์</label>
                                <input type="text" name="nut_phone" id="nut_phone" class="form-control" placeholder="08x-xxxxxxx">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">ตำแหน่งงาน <span class="text-danger">*</span></label>
                            <select name="nut_position" id="nut_position" class="form-control" required>
                                <option value="">-- เลือกตำแหน่ง --</option>
                                <option value="นักโภชนาการ">นักโภชนาการ</option>
                                <option value="โภชนากร">โภชนากร</option>
                                <option value="พยาบาลวิชาชีพ">พยาบาลวิชาชีพ</option>
                                <option value="เจ้าหน้าที่">เจ้าหน้าที่</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Username <span class="text-danger">*</span></label>
                                <input type="text" name="nut_username" id="nut_username" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Password <small class="text-muted" id="passHint"></small></label>
                                <input type="password" name="nut_password" id="nut_password" class="form-control">
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded-lg border mt-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_admin" name="is_admin" value="1">
                                <label class="custom-control-label font-weight-bold" for="is_admin">กำหนดสิทธิ์เป็นผู้ดูแลระบบ (Admin)</label>
                                <p class="small text-muted mb-0 mt-1">สามารถเข้าถึงหน้าจัดการผู้ใช้และข้อมูลมาตรฐานได้</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4">
                        <button type="button" class="btn btn-light px-4" data-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary px-5 shadow">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    paginate: {
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                }
            });

            $(document).on('change', '.toggle-active', function() {
                var userId = $(this).data('id');
                var status = $(this).is(':checked') ? 1 : 0;
                var label = $(this).siblings('label');
                var row = $(this).closest('tr');

                $.get('admin_users.php', {
                    toggle_active: 1,
                    id: userId,
                    status: status
                }, function() {
                    if (status) {
                        label.text('Active').removeClass('text-muted').addClass('text-success');
                        row.removeClass('bg-light text-muted');
                    } else {
                        label.text('Inactive').removeClass('text-success').addClass('text-muted');
                        row.addClass('bg-light text-muted');
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                $('#modalTitle').text('แก้ไขข้อมูลผู้ใช้');
                $('#formAction').val('edit');
                $('#nut_id').val($(this).data('id'));
                $('#nut_fullname').val($(this).data('fullname'));
                $('#nut_email').val($(this).data('email'));
                $('#nut_phone').val($(this).data('phone'));
                $('#nut_username').val($(this).data('username')).prop('readonly', true).addClass('bg-light');
                $('#nut_position').val($(this).data('position'));
                $('#is_admin').prop('checked', $(this).data('admin') == 1);
                $('#passHint').text('(กรอกเพื่อเปลี่ยนใหม่)');
                $('#nut_password').attr('required', false).val('');
            });
        });

        function resetForm() {
            $('#modalTitle').text('เพิ่มผู้ใช้งานใหม่');
            $('#formAction').val('add');
            $('#nut_id').val('');
            $('#nut_fullname').val('');
            $('#nut_email').val('');
            $('#nut_phone').val('');
            $('#nut_username').val('').prop('readonly', false).removeClass('bg-light');
            $('#nut_position').val('');
            $('#is_admin').prop('checked', false);
            $('#passHint').text('(รหัสผ่านเริ่มต้น)');
            $('#nut_password').attr('required', true).val('');
        }
    </script>
</body>

</html>