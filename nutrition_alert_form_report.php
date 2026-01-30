<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'connect_db.php';

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

date_default_timezone_set('Asia/Bangkok');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Input validation for doc_no
$doc_no = trim($_GET['doc_no'] ?? '');
if (empty($doc_no) || !preg_match('/^[A-Z]+-[A-Za-z0-9\-]+$/', $doc_no)) {
    error_log("Invalid doc_no parameter: $doc_no");
    die("ข้อผิดพลาด: พารามิเตอร์ไม่ถูกต้อง");
}

// ดึงข้อมูลหลัก
$sql = "
SELECT 
    nutrition_assessment.*,
    nutritionists.nut_fullname, 
    nutritionists.nut_position, 
    patients.patients_firstname, 
    patients.patients_lastname, 
    patients.patients_gender, 
    patients.patients_dob, 
    patients.patients_hn,
    
    admissions.admit_datetime,
    admissions.bed_number,
    
    wards.ward_name,
    
    weight_option.weight_option_label, 
    weight_option.weight_option_score,
    patient_shape.patient_shape_label, 
    patient_shape.patient_shape_score,
    weight_change_4_weeks.weight_change_4_weeks_label, 
    weight_change_4_weeks.weight_change_4_weeks_score,
    food_type.food_type_label, 
    food_type.food_type_score,
    food_amount.food_amount_label, 
    food_amount.food_amount_score,
    food_access.food_access_label, 
    food_access.food_access_score
FROM nutrition_assessment
JOIN patients ON patients.patients_hn = nutrition_assessment.patients_hn
JOIN admissions ON admissions.admissions_an = nutrition_assessment.admissions_an
LEFT JOIN wards ON wards.ward_id = admissions.ward_id 
LEFT JOIN nutritionists ON nutrition_assessment.nut_id = nutritionists.nut_id

LEFT JOIN weight_option ON weight_option.weight_option_id = nutrition_assessment.weight_option_id
LEFT JOIN patient_shape ON patient_shape.patient_shape_id = nutrition_assessment.patient_shape_id
LEFT JOIN weight_change_4_weeks ON weight_change_4_weeks.weight_change_4_weeks_id = nutrition_assessment.weight_change_4_weeks_id
LEFT JOIN food_type ON food_type.food_type_id = nutrition_assessment.food_type_id
LEFT JOIN food_amount ON food_amount.food_amount_id = nutrition_assessment.food_amount_id
LEFT JOIN food_access ON food_access.food_access_id = nutrition_assessment.food_access_id
WHERE nutrition_assessment.doc_no = :doc_no
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([':doc_no' => $doc_no]);
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    error_log("NAF form not found: doc_no=$doc_no, user=" . $_SESSION['user_id']);
    die("ข้อผิดพลาด: ไม่พบข้อมูล");
}

$assessment_id = $assessment['nutrition_assessment_id'];

// Prepare Data & Formatting

// Path Logo
$logo_path = 'img/logo_kph.jpg';

// เพศ
$gender_th = '-';
$g_code = strtoupper($assessment['patients_gender'] ?? '');
if ($g_code == 'M' || $g_code == '1' || $g_code == 'CHAI') {
    $gender_th = 'ชาย';
} elseif ($g_code == 'F' || $g_code == '2' || $g_code == 'YING') {
    $gender_th = 'หญิง';
} else {
    $gender_th = htmlspecialchars($assessment['patients_gender'] ?? '-');
}

// 2.1 ข้อมูลส่วนสูง
$height_detail_parts = [];
if (!empty($assessment['height_measure']) && $assessment['height_measure'] > 0) {
    $height_detail_parts[] = "วัดส่วนสูง: " . $assessment['height_measure'] . " ซม.";
}
if (!empty($assessment['body_length']) && $assessment['body_length'] > 0) {
    $height_detail_parts[] = "วัดความยาวตัว (นอน): " . $assessment['body_length'] . " ซม.";
}
if (!empty($assessment['arm_span']) && $assessment['arm_span'] > 0) {
    $height_detail_parts[] = "Arm span: " . $assessment['arm_span'] . " ซม.";
}
if (!empty($assessment['height_relative']) && $assessment['height_relative'] > 0) {
    $height_detail_parts[] = "ญาติบอก/กะประมาณ: " . $assessment['height_relative'] . " ซม.";
}
$height_detail = !empty($height_detail_parts) ? implode("<br/>", $height_detail_parts) : "-";

// น้ำหนัก/BMI
$q2_detail = "";
$q2_score = 0;

