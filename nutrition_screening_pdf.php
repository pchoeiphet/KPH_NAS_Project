<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

$doc_no = $_GET['doc_no'] ?? '';
if (empty($doc_no)) die("Error: ไม่พบเลขที่เอกสาร");

try {
    $sql = "
        SELECT 
            nutrition_screening.*, 
            patients.patients_firstname, 
            patients.patients_lastname, 
            patients.patients_hn, 
            patients.patients_dob,
            admissions.bed_number, 
            admissions.admit_datetime,
            wards.ward_name, 
            doctor.doctor_name, 
            health_insurance.health_insurance_name
        FROM nutrition_screening
        JOIN patients ON nutrition_screening.patients_hn = patients.patients_hn
        JOIN admissions ON nutrition_screening.admissions_an = admissions.admissions_an
        LEFT JOIN wards ON admissions.ward_id = wards.ward_id
        LEFT JOIN doctor ON admissions.doctor_id = doctor.doctor_id
        LEFT JOIN health_insurance ON admissions.health_insurance_id = health_insurance.health_insurance_id
        WHERE nutrition_screening.doc_no = :doc_no
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_no' => $doc_no]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) die("ไม่พบข้อมูล");

    $age = '-';
    if (!empty($data['patients_dob'])) {
        $diff = (new DateTime())->diff(new DateTime($data['patients_dob']));
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน';
    }
    $score = intval($data['q1_weight_loss'] ?? 0) + intval($data['q2_eat_less'] ?? 0) + intval($data['q3_bmi_abnormal'] ?? 0) + intval($data['q4_critical'] ?? 0);
    $fullname = ($data['patients_firstname'] ?? '') . ' ' . ($data['patients_lastname'] ?? '');
} catch (PDOException $e) {
    die($e->getMessage());
}

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
        padding: 2px 10px; 
        font-weight: bold; 
        font-size: 13pt;
        margin-top: 6px;
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

<div style="width: 100%;">
    <!-- Patient ID Sticker / Addressograph Area -->
    <div class="addressograph">
        <table style="font-size: 12.5pt; line-height: 1.05;">
            <tr><td colspan="2" class="bold" style="font-size: 15pt; border-bottom: 1px solid #000; padding-bottom: 2px;">' . $fullname . '</td></tr>
            <tr>
                <td width="50%"><b>HN:</b> ' . $data['patients_hn'] . '</td>
                <td width="50%"><b>AN:</b> ' . $data['admissions_an'] . '</td>
            </tr>
            <tr>
                <td><b>อายุ:</b> ' . $age . '</td>
                <td><b>หอผู้ป่วย:</b> ' . $data['ward_name'] . '</td>
            </tr>
            <tr>
                <td><b>เตียง:</b> ' . ($data['bed_number'] ?? '-') . '</td>
                <td><b>สิทธิ:</b> ' . ($data['health_insurance_name'] ?? '-') . '</td>
            </tr>
            <tr>
                <td colspan="2"><b>แพทย์เจ้าของไข้:</b> ' . ($data['doctor_name'] ?? '-') . '</td>
            </tr>
        </table>
    </div>

    <!-- Official Header Title -->
    <div style="padding-top: 5px;">
        <div style="font-size: 20pt;" class="bold">แบบคัดกรองภาวะโภชนาการ</div>
        <div style="font-size: 16pt;" class="bold">โรงพยาบาลกำแพงเพชร</div>
        <div style="font-size: 12pt; margin-top: 5px; font-style: italic;">(Nutrition Screening Tool : SPENT)</div>
        <div style="margin-top: 15px; font-size: 14pt;">
            <b>คัดกรองครั้งที่:</b> <span style="font-size: 16pt;">&nbsp; ' . ($data['screening_round'] ?? '1') . ' &nbsp;</span>
        </div>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="section-header">ส่วนที่ 1: ข้อมูลแรกรับและการวินิจฉัย (Admission & Clinical Data)</div>
<table class="table-content">
    <tr>
        <td width="35%"><b>วันที่คัดกรอง:</b> ' . date('d/m/Y', strtotime($data['screening_datetime'])) . '</td>
        <td width="25%"><b>เวลา:</b> ' . date('H:i', strtotime($data['screening_datetime'])) . ' น.</td>
        <td width="40%"><b>วันที่รับเข้ารักษา:</b> ' . date('d/m/Y', strtotime($data['admit_datetime'])) . '</td>
    </tr>
    <tr>
        <td colspan="3"><b>การวินิจฉัยโรค (Diagnosis):</b> ' . ($data['initial_diagnosis'] ?: '-') . '</td>
    </tr>
</table>

<table class="table-content" style="margin-top: 6px;">
    <tr class="text-center bold bg-light">
        <td width="20%">น้ำหนัก (kg)</td>
        <td width="20%">ส่วนสูง (cm)</td>
        <td width="20%">BMI (kg/m²)</td>
        <td width="40%">วิธีการชั่ง/วัด</td>
    </tr>
    <tr class="text-center" style="font-size: 16pt;">
        <td class="bold">' . $data['present_weight'] . '</td>
        <td>' . $data['height'] . '</td>
        <td class="bold">' . $data['bmi'] . '</td>
        <td style="font-size: 13pt;">' . ($data['weight_method'] ?? '-') . '</td>
    </tr>
