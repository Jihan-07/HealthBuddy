<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_SESSION['user_id'])) {
    // Validate and sanitize input
    if (!isset($_POST['medicine_id'], $_POST['status'])) {
        echo "Missing required fields.";
        exit();
    }

    $medicine_id = intval($_POST['medicine_id']);
    $status = trim($_POST['status']);
    $user_id = $_SESSION['user_id'];

    // Check if medicine_id is valid
    if ($medicine_id <= 0 || empty($status)) {
        echo "Invalid medicine ID or status.";
        exit();
    }

    // Prepare and execute query
    $stmt = $conn->prepare("UPDATE medicines SET status = ?, updated_at = NOW() WHERE id = ? AND patient_id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        exit();
    }

    $stmt->bind_param("sii", $status, $medicine_id, $user_id);

    if ($stmt->execute()) {
        header("Location: patient_dashboard.php");
        exit();
    } else {
        echo "Failed to update status: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: Login.php");
    exit();
}