if (isset($assessment['is_no_weight']) && $assessment['is_no_weight'] == 0) {
    $weight_opt_score = $assessment['weight_option_score'] ?? 0;

    // แปลงเป็น float เพื่อความชัวร์ในการคำนวณ
    $bmi_val = (float)$assessment['bmi'];
    $bmi_score_calc = 0;
    $bmi_range_text = "";

    if ($bmi_val < 18.0) {
        if ($bmi_val < 17.0) {
            $bmi_score_calc = 2;
            $bmi_range_text = "(< 17.0)";
        } else {
            $bmi_score_calc = 1;
            $bmi_range_text = "(17.0 - 18.0)";
        }
    } elseif ($bmi_val < 30.0) {
        $bmi_score_calc = 0;
        $bmi_range_text = "(18.1 - 29.9)";
    } else {
        $bmi_score_calc = 1;
        $bmi_range_text = "(≥ 30.0)";
    }

    $q2_detail .= "<b>น้ำหนัก:</b> " . ($assessment['weight'] ?? '-') . " กก.<br/>";
    $q2_detail .= "<b>วิธีการชั่ง:</b> " . ($assessment['weight_option_label'] ?? '-') . " (" . $weight_opt_score . ")<br/>";

    // แสดงผล
    $q2_detail .= "<b>BMI:</b> " . number_format($bmi_val, 2) . " กก./ม² " . $bmi_range_text;

    $q2_score = $bmi_score_calc + $weight_opt_score;
} else {
    $lab_method = $assessment['lab_method'] ?? '';
    if (stripos($lab_method, 'albumin') !== false) {
        $val = $assessment['albumin_val'];
        $sc = $assessment['lab_score'];
        $q2_detail = "<b>ไม่สามารถชั่งน้ำหนักได้ ใช้ค่า Albumin:</b> " . $val . " g/dl";
        $q2_score = $sc;
    } elseif (stripos($lab_method, 'tlc') !== false) {
        $val = $assessment['tlc_val'];
        $sc = $assessment['lab_score'];
        $q2_detail = "<b>ไม่สามารถชั่งน้ำหนักได้ ใช้ค่า TLC:</b> " . $val . " cells/mm³";
        $q2_score = $sc;
    } else {
        $q2_detail = "ไม่สามารถประเมินได้";
        $q2_score = 0;
    }
}

// อาการ
$sql_sym = "SELECT sp.symptom_problem_name, sps.symptom_problem_score 
            FROM symptom_problem_saved sps
            JOIN symptom_problem sp ON sp.symptom_problem_id = sps.symptom_problem_id 
            WHERE sps.nutrition_assessment_id = :id";
$stmt_sym = $conn->prepare($sql_sym);
$stmt_sym->execute([':id' => $assessment_id]);
$symptomsData = $stmt_sym->fetchAll(PDO::FETCH_ASSOC);

$symptom_list = [];
$symptom_score_total = 0;
foreach ($symptomsData as $s) {
    $symptom_list[] = "&bull; " . $s['symptom_problem_name'] . " (" . $s['symptom_problem_score'] . ")";
    $symptom_score_total += $s['symptom_problem_score'];
}
$symptom_detail = !empty($symptom_list) ? implode("<br/>", $symptom_list) : "- ไม่มีอาการ -";

// โรค
$sql_dis = "SELECT d.disease_name, ds.disease_other_name, ds.disease_score 
            FROM disease_saved ds
            LEFT JOIN disease d ON d.disease_id = ds.disease_id 
            WHERE ds.nutrition_assessment_id = :id";
$stmt_dis = $conn->prepare($sql_dis);
$stmt_dis->execute([':id' => $assessment_id]);
$diseasesData = $stmt_dis->fetchAll(PDO::FETCH_ASSOC);

$disease_list = [];
$disease_score_total = 0;
foreach ($diseasesData as $d) {
    $name = !empty($d['disease_name']) ? $d['disease_name'] : 'โรคอื่นๆ';
    if (!empty($d['disease_other_name'])) {
        $name .= " (" . $d['disease_other_name'] . ")";
    }
    $disease_list[] = "&bull; " . $name . " (" . $d['disease_score'] . ")";
    $disease_score_total += $d['disease_score'];
}
$disease_detail = !empty($disease_list) ? implode("<br/>", $disease_list) : "- ไม่มีโรคที่ต้องระวัง -";

// อายุ
$age = '-';
if (!empty($assessment['patients_dob'])) {
    $diff = date_diff(date_create($assessment['patients_dob']), date_create('today'));
    $age = "{$diff->y} ปี {$diff->m} เดือน {$diff->d} วัน";
}

