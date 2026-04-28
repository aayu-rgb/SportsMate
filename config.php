<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbName = "sportsmate_db";
$portsToTry = [3306, 3307];

$conn = null;
foreach ($portsToTry as $port) {
    $candidate = @new mysqli($host, $user, $pass, $dbName, $port);
    if (!$candidate->connect_errno) {
        $conn = $candidate;
        break;
    }
}

if (!$conn) {
    http_response_code(500);
    die("Database connection failed. Start MySQL in XAMPP and verify database 'sportsmate_db' exists.");
}
?>
