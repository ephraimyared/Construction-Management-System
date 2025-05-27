<?php
session_start();
require '../db_connection.php';

// Verify admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Initialize variables
$message = '';
$message_type = '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_data = null;
$projects = [];
$other_managers = [];
$manager_workloads = [];

// Validate parameters
if (empty($type) || $id <= 0) {
    header("Location: manager_management.php");
    exit();
}

// Get user data
if ($type === 'manager') {
    $user_query = $connection->prepare("SELECT UserID, FirstName, LastName, Email FROM users WHERE UserID = ? AND Role = 'Project Manager'");
    $user_query->bind_param("i", $id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    
    if ($user_result->num_rows === 0) {
        header("Location: manager_management.php");
        exit();
    }
    
    $user_data = $user_result->fetch_assoc();
    
    // Get projects assigned to this manager
    $projects_query = $connection->prepare("SELECT project_id, project_name, status, start_date, end_date FROM projects WHERE manager_id = ?");
    $projects_query->bind_param("i", $id);
    $projects_query->execute();
    $projects = $projects_query->get_result();
    
    // Get other active managers for reassignment with their workload
    $managers_query = $connection->prepare("
        SELECT u.UserID, u.FirstName, u.LastName, COUNT(p.project_id) as project_count 
        FROM users u 
        LEFT JOIN projects p ON u.UserID = p.manager_id 
        WHERE u.Role = 'Project Manager' AND u.UserID != ? AND (u.is_active = 1 OR u.is_active IS NULL)
        GROUP BY u.UserID
        ORDER BY project_count ASC
    ");
    $managers_query->bind_param("i", $id);
    $managers_query->execute();
    $manager_workloads = $managers_query->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle multi-manager reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_projects_multi'])) {
    $project_assignments = isset($_POST['project_assignments']) ? $_POST['project_assignments'] : [];
    
    if (empty($project_assignments)) {
        $message = "No projects were assigned to managers.";
        $message_type = "warning";
    } else {
        try {
            // Begin transaction
            $connection->begin_transaction();
            
            $reassigned_count = 0;
            
            // Process each project assignment
            foreach ($project_assignments as $project_id => $manager_id) {
                if (!empty($manager_id)) {
                    $update_query = $connection->prepare("UPDATE projects SET manager_id = ? WHERE project_id = ?");
                    $update_query->bind_param("ii", $manager_id, $project_id);
                    $update_query->execute();
                    $reassigned_count++;
                }
            }
            
            // Commit transaction
            $connection->commit();
            
            $message = "$reassigned_count project(s) reassigned successfully.";
            $message_type = "success";
            
            // Check if all projects are reassigned
            $remaining_query = $connection->prepare("SELECT COUNT(*) as count FROM projects WHERE manager_id = ?");
            $remaining_query->bind_param("i", $id);
            $remaining_query->execute();
            $remaining = $remaining_query->get_result()->fetch_assoc()['count'];
            
            if ($remaining == 0) {
                // All projects reassigned, proceed with deletion
                $delete_query = $connection->prepare("DELETE FROM users WHERE UserID = ? AND Role = 'Project Manager'");
                $delete_query->bind_param("i", $id);
                $delete_query->execute();
                
                // Redirect back to manager management
                $_SESSION['message'] = "Manager deleted successfully after reassigning all projects.";
                $_SESSION['message_type'] = "success";
                header("Location: manager_management.php");
                exit();
            } else {
                // Refresh project list
                $projects_query->execute();
                $projects = $projects_query->get_result();
                
                // Refresh manager workloads
                $managers_query->execute();
                $manager_workloads = $managers_query->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $connection->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Handle delete without reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_without_reassign'])) {
    try {
        // Begin transaction
        $connection->begin_transaction();
        
        // Set projects to NULL manager
        $update_query = $connection->prepare("UPDATE projects SET manager_id = NULL WHERE manager_id = ?");
        $update_query->bind_param("i", $id);
        $update_query->execute();
        
        // Delete the manager
        $delete_query = $connection->prepare("DELETE FROM users WHERE UserID = ? AND Role = 'Project Manager'");
        $delete_query->bind_param("i", $id);
        $delete_query->execute();
        
        // Commit transaction
        $connection->commit();
        
        // Redirect back to manager management
        $_SESSION['message'] = "Manager deleted successfully. Projects are now unassigned.";
        $_SESSION['message_type'] = "success";
        header("Location: manager_management.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $connection->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reassign Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
        .back-btn {
            margin: 20px;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .project-card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 10px;
        }
        .project-card:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .workload-table {
            margin-bottom: 25px;
        }
        .workload-low {
            background-color: #d1e7dd;
        }
        .workload-medium {
            background-color: #fff3cd;
        }
        .workload-high {
            background-color: #f8d7da;
        }
        .workload-indicator {
            width: 15px;
            height: 15px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 5px;
        }
        .project-manager-select {
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <a href="manager_management.php" class="btn btn-secondary back-btn">
                    <i class="bi bi-arrow-left"></i> Back to Managers
                </a>
            </div>
        </div>
        
        <div class="container mt-3">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Reassign Projects Before Deletion</h2>
                </div>
                
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($user_data): ?>
                        <div class="user-info">
                            <h4>Manager to Delete:</h4>
                            <p><strong>Name:</strong> <?= htmlspecialchars($user_data['FirstName'] . ' ' . $user_data['LastName']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($user_data['Email']) ?></p>
                        </div>
                        
                        <?php if ($projects && $projects->num_rows > 0): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> This manager has <?= $projects->num_rows ?> project(s) assigned. Please reassign them before deletion.
                            </div>
                            
                            <?php if (count($manager_workloads) > 0): ?>
                                <!-- Manager Workload Table -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Project Manager Workloads</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <span class="workload-indicator" style="background-color: #d1e7dd;"></span> Low workload (0-2 projects)
                                                <span class="workload-indicator ms-3" style="background-color: #fff3cd;"></span> Medium workload (3-5 projects)
                                                <span class="workload-indicator ms-3" style="background-color: #f8d7da;"></span> High workload (6+ projects)
                                            </small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover workload-table">
                                                <thead>
                                                    <tr>
                                                        <th>Manager Name</th>
                                                        <th>Current Projects</th>
                                                        <th>Workload</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($manager_workloads as $manager): ?>
                                                        <?php 
                                                            $workload_class = '';
                                                            $workload_text = '';
                                                            
                                                            if ($manager['project_count'] <= 2) {
                                                                $workload_class = 'workload-low';
                                                                $workload_text = 'Low';
                                                            } elseif ($manager['project_count'] <= 5) {
                                                                $workload_class = 'workload-medium';
                                                                $workload_text = 'Medium';
                                                            } else {
                                                                $workload_class = 'workload-high';
                                                                $workload_text = 'High';
                                                            }
                                                        ?>
                                                        <tr class="<?= $workload_class ?>">
                                                            <td><?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?></td>
                                                            <td><?= $manager['project_count'] ?></td>
                                                            <td><?= $workload_text ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="post" action="">
                                    <div class="mb-4">
                                        <h5>Assign Each Project to a Manager:</h5>
                                        <p class="text-muted">You can distribute projects across multiple managers to balance workload.</p>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Project</th>
                                                        <th>Status</th>
                                                        <th>Timeline</th>
                                                        <th>Assign To</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    // Reset the projects result pointer
                                                    $projects_query->execute();
                                                    $projects = $projects_query->get_result();
                                                    
                                                    while ($project = $projects->fetch_assoc()): 
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?= htmlspecialchars($project['project_name']) ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?= getStatusColor($project['status']) ?>"><?= $project['status'] ?></span>
                                                            </td>
                                                            <td>
                                                                <small>
                                                                    <?= date('M d, Y', strtotime($project['start_date'])) ?> - 
                                                                    <?= date('M d, Y', strtotime($project['end_date'])) ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <select class="form-select project-manager-select" name="project_assignments[<?= $project['project_id'] ?>]" required>
                                                                    <option value="">-- Select Manager --</option>
                                                                                          <?php foreach ($manager_workloads as $manager): ?>
                                                                        <?php 
                                                                            $workload_text = '';
                                                                            if ($manager['project_count'] <= 2) {
                                                                                $workload_text = 'Low';
                                                                            } elseif ($manager['project_count'] <= 5) {
                                                                                $workload_text = 'Medium';
                                                                            } else {
                                                                                $workload_text = 'High';
                                                                            }
                                                                        ?>
                                                                        <option value="<?= $manager['UserID'] ?>">
                                                                            <?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?> 
                                                                            (Workload: <?= $workload_text ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-4">
                                            <div>
                                                <button type="button" class="btn btn-outline-primary" id="assignLowWorkload">
                                                    <i class="bi bi-lightning-charge"></i> Auto-Assign to Low Workload Managers
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary ms-2" id="clearAllAssignments">
                                                    <i class="bi bi-x-circle"></i> Clear All
                                                </button>
                                            </div>
                                            <div>
                                                <button type="submit" name="reassign_projects_multi" class="btn btn-primary">
                                                    <i class="bi bi-arrow-right-circle"></i> Save Assignments
                                                </button>
                                                <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                                                    <i class="bi bi-trash"></i> Delete Without Reassigning
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-circle"></i> There are no other active managers to reassign projects to. Please activate another manager first.
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="manager_management.php" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> Return to Manager Management
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> This manager has no projects assigned. You can safely delete them.
                            </div>
                            <form method="post" action="">
                                <div class="d-grid gap-2">
                                    <button type="submit" name="delete_without_reassign" class="btn btn-danger">
                                        <i class="bi bi-trash"></i> Delete Manager
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Without Reassign Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion Without Reassignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> <strong>Warning!</strong>
                        <p>You are about to delete this manager without reassigning their projects. All projects will become unassigned.</p>
                        <p>This could cause issues with project management and reporting.</p>
                    </div>
                    <p>Are you absolutely sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" action="">
                        <button type="submit" name="delete_without_reassign" class="btn btn-danger">Delete Without Reassigning</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Form validation
            $('form').submit(function(e) {
                if ($(this).find('button[name="reassign_projects_multi"]').length > 0) {
                    // Check if at least one project is assigned
                    var atLeastOneAssigned = false;
                    $('.project-manager-select').each(function() {
                        if ($(this).val()) {
                            atLeastOneAssigned = true;
                            return false; // Break the loop
                        }
                    });
                    
                    if (!atLeastOneAssigned) {
                        alert('Please assign at least one project to a manager.');
                        e.preventDefault();
                        return false;
                    }
                }
            });
            
            // Auto-assign to low workload managers
            $('#assignLowWorkload').click(function() {
                // Get managers with low workload
                var lowWorkloadManagers = [];
                <?php foreach ($manager_workloads as $manager): ?>
                    <?php if ($manager['project_count'] <= 2): ?>
                        lowWorkloadManagers.push({
                            id: <?= $manager['UserID'] ?>,
                            name: "<?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?>",
                            count: <?= $manager['project_count'] ?>
                        });
                    <?php endif; ?>
                <?php endforeach; ?>
                
                // Sort by current project count (ascending)
                lowWorkloadManagers.sort(function(a, b) {
                    return a.count - b.count;
                });
                
                if (lowWorkloadManagers.length === 0) {
                    alert('No managers with low workload available.');
                    return;
                }
                
                // Distribute projects evenly among low workload managers
                var managerIndex = 0;
                $('.project-manager-select').each(function() {
                    if (lowWorkloadManagers.length > 0) {
                        $(this).val(lowWorkloadManagers[managerIndex].id);
                        
                        // Move to next manager in round-robin fashion
                        managerIndex = (managerIndex + 1) % lowWorkloadManagers.length;
                    }
                });
            });
            
            // Clear all assignments
            $('#clearAllAssignments').click(function() {
                $('.project-manager-select').val('');
            });
            
            // Highlight row in workload table when selecting a manager
            $('.project-manager-select').change(function() {
                var selectedId = $(this).val();
                
                // Reset highlighting for this row's manager
                $(this).closest('tr').find('.manager-highlight').removeClass('manager-highlight');
                
                if (selectedId) {
                    // Find the manager name from the selected option
                    var managerName = $(this).find('option:selected').text().split('(')[0].trim();
                    
                    // Highlight the corresponding row in the workload table
                    $('.workload-table tbody tr').each(function() {
                        if ($(this).find('td:first').text().trim() === managerName) {
                            $(this).addClass('table-primary');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
// Helper function to get appropriate badge color based on status
function getStatusColor($status) {
    switch ($status) {
        case 'Planning':
            return 'info';
        case 'In Progress':
            return 'primary';
        case 'On Hold':
            return 'warning';
        case 'Completed':
            return 'success';
        case 'Cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