// แหล่งข้อมูล
$infoSourceText = $assessment['info_source'] ?? '-';
if ($infoSourceText == 'patient') $infoSourceText = 'ผู้ป่วย';
elseif ($infoSourceText == 'relative') $infoSourceText = 'ญาติ';
elseif ($infoSourceText == 'other') $infoSourceText = 'อื่นๆ';
if (!empty($assessment['other_source'])) {
    $infoSourceText .= ' (' . htmlspecialchars($assessment['other_source']) . ')';
}

// ครั้งที่ประเมิน
$hn = $assessment['patients_hn'];
$sql_seq = "SELECT doc_no FROM nutrition_assessment WHERE patients_hn = :hn ORDER BY assessment_datetime ASC";
$stmt_seq = $conn->prepare($sql_seq);
$stmt_seq->execute([':hn' => $hn]);
$all_docs = $stmt_seq->fetchAll(PDO::FETCH_COLUMN);
$key = array_search($doc_no, $all_docs);
$assessment_no = ($key !== false) ? $key + 1 : 1;

$patient_full_name = htmlspecialchars($assessment['patients_firstname'] . ' ' . $assessment['patients_lastname']);
$admit_date_th = date('d/m/', strtotime($assessment['admit_datetime'])) . (date('Y', strtotime($assessment['admit_datetime'])) + 543);


// ตรวจสอบว่ามีชื่อใหม่ไหม ถ้าไม่มีใช้ชื่อเก่า ถ้าไม่มีอีกให้เป็นขีด
$assessor_show = !empty($assessment['nut_fullname'])
    ? htmlspecialchars($assessment['nut_fullname'])
    : htmlspecialchars($assessment['assessor_name'] ?? '.................................................................');

// ตรวจสอบตำแหน่ง
$position_show = !empty($assessment['nut_position'])
    ? htmlspecialchars($assessment['nut_position'])
    : 'นักโภชนาการ';

$assess_timestamp = strtotime($assessment['assessment_datetime']);
// แบบสั้น (สำหรับ Header)
$assess_date_th = date('d/m/', $assess_timestamp) . (date('Y', $assess_timestamp) + 543);
// แบบยาวมีเวลา (สำหรับตารางข้อมูล)
$assess_datetime_th = $assess_date_th . " " . date('H:i', $assess_timestamp) . " น.";

