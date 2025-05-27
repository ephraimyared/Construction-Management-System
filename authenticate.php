<?php
session_start();
require 'db_connection.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: role_selection.php?error=invalid_request");
    exit();
}

// Get and sanitize inputs
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$selectedRole = trim($_POST['role'] ?? '');

// Validate inputs
if (empty($username) || empty($password) || empty($selectedRole)) {
    header("Location: role_selection.php?error=empty_fields");
    exit();
}

// Prepare SQL query with prepared statement
$query = "SELECT * FROM users WHERE Username = ?";
$stmt = $connection->prepare($query);

if (!$stmt) {
    header("Location: role_selection.php?error=db_error");
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 0) {
    header("Location: role_selection.php?error=invalid_credentials");
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['Password'])) {
    header("Location: role_selection.php?error=invalid_credentials");
    exit();
}

// Verify selected role matches user's actual role
if (strtolower(trim($user['Role'])) !== strtolower($selectedRole)) {
    header("Location: role_selection.php?error=role_mismatch");
    exit();
}

// Authentication successful - create session
$_SESSION['user'] = [
    'id' => $user['UserID'],
    'username' => $user['Username'],
    'firstname' => $user['Firstname'] ?? '',
    'lastname' => $user['Lastname'] ?? '',
    'email' => $user['Email'] ?? '',
    'role' => $user['Role'],
    'loggedin' => true,
    'last_login' => time()
];

// Redirect to appropriate dashboard
require_once 'getDashboardUrl.php'; // File containing your getDashboardUrl() function
$dashboardUrl = getDashboardUrl($user['Role']);

// Update last login time (optional)
$updateQuery = "UPDATE users SET LastLogin = NOW() WHERE userID = ?";
$updateStmt = $connection->prepare($updateQuery);
$updateStmt->bind_param("i", $user['userID']);
$updateStmt->execute();

header("Location: $dashboardUrl");
exit();
?>