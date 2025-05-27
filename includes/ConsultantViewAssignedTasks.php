<?php
session_start();
include '../db_connection.php';

// Ensure only Consultants access this
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Consultant') {
    header("Location: unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch tasks assigned to this Consultant
$query = "SELECT pa.*, p.project_name, pm.FirstName AS ManagerFirstName, pm.LastName AS ManagerLastName
          FROM project_assignments pa
          JOIN projects p ON pa.project_id = p.project_id
          JOIN users pm ON pa.user_id = pm.UserID
          WHERE pa.contractor_id = ? AND pa.role_in_project = 'Assigned Consultant'";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user info
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
    <title>View Assigned Tasks</title>
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

        .page-header {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .breadcrumb {
            margin: 0;
            padding: 0;
            background: transparent;
        }

        .breadcrumb-item a {
            color: var(--gray-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }

        .breadcrumb-item.active {
            color: var(--primary-color);
        }

        .tasks-container {
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .card {
            border: none;
            border-radius: var(--border-radius-sm);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 20px;
        }

        .task-header {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .task-status {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: var(--accent-color);
            color: white;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--light-color);
            color: var(--dark-color);
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: var(--gray-color);
            color: white;
        }

        .alert-info {
            background-color: rgba(76, 201, 240, 0.1);
            border-color: var(--accent-color);
            color: var(--primary-color);
            border-radius: var(--border-radius-sm);
        }

        .btn-update-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            margin-top: 15px;
        }

        .btn-update-status:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .tasks-container {
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
            <a href="ConsultantDashboard.php" class="sidebar-logo">
              <span class="logo-text"><img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="ConsultantDashboard.php">
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
                <a href="ConsultantViewAssignedTasks.php" class="active">
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
        <div class="page-header">
            <div>
                <h1 class="page-title">Project Tasks Status</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="ConsultantDashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Project Tasks Status </li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="tasks-container">
            <h2 class="mb-4"><i class="fas fa-tasks me-2"></i>Your Assigned Tasks</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($task = $result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="task-header">
                                        <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                                        <span class="task-status badge <?php echo getStatusBadgeClass($task['status']); ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-calendar-alt me-2"></i>Start Date:</strong> 
                                        <?php echo date('M d, Y', strtotime($task['start_date'])); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-calendar-check me-2"></i>End Date:</strong> 
                                        <?php echo date('M d, Y', strtotime($task['end_date'])); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-user me-2"></i>Assigned By:</strong> 
                                        <?php echo htmlspecialchars($task['ManagerFirstName'] . ' ' . $task['ManagerLastName']); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong><i class="fas fa-info-circle me-2"></i>Description:</strong>
                                        <p class="mt-2"><?php echo htmlspecialchars($task['description']); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($task['attachment_path'])): ?>
                                        <div class="mb-3">
                                            <strong><i class="fas fa-paperclip me-2"></i>Attachment:</strong>
                                            <a href="<?php echo htmlspecialchars($task['attachment_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="fas fa-download me-1"></i> Download Attachment
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="ConsultantPrepareReport.php?project_id=<?php echo $task['project_id']; ?>&task_id=<?php echo $task['assignment_id']; ?>" class="btn-update-status">
                                            <i class="fas fa-sync-alt"></i> Update Status
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i> You don't have any tasks assigned to you yet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>

<?php
// Helper function to get the appropriate badge class based on status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Assigned':
            return 'bg-info';
        case 'In Progress':
            return 'bg-primary';
        case 'On Hold':
            return 'bg-warning';
        case 'Completed':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

$connection->close();
?>