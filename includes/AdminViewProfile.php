<?php
session_start();
include '../db_connection.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch admin user info
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $errors[] = "Required fields cannot be empty";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Check if email exists for another user
    $email_check = $connection->prepare("SELECT UserID FROM users WHERE Email = ? AND UserID != ?");
    $email_check->bind_param("si", $email, $user_id);
    $email_check->execute();
    $email_check->store_result();
    if ($email_check->num_rows > 0) {
        $errors[] = "Email address is already in use by another account";
    }
    
    // Phone validation for Ethiopian format: +251 followed by 9 digits starting with 7 or 9
    if (!empty($phone)) {
        // Remove any spaces from the phone number
        $phone = str_replace(' ', '', $phone);
        
        // Check if it starts with +251 and followed by 9 digits starting with 7 or 9
        if (!preg_match('/^\+251[79]\d{8}$/', $phone)) {
            $errors[] = 'Phone number must be in Ethiopian format: +251 followed by 9 digits starting with 7 or 9';
        }
    }
    
    if (empty($errors)) {
        try {
            // Update users table
            $update_query = "UPDATE users SET FirstName = ?, LastName = ?, Email = ?, Phone = ? WHERE UserID = ?";
            $stmt_update = $connection->prepare($update_query);
            $stmt_update->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            
            if ($stmt_update->execute()) {
                $success_message = "Profile updated successfully!";
                
                // Refresh user data
                $stmt->execute();
                $user_result = $stmt->get_result();
                $user = $user_result->fetch_assoc();
            } else {
                $error_message = "Error updating profile: " . $connection->error;
            }
        } catch (Exception $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All password fields are required";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    if (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters long";
    }
    
    // Verify current password
    $password_check = $connection->prepare("SELECT Password FROM users WHERE UserID = ?");
    $password_check->bind_param("i", $user_id);
    $password_check->execute();
    $password_result = $password_check->get_result()->fetch_assoc();
    
    if (!password_verify($current_password, $password_result['Password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_password = $connection->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            $update_password->bind_param("si", $hashed_password, $user_id);
            $update_password->execute();
            
            $success_message = "Password changed successfully!";
            
        } catch (Exception $e) {
            $error_message = "Error changing password: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Handle security questions update
$sq_success_message = '';
$sq_error_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security_questions'])) {
    $question_1 = $_POST['security_question_1'] ?? '';
    $answer_1 = trim($_POST['security_answer_1'] ?? '');
    $question_2 = $_POST['security_question_2'] ?? '';
    $answer_2 = trim($_POST['security_answer_2'] ?? '');
    
    // Validate inputs
    $sq_errors = [];
    
    if (empty($question_1) || empty($answer_1) || empty($question_2) || empty($answer_2)) {
        $sq_errors[] = "All security question fields are required.";
    }
    
    if ($question_1 === $question_2 && !empty($question_1)) {
        $sq_errors[] = "Please select two different security questions.";
    }
    



// Validate answer format based on question type
if ($question_2 === 'birthPlace' && !empty($answer_2)) {
    // For "When were you born" - validate it's a number (year)
    if (!preg_match('/^\d+$/', $answer_2)) {
        $sq_errors[] = "Birth year must be a number.";
    } 
    // Check if birth year is before 2003
    else if ((int)$answer_2 >= 2003) {
        $sq_errors[] = "Birth year must be before 2003.";
    }
    else {
        // Append G.C. to the birth year
        $answer_2 = $answer_2 . " G.C.";
    }
} else {
    // For all other questions - validate they contain only characters
    if (!empty($answer_1) && !preg_match('/^[a-zA-Z\s]+$/', $answer_1)) {
        $sq_errors[] = "Answer to question 1 must contain only letters.";
    }
    
    if (!empty($answer_2) && $question_2 !== 'birthPlace' && !preg_match('/^[a-zA-Z\s]+$/', $answer_2)) {
        $sq_errors[] = "Answer to question 2 must contain only letters.";
    }
}

    // If no errors, update or insert security questions
    if (empty($sq_errors)) {
        // Convert answers to lowercase for case-insensitive comparison later
        $answer_1 = strtolower($answer_1);
        if ($question_2 !== 'birthPlace') {
            $answer_2 = strtolower($answer_2);
        }
        
        // Check if user already has security questions
        $check_query = "SELECT id FROM user_security_questions WHERE user_id = ?";
        $stmt = $connection->prepare($check_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing questions
            $update_query = "UPDATE user_security_questions SET question_1 = ?, answer_1 = ?, question_2 = ?, answer_2 = ? WHERE user_id = ?";
            $stmt = $connection->prepare($update_query);
            $stmt->bind_param("ssssi", $question_1, $answer_1, $question_2, $answer_2, $user_id);
            
            if ($stmt->execute()) {
                $sq_success_message = "Security questions updated successfully.";
            } else {
                $sq_error_message = "Failed to update security questions: " . $connection->error;
            }
        } else {
            // Insert new questions
            $insert_query = "INSERT INTO user_security_questions (user_id, question_1, answer_1, question_2, answer_2) VALUES (?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($insert_query);
            $stmt->bind_param("issss", $user_id, $question_1, $answer_1, $question_2, $answer_2);
            
            if ($stmt->execute()) {
                $sq_success_message = "Security questions saved successfully.";
            } else {
                $sq_error_message = "Failed to save security questions: " . $connection->error;
            }
        }
    } else {
        $sq_error_message = implode("<br>", $sq_errors);
    }
}

// Fetch existing security questions if any
$existing_questions = [
    'question_1' => '',
    'answer_1' => '',
    'question_2' => '',
    'answer_2' => ''
];

$query = "SELECT question_1, answer_1, question_2, answer_2 FROM user_security_questions WHERE user_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $existing_questions = $result->fetch_assoc();
    
    // Remove G.C. from birth year if present
    if ($existing_questions['question_2'] === 'birthPlace' && 
        strpos($existing_questions['answer_2'], ' G.C.') !== false) {
        $existing_questions['answer_2'] = str_replace(' G.C.', '', $existing_questions['answer_2']);
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile - CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff6600;
            --primary-light: #ff8533;
            --primary-dark: #e65c00;
            --secondary: #4361ee;
            --success: #2ecc71;
            --info: #3498db;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --gradient: linear-gradient(135deg, var(--primary), var(--primary-light));
            --shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }
        
        /* Body Styling */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
            height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(180deg, #ff6600, #ff8533);
            padding-top: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .sidebar-brand i {
            font-size: 28px;
            margin-right: 10px;
            color: white;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
            padding: 0 15px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar-menu i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('../images/Admin.jpg') no-repeat center center fixed;
            background-size: cover;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h2 {
            color: #ff6600;
            margin: 0;
            font-size: 24px;
        }

        /* Profile Container */
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 1rem;
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .contact-item i {
            color: var(--primary);
        }

        /* Profile Section */
        .profile-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gradient);
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 102, 0, 0.25);
        }

        .btn-update {
            background: var(--gradient);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            color: white;
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 102, 0, 0.3);
        }

        .alert {
            border-radius: 8px;
            padding: 15px 20px;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #333;
            background: transparent;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
        }

        .user-dropdown .dropdown-toggle:hover,
        .user-dropdown .dropdown-toggle:focus {
            background-color: rgba(255, 102, 0, 0.1);
        }

        .user-avatar-small {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ff6600, #ff8533);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 15px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px 20px;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 102, 0, 0.1);
            color: #ff6600;
        }

        .dropdown-item i {
            margin-right: 10px;
            color: #ff6600;
        }

        .dropdown-divider {
            margin: 10px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .dropdown-item.logout {
            color: #dc3545;
        }

        .dropdown-item.logout i {
            color: #dc3545;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                text-align: center;
            }

            .sidebar-header h3, .sidebar-menu span {
                display: none;
            }

            .sidebar-brand i {
                margin-right: 0;
            }

            .sidebar-menu a {
                justify-content: center;
            }

            .sidebar-menu i {
                margin-right: 0;
                font-size: 20px;
            }

            .main-content {
                margin-left: 70px;
            }
            
            .user-dropdown .dropdown-toggle span {
                display: none;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .profile-contact {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-tachometer-alt"></i>
                <h3>Admin Panel</h3>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="AdminDashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="AdminManageAccount.php">
                    <i class="fas fa-user-cog"></i>
                    <span>Manage Account</span>
                </a>
            </li>
            <li>
                <a href="ApproveProjects.php">
                    <i class="fas fa-check-circle"></i>
                    <span>Approve Projects</span>
                </a>
            </li>
            <li>
                <a href="AdminGenerateReport.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Generate Report</span>
                </a>
            </li>
            <li>
                <a href="AdminViewProfile.php" class="active">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            
            <!-- User Profile Dropdown -->
            <div class="user-dropdown dropdown">
                <button class="dropdown-toggle" type="button" id="userMenu" onclick="toggleDropdown()">
                    <div class="user-avatar-small">
                        <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'A'; ?>
                    </div>
                    <span><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : 'Admin'; ?></span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a class="dropdown-item" href="AdminViewProfile.php">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item logout" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'A'; ?>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Admin'; ?></h1>
                    <div class="profile-role">
                        <i class="fas fa-user-shield"></i>
                        Administrator
                    </div>
                    <div class="profile-contact">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <?php echo isset($user['Email']) ? htmlspecialchars($user['Email']) : 'Not Available'; ?>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <?php echo isset($user['Phone']) ? htmlspecialchars($user['Phone']) : 'Not Available'; ?>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-calendar-alt"></i>
                            Joined: <?php echo isset($user['RegistrationDate']) ? date('M d, Y', strtotime($user['RegistrationDate'])) : 'Not Available'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Edit Profile Section -->
            <div class="profile-section">
                <h2 class="section-title">Edit Profile</h2>
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($user['LastName']) ? htmlspecialchars($user['LastName']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($user['Email']) ? htmlspecialchars($user['Email']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($user['Phone']) ? htmlspecialchars($user['Phone']) : ''; ?>" placeholder="+251xxxxxxxxx" required>
                            <small class="text-muted">Format: +251 followed by 9 digits starting with 7 or 9</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="update_profile" class="btn btn-update">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

           <!-- Change Password Section -->
<div class="profile-section">
    <h2 class="section-title">Change Password</h2>
    <form method="POST" action="">
        <div class="row mb-3">
            <div class="col-md-4 mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
                <small class="text-muted">Minimum 8 characters</small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <button type="submit" name="change_password" class="btn btn-update">
                <i class="fas fa-key me-2"></i> Change Password
            </button>
        </div>
    </form>
</div>

           <!-- Security Questions Section -->
          <div class="profile-section">
          <h2 class="section-title">
           <i class="fas fa-shield-alt"></i>
               Security Questions
                             </h2>
    <p class="text-muted mb-4">Set up security questions to help recover your account if you forget your password.</p>
    
    <!-- Display security questions success/error messages -->
    <?php if (!empty($sq_success_message)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $sq_success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($sq_error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $sq_error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="security-card">
            <div class="security-card-header">
                <h5><i class="fas fa-question-circle"></i> Question 1</h5>
            </div>
            <div class="security-card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="security_question_1" class="form-label">Select Your Question</label>
                        <select id="security_question_1" name="security_question_1" class="form-select" required>
                            <option value="">-- Select a question --</option>
                            <option value="birth_city" <?= $existing_questions['question_1'] === 'birth_city' ? 'selected' : '' ?>>What city were you born in?</option>
                            <option value="nickname" <?= $existing_questions['question_1'] === 'nickname' ? 'selected' : '' ?>>What is your Nickname?</option>
                            <option value="first_school" <?= $existing_questions['question_1'] === 'first_school' ? 'selected' : '' ?>>What was the name of your first school?</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="security_answer_1" class="form-label">Your Answer</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="text" id="security_answer_1" name="security_answer_1" class="form-control" value="<?= htmlspecialchars($existing_questions['answer_1']) ?>" required>
                        </div>
                        <small class="form-text text-muted">Answer must contain only letters</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="security-card">
            <div class="security-card-header">
                <h5><i class="fas fa-question-circle"></i> Question 2</h5>
            </div>
            <div class="security-card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="security_question_2" class="form-label">Select Your Question</label>
                        <select id="security_question_2" name="security_question_2" class="form-select" required>
                            <option value="">-- Select a question --</option>
                            <option value="roleModel" <?= $existing_questions['question_2'] === 'roleModel' ? 'selected' : '' ?>>Who is your role model?</option>
                            <option value="best_friend" <?= $existing_questions['question_2'] === 'best_friend' ? 'selected' : '' ?>>Who was your best friend?</option>
                            <option value="birthPlace" <?= $existing_questions['question_2'] === 'birthPlace' ? 'selected' : '' ?>>When were you born?</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="security_answer_2" class="form-label">Your Answer</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="text" id="security_answer_2" name="security_answer_2" class="form-control" value="<?= htmlspecialchars($existing_questions['answer_2']) ?>" required>
                        </div>
                        <small id="answer2Help" class="form-text text-muted">
                            For birth year, enter numbers only before 2003 (G.C. will be added automatically)
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-grid gap-2 mt-4">
            <button type="submit" name="update_security_questions" class="btn btn-update">
                <i class="fas fa-shield-alt me-2"></i> Save Security Questions
            </button>
        </div>
       </form>
         </div>
        </div>
    </div>

    <!-- JavaScript for dropdown functionality -->
    <script>
        function toggleDropdown() {
            document.getElementById("userDropdownMenu").classList.toggle("show");
        }

        // Close the dropdown if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-toggle')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php if(isset($connection)) $connection->close(); ?>
