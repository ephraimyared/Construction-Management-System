<?php
session_start();
include '../db_connection.php';

// Check if logged in as Consultant
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Consultant') {
    header("Location: unauthorized.php");
    exit();
}

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT * FROM users WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch Consultant-specific projects
$project_query = "SELECT * FROM projects WHERE consultant_id = ?";
$stmt = $connection->prepare($project_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Count total projects
$total_projects = $projects_result->num_rows;

// Count projects by status
$status_counts = [
    'Planning' => 0,
    'In Progress' => 0,
    'On Hold' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

// Reset result pointer to count by status
if ($total_projects > 0) {
    while ($project = $projects_result->fetch_assoc()) {
        if (isset($status_counts[$project['status']])) {
            $status_counts[$project['status']]++;
        }
    }
    // Reset pointer for later use
    $projects_result->data_seek(0);
}

// Also fetch projects from project_assignments table where the consultant is assigned
$assigned_projects_query = "SELECT p.*, pa.start_date as assignment_start, pa.end_date as assignment_end, pa.status as assignment_status 
                           FROM projects p 
                           JOIN project_assignments pa ON p.project_id = pa.project_id 
                           WHERE pa.contractor_id = ? AND pa.role_in_project = 'Assigned Consultant'";
$stmt = $connection->prepare($assigned_projects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assigned_projects_result = $stmt->get_result();

// Add these to our counts
$total_projects += $assigned_projects_result->num_rows;

// Count assigned projects by status
if ($assigned_projects_result->num_rows > 0) {
    while ($project = $assigned_projects_result->fetch_assoc()) {
        $status = $project['assignment_status'] ?? $project['status'];
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        } else {
            // If status is not one of our predefined statuses, count as In Progress
            $status_counts['In Progress']++;
        }
    }
    // Reset pointer for later use
    $assigned_projects_result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultant Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --danger-color: #e63946;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --body-bg: #f5f7fb;
            --card-border-radius: 0.75rem;
            --border-radius-sm: 0.375rem;
            --box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--body-bg);
            color: var(--dark-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
            color: var(--accent-color);
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
            color: var(--accent-color);
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
            border-left-color: var(--accent-color);
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
            background: rgba(255, 255, 255, 0.2);
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
            color: #e74c3c;
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: var(--transition);
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--card-border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }

        .welcome-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.75rem;
            position: relative;
        }

        .welcome-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            position: relative;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .stat-total {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stat-progress {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--accent-color);
        }

        .stat-completed {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .stat-hold {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin: 0.25rem 0 0;
        }

        .dashboard-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            border-radius: var(--card-border-radius);
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: var(--dark-color);
            display: flex;
            flex-direction: column;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            color: var(--dark-color);
        }

        .action-icon {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .action-report {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
        }

        .action-status {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
        }

        .action-profile {
            background: linear-gradient(135deg, var(--success-color), var(--accent-color));
            color: white;
        }

        .action-tasks {
            background: linear-gradient(135deg, var(--accent-color), var(--success-color));
            color: white;
        }

        .action-content {
            padding: 1.5rem;
        }

        .action-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
        }

        .action-description {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin: 0;
        }

        .recent-projects {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        }

        .project-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .project-item {
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem;
            background: rgba(0, 0, 0, 0.02);
            transition: background 0.3s ease;
        }

        .project-item:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        .project-item:last-child {
            margin-bottom: 0;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .project-name {
            font-weight: 600;
            margin: 0;
        }

        .project-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .status-planning {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .status-progress {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--accent-color);
        }

        .status-hold {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
        }

        .status-completed {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .status-cancelled {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--danger-color);
        }

        .project-dates {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-bottom: 0.5rem;
        }

        .project-date {
            display: flex;
            align-items: center;
        }

        .project-date i {
            margin-right: 0.25rem;
        }

        .project-description {
            font-size: 0.9rem;
            margin: 0 0 0.75rem;
        }

        .project-actions {
            display: flex;
            justify-content: flex-end;
        }

        .btn-view {
            font-size: 0.85rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn-view:hover {
            background-color: rgba(67, 97, 238, 0.2);
        }

        .no-projects {
            text-align: center;
            padding: 2rem;
            color: var(--gray-color);
        }

        .no-projects i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-projects p {
            margin: 0;
            font-size: 1.1rem;
        }

        /* Responsive Design */
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
            
            .welcome-header {
                padding: 1.5rem;
            }
            
            .welcome-header h2 {
                font-size: 1.5rem;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(100%, 1fr));
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
            <a href="ConsultantDashboard.php" class="sidebar-logo">
                   <span class="logo-text"><img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="ConsultantDashboard.php" class="active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="ConsultantPrepareReport.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Submit Reports</span>
                </a>
            </li>
            <li>
                <a href="ConsultantViewAssignedTasks.php">
                    <i class="fas fa-project-diagram"></i>
                    <span>Track Projects status</span>
                </a>
            </li>
            <li>
                <a href="ConsultantProfile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'C'; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Consultant'; ?></div>
                    <div class="user-role">Consultant</div>
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
            <!-- Welcome Header -->
            <div class="welcome-header">
                <h2>Welcome, <?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : 'Consultant'; ?>!</h2>
                <p>Here's an overview of your projects and activities</p>
            </div>

            <!-- Stats Overview -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon stat-total">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-value"><?php echo $total_projects; ?></h3>
                        <p class="stat-label">Total Projects</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-progress">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-value"><?php echo $status_counts['In Progress']; ?></h3>
                        <p class="stat-label">In Progress</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-actions">
                <a href="ConsultantPrepareReport.php" class="action-card">
                    <div class="action-icon action-report">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="action-content">
                        <h3 class="action-title">Submit Reports</h3>
                        <p class="action-description">Create and submit project reports and updates</p>
                    </div>
                </a>

                <a href="ConsultantViewAssignedTasks.php" class="action-card">
                    <div class="action-icon action-status">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="action-content">
                        <h3 class="action-title">Track Projects Status</h3>
                        <p class="action-description">Check all your assigned projects and details</p>
                    </div>
                </a>
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


