<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

include '../db_connection.php';

// Fetch all users from the database EXCEPT admins
$query = "SELECT u.UserID, u.FirstName, u.LastName, u.Email, u.Phone, 
          CASE WHEN u.is_active = 1 THEN 'Active' ELSE 'Inactive' END as Status, 
          u.Role as Role 
          FROM users u 
          WHERE u.Role != 'Admin'
          ORDER BY u.Role, u.LastName, u.FirstName";
$result = $connection->query($query);
if (!$result) {
    die("Database error: " . $connection->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Users | Salale University CMS</title>
    
    <!-- Latest Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (for icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #36b9cc;
            --secondary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
        }
        body {
            background-color: #f8f9fc;
            padding-top: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: bold;
            color: #5a5c69;
        }
        .back-btn {
            margin-bottom: 20px;
        }
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #dc3545;
        }
        .btn-action {
            margin-right: 5px;
        }
        .filter-controls {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="AdminDashboard.php" class="btn btn-primary back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Manage All Users</h2>
        </div>
        <div class="card-body">
            <div class="filter-controls">
                <div class="row">
                    <div class="col-md-4">
                        <select id="roleFilter" class="form-select">
                            <option value="">All Roles</option>
                            <option value="Project Manager">Project Manager</option>
                            <option value="Contractor">Contractor</option>
                            <option value="Consultant">Consultant</option>
                            <option value="Site Engineer">Site Engineer</option>
                            <option value="Employee">Employee</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email...">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <tr data-role="<?= htmlspecialchars($user['Role']) ?>" data-status="<?= htmlspecialchars($user['Status']) ?>">
                                <td><?= htmlspecialchars($user['UserID']) ?></td>
                                <td><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></td>
                                <td><?= htmlspecialchars($user['Email']) ?></td>
                                <td><?= htmlspecialchars($user['Phone']) ?></td>
                                <td><?= htmlspecialchars($user['Role']) ?></td>
                                <td>
                                    <span class="badge <?= $user['Status'] == 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= htmlspecialchars($user['Status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('tbody tr');
    
    function filterTable() {
        const roleValue = roleFilter.value;
        const statusValue = statusFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        
        tableRows.forEach(row => {
            const role = row.getAttribute('data-role');
            const status = row.getAttribute('data-status');
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            
            const roleMatch = !roleValue || role === roleValue;
            const statusMatch = !statusValue || status === statusValue;
            const searchMatch = !searchValue || name.includes(searchValue) || email.includes(searchValue);
            
            if (roleMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    roleFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);
});
</script>
</body>
</html>
