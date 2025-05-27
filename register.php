<?php
$servername = "localhost"; 
$username = "root"; 
$password = "";
$dbname = "cms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = $_POST["role"]; // Get user role from form

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if an Admin already exists
        if ($role === "Admin") {
            $admin_check = "SELECT * FROM users WHERE Role = 'Admin'";
            $admin_result = $conn->query($admin_check);

            if ($admin_result->num_rows > 0) {
                echo "<script>alert('An Admin already exists! Only one Admin is allowed.');</script>";
                exit();
            }
        }

        // Check if username or email already exists using prepared statement
        $check_query = "SELECT * FROM users WHERE Username=? OR Email=?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Username or Email already exists!');</script>";
        } else {
            // Insert new user using prepared statement
            $sql = "INSERT INTO users (FirstName, LastName, Username, Email, Password, Role, RegistrationDate) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $firstname, $lastname, $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                echo "<script>alert('Registration successful!');</script>";
            } else {
                echo "Error: " . $stmt->error;
            }
        }
    }
}
?>
