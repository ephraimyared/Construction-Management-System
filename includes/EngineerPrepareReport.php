<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

$engineer_id = $_SESSION['user_id'];
$message = "";

// Fetch projects assigned to this engineer
// Updated query:  Join projects with project_assignments to filter by the engineer.
$stmt = $connection->prepare("
    SELECT p.project_id, p.project_name
    FROM projects p
    JOIN project_assignments pa ON p.project_id = pa.project_id
    WHERE pa.user_id = ? AND pa.role_in_project = 'Assigned Site Engineer'
");
$stmt->bind_param("i", $engineer_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $report_type = $_POST['report_type'];
    $title = $_POST['title'];
    $content = $_POST['content'];

    if ($project_id && $report_type && $title && $content) {
        $stmt = $connection->prepare("
            INSERT INTO reports (project_id, created_by, report_type, title, content)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $project_id, $engineer_id, $report_type, $title, $content);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ Report submitted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Failed to submit report.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">⚠️ Please fill in all fields.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 p-4 bg-white shadow rounded">
    <h2 class="mb-4 text-success">📄 Submit Report</h2>
    <?= $message ?>

    <form method="post">
        <div class="mb-3">
            <label for="project_id" class="form-label">Select Project</label>
            <select name="project_id" class="form-select" required>
                <option value="">-- Select Project --</option>
                <?php while ($row = $projects_result->fetch_assoc()): ?>
                    <option value="<?= $row['project_id'] ?>"><?= htmlspecialchars($row['project_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Report Type</label>
            <select name="report_type" class="form-select" required>
                <option value="Progress">Progress</option>
                <option value="Issue">Issue</option>
                <option value="Completion">Completion</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="form-label">Content</label>
            <textarea name="content" rows="6" class="form-control" required></textarea>
        </div>

        <button type="submit" class="btn btn-success">Submit Report</button>
        <a href="SiteEngineerDashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
