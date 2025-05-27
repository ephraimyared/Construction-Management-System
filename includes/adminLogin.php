<?php
session_start();
// Corrected the include path to go one directory up to reach db_connection.php
include("../db_connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Check if is_active column exists, if not, add it
    $check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($check_column->num_rows == 0) {
        $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
    }

    // Modified query to check for active accounts
    $query = "SELECT * FROM users WHERE Username=? AND (is_active = 1 OR is_active IS NULL)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); // Fetch user details

        // Verify hashed password
        if (password_verify($password, $row['Password'])) {
            // Check if the user is Admin
            if ($row['Role'] === 'Admin') {
                // Set session variables for Admin
                $_SESSION["username"] = $row["Username"];
                $_SESSION["user_role"] = $row["Role"];  // Set the user role as Admin
                $_SESSION["user_id"] = $row["UserID"];  // Also store the user ID

                // Log successful login
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 1)");
                $log_stmt->bind_param("ss", $username, $ip);
                $log_stmt->execute();
                
                // Update last login time
                $update_stmt = $connection->prepare("UPDATE users SET LastLogin = NOW() WHERE UserID = ?");
                $update_stmt->bind_param("i", $row["UserID"]);
                $update_stmt->execute();

                // Redirect to the Admin Dashboard
                header("Location: AdminDashboard.php");
                exit();
            } else {
                $error_message = "You are not authorized to access this page.";
                
                // Log failed login attempt (wrong role)
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
                $log_stmt->bind_param("ss", $username, $ip);
                $log_stmt->execute();
            }
        } else {
            $error_message = "Incorrect password.";
            
            // Log failed login attempt (wrong password)
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
            $log_stmt->bind_param("ss", $username, $ip);
            $log_stmt->execute();
        }
    } else {
        // Check if the account exists but is deactivated
        $check_query = "SELECT * FROM users WHERE Username=? AND is_active = 0";
        $check_stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if ($check_result->num_rows > 0) {
            $error_message = "Your account has been deactivated. Please contact an administrator.";
        } else {
            $error_message = "User not found.";
        }
        
        // Log failed login attempt (user not found or deactivated)
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt = $connection->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)");
        $log_stmt->bind_param("ss", $username, $ip);
        $log_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #ff6600;
            margin-bottom: 20px;
        }
        .error-message {
            text-align: center;
            color: red;
            margin-top: 10px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .login-button {
            background-color: #ff6600;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .login-button:hover {
            background-color: #cc5500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="adminLogin.php" method="post">
            <label>Username:</label>
            <input type="text" name="username" required>
            <label>Password:</label>
            <input type="password" name="password" required>
            <button type="submit" class="login-button">Login</button>
            <?php if (isset($error_message)) { ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php } ?>
        </form>
    </div>
</body>
</html>
