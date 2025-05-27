<?php
session_start();
require '../db_connection.php';

// Initialize variables
$message = '';
$message_type = '';
$show_credentials = false;
$generated_username = '';
$generated_password = '';

// Verify admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Function to generate a strong password
function generateStrongPassword($length = 12) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';
    
    $all = $uppercase . $lowercase . $numbers . $special;
    $password = '';
    
    // Ensure at least one character from each set
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Fill the rest of the password
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    // Shuffle the password to make it more random
    return str_shuffle($password);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_admin'])) {
    try {
        $firstName = $connection->real_escape_string($_POST['first_name']);
        $lastName = $connection->real_escape_string($_POST['last_name']);
        $email = $connection->real_escape_string($_POST['email']);
        $phone = $connection->real_escape_string($_POST['phone'] ?? '');
        
        // Generate a strong password
        $password = generateStrongPassword();
        
        // Validate first name and last name (must start with capital letter and contain only letters)
        if (!preg_match('/^[A-Z][a-zA-Z]*$/', $firstName)) {
            throw new Exception("First name must start with a capital letter and contain only letters.");
        }
        
        if (!preg_match('/^[A-Z][a-zA-Z]*$/', $lastName)) {
            throw new Exception("Last name must start with a capital letter and contain only letters.");
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }
        
        // Check if email already exists
        $check_email = $connection->prepare("SELECT UserID FROM users WHERE Email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $email_result = $check_email->get_result();
        if ($email_result->num_rows > 0) {
            throw new Exception("Email address already in use. Please use a different email.");
        }
        
        // Validate phone number - only validate the 9 digits after +251
        if (!empty($phone)) {
            // Add +251 prefix if not present
            if (substr($phone, 0, 4) !== '+251') {
                $phone = '+251' . $phone;
            }
            
            // Check if the remaining part is 9 digits starting with 7 or 9
            $digits = substr($phone, 4);
            if (!preg_match('/^[79]\d{8}$/', $digits)) {
                throw new Exception("Phone number must have 9 digits after +251, starting with 7 or 9.");
            }
        } else {
            $phone = ''; // Set to empty if not provided
        }
        
        // Generate a unique username (firstname.lastname)
        $username = strtolower($firstName . '.' . $lastName);
        
        // First check if username exists
        $check = $connection->query("SELECT UserID FROM users WHERE Username = '$username'");
        if ($check->num_rows > 0) {
            // If exists, add a number to make it unique
            $counter = 1;
            while ($connection->query("SELECT UserID FROM users WHERE Username = '$username$counter'")->num_rows > 0) {
                $counter++;
            }
            $username = $username . $counter;
        }
        
        // Store the generated credentials for display
        $generated_username = $username;
        $generated_password = $password;
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Now insert with the unique username
        $query = "INSERT INTO users (Username, FirstName, LastName, Email, Password, Role, Phone) 
                  VALUES (?, ?, ?, ?, ?, 'Admin', ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("ssssss", $username, $firstName, $lastName, $email, $hashed_password, $phone);
        $stmt->execute();
        
        $message = "Admin account created successfully!";
        $message_type = "success";
        $show_credentials = true;
        
        // Clear form data after successful submission
        $firstName = $lastName = $email = $phone = '';
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Fetch admin user info for the header
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Admin Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color:rgb(115, 163, 211);
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
        .form-label {
            font-weight: 500;
        }
        .validation-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .back-btn {
            margin-bottom: 20px;
        }
        /* Enhanced create admin button */
        .create-admin-btn {
            background: linear-gradient(135deg, #4a6fdc 0%, #2c4bbd 100%);
            color: white;
            font-weight: 600;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(44, 75, 189, 0.3);
            transition: all 0.3s ease;
        }
        .create-admin-btn:hover {
            background: linear-gradient(135deg, #3a5fc9 0%, #1c3aa9 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(44, 75, 189, 0.4);
        }
        .create-admin-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(44, 75, 189, 0.4);
        }
        .create-admin-btn i {
            margin-right: 8px;
        }
        
        /* Credentials box styles */
        .credentials-box {
            background-color: #f0f8ff;
            border: 1px solid #d1e7ff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .credentials-title {
            color: #4a6fdc;
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
            color: #333;
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
            color: #4a6fdc;
            cursor: pointer;
            margin-left: 0.5rem;
            transition: all 0.2s;
        }
        
        .copy-btn:hover {
            color: #2c4bbd;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="AdminManageAccount.php?role=Admin" class="btn btn-secondary back-btn">
            <i class="bi bi-arrow-left"></i> Back to Admin Management
        </a>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Create New Admin Account</h2>
            </div>
            
            <div class="card-body">
                <?php if ($message && !$show_credentials): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_credentials): ?>
                    <!-- Display generated credentials -->
                    <div class="credentials-box">
                        <div class="credentials-title">
                            <i class="bi bi-key-fill"></i> Account Credentials
                        </div>
                        <p class="text-muted mb-3">The following credentials have been automatically generated for this admin. Please save or share these with the admin user.</p>
                        
                        <div class="credential-item">
                            <span class="credential-label">Username:</span>
                            <span class="credential-value" id="gen-username"><?php echo htmlspecialchars($generated_username); ?></span>
                            <button type="button" class="copy-btn" onclick="copyToClipboard('gen-username')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        
                        <div class="credential-item">
                            <span class="credential-label">Password:</span>
                            <span class="credential-value" id="gen-password"><?php echo htmlspecialchars($generated_password); ?></span>
                            <button type="button" class="copy-btn" onclick="copyToClipboard('gen-password')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <button type="button" class="btn btn-primary" onclick="printCredentials()">
                                <i class="bi bi-printer-fill me-2"></i>Print Credentials
                            </button>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Important:</strong> Please save this information as it won't be shown again.
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="createAdminForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required 
                                   pattern="[A-Z][a-zA-Z]*" value="<?= htmlspecialchars($firstName ?? '') ?>">
                            <div class="validation-info">Must start with a capital letter and contain only letters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                   pattern="[A-Z][a-zA-Z]*" value="<?= htmlspecialchars($lastName ?? '') ?>">
                            <div class="validation-info">Must start with a capital letter and contain only letters.</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= htmlspecialchars($email ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text">+251</span>
                                  <input type="text" class="form-control" id="phone" name="phone" 
                                       placeholder="9XXXXXXXX" value="<?= htmlspecialchars($phone ?? '') ?>">
                            </div>
                            <div class="validation-info">Format: 9 digits starting with 7 or 9</div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" name="create_admin" class="btn create-admin-btn">
                            <i class="bi bi-person-plus-fill"></i> Create Admin Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                // Show a temporary "Copied!" tooltip
                const copyBtn = element.nextElementSibling;
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalHTML;
                }, 1500);
            });
        }
        
              // Print credentials function
        function printCredentials() {
            const username = document.getElementById('gen-username').textContent;
            const password = document.getElementById('gen-password').textContent;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Admin Credentials</title>
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
                       <p>Admin Account Credentials</p>
                    </div>
                    
                    <div class="credentials">
                        <h3>Admin Account Information</h3>
                        
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
            }, 500);
        }
        
        // Form validation
        document.getElementById('createAdminForm').addEventListener('submit', function(event) {
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            const phone = document.getElementById('phone').value;
            
            // Validate first name and last name
            const namePattern = /^[A-Z][a-zA-Z]*$/;
            if (!namePattern.test(firstName)) {
                alert('First name must start with a capital letter and contain only letters.');
                event.preventDefault();
                return;
            }
            
            if (!namePattern.test(lastName)) {
                alert('Last name must start with a capital letter and contain only letters.');
                event.preventDefault();
                return;
            }
            
            // Validate phone if provided
            if (phone.trim() !== '') {
                const phonePattern = /^[79]\d{8}$/;
                if (!phonePattern.test(phone)) {
                    alert('Phone number must be 9 digits starting with 7 or 9.');
                    event.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>