<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

$project_manager_id = $_SESSION['user_id'];
$message = "";

// Fetch projects managed by this PM
$stmt = $connection->prepare("SELECT project_id, project_name FROM projects WHERE manager_id = ?");
$stmt->bind_param("i", $project_manager_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $report_type = $_POST['report_type'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $file_path = null;

    // Handle file upload
    if (!empty($_FILES['report_file']['name'])) {
        $upload_dir = "../uploads/reports/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($_FILES['report_file']['name']);
        $target_file = $upload_dir . time() . "_" . $file_name;

        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        } else {
            $message = '<div class="alert alert-danger">❌ Failed to upload file.</div>';
        }
    }

    if ($project_id && $report_type && $title && $content) {
        $stmt = $connection->prepare("INSERT INTO reports (project_id, created_by, report_type, title, content, attachment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $project_id, $project_manager_id, $report_type, $title, $content, $file_path);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ Report submitted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error submitting report: ' . $stmt->error . '</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">⚠️ Please fill in all required fields.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #e3f2fd, #f8f9fa);
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            max-width: 850px;
            background: #fff;
            padding: 45px;
            margin: 60px auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #0d6efd;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .form-control, .form-select {
            border-radius: 8px;
            transition: border-color 0.3s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            font-weight: 500;
            padding: 10px 25px;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
        }

        .btn-secondary {
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 25px;
        }

        .alert {
            font-weight: 500;
            border-radius: 8px;
        }

        .icon-title {
            margin-right: 10px;
            color: #0d6efd;
        }

        .file-label {
            color: #495057;
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-pen-nib icon-title"></i> Submit Project Report</h2>
    
    <?= $message ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Select Project</label>
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
                <option value="">-- Select Report Type --</option>
                <option value="Progress">Progress</option>
                <option value="Site Inspection">Site Inspection</option>
                <option value="Material Quality">Material Quality</option>
                <option value="Technical Issue">Technical Issue</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Report Title</label>
            <input type="text" name="title" class="form-control" placeholder="Enter report title" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Report Content</label>
            <textarea name="content" class="form-control" rows="6" placeholder="Write your report..." required></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label file-label">Attach File (optional)</label>
            <input type="file" name="report_file" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-paper-plane"></i> Submit Report</button>
        <a href="ManagerManageReport.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Report Panel</a>
    </form>
</div>

</body>
</html>

<?php $connection->close(); ?>
