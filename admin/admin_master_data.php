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

// Handle Form Submission (Add/Edit)
$msg = "";
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

    // ACTION: ADD
    if ($_POST['action'] == 'add') {
        $cols[] = 'is_active';
        $vals[] = 1;
        $sql = "INSERT INTO $table (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($vals), '?')) . ")";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute($vals)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle mr-2'></i> เพิ่มข้อมูลสำเร็จ <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        }
    }
    // ACTION: EDIT
    elseif ($_POST['action'] == 'edit') {
        $id = $_POST['item_id'];
        $set_clause = [];
        foreach ($cols as $col) {
            $set_clause[] = "$col = ?";
        }
        $sql = "UPDATE $table SET " . implode(', ', $set_clause) . " WHERE {$config['pk']} = ?";
        $vals[] = $id;

        $stmt = $conn->prepare($sql);
        if ($stmt->execute($vals)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-check-circle mr-2'></i> แก้ไขข้อมูลสำเร็จ <button type='button' class='close' data-dismiss='alert'>&times;</button></div>";
        }
    }
}

// Handle AJAX Toggle Active
if (isset($_GET['toggle_active'])) {
    $table = $_GET['table'];
    $id = $_GET['id'];
    $status = $_GET['status'];
    if (array_key_exists($table, $tables_config)) {
        $pk = $tables_config[$table]['pk'];
        $stmt = $conn->prepare("UPDATE $table SET is_active = ? WHERE $pk = ?");
        $stmt->execute([$status, $id]);
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
    <title>จัดการข้อมูลพื้นฐาน - ระบบประเมินภาวะโภชนาการ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

    <style>
        body {
            font-family: "Sarabun", sans-serif;
            background-color: #f8f9fa;
        }

        .card-stat {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
            margin-bottom: 20px;
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

        .badge-warning {
            color: #212529;
            background-color: #ffc107;
        }

        /* เพิ่มเติมสำหรับการจัดการ Tabs และ Table */
        .nav-pills .nav-link {
            background-color: white;
            color: #495057;
            border: 1px solid #dee2e6;
            margin-right: 5px;
            border-radius: 50px;
            padding: 8px 20px;
        }

        .nav-pills .nav-link.active {
            background-color: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            background-color: #fff;
        }

        .custom-control-input:checked~.custom-control-label::before {
            border-color: #28a745;
            background-color: #28a745;
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
                    <a href="admin_users.php" class="nav-link"><i class="fas fa-users mr-2"></i> จัดการผู้ใช้</a>
                </li>
                <li class="nav-item">
                    <a href="admin_master_data.php" class="nav-link active"><i class="fas fa-database mr-2"></i> ข้อมูลมาตรฐาน</a>
                </li>
                <li class="nav-item mt-auto">
                    <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt mr-2"></i> ออกจากระบบ</a>
                </li>
            </ul>
        </div>

        <div class="container-fluid p-4" style="height: 100vh; overflow-y: auto;">

            <h2 class="text-dark font-weight-bold mb-4">จัดการข้อมูลมาตรฐาน (Master Data)</h2>

            <?php echo $msg; ?>

            <ul class="nav nav-pills mb-4" id="masterTab" role="tablist">
                <?php $is_first = true;
                foreach ($tables_config as $tb_key => $conf): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_first ? 'active' : ''; ?>"
                            id="<?php echo $tb_key; ?>-tab" data-toggle="pill" href="#content-<?php echo $tb_key; ?>" role="tab"
                            onclick="setCurrentTable('<?php echo $tb_key; ?>', '<?php echo $conf['label']; ?>', <?php echo $conf['has_score'] ? 1 : 0; ?>, <?php echo $conf['has_type'] ? 1 : 0; ?>)">
                            <?php echo $conf['label']; ?>
                        </a>
                    </li>
                <?php $is_first = false;
                endforeach; ?>
            </ul>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="tab-content">
                        <?php $is_first = true;
                        foreach ($tables_config as $tb_key => $conf):
                            $col_count = 4;
                            if ($conf['has_type']) $col_count++;
                            if ($conf['has_score']) $col_count++;
                        ?>
                            <div class="tab-pane fade <?php echo $is_first ? 'show active' : ''; ?>" id="content-<?php echo $tb_key; ?>" role="tabpanel">

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="m-0 font-weight-bold text-dark"><?php echo $conf['label']; ?></h5>
                                    <button class="btn btn-primary shadow-sm rounded-pill px-4" onclick="openModal('add', '<?php echo $tb_key; ?>')">
                                        <i class="fas fa-plus mr-1"></i> เพิ่มรายการ
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover data-table w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">ID</th>
                                                <th>ชื่อรายการ</th>
                                                <?php if ($conf['has_type']): ?><th>หมวดหมู่</th><?php endif; ?>
                                                <?php if ($conf['has_score']): ?><th>คะแนน</th><?php endif; ?>
                                                <th>สถานะ</th>
                                                <th class="text-center">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data_store[$tb_key] as $row): ?>
                                                <tr class="<?php echo $row['is_active'] == 0 ? 'table-secondary text-muted' : ''; ?>">
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>

                                                    <?php if ($conf['has_type']): ?>
                                                        <td><span class="badge badge-light border"><?php echo htmlspecialchars($row['item_type']); ?></span></td>
                                                    <?php endif; ?>

                                                    <?php if ($conf['has_score']): ?>
                                                        <td><span class="badge badge-info px-3"><?php echo $row['score']; ?></span></td>
                                                    <?php endif; ?>

                                                    <td>
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input toggle-active"
                                                                id="sw_<?php echo $tb_key . '_' . $row['id']; ?>"
                                                                data-table="<?php echo $tb_key; ?>"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label small" for="sw_<?php echo $tb_key . '_' . $row['id']; ?>">
                                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-warning rounded-circle"
                                                            onclick='openModal("edit", "<?php echo $tb_key; ?>", <?php echo json_encode($row); ?>)'>
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
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
    </div>

    <div class="modal fade" id="masterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <form method="POST">
                    <div class="modal-header text-white" style="background-color: #2c3e50;">
                        <h5 class="modal-title font-weight-bold" id="modalTitle">จัดการข้อมูล</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" id="formAction">
                        <input type="hidden" name="target_table" id="targetTable">
                        <input type="hidden" name="item_id" id="itemId">

                        <div class="form-group">
                            <label class="font-weight-bold">ชื่อรายการ <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="itemName" class="form-control" required placeholder="ระบุชื่อ...">
                        </div>

                        <div class="form-group" id="typeGroup" style="display: none;">
                            <label class="font-weight-bold">ประเภท/หมวดหมู่</label>
                            <select name="item_type" id="itemType" class="form-control bg-light"></select>
                        </div>

                        <div class="form-group" id="scoreGroup">
                            <label class="font-weight-bold">คะแนน (Score)</label>
                            <input type="number" step="1" name="item_score" id="itemScore" class="form-control" placeholder="0">
                        </div>

                        <div class="alert alert-info small mt-3 mb-0">
                            <i class="fas fa-info-circle mr-1"></i> การแก้ไขข้อมูลจะไม่มีผลกับแบบประเมินที่บันทึกไปแล้ว
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" style="background-color: #2c3e50; border-color: #2c3e50;">บันทึก</button>
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
        const tableConfigs = <?php echo json_encode($tables_config); ?>;

        // ฟังก์ชันสำหรับตั้งค่า DataTables
        function initDataTable(tableElement) {
            if (!$.fn.DataTable.isDataTable(tableElement)) {
                $(tableElement).DataTable({
                    "language": {
                        "search": "ค้นหา:",
                        "paginate": {
                            "next": ">",
                            "previous": "<"
                        },
                        "zeroRecords": "ไม่พบข้อมูล",
                        "info": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                        "infoEmpty": "ไม่มีข้อมูล",
                        "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                        "lengthMenu": "แสดง _MENU_ รายการ"
                    },
                    "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                    "autoWidth": false // ป้องกันปัญหาความกว้างเพี้ยนในบาง Browser
                });
            } else {
                // ถ้ามีอยู่แล้ว ให้จัดระเบียบคอลัมน์ใหม่ (เผื่อกรณีเปลี่ยน tab แล้วย่อขยาย)
                $(tableElement).DataTable().columns.adjust();
            }
        }

        $(document).ready(function() {
            // 1. โหลด DataTable ให้กับ Tab แรกสุดที่ Active อยู่ทันทีที่เข้าหน้าเว็บ
            initDataTable($('.tab-pane.active .data-table'));

            // 2. เมื่อมีการกดเปลี่ยน Tab ให้โหลด DataTable ของ Tab นั้นๆ
            $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
                const targetTab = $(e.target).attr("href"); // id ของ tab ที่กด (เช่น #content-disease)
                const tableInTab = $(targetTab).find('.data-table');
                initDataTable(tableInTab);
            });

            // --- ส่วนจัดการ Toggle Switch ---
            $(document).on('change', '.toggle-active', function() {
                const table = $(this).data('table');
                const id = $(this).data('id');
                const status = $(this).is(':checked') ? 1 : 0;
                const row = $(this).closest('tr');
                const label = $(this).siblings('label');

                $.get('admin_master_data.php', {
                    toggle_active: 1,
                    table: table,
                    id: id,
                    status: status
                }, function() {
                    label.text(status ? 'Active' : 'Inactive');
                    status ? row.removeClass('table-secondary text-muted') : row.addClass('table-secondary text-muted');
                });
            });
        });

        // --- ส่วนจัดการ Modal ---
        function openModal(action, tableKey, data = null) {
            const config = tableConfigs[tableKey];
            $('#formAction').val(action);
            $('#targetTable').val(tableKey);
            $('#modalTitle').html((action === 'add' ? '<i class="fas fa-plus-circle"></i> เพิ่ม' : '<i class="fas fa-edit"></i> แก้ไข') + ' ' + config.label);

            config.has_score ? $('#scoreGroup').show() : $('#scoreGroup').hide();

            if (config.has_type) {
                $('#typeGroup').show();
                let options = '';
                config.type_options.forEach(opt => {
                    // เช็คค่าเดิมเพื่อ select option ให้ถูกต้อง
                    const selected = (data && data.item_type === opt) ? 'selected' : '';
                    options += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
                $('#itemType').html(options);
            } else {
                $('#typeGroup').hide();
            }

            if (action === 'edit' && data) {
                $('#itemId').val(data.id);
                $('#itemName').val(data.name);
                $('#itemScore').val(data.score);
                if (config.has_type) $('#itemType').val(data.item_type);
            } else {
                $('#itemId').val('');
                $('#itemName').val('');
                $('#itemScore').val('0');
            }
            $('#masterModal').modal('show');
        }
    </script>
</body>

</html>