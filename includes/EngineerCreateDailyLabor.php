<?php
session_start();
include '../db_connection.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

$site_engineer_id = $_SESSION['user_id'];
$message = "";

// Fetch projects assigned to this Site Engineer
$stmt = $connection->prepare("SELECT project_id, project_name FROM projects WHERE site_engineer_id = ?");
$stmt->bind_param("i", $site_engineer_id);
$stmt->execute();
$projects = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $labor_date = $_POST['labor_date'];
    $num_workers = $_POST['number_of_workers'];
    $description = $_POST['work_description'];

    if ($project_id && $labor_date && $num_workers && $description) {
        $stmt = $connection->prepare("
            INSERT INTO daily_labor (project_id, site_engineer_id, labor_date, number_of_workers, work_description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issis", $project_id, $site_engineer_id, $labor_date, $num_workers, $description);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ Daily labor record added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error: ' . $stmt->error . '</div>';
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
    <title>Create Daily Labor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f1f1f1;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 40px;
            margin: 60px auto;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #198754;
            margin-bottom: 25px;
        }
        .btn-success {
            background-color: #198754;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Daily Labor Entry</h2>
    <?= $message ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-select" required>
                <option value="">-- Select Project --</option>
                <?php while ($row = $projects->fetch_assoc()): ?>
                    <option value="<?= $row['project_id'] ?>"><?= htmlspecialchars($row['project_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Labor Date</label>
            <input type="date" name="labor_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Number of Workers</label>
            <input type="number" name="number_of_workers" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Work Description</label>
            <textarea name="work_description" class="form-control" rows="4" required></textarea>
        </div>

        <button type="submit" class="btn btn-success">Save Entry</button>
        <a href="EngineerManageDailyLabor.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>

<?php $connection->close(); ?>
