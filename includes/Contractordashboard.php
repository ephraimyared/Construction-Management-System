<?php
session_start();
include '../db_connection.php';

// Check if the user is logged in and is a Contractor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Contractor') {
    header("Location: unauthorized.php");
    exit();
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch projects for the contractor
$project_query = "SELECT * FROM projects WHERE contractor_id = ?";
$stmt = $connection->prepare($project_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Count projects by status
$total_projects = $projects_result->num_rows;
$active_projects = 0;
$completed_projects = 0;
$pending_projects = 0;

// Reset the result pointer
$projects_result->data_seek(0);
while ($project = $projects_result->fetch_assoc()) {
    if ($project['status'] == 'Active' || $project['status'] == 'In Progress') {
        $active_projects++;
    } elseif ($project['status'] == 'Completed') {
        $completed_projects++;
    } else {
        $pending_projects++;
    }
}

// Reset the result pointer again for the main display
$projects_result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contractor Dashboard | Construction Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6600;
            --secondary-color:rgb(63, 65, 69);
            --accent-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --gray: #6c757d;
            --gray-light: #f8f9fa;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
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
            background: var(--secondary-color);
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
            transition: var(--transition);
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
            transition: var(--transition);
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
            background: linear-gradient(135deg, var(--primary-color), #ff8533);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            color: white;
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
            color: white;
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

        .stat-icon.blue { background-color: var(--accent-color); }
        .stat-icon.green { background-color: var(--success-color); }
        .stat-icon.orange { background-color: var(--warning-color); }
        .stat-icon.red { background-color: var(--danger-color); }

        /* Contractor Info */
        .contractor-info {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .contractor-info h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .contractor-info li {
            background-color: var(--gray-light);
            transition: transform 0.3s;
        }

        .contractor-info li:hover {
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

        .action-icon.blue { background-color: var(--accent-color); }
        .action-icon.green { background-color: var(--success-color); }
        .action-icon.orange { background-color: var(--warning-color); }
        .action-icon.red { background-color: var(--danger-color); }

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
            background-color: var(--accent-color);
        }
        
        .footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
            margin-top: 40px;
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
        <a href="Contractordashboard.php" class="sidebar-logo">
            
            <span class="logo-text"><img src="../images/LOGO.png" alt="SLU Logo"> </span>
        </a>
        <button class="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="Contractordashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="ContractorPrepareReport.php">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
        </li>
        <li>
            <a href="ViewTasksAndSchedule.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedule</span>
            </a>
        </li>
        <li>
            <a href="ContractorManageEmployee.php">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </a>
        </li>
        <li>
            <a href="ContractorAssignTasks.php">
                <i class="fas fa-tasks"></i>
                <span>Assign Tasks</span>
            </a>
        </li>
        <li>
            <a href="ContractorProfile.php">
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
                <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Contractor'; ?></div>
                <div class="user-role">Contractor</div>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span class="logout-text">Logout</span>
        </a>
    </div>
</div>

<    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="fw-bold">Welcome, <?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : 'Contractor'; ?>!</h1>
            </div>
        </div>
        
    

<!-- Quick Actions -->
<div class="col-12 mb-4">
    <h3 class="position-relative pb-3 mb-4">Quick Actions</h3>
    <div class="row g-4">
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="ViewTasksAndSchedule.php" class="action-button d-block p-4 text-center h-100">
                <div class="action-icon blue d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 60px; height: 60px; border-radius: 50%;">
                    <i class="fas fa-calendar-alt fa-lg"></i>
                </div>
                <h5>View Schedule</h5>
                <p class="text-muted mb-0">Check your project timeline</p>
            </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="ContractorPrepareReport.php" class="action-button d-block p-4 text-center h-100">
                <div class="action-icon green d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 60px; height: 60px; border-radius: 50%;">
                    <i class="fas fa-file-alt fa-lg"></i>
                </div>
                <h5>Submit Report</h5>
                <p class="text-muted mb-0">Create progress reports</p>
            </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="ContractorManageEmployee.php" class="action-button d-block p-4 text-center h-100">
                <div class="action-icon orange d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 60px; height: 60px; border-radius: 50%;">
                    <i class="fas fa-users fa-lg"></i>
                </div>
                <h5>Manage Employees</h5>
                <p class="text-muted mb-0">Oversee your team</p>
            </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="ContractorProfile.php" class="action-button d-block p-4 text-center h-100">
                <div class="action-icon red d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 60px; height: 60px; border-radius: 50%;">
                    <i class="fas fa-user-cog fa-lg"></i>
                </div>
                <h5>Update Profile</h5>
                <p class="text-muted mb-0">Manage your account</p>
            </a>
        </div>
    </div>
</div>

        
               <!-- Recent Projects -->
        <div class="mb-4">
            <h3 class="position-relative pb-3 mb-4">Recent Projects Assigned by The Manager</h3>
            <?php
            // Fetch assigned tasks from project_assignments table
            $assigned_tasks_query = "SELECT pa.*, p.project_name, u.FirstName, u.LastName 
                                    FROM project_assignments pa
                                    JOIN projects p ON pa.project_id = p.project_id
                                    JOIN users u ON pa.user_id = u.UserID
                                    WHERE pa.contractor_id = ? AND pa.role_in_project = 'Assigned Contractor'
                                    ORDER BY pa.start_date ASC";
            $stmt = $connection->prepare($assigned_tasks_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $assigned_tasks_result = $stmt->get_result();
            ?>
            
            <?php if ($assigned_tasks_result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($task = $assigned_tasks_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="project-card h-100">
                                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($task['project_name']); ?></h5>
                                        <span class="project-status <?php echo $task['status'] == 'Completed' ? 'status-completed' : ($task['status'] == 'In Progress' ? 'status-active' : 'status-pending'); ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 100) . (strlen($task['description']) > 100 ? '...' : '')); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i> 
                                            <?php 
                                            echo isset($task['start_date']) ? date('M d', strtotime($task['start_date'])) : 'N/A'; 
                                            echo ' - '; 
                                            echo isset($task['end_date']) ? date('M d, Y', strtotime($task['end_date'])) : 'N/A'; 
                                            ?>
                                        </small>
                                        <a href="ViewTasksAndSchedule.php?id=<?php echo $task['assignment_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </div>
                                    <div class="mt-3 pt-3 border-top">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i> Assigned by: <?php echo htmlspecialchars($task['FirstName'] . ' ' . $task['LastName']); ?>
                                        </small>
                                        <?php if (!empty($task['attachment_path'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <a href="<?php echo $task['attachment_path']; ?>" target="_blank">
                                                    <i class="fas fa-paperclip me-1"></i> View Attachment
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No tasks assigned to you yet.
                </div>
            <?php endif; ?>
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

