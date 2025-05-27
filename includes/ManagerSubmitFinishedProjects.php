<?php
session_start();
include '../db_connection.php';

// Check if user is logged in and is a Project Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $completion_date = $_POST['completion_date'];
    $completion_notes = trim($_POST['completion_notes']);
    $manager_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($project_id) || empty($completion_date) || empty($completion_notes)) {
        $error_message = "All fields are required.";
    } else {
        // Check if project exists and belongs to this manager
        $check_query = "SELECT * FROM projects WHERE project_id = ? AND manager_id = ? AND (status != 'Completed' OR decision_status = 'Rejected')";
        $stmt = $connection->prepare($check_query);
        $stmt->bind_param("ii", $project_id, $manager_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = "Invalid project or project already marked as completed and approved.";
        } else {
            // Process file upload if present
            $attachment_path = null;
            $has_attachment = false;
            
            if (isset($_FILES['project_attachment']) && $_FILES['project_attachment']['error'] == 0) {
                $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
                $file_type = $_FILES['project_attachment']['type'];
                $file_size = $_FILES['project_attachment']['size'];
                $file_name = $_FILES['project_attachment']['name'];
                
                // Validate file type and size
                if (!in_array($file_type, $allowed_types)) {
                    $error_message = "Invalid file type. Only PDF, DOC, DOCX, JPG, and PNG files are allowed.";
                } elseif ($file_size > 10485760) { // 10MB limit
                    $error_message = "File size exceeds the limit of 10MB.";
                } else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = '../uploads/project_completions/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $new_filename = 'project_' . $project_id . '_' . time() . '_' . basename($file_name);
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['project_attachment']['tmp_name'], $upload_path)) {
                        $attachment_path = 'uploads/project_completions/' . $new_filename;
                        $has_attachment = true;
                    } else {
                        $error_message = "Failed to upload file. Please try again.";
                    }
                }
            }
            
            if (empty($error_message)) {
                // Insert into completed_projects table
                if ($has_attachment) {
                    $insert_query = "INSERT INTO completed_projects (project_id, manager_id, completion_date, completion_notes, submission_date, status, attachment_path) 
                                    VALUES (?, ?, ?, ?, NOW(), 'Pending', ?)";
                    $stmt = $connection->prepare($insert_query);
                    $stmt->bind_param("iisss", $project_id, $manager_id, $completion_date, $completion_notes, $attachment_path);
                } else {
                    $insert_query = "INSERT INTO completed_projects (project_id, manager_id, completion_date, completion_notes, submission_date, status) 
                                    VALUES (?, ?, ?, ?, NOW(), 'Pending')";
                    $stmt = $connection->prepare($insert_query);
                    $stmt->bind_param("iiss", $project_id, $manager_id, $completion_date, $completion_notes);
                }
                
                if ($stmt->execute()) {
                    // Update project status
                    $update_query = "UPDATE projects SET status = 'Completed' WHERE project_id = ?";
                    $stmt = $connection->prepare($update_query);
                    $stmt->bind_param("i", $project_id);
                    $stmt->execute();
                    
                    $success_message = "Project successfully marked as completed and submitted for review.";
                } else {
                    $error_message = "Error submitting project: " . $connection->error;
                }
            }
        }
    }
}


// Get all projects assigned to this manager that are completed
$manager_id = $_SESSION['user_id'];
$projects_query = "SELECT p.*, cp.status as submission_status 
                  FROM projects p 
                  LEFT JOIN completed_projects cp ON p.project_id = cp.project_id AND cp.status = 'Rejected'
                  WHERE p.manager_id = ? AND p.status = 'Completed' 
                  ORDER BY p.start_date DESC";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Get all projects this manager has already submitted
$submitted_query = "SELECT cp.*, p.project_name, p.end_date 
                   FROM completed_projects cp 
                   JOIN projects p ON cp.project_id = p.project_id 
                   WHERE cp.manager_id = ? 
                   ORDER BY cp.submission_date DESC";
