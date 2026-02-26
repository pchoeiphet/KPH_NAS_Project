<?php
/**
 * KPH NAS - Code Patterns & Examples
 * PHP Coding Standards & Architecture Patterns
 * Version: 1.0 (February 26, 2026)
 */

// ============================================================
// PATTERN 1: Database Connection (connect_db.php pattern)
// ============================================================

class DatabaseConnection {
    
    public static function getInstance() {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "kph_nas_db";
        
        try {
            $conn = new PDO(
                "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );
            
            // Set error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set fetch mode
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $conn;
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Connection failed. Please contact administrator.");
        }
    }
}

// Usage: $conn = DatabaseConnection::getInstance();


// ============================================================
// PATTERN 2: Session Management
// ============================================================

class SessionManager {
    
    public static function initializeSession() {
        session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['nutritionist_id'])) {
            header("Location: login.php");
            exit;
        }
    }
    
    public static function setUserSession($nutritionist_data) {
        $_SESSION['nutritionist_id'] = $nutritionist_data['nutritionist_id'];
        $_SESSION['nutritionist_fullname'] = $nutritionist_data['nutritionist_fullname'];
        $_SESSION['nutritionist_username'] = $nutritionist_data['nutritionist_username'];
        $_SESSION['is_admin'] = $nutritionist_data['is_admin'];
        $_SESSION['login_time'] = time();
    }
    
    public static function requireAdmin() {
        self::initializeSession();
        if ($_SESSION['is_admin'] != 1) {
            header("Location: index.php?error=unauthorized");
            exit;
        }
    }
    
    public static function logout() {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}


// ============================================================
// PATTERN 3: Input Validation & Sanitization
// ============================================================

class InputValidator {
    
    /**
     * Validate patient HN (Hospital Number)
     */
    public static function validateHN($hn) {
        $hn = trim($hn);
        
        if (empty($hn)) {
            throw new Exception("HN cannot be empty");
        }
        
        if (strlen($hn) > 20) {
            throw new Exception("HN is too long");
        }
        
        if (!preg_match('/^[0-9]+$/', $hn)) {
            throw new Exception("HN must contain numbers only");
        }
        
        return $hn;
    }
    
    /**
     * Validate Admission Number (AN)
     */
    public static function validateAN($an) {
        $an = trim($an);
        
        if (empty($an)) {
            throw new Exception("AN cannot be empty");
        }
        
        if (!preg_match('/^[0-9]+$/', $an)) {
            throw new Exception("AN must contain numbers only");
        }
        
        return $an;
    }
    
    /**
     * Validate Height (cm)
     */
    public static function validateHeight($height) {
        $height = floatval($height);
        
        if ($height < 50 || $height > 250) {
            throw new Exception("Height must be between 50-250 cm");
        }
        
        return $height;
    }
    
    /**
     * Validate Weight (kg)
     */
    public static function validateWeight($weight) {
        $weight = floatval($weight);
        
        if ($weight < 10 || $weight > 300) {
            throw new Exception("Weight must be between 10-300 kg");
        }
        
        return $weight;
    }
    
    /**
     * Validate Date Format (YYYY-MM-DD)
     */
    public static function validateDate($date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception("Invalid date format");
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        
        if (!$d || $d->format('Y-m-d') !== $date) {
            throw new Exception("Date is not valid");
        }
        
        return $date;
    }
}


// ============================================================
// PATTERN 4: BMI Calculation & Scoring
// ============================================================

class BMICalculator {
    
    /**
     * Calculate BMI from height and weight
     * BMI = weight (kg) / (height (m))^2
     */
    public static function calculateBMI($weight_kg, $height_cm) {
        if ($height_cm <= 0) {
            return null;
        }
        
        $height_m = $height_cm / 100;
        $bmi = $weight_kg / ($height_m * $height_m);
        
        return round($bmi, 2);
    }
    
    /**
     * Get BMI score for risk assessment
     */
    public static function getBMIScore($bmi) {
        if ($bmi < 17) {
            return 2;  // High risk
        } elseif ($bmi >= 17 && $bmi <= 20) {
            return 1;  // Moderate risk
        } else {
            return 0;  // Low risk
        }
    }
    
    /**
     * Get BMI category
     */
    public static function getBMICategory($bmi) {
        if ($bmi < 18.5) {
            return 'Underweight';
        } elseif ($bmi >= 18.5 && $bmi < 25) {
            return 'Normal weight';
        } elseif ($bmi >= 25 && $bmi < 30) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }
}


// ============================================================
// PATTERN 5: NAF Score Calculation
// ============================================================

class NAFScoreCalculator {
    
