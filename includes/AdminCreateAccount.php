<?php
session_start();
include '../db_connection.php';

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input values and trim them
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Don't hash yet for validation
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']); // Get the phone number
    $role = 'Project Manager'; // Always create Project Manager here

    $error_message = ''; // Initialize error message

    // Validate First Name
    if (!startsWithCapitalAndLettersOnly($first_name)) {
        $error_message .= "First name must start with a capital letter and contain only letters.<br>";
    }

    // Validate Last Name
    if (!startsWithCapitalAndLettersOnly($last_name)) {
        $error_message .= "Last name must start with a capital letter and contain only letters.<br>";
    }

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message .= "Invalid email format.<br>";
    }

    // Validate Username
    if (!validate_username($username)) {
        $error_message .= "Invalid username! It must start with a letter, @, or _ and can include letters, numbers, @, and _.<br>";
    }

    // Validate Password
    if (!validate_password($password)) {
        $error_message .= "Password must be 6 to 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one of the following symbols: @ or _.<br>";
    }

    // If there are no validation errors, proceed to check for existing username and insert
    if (empty($error_message)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Check if username already exists
        $stmt = $connection->prepare("SELECT * FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Username already exists!";
        } else {
            // Insert new user into the database
            $stmt = $connection->prepare("INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $username, $hashed_password, $first_name, $last_name, $email, $phone, $role);
            if ($stmt->execute()) {
                $success_message = "Project Manager created successfully!";
            } else {
                $error_message = "Error creating Project Manager. Please try again.";
            }
        }
        $stmt->close();
    }
    $connection->close();
}
?>
 <a href="AdminManageAccount.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back 
                </a>
                <style>
         .back-button {
            position: absolute;
            top: 66px;
            left: 20px;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: white;
            border: none;
            background-color: #3498db;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .back-button:hover {
            background-color:rgb(21, 148, 79);
            transform: translateY(-2px);
        }</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project Manager</title>
    <style>
       
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 60%;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 16px;
            margin: 10px 0 5px;
        }
        input {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            padding: 10px;
            background-color: #5cb85c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #4cae4c;
        }
        .message {
            color: #d9534f;
            font-size: 16px;
            text-align: center;
        }
        .success {
            color: #5bc0de;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }
        .back-link a {
            text-decoration: none;
            color: #337ab7;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .phone-input-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .phone-prefix {
            padding: 10px;
            background-color: #eee;
            border: 1px solid #ccc;
            border-right: none;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
            color: #333;
            width: 40px;
            text-align: center;
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }
        #phone-number-field {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 0 5px 5px 0;
            font-size: 14px;
            flex: 1;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Project Manager</h2>

        <?php if (isset($error_message)): ?>
            <p class="message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <p class="message success"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <form method="POST" action="AdminCreateAccount.php">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" placeholder="Enter First Name" required>

            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" placeholder="Enter Last Name" required>

            <label for="email">Email</label>
            <input type="email" name="email" placeholder="Enter Email Address" required>

            <label for="phone">Phone</label>
            <input type="text" name="phone" placeholder="Enter Phone Number" required>

            <label for="username">Username</label>
            <input type="text" name="username" placeholder="Enter Username" required>

            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Enter Password" required>

            <button type="submit">Create Project Manager</button>
        </form>

        <div class="back-link">
            <a href="ManageAccount.php">All Users List</a>
        </div>
    </div>
</body>
</html>
