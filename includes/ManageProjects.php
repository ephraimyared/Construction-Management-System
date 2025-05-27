<?php
session_start();
include '../db_connection.php';

// Check session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

// Get manager's projects count
$user_id = $_SESSION['user_id'];
$project_count_query = "SELECT COUNT(*) as total FROM projects WHERE manager_id = ?";
$stmt = $connection->prepare($project_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();
$project_count = $count_result['total'];

// Get user info
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Projects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff6600;
            --primary-light: #ff8533;
            --primary-dark: #e65c00;
            --secondary: #4361ee;
            --success: #2ecc71;
            --info: #3498db;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --gradient: linear-gradient(135deg, var(--primary), var(--primary-light));
            --shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7ff;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
            transition: var(--transition);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: var(--dark);
            color: white;
            z-index: 1000;
            transition: var(--transition);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .sidebar-logo i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .sidebar-collapsed .logo-text {
            display: none;
        }

        .toggle-sidebar {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            color: var(--primary);
        }

        .sidebar-collapsed .toggle-sidebar {
            transform: rotate(180deg);
        }

        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary);
        }

        .sidebar-menu i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-collapsed .sidebar-menu span {
            display: none;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .user-details {
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-collapsed .user-details {
            display: none;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: var(--transition);
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .dashboard-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
            border-top: 6px solid var(--primary);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h2 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .action-dashboard {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding-bottom: 10px;
        }

        .option-card {
            flex: 0 0 250px;
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            color: var(--dark);
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .option-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
        }

        .option-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .option-card small {
            display: block;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .badge-count {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            font-size: 14px;
            font-weight: 600;
            border-radius: 50px;
            padding: 5px 15px;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: var(--sidebar-collapsed-width);
            }
            
            .logo-text, .sidebar-menu span, .user-details, .logout-text {
                display: none;
            }
            
            .main-content {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .sidebar-collapsed .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .sidebar-collapsed .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .dashboard-container {
                padding: 20px;
            }
            
            .option-card {
                flex: 0 0 100%;
            }
        }
        img {
         max-width: 80%; 
         height: auto; 
            }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="ManagerDashboard.php" class="sidebar-logo">
                <span class="logo-text"> <img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="ManagerDashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="ManageProjects.php" class="active">
                    <i class="fas fa-project-diagram"></i>
                    <span>Manage Projects</span>
                </a>
            </li>
            <li>
                <a href="ProjectAssignment.php">
                    <i class="fas fa-tasks"></i>
                    <span>Project Assignment</span>
                </a>
            </li>
            <li>
                <a href="ManagerManageReport.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </a>
            </li>
            <li>
                <a href="ManagerProfile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'M'; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Project Manager'; ?></div>
                    <div class="user-role">Project Manager</div>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="logout-text">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <div class="header">
                <h2><i class="fas fa-cogs"></i> Manage Projects</h2>
                <p class="text-muted">Select an option to manage your projects efficiently</p>
            </div>

            <div class="action-dashboard">
                <!-- Create Project -->
                <div class="option-card" onclick="location.href='create_project.php'">
                    <div class="option-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="option-name">Create and Schedule Project</div>
                    <small>Start a new project</small>
                </div>

                <!-- View Reports -->
                <div class="option-card" onclick="location.href='SendProjectForApproval.php'">
                    <div class="option-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="option-name">Send for Approval</div>
                    <small>Send the project Tasks to Admin</small>
                </div>
                <!-- Approved/Rejected Projects -->
                <div class="option-card" onclick="location.href='ViewApprovedRejectedProjects.php'">
                    <div class="option-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="option-name">Approved/Rejected Project Tasks</div>
                    <small>View status of your submissions</small>
                </div>

                <!-- View All Projects -->
                <div class="option-card" onclick="location.href='view_projects.php'">
                    <div class="option-icon"><i class="fas fa-list"></i></div>
                    <div class="option-name">View All Projects</div>
                    <small>Manage your existing projects</small>
                    <div class="badge-count"><?php echo $project_count; ?></div>
                </div>

                <!-- Update Project -->
                <div class="option-card" onclick="location.href='update_project.php'">
                    <div class="option-icon"><i class="fas fa-edit"></i></div>
                    <div class="option-name">Update Project</div>
                    <small>Modify existing project details</small>
                </div>

                <!-- Project Timeline -->
                <div class="option-card" onclick="location.href='project_timeline.php'">
                    <div class="option-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="option-name">Project Timeline</div>
                    <small>View and manage project schedules</small>
                </div>

                <!-- Project Reports -->
                <div class="option-card" onclick="location.href='ManagerManageReport.php' ">
                    <div class="option-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="option-name">Project Reports</div>
                    <small>Generate and view project reports</small>
                </div>
                <div class="option-card" onclick="location.href='ManagerSubmitFinishedProjects.php' ">
                    <div class="option-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="option-name">Project Submission</div>
                    <small>Submit Finished Projects</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar functionality
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });

        // Responsive behavior for small screens
        function checkScreenSize() {
            if (window.innerWidth < 992) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }

        // Check on load and resize
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
    </script>
</body>
</html>
<?php $connection->close(); ?>

        
