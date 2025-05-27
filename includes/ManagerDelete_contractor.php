<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php';

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Validate and sanitize input
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $contractor_id = $_GET['id'];

    // Prepare and execute the deletion query safely
    $stmt = $connection->prepare("DELETE FROM users WHERE UserID = ? AND Role = 'Contractor'");
    $stmt->bind_param("i", $contractor_id);

    if ($stmt->execute()) {
        $stmt->close();
        $connection->close();
        header("Location: manager_manage_contractor.php");
        exit();
    } else {
        die("Error deleting contractor: " . $stmt->error);
    }
} else {
    die("Invalid contractor ID.");
}
?>
