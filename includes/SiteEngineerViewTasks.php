<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in and is a Site Engineer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

$site_engineer_id = $_SESSION['user_id'];
$message = "";

// Fetch tasks assigned to this Site Engineer
// MODIFIED QUERY: Changed from pa.user_id = ? to pa.contractor_id = ? to match ManagerAssignToSiteEngineer.php
$tasks_query = "SELECT pa.*, p.project_name, p.status as project_status, 
                u.FirstName as manager_first_name, u.LastName as manager_last_name 
                FROM project_assignments pa 
                JOIN projects p ON pa.project_id = p.project_id 
                JOIN users u ON pa.user_id = u.UserID 
                WHERE pa.contractor_id = ? AND pa.role_in_project = 'Assigned Site Engineer' 
                ORDER BY pa.start_date DESC";
$stmt = $connection->prepare($tasks_query);
$stmt->bind_param("i", $site_engineer_id);
$stmt->execute();
$tasks_result = $stmt->get_result();

// Handle task status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $assignment_id = $_POST['assignment_id'];
    $new_status = $_POST['new_status'];
    $completion_notes = $_POST['completion_notes'] ?? '';
    
    $update_query = "UPDATE project_assignments SET status = ?, completion_notes = ?, updated_at = NOW() 
                    WHERE assignment_id = ? AND contractor_id = ?";
    $update_stmt = $connection->prepare($update_query);
    $update_stmt->bind_param("ssii", $new_status, $completion_notes, $assignment_id, $site_engineer_id);
    
    if ($update_stmt->execute()) {
        $message = "✅ Task status updated successfully!";
        
        // Refresh the tasks list
        $stmt->execute();
        $tasks_result = $stmt->get_result();
    } else {
        $message = "❌ Error updating task status: " . $update_stmt->error;
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assigned Tasks | Site Engineer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .back-button {
            margin-bottom: 15px;
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .task-card {
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .task-card.status-completed {
            border-left-color: var(--success-color);
        }
        
        .task-card.status-in-progress {
            border-left-color: var(--warning-color);
        }
        
        .task-card.status-pending {
            border-left-color: var(--info-color);
        }
        
        .task-card.status-cancelled {
            border-left-color: var(--danger-color);
        }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .task-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .task-status {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-assigned {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .status-in-progress {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .status-completed {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .task-dates {
            display: flex;
            margin-bottom: 15px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .task-date {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }
        
        .task-date i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .task-description {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .task-project {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .project-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .project-details {
            flex: 1;
        }
        
        .project-name {
            font-weight: 600;
            margin: 0;
            color: var(--secondary-color);
        }
        
        .project-location {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 3px 0 0;
        }
        
        .task-manager {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .manager-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .manager-details {
            flex: 1;
        }
        
        .manager-name {
            font-weight: 600;
            margin: 0;
            color: var(--secondary-color);
        }
        
        .manager-role {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 3px 0 0;
        }
        
        .task-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-update {
            background-color: var(--primary-color);
            border: none;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-update:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-complete {
            background-color: var(--success-color);
            border: none;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-complete:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--secondary-color);
        }
        
        .empty-state p {
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="SiteEngineerDashboard.php" class="btn btn-light back-button mb-3">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h2><i class="fas fa-tasks me-2"></i>My Assigned Tasks</h2>
        <p>View and manage tasks assigned to you by project managers</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Debug information -->
    <!-- Debug: User ID = <?= $site_engineer_id ?> -->
    <!-- Debug: Number of tasks found = <?= $tasks_result->num_rows ?> -->
    
    <?php if ($tasks_result->num_rows > 0): ?>
        <?php while ($task = $tasks_result->fetch_assoc()): ?>
            <div class="card task-card status-<?= strtolower(str_replace(' ', '-', $task['status'])) ?>">
                <div class="card-body">
                    <div class="task-header">
                        <h3 class="task-title">Task #<?= $task['assignment_id'] ?></h3>
                        <span class="task-status status-<?= strtolower(str_replace(' ', '-', $task['status'])) ?>">
                            <?= $task['status'] ?>
                        </span>
                    </div>
                    



                                           <div class="task-date">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Start: <?= date('M d, Y', strtotime($task['start_date'])) ?></span>
                        </div>
                        <div class="task-date">
                            <i class="fas fa-calendar-check"></i>
                            <span>End: <?= date('M d, Y', strtotime($task['end_date'])) ?></span>
                        </div>
                        <?php if (isset($task['updated_at']) && !empty($task['updated_at'])): ?>
                        <div class="task-date">
                            <i class="fas fa-clock"></i>
                            <span>Last Updated: <?= date('M d, Y', strtotime($task['updated_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>




                    
                    <div class="task-description">
                        <?= nl2br(htmlspecialchars($task['description'])) ?>
                    </div>
                    
                    <div class="task-project">
                        <div class="project-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="project-details">
                            <h5 class="project-name"><?= htmlspecialchars($task['project_name']) ?></h5>
                            <p class="project-location">Project Status: <?= $task['project_status'] ?></p>
                        </div>
                    </div>
                    
                    <div class="task-manager">
                        <div class="manager-avatar">
                            <?= strtoupper(substr($task['manager_first_name'], 0, 1)) ?>
                        </div>
                        <div class="manager-details">
                            <h5 class="manager-name"><?= htmlspecialchars($task['manager_first_name'] . ' ' . $task['manager_last_name']) ?></h5>
                            <p class="manager-role">Project Manager</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($task['completion_notes'])): ?>
                    <div class="mt-3">
                        <h6 class="fw-bold">Completion Notes:</h6>
                        <div class="task-description">
                            <?= nl2br(htmlspecialchars($task['completion_notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($task['attachment_path'])): ?>
                    <div class="mt-3">
                        <h6 class="fw-bold">Attachment:</h6>
                        <a href="<?= htmlspecialchars($task['attachment_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-file-download me-1"></i> View Attachment
                        </a>
                    </div>
                    <?php endif; ?>
                    
                                        <div class="task-actions">
                        <?php if ($task['status'] !== 'Completed' && $task['status'] !== 'Cancelled'): ?>
                            <a href="SiteEngineerSubmitReport.php?assignment_id=<?= $task['assignment_id'] ?>" class="btn btn-primary btn-update">
                                <i class="fas fa-edit me-1"></i> Update Status
                            </a>
                            
                            <?php if ($task['status'] === 'In Progress'): ?>
                            <button type="button" class="btn btn-success btn-complete" data-bs-toggle="modal" data-bs-target="#completeTaskModal<?= $task['assignment_id'] ?>">
                                <i class="fas fa-check-circle me-1"></i> Mark as Completed
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Update Task Status Modal -->
            <div class="modal fade" id="updateTaskModal<?= $task['assignment_id'] ?>" tabindex="-1" aria-labelledby="updateTaskModalLabel<?= $task['assignment_id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateTaskModalLabel<?= $task['assignment_id'] ?>">Update Task Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="">
                            <div class="modal-body">
                                <input type="hidden" name="assignment_id" value="<?= $task['assignment_id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                
                                <div class="mb-3">
                                    <label for="new_status" class="form-label">Status</label>
                                    <select class="form-select" id="new_status" name="new_status" required>
                                        <option value="">-- Select Status --</option>
                                        <option value="Assigned" <?= $task['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                        <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $task['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="completion_notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="completion_notes" name="completion_notes" rows="4" placeholder="Add any notes about the status update"><?= htmlspecialchars($task['completion_notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Complete Task Modal -->
            <div class="modal fade" id="completeTaskModal<?= $task['assignment_id'] ?>" tabindex="-1" aria-labelledby="completeTaskModalLabel<?= $task['assignment_id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="completeTaskModalLabel<?= $task['assignment_id'] ?>">Complete Task</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="">
                            <div class="modal-body">
                                <input type="hidden" name="assignment_id" value="<?= $task['assignment_id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="new_status" value="Completed">
                                
                                <p>Are you sure you want to mark this task as completed?</p>
                                
                                <div class="mb-3">
                                    <label for="completion_notes_complete" class="form-label">Completion Notes</label>
                                    <textarea class="form-control" id="completion_notes_complete" name="completion_notes" rows="4" placeholder="Add any notes about the completion of this task"><?= htmlspecialchars($task['completion_notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Mark as Completed</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No Tasks Assigned</h3>
                <p>You don't have any tasks assigned to you at the moment. Check back later or contact your project manager.</p>
                <a href="SiteEngineerDashboard.php" class="btn btn-primary">
                    <i class="fas fa-home me-1"></i> Return to Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

