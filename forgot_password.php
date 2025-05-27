<?php
session_start();
include 'db_connection.php';

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 1;

// Step 1: Email verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_email'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
       $stmt = $connection->prepare("SELECT UserID, Email, Role as UserRole FROM users WHERE Email = ?");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "No account found with this email address.";
        } else {
            $user = $result->fetch_assoc();
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_user_id'] = $user['UserID'];
            $_SESSION['reset_user_role'] = $user['UserRole'];
            
            // Check if user has security questions set up
            $check_sq = $connection->prepare("SELECT id FROM user_security_questions WHERE user_id = ?");
            $check_sq->bind_param("i", $user['UserID']);
            $check_sq->execute();
            $sq_result = $check_sq->get_result();
            
            if ($sq_result->num_rows === 0) {
                $error = "Security questions not set up for this account. Please contact support.";
            } else {
                // Redirect to security questions step
                header("Location: forgot_password.php?step=2");
                exit();
            }
        }
    }
}




// Step 2: Security Questions verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_answers'])) {
    if (!isset($_SESSION['reset_user_id'])) {
        header("Location: forgot_password.php");
        exit();
    }
    
    $user_id = $_SESSION['reset_user_id'];
    $answer1 = strtolower(trim($_POST['security_answer_1']));
    $answer2 = strtolower(trim($_POST['security_answer_2']));
    
    // Get stored security questions and answers
    $stmt = $connection->prepare("SELECT question_1, answer_1, question_2, answer_2 FROM user_security_questions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Security questions not found. Please contact support.";
    } else {
        $security_data = $result->fetch_assoc();
        
        // Special handling for birth year question
        $stored_answer2 = strtolower($security_data['answer_2']);
        if ($security_data['question_2'] === 'birthPlace' && strpos($stored_answer2, ' g.c.') !== false) {
            // Remove G.C. for comparison
            $stored_answer2 = str_replace(' g.c.', '', $stored_answer2);
        }
        
        // Compare answers (case-insensitive)
        if (strtolower($security_data['answer_1']) === $answer1 && 
            $stored_answer2 === $answer2) {
            // Answers correct, proceed to reset password
            $_SESSION['security_verified'] = true;
            header("Location: forgot_password.php?step=3");
            exit();
        } else {
            $error = "Your answers do not match our records. Please try again.";
        }
    }
}


// Step 3: Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['security_verified'])) {
        header("Location: forgot_password.php");
        exit();
    }
    
    $user_id = $_SESSION['reset_user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $connection->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Clear session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_user_role']);
            unset($_SESSION['security_verified']);
            
            $success = "Your password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Failed to reset password. Please try again later.";
        }
    }
}

// Fetch security questions if on step 2
$security_questions = [];
if ($step == 2 && isset($_SESSION['reset_user_id'])) {
    $user_id = $_SESSION['reset_user_id'];
    $stmt = $connection->prepare("SELECT question_1, question_2 FROM user_security_questions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $security_questions = $result->fetch_assoc();
    }
}

// Function to get question text based on question code
function getQuestionText($question_code) {
    $questions = [
        'birth_city' => 'What city were you born in?',
        'nickname' => 'What is your Nickname?',
        'first_school' => 'What was the name of your first school?',
        'roleModel' => 'Who is your role model?',
        'best_friend' => 'Who was your best friend?',
        'birthPlace' => 'When were you born?'
        // Remove the typo 'birthPlacer'
    ];
    
    return isset($questions[$question_code]) ? $questions[$question_code] : 'Unknown question ('.$question_code.')';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SLU CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            --border-radius: 10px;
            --shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
        }
        
        .forgot-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 3rem;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-top: 10px;
            color: var(--dark);
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 20px;
            height: 2px;
            background-color: #dee2e6;
            transform: translateY(-50%);
        }
        
        .step.active:not(:last-child):after {
            background-color: var(--primary);
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
        }
        
        .step.active .step-number {
            background-color: var(--primary);
            color: white;
        }
        
        .step-title {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .step.active .step-title {
            color: var(--primary);
            font-weight: 600;
        }
        
        .form-control, .btn {
            padding: 12px 15px;
            border-radius: 8px;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 102, 0, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
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
            background: rgba(255, 102, 0, 0.1);
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
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        @media (max-width: 576px) {
            .forgot-container {
                margin: 40px auto;
                padding: 20px;
            }
            
            .step-title {
                font-size: 0.7rem;
            }
        }
                img {
    max-width: 30%; 
    height: auto; 
}
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <div class="logo">
                 <span class="logo-text"> <img src="images/LOGO.png" alt="SLU Logo"> </span>
                <p class="text-muted">Password Recovery</p>
            </div>
            
            <div class="step-indicator">
                <div class="step <?php echo $step == 1 ? 'active' : ''; ?>">
                    <div class="step-number">1</div>
                    <div class="step-title">Email Verification</div>
                </div>
                <div class="step <?php echo $step == 2 ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <div class="step-title">Security Questions</div>
                </div>
                <div class="step <?php echo $step == 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <div class="step-title">Reset Password</div>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- Step 1: Email Verification -->
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                        </div>
                        <div class="form-text">Enter the email address associated with your account.</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="verify_email" class="btn btn-primary">Continue</button>
                        <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                    </div>
                </form>
            
                      <?php elseif ($step == 2): ?>
                <!-- Step 2: Security Questions -->
                <form method="POST" action="">
                    <p class="mb-4">Please answer your security questions to verify your identity.</p>
                    
                    <?php if (!empty($security_questions)): ?>
                        <div class="security-card">
                            <div class="security-card-header">
                                <h5><i class="fas fa-question-circle"></i> Question 1</h5>
                            </div>
                            <div class="security-card-body">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getQuestionText($security_questions['question_1']); ?></label>
                                    <input type="text" class="form-control" name="security_answer_1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="security-card">
                            <div class="security-card-header">
                                <h5><i class="fas fa-question-circle"></i> Question 2</h5>
                            </div>
                            <div class="security-card-body">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getQuestionText($security_questions['question_2']); ?></label>
                                    <input type="text" class="form-control" name="security_answer_2" required>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Security questions could not be loaded. Please try again or contact support.
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" name="verify_answers" class="btn btn-primary" <?php echo empty($security_questions) ? 'disabled' : ''; ?>>Verify Answers</button>
                        <a href="forgot_password.php" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
            
            <?php elseif ($step == 3): ?>
                <!-- Step 3: Reset Password -->
                <?php if (!empty($success)): ?>
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle success-icon"></i>
                        <h4 class="mb-3">Password Reset Successful!</h4>
                        <p>Your password has been reset successfully.</p>
                        <div class="d-grid mt-4">
                            <a href="login.php" class="btn btn-primary">Login with New Password</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <p class="mb-4">Create a new password for your account.</p>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                            <a href="forgot_password.php?step=2" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    <small>
                        <i class="fas fa-shield-alt me-1"></i> 
                        Your security is important to us. We'll never share your information with others.
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Function to toggle password visibility
            function setupPasswordToggle(inputId) {
                const input = document.getElementById(inputId);
                if (!input) return;
                
                const inputGroup = input.parentElement;
                const toggleBtn = document.createElement('span');
                toggleBtn.className = 'input-group-text';
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
                toggleBtn.style.cursor = 'pointer';
                
                toggleBtn.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        input.type = 'password';
                        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
                
                inputGroup.appendChild(toggleBtn);
            }
            
            // Setup toggles for password fields
            setupPasswordToggle('new_password');
            setupPasswordToggle('confirm_password');
        });
    </script>
</body>
</html>
