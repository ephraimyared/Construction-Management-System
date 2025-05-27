<?php
session_start();
include '../db_connection.php';

// Admin check: Only Project Managers can create Contractors or Consultants
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role']; // Either 'Contractor' or 'Consultant' chosen by Project Manager

    // Check if username already exists
    $stmt = $connection->prepare("SELECT * FROM users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Username already exists!";
    } else {
        // Insert new Contractor or Consultant into the users table
        $stmt = $connection->prepare("INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $password, $firstName, $lastName, $email, $phone, $role);
        $stmt->execute();

        echo "$role created successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Contractor or Consultant</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional: link to external CSS for style -->
    <style>
        /* Add custom CSS styles for a better UI */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
        }

        input[type="text"], input[type="password"], input[type="email"], input[type="tel"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .back-link a {
            text-decoration: none;
            color: #007BFF;
        }

        .back-link a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Contractor or Consultant</h2>
    <form method="POST" action="ProjectManagerCreateAccount.php">
        <label for="username">Username</label>
        <input type="text" name="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" required>

        <label for="first_name">First Name</label>
        <input type="text" name="first_name" required>

        <label for="last_name">Last Name</label>
        <input type="text" name="last_name" required>

        <label for="email">Email</label>
        <input type="email" name="email" required>

        <label for="phone">Phone (with +251)</label>
        <div style="display: flex; justify-content: space-between;">
            <input type="text" value="+251" disabled style="width: 20%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc;">
            <input type="tel" name="phone" required pattern="7\d{8}|9\d{8}" placeholder="Enter phone number starting with 7 or 9" style="width: 75%; padding: 12px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc;">
        </div>

        <label for="role">Role</label>
        <select name="role" required>
            <option value="Contractor">Contractor</option>
            <option value="Consultant">Consultant</option>
        </select>

        <button type="submit">Create User</button>
    </form>

    <div class="back-link">
        <a href="ManageAccount.php">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
