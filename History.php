<?php
session_start();
include('db.php');

// Redirect if not a logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: Login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch medicine history (not today's medicines) with status Taken or Missed
$sql = "SELECT DATE(intake_time) as intake_date, status 
        FROM medicines 
        WHERE patient_id = ? 
          AND DATE(intake_time) < CURDATE() 
          AND (status = 'Taken' OR status = 'Missed')
        ORDER BY intake_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

$stmt->close();
$conn->close();
?>