</table>

<div class="section-header">ส่วนที่ 2: แบบคัดกรองภาวะโภชนาการ (SPENT Tool)</div>
<table class="table-content">
    <thead>
        <tr class="text-center bold bg-light">
            <th width="74%">หัวข้อการพิจารณา</th>
            <th width="13%">ใช่ (1)</th>
            <th width="13%">ไม่ใช่ (0)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 6px;">1. น้ำหนักตัวลดลงโดยไม่ได้ตั้งใจในช่วง 6 เดือนที่ผ่านมา</td>
            <td class="text-center">' . (($data['q1_weight_loss'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q1_weight_loss'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
        <tr>
            <td style="padding: 6px;">2. ความสามารถในการรับประทานอาหารลดลงมากกว่า 1 สัปดาห์</td>
            <td class="text-center">' . (($data['q2_eat_less'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q2_eat_less'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
        <tr>
            <td style="padding: 6px;">3. มีค่าดัชนีมวลกาย (BMI) < 18.5 หรือ &ge; 25.0 kg/m²</td>
            <td class="text-center">' . (($data['q3_bmi_abnormal'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q3_bmi_abnormal'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
        <tr>
            <td style="padding: 6px;">4. ผู้ป่วยมีภาวะวิกฤต / กึ่งวิกฤต</td>
            <td class="text-center">' . (($data['q4_critical'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q4_critical'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
    </tbody>
</table>

<div style="margin-top: 8px;">
    <div class="score-result">
        <span style="font-size: 11pt;">คะแนนรวม</span><br>
        <span style="font-size: 22pt;" class="bold">' . $score . '</span>
    </div>
    <div style="padding-top: 5px;">
        <span class="bold" style="font-size: 16pt;">สรุปผลการคัดกรอง: ' . ($score >= 2 ? 'มีความเสี่ยง (At Risk)' : 'ปกติ (Normal)') . '</span><br>
        <span style="font-size: 12pt;">* เกณฑ์ตัดสิน: รวมคะแนน &ge; 2 คะแนน ถือว่ามีความเสี่ยงต่อภาวะโภชนาการ</span>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="section-header">ส่วนที่ 3: แผนการจัดการและข้อเสนอแนะ (Intervention Plan)</div>
<table class="table-content">
    <tr class="bold bg-light">
        <td width="50%">แนวทางดำเนินการ</td>
        <td width="50%">การปฏิบัติจริง</td>
    </tr>
    <tr>
        <td style="font-size: 13pt;">
            ' . ($score >= 2
    ? '• <b>พบความเสี่ยง:</b> ต้องส่งประเมิน NAF ต่อเชิงลึก<br>• ปรึกษานักกำหนดอาหาร / Nutrition Team'
    : '• <b>ไม่พบความเสี่ยง:</b> ให้การดูแลตามมาตรฐานโรงพยาบาล<br>• ติดตามและคัดกรองซ้ำในอีก 7 วัน') . '
        </td>
        <td style="font-size: 13pt;">
            <span class="checkbox">' . ($score >= 2 ? '&#9745;' : '&#9744;') . '</span> ประสานงานนักกำหนดอาหาร (Consult Dietitian)<br>
            <span class="checkbox">' . ($score < 2 ? '&#9745;' : '&#9744;') . '</span> เฝ้าระวังและติดตามอาการตามเกณฑ์ (Monitoring)<br>
            <span class="checkbox">&#9744;</span> อื่นๆ ................................................................
        </td>
    </tr>
    <tr>
        <td colspan="2" style="height: 40px;"><b>หมายเหตุ (Notes):</b> ' . ($data['notes'] ?: '-') . '</td>
    </tr>
</table>

<br>

<!-- ส่วนลงชื่อผู้คัดกรอง -->
<table width="100%" style="margin-top: 30px;">
    <tr>
        <td width="50%">
            <div style="border: 1px dashed #000; padding: 6px; font-size: 11pt; height: 70px; width: 90%;">
                <b>บันทึกเพิ่มเติมจากฝ่ายโภชนาการ:</b>
            </div>
        </td>
        <td width="50%" class="text-center" style="vertical-align: bottom;">
            ลงชื่อ................................................................ ผู้คัดกรอง<br>
            ( ' . $data['assessor_name'] . ' )<br>
            <span class="bold">ตำแหน่ง นักโภชนาการ</span><br>
            วันที่พิมพ์: ' . date('d/m/Y H:i') . ' น.
        </td>
    </tr>
</table>

<div style="position: absolute; bottom: 5px; width: 100%; border-top: 1px solid #000; padding-top: 3px; font-size: 10pt;">
    <table width="100%">
        <tr>
            <td width="40%">เลขที่ใบงาน: ' . $data['doc_no'] . '</td>
            <td width="10%" class="text-left">ฝ่ายโภชนศึกษาและโภชนบำบัด โรงพยาบาลกำแพงเพชร</td>
        </tr>
    </table>
</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output('Nutrition_Screening_Form.pdf', 'I');
