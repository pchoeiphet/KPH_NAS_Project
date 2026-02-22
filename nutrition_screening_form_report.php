<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_destroy();
    error_log("Session timeout for user: " . $_SESSION['user_id']);
    die("ข้อผิดพลาด: หมดเวลาการใช้งาน");
}
$_SESSION['last_activity'] = time();

// Input validation for doc_no
$doc_no = trim($_GET['doc_no'] ?? '');
if (empty($doc_no) || !preg_match('/^[A-Z]+-[A-Za-z0-9\-]+$/', $doc_no)) {
    error_log("Invalid doc_no parameter: $doc_no");
    die("ข้อผิดพลาด: พารามิเตอร์ไม่ถูกต้อง");
}

// ดึงข้อมูลหลัก
try {
    $sql = "
        SELECT 
            nutrition_screening.*, 
            nutritionist.nutritionist_fullname,
            nutritionist.nutritionist_position,
            patient.patient_firstname, 
            patient.patient_lastname, 
            patient.patient_hn, 
            patient.patient_dob,
            patient.patient_gender,
            admissions.bed_number, 
            admissions.admit_datetime,
            ward.ward_name, 
            doctor.doctor_name, 
            health_insurance.health_insurance_name
        FROM nutrition_screening
        JOIN patient ON nutrition_screening.patient_hn = patient.patient_hn
        JOIN admissions ON nutrition_screening.admissions_an = admissions.admissions_an
        LEFT JOIN ward ON admissions.ward_id = ward.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        LEFT JOIN health_insurance ON admissions.health_insurance_id = health_insurance.health_insurance_id
        LEFT JOIN nutritionist ON nutrition_screening.nutritionist_id = nutritionist.nutritionist_id
        
        WHERE nutrition_screening.doc_no = :doc_no
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_no' => $doc_no]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        error_log("SPENT form not found: doc_no=$doc_no, user=" . $_SESSION['user_id']);
        die("ข้อผิดพลาด: ไม่พบข้อมูล");
    }

    $age = '-';
    if (!empty($data['patient_dob'])) {
        $diff = (new DateTime())->diff(new DateTime($data['patient_dob']));
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน' . ' ' . $diff->d . ' วัน';
    }

    $gender = '-';
    if (!empty($data['patient_gender'])) {
        if ($data['patient_gender'] == 'ชาย') {
            $gender = 'ชาย';
        } elseif ($data['patient_gender'] == 'หญิง') {
            $gender = 'หญิง';
        }
    }

    $score = intval($data['q1_weight_loss'] ?? 0) + intval($data['q2_eat_less'] ?? 0) + intval($data['q3_bmi_abnormal'] ?? 0) + intval($data['q4_critical'] ?? 0);
    $fullname = ($data['patient_firstname'] ?? '') . ' ' . ($data['patient_lastname'] ?? '');

    // ดึงลายเซ็นถ้ามี
    $signature_html = '';
    try {
        $stmt_sig = $conn->prepare("
            SELECT nutritionist_signature_type, nutritionist_signature_data 
            FROM nutritionist_signature 
            WHERE nutritionist_id = :nutritionist_id 
            LIMIT 1
        ");
        $stmt_sig->execute([':nutritionist_id' => $data['nutritionist_id']]);
        $signature = $stmt_sig->fetch(PDO::FETCH_ASSOC);

        if ($signature && !empty($signature['nutritionist_signature_data'])) {
            if ($signature['nutritionist_signature_type'] === 'canvas') {
                // ลายเซ็นแบบวาด
                $base64_image = $signature['nutritionist_signature_data'];
                $signature_html = '<img src="data:image/png;base64,' . $base64_image . '" style="height: 40px; margin-bottom: -10px;">';
            } else {
                // ลายเซ็นแบบพิมพ์
                $signature_html = '<div style="font-size: 14pt; font-weight: bold; margin-top: 10px;">' .
                    htmlspecialchars($signature['nutritionist_signature_data'], ENT_QUOTES, 'UTF-8') . '</div>';
            }
        } else {
            // กรณีไม่มีลายเซ็น ให้เว้นว่าง
            $signature_html = '';
        }
    } catch (PDOException $e) {
        error_log("Error fetching signature: " . $e->getMessage());
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("ข้อผิดพลาด: ไม่สามารถเชื่อมต่อฐานข้อมูล");
}

$assessor_show = !empty($data['nutritionist_fullname']) ? $data['nutritionist_fullname'] : '-';
$position_show = !empty($data['nutritionist_position']) ? $data['nutritionist_position'] : 'นักโภชนาการ';

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];
$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 12,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 8,
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
    'fontdata' => $fontData + [
        'sarabun' => [
            'R'  => 'THSarabunNew.ttf',
            'B'  => 'THSarabunNew Bold.ttf',
            'I'  => 'THSarabunNew Italic.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf',
        ],
    ],
    'default_font' => 'sarabun',
    'default_font_size' => 14
]);

