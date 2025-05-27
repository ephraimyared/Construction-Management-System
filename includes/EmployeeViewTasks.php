<?php 
session_start();
include '../db_connection.php'; // Include the database connection

// Check if the user is logged in and their role is 'Employee'
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Employee') {
    header("Location: unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Query to fetch tasks assigned to the employee using 'user_id'
$query = "SELECT ta.task_id, t.task_name, 
          COALESCE(ta.task_description, t.description) AS task_description, 
          COALESCE(ta.assignment_date, NOW()) AS start_date, 
          COALESCE(ta.end_date, DATE_ADD(NOW(), INTERVAL 14 DAY)) AS end_date, 
          p.project_name,
          COALESCE(ta.status, 'Assigned') AS status,
          u.FirstName AS assigned_by_first,
          u.LastName AS assigned_by_last
          FROM task_assignments ta
          LEFT JOIN tasks t ON ta.task_id = t.task_id
          LEFT JOIN projects p ON ta.project_id = p.project_id
          LEFT JOIN users u ON t.assigned_by = u.UserID
          WHERE ta.user_id = ?";
// Using 'user_id' for employee identification

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id); // Bind the user ID parameter
$stmt->execute();
$tasks_result = $stmt->get_result();

// Get employee information
$employee_query = "SELECT FirstName, LastName FROM users WHERE UserID = ?";
$stmt = $connection->prepare($employee_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$employee_result = $stmt->get_result();
$employee = $employee_result->fetch_assoc();

// Update task status if form is submitted
if (isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE task_assignments SET status = ? WHERE task_id = ? AND user_id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("sii", $new_status, $task_id, $user_id);
    
    if ($stmt->execute()) {
        // Redirect to refresh the page and show updated data
        header("Location: EmployeeViewTasks.php?success=1");
        exit();
    }
}

// Count tasks by status
$status_counts = [
    'Assigned' => 0,
    'In Progress' => 0,
    'Completed' => 0
];

// Store tasks by status for easier display
$tasks_by_status = [
    'Assigned' => [],
    'In Progress' => [],
    'Completed' => []
];

// Process tasks for display
if ($tasks_result->num_rows > 0) {
    // Reset result pointer
    $tasks_result->data_seek(0);
    
    while ($task = $tasks_result->fetch_assoc()) {
        $status = $task['status'];
        if (!isset($status_counts[$status])) {
            $status_counts[$status] = 0;
        }
        $status_counts[$status]++;
        
        // Add task to appropriate status array
        if (!isset($tasks_by_status[$status])) {
            $tasks_by_status[$status] = [];
        }
        $tasks_by_status[$status][] = $task;
    }
    
    // Reset result pointer again for the main display
    $tasks_result->data_seek(0);
}

// Check for success message
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Task status updated successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | Employee Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
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
        
        .page-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 1rem;
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
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--card-border-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
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
        
        .stat-assigned {
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
        
        .task-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .task-card {
            background-color: white;
            border-radius: var(--border-radius-sm);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .task-card-assigned {
            border-left-color: var(--primary-color);
        }
        
        .task-card-in-progress {
            border-left-color: var(--accent-color);
        }
        
        .task-card-completed {
            border-left-color: var(--success-color);
        }
        
        .task-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.1rem;
            color: var(--dark-color);
        }
        
        .task-project {
            font-size: 0.8rem;
            color: var(--gray-color);
            margin-top: 0.25rem;
        }
        
        .task-status {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-assigned {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .status-in-progress {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--accent-color);
        }
        
        .status-completed {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }
        
        .task-content {
            padding: 1rem;
        }
        
        .task-description {
            margin-bottom: 1rem;
            color: var(--dark-color);
            font-size: 0.95rem;
        }
        
        .task-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        
        .task-dates {
            display: flex;
            flex-direction: column;
        }
        
        .task-date-label {
            color: var(--gray-color);
            margin-bottom: 0.25rem;
        }
        
        .task-date-value {
            font-weight: 500;
        }
        
        .task-assigned-by {
            text-align: right;
        }
        
        .task-assigned-label {
            color: var(--gray-color);
            margin-bottom: 0.25rem;
        }
        
        .task-assigned-value {
            font-weight: 500;
        }
        
        .task-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .update-status-form {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .status-select {
            flex: 1;
            margin-right: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.9rem;
        }
        
        .update-btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-size: 0.9rem;
            font-weight: 500
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .update-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .view-details-btn {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-details-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            max-width: 400px;
            margin: 0 auto;
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
        
        .task-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        .task-tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: var(--gray-color);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
        }
        
        .task-tab.active {
            color: var(--primary-color);
        }
        
        .task-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .task-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.1);
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .task-tab.active .task-tab-count {
            background-color: var(--primary-color);
            color: white;
        }
        
        .task-content-container {
            min-height: 300px;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1.25rem;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .task-list {
                grid-template-columns: 1fr;
            }
            
            .task-tabs {
                padding-bottom: 0.5rem;
            }
            
            .task-tab {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <a href="EmployeeDashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1><i class="fas fa-tasks me-2"></i> My Tasks</h1>
            <p>Welcome, <?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>! Here are your assigned tasks.</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-assigned">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?= $status_counts['Assigned'] ?></p>
                    <p class="stat-label">Assigned Tasks</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-progress">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?= $status_counts['In Progress'] ?></p>
                    <p class="stat-label">In Progress</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon stat-completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <p class="stat-value"><?= $status_counts['Completed'] ?></p>
                    <p class="stat-label">Completed</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                <span>Task Management</span>
            </div>
            <div class="card-body">
                <div class="task-tabs">
                    <div class="task-tab active" data-tab="all">
                        All Tasks <span class="task-tab-count"><?= $tasks_result->num_rows ?></span>
                    </div>
                    <div class="task-tab" data-tab="assigned">
                        Assigned <span class="task-tab-count"><?= $status_counts['Assigned'] ?></span>
                    </div>
                    <div class="task-tab" data-tab="in-progress">
                        In Progress <span class="task-tab-count"><?= $status_counts['In Progress'] ?></span>
                    </div>
                    <div class="task-tab" data-tab="completed">
                        Completed <span class="task-tab-count"><?= $status_counts['Completed'] ?></span>
                    </div>
                </div>
                
                <div class="task-content-container">
                    <div class="task-content" id="all-tasks">
                        <?php if ($tasks_result->num_rows > 0): ?>
                            <div class="task-list">
                                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                    <?php 
                                        $status_class = strtolower(str_replace(' ', '-', $task['status']));
                                    ?>
                                    <div class="task-card task-card-<?= $status_class ?>">
                                        <div class="task-header">
                                            <div>
                                                <h3 class="task-title"><?= htmlspecialchars($task['task_name']) ?></h3>
                                                <div class="task-project">Project: <?= htmlspecialchars($task['project_name']) ?></div>
                                            </div>
                                            <span class="task-status status-<?= $status_class ?>"><?= htmlspecialchars($task['status']) ?></span>
                                        </div>
                                        <div class="task-content">
                                            <div class="task-description">
                                                <?= htmlspecialchars($task['task_description']) ?>
                                            </div>
                                            <div class="task-meta">
                                                <div class="task-dates">
                                                    <div class="task-date-label">Start Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['start_date']))) ?></div>
                                                </div>
                                                <div class="task-dates">
                                                    <div class="task-date-label">Due Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['end_date']))) ?></div>
                                                </div>
                                                <div class="task-assigned-by">
                                                    <div class="task-assigned-label">Assigned By:</div>
                                                    <div class="task-assigned-value">
                                                        <?= htmlspecialchars($task['assigned_by_first'] . ' ' . $task['assigned_by_last']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="task-actions">
                                                <form class="update-status-form" method="POST" action="EmployeeViewTasks.php">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <select name="status" class="status-select">
                                                        <option value="Assigned" <?= $task['status'] == 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                                        <option value="In Progress" <?= $task['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                        <option value="Completed" <?= $task['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="update-btn">
                                                        <i class="fas fa-sync-alt me-1"></i> Update
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h3>No Tasks Assigned</h3>
                                <p>You don't have any tasks assigned to you at the moment. Check back later or contact your supervisor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-content" id="assigned-tasks" style="display: none;">
                        <?php if (count($tasks_by_status['Assigned']) > 0): ?>
                            <div class="task-list">
                                <?php foreach ($tasks_by_status['Assigned'] as $task): ?>
                                    <div class="task-card task-card-assigned">
                                        <div class="task-header">
                                            <div>
                                                <h3 class="task-title"><?= htmlspecialchars($task['task_name']) ?></h3>
                                                <div class="task-project">Project: <?= htmlspecialchars($task['project_name']) ?></div>
                                            </div>
                                            <span class="task-status status-assigned">Assigned</span>
                                        </div>
                                        <div class="task-content">
                                            <div class="task-description">
                                                <?= htmlspecialchars($task['task_description']) ?>
                                            </div>
                                            <div class="task-meta">
                                                <div class="task-dates">
                                                    <div class="task-date-label">Start Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['start_date']))) ?></div>
                                                </div>
                                                <div class="task-dates">
                                                    <div class="task-date-label">Due Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['end_date']))) ?></div>
                                                </div>
                                                <div class="task-assigned-by">
                                                    <div class="task-assigned-label">Assigned By:</div>
                                                    <div class="task-assigned-value">
                                                        <?= htmlspecialchars($task['assigned_by_first'] . ' ' . $task['assigned_by_last']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="task-actions">
                                                <form class="update-status-form" method="POST" action="EmployeeViewTasks.php">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <select name="status" class="status-select">
                                                        <option value="Assigned" selected>Assigned</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="update-btn">
                                                        <i class="fas fa-sync-alt me-1"></i> Update
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h3>No Assigned Tasks</h3>
                                <p>You don't have any tasks in the "Assigned" status.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-content" id="in-progress-tasks" style="display: none;">
                        <?php if (count($tasks_by_status['In Progress']) > 0): ?>
                            <div class="task-list">
                                <?php foreach ($tasks_by_status['In Progress'] as $task): ?>
                                    <div class="task-card task-card-in-progress">
                                        <div class="task-header">
                                            <div>
                                            <h3 class="task-title"><?= htmlspecialchars($task['task_name']) ?></h3>
                                                <div class="task-project">Project: <?= htmlspecialchars($task['project_name']) ?></div>
                                            </div>
                                            <span class="task-status status-in-progress">In Progress</span>
                                        </div>
                                        <div class="task-content">
                                            <div class="task-description">
                                                <?= htmlspecialchars($task['task_description']) ?>
                                            </div>
                                            <div class="task-meta">
                                                <div class="task-dates">
                                                    <div class="task-date-label">Start Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['start_date']))) ?></div>
                                                </div>
                                                <div class="task-dates">
                                                    <div class="task-date-label">Due Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['end_date']))) ?></div>
                                                </div>
                                                <div class="task-assigned-by">
                                                    <div class="task-assigned-label">Assigned By:</div>
                                                    <div class="task-assigned-value">
                                                        <?= htmlspecialchars($task['assigned_by_first'] . ' ' . $task['assigned_by_last']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="task-actions">
                                                <form class="update-status-form" method="POST" action="EmployeeViewTasks.php">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <select name="status" class="status-select">
                                                        <option value="Assigned">Assigned</option>
                                                        <option value="In Progress" selected>In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="update-btn">
                                                        <i class="fas fa-sync-alt me-1"></i> Update
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-spinner"></i>
                                <h3>No In-Progress Tasks</h3>
                                <p>You don't have any tasks in the "In Progress" status.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-content" id="completed-tasks" style="display: none;">
                        <?php if (count($tasks_by_status['Completed']) > 0): ?>
                            <div class="task-list">
                                <?php foreach ($tasks_by_status['Completed'] as $task): ?>
                                    <div class="task-card task-card-completed">
                                        <div class="task-header">
                                            <div>
                                                <h3 class="task-title"><?= htmlspecialchars($task['task_name']) ?></h3>
                                                <div class="task-project">Project: <?= htmlspecialchars($task['project_name']) ?></div>
                                            </div>
                                            <span class="task-status status-completed">Completed</span>
                                        </div>
                                        <div class="task-content">
                                            <div class="task-description">
                                                <?= htmlspecialchars($task['task_description']) ?>
                                            </div>
                                            <div class="task-meta">
                                                <div class="task-dates">
                                                    <div class="task-date-label">Start Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['start_date']))) ?></div>
                                                </div>
                                                <div class="task-dates">
                                                    <div class="task-date-label">Due Date:</div>
                                                    <div class="task-date-value"><?= htmlspecialchars(date('M d, Y', strtotime($task['end_date']))) ?></div>
                                                </div>
                                                <div class="task-assigned-by">
                                                    <div class="task-assigned-label">Assigned By:</div>
                                                    <div class="task-assigned-value">
                                                        <?= htmlspecialchars($task['assigned_by_first'] . ' ' . $task['assigned_by_last']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="task-actions">
                                                <form class="update-status-form" method="POST" action="EmployeeViewTasks.php">
                                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                                    <select name="status" class="status-select">
                                                        <option value="Assigned">Assigned</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="Completed" selected>Completed</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="update-btn">
                                                        <i class="fas fa-sync-alt me-1"></i> Update
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h3>No Completed Tasks</h3>
                                <p>You don't have any tasks in the "Completed" status.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabs = document.querySelectorAll('.task-tab');
            const contents = document.querySelectorAll('.task-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    tab.classList.add('active');
                    
                    // Hide all content sections
                    contents.forEach(content => {
                        content.style.display = 'none';
                    });
                    
                    // Show the corresponding content
                    const tabId = tab.getAttribute('data-tab');
                    document.getElementById(tabId + '-tasks').style.display = 'block';
                });
            });
            
            // Auto-hide success message after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    successAlert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        successAlert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            // Calculate days remaining for each task
            const calculateDaysRemaining = () => {
                const dueDates = document.querySelectorAll('.task-date-value:nth-child(2)');
                dueDates.forEach(dateElement => {
                    const dueDate = new Date(dateElement.textContent);
                    const today = new Date();
                    
                    // Reset time part for accurate day calculation
                    dueDate.setHours(0, 0, 0, 0);
                    today.setHours(0, 0, 0, 0);
                    
                    const diffTime = dueDate - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    if (diffDays > 0) {
                        const daysLabel = document.createElement('span');
                        daysLabel.classList.add('days-remaining');
                        daysLabel.textContent = ` (${diffDays} days remaining)`;
                        
                        if (diffDays <= 3) {
                            daysLabel.style.color = '#f72585'; // Warning color for urgent tasks
                            daysLabel.style.fontWeight = '600';
                        }
                        
                        dateElement.appendChild(daysLabel);
                    } else if (diffDays === 0) {
                        const daysLabel = document.createElement('span');
                        daysLabel.classList.add('days-remaining');
                        daysLabel.textContent = ' (Due today)';
                        daysLabel.style.color = '#f72585';
                        daysLabel.style.fontWeight = '600';
                        dateElement.appendChild(daysLabel);
                    } else {
                        const daysLabel = document.createElement('span');
                        daysLabel.classList.add('days-remaining');
                        daysLabel.textContent = ' (Overdue)';
                        daysLabel.style.color = '#f72585';
                        daysLabel.style.fontWeight = '600';
                        dateElement.appendChild(daysLabel);
                    }
                });
            };
            
            calculateDaysRemaining();
        });
    </script>
</body>
</html>

<?php
$connection->close();
?>
