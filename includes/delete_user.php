<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    die("Unauthorized access!");
}

include '../db_connection.php';

if (!isset($_GET['userId'])) {
    die("Invalid request!");
}

$userId = (int)$_GET['userId'];

// Prevent deleting the current admin
if ($userId === $_SESSION['user_id']) {
    die("You cannot delete your own account while logged in!");
}

// Delete user
$stmt = $connection->prepare("DELETE FROM users WHERE UserId = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    echo "success";
} else {
    error_log("Database error: " . $stmt->error);
    die("Failed to delete user. Please try again.");
}

$stmt->close();
$connection->close();
?>