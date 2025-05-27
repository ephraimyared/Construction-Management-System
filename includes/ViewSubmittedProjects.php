<?php
session_start();
include '../db_connection.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['submission_id'])) {
    $submission_id = (int)$_POST['submission_id'];
    $action = $_POST['action'];
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'Approved' : 'Rejected';
        
        // Update the submission status
        $update_query = "UPDATE completed_projects SET status = ?, admin_id = ?, admin_notes = ?, review_date = NOW() WHERE id = ?";
        $stmt = $connection->prepare($update_query);
        $admin_id = $_SESSION['user_id'];
        $stmt->bind_param("sisi", $status, $admin_id, $admin_notes, $submission_id);
        
        if ($stmt->execute()) {
            $success_message = "Project submission has been " . strtolower($status) . " successfully.";
        } else {
            $error_message = "Error updating submission: " . $connection->error;
        }
    }
}

// Get all submitted projects
$query = "SELECT cp.*, p.project_name, p.description, p.budget, p.start_date, 
          CONCAT(u1.FirstName, ' ', u1.LastName) AS ManagerName,
          CONCAT(u2.FirstName, ' ', u2.LastName) AS AdminName
          FROM completed_projects cp
          JOIN projects p ON cp.project_id = p.project_id
          JOIN users u1 ON cp.manager_id = u1.UserID
          LEFT JOIN users u2 ON cp.admin_id = u2.UserID
          ORDER BY 
            CASE 
                WHEN cp.status = 'Pending' THEN 1
                WHEN cp.status = 'Approved' THEN 2
                WHEN cp.status = 'Rejected' THEN 3
            END,
            cp.submission_date DESC";
            
$result = $connection->query($query);
if (!$result) {
    die("Database error: " . $connection->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submitted Projects | Salale University CMS</title>
    
    <!-- Latest Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (for icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .table th {
            background-color: var(--light-color);
        }
        
        .project-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .review-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            color: var(--secondary-color);
            text-decoration: none;
            transition: color 0.2s;
            margin-top: 10px;
        }
        
        .attachment-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .attachment-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center mb-4">View Submitted Projects</h1>
                <div class="d-flex justify-content-between mb-3">
                    <a href="AdminManageProjects.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Submitted Projects</h5>
            </div>
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="accordion" id="submittedProjectsAccordion">
                        <?php $counter = 1; while ($project = $result->fetch_assoc()): ?>
                            <div class="accordion-item mb-3 border">
                                <h2 class="accordion-header" id="heading<?php echo $counter; ?>">
                                    <button class="accordion-button <?php echo ($project['status'] == 'Pending') ? '' : 'collapsed'; ?>" 
                                            type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?php echo $counter; ?>" 
                                            aria-expanded="<?php echo ($project['status'] == 'Pending') ? 'true' : 'false'; ?>" 
                                            aria-controls="collapse<?php echo $counter; ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <span>
                                                <strong><?php echo htmlspecialchars($project['project_name']); ?></strong> - 
                                                Submitted by <?php echo htmlspecialchars($project['ManagerName']); ?>
                                            </span>
                                            <span>
                                                <?php if ($project['status'] == 'Approved'): ?>
                                                    <span class="badge bg-success status-badge">Approved</span>
                                                <?php elseif ($project['status'] == 'Rejected'): ?>
                                                    <span class="badge bg-danger status-badge">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark status-badge">Pending Review</span>
                                                <?php endif; ?>
                                                <small class="ms-2 text-muted">
                                                    <?php echo date('M d, Y', strtotime($project['submission_date'])); ?>
                                                </small>
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $counter; ?>" 
                                     class="accordion-collapse collapse <?php echo ($project['status'] == 'Pending') ? 'show' : ''; ?>" 
                                     aria-labelledby="heading<?php echo $counter; ?>" 
                                     data-bs-parent="#submittedProjectsAccordion">
                                    <div class="accordion-body">
                                        <div class="project-details">
                                            <h5>Project Details</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Project Description:</strong> <?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                                    <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($project['start_date'])); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Completion Date:</strong> <?php echo date('M d, Y', strtotime($project['completion_date'])); ?></p>
                                                    <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($project['ManagerName']); ?></p>
                                                    <p><strong>Submission Date:</strong> <?php echo date('M d, Y h:i A', strtotime($project['submission_date'])); ?></p>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <h6>Completion Notes:</h6>
                                                <div class="p-3 bg-white border rounded">
                                                    <?php echo nl2br(htmlspecialchars($project['completion_notes'])); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($project['attachment_path'])): ?>
                                                <div class="mt-3">
                                                    <h6>Project Attachment:</h6>
                                                    <a href="../<?php echo $project['attachment_path']; ?>" class="attachment-link" target="_blank">
                                                        <i class="fas fa-file-download"></i> Download Attachment
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($project['status'] != 'Pending'): ?>
                                                <div class="mt-3">
                                                    <h6>Admin Review:</h6>
                                                    <div class="p-3 bg-white border rounded">
                                                        <p><strong>Reviewed By:</strong> <?php echo htmlspecialchars($project['AdminName']); ?></p>
                                                        <p><strong>Review Date:</strong> <?php echo date('M d, Y h:i A', strtotime($project['review_date'])); ?></p>
                                                        <p><strong>Admin Notes:</strong></p>
                                                        <div class="p-2 bg-light border rounded">
                                                            <?php echo !empty($project['admin_notes']) ? nl2br(htmlspecialchars($project['admin_notes'])) : 'No notes provided.'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($project['status'] == 'Pending'): ?>
                                            <div class="action-buttons">
                                                <button class="btn btn-success" onclick="showReviewForm(<?php echo $counter; ?>, 'approve')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="btn btn-danger" onclick="showReviewForm(<?php echo $counter; ?>, 'reject')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                            
                                            <div id="reviewForm<?php echo $counter; ?>" class="review-form">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="submission_id" value="<?php echo $project['id']; ?>">
                                                    <input type="hidden" name="action" id="action<?php echo $counter; ?>" value="">
                                                    
                                                    <div class="mb-3">
                                                        <label for="admin_notes<?php echo $counter; ?>" class="form-label">Review Notes:</label>
                                                        <textarea class="form-control" id="admin_notes<?php echo $counter; ?>" name="admin_notes" rows="3" 
                                                            placeholder="Provide your feedback about this project submission..."></textarea>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" class="btn btn-secondary me-2" onclick="hideReviewForm(<?php echo $counter; ?>)">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="btn btn-primary">
                                                            Submit Review
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php $counter++; endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No submitted projects found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
        <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showReviewForm(id, action) {
            // Hide all review forms first
            document.querySelectorAll('.review-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show the selected form
            const form = document.getElementById('reviewForm' + id);
            form.style.display = 'block';
            
            // Set the action
            document.getElementById('action' + id).value = action;
            
            // Update the form title based on action
            const actionText = (action === 'approve') ? 'Approve' : 'Reject';
            const actionClass = (action === 'approve') ? 'text-success' : 'text-danger';
            
            // Scroll to the form
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function hideReviewForm(id) {
            document.getElementById('reviewForm' + id).style.display = 'none';
        }
    </script>
</body>
</html>
 

