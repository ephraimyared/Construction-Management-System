<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Project Manager') {
    header("Location: unauthorized.php");
    exit();
}

$message = "";
$message_type = "success";

// Fetch projects managed by this manager that have been approved
$projects_query = "
    SELECT * FROM projects 
    WHERE manager_id = ? 
    AND decision_status = 'Approved' 
    ORDER BY project_name ASC
";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$projects_result = $stmt->get_result();

// Reset the contractors result pointer
$stmt_contractors = $connection->prepare("SELECT * FROM users WHERE Role = 'Contractor' AND managed_by_contractor_id = ?");
$stmt_contractors->bind_param("i", $_SESSION['user_id']);
$stmt_contractors->execute();
$contractors_result = $stmt_contractors->get_result();

// Fetch the number of projects assigned to each contractor
$assignment_counts = [];
$result = $connection->query("SELECT contractor_id, COUNT(*) as assignment_count FROM project_assignments WHERE role_in_project = 'Assigned Contractor' GROUP BY contractor_id");
while ($row = $result->fetch_assoc()) {
    $assignment_counts[$row['contractor_id']] = $row['assignment_count'];
}

// Fetch the actual projects assigned to each contractor
$contractor_projects = [];
$projects_query = $connection->query("
    SELECT pa.contractor_id, p.project_name, pa.start_date, pa.end_date, pa.status, pa.project_id, pa.assignment_id
    FROM project_assignments pa
    JOIN projects p ON pa.project_id = p.project_id
    WHERE pa.role_in_project = 'Assigned Contractor'
    ORDER BY pa.contractor_id, p.project_name
");

while ($row = $projects_query->fetch_assoc()) {
    if (!isset($contractor_projects[$row['contractor_id']])) {
        $contractor_projects[$row['contractor_id']] = [];
    }
    $contractor_projects[$row['contractor_id']][] = [
        'project_name' => $row['project_name'],
        'project_id' => $row['project_id'],
        'assignment_id' => $row['assignment_id'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'status' => $row['status']
    ];
}

// Create a lookup array for projects that already have contractors assigned
$project_assignments = [];
$assigned_projects_query = $connection->query("
    SELECT project_id, contractor_id, assignment_id 
    FROM project_assignments 
    WHERE role_in_project = 'Assigned Contractor'
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
        header("Location: ManagerAssignToContractor.php?success=removed");
        exit();
    } else {
        $message = "❌ Error removing assignment: " . $stmt->error;
        $message_type = "danger";
    }
}

// Handle reassignment
if (isset($_POST['reassign']) && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];
    $new_contractor_id = $_POST['new_contractor_id'];
    
    // Update the assignment with the new contractor
    $update_query = "UPDATE project_assignments SET contractor_id = ? WHERE assignment_id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("ii", $new_contractor_id, $assignment_id);
    
    if ($stmt->execute()) {
        $message = "✅ Project successfully reassigned to new contractor!";
        $message_type = "success";
        
        // Refresh the page to update the data
        header("Location: ManagerAssignToContractor.php?success=reassigned");
        exit();
    } else {
        $message = "❌ Error reassigning project: " . $stmt->error;
        $message_type = "danger";
    }
}

