<?php
session_start();
include '../db_connection.php'; // Include the database connection

// Ensure the user is logged in and is a Site Engineer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

// Fetch user details from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    // Redirect to unauthorized page if no user is found
    header("Location: ../unauthorized.php");
    exit();
}

// Fetch projects assigned to the Site Engineer
$project_query = "SELECT * FROM projects WHERE site_engineer_id = ?";
$project_stmt = $connection->prepare($project_query);
$project_stmt->bind_param("i", $user_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();

// Count projects by status
$total_projects = $project_result->num_rows;
$status_counts = [
    'Planning' => 0,
    'In Progress' => 0,
    'On Hold' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

// Reset result pointer to count by status
if ($total_projects > 0) {
    while ($project = $project_result->fetch_assoc()) {
        if (isset($status_counts[$project['status']])) {
            $status_counts[$project['status']]++;
        }
    }
    // Reset pointer for later use
    $project_result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Engineer Dashboard | Salale University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-radius: 10px;
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: #333;
            min-height: 100vh;
            padding: 0;
            margin: 0;
            display: flex;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-color);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .sidebar-item:hover, .sidebar-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--accent-color);
        }
        
        .sidebar-item i {
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logout {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .sidebar-logout:hover {
            background: rgba(231, 76, 60, 0.2);
            color: white;
        }
        
        .sidebar-logout i {
            margin-right: 0.75rem;
        }
        
        /* Main content wrapper */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
        
        /* Original Dashboard Styles */
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
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
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
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
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }
        
        .stat-progress {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .stat-hold {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .stat-completed {
            background-color: rgba(26, 188, 156, 0.1);
            color: var(--accent-color);
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
            color: #6c757d;
            margin: 0.25rem 0 0;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            text-decoration: none;
            color: var(--dark-color);
            display: flex;
            flex-direction: column;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
            color: var(--dark-color);
        }
        
        .action-icon {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        
        .action-tasks {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .action-labor {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .action-report {
            background: linear-gradient(135deg, #1abc9c, #16a085);
            color: white;
        }
        
        .action-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .action-title {
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0 0 0.5rem;
        }
        
        .action-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .projects-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.25rem;
            color: var(--dark-color);
        }
        
        .project-card {
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .project-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            color: var(--secondary-color);
        }
        
        .project-status {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-planning {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }
        
        .status-in-progress {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .status-on-hold {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .status-completed {
            background-color: rgba(26, 188, 156, 0.1);
            color: var(--accent-color);
        }
        
        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .project-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .project-detail {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .empty-projects {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-projects i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-projects h3 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-projects p {
            margin-bottom: 1.5rem;
        }
        
        .logout-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            z-index: 1000;
        }
        
        .logout-btn:hover {
            transform: scale(1.1);
            background-color: #c0392b;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(100%, 1fr));
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .welcome-header {
                padding: 1.5rem;
            }
            
            .welcome-header h2 {
                font-size: 1.5rem;
            }
            
            .project-details {
                grid-template-columns: 1fr;
            }
            
            .logout-btn {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
        img {
    max-width: 80%; 
    height: auto; 
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
    background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
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
    position: static; /* Override any fixed positioning */
    bottom: auto;
    right: auto;
    font-size: 0.9rem; /* Smaller font size */
    box-shadow: none; /* Remove any shadow */
}

..logout-btn:hover {
    background: rgba(231, 76, 60, 0.3);
    transform: none; /* Remove any transform on hover */
}

@media (max-width: 992px) {
    .sidebar-collapsed .logout-text {
        display: none;
    }
}
/* Remove or override the fixed position logout button styles */
.main-content .logout-btn {
    display: none; 
}
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
         <span class="logo-text"> <img src="../images/LOGO.png" alt="SLU Logo"> </span>
    </div>
    <div class="sidebar-menu">
        <a href="SiteEngineerDashboard.php" class="sidebar-item active">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="SiteEngineerViewTasks.php" class="sidebar-item">
            <i class="fas fa-tasks"></i>
            <span>View Tasks</span>
        </a>
        <a href="SiteEngineerManageDailyLabor.php" class="sidebar-item">
            <i class="fas fa-hard-hat"></i>
            <span>Track Labor</span>
        </a>
        <a href="SiteEngineerSubmitReport.php" class="sidebar-item">
            <i class="fas fa-file-alt"></i>
            <span>Submit Reports</span>
        </a>
        <a href="EngineerProfile.php" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
    </div>
  
                    
   <div class="sidebar-footer">
    <div class="user-info">
        <div class="user-avatar">
            <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'E'; ?>
        </div>
        <div class="user-details">
            <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Engineer'; ?></div>
            <div class="user-role"><?php echo $_SESSION['user_role']; ?></div>
        </div>
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span class="logout-text">Logout</span>
    </a>
</div>
</div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Mobile menu toggle -->
    <button class="toggle-sidebar d-md-none">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Logout Button -->
    <a href="../logout.php" class="logout-btn d-md-none" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </a>

    <div class="dashboard-container">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h2>Welcome, <?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?>!</h2>
        </div>
    
        <div class="action-buttons">
            <a href="SiteEngineerViewTasks.php" class="action-card">
                <div class="action-icon action-tasks">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="action-content">
                    <h3 class="action-title">View Tasks</h3>
                    <p class="action-description">Check and manage tasks assigned to you across different projects</p>
                </div>
            </a>
            
            <a href="SiteEngineerManageDailyLabor.php" class="action-card">
                <div class="action-icon action-labor">
                    <i class="fas fa-hard-hat"></i>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Track Labor</h3>
                    <p class="action-description">Record and monitor daily labor activities and hours worked</p>
                </div>
            </a>
            
            <a href="SiteEngineerSubmitReport.php" class="action-card">
                <div class="action-icon action-report">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="action-content">
                    <h3 class="action-title">Submit Reports</h3>
                    <p class="action-description">Create and submit progress reports, issues, and financial updates</p>
                </div>
            </a>
        </div>
            
            <?php if ($project_result->num_rows > 0): ?>
                <?php while ($project = $project_result->fetch_assoc()): ?>
                    <div class="project-card">
                        <div class="project-header">
                            <h3 class="project-title"><?= htmlspecialchars($project['project_name']) ?></h3>
                            <span class="project-status status-<?= strtolower(str_replace(' ', '-', $project['status'])) ?>">
                                <?= htmlspecialchars($project['status']) ?>
                            </span>
                        </div>
                        
                        <p class="project-description">
                            <?= htmlspecialchars($project['description'] ?? 'No description available.') ?>
                        </p>
                        
                        <div class="project-details">
                            <div class="project-detail">
                                <span class="detail-label">Start Date</span>
                                <span class="detail-value"><?= htmlspecialchars(date('M d, Y', strtotime($project['start_date']))) ?></span>
                            </div>
                            
                            <div class="project-detail">
                                <span class="detail-label">End Date</span>
                                <span class="detail-value"><?= htmlspecialchars(date('M d, Y', strtotime($project['end_date']))) ?></span>
                            </div>
                            
                            <div class="project-detail">
                                <span class="detail-label">Budget</span>
                                <span class="detail-value">
                                    <?= $project['budget'] ? number_format($project['budget'], 2) : 'Not specified' ?>
                                </span>
                            </div>
                            
                            <div class="project-detail">
                                <span class="detail-label">Manager</span>
                                <span class="detail-value">
                                    <?php
                                    // Fetch manager name
                                    $manager_query = "SELECT FirstName, LastName FROM users WHERE UserID = ?";
                                    $manager_stmt = $connection->prepare($manager_query);
                                    $manager_stmt->bind_param("i", $project['manager_id']);
                                    $manager_stmt->execute();
                                    $manager_result = $manager_stmt->get_result();
                                    if ($manager_result->num_rows > 0) {
                                        $manager = $manager_result->fetch_assoc();
                                        echo htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']);
                                    } else {
                                        echo 'Not assigned';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                
            <?php endif; ?>
        </div>       
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add any JavaScript functionality here
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stats on page load
        const statValues = document.querySelectorAll('.stat-value');
        statValues.forEach(stat => {
            const finalValue = parseInt(stat.textContent);
            let currentValue = 0;
            const duration = 1000; // 1 second
            const increment = finalValue / (duration / 16); // 60fps
            
            const animateStat = () => {
                currentValue += increment;
                if (currentValue < finalValue) {
                    stat.textContent = Math.floor(currentValue);
                    requestAnimationFrame(animateStat);
                } else {
                    stat.textContent = finalValue;
                }
            };
            
            animateStat();
        });
        
        // Mobile sidebar toggle
        const toggleBtn = document.querySelector('.toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickInsideToggle = toggleBtn && toggleBtn.contains(event.target);
            
            if (window.innerWidth <= 768 && !isClickInsideSidebar && !isClickInsideToggle && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    });
</script>

</body>
</html>

<?php
// Close database connections
if (isset($stmt)) $stmt->close();
if (isset($project_stmt)) $project_stmt->close();
if (isset($manager_stmt)) $manager_stmt->close();
if (isset($activity_stmt)) $activity_stmt->close();
$connection->close();
?>
