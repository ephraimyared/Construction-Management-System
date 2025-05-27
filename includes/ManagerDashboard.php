<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetching projects for the manager
$projects_query = "SELECT * FROM projects WHERE manager_id = ?";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Count projects by status
$total_projects = $projects_result->num_rows;
$active_projects = 0;
$pending_approval = 0;
$completed_projects = 0;
$hold_projects = 0; // Initialize the hold_projects variable

// For debugging
$debug_info = [];

// Reset the result pointer
$projects_result->data_seek(0);
while ($project = $projects_result->fetch_assoc()) {
    // Add to debug info
    $debug_info[] = [
        'project_id' => $project['project_id'],
        'project_name' => $project['project_name'],
        'status' => $project['status'],
        'decision_status' => $project['decision_status']
    ];
    
    // Count based on decision_status
    if ($project['decision_status'] == '') {
        // Projects created but not yet submitted for approval
        $pending_approval++;
    } 
    else if ($project['decision_status'] == 'Pending') {
        // Projects submitted but waiting for admin decision
        $pending_approval++;
    }
    else if ($project['decision_status'] == 'Approved') {
        if ($project['status'] == 'Completed') {
            // Approved and completed projects
            $completed_projects++;
        } 
        else {
            // All approved projects (regardless of status) that aren't completed
            $active_projects++;
        }
    }
    else if ($project['decision_status'] == 'Rejected') {
        // Rejected projects
        $hold_projects++;
    }
}

// Uncomment for debugging
// echo "<pre>";
// print_r($debug_info);
// echo "</pre>";

// Fetching contractors for "Assign Task" use case
$contractors_query = "SELECT * FROM users WHERE Role = 'Contractor'";
$stmt = $connection->prepare($contractors_query);
$stmt->execute();
$contractors_result = $stmt->get_result();

// Reset the projects result pointer for the main display
$projects_result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Manager Dashboard | Construction Management System</title>
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color:rgb(54, 62, 69);
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --gray:rgb(65, 71, 76);
            --gray-light: #f8f9fa;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: var(--dark-color);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
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
            color: var(--primary-color);
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
            transition: all 0.3s ease;
        }

        .toggle-sidebar:hover {
            color: var(--primary-color);
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
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary-color);
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
            background: linear-gradient(135deg, var(--primary-color),rgb(64, 67, 73));
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
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 10px 0;
        }

        .logo-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .logo-subtitle {
            font-size: 0.9rem;
            color: var(--light-color);
        }

        /* Navigation */
        .main-nav {
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .nav-links a {
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--primary-color);
        }

        .nav-links a.active {
            border-bottom: 3px solid var(--primary-color);
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        /* Dashboard Stats */
        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            border-radius: 50%;
            color: white;
        }

        .stat-icon.blue { background-color: var(--primary-color); }
        .stat-icon.green { background-color: var(--success-color); }
        .stat-icon.orange { background-color: var(--warning-color); }
        .stat-icon.red { background-color: var(--danger-color); }

        /* Manager Info */
        .manager-info {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .manager-info h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .manager-info li {
            background-color: var(--gray-light);
            transition: transform 0.3s;
        }

        .manager-info li:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Action Buttons */
        .action-button {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: var(--dark-color);
        }

        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            color: var(--dark-color);
        }

        .action-icon {
            border-radius: 50%;
            color: white;
        }

        .action-icon.blue { background-color: var(--primary-color); }
        .action-icon.green { background-color: var(--success-color); }
        .action-icon.orange { background-color: var(--warning-color); }
        .action-icon.purple { background-color: #9b59b6; }

        /* Projects Section */
        .project-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .project-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .status-active {
            background-color: var(--success-color);
        }
        
        .status-pending {
            background-color: var(--warning-color);
        }
        .status-completed {
            background-color: var(--primary-color);
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-top: 40px;
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
                padding: 15px;
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
                <a href="ManagerDashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="ManageProjects.php">
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
                    <span>Manage Reports</span>
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
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="fw-bold">Welcome, <?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : 'Manager'; ?>!</h1>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon blue p-3 me-3">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Projects</h6>
                            <h3 class="fw-bold mb-0"><?php echo $total_projects; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon green p-3 me-3">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Active Projects</h6>
                            <h3 class="fw-bold mb-0"><?php echo $active_projects; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stat-card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon orange p-3 me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Pending Approval</h6>
                            <h3 class="fw-bold mb-0"><?php echo $pending_approval; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stat-card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon purple p-3 me-3">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">On Hold</h6>
                            <h3 class="fw-bold mb-0"><?php echo isset($hold_projects) ? $hold_projects : 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stat-card p-3 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon red p-3 me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Completed</h6>
                            <h3 class="fw-bold mb-0"><?php echo $completed_projects; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Quick Actions -->
           <!-- Project Manager Responsibilities -->
<div class="col-12 mb-5">
    <h3 class="position-relative pb-3 mb-4 section-title">Project Manager Responsibilities</h3>
    <div class="row g-4">
        <!-- Project Planning -->
        <div class="col-lg-6 mb-4">
            <a href="create_project.php" class="action-button d-block p-4 h-100">
                <div class="d-flex align-items-center">
                    <div class="action-icon blue p-3 me-3">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h5 class="mb-2">Project Planning</h5>
                        <p class="text-muted mb-0">Create and schedule construction projects</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Team Coordination -->
        <div class="col-lg-6 mb-4">
            <a href="ProjectAssignment.php" class="action-button d-block p-4 h-100">
                <div class="d-flex align-items-center">
                    <div class="action-icon green p-3 me-3">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div>
                        <h5 class="mb-2">Team Coordination</h5>
                        <p class="text-muted mb-0">Assign tasks to contractors and consultants</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Progress Monitoring -->
        <div class="col-lg-6 mb-4">
            <a href="ManagerManageReport.php" class="action-button d-block p-4 h-100">
                <div class="d-flex align-items-center">
                    <div class="action-icon orange p-3 me-3">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h5 class="mb-2">Progress Monitoring</h5>
                        <p class="text-muted mb-0">Track and report project progress</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Project Task Approval -->
        <div class="col-lg-6 mb-4">
            <a href="SendProjectForApproval.php" class="action-button d-block p-4 h-100">
                <div class="d-flex align-items-center">
                    <div class="action-icon purple p-3 me-3">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <h5 class="mb-2">Project Tasks Approval</h5>
                        <p class="text-muted mb-0">Submit project Tasks for admin to review</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
