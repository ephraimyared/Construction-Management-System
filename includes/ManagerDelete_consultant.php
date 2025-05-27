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
    $consultant_id = $_GET['id'];

    // Use prepared statement
    $stmt = $connection->prepare("DELETE FROM users WHERE UserID = ? AND Role = 'Consultant'");
    $stmt->bind_param("i", $consultant_id);

    if ($stmt->execute()) {
        $stmt->close();
        $connection->close();
        header("Location: manager_manage_consultant.php");
        exit();
    } else {
        die("Error deleting consultant: " . $stmt->error);
    }
} else {
    die("Invalid consultant ID.");
}
?>
