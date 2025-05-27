<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Contractor') {
    header("Location: unauthorized.php");
    exit();
}

$contractor_id = $_SESSION['user_id'];

$query = "
    SELECT 
        p.project_id,
        p.project_name,
        pa.assignment_id,
        pa.description AS task_description,
        pa.start_date,
        pa.end_date,
        pa.status,
        pa.attachment_path,
        pa.assigned_date AS created_at,
        CONCAT(u.FirstName, ' ', u.LastName) AS assigned_by
    FROM 
        project_assignments pa
    JOIN projects p ON pa.project_id = p.project_id
    JOIN users u ON pa.user_id = u.UserID
    WHERE 
        pa.contractor_id = ?
    ORDER BY 
        pa.start_date ASC
";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $contractor_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assigned Tasks</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-dark: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            color: var(--text-dark);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .task-container {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .task-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-body {
            padding: 1.5rem;
        }
        
        .task-footer {
            background-color: #f8f9fc;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e3e6f0;
        }
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        
        .status-pending, .status-assigned {
            background-color: #f6c23e;
            color: #fff;
        }
        
        .status-completed {
            background-color: #1cc88a;
            color: #fff;
        }
        
        .status-in-progress {
            background-color: #36b9cc;
            color: #fff;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background-color: var(--accent-color);
            transform: translateX(-3px);
            color: white;
        }
        
        h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem !important;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .task-info-item {
            margin-bottom: 1rem;
        }
        .task-info-label {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.25rem;
        }
        
        .task-info-value {
            background-color: #f8f9fc;
            padding: 0.75rem;
            border-radius: 0.35rem;
            border: 1px solid #e3e6f0;
        }
        
        .task-description {
            background-color: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }
        
        .date-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #4e73df;
            color: white;
            border-radius: 0.35rem;
            margin-right: 0.5rem;
            font-size: 0.875rem;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            color: #5a5c69;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .attachment-link:hover {
            background-color: #eaecf4;
            color: #3a3b45;
        }
        
        .attachment-icon {
            margin-right: 0.5rem;
            color: #4e73df;
        }
        
        .no-tasks {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .no-tasks-icon {
            font-size: 3rem;
            color: #d1d3e2;
            margin-bottom: 1rem;
        }
        
        .task-dates {
            display: flex;
            margin-bottom: 1.5rem;
        }
        
        .task-assigned-by {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .assigned-by-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background-color: #4e73df;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <a href="ContractorDashboard.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
    
    <div class="container py-5">
        <h2 class="text-center mb-4">My Assigned Tasks & Schedule</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($task = $result->fetch_assoc()): ?>
                <div class="task-container">
                    <div class="task-header">
                        <h4 class="mb-0"><?= htmlspecialchars($task['project_name']) ?></h4>
                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $task['status'])) ?>">
                            <?= htmlspecialchars($task['status']) ?>
                        </span>
                    </div>
                    
                    <div class="task-body">
                        <div class="task-assigned-by">
                            <div class="assigned-by-avatar">
                                <?= strtoupper(substr($task['assigned_by'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="text-muted">Assigned by</div>
                                <div class="fw-bold"><?= htmlspecialchars($task['assigned_by']) ?></div>
                            </div>
                        </div>
                        
                        <div class="task-dates">
                            <div class="date-badge">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Start: <?= date('M d, Y', strtotime($task['start_date'])) ?>
                            </div>
                            <div class="date-badge">
                                <i class="fas fa-calendar-check me-2"></i>
                                End: <?= date('M d, Y', strtotime($task['end_date'])) ?>
                            </div>
                        </div>
                        
                        <div class="task-info-item">
                            <div class="task-info-label">Task Description</div>
                            <div class="task-description">
                                <?= nl2br(htmlspecialchars($task['task_description'])) ?>
                            </div>
                        </div>
                        
                        <div class="task-info-item">
                            <div class="task-info-label">Assignment Date</div>
                            <div class="task-info-value">
                                <?= date('F d, Y \a\t h:i A', strtotime($task['created_at'])) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($task['attachment_path'])): ?>
                            <div class="task-info-item">
                                <div class="task-info-label">Attachment</div>
                                <div class="task-info-value">
                                    <a href="<?= htmlspecialchars($task['attachment_path']) ?>" class="attachment-link" target="_blank">
                                        <i class="fas fa-paperclip attachment-icon"></i>
                                        View Attached Document
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-footer">
                        <div class="d-flex justify-content-between">
                            <a href="ViewProjectDetails.php?id=<?= $task['project_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-project-diagram me-2"></i>View Details
                            </a>
                            
                            
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="task-container">
                <div class="no-tasks">
                    <div class="no-tasks-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h4>No tasks assigned to you yet</h4>
                    <p class="text-muted">When a project manager assigns you a task, it will appear here.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5.3 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
<?php $connection->close(); ?>
