<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in and is a Contractor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Contractor') {
    header("Location: login.php");
    exit();
}

$contractor_id = $_SESSION['user_id'];
$message = "";

// Create upload directory if it doesn't exist
$upload_dir = "../uploads/contractor_reports/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch projects assigned to this contractor
$stmt = $connection->prepare("
    SELECT DISTINCT p.project_id, p.project_name
    FROM projects p
    JOIN project_assignments pa ON p.project_id = pa.project_id
    WHERE pa.contractor_id = ?
");
$stmt->bind_param("i", $contractor_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Fetch previously submitted reports by this contractor
$stmt = $connection->prepare("
    SELECT r.*, p.project_name 
    FROM reports r
    JOIN projects p ON r.project_id = p.project_id
    WHERE r.created_by = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $contractor_id);
$stmt->execute();
$submitted_reports = $stmt->get_result();

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
                $file_name = time() . '_' . $contractor_id . '_' . basename($_FILES['report_file']['name']);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                    // File uploaded successfully, save relative path to database
                    $file_path = 'uploads/contractor_reports/' . $file_name;
                } else {
                    $message = '<div class="alert alert-danger">❌ Error uploading file: ' . error_get_last()['message'] . '</div>';
                    $file_path = null;
                }
            }
        }
        
        // If no file upload error, proceed with report submission
        if (empty($message)) {
            try {
                // Use current timestamp for created_at field
                $current_time = date('Y-m-d H:i:s');
                
                $stmt = $connection->prepare("INSERT INTO reports (project_id, created_by, report_type, title, content, file_attachment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssss", $project_id, $contractor_id, $report_type, $title, $content, $file_path, $current_time);

                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">✅ Report submitted successfully to Project Manager!</div>';
                    // Clear form data on successful submission
                    $_POST = array();
                    
                    // Refresh the submitted reports list
                    $stmt = $connection->prepare("
                        SELECT r.*, p.project_name 
                        FROM reports r
                        JOIN projects p ON r.project_id = p.project_id
                        WHERE r.created_by = ?
                        ORDER BY r.created_at DESC
                    ");
                    $stmt->bind_param("i", $contractor_id);
                    $stmt->execute();
                    $submitted_reports = $stmt->get_result();
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
$stmt->bind_param("i", $contractor_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Report | Contractor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: rgb(54, 62, 69);
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --gray: rgb(65, 71, 76);
            --gray-light: #f8f9fa;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --card-border-radius: 0.75rem;
            --border-radius-sm: 0.375rem;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: var(--dark-color);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
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
            color: var(--primary-color);
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
            transition: all 0.3s ease;
        }

        .toggle-sidebar:hover {
            color: var(--primary-color);
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
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary-color);
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
            background: linear-gradient(135deg, var(--primary-color), rgb(64, 67, 73));
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
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 0.3);
        }

        .sidebar-collapsed .logout-text {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: var(--card-border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        .page-header h1 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .page-header p {
            opacity: 0.8;
            margin-bottom: 0;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px 25px;
        }

        .card-body {
            padding: 25px;
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .btn {
            padding: 12px 20px;
            font-weight: 500;
            border-radius: var(--border-radius-sm);
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Alert Styles */
        .alert {
            border-radius: var(--border-radius-sm);
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
        }

        /* Submitted Reports Section */
        .reports-container {
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .report-card {
            background-color: #f8f9fa;
            border-radius: var(--border-radius-sm);
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .report-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .report-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .report-project {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .report-date {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .report-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .report-type-progress {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .report-type-issue {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .report-type-completion {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }

        .report-type-other {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        .report-content {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 10px;
            max-height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .report-attachment {
            display: inline-flex;
            align-items: center;
            font-size: 0.85rem;
            color: var(--primary-color);
            text-decoration: none;
            margin-top: 5px;
        }

        .report-attachment i {
            margin-right: 5px;
        }

        .report-attachment:hover {
            text-decoration: underline;
        }

        .no-reports {
            text-align: center;
            padding: 30px;
            color: var(--gray);
            font-style: italic;
        }

        .no-reports i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #e9ecef;
        }

        .view-more-btn {
            color: var(--primary-color);
            background: none;
            border: none;
            padding: 0;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }

        .view-more-btn i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }

        .view-more-btn:hover i {
            transform: translateX(3px);
        }

        /* Responsive Adjustments */
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
                padding: 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .card-header, .card-body, .reports-container {
                padding: 15px;
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
            <a href="ContractorDashboard.php" class="sidebar-logo">
                <span class="logo-text"> <img src="../images/LOGO.png" alt="SLU Logo"> </span>
            </a>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="ContractorDashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="ContractorPrepareReport.php" class="active">
                    <i class="fas fa-file-alt"></i>
                    <span>Submit Reports</span>
                </a>
            </li>
            <li>
                <a href="ContractorProfile.php">
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
                    <div class="user-name"><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) : 'Contractor'; ?></div>
                    <div class="user-role">Contractor</div>
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
        <div class="container-fluid">
            <div class="page-header">
                <h1><i class="fas fa-file-alt me-2"></i> Submit Reports</h1>
                <p>Create and submit reports for your assigned projects</p>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <!-- Previously Submitted Reports Section -->
            <div class="reports-container">
                <h2 class="section-title"><i class="fas fa-history"></i> Your Submitted Reports</h2>
                
                <?php if ($submitted_reports->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($report = $submitted_reports->fetch_assoc()): 
                            $report_type_class = '';
                            switch ($report['report_type']) {
                                case 'Progress Report':
                                    $report_type_class = 'report-type-progress';
                                    break;
                                case 'Issue Report':
                                    $report_type_class = 'report-type-issue';
                                    break;
                                case 'Completion Report':
                                    $report_type_class = 'report-type-completion';
                                    break;
                                default:
                                    $report_type_class = 'report-type-other';
                            }
                        ?>
                            <div class="col-md-6 mb-3">
                                <div class="report-card">
                                    <div class="report-header">
                                        <div>
                                            <h5 class="report-title"><?= htmlspecialchars($report['title']) ?></h5>
                                            <div class="report-project">
                                                <i class="fas fa-project-diagram"></i> 
                                                <?= htmlspecialchars($report['project_name']) ?>
                                            </div>
                                        </div>
                                        <div class="report-date">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= date('M d, Y', strtotime($report['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <span class="report-type <?= $report_type_class ?>">
                                        <?= htmlspecialchars($report['report_type']) ?>
                                    </span>
                                    
                                    <div class="report-content">
                                        <?= nl2br(htmlspecialchars($report['content'])) ?>
                                    </div>
                                    
                                    <?php if (!empty($report['file_attachment'])): ?>
                                        <a href="../<?= htmlspecialchars($report['file_attachment']) ?>" class="report-attachment" target="_blank">
                                            <i class="fas fa-paperclip"></i> View Attachment
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button class="view-more-btn mt-2" data-bs-toggle="modal" data-bs-target="#reportModal<?= $report['report_id'] ?>">
                                        View Full Report <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                                
                                <!-- Report Modal -->
                                <div class="modal fade" id="reportModal<?= $report['report_id'] ?>" tabindex="-1" aria-labelledby="reportModalLabel<?= $report['report_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="reportModalLabel<?= $report['report_id'] ?>">
                                                    <?= htmlspecialchars($report['title']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <span class="report-type <?= $report_type_class ?>">
                                                        <?= htmlspecialchars($report['report_type']) ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Project:</strong> <?= htmlspecialchars($report['project_name']) ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Submitted on:</strong> <?= date('F d, Y \a\t h:i A', strtotime($report['created_at'])) ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Report Content:</strong>
                                                    <div class="p-3 bg-light rounded">
                                                        <?= nl2br(htmlspecialchars($report['content'])) ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($report['file_attachment'])): ?>
                                                    <div class="mb-3">
                                                        <strong>Attachment:</strong>
                                                        <div class="mt-2">
                                                            <a href="../<?= htmlspecialchars($report['file_attachment']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                <i class="fas fa-download me-1"></i> Download Attachment
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-reports">
                        <i class="fas fa-file-alt"></i>
                        <h4>No reports submitted yet</h4>
                        <p>Your submitted reports will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Submit New Report Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="section-title mb-0"><i class="fas fa-plus-circle"></i> Submit New Report</h2>
                </div>

                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="project_id" class="form-label">Select Project</label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php while ($project = $projects_result->fetch_assoc()): ?>
                                        <option value="<?= $project['project_id'] ?>" <?= (isset($_POST['project_id']) && $_POST['project_id'] == $project['project_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['project_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select name="report_type" id="report_type" class="form-select" required>
                                    <option value="">-- Select Report Type --</option>
                                    <option value="Progress Report" <?= (isset($_POST['report_type']) && $_POST['report_type'] == 'Progress Report') ? 'selected' : '' ?>>Progress Report</option>
                                    <option value="Issue Report" <?= (isset($_POST['report_type']) && $_POST['report_type'] == 'Issue Report') ? 'selected' : '' ?>>Issue Report</option>
                                    <option value="Completion Report" <?= (isset($_POST['report_type']) && $_POST['report_type'] == 'Completion Report') ? 'selected' : '' ?>>Completion Report</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Report Title</label>
                            <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Report Content</label>
                            <textarea name="content" id="content" class="form-control" rows="6" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="report_file" class="form-label">Attachment (Optional)</label>
                            <input type="file" name="report_file" id="report_file" class="form-control">
                            <div class="form-text">Allowed file types: PDF, Word, Excel, JPEG, PNG (Max size: 10MB)</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
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
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const projectId = document.getElementById('project_id').value;
            const reportType = document.getElementById('report_type').value;
            const title = document.getElementById('title').value;
            const content = document.getElementById('content').value;
            
            if (!projectId || !reportType || !title || !content) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // File size validation
        document.getElementById('report_file').addEventListener('change', function() {
            const fileInput = this;
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (fileInput.files.length > 0) {
                const fileSize = fileInput.files[0].size;
                
                if (fileSize > maxSize) {
                    alert('File size exceeds the limit of 10MB. Please choose a smaller file.');
                    fileInput.value = '';
                }
            }
        });
    </script>
</body>
</html>


