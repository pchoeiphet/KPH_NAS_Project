<?php
// nutrition_screening_pdf.php
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

// mPDF Configuration
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [__DIR__ . '/fonts']),
    'fontdata' => (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
        'sarabun' => [
            'R' => 'THSarabunNew.ttf',
            'B' => 'THSarabunNew Bold.ttf',
        ]
    ],
    'default_font' => 'sarabun'
]);

$html = '
<style>
    body { font-family: "sarabun"; font-size: 15pt; line-height: 1.1; color: #000; }
    .w-100 { width: 100%; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .border { border: 1px solid #000; }
    .b-bottom { border-bottom: 1px solid #000; }
    .b-top { border-top: 1px solid #000; }
    
    /* Addressograph Box - มาตรฐานเวชระเบียน */
    .addressograph {
        border: 2px solid #000;
        padding: 8px;
        width: 320px;
        float: right;
    }
    
    .form-header { margin-bottom: 15px; }
    .section-title { background-color: #f0f0f0; padding: 4px 10px; font-weight: bold; border: 1px solid #000; margin-top: 10px; }
    
    .data-table { width: 100%; border-collapse: collapse; margin-top: -1px; }
    .data-table td, .data-table th { border: 1px solid #000; padding: 5px 8px; vertical-align: top; }
    
    .checkbox { font-family: DejaVu Sans, sans-serif; font-size: 14pt; }
    .score-box { font-size: 22pt; font-weight: bold; padding: 10px; border: 2px solid #000; display: inline-block; width: 60px; text-align: center; }
</style>

<!-- ส่วนหัวเอกสาร -->
<div class="form-header">
    <div class="addressograph">
        <div style="font-size: 10pt; text-align: right; margin-bottom: 5px;"><i>Patient Label / Sticker</i></div>
        <table width="100%" style="font-size: 14pt; line-height: 1.0;">
            <tr><td colspan="2" class="bold">ชื่อ-สกุล: ' . $fullname . '</td></tr>
            <tr><td width="50%"><b>HN:</b> ' . $data['patients_hn'] . '</td><td width="50%"><b>AN:</b> ' . $data['admissions_an'] . '</td></tr>
            <tr><td><b>อายุ:</b> ' . $age . '</td><td><b>เตียง:</b> ' . ($data['bed_number'] ?? '-') . '</td></tr>
            <tr><td colspan="2"><b>หอผู้ป่วย:</b> ' . $data['ward_name'] . '</td></tr>
        </table>
    </div>

    <div style="padding-top: 5px;">
        <div style="font-size: 11pt;">รหัสแบบฟอร์ม: KPP-NU-001 (Rev.02/2567)</div>
        <div style="font-size: 18pt;" class="bold">แบบประเมินภาวะโภชนาการเบื้องต้น</div>
        <div style="font-size: 14pt;" class="bold">โรงพยาบาลกำแพงเพชร</div>
        <div style="font-size: 12pt; margin-top: 5px;">Nutrition Screening Tool (SPENT)</div>
    </div>
    <div style="clear: both;"></div>
</div>

<div class="section-title">ส่วนที่ 1: ข้อมูลทางคลินิก (Clinical Information)</div>
<table class="data-table">
    <tr>
        <td width="30%"><b>วันที่คัดกรอง:</b> ' . date('d/m/Y', strtotime($data['screening_datetime'])) . '</td>
        <td width="25%"><b>เวลา:</b> ' . date('H:i', strtotime($data['screening_datetime'])) . ' น.</td>
        <td width="45%"><b>สิทธิการรักษา:</b> ' . ($data['health_insurance_name'] ?? '-') . '</td>
    </tr>
    <tr>
        <td colspan="3"><b>การวินิจฉัยโรค (Diagnosis):</b> ' . ($data['initial_diagnosis'] ?: '-') . '</td>
    </tr>
</table>

<table class="data-table" style="margin-top: 10px;">
    <tr class="text-center">
        <td width="25%"><b>น้ำหนัก (kg)</b></td>
        <td width="25%"><b>ส่วนสูง (cm)</b></td>
        <td width="25%"><b>BMI (kg/m²)</b></td>
        <td width="25%"><b>วิธีการวัด</b></td>
    </tr>
    <tr class="text-center" style="font-size: 16pt;">
        <td>' . $data['present_weight'] . '</td>
        <td>' . $data['height'] . '</td>
        <td class="bold">' . $data['bmi'] . '</td>
        <td style="font-size: 14pt;">' . ($data['weight_method'] ?? '-') . '</td>
    </tr>
</table>

<div class="section-title">ส่วนที่ 2: การคัดกรอง SPENT Score (Screening Tool)</div>
<table class="data-table">
    <thead>
        <tr style="background-color: #f9f9f9;">
            <th width="74%">หัวข้อประเมิน</th>
            <th width="13%">ใช่ (1)</th>
            <th width="13%">ไม่ใช่ (0)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1. น้ำหนักตัวลดลงโดยไม่ได้ตั้งใจ (ช่วง 6 เดือนที่ผ่านมา)</td>
            <td class="text-center">' . (($data['q1_weight_loss'] == 1) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
            <td class="text-center">' . (($data['q1_weight_loss'] == 0) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
        </tr>
        <tr>
            <td>2. รับประทานอาหารได้น้อยลงกว่าปกติ (> 7 วัน)</td>
            <td class="text-center">' . (($data['q2_eat_less'] == 1) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
            <td class="text-center">' . (($data['q2_eat_less'] == 0) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
        </tr>
        <tr>
            <td>3. BMI < 18.5 หรือ &ge; 25.0 kg/m²</td>
            <td class="text-center">' . (($data['q3_bmi_abnormal'] == 1) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
            <td class="text-center">' . (($data['q3_bmi_abnormal'] == 0) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
        </tr>
        <tr>
            <td>4. ผู้ป่วยมีภาวะวิกฤต/กึ่งวิกฤต หรือมีโรคเรื้อรังที่ส่งผลต่อโภชนาการ</td>
            <td class="text-center">' . (($data['q4_critical'] == 1) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
            <td class="text-center">' . (($data['q4_critical'] == 0) ? '<span class="checkbox">&#9745;</span>' : '<span class="checkbox">&#9744;</span>') . '</td>
        </tr>
    </tbody>
</table>

<table width="100%" style="margin-top: 10px;">
    <tr>
        <td width="70%" style="vertical-align: top;">
            <div class="bold" style="text-decoration: underline;">เกณฑ์การสรุปผล:</div>
            <div>• <b>คะแนน 0 - 1 :</b> ปกติ (Normal) ประเมินซ้ำทุก 7 วัน</div>
            <div>• <b>คะแนน &ge; 2 :</b> มีความเสี่ยง (At Risk) ประเมิน NAF ต่อละเอียด</div>
        </td>
        <td width="30%" class="text-right">
            <div style="margin-bottom: 5px;">คะแนนรวม (Total Score)</div>
            <div class="score-box">' . $score . '</div>
        </td>
    </tr>
</table>

<div class="section-title">ส่วนที่ 3: แผนการจัดการและสรุป (Management Plan)</div>
<table class="data-table">
    <tr>
        <td width="50%">
            <b>ผลการวิเคราะห์:</b><br>
            <div style="font-size: 16pt; padding: 5px;">
                ' . ($score >= 2
    ? '<span class="checkbox">&#9745;</span> <b>มีความเสี่ยง (At Risk)</b>'
    : '<span class="checkbox">&#9745;</span> <b>ปกติ (Normal)</b>') . '
            </div>
        </td>
        <td width="50%">
            <b>แผนการดูแล (Intervention):</b><br>
            <div style="font-size: 13pt;">
                ' . ($score >= 2
    ? '1. ปรึกษานักกำหนดอาหาร/โภชนากร<br>2. ประเมิน Nutrition Assessment Form (NAF)'
    : '1. คัดกรองซ้ำในอีก 7 วัน<br>2. ติดตามการบริโภคอาหาร/น้ำหนัก') . '
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="height: 60px;"><b>หมายเหตุ (Notes):</b> ' . ($data['notes'] ?: '-') . '</td>
    </tr>
</table>

<!-- ส่วนลงนาม -->
<table width="100%" style="margin-top: 40px;">
    <tr>
        <td width="40%" class="text-center">
            
        </td>
        <td width="60%" class="text-center">
            ลงชื่อ.................................................................... ผู้ประเมิน<br>
            ( ' . $data['assessor_name'] . ' )<br>
            ตำแหน่ง.............................................................<br>
            วันที่ .........../............/........... เวลา ............ น.
        </td>
    </tr>
</table>

<div style="position: absolute; bottom: 10px; width: 100%; font-size: 10pt; border-top: 1px solid #ccc; padding-top: 5px;">
    <table width="100%">
        <tr>
            <td width="50%">เลขที่ใบงาน: ' . $data['doc_no'] . '</td>
            <td width="50%" class="text-right">ฝ่ายโภชนศึกษาและโภชนบำบัด โรงพยาบาลกำแพงเพชร</td>
        </tr>
    </table>
</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output('Nutrition_Screening_Form.pdf', 'I');
