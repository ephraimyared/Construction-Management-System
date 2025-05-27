<?php
session_start();
ob_start();
include 'db_connection.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Helper function for password verification
function verifyPassword($input_password, $stored_hash) {
    if (password_verify($input_password, $stored_hash)) return true;
    if (strlen($stored_hash) == 32 && md5($input_password) === $stored_hash) return true;
    if ($input_password === $stored_hash) return true;
    return false;
}

// Helper function for upgrading old hashes
function upgradePasswordHash($password, $current_hash, $user_id) {
    global $connection;
    if (password_needs_rehash($current_hash, PASSWORD_BCRYPT) || strlen($current_hash) < 60) {
        $new_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $connection->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        return $stmt->execute();
    }
    return false;
}

// Map user role to dashboard URL
function getDashboardUrl($role) {
    $role = strtolower(trim($role));
    $mapping = [
        'admin' => 'includes/AdminDashboard.php',
        'project manager' => 'includes/ManagerDashboard.php',
        'contractor' => 'includes/ContractorDashboard.php',
        'consultant' => 'includes/ConsultantDashboard.php',
        'site engineer' => 'includes/SiteEngineerDashboard.php',
        'employee' => 'includes/EmployeeDashboard.php'
    ];
    return $mapping[$role] ?? 'login.php';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($connection->real_escape_string($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required");
        }

        // Check if is_active column exists, if not, add it
        $check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        if ($check_column->num_rows == 0) {
            $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
        }

        // Modified query to check for active accounts
        $stmt = $connection->prepare("SELECT * FROM users WHERE Username = ? AND (is_active = 1 OR is_active IS NULL)");
        if (!$stmt) {
            throw new Exception("Database error: " . $connection->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (verifyPassword($password, $user['Password'])) {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_role'] = $user['Role'];

                upgradePasswordHash($password, $user['Password'], $user['UserID']);
                session_regenerate_id(true);
                
                // Log successful login
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 1)");
                $log_stmt->bind_param("ss", $username, $ip);
                $log_stmt->execute();
                
                // Update last login time
                $update_stmt = $connection->prepare("UPDATE users SET LastLogin = NOW() WHERE UserID = ?");
                $update_stmt->bind_param("i", $user['UserID']);
                $update_stmt->execute();
                
                ob_end_clean();
                header("Location: " . getDashboardUrl($user['Role']));
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid username or password.";
                
                // Log failed login attempt
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
                $log_stmt->bind_param("ss", $username, $ip);
                $log_stmt->execute();
                
                header("Location: login.php");
                exit();
            }
        } else {
            // Check if the account exists but is deactivated
            $check_stmt = $connection->prepare("SELECT * FROM users WHERE Username = ? AND is_active = 0");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 1) {
                $_SESSION['login_error'] = "Your account has been deactivated. Please contact an administrator.";
            } else {
                $_SESSION['login_error'] = "Invalid username or password.";
            }
            
            // Log failed login attempt
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
            $log_stmt->bind_param("ss", $username, $ip);
            $log_stmt->execute();
            
            header("Location: login.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = "Login failed: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
}

$connection->close();
?>
