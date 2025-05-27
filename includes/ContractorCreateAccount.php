<?php
session_start();
include '../db_connection.php';

// Only allow contractors
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Contractor') {
    header("Location: ../index.php");
    exit();
}

// Function to check if a string starts with a capital letter and contains only letters
function startsWithCapitalAndLettersOnly($string) {
    return preg_match("/^[A-Z][a-zA-Z]*$/", $string);
}

// Function for username validation
function validate_username($username) {
    return preg_match("/^[a-zA-Z@_][a-zA-Z0-9@_]*$/", $username);
}

// Function for password validation
function validate_password($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@_])[A-Za-z\d@_]{6,8}$/', $password);
}

// Function for phone validation - Ethiopian phone numbers only
function validate_phone($phone) {
    // Remove any spaces, dashes, or other non-digit characters
    $cleaned_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if the number starts with 9 (Ethio Telecom) or 7 (Safaricom)
    // and has exactly 9 digits
    if (preg_match('/^[97]\d{8}$/', $cleaned_phone)) {
        return true;
    }
    
    return false;
}

// Function to generate a username based on first and last name
function generate_username($first_name, $last_name) {
    // Take first letter of first name and up to 5 letters of last name
    $base = strtolower(substr($first_name, 0, 1) . substr($last_name, 0, 5));
    
    // Add random numbers (3 digits)
    $random_numbers = rand(100, 999);
    
    // Add a random special character (@ or _)
    $special_chars = ['@', '_'];
    $random_special = $special_chars[array_rand($special_chars)];
    
    return $base . $random_special . $random_numbers;
}

// Function to generate a strong password
function generate_strong_password() {
    // Define character sets
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $special = '@_';
    
    // Generate a password with 8 characters (maximum allowed length)
    $password = '';
    
    // Ensure at least one of each required character type
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    $password .= $special[rand(0, strlen($special) - 1)];
    
    // Fill the rest with random characters from all sets
    $all_chars = $lowercase . $uppercase . $numbers . $special;
    for ($i = 0; $i < 4; $i++) {
        $password .= $all_chars[rand(0, strlen($all_chars) - 1)];
    }
    
    // Shuffle the password to make it more random
    $password = str_shuffle($password);
    
    return $password;
}

