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
    nutritionist.nutritionist_fullname, 
    nutritionist.nutritionist_position, 
    patient.patient_firstname, 
    patient.patient_lastname, 
    patient.patient_gender, 
    patient.patient_dob, 
    patient.patient_hn,
    
    admissions.admit_datetime,
    admissions.bed_number,
    
    ward.ward_name,
    
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
JOIN patient ON patient.patient_hn = nutrition_assessment.patient_hn
JOIN admissions ON admissions.admissions_an = nutrition_assessment.admissions_an
LEFT JOIN ward ON ward.ward_id = admissions.ward_id 
LEFT JOIN nutritionist ON nutrition_assessment.nutritionist_id = nutritionist.nutritionist_id

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
$g_code = strtoupper($assessment['patient_gender'] ?? '');
if ($g_code == 'M' || $g_code == '1' || $g_code == 'CHAI') {
    $gender_th = 'ชาย';
} elseif ($g_code == 'F' || $g_code == '2' || $g_code == 'YING') {
    $gender_th = 'หญิง';
} else {
    $gender_th = htmlspecialchars($assessment['patient_gender'] ?? '-');
}

// ข้อมูลส่วนสูง
$height_detail_parts = [];
if (!empty($assessment['height_measure']) && $assessment['height_measure'] > 0) {
    $height_detail_parts[] = "วัดส่วนสูง: " . $assessment['height_measure'] . " ซม.";
}
if (!empty($assessment['body_length']) && $assessment['body_length'] > 0) {
    $height_detail_parts[] = "วัดความยาวตัว: " . $assessment['body_length'] . " ซม.";
}
if (!empty($assessment['arm_span']) && $assessment['arm_span'] > 0) {
    $height_detail_parts[] = "Arm span: " . $assessment['arm_span'] . " ซม.";
}
if (!empty($assessment['height_relative']) && $assessment['height_relative'] > 0) {
    $height_detail_parts[] = "ญาติบอก: " . $assessment['height_relative'] . " ซม.";
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
if (!empty($assessment['patient_dob'])) {
    $diff = date_diff(date_create($assessment['patient_dob']), date_create('today'));
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
$hn = $assessment['patient_hn'];
$sql_seq = "SELECT doc_no FROM nutrition_assessment WHERE patient_hn = :hn ORDER BY nutrition_assessment_datetime ASC";
$stmt_seq = $conn->prepare($sql_seq);
$stmt_seq->execute([':hn' => $hn]);
$all_docs = $stmt_seq->fetchAll(PDO::FETCH_COLUMN);
$key = array_search($doc_no, $all_docs);
$assessment_no = ($key !== false) ? $key + 1 : 1;

$patient_full_name = htmlspecialchars($assessment['patient_firstname'] . ' ' . $assessment['patient_lastname']);
$admit_date_th = date('d/m/', strtotime($assessment['admit_datetime'])) . (date('Y', strtotime($assessment['admit_datetime'])) + 543);


// ตรวจสอบว่ามีชื่อใหม่ไหม ถ้าไม่มีใช้ชื่อเก่า ถ้าไม่มีอีกให้เป็นขีด
$assessor_show = !empty($assessment['nutritionist_fullname'])
    ? htmlspecialchars($assessment['nutritionist_fullname'])
    : htmlspecialchars($assessment['assessor_name'] ?? '.................................................................');

// ตรวจสอบตำแหน่ง
$position_show = !empty($assessment['nutritionist_position'])
    ? htmlspecialchars($assessment['nutritionist_position'])
    : 'นักโภชนาการ';

// ดึงลายเซ็นนักโภชนาการ
$signature_html = '';
try {
    $stmt_sig = $conn->prepare("
        SELECT nutritionist_signature_type, nutritionist_signature_data 
        FROM nutritionist_signature 
        WHERE nutritionist_id = :nutritionist_id 
        LIMIT 1
    ");
    $stmt_sig->execute([':nutritionist_id' => $assessment['nutritionist_id']]);
    $signature = $stmt_sig->fetch(PDO::FETCH_ASSOC);

    if ($signature && !empty($signature['nutritionist_signature_data'])) {
        $img_src = 'data:image/png;base64,' . $signature['nutritionist_signature_data'];

        // กำหนดขนาดรูปภาพ
        $signature_html = '<img src="' . $img_src . '" style="height: 40px; width: auto; display: block; margin: 0 auto;">';
    } else {
        // กรณีไม่มีลายเซ็นในระบบ ให้เว้นว่าง
        $signature_html = '';
    }
} catch (PDOException $e) {
    error_log("Error fetching signature: " . $e->getMessage());
}

$assess_timestamp = strtotime($assessment['nutrition_assessment_datetime']);
// แบบสั้น
$assess_date_th = date('d/m/', $assess_timestamp) . (date('Y', $assess_timestamp) + 543);
// แบบยาวมีเวลา
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
    
    .signature-section { margin-top: 40px; text-align: center; page-break-inside: avoid; }
    
    .interp-table th, .interp-table td { border: 1px solid #000; padding: 5px; vertical-align: top; }
    .interp-table th { background-color: #f5f5f5; text-align: center; }
    .interp-score { font-weight: bold; text-align: center; display: block; }
    
    .footer-audit { font-size: 11pt; text-align: right; color: #666; margin-top: 10px; font-style: italic; }
</style>

<table class="header-table" style="margin-bottom: 5px;">
    <tr>
        <td width="65%" align="left" style="vertical-align: middle;">
            <table width="100%">
                <tr>
                    <td width="75px" style="vertical-align: middle;">
                        <img src="' . $logo_path . '" style="width: 70px; height: auto;">
                    </td>
                    <td align="left" style="padding-left: 10px; vertical-align: middle;">
                        <div style="font-size:16pt; line-height:1.1;">โรงพยาบาลกำแพงเพชร</div>
                        <div style="font-size: 16pt; line-height: 1.1;">แบบประเมินภาวะโภชนาการ</div>
                        <div style="font-size: 16pt; line-height: 1.1;">Nutrition Alert Form</div>
                        <div style="font-size: 16pt; line-height: 1.1;">(การประเมินครั้งที่ ' . $assessment_no . ')</div>
                    </td>
                </tr>
            </table>
        </td>

        <td width="48%" align="right" style="vertical-align: top;">
    <div style="border: 1px solid #000; padding: 8px 12px; border-radius: 5px; background-color: #fafafa; width: 260px; display: inline-block;">
        <table width="100%" style="font-size: 14pt; line-height: 1.2;">
            <tr>
                <td colspan="2" align="left"><b>ชื่อ - สกุล:</b> ' . htmlspecialchars($patient_full_name) . '</td>
            </tr>
            <tr>
                <td width="55%" align="left"><b>HN:</b> ' . htmlspecialchars($assessment['patient_hn'] ?? '-') . '</td>
                <td width="45%" align="left"><b>AN:</b> ' . htmlspecialchars($assessment['admissions_an'] ?? '-') . '</td>
            </tr>
            <tr>
                <td align="left"><b>อายุ:</b> ' . htmlspecialchars($age ?? '-') . '</td>
                <td align="left"><b>เพศ:</b> ' . htmlspecialchars($gender_th ?? '-') . '</td>
            </tr>
            <tr>
                <td align="left"><b>หอผู้ป่วย:</b> ' . htmlspecialchars($assessment['ward_name'] ?? '-') . '</td>
                <td align="left"><b>เตียง:</b> ' . htmlspecialchars($assessment['bed_number'] ?? '-') . '</td>
            </tr>
        </table>
    </div>
</td>
    </tr>
</table>

<div class="info-box" style="padding: 0;">
    <div style="background-color: #f2f2f2; border-bottom: 1px solid #000; padding: 3px 10px; font-weight: bold; font-size: 14pt;">
        ส่วนที่ 1: ข้อมูลแรกรับและการวินิจฉัย (Admission & Clinical Data)
    </div>
    
    <div style="padding: 8px;">
        <table width="100%" style="border-collapse: collapse; line-height: 1.2;">
            <tr>
                <td width="33%" style="padding: 2px 4px;">
                    <b>ข้อมูลจาก:</b> ' . htmlspecialchars($infoSourceText ?? '-') . '
                </td>
                <td width="33%" style="padding: 2px 4px;">
                    <b>วันที่ Admit:</b> ' . htmlspecialchars($admit_date_th ?? '-') . '
                </td>
                <td width="34%" style="padding: 2px 4px;">
                    <b>วันที่ประเมิน:</b> ' . htmlspecialchars($assess_datetime_th ?? '-') . '
                </td>
            </tr>
            
            <tr>
                <td colspan="3" style="padding: 6px 4px; vertical-align: top;">
                    <div style="border-top: 1px dotted #ccc; margin-top: 5px; padding-top: 5px;">
                        <b>การวินิจฉัยเบื้องต้น (Initial Diagnosis):</b>
                        <div style="margin-top: 3px; font-size: 13pt;">
                            ' . htmlspecialchars($assessment['initial_diagnosis'] ?? '-') . '
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
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

<table width="100%" class="signature-section" style="margin-top: 20px;">
    <tr>
        <td width="40%"></td>
        
        <td width="60%" align="center" style="vertical-align: bottom;">
            
            <div style="height: 50px; display: flex; align-items: end; justify-content: center;">
                ' . $signature_html . '
            </div>
            
            <div style="margin-top: 5px;">ลงชื่อ ................................................................. ผู้ประเมิน</div>
            
            <div style="margin-top: 5px;">
                ( ' . $assessor_show . ' )
            </div>
            
            <div>ตำแหน่ง ' . $position_show . '</div>
            <div style="margin-top: 5px; font-size: 12pt;">วันที่พิมพ์: ' . date('d/m/') . (date('Y') + 543) . date(' H:i') . ' น.</div>
            </td>
    </tr>
</table>

<div style="position: absolute; bottom: 5px; width: 100%; border-top: 1px solid #000; padding-top: 3px; font-size: 10pt;">
    <table width="100%">
        <tr>
            <td width="40%">เลขที่ใบงาน: ' . htmlspecialchars($doc_no) . '</td>
            <td width="60%" align="right">ฝ่ายโภชนศึกษาและโภชนบำบัด โรงพยาบาลกำแพงเพชร</td>
        </tr>
    </table>
</div>

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

<div style="position: absolute; bottom: 5px; width: 100%; border-top: 1px solid #000; padding-top: 3px; font-size: 10pt;">
    <table width="100%">
        <tr>
            <td width="40%">เลขที่ใบงาน: ' . htmlspecialchars($doc_no) . '</td>
            <td width="60%" align="right">ฝ่ายโภชนศึกษาและโภชนบำบัด โรงพยาบาลกำแพงเพชร</td>
        </tr>
    </table>
</div>
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