// Handle form submission for new assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['reassign']) && !isset($_POST['remove_assignment'])) {
    $project_id = $_POST['project_id'];
    $contractor_id = $_POST['contractor_id'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = 'Assigned';
    $role_in_project = 'Assigned Contractor';
    $user_id = $_SESSION['user_id']; // Manager ID
    
    // Check if this project is already assigned to this contractor
    $already_assigned = false;
    if (isset($project_assignments[$project_id])) {
        foreach ($project_assignments[$project_id] as $assignment) {
            if ($assignment['contractor_id'] == $contractor_id) {
                $already_assigned = true;
                break;
            }
        }
    }
    
    if ($already_assigned) {
        $message = "⚠️ This project is already assigned to this contractor.";
        $message_type = "danger";
    } else {
        // Check if this project already has a contractor assigned
        $has_contractor = isset($project_assignments[$project_id]) && !empty($project_assignments[$project_id]);
        
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
                // If the project already has a contractor, show a warning but still allow the assignment
                if ($has_contractor) {
                    $warning_message = "⚠️ Note: This project already has a contractor assigned. You are adding an additional contractor to work on this project.";
                }
                
                $insert_query = "INSERT INTO project_assignments (project_id, user_id, contractor_id, description, start_date, end_date, status, role_in_project, attachment_path)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($insert_query);
                $stmt->bind_param("iiissssss", $project_id, $user_id, $contractor_id, $description, $start_date, $end_date, $status, $role_in_project, $file_path);
                if ($stmt->execute()) {
                    $message = "✅ Task assigned successfully!";
                    if (isset($warning_message)) {
                        $message .= " " . $warning_message;
                    }
                    $message_type = "success";
                    
                    // Refresh the page to update the data
                    header("Location: ManagerAssignToContractor.php?success=assigned");
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
        $message = "✅ Task assigned successfully!";
        $message_type = "success";
    } else if ($_GET['success'] == 'reassigned') {
        $message = "✅ Project successfully reassigned to new contractor!";
        $message_type = "success";
    } else if ($_GET['success'] == 'removed') {
        $message = "✅ Assignment successfully removed!";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Task to Contractor</title>
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
        
        .reassign-btn {
            background-color: #ff9800;
            color: white;
            border: none;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-right: 0.5rem;
        }
        
        .reassign-btn i {
            margin-right: 0.35rem;
        }
        
        .reassign-btn:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .remove-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .remove-btn i {
            margin-right: 0.35rem;
        }
        
        .remove-btn:hover {
            background-color: #c1121f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
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
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
        <h1><i class="fas fa-hard-hat me-2"></i> Assign Tasks to Contractors</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Contractors Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-users"></i> Available Contractors
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($contractors_result->num_rows > 0): 
                        ?>
                            <?php while ($contractor = $contractors_result->fetch_assoc()): ?>
                                <?php
                                // Get the number of projects assigned to the contractor
                                $assignment_count = isset($assignment_counts[$contractor['UserID']]) ? $assignment_counts[$contractor['UserID']] : 0;
                                $has_projects = isset($contractor_projects[$contractor['UserID']]) && !empty($contractor_projects[$contractor['UserID']]);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($contractor['UserID']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle">
                                                <?= strtoupper(substr($contractor['FirstName'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($contractor['Email']) ?></td>
                                    <td><?= htmlspecialchars($contractor['Phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($assignment_count > 0): ?>
                                            <div class="assignment-count">
                                                <span class="badge bg-primary"><?= $assignment_count ?></span>
                                                <span>Project<?= $assignment_count > 1 ? 's' : '' ?> Assigned</span>
                                            </div>
                                            
                                            <?php if ($has_projects): ?>
                                                <div class="project-list">
                                                    <?php foreach(array_slice($contractor_projects[$contractor['UserID']], 0, 2) as $project): ?>
                                                        <span class="project-badge">
                                                            <i class="fas fa-project-diagram project-icon"></i>
                                                            <?= htmlspecialchars($project['project_name']) ?>
                                                            
                                                            <div class="action-buttons ms-2">
                                                                <button type="button" class="btn btn-sm reassign-btn" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#reassignModal"
                                                                        data-assignment-id="<?= $project['assignment_id'] ?>"
                                                                        data-project-name="<?= htmlspecialchars($project['project_name']) ?>"
                                                                        data-contractor-id="<?= $contractor['UserID'] ?>"
                                                                        data-contractor-name="<?= htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']) ?>">
                                                                    <i class="fas fa-exchange-alt"></i> Reassign
                                                                </button>
                                                                
                                                                <button type="button" class="btn btn-sm remove-btn" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#removeModal"

                                                                        data-assignment-id="<?= $project['assignment_id'] ?>"
                                                                        data-project-name="<?= htmlspecialchars($project['project_name']) ?>"
                                                                        data-contractor-name="<?= htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']) ?>">
                                                                    <i class="fas fa-trash-alt"></i> Remove
                                                                </button>
                                                            </div>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if (count($contractor_projects[$contractor['UserID']]) > 2): ?>
                                                        <button type="button" class="btn btn-sm view-all-btn" 
                                                                data-bs-toggle="popover" 
                                                                title="All Assigned Projects" 
                                                                data-bs-html="true"
                                                                data-bs-content="<?php 
                                                                    $content = '';
                                                                    foreach($contractor_projects[$contractor['UserID']] as $project) {
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
                                                                        $content .= '<div class=\'mt-2\'>';
                                                                        $content .= '<button type=\'button\' class=\'btn btn-sm reassign-btn\' 
                                                                            onclick=\'openReassignModal(' . $project['assignment_id'] . ', "' . 
                                                                            htmlspecialchars($project['project_name']) . '", ' . 
                                                                            $contractor['UserID'] . ', "' . 
                                                                            htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']) . 
                                                                            '")\'>
                                                                            <i class=\'fas fa-exchange-alt\'></i> Reassign
                                                                        </button>';
                                                                        $content .= '<button type=\'button\' class=\'btn btn-sm remove-btn ms-1\' 
                                                                            onclick=\'openRemoveModal(' . $project['assignment_id'] . ', "' . 
                                                                            htmlspecialchars($project['project_name']) . '", "' . 
                                                                            htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']) . 
                                                                            '")\'>
                                                                            <i class=\'fas fa-trash-alt\'></i> Remove
                                                                        </button>';
                                                                        $content .= '</div>';
                                                                        $content .= '</div>';
                                                                    }
                                                                    echo $content;
                                                                ?>">
                                                            View all <?= count($contractor_projects[$contractor['UserID']]) ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="no-assignments">No projects assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No contractors found. Please ask the admin to create contractors first.</td>
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
            <i class="fas fa-tasks"></i> Assign Task to Contractor
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
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $projects_result = $stmt->get_result();
                            
                            if ($projects_result->num_rows > 0) {
                                while ($project = $projects_result->fetch_assoc()): 
                                    // Check if this project already has a contractor assigned
                                    $has_contractor = isset($project_assignments[$project['project_id']]) && !empty($project_assignments[$project['project_id']]);
                                ?>
                                    <option value="<?php echo $project['project_id']; ?>" 
                                            data-start="<?php echo $project['start_date']; ?>" 
                                            data-end="<?php echo $project['end_date']; ?>">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                        <?php if ($has_contractor): ?> 
                                            (Has Contractor)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile;
                            } else { ?>
                                <option value="" disabled>No approved projects available</option>
                            <?php } ?>
                        </select>
                       
                    </div>

                    <div class="col-md-6">
                        <label for="contractor_id" class="form-label">Select Contractor</label>
                        <select name="contractor_id" id="contractor_id" class="form-select" required>
                            <option value="">-- Select Contractor --</option>
                            <?php 
                            // Reset the contractors result pointer
                            $stmt_contractors = $connection->prepare("SELECT * FROM users WHERE Role = 'Contractor' AND managed_by_contractor_id = ?");
                            $stmt_contractors->bind_param("i", $_SESSION['user_id']);
                            $stmt_contractors->execute();
                            $contractors_result = $stmt_contractors->get_result();
                            
                            while ($contractor = $contractors_result->fetch_assoc()): 
                                // Get the number of projects assigned to this contractor
                                $count = isset($assignment_counts[$contractor['UserID']]) ? $assignment_counts[$contractor['UserID']] : 0;
                            ?>
                                <option value="<?php echo $contractor['UserID']; ?>">
                                    <?php echo htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']); ?>
                                    <?php if ($count > 0): ?> (<?= $count ?> project<?= $count > 1 ? 's' : '' ?>)<?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div id="duplicateWarning" class="text-danger mt-2" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i> This contractor is already assigned to this project.
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Task Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required readonly>
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
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-paper-plane me-2"></i> Assign Task
                    </button>
                </div>
            </form>
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
            <div class="modal-body">
                <form method="POST" action="" id="reassignForm">
                    <input type="hidden" name="reassign" value="1">
                    <input type="hidden" name="assignment_id" id="modal_assignment_id">
                    
                    <div class="mb-3">
                        <p>You are reassigning the project <strong id="modal_project_name"></strong> from <strong id="modal_current_contractor"></strong> to a new contractor.</p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_contractor_id" class="form-label">Select New Contractor</label>
                        <select name="new_contractor_id" id="new_contractor_id" class="form-select" required>
                            <option value="">-- Select New Contractor --</option>
                            <?php 
                            // Reset the contractors result pointer
                            $stmt_contractors = $connection->prepare("SELECT * FROM users WHERE Role = 'Contractor' AND managed_by_contractor_id = ?");
                            $stmt_contractors->bind_param("i", $_SESSION['user_id']);
                            $stmt_contractors->execute();
                            $contractors_result = $stmt_contractors->get_result();
                            
                            while ($contractor = $contractors_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $contractor['UserID']; ?>">
                                    <?php echo htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-exchange-alt me-2"></i> Confirm Reassignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Remove Assignment Modal -->
<div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="removeModalLabel">Remove Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="removeForm">
                    <input type="hidden" name="remove_assignment" value="1">
                    <input type="hidden" name="assignment_id" id="remove_assignment_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone.
                    </div>
                    
                    <p>Are you sure you want to remove the project <strong id="remove_project_name"></strong> from <strong id="remove_contractor_name"></strong>?</p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-2"></i> Remove Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // File upload preview
    document.getElementById('attachment').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        document.getElementById('file-name').textContent = fileName;
    });
    
    // Auto-fill project dates when a project is selected
    document.getElementById('project_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (this.value) {
            // Get dates from the data attributes
            const startDate = selectedOption.getAttribute('data-start');
            const endDate = selectedOption.getAttribute('data-end');
            
            // Set the date values
            startDateInput.value = startDate;
            endDateInput.value = endDate;
        } else {
            startDateInput.value = '';
            endDateInput.value = '';
        }
    });

    // Initialize popovers
    document.addEventListener('DOMContentLoaded', function() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                trigger: 'click',
                html: true,
                sanitize: false
            });
        });
        
        // Close popovers when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[data-bs-toggle="popover"]') && !e.target.closest('.popover')) {
                popoverList.forEach(function(popover) {
                    popover.hide();
                });
            }
        });
    });
    
    // Functions to open modals from popovers
    function openReassignModal(assignmentId, projectName, contractorId, contractorName) {
        document.getElementById('modal_assignment_id').value = assignmentId;
        document.getElementById('modal_project_name').textContent = projectName;
        document.getElementById('modal_current_contractor').textContent = contractorName;
        
        // Set the current contractor as selected in the dropdown
        const newContractorSelect = document.getElementById('new_contractor_id');
        for (let i = 0; i < newContractorSelect.options.length; i++) {
            if (newContractorSelect.options[i].value == contractorId) {
                newContractorSelect.options[i].disabled = true;
            } else {
                newContractorSelect.options[i].disabled = false;
            }
        }
        
        // Close any open popovers
        var popoverList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverList.forEach(function(el) {
            bootstrap.Popover.getInstance(el).hide();
        });
        
        // Open the modal
        var reassignModal = new bootstrap.Modal(document.getElementById('reassignModal'));
        reassignModal.show();
    }
    
    function openRemoveModal(assignmentId, projectName, contractorName) {
        document.getElementById('remove_assignment_id').value = assignmentId;
        document.getElementById('remove_project_name').textContent = projectName;
        document.getElementById('remove_contractor_name').textContent = contractorName;
        
        // Close any open popovers
        var popoverList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverList.forEach(function(el) {
            bootstrap.Popover.getInstance(el).hide();
        });
        
        // Open the modal
        var removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
        removeModal.show();
    }
    
    // Handle reassign modal events
    document.getElementById('reassignModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button) {
            const assignmentId = button.getAttribute('data-assignment-id');
            const projectName = button.getAttribute('data-project-name');
            const contractorId = button.getAttribute('data-contractor-id');
            const contractorName = button.getAttribute('data-contractor-name');
            
            document.getElementById('modal_assignment_id').value = assignmentId;
            document.getElementById('modal_project_name').textContent = projectName;
            document.getElementById('modal_current_contractor').textContent = contractorName;
            
            // Disable selecting the current contractor
            const newContractorSelect = document.getElementById('new_contractor_id');
            for (let i = 0; i < newContractorSelect.options.length; i++) {
                if (newContractorSelect.options[i].value == contractorId) {
                    newContractorSelect.options[i].disabled = true;
                } else {
                    newContractorSelect.options[i].disabled = false;
                }
            }
        }
    });
    
    // Handle remove modal events
    document.getElementById('removeModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button) {
            const assignmentId = button.getAttribute('data-assignment-id');
            const projectName = button.getAttribute('data-project-name');
            const contractorName = button.getAttribute('data-contractor-name');
            
            document.getElementById('remove_assignment_id').value = assignmentId;
            document.getElementById('remove_project_name').textContent = projectName;
            document.getElementById('remove_contractor_name').textContent = contractorName;
        }
    });
    
    // Check for duplicate assignments
    document.getElementById('contractor_id').addEventListener('change', checkDuplicateAssignment);
    document.getElementById('project_id').addEventListener('change', checkDuplicateAssignment);
    
    function checkDuplicateAssignment() {
        const projectId = document.getElementById('project_id').value;
        const contractorId = document.getElementById('contractor_id').value;
        const warningElement = document.getElementById('duplicateWarning');
        
        if (projectId && contractorId) {
            // This is a simplified check - in a real application, you might want to do an AJAX call to the server
            // to check if this assignment already exists
            const isDuplicate = false; // Replace with actual logic to check for duplicates
            
            if (isDuplicate) {
                warningElement.style.display = 'block';
                document.getElementById('submitBtn').disabled = true;
            } else {
                warningElement.style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
            }
        } else {
            warningElement.style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
        }
    }
</script>
</body>
</html>
<?php $connection->close(); ?>