$success_message = '';
$error_message = '';
$first_name = $last_name = $email = $phone = '';
$generated_username = '';
$generated_password = '';
$show_credentials = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input values and trim them
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = 'Employee';  // Hardcoded role: Contractors create *Employees*.
    $contractor_id = $_SESSION['user_id']; // Get the contractor ID from the session

    // Initialize error message.
    $error_message = ''; 
    $validation_errors = [];

    // Validate First Name
    if (empty($first_name)) {
        $validation_errors['first_name'] = "First name is required.";
    } elseif (!startsWithCapitalAndLettersOnly($first_name)) {
        $validation_errors['first_name'] = "First name must start with a capital letter and contain only letters.";
    }

    // Validate Last Name
    if (empty($last_name)) {
        $validation_errors['last_name'] = "Last name is required.";
    } elseif (!startsWithCapitalAndLettersOnly($last_name)) {
        $validation_errors['last_name'] = "Last name must start with a capital letter and contain only letters.";
    }

    // Validate Email
    if (empty($email)) {
        $validation_errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors['email'] = "Invalid email format.";
    }

    // Validate Phone Number
    if (empty($phone)) {
        $validation_errors['phone'] = "Phone number is required.";
    } elseif (!validate_phone($phone)) {
        $validation_errors['phone'] = "Invalid Ethiopian phone number. Please enter a valid 9-digit number starting with 9 (Ethio Telecom) or 7 (Safaricom).";
    } else {
        // Prepend +251 for database storage
        $phone_for_db = "+251" . preg_replace('/[^0-9]/', '', $phone);
    }

    // If there are validation errors, create an error message
    if (!empty($validation_errors)) {
        $error_message = "Please correct the following errors:";
    } else {
        // Generate username and password
        $username = generate_username($first_name, $last_name);
        $password = generate_strong_password();
        $generated_username = $username;
        $generated_password = $password;
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Check if username already exists
        $stmt = $connection->prepare("SELECT * FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // If username exists, regenerate with a different random number
            $username = generate_username($first_name, $last_name);
            $generated_username = $username;
            
            // Check again
            $stmt = $connection->prepare("SELECT * FROM users WHERE Username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $validation_errors['username'] = "Could not generate a unique username. Please try again.";
                $error_message = "Please correct the following errors:";
            }
        }
        
        if (empty($validation_errors)) {
            // Insert new user into the database.  Importantly, *this* is where the role and contractor ID are set.
            $stmt = $connection->prepare("INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role, managed_by_contractor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $username, $hashed_password, $first_name, $last_name, $email, $phone_for_db, $role, $contractor_id);  //  $role is 'Employee'
            if ($stmt->execute()) {
                $success_message = "Employee created successfully!";
                $show_credentials = true;
                // Don't clear form data so credentials can be shown with the employee info
            } else {
                $error_message = "Error creating employee. Please try again.";
                $generated_username = '';
                $generated_password = '';
            }
        }
        $stmt->close();
    }
    $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Employee Account | Construction Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            color: var(--dark-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            max-width: 900px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.1);
        }
        
        .invalid-feedback {
            font-size: 0.85rem;
            color: var(--danger-color);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.2);
        }
        
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background-color: white;
            color: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .phone-prefix {
            font-weight: 500;
            color: #6c757d;
            pointer-events: none;
            z-index: 10;
        }
        
        .credentials-box {
            background-color: #f0f8ff;
            border: 1px solid #d1e7ff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .credentials-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .credentials-title i {
            margin-right: 0.5rem;
        }
        
        .credential-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            background-color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;    
            }
        
        .credential-label {
            font-weight: 600;
            width: 100px;
            color: var(--dark-color);
        }
        
        .credential-value {
            font-family: monospace;
            font-size: 1rem;
            flex-grow: 1;
            word-break: break-all;
        }
        
        .copy-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            margin-left: 0.5rem;
            transition: all 0.2s;
        }
        
        .copy-btn:hover {
            color: var(--accent-color);
            transform: scale(1.1);
        }
        
        .phone-input {
            padding-left: 3.5rem !important;
        }
        
        /* Fix for phone number display */
        input[type="tel"] {
            font-size: 1rem !important;
            font-family: inherit !important;
            vertical-align: baseline !important;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="ContractorManageEmployee.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Page Header -->
    <header class="page-header">
        <div class="container text-center">
            <h1><i class="fas fa-user-plus me-2"></i>Create Employee Account</h1>
            <p class="lead">Add a new employee to your team</p>
        </div>
    </header>

    <div class="container mb-5">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            </div>
            
            <?php if ($show_credentials): ?>
            <!-- Display generated credentials -->
            <div class="credentials-box">
                <div class="credentials-title">
                    <i class="fas fa-key"></i> Account Credentials
                </div>
                <p class="text-muted mb-3">The following credentials have been automatically generated for this employee. Please save or share these with the employee.</p>
                
                <div class="credential-item">
                    <span class="credential-label">Username:</span>
                    <span class="credential-value" id="gen-username"><?php echo htmlspecialchars($generated_username); ?></span>
                    <button type="button" class="copy-btn" onclick="copyToClipboard('gen-username')">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
                
                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value" id="gen-password"><?php echo htmlspecialchars($generated_password); ?></span>
                    <button type="button" class="copy-btn" onclick="copyToClipboard('gen-password')">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
                
                <div class="mt-3 text-center">
                    <button type="button" class="btn btn-primary" onclick="printCredentials()">
                        <i class="fas fa-print me-2"></i>Print Credentials
                    </button>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <?php if (!empty($validation_errors)): ?>
                <ul class="mb-0 mt-2">
                    <?php foreach ($validation_errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-id-card me-2"></i>Employee Information</h4>
            </div>
            <div class="card-body">
                <form id="createEmployeeForm" method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control <?php echo isset($validation_errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                            <?php if (isset($validation_errors['first_name'])): ?>
                                <div class="invalid-feedback"><?php echo $validation_errors['first_name']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Must start with a capital letter and contain only letters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control <?php echo isset($validation_errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                            <?php if (isset($validation_errors['last_name'])): ?>
                                <div class="invalid-feedback"><?php echo $validation_errors['last_name']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Must start with a capital letter and contain only letters.</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control <?php echo isset($validation_errors['email']) ? 'is-invalid' : ''; ?>" 
                                id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($validation_errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $validation_errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <div class="position-relative">
                                <input type="tel" class="form-control phone-input <?php echo isset($validation_errors['phone']) ? 'is-invalid' : ''; ?>" 
                                    id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="9XXXXXXXX" required>
                                <div class="position-absolute start-0 top-0 ps-3 pt-2 phone-prefix">+251</div>
                                <?php if (isset($validation_errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $validation_errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Ethiopian phone number format (9 digits starting with 9 or 7).</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Create Employee Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                // Show a temporary "Copied!" tooltip
                const copyBtn = element.nextElementSibling;
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                }, 1500);
            });
        }
        
        // Print credentials function
        function printCredentials() {
            const username = document.getElementById('gen-username').textContent;
            const password = document.getElementById('gen-password').textContent;
            const firstName = "<?php echo addslashes(htmlspecialchars($first_name)); ?>";
            const lastName = "<?php echo addslashes(htmlspecialchars($last_name)); ?>";
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Employee Credentials</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            padding: 20px;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 20px;
                            border-bottom: 1px solid #ddd;
                            padding-bottom: 10px;
                        }
                        .logo {
                            max-width: 150px;
                            margin-bottom: 15px;
                        }
                        .credentials {
                            border: 1px solid #ddd;
                            padding: 20px;
                            max-width: 500px;
                            margin: 0 auto;
                            border-radius: 5px;
                        }
                        .credential-item {
                            margin-bottom: 10px;
                        }
                        .label {
                            font-weight: bold;
                            display: inline-block;
                            width: 100px;
                        }
                        .value {
                            font-family: monospace;
                        }
                        .footer {
                            margin-top: 30px;
                            font-size: 0.9em;
                            text-align: center;
                            color: #666;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <img src="../images/LOGO.png" alt="SLU Logo" class="logo">
                        <h2>SLU Construction Management System</h2>
                        <p>Employee Account Credentials</p>
                    </div>
                    
                    <div class="credentials">
                        <h3>Account Information for ${firstName} ${lastName}</h3>
                        
                        <div class="credential-item">
                            <span class="label">Username:</span>
                            <span class="value">${username}</span>
                        </div>
                        
                        <div class="credential-item">
                            <span class="label">Password:</span>
                            <span class="value">${password}</span>
                        </div>
                        
                        <p><strong>Important:</strong> Please keep these credentials secure and change your password after first login.</p>
                    </div>
                    
                    <div class="footer">
                        <p>Generated on ${new Date().toLocaleString()}</p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Print after a short delay to ensure the content is loaded
            setTimeout(() => {
                printWindow.print();
                // printWindow.close();
            }, 500);
        }
    </script>
</body>
</html>