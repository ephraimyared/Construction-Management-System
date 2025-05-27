<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php';

// Fetch Consultants from the database
$query = "SELECT UserID, FirstName, LastName, Email, Role FROM users WHERE Role = 'Consultant'";
$result = $connection->query($query);
if (!$result) {
    die("Database error: " . $connection->error);
}
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Consultants</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #343a40, #495057);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            color: #343a40;
        }

        .dashboard-container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h2 {
            color: #007bff;
            font-weight: bold;
        }

        .btn {
            margin-top: 10px;
        }

        .logout-btn, .back-btn {
            position: fixed;
            top: 20px;
            padding: 10px 20px;
            z-index: 1000;
            border-radius: 6px;
            font-weight: bold;
        }

        .logout-btn {
            right: 20px;
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .back-btn {
            left: 20px;
            background-color: #198754;
            color: white;
            border: none;
        }

        .back-btn:hover {
            background-color: #157347;
        }
    </style>
</head>
<body>

<a href="logout.php" class="btn logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
<a href="ManagerManageUsers.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>

<div class="dashboard-container">
    <div class="header">
        <h2><i class="fas fa-user-tie"></i> Manage Consultants</h2>
        <p class="text-muted">Below is a list of Consultants</p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['UserID']; ?></td>
                    <td><?php echo $row['FirstName']; ?></td>
                    <td><?php echo $row['LastName']; ?></td>
                    <td><?php echo $row['Email']; ?></td>
                    <td>
                        <a href="ManagerEdit_consultant.php?id=<?php echo $row['UserID']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <!-- Trigger Modal -->
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['UserID']; ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>

                        <!-- Delete Confirmation Modal -->
                        <div class="modal fade" id="deleteModal<?php echo $row['UserID']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $row['UserID']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $row['UserID']; ?>"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete consultant <strong><?php echo $row['FirstName'] . ' ' . $row['LastName']; ?></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <a href="ManagerDelete_consultant.php?id=<?php echo $row['UserID']; ?>" class="btn btn-danger">Yes, Delete</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Modal -->
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
