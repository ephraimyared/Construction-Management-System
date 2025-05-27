<?php
session_start();
include '../db_connection.php';

// Ensure the user is a Project Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../login.php");
    exit();
}

// Check if report ID is provided
if (!isset($_GET['report_id']) || empty($_GET['report_id'])) {
    header("Location: PMViewReports.php?type=site_engineer");
    exit();
}

$report_id = $_GET['report_id'];
$project_manager_id = $_SESSION['user_id'];

// Fetch the report details with user and project information
$stmt = $connection->prepare("
    SELECT r.*, 
           u.FirstName, u.LastName, u.Email, u.Phone,
           p.project_name, p.description as project_description, p.start_date, p.end_date, p.budget
    FROM reports r
    JOIN users u ON r.created_by = u.UserID
    JOIN projects p ON r.project_id = p.project_id
    WHERE r.report_id = ? 
    AND p.manager_id = ?
    AND u.Role = 'Site Engineer'
");

$stmt->bind_param("ii", $report_id, $project_manager_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if report exists and belongs to a project managed by this manager
if ($result->num_rows === 0) {
    header("Location: PMViewReports.php?type=site_engineer&error=report_not_found");
    exit();
}

$report = $result->fetch_assoc();

// Fetch comments on this report
$comments_stmt = $connection->prepare("
    SELECT c.*, u.FirstName, u.LastName, u.Role
    FROM comments c
    JOIN users u ON c.user_id = u.UserID
    WHERE c.report_id = ?
    ORDER BY c.comment_date ASC
");
$comments_stmt->bind_param("i", $report_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

// Handle adding a new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && !empty($_POST['comment_text'])) {
    $comment_text = $_POST['comment_text'];
    $user_id = $_SESSION['user_id'];
    
    $add_comment = $connection->prepare("
        INSERT INTO comments (report_id, user_id, comment_text) 
        VALUES (?, ?, ?)
    ");
    $add_comment->bind_param("iis", $report_id, $user_id, $comment_text);
    
    if ($add_comment->execute()) {
        // Redirect to refresh the page and show the new comment
        header("Location: SiteEngineerViewReportById.php?report_id=$report_id&success=comment_added");
        exit();
    } else {
        $error_message = "Failed to add comment: " . $connection->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Engineer Report | Project Manager Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-radius: 10px;
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: #333;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }
        
        .header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.75rem;
            position: relative;
        }
        
        .header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            position: relative;
        }
        
        .back-button {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .report-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .report-header {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .report-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .report-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
        }
        
        .meta-item i {
            color: var(--secondary-color);
            margin-right: 0.5rem;
        }
        
        .report-body {
            padding: 1.5rem;
        }
        
        .report-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .project-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .project-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .project-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            margin-bottom: 0.5rem;
        }
        
        .detail-label {
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 400;
        }
        
        .report-content {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #e9ecef;
        }
        
        .engineer-info {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .engineer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        .engineer-details h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .engineer-details p {
            margin: 0.25rem 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .file-attachment {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 4px;
            color: var(--secondary-color);
            text-decoration: none;
            margin-top: 1rem;
            transition: var(--transition);
        }
        
        .file-attachment i {
            margin-right: 0.5rem;
        }
        
        .file-attachment:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }
        
        .comments-section {
            margin-top: 2rem;
        }
        
        .comment {
            display: flex;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .comment:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .comment-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 600;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .comment-content {
            flex-grow: 1;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .comment-author {
            font-weight: 600;
        }
        
        .comment-role {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 0.5rem;
        }
        
        .comment-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .comment-text {
            margin: 0;
            line-height: 1.5;
        }
        
        .add-comment {
            margin-top: 2rem;
        }
        
        .add-comment textarea {
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="PMViewReports.php?type=site_engineer" class="btn back-button mb-3">
            <i class="fas fa-arrow-left me-2"></i>Back to Site Engineer Reports
        </a>
        <h2><i class="fas fa-file-alt me-2"></i>Site Engineer Report Details</h2>
        <p>Review detailed information about this report</p>
    </div>
    
    <div class="report-card">
        <div class="report-header">
            <div class="report-title"><?= htmlspecialchars($report['title']) ?></div>
            
            <div class="report-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span>Submitted: <?= date('F j, Y', strtotime($report['submitted_at'])) ?></span>
                </div>
                
                <?php if (!empty($report['report_type'])): ?>
                <div class="meta-item">
                    <i class="fas fa-tag"></i>
                    <span>Type: <?= htmlspecialchars(ucfirst($report['report_type'])) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($report['budget_status'])): ?>
                <div class="meta-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Budget Status: <?= htmlspecialchars($report['budget_status']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="report-body">
            <div class="report-section">
                <h3 class="section-title">Project Information</h3>
                <div class="project-info">
                    <div class="project-title"><?= htmlspecialchars($report['project_name']) ?></div>
                    <div class="project-details">
                        <div class="detail-item">
                            <div class="detail-label">Start Date</div>
                            <div class="detail-value"><?= date('F j, Y', strtotime($report['start_date'])) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">End Date</div>
                            <div class="detail-value"><?= date('F j, Y', strtotime($report['end_date'])) ?></div>
                        </div>
                        <?php if (!empty($report['budget'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">Budget</div>
                            <div class="detail-value">$<?= number_format($report['budget'], 2) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            
            <div class="report-section">
                <h3 class="section-title">Site Engineer Information</h3>
                <div class="engineer-info">
                    <div class="engineer-avatar">
                        <?= strtoupper(substr($report['FirstName'], 0, 1) . substr($report['LastName'], 0, 1)) ?>
                    </div>
                    <div class="engineer-details">
                        <h4><?= htmlspecialchars($report['FirstName'] . ' ' . $report['LastName']) ?></h4>
                        <p>
                            <?php if (!empty($report['Email'])): ?>
                                <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($report['Email']) ?>
                            <?php endif; ?>
                            <?php if (!empty($report['Phone'])): ?>
                                <br><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($report['Phone']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
                        <a href="../<?= htmlspecialchars($report['file_attachment']) ?>" class="file-attachment" target="_blank">
                            <i class="fas fa-file-download"></i> Download Attachment
                        </a>
                    
           
                
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connections
if (isset($stmt)) $stmt->close();
if (isset($comments_stmt)) $comments_stmt->close();
if (isset($add_comment)) $add_comment->close();
$connection->close();
?>

