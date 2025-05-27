<?php
session_start();
include '../db_connection.php';

// Check session - allow both Engineer and Site Engineer roles
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Engineer' && $_SESSION['user_role'] !== 'Site Engineer')) {
    header("Location: ../login.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle profile update
$success_message = '';
$error_message = '';

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
            $update_user = $connection->prepare("UPDATE users SET FirstName = ?, LastName = ?, Email = ?, Phone = ? WHERE UserID = ?");
            $update_user->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            $update_user->execute();
            
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt->execute();
            $user_result = $stmt->get_result();
            $user = $user_result->fetch_assoc();
            
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

// Determine which dashboard to return to based on user role
$dashboard_link = ($_SESSION['user_role'] === 'Site Engineer') ? 'SiteEngineerDashboard.php' : 'EngineerDashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3498db;
            --primary-light: #5dade2;
            --primary-dark: #2980b9;
            --secondary: #4361ee;
            --success: #2ecc71;
            --info: #3498db;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark:rgb(37, 59, 82);
            --gray: #6c757d;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --gradient: linear-gradient(135deg, var(--primary), var(--primary-light));
            --shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7ff;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
            transition: var(--transition);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: var(--dark);
            color: white;
            z-index: 1000;
            transition: var(--transition);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .sidebar-logo i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .sidebar-collapsed .logo-text {
            display: none;
        }

        .toggle-sidebar {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            color: var(--primary);
        }

        .sidebar-collapsed .toggle-sidebar {
            transform: rotate(180deg);
        }

        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary);
        }

        .sidebar-menu i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-collapsed .sidebar-menu span {
            display: none;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .user-details {
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-collapsed .user-details {
            display: none;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: var(--transition);
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }

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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
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
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background: var(--gradient);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Security Questions Specific Styles */
        .security-card {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            overflow: hidden;
            transition: var(--transition);
        }

        .security-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .security-card-header {
            background: rgba(52, 152, 219, 0.1);
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .security-card-header h5 {
            margin: 0;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-card-body {
            padding: 20px;
        }

        .input-group-text {
            background-color: var(--light);
            border-color: #ced4da;
        }

        .input-group-text i {
            color: var(--primary);
        }

        @media (max-width: 992px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            .logo-text, .sidebar-menu span, .user-details, .logout-text {
                display: none;
            }
            
            .main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .sidebar-collapsed .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .sidebar-collapsed .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .profile-contact {
                justify-content: center;
            }
            
            .profile-section {
                padding: 20px;
            }
        }
        img {
            max-width: 80%; 
            height: auto; 
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo $dashboard_link; ?>" class="sidebar-logo">
                <span class="logo-text"><img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $dashboard_link; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if ($_SESSION['user_role'] === 'Site Engineer'): ?>
            <a href="SiteEngineerViewTasks.php" class="sidebar-item">
            <i class="fas fa-tasks"></i>
            <span>View Tasks</span>
        </a>
                <a href="SiteEngineerManageDailyLabor.php" class="sidebar-item">
            <i class="fas fa-hard-hat"></i>
            <span>Track Labor</span>
        </a>
        <a href="SiteEngineerSubmitReport.php" class="sidebar-item">
            <i class="fas fa-file-alt"></i>
            <span>Submit Reports</span>
        </a>
            
               
            <?php endif; ?>
            <li>
                <a href="EngineerProfile.php" class="active">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'E'; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Engineer'; ?></div>
                    <div class="user-role"><?php echo $_SESSION['user_role']; ?></div>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="logout-text">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="profile-container">
            <?php if (!empty($success_message) || !empty($sq_success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo !empty($success_message) ? $success_message : $sq_success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message) || !empty($sq_error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo !empty($error_message) ? $error_message : $sq_error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'E'; ?>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Engineer'; ?></h1>
                    <div class="profile-role">
                        <i class="fas fa-hard-hat"></i> <?php echo $_SESSION['user_role']; ?>
                    </div>
                    <div class="profile-contact">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <?php echo isset($user['Email']) ? htmlspecialchars($user['Email']) : 'No email provided'; ?>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <?php echo isset($user['Phone']) ? htmlspecialchars($user['Phone']) : 'No phone provided'; ?>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-user-tag"></i>
                            ID: <?php echo isset($user['UserID']) ? htmlspecialchars($user['UserID']) : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Section -->
            <div class="profile-section">
                <h2 class="section-title"><i class="fas fa-user-edit"></i> Edit Profile</h2>
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($user['LastName']) ? htmlspecialchars($user['LastName']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($user['Email']) ? htmlspecialchars($user['Email']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($user['Phone']) ? htmlspecialchars($user['Phone']) : ''; ?>" placeholder="+251 7X XXX XXXX">
                            <small class="form-text text-muted">Ethiopian format: +251 followed by 9 digits starting with 7 or 9</small>
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

           <!-- Change Password Section -->
                <div class="profile-section">
                    <h2 class="section-title">
                        <i class="fas fa-key"></i>
                        Change Password
                    </h2>
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('current_password')"></i>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('new_password')"></i>
                                </div>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('confirm_password')"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="change_password" class="btn btn-primary">
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
                        <button type="submit" name="update_security_questions" class="btn btn-primary">
                            <i class="fas fa-shield-alt me-2"></i> Save Security Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar functionality
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // Responsive behavior for small screens
        function checkScreenSize() {
            if (window.innerWidth < 992) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }

        // Check on load and resize
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);

        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Dynamic help text based on question selection
        document.getElementById('security_question_2').addEventListener('change', function() {
            const helpText = document.getElementById('answer2Help');
            const answerInput = document.getElementById('security_answer_2');
            
            if (this.value === 'birthPlace') {
                helpText.textContent = 'Enter birth year before 2003 (G.C. will be added automatically)';
                answerInput.setAttribute('pattern', '\\d+');
                answerInput.setAttribute('title', 'Please enter a year before 2003');
                
                // Add an input event listener to validate the year
                answerInput.addEventListener('input', function() {
                    const year = parseInt(this.value);
                    if (year >= 2003) {
                        this.setCustomValidity('Birth year must be before 2003');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            } else {
                helpText.textContent = 'Answer must contain only letters';
                answerInput.setAttribute('pattern', '[a-zA-Z\\s]+');
                answerInput.setAttribute('title', 'Please enter letters only');
                
                // Remove the custom validity
                answerInput.setCustomValidity('');
            }
        });

        // Trigger the change event on page load to set the correct help text
        window.addEventListener('load', function() {
            const question2Select = document.getElementById('security_question_2');
            if (question2Select) {
                question2Select.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
<?php $connection->close(); ?>


