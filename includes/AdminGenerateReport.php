<?php 
session_start();
include('../db_connection.php');

// Check if user is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Fetch all reports submitted by Project Managers
$query = "SELECT r.report_id, r.report_type, r.title, r.submitted_at, p.project_name, u.username as submitted_by, r.attachment
          FROM reports r
          JOIN projects p ON r.project_id = p.project_id
          JOIN users u ON r.created_by = u.UserID
          WHERE u.role = 'Project Manager'
          ORDER BY r.submitted_at DESC";
$stmt = $connection->prepare($query);
$stmt->execute();
$reports_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e5383b;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gradient: linear-gradient(135deg, var(--primary), var(--secondary));
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7ff;
            color: var(--dark);
            min-height: 100vh;
            padding-bottom: 50px;
            position: relative;
        }

        .container {
            max-width: 1200px;
            background: white;
            padding: 40px;
            margin: 40px auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            position: relative;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .icon-title {
            margin-right: 10px;
            color: var(--primary);
        }

        .back-button {
            padding: 10px 20px;
            font-size: 15px;
            border-radius: 50px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            color: white;
            border: none;
            background: var(--gradient);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            color: white;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: var(--gradient);
            color: white;
        }

        .table thead th {
            padding: 18px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: #444;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-info {
            background: var(--info);
            border: none;
            color: white;
        }

        .btn-info:hover {
            background: #3a87e0;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(72, 149, 239, 0.3);
        }

        .btn-download {
            background: var(--success);
            border: none;
            color: white;
        }

        .btn-download:hover {
            background: #3ab4d9;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }

        .no-attachment {
            color: var(--gray);
            font-style: italic;
            opacity: 0.7;
        }
        
        .report-id {
            font-weight: 600;
            color: var(--primary);
        }
        
        .project-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        .report-type {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }
        
        .report-title {
            font-weight: 500;
            color: var(--dark);
        }
        
        .submitted-by {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .submitted-at {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        @media (max-width: 992px) {
            .container {
                padding: 30px;
                margin: 30px 15px;
            }
            
            .table thead th {
                padding: 15px 10px;
                font-size: 0.75rem;
            }
            
            .table tbody td {
                padding: 12px 10px;
            }
            
            .btn-sm {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 25px 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                border-radius: var(--border-radius);
                overflow: hidden;
                box-shadow: var(--shadow);
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-clipboard-list icon-title"></i>Manage Reports</h2>
            <a href="AdminDashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Project Name</th>
                        <th>Report Type</th>
                        <th>Title</th>
                        <th>Submitted By</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                        <th>Attachment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports_result->num_rows > 0): ?>
                        <?php while ($row = $reports_result->fetch_assoc()): ?>
                            <tr>
                                <td class="report-id">#<?= $row['report_id'] ?></td>
                                <td class="project-name"><?= htmlspecialchars($row['project_name']) ?></td>
                                <td><span class="report-type"><?= htmlspecialchars($row['report_type']) ?></span></td>
                                <td class="report-title"><?= htmlspecialchars($row['title']) ?></td>
                                <td>
                                    <div class="submitted-by">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($row['submitted_by'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($row['submitted_by']) ?>
                                    </div>
                                </td>
                                <td class="submitted-at">
                                    <?= date('M d, Y g:i A', strtotime($row['submitted_at'])) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="AdminViewReportById.php?report_id=<?= $row['report_id'] ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['attachment'])): ?>
                                        <a href="<?= htmlspecialchars($row['attachment']) ?>" class="btn btn-download btn-sm" target="_blank">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="no-attachment">No Attachment</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-0">No reports found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $connection->close(); ?>
