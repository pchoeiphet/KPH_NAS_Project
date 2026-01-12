<?php
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

$doc_no = $_GET['doc_no'] ?? '';

if (empty($doc_no)) {
    die("Error: ไม่พบเลขที่เอกสาร");
}

try {
    // ดึงข้อมูลการคัดกรอง + ข้อมูลผู้ป่วย
    $sql = "
        SELECT ns.*, 
            p.patients_firstname, p.patients_lastname, p.patients_hn,
            a.bed_number, w.ward_name
        FROM nutrition_screening ns
        JOIN patients p ON ns.patients_hn = p.patients_hn
        JOIN admissions a ON ns.admissions_an = a.admissions_an
        LEFT JOIN wards w ON a.ward_id = w.ward_id
        WHERE ns.doc_no = :doc_no
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_no' => $doc_no]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) die("ไม่พบข้อมูลเอกสาร");

    // คำนวณคะแนนรวมเพื่อโชว์ผล
    $score = $data['q1_weight_loss'] + $data['q2_eat_less'] + $data['q3_bmi_abnormal'] + $data['q4_critical'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายละเอียดการคัดกรอง (View Only)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nutrition_screening_form.css">
    <style>
        /* สไตล์สำหรับโหมดอ่านอย่างเดียว */
        input[disabled],
        textarea[disabled] {
            background-color: #f8f9fa !important;
            border: 1px solid #e9ecef;
            color: #495057;
        }

        .score-radio:checked+label::before {
            border-color: #6c757d !important;
            /* เปลี่ยนสีตอน checked แบบ disabled */
        }

        .view-only-banner {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ffeeba;
            font-weight: bold;
        }

        .result-box {
            display: block !important;
            /* บังคับโชว์ผลลัพธ์ */
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-md navbar-light fixed-top navbar-custom border-bottom">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="img/logo_kph.jpg" class="brand-logo mr-2" alt="Logo" style="height: 40px;">
                <div class="brand-text">
                    <h1>ระบบประเมินภาวะโภชนาการ</h1>
                    <small>Nutrition Alert System (NAS)</small>
                </div>
            </a>
        </div>
    </nav>

    <div class="container-fluid mt-5 pt-4 pb-5 px-lg-5">

        <div class="alert alert-warning shadow-sm mt-3">
            <i class="fa-solid fa-lock mr-2"></i> คุณกำลังอยู่ในโหมด <strong>ดูประวัติย้อนหลัง (Read Only)</strong> ไม่สามารถแก้ไขข้อมูลได้
        </div>

        <div class="mb-3">
            <a href="patient_profile.php?hn=<?= $data['patients_hn'] ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="fa-solid fa-chevron-left mr-1"></i> ย้อนกลับ
            </a>
        </div>

        <div class="card form-card mb-5">
            <div class="form-header-box">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="mb-1 font-weight-bold text-dark">แบบคัดกรองภาวะโภชนาการ (SPENT)</h4>
                        <small class="text-muted">Document No.: <?= $data['doc_no'] ?></small>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-secondary p-2">History View</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-3">
                        <small class="text-muted">ผู้ป่วย:</small><br>
                        <strong><?= $data['patients_firstname'] . ' ' . $data['patients_lastname'] ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">วันที่บันทึก:</small><br>
                        <strong><?= date('d/m/Y H:i', strtotime($data['screening_datetime'])) ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">ผู้คัดกรอง:</small><br>
                        <strong><?= $data['assessor_name'] ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">ครั้งที่:</small><br>
                        <strong><?= $data['screening_seq'] ?></strong>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form>
                    <div class="form-group mb-4">
                        <label class="section-label">1. การวินิจฉัยเบื้องต้น</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['initial_diagnosis']) ?>" disabled>
                    </div>

                    <hr class="my-4" style="border-top: 1px dashed #dee2e6;">

                    <div class="mb-4">
                        <label class="section-label">2. ข้อมูลสัดส่วนร่างกาย</label>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="small text-muted">น้ำหนัก (กก.)</label>
                                <input type="text" class="form-control" value="<?= $data['present_weight'] ?>" disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">ส่วนสูง (ซม.)</label>
                                <input type="text" class="form-control" value="<?= $data['height'] ?>" disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">น้ำหนักปกติ</label>
                                <input type="text" class="form-control" value="<?= $data['normal_weight'] ?>" disabled>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">BMI</label>
                                <input type="text" class="form-control bg-light" value="<?= $data['bmi'] ?>" disabled>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">ที่มาของน้ำหนัก: <strong><?= $data['weight_method'] ?></strong></small>
                        </div>
                    </div>

                    <hr class="my-4" style="border-top: 1px dashed #dee2e6;">

                    <div class="mb-4">
                        <label class="section-label">3. แบบคัดกรอง (Screening Questions)</label>
                        <table class="table table-bordered table-screening mb-0">
                            <thead>
                                <tr>
                                    <th>คำถาม</th>
                                    <th width="15%" class="text-center">ผลการประเมิน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1. น้ำหนักลดลงโดยไม่ตั้งใจ (6 เดือน)</td>
                                    <td class="text-center font-weight-bold"><?= ($data['q1_weight_loss'] == 1) ? '<span class="text-danger">ใช่ (1)</span>' : '<span class="text-success">ไม่ใช่ (0)</span>' ?></td>
                                </tr>
                                <tr>
                                    <td>2. ได้รับอาหารน้อยกว่าปกติ (> 7 วัน)</td>
                                    <td class="text-center font-weight-bold"><?= ($data['q2_eat_less'] == 1) ? '<span class="text-danger">ใช่ (1)</span>' : '<span class="text-success">ไม่ใช่ (0)</span>' ?></td>
                                </tr>
                                <tr>
                                    <td>3. BMI < 18.5 หรือ ≥ 25.0</td>
                                    <td class="text-center font-weight-bold"><?= ($data['q3_bmi_abnormal'] == 1) ? '<span class="text-danger">ใช่ (1)</span>' : '<span class="text-success">ไม่ใช่ (0)</span>' ?></td>
                                </tr>
                                <tr>
                                    <td>4. ภาวะโรควิกฤต/กึ่งวิกฤต</td>
                                    <td class="text-center font-weight-bold"><?= ($data['q4_critical'] == 1) ? '<span class="text-danger">ใช่ (1)</span>' : '<span class="text-success">ไม่ใช่ (0)</span>' ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label class="section-label">4. หมายเหตุ</label>
                        <textarea class="form-control" rows="2" disabled><?= htmlspecialchars($data['notes']) ?></textarea>
                    </div>

                    <?php
                    $box_class = ($score >= 2) ? 'risk-high' : 'risk-normal';
                    $border_color = ($score >= 2) ? '#dc3545' : '#28a745';
                    $bg_color = ($score >= 2) ? '#fff5f5' : '#f0fff4';
                    $icon = ($score >= 2) ? 'fa-triangle-exclamation text-danger' : 'fa-circle-check text-success';
                    $title_text = ($score >= 2) ? 'มีความเสี่ยง (At Risk)' : 'ภาวะโภชนาการปกติ (Normal)';
                    $title_class = ($score >= 2) ? 'text-danger' : 'text-success';
                    ?>
                    <div class="result-box mt-4 p-4 rounded text-center" style="border: 2px dashed <?= $border_color ?>; background-color: <?= $bg_color ?>;">
                        <div class="mb-3">
                            <i class="fa-solid <?= $icon ?> fa-4x"></i>
                        </div>
                        <h3 class="font-weight-bold mb-2 <?= $title_class ?>"><?= $title_text ?></h3>
                        <p class="mb-0" style="font-size: 1.1rem;">
                            คะแนนรวม: <strong><?= $score ?></strong> คะแนน
                        </p>
                        <p class="text-muted mt-2 mb-0">สถานะบันทึก: <?= $data['screening_status'] ?></p>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>