// HTML Structure  
$html = '
<style>
    body { font-family: "sarabun"; color: #000; line-height: 1.1; }
    table { width: 100%; border-collapse: collapse; }
    .bold { font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .border-main { border: 1.5px solid #000; }
    .bg-light { background-color: #f5f5f5; }
    
    /* Addressograph Box สำหรับติด Sticker หรือพิมพ์ข้อมูลผู้ป่วย */
    .addressograph {
        border: 2px solid #000;
        padding: 6px;
        width: 360px;
        float: right;
        margin-bottom: 5px;
    }

    .section-header { 
        background-color: #f0f0f0; 
        border: 1px solid #000; 
        padding: 4px 10px; 
        font-weight: bold; 
        font-size: 14pt;
        margin-top: 5px;
    }

    .table-content td, .table-content th { 
        border: 1px solid #000; 
        padding: 4px 8px; 
        vertical-align: top; 
        font-size: 13.5pt;
    }

    .checkbox { font-family: DejaVu Sans; font-size: 13pt; }
    
    .score-result {
        border: 2px solid #000;
        padding: 5px;
        text-align: center;
        width: 110px;
        float: right;
    }
</style>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
    <tr>
        <td width="65%" style="text-align:left; vertical-align:middle;">
            <table width="100%">
                <tr>
                    <td width="75px" style="vertical-align:middle;">
                        <img src="img/logo_kph.jpg" style="height:70px;">
                    </td>
                    <td style="vertical-align:middle; padding-left:10px;">
                        <div style="font-size:16pt; line-height:1.1;">โรงพยาบาลกำแพงเพชร</div>
                        <div style="font-size:16pt; line-height:1.1;">แบบคัดกรองภาวะโภชนาการ</div>
                        <div style="font-size:16pt; font-style:italic; color:#333;">(Nutrition Screening Tool : SPENT)</div>
                        <div style="margin-top:4px; font-size:16pt;">
                            (การคัดกรองครั้งที่ <span style="font-size:15pt;">' . ($data['nutrition_screening_seq'] ?? '1') . '</span>)
                        </div>
                    </td>
                </tr>
            </table>
        </td>

        <td width="48%" align="right" style="vertical-align: top;">
    <div style="border: 1px solid #000; padding: 8px 12px; border-radius: 5px; background-color: #fafafa; width: 270px; display: inline-block;">
        <table width="100%" style="font-size: 14pt; border-collapse: collapse; line-height: 1.2;">
            <tr>
                <td colspan="2" align="left">
                    <b>ชื่อ - สกุล:</b> ' . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . '
                </td>
            </tr>
            <tr>
                <td width="55%" align="left">
                    <b>HN:</b> ' . htmlspecialchars($data['patient_hn'] ?? '-', ENT_QUOTES, 'UTF-8') . '
                </td>
                <td width="45%" align="left">
                    <b>AN:</b> ' . htmlspecialchars($data['admissions_an'] ?? '-', ENT_QUOTES, 'UTF-8') . '
                </td>
            </tr>
            <tr>
                <td align="left"><b>อายุ:</b> ' . htmlspecialchars($age, ENT_QUOTES, 'UTF-8') . '</td>
                <td align="left"><b>เพศ:</b> ' . htmlspecialchars($gender, ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td align="left"><b>หอผู้ป่วย:</b> ' . htmlspecialchars($data['ward_name'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
                <td align="left"><b>เตียง:</b> ' . htmlspecialchars($data['bed_number'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>
    </div>
</td>
    </tr>
</table>

<div class="section-header">ส่วนที่ 1: ข้อมูลแรกรับและการวินิจฉัย (Admission & Clinical Data)</div>
<table class="table-content">
    <tr>
        <td width="33%">
            <b>วันที่รับเข้ารักษา:</b><br>
            ' . date('d/m/', strtotime($data['admit_datetime'])) . (date('Y', strtotime($data['admit_datetime'])) + 543) . date(' H:i', strtotime($data['admit_datetime'])) . ' น.
        </td>
        <td width="33%">
            <b>วันที่คัดกรอง:</b><br>
            ' . date('d/m/', strtotime($data['nutrition_screening_datetime'])) . (date('Y', strtotime($data['nutrition_screening_datetime'])) + 543) . '
        </td>
        <td width="30%">
            <b>เวลาคัดกรอง:</b><br>
            ' . date('H:i', strtotime($data['nutrition_screening_datetime'])) . ' น.
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>การวินิจฉัยโรค (Diagnosis):</b><br>
            ' . htmlspecialchars($data['initial_diagnosis'] ?? '-', ENT_QUOTES, 'UTF-8') . '
        </td>
    </tr>
</table>


<table class="table-content" style="margin-top: 6px;">
    <tr class="text-center bold bg-light">
        <td width="20%">น้ำหนักปัจจุบัน (กก.)</td>
        <td width="20%">น้ำหนักปกติ (กก.)</td>
        <td width="20%">ส่วนสูง (ซม.)</td>
        <td width="20%">BMI (กก./ม²)</td>
        <td width="20%">ประเมินน้ำหนักโดย</td>
    </tr>
    <tr class="text-center" style="font-size: 15pt;">
        <td class="bold">' . htmlspecialchars($data['present_weight'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($data['normal_weight'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($data['height'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td class="bold">' . htmlspecialchars($data['bmi'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        <td style="font-size: 13pt;">' . htmlspecialchars($data['weight_method'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
    </tr>
</table>

<div class="section-header">ส่วนที่ 2: แบบคัดกรองภาวะโภชนาการ (SPENT Nutrition Screening Tool)</div>
<table class="table-content">
    <thead>
        <tr class="text-center bold bg-light">
            <th width="74%">หัวข้อการคัดกรอง</th>
            <th width="13%">ใช่ (1)</th>
            <th width="13%">ไม่ใช่ (0)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 6px;">1. ผู้ป่วยน้ำหนักตัวลดลง โดยไม่ได้ตั้งใจในช่วง 6 เดือนที่ผ่านมาหรือไม่</td>
            <td class="text-center">' . (($data['q1_weight_loss'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q1_weight_loss'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
        <tr>
            <td style="padding: 6px;">2. ผู้ป่วยได้รับอาหารน้อยกว่าที่เคยได้ (> 7 วัน)</td>
            <td class="text-center">' . (($data['q2_eat_less'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q2_eat_less'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
        <tr>
            <td style="padding: 6px;">3. BMI < 18.5 หรือ ≥ 25.0 กก./ม.² หรือไม่</td>
            <td class="text-center">' . (($data['q3_bmi_abnormal'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q3_bmi_abnormal'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
        <tr>
            <td style="padding: 6px;">4. ผู้ป่วยมีภาวะโรควิกฤต หรือกึ่งวิกฤตร่วมด้วยหรือไม่</td>
            <td class="text-center">' . (($data['q4_critical'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q4_critical'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
    </tbody>
</table>

<div class="section-header">
    สรุปผลการคัดกรองภาวะโภชนาการ (SPENT Nutrition Screening Tool)
</div>


<table class="table-content">
    <tr>
        <td width="5%" class="text-center">
            <span class="checkbox">
                ' . ($score >= 2 ? '&#9745;' : '&#9744;') . '
            </span>
        </td>
        <td width="95%" style="font-size: 14pt;">
            ถ้าตอบ <b>ใช่ ≥ 2 ข้อ</b>  
            ทำการประเมินภาวะโภชนาการต่อ  
            หรือปรึกษานักกำหนดอาหาร / ทีมโภชนบำบัด
        </td>
    </tr>
    <tr>
        <td class="text-center">
            <span class="checkbox">
                ' . ($score <= 1 ? '&#9745;' : '&#9744;') . '
            </span>
        </td>
        <td style="font-size: 14pt;">
            ถ้าตอบ <b>ใช่ ≤ 1 ข้อ</b>  
            ให้คัดกรองซ้ำ <b>สัปดาห์ละ 1 ครั้ง</b>  
            ในช่วงที่ผู้ป่วยอยู่โรงพยาบาล
        </td>
    </tr>

</table><div class="section-header">ส่วนที่ 3: แผนการจัดการและข้อเสนอแนะ (Intervention Plan)</div>
<table class="table-content">
    <tr class="bold" style="border-bottom:2px solid #000;">
        <td width="50%">แนวทางดำเนินการ</td>
        <td width="50%">การปฏิบัติจริง</td>
    </tr>
    <tr>
        <td style="font-size:13pt; line-height:1.4;">
    ' . ($score >= 2
    ? '• <b>พบความเสี่ยงทางโภชนาการ</b><br>
           • ส่งประเมินภาวะโภชนาการเชิงลึก (NAF)<br>
           • ปรึกษานักกำหนดอาหาร / ทีมโภชนบำบัด'
    : '• <b>ไม่พบความเสี่ยงทางโภชนาการ</b><br>
           • ให้การดูแลตามแนวทางมาตรฐานโรงพยาบาล<br>
           • คัดกรองซ้ำทุก 7 วันระหว่างการนอนรักษา'
) . '
</td>
        <td style="font-size:13pt; line-height:1.6;">
    <span class="checkbox">' . ($score >= 2 ? '&#9745;' : '&#9744;') . '</span>
    ปรึกษานักกำหนดอาหาร / ทีมโภชนบำบัด (Consult Dietitian)<br>

    <span class="checkbox">' . ($score < 2 ? '&#9745;' : '&#9744;') . '</span>
    เฝ้าระวังและติดตามอาการ (Monitoring)<br>

    <span class="checkbox">&#9744;</span>
    อื่น ๆ ................................................................
</td>

    </tr>
   <tr>
    <td colspan="2" style="height:50px; font-size:13pt;">
        <b>หมายเหตุ / ข้อสังเกตเพิ่มเติม:</b><br>
        ' . htmlspecialchars($data['notes'] ?? '', ENT_QUOTES, 'UTF-8') . '
    </td>
</tr>

</table>

<br>

<table width="100%" style="margin-top: 30px;">
    <tr>
        <td width="40%"></td>
        
        <td width="60%" align="center" style="vertical-align: bottom;">
            
            <div style="height: 50px; display: flex; align-items: end; justify-content: center;">
                ' . $signature_html . '
            </div>
            
            <div style="margin-top: 5px;">ลงชื่อ................................................................ ผู้คัดกรอง</div>
            
            <div style="margin-top: 5px;">( ' . htmlspecialchars($assessor_show, ENT_QUOTES, 'UTF-8') . ' )</div>
            <div>ตำแหน่ง ' . htmlspecialchars($position_show, ENT_QUOTES, 'UTF-8') . '</div>
            <div style="margin-top: 5px; font-size: 12pt;">วันที่พิมพ์: ' . date('d/m/') . (date('Y') + 543) . date(' H:i') . ' น.</div>
        </td>
    </tr>
</table>

<div style="position: absolute; bottom: 5px; width: 100%; border-top: 1px solid #000; padding-top: 3px; font-size: 10pt;">
    <table width="100%">
        <tr>
            <td width="40%">เลขที่ใบงาน: ' . htmlspecialchars($data['doc_no'], ENT_QUOTES, 'UTF-8') . '</td>
            <td width="10%" class="text-left">ฝ่ายโภชนศึกษาและโภชนบำบัด โรงพยาบาลกำแพงเพชร</td>
        </tr>
    </table>
</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output($data['doc_no'] . '.pdf', 'I');
