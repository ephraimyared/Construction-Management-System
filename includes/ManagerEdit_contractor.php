<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php'; // Ensure the database connection is included

// Get the UserID from the URL
$contractor_id = $_GET['id'];

// Fetch the contractor's details from the database
$query = "SELECT * FROM users WHERE UserID = '$contractor_id' AND Role = 'Contractor'";
$result = $connection->query($query);

if ($result->num_rows == 0) {
    die("Contractor not found.");
}

$contractor = $result->fetch_assoc();

// Handle form submission to update contractor details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];

    // Update the contractor in the database
    $update_query = "UPDATE users SET FirstName = '$first_name', LastName = '$last_name', Email = '$email' WHERE UserID = '$contractor_id' AND Role = 'Contractor'";
    if ($connection->query($update_query)) {
        header("Location: manager_manage_contractor.php");
        exit();
    } else {
        $error = "Error updating contractor: " . $connection->error;
    }
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Contractor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Contractor</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $contractor['FirstName']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $contractor['LastName']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo $contractor['Email']; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Contractor</button>
    </form>
    <a href="manager_manage_contractor.php" class="btn btn-secondary mt-3">Back to Contractors</a>
</div>
</body>
</html>
