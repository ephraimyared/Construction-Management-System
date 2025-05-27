<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

include '../db_connection.php';

$userId = $_POST['userId'] ?? 0;
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');

// Validate inputs
if (empty($firstName) || empty($lastName) || empty($email) || empty($role)) {
    die(json_encode(['success' => false, 'message' => 'All fields are required']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid email format']));
}

// Check if email exists for another user
$stmt = $connection->prepare("SELECT UserId FROM users WHERE Email = ? AND UserId != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die(json_encode(['success' => false, 'message' => 'Email already in use']));
}

// Update user
$stmt = $connection->prepare("UPDATE users SET FirstName=?, LastName=?, Email=?, Role=? WHERE UserId=?");
$stmt->bind_param("ssssi", $firstName, $lastName, $email, $role, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$connection->close();
?>