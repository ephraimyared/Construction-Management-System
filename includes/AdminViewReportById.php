<?php
session_start();
include('../db_connection.php');

// Check if the user is logged in and has the 'Admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Check if report_id is passed via URL
if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];

    // Fetch report details
    $stmt = $connection->prepare("SELECT r.report_id, r.report_type, r.title, r.content, r.submitted_at, p.project_name, u.username as submitted_by
                                  FROM reports r
                                  JOIN projects p ON r.project_id = p.project_id
                                  JOIN users u ON r.created_by = u.UserID
                                  WHERE r.report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the report data
        $report = $result->fetch_assoc();
    } else {
        echo "Report not found!";
        exit();
    }
} else {
    echo "No report selected!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Report | SLU Construction Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .report-container {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .report-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .report-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .report-meta {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
        }

        .report-meta-item {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .report-meta-item i {
            width: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }

        .report-meta-label {
            font-weight: 600;
            color: var(--dark-color);
            width: 140px;
        }

        .report-meta-value {
            color: var(--secondary-color);
        }

        .report-content {
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            line-height: 1.7;
            color: #333;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .report-content-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }

        .btn-back {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .report-type-badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: var(--info-color);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .report-container {
                padding: 20px;
                margin: 20px auto;
            }
            
            .report-meta-item {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 15px;
            }
            
            .report-meta-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>

<div class="report-container">
    <div class="report-header">
        <h2 class="report-title">
            <i class="fas fa-file-alt me-2"></i> <?= htmlspecialchars($report['title']) ?>
        </h2>
        <div class="report-type-badge">
            <i class="fas fa-tag me-1"></i> <?= htmlspecialchars($report['report_type']) ?>
        </div>
    </div>

    <div class="report-meta">
        <div class="report-meta-item">
            <i class="fas fa-building"></i>
            <span class="report-meta-label">Project:</span>
            <span class="report-meta-value"><?= htmlspecialchars($report['project_name']) ?></span>
        </div>
        
        <div class="report-meta-item">
            <i class="fas fa-user"></i>
            <span class="report-meta-label">Submitted By:</span>
            <span class="report-meta-value"><?= htmlspecialchars($report['submitted_by']) ?></span>
        </div>
        
        <div class="report-meta-item">
            <i class="fas fa-calendar-alt"></i>
            <span class="report-meta-label">Submission Date:</span>
            <span class="report-meta-value"><?= date('F j, Y, g:i a', strtotime($report['submitted_at'])) ?></span>
        </div>
    </div>

    <div class="report-content">
        <h4 class="report-content-title">Report Content</h4>
        <div class="report-content-body">
            <?= nl2br(htmlspecialchars($report['content'])) ?>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="AdminGenerateReport.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $connection->close(); ?>
