<?php
session_start();
include '../db_connection.php';

// Ensure user is a Project Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";

// Calculate minimum start date (30 days from today)
$min_start_date = date('Y-m-d', strtotime('+30 days'));

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $manager_id = $_SESSION['user_id'];
    $project_name = trim($_POST['project_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate all fields are filled
    if ($project_name && $description && $start_date && $end_date) {
        // Validate start date is at least 30 days from today
        if (strtotime($start_date) < strtotime($min_start_date)) {
            $error = "Start date must be at least 30 days from today.";
        } 
        // Validate end date is after start date
        else if ($end_date <= $start_date) {
            $error = "End date must be after the start date.";
        } 
        // Validate there are at least 180 days between start and end date
        else if (strtotime($end_date) - strtotime($start_date) < 180 * 24 * 60 * 60) {
            $error = "There must be at least 180 days between start and end dates.";
        } 
        else {
            // Handle file upload
            $attachment_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
                $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['attachment']['type'], $allowed_types)) {
                    $error = "Only PDF and Word documents are allowed.";
                } else if ($_FILES['attachment']['size'] > $max_size) {
                    $error = "File size must be less than 5MB.";
                } else {
                    $upload_dir = '../uploads/project_attachments/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                    $file_name = 'project_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $attachment_path = $upload_dir . $file_name;
                    
                    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment_path)) {
                        $error = "Failed to upload file. Please try again.";
                    } else {
                        // Store relative path in database
                        $attachment_path = 'uploads/project_attachments/' . $file_name;
                    }
                }
            }
            
            if (empty($error)) {
                // Set initial status as 'Planning'
                $stmt = $connection->prepare("INSERT INTO projects (project_name, description, start_date, end_date, manager_id, status, attachment) VALUES (?, ?, ?, ?, ?, 'Planning', ?)");
                $stmt->bind_param("ssssis", $project_name, $description, $start_date, $end_date, $manager_id, $attachment_path);
                if ($stmt->execute()) {
                    $success = "Project created successfully with status 'Planning'.";
                    // Reset form after successful submission
                    $project_name = $description = $start_date = $end_date = "";
                } else {
                    $error = "Error creating project: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Project</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --transition: all 0.3s ease;
            --border-radius: 0.5rem;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        body {
            background: linear-gradient(to right, #f0f4f8, #d9e2ec);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            max-width: 800px;
            background: white;
            padding: 40px;
            margin: 30px auto;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
        }

        h2 {
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background-color: var(--secondary);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            padding: 12px 15px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .alert {
            margin-top: 15px;
            border-radius: var(--border-radius);
            padding: 15px;
        }
        
        .back-btn {
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .back-btn:hover {
            color: var(--primary-dark);
            transform: translateX(-3px);
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .form-section {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary);
        }
        
        .form-section-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-upload-input {
            position: relative;
            z-index: 2;
            width: 100%;
            height: calc(1.5em + 0.75rem + 2px);
            margin: 0;
            opacity: 0;
        }
        
        .file-upload-text {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1;
            height: calc(1.5em + 0.75rem + 2px);
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
        
        .custom-file-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 90px;
            flex: 1;
        }
        
        .custom-file-button {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            z-index: 3;
            display: block;
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
            line-height: 1.5;
            color: #fff;
            background-color: var(--primary);
            border-left: inherit;
            border-radius: 0 0.25rem 0.25rem 0;
        }
        
        .date-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .required-field::after {
            content: "*";
            color: var(--danger);
            margin-left: 4px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="ManagerDashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2><i class="fas fa-plus-circle"></i> Create New Project</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" id="createProjectForm">
        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-info-circle me-2"></i>Basic Information</div>
            <div class="mb-3">
                <label for="project_name" class="form-label required-field">Project Name</label>
                <input type="text" class="form-control" name="project_name" id="project_name" 
                       value="<?php echo isset($project_name) ? htmlspecialchars($project_name) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label required-field">Project Description</label>
                <textarea class="form-control" name="description" id="description" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-calendar-alt me-2"></i>Project Timeline</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="start_date" class="form-label required-field">Start Date</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" 
                               min="<?php echo $min_start_date; ?>" 
                               value="<?php echo isset($start_date) ? htmlspecialchars($start_date) : ''; ?>" required>
                        <div class="date-info">
                            <i class="fas fa-info-circle"></i> Must be at least 30 days from today
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="end_date" class="form-label required-field">End Date</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" 
                               value="<?php echo isset($end_date) ? htmlspecialchars($end_date) : ''; ?>" required>
                        <div class="date-info">
                            <i class="fas fa-info-circle"></i> Must be at least 180 days after start date
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-section">
            <div class="form-section-title"><i class="fas fa-paperclip me-2"></i>Project Attachments</div>
            <div class="mb-3">
                <label for="attachment" class="form-label">Project Document (Optional)</label>
                <div class="file-upload-wrapper">
                    <input type="file" class="file-upload-input" name="attachment" id="attachment" accept=".pdf,.doc,.docx">
                    <div class="file-upload-text">
                        <span class="custom-file-label" id="file-chosen">No file chosen</span>
                        <span class="custom-file-button">Browse</span>
                    </div>
                </div>
                            <div class="date-info">
                    <i class="fas fa-info-circle"></i> Accepted formats: PDF, DOC, DOCX (Max size: 5MB)
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Create Project
            </button>
            <a href="ManageProjects.php" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </form>
</div>

<script>
// Update file input label when file is selected
document.getElementById('attachment').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
    document.getElementById('file-chosen').textContent = fileName;
});

// Form validation
document.getElementById('createProjectForm').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);
    const minStartDate = new Date('<?php echo $min_start_date; ?>');
    
    // Check if start date is at least 30 days from today
    if (startDate < minStartDate) {
        e.preventDefault();
        alert('Start date must be at least 30 days from today.');
        return;
    }
    
    // Check if end date is after start date
    if (endDate <= startDate) {
        e.preventDefault();
        alert('End date must be after the start date.');
        return;
    }
    
    // Check if there are at least 180 days between start and end date
    const dayDifference = (endDate - startDate) / (1000 * 60 * 60 * 24);
    if (dayDifference < 180) {
        e.preventDefault();
        alert('There must be at least 180 days between start and end dates.');
        return;
    }
    
    // Validate file size if a file is selected
    const fileInput = document.getElementById('attachment');
    if (fileInput.files.length > 0) {
        const fileSize = fileInput.files[0].size; // in bytes
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (fileSize > maxSize) {
            e.preventDefault();
            alert('File size must be less than 5MB.');
            return;
        }
        
        // Validate file type
        const fileName = fileInput.files[0].name;
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        if (!['pdf', 'doc', 'docx'].includes(fileExt)) {
            e.preventDefault();
            alert('Only PDF and Word documents are allowed.');
            return;
        }
    }
});

// Calculate and set minimum end date when start date changes
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    if (!isNaN(startDate.getTime())) {
        // Add 180 days to start date
        const minEndDate = new Date(startDate);
        minEndDate.setDate(startDate.getDate() + 180);
        
        // Format date as YYYY-MM-DD for input
        const minEndDateStr = minEndDate.toISOString().split('T')[0];
        
        // Set minimum end date
        document.getElementById('end_date').min = minEndDateStr;
        
        // If current end date is less than minimum, update it
        const endDateInput = document.getElementById('end_date');
        if (endDateInput.value && new Date(endDateInput.value) < minEndDate) {
            endDateInput.value = minEndDateStr;
        }
    }
});
</script>

</body>
</html>

<?php $connection->close(); ?>

                
