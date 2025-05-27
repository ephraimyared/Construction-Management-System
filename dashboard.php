<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="CSS/dashboardStyle.css">
</head>
<body>

    <?php
    session_start();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
        header("Location: login.php");
        exit();
    }
    ?>

    <h1>Welcome to the Dashboard</h1>

    <div class="dashboard-container">
        <div class="role-item">
            <img src="images/Admin.jpg" alt="Admin login" width="150" height="150">
            <button><a href="includes/adminLogin.php">Admin</a></button>
        </div>
        <div class="role-item">
            <img src="images/manager.jpg" alt="Project Manager login" width="150" height="150">
            <button><a href="includes/project_manager_dashboard.php">Project Manager</a></button>
        </div>
        <div class="role-item">
            <img src="images/contractor.jpg" alt="Contractor login" width="150" height="150">
            <button><a href="includes/contractor_dashboard.php">Contractor</a></button>
        </div>
        <div class="role-item">
            <img src="images/consultant.jpg" alt="Consultant login" width="150" height="150">
            <button><a href="includes/consultant_dashboard.php">Consultant</a></button>
        </div>
        <div class="role-item">
            <img src="images/site_engineer.jpg" alt="Site Engineer login" width="150" height="150">
            <button><a href="includes/site_engineer_dashboard.php">Site Engineer</a></button>
        </div>
        <div class="role-item">
            <img src="images/employee.jpg" alt="Employee login" width="150" height="150">
            <button><a href="includes/employee_dashboard.php">Employee</a></button>
        </div>
    </div>

    <!-- Logout Link -->
    <div class="logout-link">
        <p><a href="logout.php">Logout</a></p>
    </div>

</body>
</html>
