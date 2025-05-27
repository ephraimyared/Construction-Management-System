<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php'; // Ensure the database connection is included

// Fetch user counts by role
$query = "SELECT Role, COUNT(*) as user_count FROM users GROUP BY Role";
$result = $connection->query($query);
if (!$result) {
    die("Database error: " . $connection->error);
}
$user_counts = [];
while ($row = $result->fetch_assoc()) {
    $user_counts[$row['Role']] = $row['user_count'];
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
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
            color: #ff6600;
            font-weight: bold;
        }

        .role-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 25px;
        }

        .role-card {
            background: #f1f3f5;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            border: 1px solid #ced4da;
            transition: all 0.3s ease-in-out;
        }

        .role-card:hover {
            transform: translateY(-6px);
            background-color: #ffe8cc;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .role-icon {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-bottom: 15px;
            border-radius: 50%;
            border: 2px solid #ff6600;
            padding: 5px;
            background-color: #fff;
        }

        .role-name {
            font-weight: bold;
            color: #343a40;
            margin-top: 10px;
        }

        .text-muted {
            font-size: 14px;
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
            background-color: #3498db;
            color: white;
            border: none;
        }

        .back-btn:hover {
            background-color:rgb(21, 148, 79);
        }

        .user-count {
            font-size: 18px;
            font-weight: bold;
            color: #ff6600;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<!-- <a href="logout.php" class="btn logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a> -->
<a href="AdminManageAccount.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>

<div class="dashboard-container">
    <div class="header">
        <h2><i class="fas fa-users-cog"></i> User Role Management</h2>
        <p class="text-muted">Select a role to manage user accounts</p>
    </div>

    <div class="role-dashboard">
        <!-- Project Manager -->
        <div class="role-card" onclick="redirectToRole('Project Manager')">
            <img src="../images/manager.jpg" class="role-icon" alt="Project Manager">
            <div class="role-name">Project Managers</div>
            <small class="text-muted">Manage construction projects</small>
            <div class="user-count"><?php echo isset($user_counts['Project Manager']) ? $user_counts['Project Manager'] : 0; ?> Users</div>
        </div>

        <!-- Contractor -->
        <div class="role-card" onclick="redirectToRole('Contractor')">
            <img src="../images/contractor.jpg" class="role-icon" alt="Contractor">
            <div class="role-name">Contractors</div>
            <small class="text-muted">Handle construction tasks</small>
            <div class="user-count"><?php echo isset($user_counts['Contractor']) ? $user_counts['Contractor'] : 0; ?> Users</div>
        </div>

        <!-- Consultant -->
        <div class="role-card" onclick="redirectToRole('Consultant')">
            <img src="../images/consultant.jpg" class="role-icon" alt="Consultant">
            <div class="role-name">Consultants</div>
            <small class="text-muted">Provide expert advice</small>
            <div class="user-count"><?php echo isset($user_counts['Consultant']) ? $user_counts['Consultant'] : 0; ?> Users</div>
        </div>

        <!-- Site Engineer -->
        <div class="role-card" onclick="redirectToRole('Site Engineer')">
            <img src="../images/site_engineer.jpg" class="role-icon" alt="Site Engineer">
            <div class="role-name">Site Engineers</div>
            <small class="text-muted">Supervise construction</small>
            <div class="user-count"><?php echo isset($user_counts['Site Engineer']) ? $user_counts['Site Engineer'] : 0; ?> Users</div>
        </div>

        <!-- Employee -->
        <div class="role-card" onclick="redirectToRole('Employee')">
            <img src="../images/employee.jpg" class="role-icon" alt="Employee">
            <div class="role-name">Employees</div>
            <small class="text-muted">Perform assigned tasks</small>
            <div class="user-count"><?php echo isset($user_counts['Employee']) ? $user_counts['Employee'] : 0; ?> Users</div>
        </div>
    </div>
</div>

<script>
    function redirectToRole(role) {
        const rolePages = {
            'Project Manager': 'manager_management.php',
            'Contractor': 'contractor_management.php',
            'Consultant': 'consultant_management.php',
            'Site Engineer': 'engineer_management.php',
            'Employee': 'employee_management.php'
        };

        if (rolePages[role]) {
            window.location.href = rolePages[role];
        } else {
            alert('No management page found for ' + role);
        }
    }
</script>

</body>
</html>


