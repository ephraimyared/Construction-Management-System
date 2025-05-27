<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in and is a Consultant
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Consultant') {
    header("Location: login.php");
    exit();
}

$consultant_id = $_SESSION['user_id'];
$message = "";

// Create upload directory if it doesn't exist
$upload_dir = "../uploads/consultant_reports/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch projects assigned to this consultant
$stmt = $connection->prepare("
    SELECT DISTINCT p.project_id, p.project_name
    FROM projects p
    JOIN project_assignments pa ON p.project_id = pa.project_id
    WHERE pa.contractor_id = ? AND pa.role_in_project = 'Assigned Consultant'
");
$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$projects_result = $stmt->get_result();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug information
    error_log("Form submitted: " . print_r($_POST, true));
    
    $project_id = $_POST['project_id'];
    $report_type = $_POST['report_type'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $file_path = null;

    // Check if all required fields are filled
    if (!empty($project_id) && !empty($report_type) && !empty($title) && !empty($content)) {
        
        // Handle file upload if a file was submitted
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($file_info, $_FILES['report_file']['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $message = '<div class="alert alert-danger">❌ Invalid file type. Allowed types: PDF, Word, Excel, JPEG, PNG.</div>';
            } elseif ($_FILES['report_file']['size'] > $max_size) {
                $message = '<div class="alert alert-danger">❌ File size exceeds the limit of 10MB.</div>';
            } else {
                // Generate unique filename
                $file_name = time() . '_' . $consultant_id . '_' . basename($_FILES['report_file']['name']);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                    // File uploaded successfully, save relative path to database
                    $file_path = 'uploads/consultant_reports/' . $file_name;
                } else {
                    $message = '<div class="alert alert-danger">❌ Error uploading file: ' . error_get_last()['message'] . '</div>';
                    $file_path = null;
                }
            }
        }
        
        // If no file upload error, proceed with report submission
        if (empty($message)) {
            try {
                // Fix: Use current timestamp for created_at field
                $current_time = date('Y-m-d H:i:s');
                
                $stmt = $connection->prepare("INSERT INTO reports (project_id, created_by, report_type, title, content, file_attachment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssss", $project_id, $consultant_id, $report_type, $title, $content, $file_path, $current_time);

                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">✅ Report submitted successfully to Project Manager!</div>';
                    // Clear form data on successful submission
                    $_POST = array();
                } else {
                    $message = '<div class="alert alert-danger">❌ Error submitting report: ' . $stmt->error . '</div>';
                    error_log("Database error: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">❌ Exception occurred: ' . $e->getMessage() . '</div>';
                error_log("Exception: " . $e->getMessage());
            }
        }
    } else {
        $message = '<div class="alert alert-warning">⚠️ Please fill in all required fields.</div>';
        // Log which fields are missing
        $missing = [];
        if (empty($project_id)) $missing[] = 'project_id';
        if (empty($report_type)) $missing[] = 'report_type';
        if (empty($title)) $missing[] = 'title';
        if (empty($content)) $missing[] = 'content';
        error_log("Missing fields: " . implode(', ', $missing));
    }
}

// Fetch user info
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Report | Consultant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & FontAwesome -->
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
            --body-bg: #f5f7fb;
            --card-border-radius: 0.75rem;
            --border-radius-sm: 0.375rem;
            --box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--body-bg);
            color: var(--dark-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
            color: var(--accent-color);
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
            color: var(--accent-color);
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
            border-left-color: var(--accent-color);
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
            background: rgba(255, 255, 255, 0.2);
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
            color: #e74c3c;
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: var(--transition);
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .page-header {
            background: white;
            border-radius: var(--card-border-radius);
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .breadcrumb {
            margin: 0;
            padding: 0;
            background: transparent;
        }

        .breadcrumb-item a {
            color: var(--gray-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }

        .breadcrumb-item.active {
            color: var(--primary-color);
        }

        .alert {
            border-radius: var(--card-border-radius);
            margin-bottom: 30px;
        }

        .report-form-container {
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            padding: 12px 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: var(--gray-color);
            border: 1px solid var(--gray-color);
        }

        .btn-secondary:hover {
            background: rgba(108, 117, 125, 0.1);
            color: var(--dark-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-text {
            color: var(--gray-color);
            font-size: 0.85rem;
            margin-top: 5px;
        }

        /* File upload styling */
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
            padding: 10px 15px;
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(0, 0, 0, 0.1);
            background-color: white;
            pointer-events: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-upload-button {
            position: absolute;
            top: 0;
            right: 0;
            height: 40px;
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 var(--border-radius-sm) var(--border-radius-sm) 0;
            pointer-events: none;
        }

        /* Responsive Design */
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
                gap: 10px;
            }
            
            .report-form-container {
                padding: 20px;
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
            <a href="ConsultantDashboard.php" class="sidebar-logo">
                 <span class="logo-text"> <img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="ConsultantDashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="ConsultantPrepareReport.php" class="active">
                    <i class="fas fa-file-alt"></i>
                    <span>Submit Reports</span>
                </a>
            </li>
       
            <li>
                <a href="ConsultantViewAssignedTasks.php">
                    <i class="fas fa-project-diagram"></i>
                    <span>Track Projects status</span>
                </a>
            </li>
            <li>
                <a href="ConsultantProfile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'C'; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Consultant'; ?></div>
                    <div class="user-role">Consultant</div>
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
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Submit Report</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="ConsultantDashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Submit Report</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <!-- Report Form -->
        <div class="report-form-container">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="project_id" class="form-label">Select Project</label>
                    <select id="project_id" name="project_id" class="form-select" required>
                        <option value=""required>-- Select Project --</option>
                        <?php while ($row = $projects_result->fetch_assoc()): ?>
                            <option value="<?= $row['project_id'] ?>"><?= htmlspecialchars($row['project_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <div class="form-text">Choose the project you want to submit a report for</div>
                </div>

                <div class="form-group">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select id="report_type" name="report_type" class="form-select" required>
                        <option value="">-- Select Report Type --</option>
                        <option value="Progress">Progress Report</option>
                        <option value="Issue">Issue Report</option>
                        <option value="Financial">Financial Report</option>
                        <option value="Completion">Completion Report</option>
                        <option value="Completion"> Other</option>
                    </select>
                    <div class="form-text">Select the type of report you are submitting</div>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Report Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Enter a descriptive title for your report" required>
                </div>

                <div class="form-group">
                    <label for="content" class="form-label">Report Content</label>
                    <textarea id="content" name="content" class="form-control" rows="6" required placeholder="Provide detailed information about the project status, issues, or any other relevant information..."></textarea>
                    <div class="form-text">Be specific and include all relevant details in your report</div>
                </div>

                <div class="form-group">
                    <label for="report_file" class="form-label" required>Attach File</label>
                    <div class="file-upload-wrapper">
                        <input type="file" id="report_file" name="report_file" class="file-upload-input">
                        <div class="file-upload-text">Choose a file...</div>
                        <span class="file-upload-button">Browse</span>
                    </div>
                    <div class="form-text">
                        Accepted file types: PDF, Word, Excel, JPEG, PNG (Max size: 10MB)
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i> Submit Report
                    </button>
                    <a href="ConsultantDashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        
        // File upload UI enhancement
        document.getElementById('report_file').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Choose a file...';
            document.querySelector('.file-upload-text').textContent = fileName;
        });
    </script>
</body>
</html>

<?php $connection->close(); ?>