<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Employee') {
    header("Location: ../unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch employee projects
$project_query = "SELECT * FROM projects WHERE employee_id = ?";
$stmt_projects = $connection->prepare($project_query);
$stmt_projects->bind_param("i", $user_id);
$stmt_projects->execute();
$projects_result = $stmt_projects->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard - CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gradient: linear-gradient(to right, #4361ee, #4cc9f0);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .welcome-header {
            background: var(--gradient);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            box-shadow: var(--shadow);
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
            font-size: 1.8rem;
            position: relative;
        }
        
        .welcome-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            position: relative;
            font-size: 1rem;
        }
        
        .card-option {
            background: white;
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        
        .card-option:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .card-option .card-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .card-option .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(76, 201, 240, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .card-option i {
            font-size: 2.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .card-option h5 {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .card-option .btn {
            background: var(--gradient);
            border: none;
            border-radius: 50px;
            padding: 8px 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .card-option .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .project-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .project-header {
            background: var(--gradient);
            color: white;
            padding: 20px 25px;
            position: relative;
        }
        
        .project-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
            position: relative;
        }
        
        .project-body {
            padding: 25px;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table thead th {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
            border: none;
        }
        
        .table tbody td {
            padding: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }
        
        .table tbody tr:hover td {
            background-color: rgba(67, 97, 238, 0.02);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        .status-pending {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning);
        }
        
        .status-completed {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .empty-projects {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-projects i {
            font-size: 4rem;
            color: rgba(67, 97, 238, 0.2);
            margin-bottom: 20px;
        }
        
        .empty-projects h5 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .empty-projects p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
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
            
            .card-option .icon-wrapper {
                width: 60px;
                height: 60px;
            }
            
            .card-option i {
                font-size: 2rem;
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
            <a href="EmployeeDashboard.php" class="sidebar-logo">
              <span class="logo-text"><img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="EmployeeDashboard.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="EmployeeViewTasks.php">
                    <i class="fas fa-tasks"></i>
                    <span>View Tasks</span>
                </a>
            </li>
            <li>
                <a href="EmployeePrepareReport.php">
                    <i class="fas fa-upload"></i>
                    <span>Submit Work</span>
                </a>
            </li>
            <li>
                <a href="EmployeeViewProfile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo substr($user['FirstName'], 0, 1); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></div>
                    <div class="user-role">Employee</div>
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
                <h2><i class="fas fa-user-hard-hat me-2"></i>Welcome, <?php echo htmlspecialchars($user['FirstName']); ?>!</h2>
                <p>Access your tasks, projects, and submit reports from your employee dashboard</p>
            </div>
            
            <!-- Dashboard Options -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card-option">
                        <div class="card-body">
                            <div class="icon-wrapper">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h5>View Tasks</h5>
                            <p class="text-muted mb-4">Check your assigned tasks and deadlines</p>
                            <a href="EmployeeViewTasks.php" class="btn">View Tasks</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card-option">
                        <div class="card-body">
                            <div class="icon-wrapper">
                                <i class="fas fa-upload"></i>
                            </div>
                            <h5>Submit Task</h5>
                            <p class="text-muted mb-4">Upload your completed work and reports</p>
                            <a href="EmployeePrepareReport.php" class="btn">Submit Task</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Project Info -->
            <div class="project-card">
                <div class="project-header">
                    <h5><i class="fas fa-briefcase me-2"></i>Your Projects</h5>
                </div>
                <div class="project-body">
                    <?php if ($projects_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($project = $projects_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars($project['project_name']); ?></td>
                                            <td><?php echo htmlspecialchars($project['project_description']); ?></td>
                                            <td>
                                                <?php 
                                                $status = strtolower($project['status']);
                                                $statusClass = 'status-pending';
                                                
                                                if ($status == 'active' || $status == 'in progress') {
                                                    $statusClass = 'status-active';
                                                } elseif ($status == 'completed') {
                                                    $statusClass = 'status-completed';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($project['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-projects">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No Projects Assigned</h5>
                            <p>You don't have any projects assigned to you at the moment. Check back later or contact your manager for more information.</p>
                        </div>
                    <?php endif; ?>
                </div>
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
