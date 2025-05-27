<?php
session_start();
include '../db_connection.php';

// Ensure only Project Managers access this
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Project Manager') {
    header("Location: unauthorized.php");
    exit();
}

$manager_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Fetch projects managed by the logged-in project manager that have been approved
$projects_query = "SELECT * FROM projects WHERE manager_id = ? AND decision_status = 'Approved'";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Reset the site engineers result pointer
$stmt_engineers = $connection->prepare("SELECT * FROM users WHERE Role = 'Site Engineer' AND managed_by_contractor_id = ?");
$stmt_engineers->bind_param("i", $manager_id);
$stmt_engineers->execute();
$site_engineers_result = $stmt_engineers->get_result();

// Fetch the number of projects assigned to each site engineer
$assignment_counts = [];
$result = $connection->query("SELECT contractor_id, COUNT(*) as assignment_count FROM project_assignments WHERE role_in_project = 'Assigned Site Engineer' GROUP BY contractor_id");
while ($row = $result->fetch_assoc()) {
    $assignment_counts[$row['contractor_id']] = $row['assignment_count'];
}

// Fetch the actual projects assigned to each site engineer
$engineer_projects = [];
$projects_query = $connection->query("
    SELECT pa.contractor_id, p.project_name, pa.start_date, pa.end_date, pa.status, pa.project_id, pa.assignment_id
    FROM project_assignments pa
    JOIN projects p ON pa.project_id = p.project_id
    WHERE pa.role_in_project = 'Assigned Site Engineer'
    ORDER BY pa.contractor_id, p.project_name
");

while ($row = $projects_query->fetch_assoc()) {
    if (!isset($engineer_projects[$row['contractor_id']])) {
        $engineer_projects[$row['contractor_id']] = [];
    }
    $engineer_projects[$row['contractor_id']][] = [
        'project_name' => $row['project_name'],
        'project_id' => $row['project_id'],
        'assignment_id' => $row['assignment_id'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'status' => $row['status']
    ];
}

// Create a lookup array for projects that already have site engineers assigned
$project_assignments = [];
$assigned_projects_query = $connection->query("
    SELECT project_id, contractor_id, assignment_id 
    FROM project_assignments 
    WHERE role_in_project = 'Assigned Site Engineer'
");
while ($row = $assigned_projects_query->fetch_assoc()) {
    if (!isset($project_assignments[$row['project_id']])) {
        $project_assignments[$row['project_id']] = [];
    }
    $project_assignments[$row['project_id']][] = [
        'contractor_id' => $row['contractor_id'],
        'assignment_id' => $row['assignment_id']
    ];
}

// Handle assignment removal
if (isset($_POST['remove_assignment']) && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];
    
    // Delete the assignment
    $delete_query = "DELETE FROM project_assignments WHERE assignment_id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("i", $assignment_id);
    
    if ($stmt->execute()) {
        $message = "✅ Assignment successfully removed!";
        $message_type = "success";
        
        // Refresh the page to update the data
        header("Location: ManagerAssignToSiteEngineer.php?success=removed");
        exit();
    } else {
        $message = "❌ Error removing assignment: " . $stmt->error;
        $message_type = "danger";
    }
}

// Handle reassignment
if (isset($_POST['reassign']) && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];
    $new_engineer_id = $_POST['new_engineer_id'];
    
    // Update the assignment with the new site engineer
    $update_query = "UPDATE project_assignments SET contractor_id = ? WHERE assignment_id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("ii", $new_engineer_id, $assignment_id);
    
    if ($stmt->execute()) {
        $message = "✅ Project successfully reassigned to new site engineer!";
        $message_type = "success";
        
        // Refresh the page to update the data
        header("Location: ManagerAssignToSiteEngineer.php?success=reassigned");
        exit();
    } else {
        $message = "❌ Error reassigning project: " . $stmt->error;
        $message_type = "danger";
    }
}

// Handle form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['reassign']) && !isset($_POST['remove_assignment'])) {
    $project_id = $_POST['project_id'];
    $site_engineer_id = $_POST['site_engineer_id'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = 'Assigned';
    $role_in_project = 'Assigned Site Engineer';
    $user_id = $_SESSION['user_id']; // Manager ID
    
    // Check if this project is already assigned to this site engineer
    $already_assigned = false;
    if (isset($project_assignments[$project_id])) {
        foreach ($project_assignments[$project_id] as $assignment) {
            if ($assignment['contractor_id'] == $site_engineer_id) {
                $already_assigned = true;
                break;
            }
        }
    }
    
    if ($already_assigned) {
        $message = "⚠️ This project is already assigned to this site engineer.";
        $message_type = "danger";
    } else {
        // Check if this project already has a site engineer assigned
        $has_engineer = isset($project_assignments[$project_id]) && !empty($project_assignments[$project_id]);
        
        // File upload handling
        $file_path = "";
        if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $upload_dir = "../uploads/assignments/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['attachment']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Check file size (limit to 10MB)
            if ($_FILES['attachment']['size'] > 10000000) {
                $message = "⚠️ File is too large. Maximum size is 10MB.";
                $message_type = "danger";
            } 
            // Check file type
            else {
                $allowed_types = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png');
                $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_types)) {
                    $message = "⚠️ Only PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, and PNG files are allowed.";
                    $message_type = "danger";
                } else if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                    $file_path = $target_file;
                } else {
                    $message = "⚠️ Error uploading file.";
                    $message_type = "danger";
                }
            }
        }

        if (empty($message) || $message_type == "success") {
            if (strtotime($start_date) > strtotime($end_date)) {
                $message = "⚠️ Start date must be before end date.";
                $message_type = "danger";
            } else {
                // If the project already has a site engineer, show a warning but still allow the assignment
                if ($has_engineer) {
                    $warning_message = "⚠️ Note: This project already has a site engineer assigned. You are adding an additional site engineer to work on this project.";
                }
                
                $insert_query = "INSERT INTO project_assignments (project_id, user_id, contractor_id, description, start_date, end_date, status, role_in_project, attachment_path)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($insert_query);
                $stmt->bind_param("iiissssss", $project_id, $user_id, $site_engineer_id, $description, $start_date, $end_date, $status, $role_in_project, $file_path);
                if ($stmt->execute()) {
                    $message = "✅ Project assigned successfully!";
                    if (isset($warning_message)) {
                        $message .= " " . $warning_message;
                    }
                    $message_type = "success";
                    
                    // Refresh the page to update the data
                    header("Location: ManagerAssignToSiteEngineer.php?success=assigned");
                    exit();
                } else {
                    $message = "❌ Error: " . $stmt->error;
                    $message_type = "danger";
                }
            }
        }
    }
}

