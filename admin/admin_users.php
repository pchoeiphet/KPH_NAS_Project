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
    $nut_position = "นักโภชนาการ"; // ล็อกค่านี้ไว้
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // เพิ่มผู้ใช้ใหม่
    if ($_POST['action'] == 'add') {
        // เช็ค Username ซ้ำ
        $check = $conn->prepare("SELECT nut_id FROM nutritionists WHERE nut_username = ?");
        $check->execute([$nut_username]);
        if ($check->rowCount() > 0) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'><i class='fas fa-exclamation-circle mr-2'></i> Username นี้มีผู้ใช้งานแล้ว <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        } else {
            $password = password_hash($_POST['nut_password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO nutritionists (nut_username, nut_password, nut_fullname, nut_position, is_admin, is_active) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$nut_username, $password, $nut_fullname, $nut_position, $is_admin])) {
                $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle mr-2'></i> เพิ่มผู้ใช้งานสำเร็จ <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
            }
        }
    }
    // แก้ไขผู้ใช้
    elseif ($_POST['action'] == 'edit') {
        $nut_id = $_POST['nut_id'];

        // อัปเดตข้อมูลทั่วไป
        $sql = "UPDATE nutritionists SET nut_fullname = ?, nut_position = ?, is_admin = ? WHERE nut_id = ?";
        $params = [$nut_fullname, $nut_position, $is_admin, $nut_id];

        // ถ้ามีการกรอกรหัสผ่านใหม่ ให้เปลี่ยนรหัสผ่านด้วย
        if (!empty($_POST['nut_password'])) {
            $sql = "UPDATE nutritionists SET nut_fullname = ?, nut_position = ?, is_admin = ?, nut_password = ? WHERE nut_id = ?";
            $params = [$nut_fullname, $nut_position, $is_admin, password_hash($_POST['nut_password'], PASSWORD_DEFAULT), $nut_id];
        }

        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle mr-2'></i> แก้ไขข้อมูลสำเร็จ <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        }
    }
}

