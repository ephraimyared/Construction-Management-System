<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../login.php");
    exit();
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ManagerDashboard.php");
    exit();
}

$project_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch project details
$query = "SELECT p.*, u.FirstName, u.LastName 
          FROM projects p 
          LEFT JOIN users u ON p.contractor_id = u.UserID 
          WHERE p.project_id = ? AND p.manager_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("ii", $project_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Project not found or doesn't belong to this manager
    header("Location: ManagerDashboard.php");
    exit();
}

$project = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details | Construction Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .project-details-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #3498db;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #3498db;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
        }
        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <a href="ManagerDashboard.php" class="btn btn-outline-secondary back-btn">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
        
        <div class="project-details-card">
            <h2 class="section-title">Project Details</h2>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h3><?php echo htmlspecialchars($project['project_name']); ?></h3>
                    <p class="text-muted">
                        <?php 
                            $statusClass = '';
                            if ($project['status'] == 'Active' || $project['status'] == 'In Progress') {
                                $statusClass = 'bg-success';
                            } elseif ($project['status'] == 'Completed') {
                                $statusClass = 'bg-info';
                            } else {
                                $statusClass = 'bg-warning';
                            }
                        ?>
                        Status: <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($project['status']); ?></span>
                        
                        <?php if (isset($project['decision_status']) && !empty($project['decision_status'])): ?>
                            <span class="ms-2">
                                Approval: 
                                <span class="badge <?php echo $project['decision_status'] === 'Approved' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($project['decision_status']); ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <!-- <a href="EditProject.php?id=<?php echo $project_id; ?>" class="btn btn-primary"
                        <i class="fas fa-edit me-1"></i> Edit Project > -->
                    </a>
                    <!-- <a href="ProjectAssignment.php?project_id=<?php echo $project_id; ?>" class="btn btn-success ms-2"
                        <i class="fas fa-tasks me-1"></i> Manage Assignment > -->
                    </a>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Basic Information</h5>
                            <hr>
                            <div class="mb-3">
                                <div class="detail-label">Description</div>
                                <div><?php echo htmlspecialchars($project['description']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="detail-label">Start Date</div>
                                    <div><?php echo htmlspecialchars($project['start_date']); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-label">End Date</div>
                                    <div><?php echo htmlspecialchars($project['end_date']); ?></div>
                                </div>
                            </div>
                            <?php if (isset($project['budget'])): ?>
                            <div class="mb-3">
                                <div class="detail-label">Budget</div>
                                <div>$<?php echo number_format($project['budget'], 2); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Assignment Information</h5>
                            <hr>
                            <div class="mb-3">
                                <div class="detail-label">Assigned Contractor</div>
                                <div>
                                    <?php if (isset($project['contractor_id']) && $project['contractor_id'] > 0): ?>
                                        <?php echo htmlspecialchars($project['FirstName'] . ' ' . $project['LastName']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No contractor assigned</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- You can add more assignment details here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional sections can be added here -->
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $connection->close(); ?>
