<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php'; // Ensure the database connection is included

// Fetch user counts for Contractors, Consultants, and Site Engineers
$query = "SELECT Role, COUNT(*) as user_count FROM users WHERE Role IN ('Contractor', 'Consultant', 'Site Engineer') GROUP BY Role";
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
    <title>Manage Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
            background-color: #198754;
            color: white;
            border: none;
        }

        .back-btn:hover {
            background-color: #157347;
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

<a href="logout.php" class="btn logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
<a href="ManagerDashboard.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>

<div class="dashboard-container">
    <div class="header">
        <h2><i class="fas fa-users-cog"></i> Manage Contractors & Consultants</h2>
        <p class="text-muted">Click on a role to manage users</p>
    </div>

    <div class="role-dashboard">
        <div class="role-card" onclick="redirectToRole('Contractor')">
            <img src="../images/contractor.jpg" class="role-icon" alt="Contractor">
            <div class="role-name">Contractors</div>
            <small class="text-muted">Handle construction tasks</small>
            <div class="user-count"><?php echo isset($user_counts['Contractor']) ? $user_counts['Contractor'] : 0; ?> Users</div>
        </div>

        <div class="role-card" onclick="redirectToRole('Consultant')">
            <img src="../images/consultant.jpg" class="role-icon" alt="Consultant">
            <div class="role-name">Consultants</div>
            <small class="text-muted">Provide expert advice</small>
            <div class="user-count"><?php echo isset($user_counts['Consultant']) ? $user_counts['Consultant'] : 0; ?> Users</div>
        </div>

        <div class="role-card" onclick="redirectToRole('Site Engineer')">
            <img src="../images/site_engineer.jpg" class="role-icon" alt="Site Engineer">
            <div class="role-name">Site Engineers</div>
            <small class="text-muted">Supervise construction sites</small>
            <div class="user-count"><?php echo isset($user_counts['Site Engineer']) ? $user_counts['Site Engineer'] : 0; ?> Users</div>
        </div>
    </div>
</div>

<script>
    function redirectToRole(role) {
        const rolePages = {
            'Contractor': 'manager_manage_contractor.php',
            'Consultant': 'manager_manage_consultant.php',
            'Site Engineer': 'manager_manage_site_engineer.php'
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
