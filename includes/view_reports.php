<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

$manager_id = $_SESSION['user_id'];

$query = "
    SELECT r.title, r.content, r.report_type, r.created_at,
           p.project_name,
           u.FirstName, u.LastName
    FROM reports r
    JOIN projects p ON r.project_id = p.project_id
    JOIN users u ON r.created_by = u.UserID
    WHERE p.manager_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Contractor Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            max-width: 1000px;
            margin: 60px auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .report-card {
            border-left: 5px solid #007bff;
            padding: 20px;
            margin-bottom: 25px;
            background: #f9fbfc;
            border-radius: 8px;
            transition: 0.3s;
        }

        .report-card:hover {
            background-color: #eaf3fb;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #343a40;
        }

        .report-meta {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }

        .report-content {
            margin-top: 10px;
            font-size: 15px;
        }

        .back-btn {
            background-color: #198754;
            color: white;
            border: none;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #157347;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="ManageProjects.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    <h2><i class="fas fa-file-alt"></i> Contractor Reports</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($report = $result->fetch_assoc()): ?>
            <div class="report-card">
                <div class="report-title"><?php echo htmlspecialchars($report['title']); ?></div>
                <div class="report-meta">
                    <strong>Type:</strong> <?php echo htmlspecialchars($report['report_type']); ?> |
                    <strong>Project:</strong> <?php echo htmlspecialchars($report['project_name']); ?> |
                    <strong>By:</strong> <?php echo htmlspecialchars($report['FirstName'] . ' ' . $report['LastName']); ?> |
                    <strong>Date:</strong> <?php echo htmlspecialchars($report['created_at']); ?>
                </div>
                <div class="report-content">
                    <?php echo nl2br(htmlspecialchars($report['content'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted">No reports submitted yet.</p>
    <?php endif; ?>
</div>

</body>
</html>

<?php $connection->close(); ?>
