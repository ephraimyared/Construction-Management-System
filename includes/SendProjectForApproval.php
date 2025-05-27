<?php 
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../index.php");
    exit();
}

$manager_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

// Handle form submission for sending project for approval
if (isset($_POST['submit_project'])) {
    $project_id = $_POST['project_id'];
    // $project_description = $_POST['project_description'];
    // $project_budget = $_POST['project_budget'];
    $project_resources = isset($_POST['project_resources']) ? implode(", ", $_POST['project_resources']) : '';

    // Handle file upload
    $file = $_FILES['project_file'];
    $upload_dir = '../uploads/';
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];
    $file_size = $file['size'];

    if ($file_error === 0) {
        $file_destination = $upload_dir . basename($file_name);
        if (move_uploaded_file($file_tmp, $file_destination)) {
            $message = "File uploaded successfully.";
        } else {
            $message = "Failed to upload file.";
            $message_type = 'danger';
        }
    }

    $stmt = $connection->prepare("UPDATE projects SET status = 'In Progress', description = ?, budget = ?, resources = ?, file_path = ? WHERE project_id = ? AND manager_id = ? AND status = 'Planning'");
    $stmt->bind_param("ssssii", $project_description, $project_budget, $project_resources, $file_destination, $project_id, $manager_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = "Project successfully sent for approval.";
    } else {
        $message = "Failed to send project. It may already be sent or not belong to you.";
        $message_type = 'danger';
    }
    $stmt->close();
}

// Get all projects for this manager
$projects = $connection->query("SELECT * FROM projects WHERE manager_id = $manager_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Project For Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin-top: 70px;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 8px 20px;
            font-weight: 500;
        }
        .input-group-text {
            background-color: #e9ecef;
            font-weight: 500;
        }
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .status-unsent {
            background-color: #ffc107;
            color: #212529;
        }
        .status-sent {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>

<a href="ManagerDashboard.php" class="btn back-btn">
    <i class="fas fa-arrow-left me-2"></i> Back
</a>

<div class="container">
    <h2 class="mb-4 text-primary"><i class="fas fa-paper-plane me-2"></i>Send Project Tasks For Approval</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive mb-4">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($projects->num_rows > 0): ?>
                    <?php while ($row = $projects->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                            <td>
                                <span class="status-badge <?= $row['status'] === 'Planning' ? 'status-unsent' : 'status-sent' ?>">
                                    <?= $row['status'] === 'Planning' ? 'Unsent' : 'Sent' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-3"></i><br>
                            No projects found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-paper-plane me-2"></i>Send a Project
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="project_id" class="form-label">Select Project to Send</label>
                    <select name="project_id" id="project_id" class="form-select" required onchange="updateProjectDetails()">
                        <option value="">-- Select Project --</option>
                        <?php
                        $unsent = $connection->query("
                            SELECT project_id, project_name 
                            FROM projects 
                            WHERE manager_id = $manager_id 
                              AND status = 'Planning'
                        ");
                        while ($proj = $unsent->fetch_assoc()) {
                            echo '<option value="' . $proj['project_id'] . '">' . htmlspecialchars($proj['project_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div id="project_details">
                    <div class="mb-4">
                        <label for="project_file" class="form-label">Attach File (PDF or Image)</label>
                        <input type="file" class="form-control" id="project_file" name="project_file" accept="application/pdf, image/*">
                    </div>
                </div>

                <button type="submit" name="submit_project" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Send for Approval
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('project_id').addEventListener('change', function() {
    var projectId = this.value;
    
    if (projectId) {
        fetch('get_project_details.php?project_id=' + projectId)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('project_description').value = data.description || '';
                    document.getElementById('project_budget').value = data.budget || '';
                    const resources = data.resources ? data.resources.split(", ") : [];
                    document.querySelectorAll("input[name='project_resources[]']").forEach((checkbox) => {
                        checkbox.checked = resources.includes(checkbox.value);
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
});
</script>

</body>
</html>

<?php $connection->close(); ?>