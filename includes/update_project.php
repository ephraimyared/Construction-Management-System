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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $project_id = $_POST['project_id'];
    $project_name = $_POST['project_name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    // Initialize resources with empty string if not set
    $resources = isset($_POST['resources']) ? $_POST['resources'] : '';
    
    // Validate dates
    $start_date_obj = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    if ($end_date_obj < $start_date_obj) {
        $error_message = "End date cannot be earlier than start date.";
    } else {
        // Handle file upload if a new file is provided
        $file_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $upload_dir = '../uploads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['attachment']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (in_array($_FILES['attachment']['type'], $allowed_types)) {
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                    $file_path = $target_file;
                } else {
                    $error_message = "Failed to upload file.";
                }
            } else {
                $error_message = "Invalid file type. Only JPG, PNG, GIF, PDF, and DOC files are allowed.";
            }
        }
        
        if (empty($error_message)) {
            // Update project in database
            $update_query = "UPDATE projects SET 
                project_name = ?, 
                description = ?, 
                start_date = ?, 
                end_date = ?, 
                status = ?, 
                resources = ?";
            
            $params = [$project_name, $description, $start_date, $end_date, $status, $resources];
            $types = "ssssss"; // Changed from "ssssdss" to "ssssss" - all strings
            
            // Add file path to query if a new file was uploaded
            if ($file_path) {
                $update_query .= ", file_path = ?";
                $params[] = $file_path;
                $types .= "s";
            }
            
            $update_query .= " WHERE project_id = ? AND manager_id = ?";
            $params[] = $project_id;
            $params[] = $user_id;
            $types .= "ii";
            
            $stmt = $connection->prepare($update_query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $success_message = "Project updated successfully!";
                
                // Refresh the projects list
                $stmt = $connection->prepare($projects_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $projects_result = $stmt->get_result();
            } else {
                $error_message = "Error updating project: " . $connection->error;
            }
        }
    }
}

// Get project details for editing
$edit_project = null;
if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $edit_query = "SELECT * FROM projects WHERE project_id = ? AND manager_id = ?";
    $stmt = $connection->prepare($edit_query);
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    
    if ($edit_result->num_rows > 0) {
        $edit_project = $edit_result->fetch_assoc();
    } else {
        $error_message = "Project not found or you don't have permission to edit it.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Project</title>
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

        .project-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .project-item {
            padding: 15px;
            border-bottom: 1px solid #e1e5eb;
            transition: var(--transition);
            cursor: pointer;
        }

        .project-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .project-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            border-left: 3px solid var(--primary);
        }

        .project-item h5 {
            margin-bottom: 5px;
            color: var(--dark);
            font-weight: 600;
        }

        .project-item p {
            margin-bottom: 5px;
            color: var(--gray);
            font-size: 0.85rem;
        }

        .project-item .badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 50px;
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .project-dates {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .project-status {
            font-size: 0.8rem;
        }

        .file-preview {
            margin-top: 15px;
            padding: 10px;
            border: 1px dashed #ccc;
            border-radius: var(--border-radius);
            background-color: #f9f9f9;
        }

        .file-preview a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
        }

        .file-preview a:hover {
            text-decoration: underline;
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
        <div class="page-header">
            <h2><i class="fas fa-edit"></i> Update Project</h2>
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

        <div class="row">
            <!-- Project List -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i> Your Projects
                    </div>
                    <div class="card-body p-0">
                        <div class="project-list">
                            <?php if ($projects_result->num_rows > 0): ?>
                                <?php while ($project = $projects_result->fetch_assoc()): ?>
                                    <div class="project-item <?php echo (isset($_GET['id']) && $_GET['id'] == $project['project_id']) ? 'active' : ''; ?>" 
                                         onclick="window.location.href='update_project.php?id=<?php echo $project['project_id']; ?>'">
                                        <h5><?php echo htmlspecialchars($project['project_name']); ?></h5>
                                        <p><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?></p>
                                        <div class="project-meta">
                                            <div class="project-dates">
                                                <i class="far fa-calendar-alt me-1"></i> 
                                                <?php echo date('M d, Y', strtotime($project['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                            </div>
                                            <div class="project-status">
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
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-folder-open mb-3" style="font-size: 2rem;"></i>
                                    <p>No projects found. Start by creating a new project.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Project Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-edit me-2"></i> 
                        <?php echo $edit_project ? 'Edit Project: ' . htmlspecialchars($edit_project['project_name']) : 'Select a Project to Edit'; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($edit_project): ?>
                            <form action="update_project.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="project_id" value="<?php echo $edit_project['project_id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="project_name" class="form-label">Project Name</label>
                                        <input type="text" class="form-control" id="project_name" name="project_name" 
                                               value="<?php echo htmlspecialchars($edit_project['project_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Planning" <?php echo $edit_project['status'] == 'Planning' ? 'selected' : ''; ?>>Planning</option>
                                            <option value="In Progress" <?php echo $edit_project['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="On Hold" <?php echo $edit_project['status'] == 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                            <option value="Completed" <?php echo $edit_project['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $edit_project['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($edit_project['description']); ?></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo $edit_project['start_date']; ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo $edit_project['end_date']; ?>" required>
                                    </div>
                                    
                                </div>
                                
                               
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="update_project" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Update Project
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-hand-point-left mb-3" style="font-size: 3rem; color: var(--gray);"></i>
                                    <h4 class="text-muted">Please select a project from the list</h4>
                                <p class="text-muted">Click on any project in the left panel to edit its details</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($edit_project && isset($edit_project['decision_status'])): ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-info-circle me-2"></i> Project Approval Status
                    </div>
                    <div class="card-body">
                        <?php 
                            $status_class = '';
                            $status_icon = '';
                            
                            if ($edit_project['decision_status'] == 'Approved') {
                                $status_class = 'text-success';
                                $status_icon = 'fa-check-circle';
                            } elseif ($edit_project['decision_status'] == 'Rejected') {
                                $status_class = 'text-danger';
                                $status_icon = 'fa-times-circle';
                            } else {
                                $status_class = 'text-warning';
                                $status_icon = 'fa-clock';
                            }
                        ?>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas <?php echo $status_icon; ?> <?php echo $status_class; ?> me-2" style="font-size: 1.5rem;"></i>
                            <h5 class="mb-0 <?php echo $status_class; ?>">
                                Status: <?php echo $edit_project['decision_status'] ? htmlspecialchars($edit_project['decision_status']) : 'Pending'; ?>
                            </h5>
                        </div>
                        
                        <?php if (!empty($edit_project['admin_comment'])): ?>
                            <div class="mt-3">
                                <h6>Admin Comments:</h6>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br(htmlspecialchars($edit_project['admin_comment'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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


