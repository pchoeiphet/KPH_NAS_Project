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

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];
$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 10,
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
    'fontdata' => $fontData + [
        'sarabun' => [
            'R' => 'THSarabunNew.ttf',
            'B' => 'THSarabunNew Bold.ttf',
            'I' => 'THSarabunNew Italic.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf'
        ]
    ],
    'default_font' => 'sarabun'
]);

// เนื้อหา HTML
$html = '
<style>
    body { font-family: "sarabun"; font-size: 15pt; line-height: 1.1; }
    .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 10px; }
    .content-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .content-table td { padding: 4px; vertical-align: top; }
    
    .border-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .border-table th, .border-table td { border: 1px solid #000; padding: 5px; }
    .bg-gray { background-color: #f2f2f2; }
    
    .title { font-size: 20pt; font-weight: bold; text-align: center; }
    .section-title { font-weight: bold; font-size: 16pt; text-decoration: underline; margin-bottom: 5px; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    
    .result-container { border: 2px solid #000; padding: 10px; margin-top: 5px; }
    .footer-sign { margin-top: 20px; width: 100%; }
</style>

<table class="header-table">
    <tr>
        <td width="70%">
            <div class="title">แบบคัดกรองภาวะโภชนาการ (SPENT)</div>
            <div style="text-align:center; font-size: 14pt;">โรงพยาบาลกำแพงเพชร (Kamphaeng Phet Hospital)</div>
        </td>
        <td width="30%" class="text-right" style="vertical-align: middle;">
            <div style="border: 1px solid #000; padding: 5px; font-size: 13pt;">
                <b>เลขที่เอกสาร:</b> ' . $data['doc_no'] . '<br>
                <b>วันที่พิมพ์:</b> ' . date('d/m/Y H:i') . '
            </div>
        </td>
    </tr>
</table>

<div class="section-title">ข้อมูลผู้ป่วย (Patient Information)</div>
<table class="content-table">
    <tr>
        <td width="50%"><b>ชื่อ-นามสกุล:</b> ' . $fullname . '</td>
        <td width="25%"><b>HN:</b> ' . $data['patients_hn'] . '</td>
        <td width="25%"><b>AN:</b> ' . $data['admissions_an'] . '</td>
    </tr>
    <tr>
        <td><b>อายุ:</b> ' . $age . '</td>
        <td><b>ตึก:</b> ' . $data['ward_name'] . '</td>
        <td><b>เตียง:</b> ' . ($data['bed_number'] ?? '-') . '</td>
    </tr>
    <tr>
        <td colspan="2"><b>สิทธิการรักษา:</b> ' . ($data['health_insurance_name'] ?? '-') . '</td>
        <td><b>วันที่รับเข้า:</b> ' . date('d/m/Y', strtotime($data['admit_datetime'])) . '</td>
    </tr>
    <tr>
        <td colspan="3"><b>แพทย์เจ้าของไข้:</b> ' . ($data['doctor_name'] ?? '-') . '</td>
    </tr>
    <tr>
        <td colspan="3"><b>การวินิจฉัยโรค:</b> ' . ($data['initial_diagnosis'] ?: '-') . '</td>
    </tr>
</table>

<div class="section-title">การประเมินทางกายภาพ (Physical Assessment)</div>
<table class="border-table">
    <tr class="bg-gray">
        <th width="20%">น้ำหนัก (kg)</th>
        <th width="20%">ส่วนสูง (cm)</th>
        <th width="20%">BMI (kg/m²)</th>
        <th width="40%">วิธีการวัด/ชั่งน้ำหนัก</th>
    </tr>
    <tr>
        <td class="text-center">' . $data['present_weight'] . '</td>
        <td class="text-center">' . $data['height'] . '</td>
        <td class="text-center"><b>' . $data['bmi'] . '</b></td>
        <td class="text-center">' . ($data['weight_method'] ?? '-') . '</td>
    </tr>
</table>

<div class="section-title">แบบคัดกรอง SPENT (Screening Tool)</div>
<table class="border-table">
    <tr class="bg-gray">
        <th width="70%">หัวข้อการประเมิน</th>
        <th width="15%">ใช่ (1)</th>
        <th width="15%">ไม่ใช่ (0)</th>
    </tr>
    <tr>
        <td>1. น้ำหนักตัวลดลงโดยไม่ได้ตั้งใจในช่วง 6 เดือนที่ผ่านมา</td>
        <td class="text-center">' . (($data['q1_weight_loss'] == 1) ? '<b>/</b>' : '') . '</td>
        <td class="text-center">' . (($data['q1_weight_loss'] == 0) ? '<b>/</b>' : '') . '</td>
    </tr>
    <tr>
        <td>2. รับประทานอาหารได้น้อยลงกว่าเดิมในช่วง 1 สัปดาห์ที่ผ่านมา</td>
        <td class="text-center">' . (($data['q2_eat_less'] == 1) ? '<b>/</b>' : '') . '</td>
        <td class="text-center">' . (($data['q2_eat_less'] == 0) ? '<b>/</b>' : '') . '</td>
    </tr>
    <tr>
        <td>3. มีค่าดัชนีมวลกาย (BMI) < 18.5 หรือ &ge; 25.0 kg/m²</td>
        <td class="text-center">' . (($data['q3_bmi_abnormal'] == 1) ? '<b>/</b>' : '') . '</td>
        <td class="text-center">' . (($data['q3_bmi_abnormal'] == 0) ? '<b>/</b>' : '') . '</td>
    </tr>
    <tr>
        <td>4. เป็นผู้ป่วยวิกฤต/กึ่งวิกฤต (อาทิ ใส่เครื่องช่วยหายใจ, โรคตับ/ไตวาย ฯลฯ)</td>
        <td class="text-center">' . (($data['q4_critical'] == 1) ? '<b>/</b>' : '') . '</td>
        <td class="text-center">' . (($data['q4_critical'] == 0) ? '<b>/</b>' : '') . '</td>
    </tr>
    <tr class="bg-gray">
        <td class="text-right"><b>คะแนนรวม (Total Score)</b></td>
        <td colspan="2" class="text-center"><b>' . $score . ' คะแนน</b></td>
    </tr>
</table>

';

// สรุปผล
$isRisk = ($score >= 2);
$resultText = $isRisk ? 'มีความเสี่ยงต่อภาวะโภชนาการ (At Risk)' : 'ปกติ (Normal)';
$suggestion = $isRisk ? 'ประเมิน NAF ต่อ หรือปรึกษานักกำหนดอาหาร' : 'ประเมินซ้ำทุก 7 วัน หรือเมื่อมีการเปลี่ยนแปลง';
$statusColor = $isRisk ? '#fce4ec' : '#f1f8e9';

$html .= '
<div class="result-container" style="background-color: ' . $statusColor . ';">
    <table width="100%">
        <tr>
            <td width="70%">
                <div style="font-size: 17pt;"><b>ผลการประเมิน:</b> ' . $resultText . '</div>
                <div style="font-size: 15pt;"><b>แผนการดูแล:</b> ' . $suggestion . '</div>
            </td>
            <td width="30%" class="text-center" style="border-left: 1px solid #000;">
                <div style="font-size: 14pt;">ระดับความเสี่ยง</div>
                <div style="font-size: 24pt; font-weight: bold;">' . ($isRisk ? 'เสี่ยง' : 'ปกติ') . '</div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 10px;">
    <b>หมายเหตุ:</b> ' . ($data['notes'] ?: '-') . '
</div>

<table class="footer-sign">
    <tr>
        <td width="50%">
            <div style="margin-top: 20px;">
                วันที่ประเมิน: ' . date('d/m/Y', strtotime($data['screening_datetime'])) . ' เวลา: ' . date('H:i', strtotime($data['screening_datetime'])) . ' น.
            </div>
        </td>
        <td width="50%" class="text-center">
            <div style="margin-top: 10px;">
                ลงชื่อ...........................................................<br>
                (' . $data['assessor_name'] . ')<br>
                ผู้ประเมิน
            </div>
        </td>
    </tr>
</table>
';

$mpdf->WriteHTML($html);
$mpdf->Output('Nutrition_Form_' . $data['doc_no'] . '.pdf', 'I');