// HTML Structure
$html = '
<style>
    body { font-family: "thsarabunnew"; font-size: 14pt; color: #000; line-height: 1.2; }
    table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: bottom; }
    .info-box { width: 100%; border: 1px solid #000; padding: 10px; margin-bottom: 12px; }
    
    .table-assess th { border: 1px solid #000; background-color: #f5f5f5; padding: 6px; text-align: center; font-weight: bold; font-size: 15pt; }
    .table-assess td { border: 1px solid #000; padding: 6px; vertical-align: top; }
    
    .col-topic { width: 25%; font-weight: bold; }
    .col-detail { width: 40%; }
    .col-score { width: 15%; text-align: center; font-weight: bold; vertical-align: middle; font-size: 16pt; }
    
    .summary-box-outer { border: 1px solid #000; margin-top: 15px; }
    .summary-header { background-color: #f5f5f5; border-bottom: 1px solid #000; padding: 8px; font-weight: bold; text-align: center; font-size: 15pt; }
    
    .score-large { font-size: 32pt; font-weight: bold; line-height: 1; margin-top: 5px; }
    .risk-large { font-size: 18pt; font-weight: bold; margin-top: 5px; }
    .risk-desc { font-size: 13pt; margin-top: 5px; color: #444; }
    
    .signature-section { margin-top: 25px; text-align: center; page-break-inside: avoid; }
    
    .interp-table th, .interp-table td { border: 1px solid #000; padding: 5px; vertical-align: top; }
    .interp-table th { background-color: #f5f5f5; text-align: center; }
    .interp-score { font-weight: bold; text-align: center; display: block; }
    
    .footer-audit { font-size: 11pt; text-align: right; color: #666; margin-top: 10px; font-style: italic; }
</style>

<table class="header-table">
    <tr>
        <td width="15%" align="center" style="vertical-align: middle;">
            <img src="' . $logo_path . '" style="width: 80px; height: auto;">
        </td>
        <td width="70%" align="center">
            <div style="font-size: 20pt; font-weight: bold;">แบบประเมินภาวะโภชนาการ (NAF)</div>
            <div style="font-size: 16pt;">Nutrition Alert Form : โรงพยาบาลกำแพงเพชร</div>
            <div style="font-size: 14pt;">(การประเมินครั้งที่ ' . $assessment_no . ')</div>
        </td>
        <td width="15%" align="right" style="font-size: 12pt;">
            <b>เลขที่เอกสาร:</b> ' . htmlspecialchars($doc_no) . '<br>
            <b>วันที่:</b> ' . htmlspecialchars($assess_date_th) . '
        </td>
    </tr>
</table>

<div class="info-box">
    <table width="100%" style="border-collapse: collapse;">
        <tr style="border-bottom: 1px dotted #ccc;">
            <td width="24%" style="padding-bottom: 5px;">
                <b>ชื่อ-สกุล:</b> ' . $patient_full_name . '
            </td>
            <td width="22%" style="padding-bottom: 5px;">
                <b>อายุ:</b> ' . htmlspecialchars($age) . '
            </td>
            <td width="10%" style="padding-bottom: 5px;">
                <b>เพศ:</b> ' . htmlspecialchars($gender_th) . '
            </td>
            <td width="10%" style="padding-bottom: 5px;">
                <b>HN:</b> ' . htmlspecialchars($assessment['patients_hn']) . '
            </td>
            <td width="15%" style="padding-bottom: 5px;">
                <b>AN:</b> ' . htmlspecialchars($assessment['admissions_an'] ?? '-') . '
            </td>
        </tr>

        <tr style="border-bottom: 1px dotted #ccc;">
            <td colspan="2" style="padding-top: 5px; padding-bottom: 5px;">
                <b>หอผู้ป่วย:</b> ' . htmlspecialchars($assessment['ward_name'] ?? '-') . '
                &nbsp;&nbsp;
                <b>เตียง:</b> ' . htmlspecialchars($assessment['bed_number'] ?? '-') . '
            </td>
            
            <td colspan="2" style="padding-top: 5px; padding-bottom: 5px;">
                <b>วันที่รับเข้ารักษา:</b> ' . htmlspecialchars($admit_date_th) . '
            </td>

            <td style="padding-top: 5px; padding-bottom: 5px;">
                <b>วันที่ประเมิน:</b> ' . htmlspecialchars($assess_datetime_th) . '
            </td>
        </tr>

        <tr>
            <td colspan="2" style="padding-top: 5px;">
                <b>การวินิจฉัยเบื้องต้น:</b> ' . htmlspecialchars($assessment['initial_diagnosis'] ?? '-') . '
            </td>
            <td colspan="3" style="padding-top: 5px;"> <b>ข้อมูลจาก:</b> ' . htmlspecialchars($infoSourceText) . '
            </td>
        </tr>
    </table>
</div>

<table class="table-assess">
    <thead>
        <tr>
            <th class="col-topic">หัวข้อการประเมิน (Assessment Items)</th>
            <th class="col-detail">รายละเอียด (Details)</th>
            <th class="col-score">คะแนน</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="col-topic">1. ส่วนสูง/ ความยาวตัว/ Arm span</td>
            <td class="col-detail">' . $height_detail . '</td>
            <td class="col-score">-</td>
        </tr>
        <tr>
            <td class="col-topic">2. น้ำหนักและค่าดัชนีมวลกาย (BMI)<br><small style="font-weight:normal;">(หรือใช้ผลเลือด Albumin/TLC หากไม่ทราบน้ำหนัก)</small></td>
            <td class="col-detail">' . $q2_detail . '</td>
            <td class="col-score">' . $q2_score . '</td>
        </tr>
        <tr>
            <td class="col-topic">3. รูปร่างของผู้ป่วย</td>
            <td class="col-detail">' . ($assessment['patient_shape_label'] ?? '-') . '</td>
            <td class="col-score">' . ($assessment['patient_shape_score'] ?? 0) . '</td>
        </tr>
        <tr>
            <td class="col-topic">4. น้ำหนักเปลี่ยนใน 4 สัปดาห์</td>
            <td class="col-detail">' . ($assessment['weight_change_4_weeks_label'] ?? '-') . '</td>
            <td class="col-score">' . ($assessment['weight_change_4_weeks_score'] ?? 0) . '</td>
        </tr>
        <tr>
            <td class="col-topic">5. อาหารที่กินในช่วง 2 สัปดาห์ที่ผ่านมา</td>
            <td class="col-detail">
                <b>ลักษณะ:</b> ' . ($assessment['food_type_label'] ?? '-') . '<br/>
                <b>ปริมาณ:</b> ' . ($assessment['food_amount_label'] ?? '-') . '
            </td>
            <td class="col-score">' . (($assessment['food_type_score'] ?? 0) + ($assessment['food_amount_score'] ?? 0)) . '</td>
        </tr>
        <tr>
            <td class="col-topic">6. อาการต่อเนื่อง > 2 สัปดาห์ที่ผ่านมา</td>
            <td class="col-detail">' . $symptom_detail . '</td>
            <td class="col-score">' . $symptom_score_total . '</td>
        </tr>
        <tr>
            <td class="col-topic">7. ความสามารถในการเข้าถึงอาหาร</td>
            <td class="col-detail">' . ($assessment['food_access_label'] ?? '-') . '</td>
            <td class="col-score">' . ($assessment['food_access_score'] ?? 0) . '</td>
        </tr>
        <tr>
            <td class="col-topic">8. โรคที่เป็นอยู่</td>
            <td class="col-detail">' . $disease_detail . '</td>
            <td class="col-score">' . $disease_score_total . '</td>
        </tr>
    </tbody>
</table>

<div class="summary-box-outer">
    <div class="summary-header">สรุปผลการประเมินภาวะโภชนาการ (Assessment Conclusion)</div>
    <table width="100%" cellpadding="10">
        <tr>
            <td width="40%" align="center" style="border-right: 1px solid #000;">
                <div style="font-size: 14pt;">คะแนนรวม (Total Score)</div>
                <div class="score-large">' . ($assessment['total_score'] ?? 0) . '</div>
            </td>
            <td width="60%" align="center">
                <div style="font-size: 14pt;">ระดับความเสี่ยง (Risk Level)</div>
                <div class="risk-large">' . ($assessment['naf_level'] ?? '-') . '</div>
                <div class="risk-desc">(แนวทางการดูแลตามเกณฑ์โภชนบำบัดโรงพยาบาล)</div>
            </td>
        </tr>
    </table>
</div>

<br/>

<table width="100%" class="signature-section">
    <tr>
        <td width="40%"></td>
        <td width="60%" align="center">
            <div style="margin-bottom: 25px;">ลงชื่อ ................................................................. ผู้ประเมิน</div>
            <div style="font-weight: bold; margin-bottom: 5px;">
    ( ' . $assessor_show . ' )
</div>
<div>ตำแหน่ง ' . $position_show . '</div>
        </td>
    </tr>
</table>

<pagebreak>

<div style="margin-top: 15px; font-weight: bold; font-size: 16pt; margin-bottom: 10px;">เกณฑ์การแปลผล (Interpretation Criteria):</div>
<table class="interp-table">
    <thead>
        <tr>
            <th width="15%">คะแนน</th>
            <th width="25%">ระดับความเสี่ยง</th>
            <th width="65%">แนวทางการจัดการ (Management)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td align="center"><span class="interp-score">0 - 5</span></td>
            <td><b>NAF A</b><br>(Normal - Mild Malnutrition)</td>
            <td>ไม่พบความเสี่ยงต่อการเกิดภาวะทุพโภชนาการ พยาบาลจะทำหน้าที่ประเมินภาวะโภชนาการซ้ำภายใน 7 วัน</td>
        </tr>
        <tr>
            <td align="center"><span class="interp-score">6 - 10</span></td>
            <td><b>NAF B</b><br>(Moderate Malnutrition)</td>
            <td>กรุณาแจ้งให้แพทย์และนักกำหนดอาหาร/นักโภชนาการทราบผลทันที พบความเสี่ยงต่อการเกิดภาวะโภชนาการ ให้นักกำหนดอาหาร/นักโภชนาการ ทำการประเมินภาวะโภชนาการและให้แพทย์ทำการดูแลรักษาภายใน 3 วัน</td>
        </tr>
        <tr>
            <td align="center"><span class="interp-score">≥ 11</span></td>
            <td><b>NAF C</b><br>(Severe Malnutrition)</td>
            <td>กรุณาแจ้งให้แพทย์และนักกำหนดอาหาร/นักโภชนาการทราบผลทันทีมีภาวะทุพโภชนาการ ให้นักกำหนดอาหาร/นักโภชนาการทำการประเมินภาวะโภชนาการ และให้แพทย์ทำการดูแลรักษาภายใน 24 ชั่วโมง</td>
        </tr>
    </tbody>
</table>
';

// Generate PDF
$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'fontDir' => array_merge($fontDirs, [__DIR__ . '/fonts']),
    'fontdata' => $fontData + [
        'thsarabunnew' => [
            'R' => 'THSarabunNew.ttf',
            'B' => 'THSarabunNew Bold.ttf',
            'I' => 'THSarabunNew Italic.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf',
        ]
    ],
    'default_font' => 'thsarabunnew',
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
]);

$mpdf->WriteHTML($html);
$mpdf->Output($doc_no . ".pdf", "I");
exit;