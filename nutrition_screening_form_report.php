<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'connect_db.php';
date_default_timezone_set('Asia/Bangkok');

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$doc_no = $_GET['doc_no'] ?? '';
if (empty($doc_no)) die("Error: ไม่พบเลขที่เอกสาร");

// ดึงข้อมูลหลัก
try {
    $sql = "
        SELECT 
            nutrition_screening.*, 
            nutritionists.nut_fullname,
            nutritionists.nut_position,
            patients.patients_firstname, 
            patients.patients_lastname, 
            patients.patients_hn, 
            patients.patients_dob,
            patients.patients_gender,
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
        LEFT JOIN nutritionists ON nutrition_screening.nut_id = nutritionists.nut_id
        
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
        $age = $diff->y . ' ปี ' . $diff->m . ' เดือน' . ' ' . $diff->d . ' วัน';
    }

    $gender = '-';
    if (!empty($data['patients_gender'])) {
        if ($data['patients_gender'] == 'ชาย') {
            $gender = 'ชาย';
        } elseif ($data['patients_gender'] == 'หญิง') {
            $gender = 'หญิง';
        }
    }

    $score = intval($data['q1_weight_loss'] ?? 0) + intval($data['q2_eat_less'] ?? 0) + intval($data['q3_bmi_abnormal'] ?? 0) + intval($data['q4_critical'] ?? 0);
    $fullname = ($data['patients_firstname'] ?? '') . ' ' . ($data['patients_lastname'] ?? '');
} catch (PDOException $e) {
    die($e->getMessage());
}

$assessor_show = !empty($data['nut_fullname']) ? $data['nut_fullname'] : '-';
$position_show = !empty($data['nut_position']) ? $data['nut_position'] : 'นักโภชนาการ';

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

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="15%" style="text-align:left; vertical-align:middle;">
            <img src="img/logo_kph.jpg" style="height:75px;">
        </td>

        <td width="55%" style="vertical-align:middle; padding-left:10px;">
            <div style="font-size:20pt; font-weight:bold; line-height:1.1;">
                แบบคัดกรองภาวะโภชนาการ
            </div>
            <div style="font-size:16pt; font-weight:bold; line-height:1.1;">
                โรงพยาบาลกำแพงเพชร
            </div>
            <div style="font-size:12pt; font-style:italic; margin-top:2px;">
                (Nutrition Screening Tool : SPENT)
            </div>
            <div style="margin-top:6px; font-size:14pt;">
                <b>คัดกรองครั้งที่:</b>
                <span style="font-size:16pt;">
                    ' . ($data['screening_seq'] ?? '1') . '
                </span>
            </div>
        </td>

        <td width="50%" style="vertical-align:top;">
    <table width="100%" style="border:2px solid #000; font-size:12.5pt; line-height:1.3;">
        <tr>
            <td colspan="2" class="bold"
                style="font-size:15pt; border-bottom:1px solid #000; padding:4px;">
                ' . $fullname . '
            </td>
        </tr>
        <tr>
            <td width="50%"><b>HN:</b> ' . $data['patients_hn'] . '</td>
            <td width="50%"><b>AN:</b> ' . $data['admissions_an'] . '</td>
        </tr>
        <tr>
            <td><b>อายุ:</b> ' . $age . '</td>
            <td><b>เพศ:</b> ' . $gender . '</td>
        </tr>
        <tr>
            <td><b>หอผู้ป่วย:</b> ' . $data['ward_name'] . '</td>
            <td><b>เตียง:</b> ' . ($data['bed_number'] ?? '-') . '</td>
        </tr>
        <tr>
            <td colspan="2"><b>สิทธิการรักษา:</b> ' . ($data['health_insurance_name'] ?? '-') . '</td>
        </tr>
        <tr>
            <td colspan="2">
                <b>แพทย์เจ้าของไข้:</b> ' . ($data['doctor_name'] ?? '-') . '
            </td>
        </tr>
    </table>
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
            ' . date('d/m/', strtotime($data['screening_datetime'])) . (date('Y', strtotime($data['screening_datetime'])) + 543) . '
        </td>
        <td width="30%">
            <b>เวลาคัดกรอง:</b><br>
            ' . date('H:i', strtotime($data['screening_datetime'])) . ' น.
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <b>การวินิจฉัยโรค (Diagnosis):</b><br>
            ' . ($data['initial_diagnosis'] ?: '-') . '
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
        <td class="bold">' . ($data['present_weight'] ?? '-') . '</td>
        <td>' . ($data['normal_weight'] ?? '-') . '</td>
        <td>' . ($data['height'] ?? '-') . '</td>
        <td class="bold">' . ($data['bmi'] ?? '-') . '</td>
        <td style="font-size: 13pt;">' . ($data['weight_method'] ?? '-') . '</td>
    </tr>
</table>

<div class="section-header">ส่วนที่ 2: แบบคัดกรองภาวะโภชนาการ (SPENT Nutrition Screening Tool)</div>
<table class="table-content">
    <thead>
        <tr class="text-center bold bg-light">
            <th width="74%">ประเด็นคำถาม</th>
            <th width="13%">ใช่ (1)</th>
            <th width="13%">ไม่ใช่ (0)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 6px;">1. ผู้ป่วยน้ำหนักตัวลดลง โดยไม่ได้ตั้งใจ (ในช่วง 6 เดือนที่ผ่านมา)</td>
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
            <td style="padding: 6px;">4. ผู้ป่วยมีภาวะโรควิกฤต หรือกึ่งวิกฤต</td>
            <td class="text-center">' . (($data['q4_critical'] == 1) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
            <td class="text-center">' . (($data['q4_critical'] == 0) ? '<span class="checkbox">&#9745;</span>' : '') . '</td>
        </tr>
    </tbody>
</table>

<div class="section-header">
    สรุปผลการคัดกรองภาวะโภชนาการ (SPENT)
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
        ' . ($data['notes'] ?: '') . '
    </td>
</tr>

</table>

<br>

<table width="100%" style="margin-top: 30px;">
    <tr>
        <td width="50%">
            <div style="border: 1px dashed #000; padding: 6px; font-size: 11pt; height: 70px; width: 90%;">
                <b>บันทึกเพิ่มเติมจากฝ่ายโภชนาการ:</b>
            </div>
        </td>
        <td width="50%" class="text-center" style="vertical-align: bottom;">
    ลงชื่อ................................................................ ผู้คัดกรอง<br>
    ( ' . $assessor_show . ' )<br>
    <span class="bold">ตำแหน่ง ' . $position_show . '</span><br>
    วันที่พิมพ์: ' . date('d/m/') . (date('Y') + 543) . date(' H:i') . ' น.
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
$mpdf->Output($data['doc_no'] . '.pdf', 'I');
?>