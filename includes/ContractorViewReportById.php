<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate and retrieve the report ID from the URL
if (!isset($_GET['report_id']) || !is_numeric($_GET['report_id'])) {
    echo "<div class='alert alert-danger'>Invalid report ID.</div>";
    exit();
}

$report_id = intval($_GET['report_id']);

// Prepare and execute the SQL statement to fetch the report details
$stmt = $connection->prepare("
    SELECT r.report_id, r.project_id, r.report_type, r.title, r.content, r.created_at,
           u.FirstName, u.LastName, p.project_name
    FROM reports r
    JOIN users u ON r.created_by = u.UserID
    JOIN projects p ON r.project_id = p.project_id
    WHERE r.report_id = ?
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the report exists
if ($result->num_rows === 0) {
    echo "<div class='alert alert-warning'>Report not found.</div>";
    exit();
}

$report = $result->fetch_assoc();
$stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 800px;
            background: #ffffff;
            padding: 40px;
            margin: 60px auto;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #28a745;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .report-detail {
            margin-bottom: 20px;
        }
        .report-detail label {
            font-weight: bold;
        }
        .btn-secondary {
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-file-alt"></i> Report Details</h2>

    <div class="report-detail">
        <label>Project Name:</label>
        <p><?= htmlspecialchars($report['project_name']) ?></p>
    </div>

    <div class="report-detail">
        <label>Report Type:</label>
        <p><?= htmlspecialchars($report['report_type']) ?></p>
    </div>

    <div class="report-detail">
        <label>Title:</label>
        <p><?= htmlspecialchars($report['title']) ?></p>
    </div>

    <div class="report-detail">
        <label>Content:</label>
        <p><?= nl2br(htmlspecialchars($report['content'])) ?></p>
    </div>

    <div class="report-detail">
        <label>Submitted By:</label>
        <p><?= htmlspecialchars($report['FirstName'] . ' ' . $report['LastName']) ?></p>
    </div>

    <div class="report-detail">
        <label>Submitted On:</label>
        <p><?= htmlspecialchars($report['created_at']) ?></p>
    </div>

    <a href="PMViewReports.php?type=contractor" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>

</body>
</html>
