<?php
session_start();
include '../db_connection.php';

// Only allow Admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$message_type = 'success';

$search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';

// Query to get employees created by contractors
if (!empty($search)) {
    $query = "
        SELECT e.*, c.FirstName AS CreatorFirstName, c.LastName AS CreatorLastName 
        FROM users e
        JOIN users c ON e.managed_by_contractor_id = c.UserID
        WHERE e.Role = 'Employee' 
        AND c.Role = 'Contractor'
        AND (
            e.FirstName LIKE '%$search%' OR 
            e.LastName LIKE '%$search%' OR 
            e.Email LIKE '%$search%' OR
            e.UserID LIKE '%$search%' OR
            e.Phone LIKE '%$search%'
        )
        ORDER BY e.UserID DESC
    ";
} else {
    $query = "
        SELECT e.*, c.FirstName AS CreatorFirstName, c.LastName AS CreatorLastName 
        FROM users e
        JOIN users c ON e.managed_by_contractor_id = c.UserID
        WHERE e.Role = 'Employee' 
        AND c.Role = 'Contractor'
        ORDER BY e.UserID DESC
    ";
}

$employees = $connection->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employees Created by Contractors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 60px auto 30px auto;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .card-header {
            background: linear-gradient(135deg, #283e51, #485563);
            color: #fff;
            padding: 1rem 1.5rem;
            border-bottom: none;
        }

        .table thead th {
            background-color: #334e68;
            color: white;
        }

        .table tbody tr:hover {
            background-color: rgba(51, 78, 104, 0.05);
        }

        .badge-role {
            background-color: #f39c12;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
            color: #fff;
        }

        .badge-contractor {
            background-color: #2ecc71;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
            color: #fff;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-left: 2.5rem;
            border-radius: 30px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #283e51;
        }

        .btn-back {
            position: absolute;
            top: 15px;
            left: 15px;
            border-radius: 30px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #5d6d7e, #34495e);
            color: white;
            font-weight: 500;
            text-decoration: none;
        }

        .btn-back:hover {
            opacity: 0.9;
        }

        .no-results {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
    </style>
</head>
<body>

<a href="AdminManageAccount.php?role=Employee" class="btn btn-back">
    <i class="fas fa-arrow-left me-2"></i> Back to Management
</a>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-hard-hat me-2"></i>Employees Created by Contractors</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="search-box">
                <div class="input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="Search employees by name, email, phone, or ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees && $employees->num_rows > 0): ?>
                            <?php while ($row = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['UserID']) ?></td>
                                    <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                                    <td><?= htmlspecialchars($row['Email']) ?></td>
                                    <td><?= htmlspecialchars($row['Phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge-contractor">
                                            <?= htmlspecialchars($row['CreatorFirstName'] . ' ' . $row['CreatorLastName']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-results">
                                    <i class="fas fa-hard-hat fa-2x mb-3"></i>
                                    <h5>No Employees Created by Contractors Found</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Handle search input
    document.querySelector('input[name="search"]').addEventListener('input', function () {
        clearTimeout(this.delay);
        this.delay = setTimeout(() => {
            this.form.submit();
        }, 400);
    });
</script>
</body>
</html>