// Handle URL parameters for success messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'assigned') {
        $message = "✅ Project assigned successfully!";
        $message_type = "success";
    } else if ($_GET['success'] == 'reassigned') {
        $message = "✅ Project successfully reassigned to new site engineer!";
        $message_type = "success";
    } else if ($_GET['success'] == 'removed') {
        $message = "✅ Assignment successfully removed!";
        $message_type = "success";
    }
}

// Get projects with assigned engineers for display in the form
$projects_with_engineers = [];
$result = $connection->query("
    SELECT p.project_id, p.project_name, 
           GROUP_CONCAT(CONCAT(u.FirstName, ' ', u.LastName) SEPARATOR ', ') as engineers
    FROM projects p
    JOIN project_assignments pa ON p.project_id = pa.project_id
    JOIN users u ON pa.contractor_id = u.UserID
    WHERE pa.role_in_project = 'Assigned Site Engineer'
    AND p.manager_id = $manager_id
    GROUP BY p.project_id
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects_with_engineers[$row['project_id']] = [
            'project_name' => $row['project_name'],
            'engineers' => $row['engineers']
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Project to Site Engineer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            --card-border-radius: 0.75rem;
            --border-radius-sm: 0.375rem;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            border-radius: var(--card-border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }
        
        .page-header h1 {
            font-weight: 600;
            margin: 0;
            font-size: 1.75rem;
            position: relative;
            z-index: 1;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .back-button i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }
        
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
                        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-container {
            border-radius: var(--card-border-radius);
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: var(--border-radius-sm);
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #3db8e0;
            border-color: #3db8e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
            border-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: var(--border-radius-sm);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--warning-color);
        }
        
        .project-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            margin: 0.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .project-badge:hover {
            background-color: rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        .project-icon {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }
        
        .assignment-count {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .assignment-count .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .project-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .view-all-btn {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .view-all-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .no-assignments {
            color: var(--gray-color);
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background-color: var(--primary-color);
            color: white;
            margin-right: 0.75rem;
        }
        
        .popover {
            border-radius: var(--border-radius-sm);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            max-width: 300px;
        }
        
        .popover-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border-bottom: none;
            padding: 0.75rem 1rem;
        }
        
        .popover-body {
            padding: 1rem;
        }
        
        .project-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .project-item:last-child {
            margin-bottom: 0;
        }
        
        .project-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: auto;
        }
        
        .status-assigned {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success-color);
        }
        
        .status-completed {
            background-color: rgba(72, 149, 239, 0.15);
            color: var(--accent-color);
        }
        
        .status-in-progress {
            background-color: rgba(63, 55, 201, 0.15);
            color: var(--secondary-color);
        }
        
        .date-range {
            font-size: 0.75rem;
            color: var(--gray-color);
            margin-top: 0.25rem;
        }
        
        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .file-upload:hover {
            border-color: var(--primary-color);
        }
        
        .file-upload-icon {
            font-size: 24px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 50px;
        }
        
        .modal-content {
            border-radius: var(--card-border-radius);
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
            border-radius: var(--card-border-radius) var(--card-border-radius) 0 0;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        .has-engineers {
            font-size: 0.8rem;
            color: var(--warning-color);
            margin-top: 0.25rem;
        }
        
        .engineer-tag {
            display: inline-flex;
            align-items: center;
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .engineer-tag i {
            margin-right: 0.25rem;
            font-size: 0.7rem;
        }
        
        .project-engineers {
            display: flex;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .project-option-with-engineers {
            display: flex;
            flex-direction: column;
        }
        
        .no-projects-message {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
            font-style: italic;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="page-header">
        <a href="ProjectAssignment.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Project Assignments
        </a>
        <h1><i class="fas fa-hard-hat me-2"></i> Assign Projects to Site Engineers</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Site Engineers Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-users"></i> Available Site Engineers
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Assigned Projects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($site_engineers_result->num_rows > 0): 
                        ?>
                            <?php while ($engineer = $site_engineers_result->fetch_assoc()): ?>
                                <?php
                                // Get the number of projects assigned to the site engineer
                                $assignment_count = isset($assignment_counts[$engineer['UserID']]) ? $assignment_counts[$engineer['UserID']] : 0;
                                $has_projects = isset($engineer_projects[$engineer['UserID']]) && !empty($engineer_projects[$engineer['UserID']]);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($engineer['UserID']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle">
                                                <?= strtoupper(substr($engineer['FirstName'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($engineer['Email']) ?></td>
                                    <td><?= htmlspecialchars($engineer['Phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($assignment_count > 0): ?>
                                            <div class="assignment-count">
                                                <span class="badge bg-primary"><?= $assignment_count ?></span>
                                                <span>Project<?= $assignment_count > 1 ? 's' : '' ?> Assigned</span>
                                            </div>
                                            
                                            <?php if ($has_projects): ?>
                                                <div class="project-list">
                                                    <?php foreach(array_slice($engineer_projects[$engineer['UserID']], 0, 2) as $project): ?>
                                                        <span class="project-badge">
                                                                                                               <i class="fas fa-project-diagram project-icon"></i>
                                                            <?= htmlspecialchars($project['project_name']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if (count($engineer_projects[$engineer['UserID']]) > 2): ?>
                                                        <button type="button" class="btn btn-sm view-all-btn" 
                                                                data-bs-toggle="popover" 
                                                                title="All Assigned Projects" 
                                                                data-bs-html="true"
                                                                data-bs-content="<?php 
                                                                    $content = '';
                                                                    foreach($engineer_projects[$engineer['UserID']] as $project) {
                                                                        $status_class = 'status-' . strtolower(str_replace(' ', '-', $project['status']));
                                                                        $content .= '<div class=\'project-item\'>';
                                                                        $content .= '<i class=\'fas fa-project-diagram me-2\'></i>';
                                                                        $content .= '<div>';
                                                                        $content .= '<div>' . htmlspecialchars($project['project_name']) . '</div>';
                                                                        $content .= '<div class=\'date-range\'>' . 
                                                                            htmlspecialchars(date('M d, Y', strtotime($project['start_date']))) . ' - ' . 
                                                                            htmlspecialchars(date('M d, Y', strtotime($project['end_date']))) . '</div>';
                                                                        $content .= '</div>';
                                                                        $content .= '<span class=\'project-status ' . $status_class . '\'>' . 
                                                                            htmlspecialchars($project['status']) . '</span>';
                                                                        $content .= '</div>';
                                                                        
                                                                        // Add action buttons for each project
                                                                        $content .= '<div class=\'action-buttons mt-2\'>';
                                                                        $content .= '<button type=\'button\' class=\'btn btn-sm btn-outline-primary action-btn\' onclick=\'openReassignModal(' . $project['assignment_id'] . ')\'><i class=\'fas fa-exchange-alt\'></i> Reassign</button>';
                                                                        $content .= '<button type=\'button\' class=\'btn btn-sm btn-outline-danger action-btn\' onclick=\'openRemoveModal(' . $project['assignment_id'] . ')\'><i class=\'fas fa-trash-alt\'></i> Remove</button>';
                                                                        $content .= '</div>';
                                                                    }
                                                                    echo $content;
                                                                ?>">
                                                            View all <?= count($engineer_projects[$engineer['UserID']]) ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="no-assignments">No projects assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($has_projects): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#engineerProjectsModal" 
                                                    data-engineer-id="<?= $engineer['UserID'] ?>" 
                                                    data-engineer-name="<?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?>">
                                                <i class="fas fa-tasks"></i> Manage
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-tasks"></i> No Projects
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No site engineers found. Please ask the admin to create site engineers first.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assignment Form -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-tasks"></i> Assign Project to Site Engineer
        </div>
        <div class="card-body">
            <form method="POST" action="" id="assignmentForm" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="project_id" class="form-label">Select Project</label>
                        <select name="project_id" id="project_id" class="form-select" required>
                            <option value="">-- Select Project --</option>
                            <?php 
                            // Reset the projects result pointer with the same filter for APPROVED projects only
                            $stmt = $connection->prepare("
                                SELECT * FROM projects 
                                WHERE manager_id = ? 
                                AND decision_status = 'Approved' 
                                ORDER BY project_name ASC
                            ");
                            $stmt->bind_param("i", $manager_id);
                            $stmt->execute();
                            $projects_result = $stmt->get_result();
                            
                            if ($projects_result->num_rows > 0) {
                                while ($project = $projects_result->fetch_assoc()): 
                                    $has_engineers = isset($projects_with_engineers[$project['project_id']]);
                                ?>
                                    <option value="<?php echo $project['project_id']; ?>" 
                                            <?= $has_engineers ? 'data-has-engineers="true"' : '' ?>
                                            data-start="<?php echo $project['start_date']; ?>" 
                                            data-end="<?php echo $project['end_date']; ?>">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                        <?php if ($has_engineers): ?>
                                            (Has Engineers)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile;
                            } else { ?>
                                <option value="" disabled>No approved projects available</option>
                            <?php } ?>
                        </select>
                        <small class="text-muted">Only approved projects are shown. Projects marked with "(Has Engineers)" already have at least one site engineer assigned.</small>
                        <div id="project-engineers-info" class="mt-2" style="display: none;">
                            <div class="alert alert-info p-2 mb-0">
                                <i class="fas fa-info-circle"></i>
                                <small>This project already has the following site engineers assigned:</small>
                                <div id="project-engineers-list" class="project-engineers mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="site_engineer_id" class="form-label">Select Site Engineer</label>
                        <select name="site_engineer_id" id="site_engineer_id" class="form-select" required>
                            <option value="">-- Select Site Engineer --</option>
                            <?php 
                            // Reset the site engineers result pointer
                            $stmt_engineers->execute();
                            $site_engineers_result = $stmt_engineers->get_result();
                            
                            while ($engineer = $site_engineers_result->fetch_assoc()): 
                                $has_assignments = isset($assignment_counts[$engineer['UserID']]) && $assignment_counts[$engineer['UserID']] > 0;
                            ?>
                                <option value="<?php echo $engineer['UserID']; ?>" <?= $has_assignments ? 'data-has-projects="true"' : '' ?>>
                                    <?php echo htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']); ?>
                                    <?php if ($has_assignments): ?>
                                        (<?= $assignment_counts[$engineer['UserID']] ?> projects)
                                    <?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Project Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required readonly>
                        <small class="text-muted">Using project's start date</small>
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required readonly>
                        <small class="text-muted">Using project's end date</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Attachment (Optional)</label>
                    <div class="file-upload">
                        <div class="file-upload-icon">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <input type="file" name="attachment" id="attachment" class="form-control" style="display: none;">
                        <label for="attachment" class="btn btn-outline-secondary">Choose File</label>
                        <div id="file-name" class="file-info">No file chosen</div>
                        <div class="file-info">
                            <small>Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG (Max size: 10MB)</small>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i> Assign Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Engineer Projects Modal -->
<div class="modal fade" id="engineerProjectsModal" tabindex="-1" aria-labelledby="engineerProjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="engineerProjectsModalLabel">Projects Assigned to <span id="engineer-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="engineer-projects-list" class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="engineer-projects-table-body">
                            <!-- Projects will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reassign Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reassignModalLabel">Reassign Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="reassign_assignment_id">
                    <input type="hidden" name="reassign" value="1">
                    
                    <p>Select a new site engineer for this project:</p>
                    
                    <div class="mb-3">
                        <label for="new_engineer_id" class="form-label">New Site Engineer</label>
                        <select name="new_engineer_id" id="new_engineer_id" class="form-select" required>
                            <option value="">-- Select Site Engineer --</option>
                            <?php 
                            // Reset the site engineers result pointer
                            $stmt_engineers->execute();
                            $site_engineers_result = $stmt_engineers->get_result();
                            
                            while ($engineer = $site_engineers_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $engineer['UserID']; ?>">
                                    <?php echo htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reassign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Assignment Modal -->
<div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeModalLabel">Remove Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" id="remove_assignment_id">
                    <input type="hidden" name="remove_assignment" value="1">
                    
                    <p>Are you sure you want to remove this assignment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Remove</button>
                </div>
                        </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                trigger: 'focus',
                placement: 'left'
            });
        });
        
        // Form validation
        const form = document.getElementById('assignmentForm');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const projectSelect = document.getElementById('project_id');
        const engineerSelect = document.getElementById('site_engineer_id');
        const projectEngineersInfo = document.getElementById('project-engineers-info');
        const projectEngineersList = document.getElementById('project-engineers-list');
        
        // Auto-fill project dates when a project is selected
        projectSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const hasEngineers = selectedOption.getAttribute('data-has-engineers') === 'true';
            
            if (this.value) {
                // Get dates from the data attributes
                const startDate = selectedOption.getAttribute('data-start');
                const endDate = selectedOption.getAttribute('data-end');
                
                console.log("Selected project:", this.value);
                console.log("Start date:", startDate);
                console.log("End date:", endDate);
                
                // Set the date values
                startDateInput.value = startDate;
                endDateInput.value = endDate;
                
                // Show assigned engineers if any
                if (hasEngineers && this.value !== '') {
                    // Fetch engineers assigned to this project via AJAX
                    fetchProjectEngineers(this.value);
                    projectEngineersInfo.style.display = 'block';
                } else {
                    projectEngineersInfo.style.display = 'none';
                }
            } else {
                startDateInput.value = '';
                endDateInput.value = '';
                projectEngineersInfo.style.display = 'none';
            }
        });
        
        // File upload display
        document.getElementById('attachment').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // Engineer Projects Modal
        const engineerProjectsModal = document.getElementById('engineerProjectsModal');
        if (engineerProjectsModal) {
            engineerProjectsModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const engineerId = button.getAttribute('data-engineer-id');
                const engineerName = button.getAttribute('data-engineer-name');
                
                document.getElementById('engineer-name').textContent = engineerName;
                
                // Fetch engineer's projects and populate the table
                fetchEngineerProjects(engineerId);
            });
        }
        
        // Function to fetch engineers assigned to a project
        function fetchProjectEngineers(projectId) {
            // This would normally be an AJAX call to the server
            // For this example, we'll use the PHP data we already have
            const projectEngineers = <?php echo json_encode($projects_with_engineers); ?>;
            
            if (projectEngineers[projectId]) {
                let engineersHtml = '';
                const engineers = projectEngineers[projectId].engineers.split(', ');
                
                engineers.forEach(engineer => {
                    engineersHtml += `<span class="engineer-tag"><i class="fas fa-user-hard-hat"></i>${engineer}</span>`;
                });
                
                projectEngineersList.innerHTML = engineersHtml;
            } else {
                projectEngineersList.innerHTML = '<span class="text-muted">No engineers assigned yet.</span>';
            }
        }
        
        // Function to fetch projects assigned to an engineer
        function fetchEngineerProjects(engineerId) {
            // This would normally be an AJAX call to the server
            // For this example, we'll use the PHP data we already have
            const engineerProjects = <?php echo json_encode($engineer_projects); ?>;
            const tableBody = document.getElementById('engineer-projects-table-body');
            
            tableBody.innerHTML = '';
            
            if (engineerProjects[engineerId] && engineerProjects[engineerId].length > 0) {
                engineerProjects[engineerId].forEach(project => {
                    const row = document.createElement('tr');
                    
                    // Format dates
                    const startDate = new Date(project.start_date).toLocaleDateString();
                    const endDate = new Date(project.end_date).toLocaleDateString();
                    
                    // Determine status class
                    let statusClass = '';
                    if (project.status === 'Assigned') {
                        statusClass = 'status-assigned';
                    } else if (project.status === 'Completed') {
                        statusClass = 'status-completed';
                    } else if (project.status === 'In Progress') {
                        statusClass = 'status-in-progress';
                    }
                    
                    row.innerHTML = `
                        <td>${project.project_name}</td>
                        <td>${startDate}</td>
                        <td>${endDate}</td>
                        <td><span class="project-status ${statusClass}">${project.status}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary action-btn" onclick="openReassignModal(${project.assignment_id})">
                                    <i class="fas fa-exchange-alt"></i> Reassign
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger action-btn" onclick="openRemoveModal(${project.assignment_id})">
                                    <i class="fas fa-trash-alt"></i> Remove
                                </button>
                            </div>
                        </td>
                    `;
                    
                    tableBody.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="5" class="text-center py-3">No projects assigned to this engineer.</td>';
                tableBody.appendChild(row);
            }
        }
        
        // Check for duplicate assignments when both project and engineer are selected
        form.addEventListener('submit', function(e) {
            const projectId = projectSelect.value;
            const engineerId = engineerSelect.value;
            
            if (projectId && engineerId) {
                // Check if this combination already exists
                const projectAssignments = <?php echo json_encode($project_assignments); ?>;
                
                if (projectAssignments[projectId]) {
                    for (let i = 0; i < projectAssignments[projectId].length; i++) {
                        if (projectAssignments[projectId][i].contractor_id == engineerId) {
                            e.preventDefault();
                            alert('This project is already assigned to this site engineer.');
                            return false;
                        }
                    }
                }
            }
        });
    });
    
    // Functions for modals
    function openReassignModal(assignmentId) {
        document.getElementById('reassign_assignment_id').value = assignmentId;
        var reassignModal = new bootstrap.Modal(document.getElementById('reassignModal'));
        reassignModal.show();
    }
    
    function openRemoveModal(assignmentId) {
        document.getElementById('remove_assignment_id').value = assignmentId;
        var removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
        removeModal.show();
    }
</script>
</body>
</html>
<?php $connection->close(); ?>


