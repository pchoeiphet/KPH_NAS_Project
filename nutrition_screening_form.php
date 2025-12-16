<?php
require_once 'connect.php';

function h($string)
{
    return htmlspecialchars($string ?? "", ENT_QUOTES, 'UTF-8');
}

$hn = isset($_GET['hn']) ? $_GET['hn'] : '6603321'; // Default test HN

try {
    $sql = "SELECT * FROM patients WHERE patients_hn = :hn";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':hn' => $hn]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("<div style='text-align:center; margin-top:50px; color:red;'><h3>ไม่พบข้อมูลผู้ป่วย HN: " . h($hn) . "</h3></div>");
    }

    $dob = new DateTime($patient['patients_dob']);
    $now = new DateTime();
    $age = $now->diff($dob);
    $age_text = $age->y . " ปี " . $age->m . " เดือน " . $age->d . " วัน";
    $admit_date = date('d/m/Y', strtotime($patient['admit_date']));
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาด SQL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบคัดกรองภาวะโภชนาการ (Screening)</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/nutrition_alert_form.css">
</head>

<style>
    :root {
        --primary-color: #2196f3;
        --primary-dark: #1565c0;
        --text-color: #263238;
        --text-muted: #546e7a;
        --bg-light: #f4f7f6;
        --white: #ffffff;
        --border-color: #cfd8dc;
        --input-border: #cccccc;

        --risk-high-bg: #ffebee;
        --risk-high-text: #c62828;
        --risk-med-bg: #fff8e1;
        --risk-med-text: #f57c00;
        --risk-low-bg: #e8f5e9;
        --risk-low-text: #2e7d32;
    }

    body {
        font-family: "Sarabun", sans-serif;
        background-color: var(--bg-light);
        margin: 0;
        padding: 0;
        color: var(--text-color);
    }

    /* Navbar & Layout */
    .navbar {
        background-color: var(--white);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        padding: 0 20px;
        height: 70px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: fixed;
        top: 0;
        width: 100%;
        box-sizing: border-box;
        z-index: 1000;
        border-bottom: 1px solid var(--border-color);
    }

    .navbar-brand {
        text-decoration: none;
        display: flex;
        align-items: center;
        color: inherit;
    }

    .brand-logo {
        width: 45px;
        height: 45px;
        object-fit: contain;
        margin-right: 12px;
    }

    .brand-text {
        display: flex;
        flex-direction: column;
        justify-content: center;
        line-height: 1.2;
    }

    .brand-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0d47a1;
    }

    .brand-subtitle {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 6px;
        transition: background 0.2s;
        border: 1px solid transparent;
    }

    .user-profile:hover {
        background-color: #f5f5f5;
        border-color: #e0e0e0;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        background-color: #e3f2fd;
        color: #0d47a1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .user-info {
        text-align: left;
        display: none;
    }

    .user-name {
        font-weight: 600;
        font-size: 0.9rem;
        display: block;
        color: var(--text-color);
    }

    .user-role {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    @media (min-width: 600px) {
        .user-info {
            display: block;
        }
    }

    .dropdown-menu {
        position: absolute;
        top: 110%;
        right: 20px;
        background-color: white;
        width: 210px;
        border-radius: 6px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #eee;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
    }

    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-user-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        background: #fcfcfc;
        border-radius: 6px 6px 0 0;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        text-decoration: none;
        color: #444;
        font-size: 0.9rem;
        transition: background 0.1s;
    }

    .dropdown-item:hover {
        background-color: #f0f7ff;
        color: var(--primary-color);
    }

    .dropdown-item i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
        color: #78909c;
    }

    .text-danger {
        color: #d32f2f !important;
    }

    .patient-banner {
        margin-top: 70px;
        background-color: #fff;
        border-bottom: 1px solid var(--border-color);
        padding: 24px 20px;
    }

    .patient-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        gap: 25px;
        align-items: flex-start;
    }

    .patient-icon-box {
        flex-shrink: 0;
        width: 120px;
        height: 140px;
        background-color: #e3f2fd;
        color: #0d47a1;
        border-radius: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        border: 1px solid #bbdefb;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .patient-details {
        flex-grow: 1;
        width: 100%;
    }

    .section-header {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0d47a1;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #eef2f5;
        padding-bottom: 5px;
        display: inline-block;
    }

    .patient-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 15px 25px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-label {
        color: var(--text-muted);
        font-size: 0.75rem;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .info-value {
        color: var(--text-color);
        font-weight: 500;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .action-bar {
        max-width: 1200px;
        margin: 20px auto 10px auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .page-title-inline {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0d47a1;
        padding-left: 20px;
        border-left: 2px solid #ddd;
        line-height: 1;
        display: flex;
        align-items: center;
        height: 30px;
    }

    /* Form Card */
    .form-card {
        background: white;
        padding: 40px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        margin-bottom: 30px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 20px;
        margin-bottom: 20px;
        align-items: end;
    }

    .col-2 {
        grid-column: span 2;
    }

    .col-3 {
        grid-column: span 3;
    }

    .col-4 {
        grid-column: span 4;
    }

    .col-5 {
        grid-column: span 5;
    }

    .col-6 {
        grid-column: span 6;
    }

    .col-7 {
        grid-column: span 7;
    }

    .col-8 {
        grid-column: span 8;
    }

    .col-12 {
        grid-column: span 12;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        height: 100%;
        justify-content: flex-start;
    }

    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 6px;
    }

    .form-control {
        padding: 8px 12px;
        border: 1px solid var(--input-border);
        border-radius: 4px;
        font-family: "Sarabun", sans-serif;
        font-size: 1rem;
        color: #333;
        transition: border-color 0.2s;
        box-sizing: border-box;
        height: 48px;
    }

    textarea.form-control {
        height: auto;
        min-height: 120px;
        padding-top: 12px;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .form-control:disabled,
    .table-radio:disabled {
        background-color: #f0f0f0;
        color: #777;
        cursor: not-allowed;
        border-color: #eee;
    }

    .form-control[readonly] {
        background-color: #f9f9f9;
        color: #666;
        cursor: default;
    }

    .bmi-box {
        background-color: #f1f8e9;
        border: 1px solid #c5e1a5;
        color: #2e7d32;
        font-weight: bold;
        text-align: center;
    }

    .score-box {
        background-color: #fff8e1;
        border: 1px solid #ffecb3;
        color: #f57c00;
        font-weight: bold;
        text-align: center;
    }

    /* Radio Groups */
    .radio-group-container {
        display: flex;
        align-items: center;
        height: auto;
        min-height: 48px;
        background-color: transparent;
        padding: 0;
    }

    .radio-wrapper {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
    }

    .radio-wrapper-compact {
        display: flex;
        gap: 12px;
        flex-wrap: nowrap;
        align-items: center;
        white-space: nowrap;
    }

    .radio-label {
        display: inline-flex;
        align-items: center;
        font-size: 0.95rem;
        cursor: pointer;
        color: #444;
        margin: 0;
    }

    .radio-label input {
        margin-right: 6px;
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
    }

    .input-inline-small {
        width: 220px !important;
        margin-left: 5px;
        display: inline-block;
        padding: 4px 8px;
        font-size: 0.9rem;
        height: 36px;
    }

    /* Screening Table */
    .screening-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .screening-table th {
        background-color: #e3f2fd;
        color: #0d47a1;
        font-weight: 600;
        padding: 12px;
        text-align: left;
        border: 1px solid #e0e0e0;
        font-size: 0.95rem;
    }

    .screening-table th.text-center {
        text-align: center;
    }

    .screening-table td {
        padding: 12px;
        border: 1px solid #e0e0e0;
        vertical-align: middle;
        color: #333;
        font-size: 0.95rem;
    }

    .screening-table td.text-center {
        text-align: center;
    }

    .table-radio {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary-color);
    }

    .screening-row:hover {
        background-color: #f9f9f9;
    }

    /* Result & High Risk Options (Screening) */
    .result-box {
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
        text-align: center;
        border: 1px solid transparent;
        display: none;
        animation: fadeIn 0.5s;
    }

    .risk-low {
        background-color: var(--risk-low-bg);
        border-color: #c8e6c9;
        color: var(--risk-low-text);
    }

    .risk-high {
        background-color: var(--risk-high-bg);
        border-color: #ffcdd2;
        color: var(--risk-high-text);
    }

    .result-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    #highRiskOptions {
        display: none;
        margin-top: 15px;
        text-align: center;
        animation: fadeIn 0.5s;
    }

    .options-container {
        display: inline-flex;
        gap: 15px;
        margin-top: 10px;
    }

    #assessmentWrapper {
        display: none;
        margin-top: 40px;
        animation: slideDown 0.5s ease-out;
    }

    .assessment-section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0d47a1;
        margin-bottom: 15px;
        padding-left: 10px;
        border-left: 4px solid var(--primary-color);
        line-height: 1.2;
    }

    .assessment-alert-header {
        background-color: #fff3e0;
        padding: 15px;
        border-radius: 6px;
        border: 1px solid #ffe0b2;
        color: #e65100;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }


    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Print Styles for PDF */
    @media print {
        body {
            background-color: white !important;
            padding-top: 0 !important;
        }

        .navbar,
        .action-bar,
        .btn-back,
        .form-row:last-child,
        /* Hide the final button row */
        #saveScreeningRow,
        #resultBox,
        .reference-note {
            display: none !important;
        }

        .patient-banner {
            margin-top: 0 !important;
            padding-top: 15px !important;
            padding-bottom: 15px !important;
            border-bottom: 3px double #0d47a1;
        }

        .main-content {
            padding: 0 10px 0 10px !important;
            max-width: 100% !important;
        }

        .form-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
    }

    @media (max-width: 992px) {

        .col-5,
        .col-7 {
            grid-column: span 12;
        }

        .radio-wrapper-compact {
            flex-wrap: wrap;
            white-space: normal;
        }
    }

    @media (max-width: 768px) {
        .form-card {
            padding: 20px;
        }

        .col-2,
        .col-3,
        .col-4,
        .col-6,
        .col-8,
        .col-12 {
            grid-column: span 12;
        }

        .summary-card-container {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<body>
    <nav class="navbar">
        <div class="navbar-left">
            <a class="navbar-brand" href="index.php">
                <img src="img/logo_kph.jpg" class="brand-logo" alt="KPH Logo" onerror="this.style.display='none'">
                <div class="brand-text">
                    <span class="brand-title">ระบบประเมินภาวะโภชนาการ</span>
                    <span class="brand-subtitle">Nutrition Alert Form - โรงพยาบาลกำแพงเพชร</span>
                </div>
            </a>
        </div>
        <div class="navbar-right">
            <div class="user-profile" id="userDropdownTrigger">
                <div class="user-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="user-info">
                    <span class="user-name">เพชรลดา เชยเพ็ชร</span>
                    <span class="user-role">นักโภชนาการ</span>
                </div>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem; color:#999; margin-left: 5px;"></i>
            </div>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-user-header"><small>ผู้ใช้งานระบบ</small><br><strong>เพชรลดา เชยเพ็ชร</strong>
                </div>
                <a href="#" class="dropdown-item"><i class="fa-regular fa-id-card"></i> ข้อมูลผู้ใช้งาน</a>
                <a href="#" class="dropdown-item"><i class="fa-solid fa-sliders"></i> ตั้งค่าระบบ</a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item text-danger" onclick="confirmLogout()"><i
                        class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="patient-banner">
        <div class="patient-container">
            <div class="patient-icon-box"><i class="fa-solid fa-user"></i></div>
            <div class="patient-details">
                <div class="section-header"><i class="fa-solid fa-hospital-user"></i> ข้อมูลผู้ป่วย</div>
                <div class="patient-grid">
                    <div class="info-item">
                        <span class="info-label">HN</span>
                        <span class="info-value"><?php echo h($patient['patients_hn']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">AN</span>
                        <span class="info-value"><?php echo h($patient['patients_an']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">ชื่อ - นามสกุล</span>
                        <span class="info-value"><?php echo h($patient['patients_name']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">อายุ</span>
                        <span class="info-value"><?php echo h($age_text); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">สิทธิการรักษา</span>
                        <span class="info-value"><?php echo h($patient['medical_rights']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">หอผู้ป่วย / เตียง</span>
                        <span class="info-value">
                            <?php echo h($patient['ward_name']); ?> /
                            <strong>เตียง <?php echo h($patient['bed_number']); ?></strong>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">วันที่ Admit</span>
                        <span class="info-value"><?php echo h($admit_date); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">เบอร์โทรศัพท์</span>
                        <span class="info-value"><?php echo h($patient['patients_phone']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <a href="patient_profile.php" class="btn-back">
            <i class="fa-solid fa-chevron-left"></i> ย้อนกลับ
        </a>
        <div class="page-title-inline">แบบคัดกรองภาวะโภชนาการ (SPENT Nutrition Screening Tool)</div>
    </div>

    <div class="main-content" style="padding: 20px; max-width: 1200px; margin: 0 auto;">

        <form class="form-card" id="screeningForm">
            <div class="form-row">
                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">วันที่ประเมิน</label>
                        <input type="date" class="form-control screen-input"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">HN</label>
                        <input type="text" class="form-control"
                            value="<?php echo h($patient['patients_hn']); ?>" readonly>
                    </div>
                </div>

                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">AN</label>
                        <input type="text" class="form-control"
                            value="<?php echo h($patient['patients_an']); ?>" readonly>
                    </div>
                </div>

                <div class="col-3">
                    <div class="form-group">
                        <label class="form-label">รับไว้ใน รพ. เมื่อ</label>
                        <input type="date" class="form-control"
                            value="<?php echo date('Y-m-d', strtotime($patient['admit_date'])); ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ - นามสกุล</label>
                        <input type="text" class="form-control"
                            value="<?php echo h($patient['patients_name']); ?>" readonly>
                    </div>
                </div>

                <div class="col-2">
                    <div class="form-group">
                        <label class="form-label">อายุ (ปี)</label>
                        <input type="text" class="form-control"
                            value="<?php echo $age->y; ?>" readonly>
                    </div>
                </div>

                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">หอผู้ป่วย</label>
                        <input type="text" class="form-control"
                            value="<?php echo h($patient['ward_name']); ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">การวินิจฉัยโรค (Diagnosis)</label>
                        <input type="text" class="form-control screen-input"
                            placeholder="ระบุการวินิจฉัยโรคเบื้องต้น..."
                            value="">
                    </div>
                </div>
            </div>
            <div class="section-divider"></div>
            <div class="section-title"><i class="fa-solid fa-weight-scale"></i> ข้อมูลสัดส่วนร่างกาย</div>
            <div class="form-row">
                <div class="col-3">
                    <div class="form-group"><label class="form-label">น้ำหนักปัจจุบัน (กก.)</label><input type="number"
                            step="0.1" class="form-control screen-input" id="currentWeight" placeholder="0.0"
                            oninput="calculateBMI()"></div>
                </div>
                <div class="col-3">
                    <div class="form-group"><label class="form-label">น้ำหนักปกติ (กก.)</label><input type="number"
                            step="0.1" class="form-control screen-input" placeholder="0.0"></div>
                </div>
                <div class="col-3">
                    <div class="form-group"><label class="form-label">ส่วนสูง (ซม.)</label><input type="number"
                            class="form-control screen-input" id="heightVal" placeholder="0" oninput="calculateBMI()">
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group"><label class="form-label">ดัชนีมวลกาย (BMI)</label><input type="text"
                            class="form-control bmi-box" id="bmiVal" placeholder="รอน้ำหนัก/ส่วนสูง" readonly></div>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12">
                    <div class="form-group"><label class="form-label">ประเมินน้ำหนักโดย</label>
                        <div class="radio-group-container">
                            <div class="radio-wrapper"><label class="radio-label"><input type="radio"
                                        name="weightMethod" class="screen-input" checked> ชั่ง</label><label
                                    class="radio-label"><input type="radio" name="weightMethod" class="screen-input">
                                    ซักถาม</label><label class="radio-label"><input type="radio" name="weightMethod"
                                        class="screen-input"> กะประมาณ</label></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-divider"></div>
            <div class="section-title"><i class="fa-solid fa-list-check"></i> แบบคัดกรอง (Screening Questions)</div>
            <div class="form-row">
                <div class="col-12">
                    <table class="screening-table">
                        <thead>
                            <tr>
                                <th>หัวข้อการคัดกรอง</th>
                                <th class="text-center" style="width: 100px;">ใช่</th>
                                <th class="text-center" style="width: 100px;">ไม่ใช่</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="screening-row">
                                <td>1. ผู้ป่วยมีน้ำหนักตัวลดลง โดยไม่ได้ตั้งใจในช่วง 6 เดือนที่ผ่านมาหรือไม่</td>
                                <td class="text-center"><input type="radio" name="q1" class="table-radio screen-input"
                                        value="yes"></td>
                                <td class="text-center"><input type="radio" name="q1" class="table-radio screen-input"
                                        value="no" checked></td>
                            </tr>
                            <tr class="screening-row">
                                <td>2. ผู้ป่วยได้รับอาหารน้อยกว่าที่เคยได้ (> 7 วัน)</td>
                                <td class="text-center"><input type="radio" name="q2" class="table-radio screen-input"
                                        value="yes"></td>
                                <td class="text-center"><input type="radio" name="q2" class="table-radio screen-input"
                                        value="no" checked></td>
                            </tr>
                            <tr class="screening-row">
                                <td>3. BMI < 18.5 หรือ>= 25.0 กก./ม.² หรือไม่</td>
                                <td class="text-center">
                                    <input type="radio" name="q3" value="yes" disabled>
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="q3" value="no" checked disabled>
                                </td>
                            </tr>
                            <tr class="screening-row">
                                <td>4. ผู้ป่วยมีภาวะโรควิกฤต หรือกึ่งวิกฤต หรือไม่</td>
                                <td class="text-center"><input type="radio" name="q4" class="table-radio screen-input"
                                        value="yes"></td>
                                <td class="text-center"><input type="radio" name="q4" class="table-radio screen-input"
                                        value="no" checked></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="form-row" id="saveScreeningRow">
                <div class="col-12" style="text-align: right;"><button type="button" class="btn-submit"
                        onclick="saveScreening()"><i class="fa-solid fa-floppy-disk"></i> บันทึกผลคัดกรอง</button></div>
            </div>
            <div id="resultBox" class="result-box">
                <div class="result-title" id="resultTitle"></div>
                <div class="result-desc" id="resultDesc"></div>
                <div id="highRiskOptions">
                    <div style="margin-top:20px; margin-bottom:10px; color:#555; font-size:0.95rem; font-weight:500;">
                        ผู้ป่วยมีความเสี่ยง ท่านต้องการดำเนินการอย่างไร?</div>
                    <div class="options-container" style="display: flex; gap: 15px; justify-content: center;"><button
                            type="button" class="btn-outline" onclick="saveLater()"><i class="fa-regular fa-clock"></i>
                            บันทึกไว้ก่อน (ทำแบบประเมินทีหลัง)</button><button type="button" class="btn-submit"
                            onclick="openAssessment()"><i class="fa-solid fa-play"></i> ทำแบบประเมินทันที</button></div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown logic
            const trigger = document.getElementById('userDropdownTrigger');
            const menu = document.getElementById('dropdownMenu');
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('show');
            });
            window.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !trigger.contains(e.target)) menu.classList.remove('show');
            });

            // Initialize Real-time Calculation Listeners
            initRealTimeCalculation();
        });

        function confirmLogout() {
            if (confirm('ยืนยันการออกจากระบบ?')) window.location.href = 'logout.php';
        }

        function calculateBMI() {
            const weight = parseFloat(document.getElementById('currentWeight').value);
            const heightCm = parseFloat(document.getElementById('heightVal').value);
            const bmiInput = document.getElementById('bmiVal');

            if (weight > 0 && heightCm > 0) {
                const heightM = heightCm / 100;
                const bmi = weight / (heightM * heightM);
                bmiInput.value = bmi.toFixed(2);

                // ส่วนควบคุมข้อ 3 อัตโนมัติ
                const q3Yes = document.querySelector('input[name="q3"][value="yes"]');
                const q3No = document.querySelector('input[name="q3"][value="no"]');

                if (bmi < 18.5 || bmi >= 25.0) {
                    q3Yes.checked = true;
                } else {
                    q3No.checked = true;
                }

                // สั่งคำนวณคะแนนรวมใหม่ทันที
                checkScore();
            } else {
                bmiInput.value = '';
            }
        }

        function checkScore() {
            let yesCount = 0;
            for (let i = 1; i <= 4; i++) {
                const radio = document.querySelector(`input[name="q${i}"]:checked`);
                if (radio && radio.value === 'yes') yesCount++;
            }

            const btnSave = document.getElementById('btnSaveLowRisk');
            const btnAssess = document.getElementById('btnGoToAssessment');

            if (yesCount >= 2) {
                btnSave.style.display = 'none';
                btnAssess.style.display = 'inline-block';
            } else {
                btnSave.style.display = 'inline-block';
                btnAssess.style.display = 'none';
            }
        }

        function saveScreening() {
            // โค้ดบันทึก Low Risk (AJAX หรือ Form Submit)
            alert('บันทึกผลคัดกรองเรียบร้อย (ความเสี่ยงต่ำ)');
            window.location.reload();
        }

        function goToAssessment() {
            // ส่ง HN ไปยังหน้าประเมิน
            const hn = "<?php echo $patient['patients_hn']; ?>";
            window.location.href = `nutrition_assessment.php?hn=${hn}`;
        }
    </script>
</body>

</html>