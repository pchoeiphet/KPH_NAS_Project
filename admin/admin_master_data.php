<?php
require_once '../connect_db.php';
date_default_timezone_set('Asia/Bangkok');
session_start();

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// กำหนดการตั้งค่าของตารางต่างๆ
$tables_config = [
    'symptom_problem' => [
        'label' => 'อาการ/ปัญหาทางโภชนาการ',
        'pk' => 'symptom_problem_id',
        'name_col' => 'symptom_problem_name',
        'has_score' => true,
        'score_col' => 'symptom_problem_score',
        'has_type' => true,
        'type_col' => 'symptom_problem_type',
        'type_options' => ['ปัญหาระหว่างกินอาหาร', 'ปัญหาระบบทางเดินอาหาร', 'ปัญหาทางการเคี้ยว/กลืนอาหาร']
    ],
    'disease' => [
        'label' => 'โรคประจำตัว',
        'pk' => 'disease_id',
        'name_col' => 'disease_name',
        'has_score' => true,
        'score_col' => 'disease_score',
        'has_type' => true,
        'type_col' => 'disease_type',
        'type_options' => ['โรคที่มีความรุนแรงน้อยถึงปานกลาง', 'โรคที่มีความรุนแรงมาก']
    ],
    'food_type' => [
        'label' => 'ลักษณะอาหาร',
        'pk' => 'food_type_id',
        'name_col' => 'food_type_label',
        'has_score' => true,
        'score_col' => 'food_type_score',
        'has_type' => false
    ],
    'food_amount' => [
        'label' => 'ปริมาณการกิน',
        'pk' => 'food_amount_id',
        'name_col' => 'food_amount_label',
        'has_score' => true,
        'score_col' => 'food_amount_score',
        'has_type' => false
    ],
    'patient_shape' => [
        'label' => 'รูปร่างผู้ป่วย',
        'pk' => 'patient_shape_id',
        'name_col' => 'patient_shape_label',
        'has_score' => true,
        'score_col' => 'patient_shape_score',
        'has_type' => false
    ],
    'weight_option' => [
        'label' => 'วิธีการชั่งน้ำหนัก',
        'pk' => 'weight_option_id',
        'name_col' => 'weight_option_label',
        'has_score' => true,
        'score_col' => 'weight_option_score',
        'has_type' => false
    ],
    'food_access' => [
        'label' => 'ความสามารถในการเข้าถึงอาหาร',
        'pk' => 'food_access_id',
        'name_col' => 'food_access_label',
        'has_score' => true,
        'score_col' => 'food_access_score',
        'has_type' => false
    ],
    'weight_change_4_weeks' => [
        'label' => 'การเปลี่ยนแปลงน้ำหนักใน 4 สัปดาห์',
        'pk' => 'weight_change_4_weeks_id',
        'name_col' => 'weight_change_4_weeks_label',
        'has_score' => true,
        'score_col' => 'weight_change_4_weeks_score',
        'has_type' => false
    ],
];

// PHP Logic (Add/Edit)
$status = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $table = $_POST['target_table'];
    if (!array_key_exists($table, $tables_config)) die("Invalid Table");

    $config = $tables_config[$table];
    $name_val = trim($_POST['item_name']);
    $score_val = isset($_POST['item_score']) ? floatval($_POST['item_score']) : 0;
    $type_val = isset($_POST['item_type']) ? $_POST['item_type'] : null;

    $cols = [$config['name_col']];
    $vals = [$name_val];

    if ($config['has_score']) {
        $cols[] = $config['score_col'];
        $vals[] = $score_val;
    }

    if ($config['has_type']) {
        $cols[] = $config['type_col'];
        $vals[] = $type_val;
    }

    if ($_POST['action'] == 'add') {
        $cols[] = 'is_active';
        $vals[] = 1;
        $sql = "INSERT INTO $table (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($vals), '?')) . ")";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute($vals)) $status = "success_add";
    } elseif ($_POST['action'] == 'edit') {
        $id = $_POST['item_id'];
        $set_clause = array_map(fn($c) => "$c = ?", $cols);
        $sql = "UPDATE $table SET " . implode(', ', $set_clause) . " WHERE {$config['pk']} = ?";
        $vals[] = $id;
        $stmt = $conn->prepare($sql);
        if ($stmt->execute($vals)) $status = "success_edit";
    }
}

