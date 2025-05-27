<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM projects WHERE manager_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Projects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #e3f2fd, #f1f8ff);
            font-family: 'Segoe UI', sans-serif;
        }

        .container {
            max-width: 1100px;
            margin: 80px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h2 {
            color: #007bff;
            font-weight: bold;
        }

        .project-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease-in-out;
        }

        .project-card:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            background-color: #eef6ff;
        }

        .project-title {
            font-size: 20px;
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 10px;
        }

        .project-info {
            font-size: 15px;
            color: #333;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #198754;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
        }

        .back-btn:hover {
            background-color: #157347;
        }

        .no-projects {
            text-align: center;
            font-size: 18px;
            color: #888;
            padding: 40px 0;
        }
    </style>
</head>
<body>

<a href="ManageProjects.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-project-diagram"></i> Available Projects</h2>
        <p class="text-muted">Projects you are managing</p>
    </div>

    <?php if ($projects->num_rows > 0): ?>
        <?php while ($project = $projects->fetch_assoc()): ?>
            <div class="project-card">
                <div class="project-title">
                    <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($project['project_name']); ?>
                </div>
                <div class="project-info">
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($project['description']); ?></p>
                    <p><strong>Start:</strong> <?php echo htmlspecialchars($project['start_date']); ?> |
                       <strong>End:</strong> <?php echo htmlspecialchars($project['end_date']); ?></p>
                    <p><strong>Status:</strong> <span class="badge bg-<?php 
                        echo $project['status'] === 'Approved' ? 'success' : 
                             ($project['status'] === 'Pending' ? 'warning' : 'secondary');
                    ?>"><?php echo htmlspecialchars($project['status']); ?></span></p>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-projects">
            <i class="fas fa-info-circle fa-2x text-secondary mb-3"></i><br>
            You have no projects at the moment.
        </div>
    <?php endif; ?>
</div>

</body>
</html>

<?php $connection->close(); ?>
