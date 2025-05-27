<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Contractor') {
    header("Location: unauthorized.php");
    exit();
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ViewTasksAndSchedule.php");
    exit();
}

$project_id = $_GET['id'];
$contractor_id = $_SESSION['user_id'];

// Verify that the contractor has access to this project
$access_check_query = "
    SELECT COUNT(*) as has_access
    FROM project_assignments 
    WHERE project_id = ? AND contractor_id = ?
";
$stmt = $connection->prepare($access_check_query);
$stmt->bind_param("ii", $project_id, $contractor_id);
$stmt->execute();
$access_result = $stmt->get_result()->fetch_assoc();

if ($access_result['has_access'] == 0) {
    header("Location: unauthorized.php");
    exit();
}

// Get project details
$project_query = "
    SELECT 
        p.*,
        CONCAT(m.FirstName, ' ', m.LastName) AS manager_name,
        m.Email AS manager_email,
        m.Phone AS manager_phone
    FROM 
        projects p
    JOIN users m ON p.manager_id = m.UserID
    WHERE 
        p.project_id = ?
";
$stmt = $connection->prepare($project_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// Get all tasks assigned to this contractor for this project
$tasks_query = "
    SELECT 
        pa.*,
        CONCAT(u.FirstName, ' ', u.LastName) AS assigned_by
    FROM 
        project_assignments pa
    JOIN users u ON pa.user_id = u.UserID
    WHERE 
        pa.project_id = ? AND pa.contractor_id = ?
    ORDER BY 
        pa.start_date ASC
";
$stmt = $connection->prepare($tasks_query);
$stmt->bind_param("ii", $project_id, $contractor_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
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
        
        .card {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 2rem;
            border: none;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-bottom: none;
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
        
        .info-label {
            font-weight: 600;
            color: #4e73df;
        }
        
        .info-value {
            background-color: #f8f9fc;
            padding: 0.75rem;
            border-radius: 0.35rem;
            border: 1px solid #e3e6f0;
            margin-bottom: 1rem;
        }
        
        .project-description {
            background-color: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
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
        
        .contact-card {
            background-color: #f8f9fc;
            border-radius: 0.35rem;
            border: 1px solid #e3e6f0;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .contact-icon {
            color: #4e73df;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: #e3e6f0;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-marker {
            position: absolute;
            left: -2rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #4e73df;
            border: 2px solid white;
        }
        
        .timeline-content {
            background-color: white;
            padding: 1rem;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 0.5rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .timeline-date {
            color: #858796;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <a href="ViewTasksAndSchedule.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i>Back to Tasks
    </a>
    
    <div class="container py-5">
        <h2 class="text-center mb-4">Project Details</h2>
        
        <?php if ($project): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?= htmlspecialchars($project['project_name']) ?></h4>
                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $project['status'])) ?>">
                        <?= htmlspecialchars($project['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-label">Project ID</div>
                            <div class="info-value"><?= htmlspecialchars($project['project_id']) ?></div>
                            
                            <div class="info-label">Start Date</div>
                            <div class="info-value"><?= date('F d, Y', strtotime($project['start_date'])) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">End Date</div>
                            <div class="info-value"><?= date('F d, Y', strtotime($project['end_date'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-label">Project Description</div>
                    <div class="project-description">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Project Manager</h5>
                            <div class="contact-card">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user-tie contact-icon"></i>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($project['manager_name']) ?></div>
                                        <div class="text-muted">Project Manager</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-envelope contact-icon"></i>
                                    <div>
                                        <div class="fw-bold">Email</div>
                                        <div><?= htmlspecialchars($project['manager_email']) ?></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone contact-icon"></i>
                                    <div>
                                        <div class="fw-bold">Phone</div>
                                        <div><?= htmlspecialchars($project['manager_phone']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Your Tasks for This Project</h4>
                </div>
                <div class="card-body">
                    <?php if ($tasks_result->num_rows > 0): ?>
                        <div class="timeline">
                            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="timeline-date">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?= date('M d, Y', strtotime($task['start_date'])) ?> - 
                                                <?= date('M d, Y', strtotime($task['end_date'])) ?>
                                            </div>
                                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $task['status'])) ?>">
                                                <?= htmlspecialchars($task['status']) ?>
                                            </span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Task Description:</strong>
                                            <div class="mt-2">
                                                <?= nl2br(htmlspecialchars($task['description'])) ?>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i> Assigned by: <?= htmlspecialchars($task['assigned_by']) ?>
                                                </small>
                                            </div>
                                            <?php if (!empty($task['attachment_path'])): ?>
                                                <a href="<?= htmlspecialchars($task['attachment_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-paperclip me-1"></i> View Attachment
                                                </a>
                                            <?php endif; ?>
                                            
                                            
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            No specific tasks assigned to you for this project yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Project not found or you don't have access to view this project.
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5.3 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
<?php $connection->close(); ?>