<?php
session_start();
include '../db_connection.php';

// Check session
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get projects managed by this user
$projects_query = "SELECT * FROM projects WHERE manager_id = ? ORDER BY project_name ASC";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();
$projects = [];
while ($row = $projects_result->fetch_assoc()) {
    $projects[] = $row;
}

// Handle form submission for adding new schedule item
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        $project_id = $_POST['project_id'];
        $activity_name = $_POST['activity_name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $dependencies = $_POST['dependencies'] ?? '';
        
        // Calculate duration in days
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $duration = $start_date_obj->diff($end_date_obj)->days;
        
        // Validate dates
        if ($end_date_obj < $start_date_obj) {
            $error_message = "End date cannot be earlier than start date.";
        } else {
            // Insert schedule item
            $insert_query = "INSERT INTO project_schedules (project_id, activity_name, start_date, end_date, duration, dependencies) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($insert_query);
            $stmt->bind_param("isssis", $project_id, $activity_name, $start_date, $end_date, $duration, $dependencies);
            
            if ($stmt->execute()) {
                $success_message = "Schedule item added successfully!";
            } else {
                $error_message = "Error adding schedule item: " . $connection->error;
            }
        }
    } elseif (isset($_POST['update_schedule'])) {
        $schedule_id = $_POST['schedule_id'];
        $activity_name = $_POST['activity_name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $dependencies = $_POST['dependencies'] ?? '';
        
        // Calculate duration in days
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $duration = $start_date_obj->diff($end_date_obj)->days;
        
        // Validate dates
        if ($end_date_obj < $start_date_obj) {
            $error_message = "End date cannot be earlier than start date.";
        } else {
            // Update schedule item
            $update_query = "UPDATE project_schedules 
                            SET activity_name = ?, start_date = ?, end_date = ?, duration = ?, dependencies = ? 
                            WHERE schedule_id = ? AND project_id IN (SELECT project_id FROM projects WHERE manager_id = ?)";
            $stmt = $connection->prepare($update_query);
            $stmt->bind_param("sssisii", $activity_name, $start_date, $end_date, $duration, $dependencies, $schedule_id, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Schedule item updated successfully!";
            } else {
                $error_message = "Error updating schedule item: " . $connection->error;
            }
        }
    } elseif (isset($_POST['delete_schedule'])) {
        $schedule_id = $_POST['schedule_id'];
        
        // Delete schedule item
        $delete_query = "DELETE FROM project_schedules 
                        WHERE schedule_id = ? AND project_id IN (SELECT project_id FROM projects WHERE manager_id = ?)";
        $stmt = $connection->prepare($delete_query);
        $stmt->bind_param("ii", $schedule_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Schedule item deleted successfully!";
        } else {
            $error_message = "Error deleting schedule item: " . $connection->error;
        }
    }
}

// Get selected project details and schedule
$selected_project = null;
$schedule_items = [];

if (isset($_GET['project_id'])) {
    $project_id = $_GET['project_id'];
    
    // Get project details
    $project_query = "SELECT * FROM projects WHERE project_id = ? AND manager_id = ?";
    $stmt = $connection->prepare($project_query);
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $project_result = $stmt->get_result();
    
    if ($project_result->num_rows > 0) {
        $selected_project = $project_result->fetch_assoc();
        
        // Get schedule items for this project
        $schedule_query = "SELECT * FROM project_schedules WHERE project_id = ? ORDER BY start_date ASC";
        $stmt = $connection->prepare($schedule_query);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $schedule_result = $stmt->get_result();
        
        while ($row = $schedule_result->fetch_assoc()) {
            $schedule_items[] = $row;
        }
    } else {
        $error_message = "Project not found or you don't have permission to view it.";
    }
}

