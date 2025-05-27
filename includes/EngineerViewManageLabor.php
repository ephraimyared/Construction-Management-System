<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in and is a Site Engineer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

$engineer_id = $_SESSION['user_id'];

// Fetch labor entries for this Site Engineer
$stmt = $connection->prepare("
    SELECT dl.*, p.project_name 
    FROM daily_labor dl
    JOIN projects p ON dl.project_id = p.project_id
    WHERE dl.site_engineer_id = ?
    ORDER BY dl.date DESC
");
$stmt->bind_param("i", $engineer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Daily Labor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 1000px;
            margin: 60px auto;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #007bff;
            margin-bottom: 30px;
            text-align: center;
        }
        table {
            margin-top: 20px;
        }
        .table th {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Daily Labor Entries</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Project</th>
                    <th>Date</th>
                    <th>Hours Worked</th>
                    <th>Tasks Performed</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['hours_worked']) ?></td>
                        <td><?= htmlspecialchars($row['tasks_performed']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No labor entries found.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="EngineerManageDailyLabor.php" class="btn btn-secondary">Back</a>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$connection->close();
?>