    /**
     * Calculate NAF Score and Level
     */
    public static function calculateNAF($bmi_score, $lab_score, $food_type_score,
                                       $food_amount_score, $food_access_score,
                                       $diseases_score = 0) {
        
        $total_score = $bmi_score + $lab_score + $food_type_score +
                      $food_amount_score + $food_access_score + $diseases_score;
        
        // Determine NAF Level
        $naf_level = self::getNAFLevel($total_score);
        
        return [
            'total_score' => $total_score,
            'naf_level' => $naf_level,
            'breakdown' => [
                'bmi' => $bmi_score,
                'lab' => $lab_score,
                'food_type' => $food_type_score,
                'food_amount' => $food_amount_score,
                'food_access' => $food_access_score,
                'diseases' => $diseases_score
            ]
        ];
    }
    
    /**
     * Get NAF Level based on total score
     */
    private static function getNAFLevel($score) {
        if ($score >= 20) {
            return 'NAF C';  // High Risk
        } elseif ($score >= 12) {
            return 'NAF B';  // Moderate Risk
        } else {
            return 'NAF A';  // Low Risk
        }
    }
    
    /**
     * Get Lab Score (Albumin or TLC)
     */
    public static function getLabScore($lab_method, $lab_value) {
        if ($lab_method == 'Albumin') {
            if ($lab_value < 2.5) {
                return 3;  // High risk
            } elseif ($lab_value >= 2.5 && $lab_value < 3.0) {
                return 1;
            } else {
                return 0;
            }
        } elseif ($lab_method == 'TLC') {
            // TLC values
            if ($lab_value < 800) {
                return 3;
            } elseif ($lab_value >= 800 && $lab_value < 1200) {
                return 1;
            } else {
                return 0;
            }
        }
        
        return 0;
    }
}


// ============================================================
// PATTERN 6: Database Query Helper
// ============================================================