$stmt = $connection->prepare($submitted_query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$submitted_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Finished Projects | Salale University CMS</title>
    
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
        
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-upload-input {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 40px;
            margin: 0;
            padding: 0;
            display: block;
            cursor: pointer;
            opacity: 0;
        }
        
        .file-upload-text {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 40px;
            z-index: 0;
            padding: 0.375rem 0.75rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
        }
        
        .file-upload-text i {
            margin-right: 8px;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            color: var(--secondary-color);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .attachment-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .attachment-link i {
            margin-right: 5px;
        }
        
        .rejected-project {
            background-color: rgba(231, 76, 60, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center mb-4">Submit Finished Projects</h1>
                <div class="d-flex justify-content-between mb-3">
                    <a href="ManagerDashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
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
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Submit a Completed Project</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($projects_result->num_rows > 0): ?>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Select Project</label>
                                    <select class="form-select" id="project_id" name="project_id" required>
                                        <option value="">-- Select a project --</option>
                                        <?php while ($project = $projects_result->fetch_assoc()): 
                                            $isRejected = isset($project['submission_status']) && $project['submission_status'] === 'Rejected';
                                            $statusText = $isRejected ? " (Rejected - Resubmit)" : " (Status: " . $project['status'] . ")";
                                            $optionClass = $isRejected ? "rejected-option" : "";
                                        ?>
                                            <option value="<?php echo $project['project_id']; ?>" 
                                                    data-end-date="<?php echo $project['end_date']; ?>"
                                                    class="<?php echo $optionClass; ?>">
                                                <?php echo htmlspecialchars($project['project_name']); ?> 
                                                <?php echo $statusText; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="completion_date" class="form-label">Completion Date</label>
                                    <input type="date" class="form-control" id="completion_date" name="completion_date" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="completion_notes" class="form-label">Completion Notes</label>
                                    <textarea class="form-control" id="completion_notes" name="completion_notes" rows="4" required 
                                        placeholder="Provide details about the project completion, challenges faced, and final outcomes..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="project_attachment" class="form-label">Attachment (Optional)</label>
                                    <div class="file-upload-wrapper">
                                        <input type="file" class="file-upload-input" id="project_attachment" name="project_attachment">
                                        <div class="file-upload-text">
                                            <i class="fas fa-file-upload"></i> <span id="file-name">Choose a file</span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Accepted file types: PDF, DOC, DOCX, JPG, PNG (Max size: 10MB)
                                    </small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Completed Project
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You don't have any active projects to mark as completed.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Previously Submitted Projects</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($submitted_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Completion Date</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Attachment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($submission = $submitted_result->fetch_assoc()): 
                                            $statusClass = '';
                                            $statusIcon = '';
                                            
                                            switch ($submission['status']) {
                                                case 'Approved':
                                                    $statusClass = 'bg-success';
                                                    $statusIcon = 'fa-check-circle';
                                                    break;
                                                case 'Rejected':
                                                    $statusClass = 'bg-danger';
                                                    $statusIcon = 'fa-times-circle';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-warning';
                                                    $statusIcon = 'fa-clock';
                                                    break;
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($submission['project_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($submission['completion_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($submission['submission_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $statusClass; ?> status-badge">
                                                        <i class="fas <?php echo $statusIcon; ?>"></i> 
                                                        <?php echo $submission['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($submission['attachment_path'])): ?>
                                                        <a href="../<?php echo $submission['attachment_path']; ?>" class="attachment-link" target="_blank">
                                                            <i class="fas fa-file-download"></i> Download
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No attachment</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You haven't submitted any completed projects yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload display
        document.getElementById('project_attachment').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose a file';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // Auto-fill completion date from project end date
        document.getElementById('project_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const endDate = selectedOption.getAttribute('data-end-date');
                if (endDate) {
                    document.getElementById('completion_date').value = endDate;
                }
            }
        });
        
        // Style rejected options in the dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const selectElement = document.getElementById('project_id');
            if (selectElement) {
                Array.from(selectElement.options).forEach(option => {
                    if (option.classList.contains('rejected-option')) {
                        option.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
                        option.style.fontWeight = 'bold';
                    }
                });
            }
        });
    </script>
</body>
</html>

