<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php'; // Ensure the database connection is included

// Fetch all site engineers from the database
$query = "SELECT * FROM users WHERE Role = 'Site Engineer'";
$result = $connection->query($query);

if ($result->num_rows == 0) {
    $engineers = []; // Initialize as empty array if no engineers found
}
else{
    $engineers = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle deletion
if (isset($_GET['delete_id'])) { // Check if delete_id is set
    $delete_id = intval($_GET['delete_id']);
     $delete_query = "DELETE FROM users WHERE UserID = ? AND Role = 'Site Engineer'";
    $delete_stmt = $connection->prepare($delete_query);
    $delete_stmt->bind_param("i", $delete_id);

    if ($delete_stmt->execute()) {
       // Deletion successful, you might want to set a success message
        header("Location: manager_manage_site_engineer.php");
        exit();
    } else {
        // Deletion failed, set an error message
        $error_message = "Failed to delete Site Engineer: " . $connection->error;
    }
}


$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Site Engineers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
        .container {
            margin-top: 20px;
        }
        h2 {
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-bottom: 20px;
        }
        .btn-primary {
            margin-right: 10px;
        }
        .btn-secondary {
            margin-right: 10px;
        }
        .btn-danger {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Site Engineers</h2>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($engineers) > 0): ?>
                        <?php foreach ($engineers as $engineer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($engineer['FirstName']); ?></td>
                                <td><?php echo htmlspecialchars($engineer['LastName']); ?></td>
                                <td><?php echo htmlspecialchars($engineer['Email']); ?></td>
                                <td>
                                    <a href="manager_edit_site_engineer.php?id=<?php echo $engineer['UserID']; ?>" class="btn btn-primary">Edit</a>
                                     <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $engineer['UserID']; ?>">
                                        Delete
                                    </button>

                                    <div class="modal fade" id="deleteModal<?php echo $engineer['UserID']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $engineer['UserID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $engineer['UserID']; ?>">Confirm Deletion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this Site Engineer?
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($engineer['Email']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="manager_manage_site_engineer.php?delete_id=<?php echo $engineer['UserID']; ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No Site Engineers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="ManagerManageUsers.php" class="btn btn-secondary">Back to Page</a>
        <a href="ManagerCreateSiteEng.php" class="btn btn-success">Create New Site Engineer</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