class NuritionAssessmentQuery {
    
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get patient info with active admission
     */
    public function getPatientWithActiveAdmission($patient_hn) {
        $sql = "SELECT 
                    p.patient_id,
                    p.patient_hn,
                    p.patient_name,
                    p.patient_dob,
                    a.admissions_id,
                    a.admissions_an,
                    a.ward_id,
                    a.admit_datetime,
                    w.ward_name
                FROM patients p
                INNER JOIN admissions a ON p.patient_id = a.patient_id
                LEFT JOIN ward w ON a.ward_id = w.ward_id
                WHERE p.patient_hn = ? 
                  AND a.discharge_datetime IS NULL
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$patient_hn]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get screening history for patient
     */
    public function getScreeningHistory($patient_hn, $limit = 10) {
        $sql = "SELECT 
                    ns.nutrition_screening_id,
                    ns.doc_no,
                    ns.nutrition_screening_datetime,
                    ns.height,
                    ns.weight,
                    ns.bmi,
                    nt.nutritionist_fullname
                FROM nutrition_screening ns
                JOIN nutritionist nt ON ns.nutritionist_id = nt.nutritionist_id
                WHERE ns.patient_hn = ?
                ORDER BY ns.nutrition_screening_datetime DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$patient_hn, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assessment history
     */
    public function getAssessmentHistory($patient_hn, $limit = 10) {
        $sql = "SELECT 
                    na.nutrition_assessment_id,
                    na.doc_no,
                    na.nutrition_assessment_datetime,
                    na.weight,
                    na.bmi,
                    na.total_score,
                    na.naf_level,
                    nt.nutritionist_fullname
                FROM nutrition_assessment na
                JOIN nutritionist nt ON na.nutritionist_id = nt.nutritionist_id
                WHERE na.patient_hn = ?
                ORDER BY na.nutrition_assessment_datetime DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$patient_hn, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Save screening assessment
     */
    public function saveScreening($data) {
        $sql = "INSERT INTO nutrition_screening 
                (doc_no, admissions_an, patient_hn, nutritionist_id,
                 nutrition_screening_datetime, nutrition_screening_seq,
                 height, weight, bmi, initial_diagnosis)
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['doc_no'],
            $data['admissions_an'],
            $data['patient_hn'],
            $data['nutritionist_id'],
            $data['seq'] ?? 1,
            $data['height'],
            $data['weight'],
            $data['bmi'],
            $data['diagnosis'] ?? null
        ]);
        
        if ($result) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get diseases for selection
     */
    public function getActiveDiseases() {
        $sql = "SELECT * FROM disease WHERE is_active = 1 
                ORDER BY disease_type DESC, disease_name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get food options
     */
    public function getFoodOptions() {
        return [
            'types' => $this->getFoodTypes(),
            'amounts' => $this->getFoodAmounts(),
            'access' => $this->getFoodAccess()
        ];
    }
    
    private function getFoodTypes() {
        $sql = "SELECT * FROM food_type WHERE is_active = 1 ORDER BY food_type_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getFoodAmounts() {
        $sql = "SELECT * FROM food_amount WHERE is_active = 1 ORDER BY food_amount_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getFoodAccess() {
        $sql = "SELECT * FROM food_access WHERE is_active = 1 ORDER BY food_access_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


// ============================================================
// PATTERN 7: Document Generation (PDF)
// ============================================================

class ReportGenerator {
    
    private $conn;
    private $mpdf;
    
    public function __construct($conn) {
        $this->conn = $conn;
        require_once 'vendor/autoload.php';
        $this->mpdf = new \Mpdf\Mpdf();
    }
    
    /**
     * Generate Screening Report PDF
     */
    public function generateScreeningPDF($screening_id, $filename = null) {
        try {
            // Fetch data
            $sql = "SELECT 
                        ns.*,
                        p.patient_name,
                        p.patient_hn,
                        a.admissions_an
                    FROM nutrition_screening ns
                    JOIN admissions a ON ns.admissions_an = a.admissions_an
                    JOIN patients p ON a.patient_id = p.patient_id
                    WHERE ns.nutrition_screening_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$screening_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                throw new Exception("Screening record not found");
            }
            
            // Build HTML
            $html = $this->buildScreeningHTML($data);
            
            // Generate PDF
            $this->mpdf->WriteHTML($html);
            
            // Output
            $filename = $filename ?? 'Screening_' . $data['doc_no'] . '.pdf';
            $this->mpdf->Output($filename, 'D');
            
        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function buildScreeningHTML($data) {
        $assessment_date = date('d/m/Y H:i', strtotime($data['nutrition_screening_datetime']));
        
        return "
        <style>
            body { font-family: dejaVuSansCondensed; font-size: 11px; }
            h2 { text-align: center; margin: 10px 0; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 5px; border: 1px solid #999; }
            .label { font-weight: bold; width: 30%; }
            .section { margin: 15px 0; border-top: 2px solid #000; padding-top: 10px; }
        </style>
        
        <h2>NUTRITION SCREENING REPORT</h2>
        <p style='text-align: center;'>King Prajadhipok Hospital</p>
        
        <div class='section'>
            <h3>Patient Information</h3>
            <table>
                <tr>
                    <td class='label'>HN:</td>
                    <td>{$data['patient_hn']}</td>
                    <td class='label'>Name:</td>
                    <td>{$data['patient_name']}</td>
                </tr>
                <tr>
                    <td class='label'>AN:</td>
                    <td>{$data['admissions_an']}</td>
                    <td class='label'>Assessment Date:</td>
                    <td>{$assessment_date}</td>
                </tr>
            </table>
        </div>
        
        <div class='section'>
            <h3>Measurements</h3>
            <table>
                <tr>
                    <td class='label'>Height (cm):</td>
                    <td>{$data['height']}</td>
                    <td class='label'>Weight (kg):</td>
                    <td>{$data['weight']}</td>
                </tr>
                <tr>
                    <td class='label'>BMI:</td>
                    <td>{$data['bmi']}</td>
                    <td class='label'>Weight Loss Period:</td>
                    <td>{$data['weight_loss_period']}</td>
                </tr>
            </table>
        </div>
        
        <p style='margin-top: 50px; border-top: 1px solid #000; padding-top: 10px;'>
            <strong>Signature:</strong> ___________________
        </p>";
    }
}


// ============================================================
// PATTERN 8: Error Handling & Response
// ============================================================

class APIResponse {
    
    public static function success($data, $message = "Success") {
        self::sendJSON([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($error_msg, $error_code = "ERROR") {
        self::sendJSON([
            'success' => false,
            'error' => $error_msg,
            'code' => $error_code
        ], 400);
    }
    
    public static function validation($errors) {
        self::sendJSON([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
    
    private static function sendJSON($data, $http_code = 200) {
        header('Content-Type: application/json');
        http_response_code($http_code);
        echo json_encode($data);
        exit;
    }
}


// ============================================================
// EXAMPLE USAGE
// ============================================================

/*

// Initialize database
$conn = DatabaseConnection::getInstance();

// Initialize session
SessionManager::initializeSession();

// Validate input
try {
    $hn = InputValidator::validateHN($_POST['patient_hn'] ?? '');
    $weight = InputValidator::validateWeight($_POST['weight'] ?? 0);
    $height = InputValidator::validateHeight($_POST['height'] ?? 0);
} catch (Exception $e) {
    APIResponse::error($e->getMessage());
}

// Calculate BMI
$bmi = BMICalculator::calculateBMI($weight, $height);
$bmi_score = BMICalculator::getBMIScore($bmi);

// Calculate NAF
$naf = NAFScoreCalculator::calculateNAF(
    $bmi_score,           // BMI Score
    0,                    // Lab Score
    1,                    // Food Type Score
    0,                    // Food Amount Score
    2,                    // Food Access Score
    3                     // Disease Score
);

// Query data
$query = new NuritionAssessmentQuery($conn);
$patient = $query->getPatientWithActiveAdmission($hn);

// Generate PDF
$report = new ReportGenerator($conn);
$report->generateScreeningPDF($screening_id);

// Return response
APIResponse::success(['screening_id' => $screening_id], "Screening saved successfully");

*/

?>
