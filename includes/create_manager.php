<?php
session_start();
include '../db_connection.php';

// Check if the user is logged in and has admin rights
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => ''
];
$generated_username = '';
$generated_password = '';
$show_credentials = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? '')
    ];

    // Validate form data
    $errors = [];

    // Required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($required_fields as $field) {
        if (empty($form_data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    // First name and last name must start with capital letter
    if (!empty($form_data['first_name']) && !preg_match('/^[A-Z][a-zA-Z]*$/', $form_data['first_name'])) {
        $errors[] = 'First name must start with a capital letter and contain only letters';
    }

    if (!empty($form_data['last_name']) && !preg_match('/^[A-Z][a-zA-Z]*$/', $form_data['last_name'])) {
        $errors[] = 'Last name must start with a capital letter and contain only letters';
    }





    // Email validation - must be a Gmail address starting with a letter
    if (!empty($form_data['email'])) {
        // First check if it's a valid email format
        if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address (e.g., name@gmail.com)';
        } else {
            // Check if the domain is gmail.com
            $email_parts = explode('@', $form_data['email']);
            $local_part = $email_parts[0];
            $domain = strtolower(array_pop($email_parts));
            
            // Check if email starts with a letter
            if (!preg_match('/^[a-zA-Z]/', $local_part)) {
                $errors[] = 'Email address must start with a letter, not a number or special character';
            }
            
            if ($domain !== 'gmail.com') {
                $errors[] = 'Please use a Gmail email address (e.g., name@gmail.com)';
            }
        }
    }

    // Check if email already exists
    if (!empty($form_data['email'])) {
        $check_email = $connection->prepare("SELECT UserID FROM users WHERE Email = ? AND Role = 'Project Manager'");
        $check_email->bind_param("s", $form_data['email']);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            $errors[] = 'Email address already exists for another Project Manager';
        }
        $check_email->close();
    }

    // Phone validation for Ethiopian format: +251 followed by 9 digits starting with 7 or 9
    if (!empty($form_data['phone'])) {
        // Remove any spaces from the phone number
        $phone = str_replace(' ', '', $form_data['phone']);
        
        // Check if it starts with +251 and followed by 9 digits starting with 7 or 9
        if (!preg_match('/^\+251[79]\d{8}$/', $phone)) {
            $errors[] = 'Phone number must be in Ethiopian format: +251 followed by 9 digits starting with 7 or 9';
        }
    }

    // If no errors, proceed with account creation
    if (empty($errors)) {
        try {
            // Generate username (firstname.lastname)
            $username = strtolower($form_data['first_name'] . '.' . $form_data['last_name']);
            $original_username = $username;
            $counter = 1;
            
            // Check if username exists and make it unique if needed
            $check_username = $connection->prepare("SELECT UserID FROM users WHERE Username = ?");
            $check_username->bind_param("s", $username);
            $check_username->execute();
            $check_username->store_result();
            
            while ($check_username->num_rows > 0) {
                $username = $original_username . $counter;
                $counter++;
                $check_username->bind_param("s", $username);
                $check_username->execute();
                $check_username->store_result();
            }
            $check_username->close();
            
            // Generate a default password
            $default_password = 'Manager@' . rand(1000, 9999);
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $connection->begin_transaction();
            
            // Insert into users table
            $insert_user = $connection->prepare("INSERT INTO users (Username, FirstName, LastName, Email, Password, Role, Phone) VALUES (?, ?, ?, ?, ?, 'Project Manager', ?)");
            $insert_user->bind_param("ssssss", $username, $form_data['first_name'], $form_data['last_name'], $form_data['email'], $hashed_password, $form_data['phone']);
            $insert_user->execute();
            $user_id = $connection->insert_id;
            
            // Commit transaction
            $connection->commit();
            
            $success_message = "Project Manager account created successfully!";
            $generated_username = $username;
            $generated_password = $default_password;
            $show_credentials = true;
            
            // Reset form data after successful submission
            $form_data = [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => ''
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $connection->rollback();
            $error_message = "Error creating account: " . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project Manager Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #f1f1f1;
            --text-color: #333;
            --light-text: #666;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f8fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: var(--secondary-color);
            color: var(--text-color);
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-button:hover {
            background-color: #e0e0e0;
            transform: translateX(-3px);
        }

        .back-button i {
            margin-right: 8px;
        }

        .card {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            border: none;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            font-weight: 600;
            font-size: 18px;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-text {
            font-size: 13px;
            color: var(--light-text);
            margin-top: 5px;
        }

        .btn {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .required-field::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 4px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 15px;
            padding-left: 15px;
        }

        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        /* New styles for credentials box */
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
            color: var(--text-color);
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
            color: var(--primary-dark);
                        transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-user-tie"></i> Create Project Manager Account</h1>
            <a href="AdminManageAccount.php?role=Project_Manager" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Management
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            
            <?php if ($show_credentials): ?>
            <!-- Display generated credentials -->
            <div class="credentials-box">
                <div class="credentials-title">
                    <i class="fas fa-key"></i> Account Credentials
                </div>
                <p class="text-muted mb-3">The following credentials have been automatically generated for this Project Manager. Please save or share these with the manager.</p>
                
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
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> Project Manager Information
            </div>
            <div class="card-body">
                <form method="POST" action="" id="managerForm">
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label required-field">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($form_data['first_name']); ?>" required>
                                <small class="form-text">Must start with a capital letter</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label required-field">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($form_data['last_name']); ?>" required>
                                <small class="form-text">Must start with a capital letter</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['email']); ?>" 
                                       placeholder="example:name@gmail.com" required>
                                <small class="form-text"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label required-field">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       placeholder="+251 9xxxxxxxx" 
                                       value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
                                <small class="form-text">Format: +251 followed by 9 digits starting with 7 or 9</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Project Manager Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side validation for phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let input = e.target.value.replace(/\s+/g, '');
            
            // If the user hasn't started with +251, add it
            if (!input.startsWith('+251') && input.length > 0) {
                if (input.startsWith('+')) {
                    // They're trying to type a different country code
                    input = '+251';
                } else if (input.startsWith('251')) {
                    // They typed 251 without the +
                    input = '+' + input;
                } else if (input.startsWith('0')) {
                    // They're using local format (0), convert to international
                    input = '+251' + input.substring(1);
                } else {
                    // They started typing the number directly
                    input = '+251' + input;
                }
            }
            
            // Format the number with a space after +251
            if (input.startsWith('+251') && input.length > 4) {
                // Extract the part after +251
                let afterCode = input.substring(4);
                
                // Ensure the first digit after +251 is either 7 or 9
                if (afterCode.length > 0 && !['7', '9'].includes(afterCode[0])) {
                    afterCode = '9' + (afterCode.length > 1 ? afterCode.substring(1) : '');
                }
                
                input = '+251 ' + afterCode;
            }
            
            // Limit to +251 plus 9 digits (total 13 characters with the + sign)
            if (input.replace(/\s+/g, '').length > 13) {
                input = input.replace(/\s+/g, '').substring(0, 13);
                // Re-add the space after +251
                input = input.substring(0, 4) + ' ' + input.substring(4);
            }
            
            e.target.value = input;
        });

        // Form validation before submission
        document.getElementById('managerForm').addEventListener('submit', function(e) {
            let firstName = document.getElementById('first_name').value;
            let lastName = document.getElementById('last_name').value;
            let email = document.getElementById('email').value;
            let phone = document.getElementById('phone').value.replace(/\s+/g, '');
            let isValid = true;
            let errorMessages = [];
            
            // Validate first name starts with capital letter
            if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                isValid = false;
                errorMessages.push('First name must start with a capital letter and contain only letters');
                document.getElementById('first_name').classList.add('is-invalid');
            } else {
                document.getElementById('first_name').classList.remove('is-invalid');
            }
            
            // Validate last name starts with capital letter
            if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                isValid = false;
                errorMessages.push('Last name must start with a capital letter and contain only letters');
                document.getElementById('last_name').classList.add('is-invalid');
            } else {
                document.getElementById('last_name').classList.remove('is-invalid');
            }
            



            // Validate email format - must be a Gmail address starting with a letter
            const emailValue = document.getElementById('email').value;
            const gmailRegex = /^[a-zA-Z][a-zA-Z0-9._%+-]*@gmail\.com$/i;
            if (!gmailRegex.test(emailValue)) {
                isValid = false;

                if (emailValue.indexOf('@') > 0 && !(/^[a-zA-Z]/).test(emailValue)) {
                    errorMessages.push('Email address must start with a letter, not a number or special character');
                } else {
                    errorMessages.push('Please enter a valid Gmail address (e.g., name@gmail.com)');
                }
                document.getElementById('email').classList.add('is-invalid');
            } else {
                document.getElementById('email').classList.remove('is-invalid');
            }
            
            // Validate phone number format
            if (!/^\+251[79]\d{8}$/.test(phone)) {
                isValid = false;
                errorMessages.push('Phone number must be in Ethiopian format: +251 followed by 9 digits starting with 7 or 9');
                document.getElementById('phone').classList.add('is-invalid');
            } else {
                document.getElementById('phone').classList.remove('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please correct the following errors:\n' + errorMessages.join('\n'));
            }
        });

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
            const firstName = "<?php echo addslashes(htmlspecialchars($form_data['first_name'])); ?>";
            const lastName = "<?php echo addslashes(htmlspecialchars($form_data['last_name'])); ?>";
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Project Manager Credentials</title>
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
                        <p>Project Manager Account Credentials</p>
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
<?php if(isset($connection)) $connection->close(); ?>