// Get schedule item for editing
$edit_schedule = null;
if (isset($_GET['edit_schedule'])) {
    $schedule_id = $_GET['edit_schedule'];
    
    $edit_query = "SELECT * FROM project_schedules 
                  WHERE schedule_id = ? AND project_id IN (SELECT project_id FROM projects WHERE manager_id = ?)";
    $stmt = $connection->prepare($edit_query);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    
    if ($edit_result->num_rows > 0) {
        $edit_schedule = $edit_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Timeline</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Gantt Chart CSS -->
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

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--light);
            color: var(--dark);
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .back-button:hover {
            background-color: var(--gray);
            color: white;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: none;
            overflow: hidden;
        }

        .card-header {
            background: var(--gradient);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            border: none;
        }

        .card-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
        }

        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            border: 1px solid #e1e5eb;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 102, 0, 0.25);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        /* Project Selection Styles */
        .project-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .project-card {
            flex: 1 1 250px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .project-card.active {
            border-color: var(--primary);
        }

        .project-card-header {
            background: var(--gradient);
            color: white;
            padding: 15px;
            font-weight: 600;
        }

        .project-card-body {
            padding: 15px;
        }

        .project-card-footer {
            padding: 10px 15px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        /* Gantt Chart Styles */
        .gantt-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        .gantt-chart {
            min-width: 100%;
            border-collapse: collapse;
        }

        .gantt-chart th, .gantt-chart td {
            padding: 10px;
            text-align: left;
            border: 1px solid #e1e5eb;
        }

        .gantt-chart th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .gantt-chart tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .gantt-chart tr:hover {
            background-color: #f1f4f9;
        }

        .gantt-bar-container {
            position: relative;
            height: 30px;
            background-color: #f1f4f9;
            border-radius: 4px;
        }

        .gantt-bar {
            position: absolute;
            height: 20px;
            top: 5px;
            background: var(--gradient);
            border-radius: 4px;
            color: white;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            padding: 0 5px;
        }

        .gantt-actions {
            display: flex;
            gap: 5px;
        }

        .gantt-actions button {
            border: none;
            background: none;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .gantt-actions .edit-btn {
            color: var(--info);
        }

        .gantt-actions .edit-btn:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        .gantt-actions .delete-btn {
            color: var(--danger);
        }

        .gantt-actions .delete-btn:hover {
            background-color: rgba(231, 76, 60, 0.1);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .timeline-header h3 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
        }

        .timeline-header .btn {
            padding: 8px 15px;
        }

        .no-schedule {
            text-align: center;
            padding: 50px 0;
            color: var(--gray);
        }

        .no-schedule i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-schedule h4 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .no-schedule p {
            max-width: 500px;
            margin: 0 auto;
        }

        .project-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .project-info-item {
            flex: 1 1 200px;
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .project-info-item h5 {
            margin: 0 0 10px 0;
            color: var(--gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .project-info-item p {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .project-info-item.primary {
            border-left: 4px solid var(--primary);
        }

        .project-info-item.info {
            border-left: 4px solid var(--info);
        }

        .project-info-item.success {
            border-left: 4px solid var(--success);
        }

        .project-info-item.warning {
            border-left: 4px solid var(--warning);
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .project-selector {
                flex-direction: column;
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
                <a href="ManageProjects.php">
                    <i class="fas fa-project-diagram"></i>
                    <span>Manage Projects</span>
                </a>
            </li>
            <li>
                <a href="AssignTasks.php">
                    <i class="fas fa-tasks"></i>
                    <span>Assign Tasks</span>
                </a>
            </li>
            <li>
                <a href="ViewReports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </a>
            </li>
            <li>
                <a href="ManageEmployees.php">
                    <i class="fas fa-users"></i>
                    <span>Manage Employees</span>
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
        <div class="page-header">
            <h2><i class="fas fa-calendar-alt"></i> Project Timeline</h2>
            <a href="ManageProjects.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Manage Projects
            </a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($selected_project)): ?>
            <!-- Project Selection -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-folder me-2"></i> Select a Project
                </div>
                <div class="card-body">
                    <?php if (count($projects) > 0): ?>
                        <div class="project-selector">
                            <?php foreach ($projects as $project): ?>
                                <div class="project-card" onclick="window.location.href='project_timeline.php?project_id=<?php echo $project['project_id']; ?>'">
                                    <div class="project-card-header">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </div>
                                    <div class="project-card-body">
                                        <p><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                    <div class="project-card-footer">
                                        <span>
                                            <i class="far fa-calendar-alt me-1"></i> 
                                            <?php echo date('M d, Y', strtotime($project['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                        </span>
                                        <?php 
                                            $status_class = '';
                                            switch($project['status']) {
                                                case 'Planning':
                                                    $status_class = 'bg-info';
                                                    break;
                                                case 'In Progress':
                                                    $status_class = 'bg-primary';
                                                    break;
                                                case 'On Hold':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'Completed':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'Cancelled':
                                                    $status_class = 'bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'bg-secondary';
                                            }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo $project['status'] ? htmlspecialchars($project['status']) : 'Planning'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-schedule">
                            <i class="fas fa-folder-open"></i>
                            <h4>No Projects Found</h4>
                            <p>You don't have any projects yet. Create a new project to get started.</p>
                            <a href="create_project.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus-circle me-2"></i> Create New Project
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Project Timeline View -->
            <div class="project-info">
                <div class="project-info-item primary">
                    <h5>Project Name</h5>
                    <p><?php echo htmlspecialchars($selected_project['project_name']); ?></p>
                </div>
                <div class="project-info-item info">
                    <h5>Duration</h5>
                    <p>
                        <?php 
                            $start_date = new DateTime($selected_project['start_date']);
                            $end_date = new DateTime($selected_project['end_date']);
                            $duration = $start_date->diff($end_date)->days;
                            echo $duration . ' days';
                        ?>
                    </p>
                </div>
                <div class="project-info-item success">
                    <h5>Start Date</h5>
                    <p><?php echo date('M d, Y', strtotime($selected_project['start_date'])); ?></p>
                </div>
                <div class="project-info-item warning">
                    <h5>End Date</h5>
                    <p><?php echo date('M d, Y', strtotime($selected_project['end_date'])); ?></p>
                </div>
            </div>

            <!-- Timeline Management -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt me-2"></i> Project Timeline
                </div>
                <div class="card-body">
                    

                    <?php if (count($schedule_items) > 0): ?>
                        <div class="gantt-container">
                            <table class="gantt-chart">
                                <thead>
                                    <tr>
                                        <th width="25%">Activity</th>
                                        <th width="15%">Start Date</th>
                                        <th width="15%">End Date</th>
                                        <th width="10%">Duration</th>
                                        <th width="20%">Dependencies</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedule_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['activity_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($item['start_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($item['end_date'])); ?></td>
                                            <td><?php echo $item['duration']; ?> days</td>
                                            <td><?php echo htmlspecialchars($item['dependencies'] ?? 'None'); ?></td>
                                            <td>
                                                <div class="gantt-actions">
                                                    <a href="project_timeline.php?project_id=<?php echo $selected_project['project_id']; ?>&edit_schedule=<?php echo $item['schedule_id']; ?>" class="edit-btn" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="delete-btn" title="Delete" 
                                                            onclick="confirmDelete(<?php echo $item['schedule_id']; ?>)">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Visual Gantt Chart -->
                        <h4 class="mt-5 mb-3">Visual Timeline</h4>
                        <div class="gantt-container">
                            <?php
                                // Calculate project timeline range
                                $project_start = new DateTime($selected_project['start_date']);
                                $project_end = new DateTime($selected_project['end_date']);
                                $project_duration = $project_start->diff($project_end)->days;
                                
                                // Add some buffer
                                $project_start->modify('-3 days');
                                $project_end->modify('+3 days');
                                $total_days = $project_start->diff($project_end)->days;
                            ?>
                            
                            <div style="position: relative; width: 100%; height: <?php echo (count($schedule_items) * 50) + 50; ?>px; background: #f8f9fa; border-radius: 8px; padding: 20px; overflow: hidden;">
                                <!-- Timeline header -->
                                <div style="display: flex; position: absolute; top: 0; left: 0; right: 0; height: 30px; border-bottom: 1px solid #e1e5eb;">
                                    <?php
                                        $current_date = clone $project_start;
                                        for ($i = 0; $i <= $total_days; $i++) {
                                            $left_pos = ($i / $total_days) * 100;
                                            echo '<div style="position: absolute; left: ' . $left_pos . '%; transform: translateX(-50%); font-size: 0.7rem; color: #6c757d;">';
                                            echo $current_date->format('M d');
                                            echo '</div>';
                                            $current_date->modify('+1 day');
                                        }
                                    ?>
                                </div>
                                
                                <!-- Timeline bars -->
                                <?php foreach ($schedule_items as $index => $item): ?>
                                    <?php
                                        $item_start = new DateTime($item['start_date']);
                                        $item_end = new DateTime($item['end_date']);
                                        
                                        // Calculate position
                                        $days_from_start = $project_start->diff($item_start)->days;
                                        $item_duration = $item_start->diff($item_end)->days;
                                        
                                        $left_pos = ($days_from_start / $total_days) * 100;
                                        $width = ($item_duration / $total_days) * 100;
                                        
                                        // Generate a color based on index
                                        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#d35400', '#34495e'];
                                        $color = $colors[$index % count($colors)];
                                    ?>
                                    <div style="position: absolute; top: <?php echo ($index * 50) + 50; ?>px; left: <?php echo $left_pos; ?>%; width: <?php echo $width; ?>%; height: 30px; background: <?php echo $color; ?>; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding: 0 10px;">
                                        <?php echo htmlspecialchars($item['activity_name']); ?>
                                    </div>
                                    <div style="position: absolute; top: <?php echo ($index * 50) + 50; ?>px; left: 0; font-size: 0.8rem; color: #333; transform: translateY(-100%);">
                                        <?php echo htmlspecialchars($item['activity_name']); ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Today marker -->
                                <?php
                                    $today = new DateTime();
                                    if ($today >= $project_start && $today <= $project_end) {
                                        $days_from_start = $project_start->diff($today)->days;
                                        $today_pos = ($days_from_start / $total_days) * 100;
                                        echo '<div style="position: absolute; top: 30px; bottom: 0; left: ' . $today_pos . '%; width: 2px; background: #e74c3c; z-index: 10;"></div>';
                                        echo '<div style="position: absolute; top: 10px; left: ' . $today_pos . '%; transform: translateX(-50%); background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem;">Today</div>';
                                    }
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Schedule Modal -->
            <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addScheduleModalLabel">Add New Activity</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="project_timeline.php?project_id=<?php echo $selected_project['project_id']; ?>" method="post">
                            <div class="modal-body">
                                <input type="hidden" name="project_id" value="<?php echo $selected_project['project_id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="activity_name" class="form-label">Activity Name</label>
                                    <input type="text" class="form-control" id="activity_name" name="activity_name" required>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               min="<?php echo $selected_project['start_date']; ?>" 
                                               max="<?php echo $selected_project['end_date']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               min="<?php echo $selected_project['start_date']; ?>" 
                                               max="<?php echo $selected_project['end_date']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="dependencies" class="form-label">Dependencies (Optional)</label>
                                    <input type="text" class="form-control" id="dependencies" name="dependencies" 
                                           placeholder="e.g., Foundation work, Electrical wiring">
                                    <div class="form-text">Enter activities that must be completed before this one.</div>
                                </div>
                            </div>
                            
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Schedule Modal -->
            <?php if ($edit_schedule): ?>
                <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editScheduleModalLabel">Edit Activity</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="project_timeline.php?project_id=<?php echo $selected_project['project_id']; ?>" method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="schedule_id" value="<?php echo $edit_schedule['schedule_id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="edit_activity_name" class="form-label">Activity Name</label>
                                        <input type="text" class="form-control" id="edit_activity_name" name="activity_name" 
                                               value="<?php echo htmlspecialchars($edit_schedule['activity_name']); ?>" required>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="edit_start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="edit_start_date" name="start_date" 
                                                   value="<?php echo $edit_schedule['start_date']; ?>"
                                                   min="<?php echo $selected_project['start_date']; ?>" 
                                                   max="<?php echo $selected_project['end_date']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="edit_end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="edit_end_date" name="end_date" 
                                                   value="<?php echo $edit_schedule['end_date']; ?>"
                                                   min="<?php echo $selected_project['start_date']; ?>" 
                                                   max="<?php echo $selected_project['end_date']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_dependencies" class="form-label">Dependencies (Optional)</label>
                                        <input type="text" class="form-control" id="edit_dependencies" name="dependencies" 
                                               value="<?php echo htmlspecialchars($edit_schedule['dependencies'] ?? ''); ?>"
                                               placeholder="e.g., Foundation work, Electrical wiring">
                                        <div class="form-text">Enter activities that must be completed before this one.</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_schedule" class="btn btn-primary">Update Activity</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Auto-trigger the edit modal -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                        editModal.show();
                    });
                </script>
            <?php endif; ?>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this activity? This action cannot be undone.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form action="project_timeline.php?project_id=<?php echo $selected_project['project_id']; ?>" method="post">
                                <input type="hidden" name="schedule_id" id="deleteScheduleId" value="">
                                <button type="submit" name="delete_schedule" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        
        // Function to confirm deletion
        function confirmDelete(scheduleId) {
            document.getElementById('deleteScheduleId').value = scheduleId;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>
<?php $connection->close(); ?>