// จัดการ AJAX Toggle Active (เปิด/ปิดใช้งาน)
if (isset($_GET['toggle_active']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['status']; // 1 or 0
    $stmt = $conn->prepare("UPDATE nutritionists SET is_active = ? WHERE nut_id = ?");
    $stmt->execute([$status, $id]);
    exit;
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
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
    <title>จัดการผู้ใช้งาน - ระบบประเมินภาวะโภชนาการ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

    <style>
        body {
            font-family: "Sarabun", sans-serif;
            background-color: #f8f9fa;
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

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            border-top: none;
            border-bottom: 2px solid #eee;
            background-color: #fff;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
        }

        .table-secondary {
            background-color: #f1f3f5;
            color: #6c757d;
        }

        .avatar-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }

        .custom-control-input:checked~.custom-control-label::before {
            border-color: #28a745;
            background-color: #28a745;
        }

        .modal-header {
            border-radius: 5px 5px 0 0;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #34495e;
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <div class="sidebar p-3 d-flex flex-column" style="width: 250px; flex-shrink: 0;">
            <h4 class="mb-4 text-center py-2 border-bottom border-secondary">
                <i class="fas fa-user-shield"></i> Admin Panel
            </h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-home mr-2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="admin_assessments.php" class="nav-link"><i class="fas fa-clipboard-list mr-2"></i> รายงานการประเมิน</a>
                </li>
                <li class="nav-item">
                    <a href="admin_users.php" class="nav-link active"><i class="fas fa-users mr-2"></i> จัดการผู้ใช้</a>
                </li>
                <li class="nav-item">
                    <a href="admin_master_data.php" class="nav-link"><i class="fas fa-database mr-2"></i> ข้อมูลมาตรฐาน</a>
                </li>
                <li class="nav-item mt-auto">
                    <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ</a>
                </li>
            </ul>
        </div>

        <div class="container-fluid p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-dark font-weight-bold mb-0">จัดการผู้ใช้งาน</h2>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#userModal" onclick="resetForm()">
                    <i class="fas fa-plus-circle mr-1"></i> เพิ่มผู้ใช้งานใหม่
                </button>
            </div>

            <?php echo $msg; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="pl-4">#</th>
                                    <th>ชื่อ-นามสกุล</th>
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
                                    <tr class="<?php echo $u['is_active'] == 0 ? 'table-secondary' : ''; ?>">
                                        <td class="pl-4 text-muted"><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle mr-3 shadow-sm" style="background-color: <?php echo $bgColor; ?>;">
                                                    <?php echo $initial; ?>
                                                </div>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($u['nut_fullname']); ?></div>
                                            </div>
                                        </td>
                                        <td class="text-primary font-weight-bold"><?php echo htmlspecialchars($u['nut_username']); ?></td>
                                        <td>
                                            <span class="badge badge-light border px-2 py-1"><?php echo htmlspecialchars($u['nut_position']); ?></span>
                                        </td>
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
                                            <button class="btn btn-sm btn-outline-warning edit-btn"
                                                data-toggle="modal" data-target="#userModal"
                                                data-id="<?php echo $u['nut_id']; ?>"
                                                data-fullname="<?php echo htmlspecialchars($u['nut_fullname']); ?>"
                                                data-username="<?php echo htmlspecialchars($u['nut_username']); ?>"
                                                data-position="<?php echo htmlspecialchars($u['nut_position']); ?>"
                                                data-admin="<?php echo $u['is_admin']; ?>">
                                                <i class="fas fa-edit"></i>
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
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form method="POST">

                    <div class="modal-header text-white" style="background-color: #2c3e50;">
                        <h5 class="modal-title font-weight-bold" id="modalTitle">
                            <i class="fas fa-user-circle mr-2"></i> จัดการข้อมูลบุคลากร
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="nut_id" id="nut_id">

                        <h6 class="text-muted text-uppercase small font-weight-bold mb-3 border-bottom pb-2">
                            <i class="fas fa-info-circle mr-1"></i> ข้อมูลทั่วไป (General Information)
                        </h6>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="nut_fullname" id="nut_fullname" class="form-control" required placeholder="ระบุชื่อและนามสกุล">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">ตำแหน่งงาน <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" value="นักโภชนาการ" readonly style="cursor: not-allowed;">
                                <input type="hidden" name="nut_position" value="นักโภชนาการ">
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small font-weight-bold mb-3 mt-3 border-bottom pb-2">
                            <i class="fas fa-lock mr-1"></i> ข้อมูลเข้าใช้งานระบบ (Login Credentials)
                        </h6>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0"><i class="fas fa-user text-muted"></i></span>
                                    </div>
                                    <input type="text" name="nut_username" id="nut_username" class="form-control border-left-0" required placeholder="ภาษาอังกฤษ (A-Z, 0-9)">
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold">Password <small class="text-muted font-weight-normal" id="passHint"></small></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-right-0"><i class="fas fa-key text-muted"></i></span>
                                    </div>
                                    <input type="password" name="nut_password" id="nut_password" class="form-control border-left-0" placeholder="รหัสผ่าน">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-secondary border-0 mt-2 d-flex align-items-center" role="alert">
                            <div class="mr-3">
                                <i class="fas fa-shield-alt fa-2x text-dark opacity-50"></i>
                            </div>
                            <div class="w-100">
                                <h6 class="mb-1 font-weight-bold text-dark">สิทธิ์ผู้ดูแลระบบ (Administrator)</h6>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_admin" name="is_admin" value="1">
                                    <label class="custom-control-label text-muted" for="is_admin">
                                        อนุญาตให้บัญชีนี้เข้าถึงเมนูจัดการระบบ (Admin Panel) ได้
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary px-4" style="background-color: #2c3e50; border-color: #2c3e50;">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Setup DataTables
            $('#usersTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    zeroRecords: "ไม่พบข้อมูล"
                }
            });

            // Toggle Active Switch (AJAX)
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
                        row.removeClass('table-secondary');
                    } else {
                        label.text('Inactive').removeClass('text-success').addClass('text-muted');
                        row.addClass('table-secondary');
                    }
                });
            });

            // Edit Button Click
            $(document).on('click', '.edit-btn', function() {
                $('#modalTitle').html('<i class="fas fa-user-edit mr-2"></i> แก้ไขข้อมูลผู้ใช้');
                $('#formAction').val('edit');
                $('#nut_id').val($(this).data('id'));
                $('#nut_fullname').val($(this).data('fullname'));

                var username = $(this).data('username');
                $('#nut_username').val(username).prop('readonly', true).addClass('bg-light');

                // Position is locked to nutritionist, no need to set value visually as it's static in HTML

                $('#is_admin').prop('checked', $(this).data('admin') == 1);

                $('#passHint').text('(กรอกเพื่อเปลี่ยนใหม่)');
                $('#nut_password').attr('required', false).val('');
            });
        });

        // Reset Modal Form
        function resetForm() {
            $('#modalTitle').html('<i class="fas fa-user-plus mr-2"></i> เพิ่มผู้ใช้งานใหม่');
            $('#formAction').val('add');
            $('#nut_id').val('');
            $('#nut_fullname').val('');
            $('#nut_username').val('').prop('readonly', false).removeClass('bg-light');
            $('#is_admin').prop('checked', false);
            $('#passHint').text('(ตั้งค่าเริ่มต้น)');
            $('#nut_password').attr('required', true).val('');
        }
    </script>
</body>

</html>