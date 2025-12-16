document.addEventListener('DOMContentLoaded', function() {
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiDisplay = document.getElementById('bmiDisplay');
    
    // Radio buttons for Q3 (BMI Question) - Disabled for manual input
    const q3Yes = document.getElementById('q3_yes');
    const q3No = document.getElementById('q3_no');
    
    // All input radios for scoring
    const allRadios = document.querySelectorAll('.score-input');
    const resultBox = document.getElementById('resultBox');
    const resultText = document.getElementById('resultText');
    const actionText = document.getElementById('actionText');

    // --- 1. ฟังก์ชันคำนวณ BMI และ Auto-Check ข้อ 3 ---
    function calculateBMI() {
        const weight = parseFloat(weightInput.value);
        const heightCm = parseFloat(heightInput.value);

        if (weight > 0 && heightCm > 0) {
            const heightM = heightCm / 100;
            const bmi = weight / (heightM * heightM);
            bmiDisplay.innerText = bmi.toFixed(2);

            // Logic ข้อ 3: BMI < 18.5 หรือ >= 25.0
            if (bmi < 18.5 || bmi >= 25.0) {
                q3Yes.checked = true;
            } else {
                q3No.checked = true;
            }
        } else {
            bmiDisplay.innerText = "-";
            q3No.checked = true; // Default to No if no data
        }
        calculateScore(); // Recalculate total score
    }

    // --- 2. ฟังก์ชันคำนวณคะแนนรวม ---
    function calculateScore() {
        let yesCount = 0;

        // วนลูปเช็คคำตอบข้อ 1, 2, 4 (จากผู้ใช้) และ 3 (จากระบบ)
        // ข้อ 1
        if (document.querySelector('input[name="q1"]:checked')?.value === '1') yesCount++;
        // ข้อ 2
        if (document.querySelector('input[name="q2"]:checked')?.value === '1') yesCount++;
        // ข้อ 3 (Auto)
        if (q3Yes.checked) yesCount++;
        // ข้อ 4
        if (document.querySelector('input[name="q4"]:checked')?.value === '1') yesCount++;

        displayResult(yesCount);
    }

    // --- 3. แสดงผลลัพธ์ (ตามตารางในรูป) ---
    function displayResult(count) {
        // Reset classes
        resultBox.classList.remove('result-green', 'result-red');

        if (count >= 2) {
            // High Risk
            resultBox.classList.add('result-red');
            resultText.innerText = `พบความเสี่ยง ${count} ข้อ (ตอบ "ใช่" ≥ 2 ข้อ)`;
            actionText.innerHTML = "🔴 ทำการประเมินภาวะโภชนาการต่อ <br> หรือปรึกษานักกำหนดอาหาร/ทีมโภชนบำบัด";
        } else {
            // Low Risk
            resultBox.classList.add('result-green');
            resultText.innerText = `ความเสี่ยงต่ำ ${count} ข้อ (ตอบ "ใช่" ≤ 1 ข้อ)`;
            actionText.innerHTML = "🟢 ให้คัดกรอง ซ้ำสัปดาห์ละ 1 ครั้ง ในช่วงที่อยู่โรงพยาบาล";
        }
    }

    // Event Listeners
    weightInput.addEventListener('input', calculateBMI);
    heightInput.addEventListener('input', calculateBMI);

    // Listen to all radio changes to update score immediately
    allRadios.forEach(radio => {
        radio.addEventListener('change', calculateScore);
    });

    // Initial check
    calculateScore();
});