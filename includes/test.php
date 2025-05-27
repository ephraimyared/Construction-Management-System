<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simulate a successful login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Admin';

echo "<h1>Session Test</h1>";
echo "<p>Session ID: ".session_id()."</p>";
echo "<pre>Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Test redirect logic
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    die("<p style='color:red'>Would redirect to role_dashboard.php because: "
        .(!isset($_SESSION['user_id']) ? "No user_id" : "Role is ".$_SESSION['user_role'])."</p>");
} else {
    die("<p style='color:green'>All checks passed - would show manager page</p>");
}
?>