// AJAX Toggle Active
if (isset($_GET['toggle_active'])) {
    $table = $_GET['table'];
    $id = $_GET['id'];
    $status_val = $_GET['status'];
    if (array_key_exists($table, $tables_config)) {
        $pk = $tables_config[$table]['pk'];
        $stmt = $conn->prepare("UPDATE $table SET is_active = ? WHERE $pk = ?");
        $stmt->execute([$status_val, $id]);
    }
    exit;
}

// Fetch Data
$data_store = [];
foreach ($tables_config as $tb_name => $conf) {
    $type_select = $conf['has_type'] ? ", {$conf['type_col']} as item_type" : "";
    $score_select = $conf['has_score'] ? ", {$conf['score_col']} as score" : ", 0 as score";
    $sql = "SELECT {$conf['pk']} as id, {$conf['name_col']} as name $score_select $type_select, is_active FROM $tb_name ORDER BY is_active DESC, id ASC";
    try {
        $stmt = $conn->query($sql);
        $data_store[$tb_name] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $data_store[$tb_name] = [];
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการข้อมูลมาตรฐาน - NAS ADMIN</title>
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
            background: var(--primary-color) !important;
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

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            background: white;
            padding: 25px;
        }

        .nav-pills .nav-link {
            border-radius: 50px;
            background: #eee;
            margin-right: 10px;
            color: #666;
            font-size: 0.9rem;
            padding: 8px 20px;
        }

        .nav-pills .nav-link.active {
            background-color: var(--hospital-blue) !important;
            color: white;
        }

        /* ส่วนปรับปรุงความกว้างของคอลัมน์ */
        .data-table {
            table-layout: fixed !important;
            /* บังคับใช้ความกว้างที่ระบุ */
            width: 100% !important;
        }

        .data-table th:nth-child(1),
        .data-table td:nth-child(1) {
            width: 70px !important;
            /* บังคับช่อง ID */
            text-align: center;
        }

        /* ถ้ามีคะแนน ให้คุมความกว้างช่องคะแนนด้วย */
        .col-score {
            width: 90px !important;
            text-align: center;
        }

        .data-table th:last-child,
        .data-table td:last-child {
            width: 100px !important;
            /* บังคับช่องจัดการ */
            text-align: center;
        }

        /* ให้คอลัมน์ชื่อรายการยืดหยุ่นคอลัมน์เดียว */
        .data-table td {
            vertical-align: middle !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ยกเลิก white-space เฉพาะคอลัมน์ชื่อรายการถ้าต้องการให้ขึ้นบรรทัดใหม่ได้ */
        .col-name {
            white-space: normal !important;
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
                <li class="nav-item"><a href="admin_users.php" class="nav-link"><i class="fas fa-user-cog mr-2"></i> จัดการผู้ใช้</a></li>
                <li class="nav-item"><a href="admin_master_data.php" class="nav-link active"><i class="fas fa-layer-group mr-2"></i> ข้อมูลมาตรฐาน</a></li>
            </ul>
            <ul class="nav flex-column nav-bottom">
                <li class="nav-item"><a href="../logout.php" class="nav-link text-danger" onclick="return confirm('คุณต้องการออกจากระบบ?')"><i class="fas fa-power-off mr-2"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="main-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-bold mb-1">จัดการข้อมูลมาตรฐาน (Master Data Management)</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0 small">
                            <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin</a></li>
                            <li class="breadcrumb-item active">Master Data</li>
                        </ol>
                    </nav>
                </div>
                <span class="badge badge-light p-2 text-muted border">
                    <i class="far fa-calendar-alt mr-1"></i> <?php echo date('d/m/Y H:i'); ?>
                </span>
            </div>


            <ul class="nav nav-pills mb-4" id="masterTab" role="tablist">
                <?php $is_first = true;
                foreach ($tables_config as $tb_key => $conf): ?>
                    <li class="nav-item"><a class="nav-link <?php echo $is_first ? 'active' : ''; ?>" data-toggle="pill" href="#content-<?php echo $tb_key; ?>"><?php echo $conf['label']; ?></a></li>
                <?php $is_first = false;
                endforeach; ?>
            </ul>

            <div class="card-custom">
                <div class="tab-content">
                    <?php $is_first = true;
                    foreach ($tables_config as $tb_key => $conf): ?>
                        <div class="tab-pane fade <?php echo $is_first ? 'show active' : ''; ?>" id="content-<?php echo $tb_key; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="m-0 font-weight-bold"><i class="fas fa-list-ul mr-2 text-primary"></i>รายการ<?php echo $conf['label']; ?></h5>
                                <button class="btn btn-success shadow-sm" onclick="openModal('add', '<?php echo $tb_key; ?>')"><i class="fas fa-plus mr-1"></i> เพิ่มข้อมูล</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover data-table w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>ID</th>
                                            <th class="col-name">ชื่อรายการ</th>
                                            <?php if ($conf['has_score']): ?><th class="col-score">คะแนน</th><?php endif; ?>
                                            <th>สถานะ</th>
                                            <th class="text-center">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($data_store[$tb_key])): foreach ($data_store[$tb_key] as $row): ?>
                                                <tr class="<?php echo $row['is_active'] == 0 ? 'table-secondary text-muted' : ''; ?>">
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td class="col-name">
                                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                                        <?php if ($conf['has_type']): ?><br><small class="text-info"><?php echo htmlspecialchars($row['item_type']); ?></small><?php endif; ?>
                                                    </td>
                                                    <?php if ($conf['has_score']): ?><td><span class="badge badge-primary px-3"><?php echo $row['score']; ?></span></td><?php endif; ?>
                                                    <td>
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input toggle-active" id="sw_<?php echo $tb_key . '_' . $row['id']; ?>" data-table="<?php echo $tb_key; ?>" data-id="<?php echo $row['id']; ?>" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="sw_<?php echo $tb_key . '_' . $row['id']; ?>"><?php echo $row['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?></label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-warning rounded-circle" onclick='openModal("edit", "<?php echo $tb_key; ?>", <?php echo json_encode($row); ?>)'><i class="fas fa-pen"></i></button>
                                                    </td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php $is_first = false;
                    endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="itemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius: 15px;">
                <form id="mainForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold" id="modalTitle">จัดการข้อมูล</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="form_action">
                        <input type="hidden" name="target_table" id="target_table">
                        <input type="hidden" name="item_id" id="item_id">
                        <div class="form-group">
                            <label>ชื่อรายการ <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="item_name" class="form-control" required>
                        </div>
                        <div id="type_section" class="form-group d-none">
                            <label>หมวดหมู่</label>
                            <select name="item_type" id="item_type" class="form-control"></select>
                        </div>
                        <div id="score_section" class="form-group d-none">
                            <label>คะแนน</label>
                            <input type="number" step="0.01" name="item_score" id="item_score" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary px-4">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // ตั้งค่า DataTable
            var tables = $('.data-table').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json"
                },
                "pageLength": 10,
                "autoWidth": false, // ปิดการคำนวณอัตโนมัติเพื่อให้ CSS ทำงานเต็มที่
                "ordering": false // ปิด ordering ถ้าต้องการให้ลำดับตามที่เราดึงจาก DB
            });

            // แก้ปัญหาขนาดคอลัมน์เวลาเปลี่ยน Tab (สำคัญมากสำหรับ Bootstrap 4)
            $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
                $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
            });

            $('.toggle-active').change(function() {
                const table = $(this).data('table');
                const id = $(this).data('id');
                const status = $(this).prop('checked') ? 1 : 0;
                $.get('admin_master_data.php', {
                    toggle_active: 1,
                    table: table,
                    id: id,
                    status: status
                });
            });

            <?php if ($status == "success_add"): ?> Swal.fire('สำเร็จ!', 'เพิ่มข้อมูลเรียบร้อยแล้ว', 'success');
            <?php endif; ?>
            <?php if ($status == "success_edit"): ?> Swal.fire('สำเร็จ!', 'แก้ไขข้อมูลเรียบร้อยแล้ว', 'success');
            <?php endif; ?>
        });

        const tablesConfig = <?php echo json_encode($tables_config); ?>;

        function openModal(mode, tableKey, data = null) {
            const conf = tablesConfig[tableKey];
            $('#form_action').val(mode);
            $('#target_table').val(tableKey);
            $('#modalTitle').text((mode === 'add' ? 'เพิ่ม' : 'แก้ไข') + conf.label);
            $('#item_id').val(data ? data.id : '');
            $('#item_name').val(data ? data.name : '');
            $('#item_score').val(data ? data.score : 0);

            if (conf.has_score) $('#score_section').removeClass('d-none');
            else $('#score_section').addClass('d-none');

            if (conf.has_type) {
                $('#type_section').removeClass('d-none');
                let options = '';
                conf.type_options.forEach(opt => {
                    const selected = (data && data.item_type === opt) ? 'selected' : '';
                    options += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
                $('#item_type').html(options);
            } else {
                $('#type_section').addClass('d-none');
            }
            $('#itemModal').modal('show');
        }
    </script>
</body>

</html>