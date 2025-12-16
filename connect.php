<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kph_naf_db";

try {
    // ต้องเป็นบรรทัดนี้
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>