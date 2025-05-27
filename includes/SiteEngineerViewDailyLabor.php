<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in and is a Site Engineer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

$site_engineer_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch projects assigned to this Site Engineer for filtering
// Fetch projects assigned to this Site Engineer through project_assignments table for filtering
$projects_query = "SELECT DISTINCT p.project_id, p.project_name 
                  FROM projects p 
                  JOIN project_assignments pa ON p.project_id = pa.project_id
                  WHERE pa.user_id = ? 
                  AND pa.role_in_project = 'Assigned Site Engineer'
                  AND pa.status IN ('Assigned', 'In Progress', 'Completed')
                  ORDER BY p.project_name";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $site_engineer_id);
$stmt->execute();
$projects_result = $stmt->get_result();


// Initialize filter variables
$filter_project = isset($_GET['project_id']) ? $_GET['project_id'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query with potential filters
$query = "SELECT dl.*, p.project_name 
          FROM daily_labor dl
          JOIN projects p ON dl.project_id = p.project_id
          WHERE dl.site_engineer_id = ?";

$params = array($site_engineer_id);
$types = "i";

if (!empty($filter_project)) {
    $query .= " AND dl.project_id = ?";
    $params[] = $filter_project;
    $types .= "i";
}

if (!empty($filter_date_from)) {
    $query .= " AND dl.date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $query .= " AND dl.date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

$query .= " ORDER BY dl.date DESC, p.project_name";

// Prepare and execute the query
$labor_stmt = $connection->prepare($query);
$labor_stmt->bind_param($types, ...$params);
$labor_stmt->execute();
$labor_result = $labor_stmt->get_result();

// Handle record deletion if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_labor'])) {
    $labor_id = $_POST['labor_id'];
    
    // Verify this record belongs to the current site engineer
    $check_query = "SELECT * FROM daily_labor WHERE labor_id = ? AND site_engineer_id = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("ii", $labor_id, $site_engineer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Record belongs to this engineer, proceed with deletion
        $delete_query = "DELETE FROM daily_labor WHERE labor_id = ?";
        $delete_stmt = $connection->prepare($delete_query);
        $delete_stmt->bind_param("i", $labor_id);
        
        if ($delete_stmt->execute()) {
            $message = "Labor record deleted successfully!";
            $message_type = "success";
            
            // Refresh the labor records
            $labor_stmt->execute();
            $labor_result = $labor_stmt->get_result();
        } else {
            $message = "Error deleting labor record: " . $connection->error;
            $message_type = "danger";
        }
        $delete_stmt->close();
    } else {
        $message = "Unauthorized deletion attempt or record not found.";
        $message_type = "danger";
    }
    $check_stmt->close();
}

// Calculate summary statistics
$total_hours = 0;

$labor_count = 0;
$project_stats = array();

// Store the result set for calculations
$labor_records = array();
while ($row = $labor_result->fetch_assoc()) {
    $labor_records[] = $row;
    $total_hours += $row['hours_worked'];
  
    $labor_count++;
    
    // Collect stats by project
    $project_id = $row['project_id'];
    $project_name = $row['project_name'];
    
    if (!isset($project_stats[$project_id])) {
        $project_stats[$project_id] = array(
            'name' => $project_name,
            'hours' => 0,
 
            'count' => 0
        );
    }
    
    $project_stats[$project_id]['hours'] += $row['hours_worked'];
    
    $project_stats[$project_id]['count']++;
}

// Reset the result pointer for display
$labor_result->data_seek(0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Daily Labor Records | Site Engineer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            padding: 15px 20px;
        }
        .card-body {
            padding: 25px;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 10px 20px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-danger {
            background-color: #e74c3c;
            border: none;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        .back-button {
            margin-bottom: 15px;
            background-color: #6c757d;
            border: none;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        .alert {
            border-radius: 8px;
            padding: 15px 20px;
        }
        .table th {
            background-color: #343a40;
            color: white;
            font-weight: 500;
        }
        .table td {
            vertical-align: middle;
        }
        .badge-hours {
            background-color: #17a2b8;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
       
        .stats-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
        }
        .stats-title {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 10px;
        }
        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #3498db;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .project-stats {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .project-stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .project-name {
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="SiteEngineerManageDailyLabor.php" class="btn btn-light back-button">
            <i class="fas fa-arrow-left"></i> Back to Labor Management
        </a>
        <h2><i class="fas fa-clipboard-list me-2"></i>Daily Labor Records</h2>
        <p>View and manage daily labor records for your projects</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filter Form -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>Filter Records
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="project_id" class="form-label">Project</label>
                    <select class="form-select" id="project_id" name="project_id">
                        <option value="">All Projects</option>
                        <?php 
                        $projects_result->data_seek(0);
                        while ($project = $projects_result->fetch_assoc()): 
                        ?>
                            <option value="<?= $project['project_id'] ?>" <?= $filter_project == $project['project_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['project_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $filter_date_from ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $filter_date_to ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Statistics -->
    <div class="row">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Labor Records</div>
                <div class="stats-value"><?= $labor_count ?></div>
                <div class="stats-label">Records found</div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-title">Total Hours Worked</div>
                <div class="stats-value"><?= number_format($total_hours, 1) ?></div>
                <div class="stats-label">Hours</div>
            </div>
        </div>
        
   
    </div>
    
    <!-- Labor Records Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list-alt me-2"></i>Labor Records
        </div>
        <div class="card-body">
            <?php if (count($labor_records) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Project</th>
                                <th>Laborer</th>
                                <th>ID</th>
                                <th>Hours</th>
                                <th>Tasks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($labor_records as $labor): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($labor['date'])) ?></td>
                                    <td><?= htmlspecialchars($labor['project_name']) ?></td>
                                    <td><?= htmlspecialchars($labor['laborer_name']) ?></td>
                                    <td>
                                    
                                    </td>
                                    <td>
                                        <span class="badge badge-hours">
                                            <?= $labor['hours_worked'] ?> hrs
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary view-tasks" 
                                                data-bs-toggle="modal" data-bs-target="#tasksModal"
                                                data-tasks="<?= htmlspecialchars($labor['tasks_performed']) ?>"
                                                data-laborer="<?= htmlspecialchars($labor['laborer_name']) ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger delete-labor" 
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-id="<?= $labor['labor_id'] ?>"
                                                data-laborer="<?= htmlspecialchars($labor['laborer_name']) ?>"
                                                data-date="<?= date('M d, Y', strtotime($labor['date'])) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <h3>No Labor Records Found</h3>
                    <p>No labor records match your filter criteria or you haven't added any labor records yet.</p>
                    <a href="SiteEngineerAddDailyLabor.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Add Labor Record
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($labor_records) > 0): ?>
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="SiteEngineerAddDailyLabor.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Add New Labor Record
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Tasks Modal -->
<div class="modal fade" id="tasksModal" tabindex="-1" aria-labelledby="tasksModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tasksModalLabel">Tasks Performed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 id="modal-laborer-name" class="mb-3"></h6>
                <div id="modal-tasks" class="p-3 bg-light rounded"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="labor_id" id="delete_labor_id">
                    <p>Are you sure you want to delete this labor record?</p>
                    <p class="mb-0"><strong>Laborer:</strong> <span id="delete_laborer_name"></span></p>
                    <p><strong>Date:</strong> <span id="delete_date"></span></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. All information associated with this record will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_labor" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Handle tasks modal
    const tasksModal = document.getElementById('tasksModal');
    if (tasksModal) {
        tasksModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const tasks = button.getAttribute('data-tasks');
            const laborer = button.getAttribute('data-laborer');
            
            document.getElementById('modal-laborer-name').textContent = 'Tasks performed by ' + laborer;
            document.getElementById('modal-tasks').innerHTML = tasks.replace(/\n/g, '<br>');
        });
    }
    
    // Handle delete modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const laborer = button.getAttribute('data-laborer');
            const date = button.getAttribute('data-date');
            
            document.getElementById('delete_labor_id').value = id;
            document.getElementById('delete_laborer_name').textContent = laborer;
            document.getElementById('delete_date').textContent = date;
        });
    }
    
    // Date range validation
    document.querySelector('form').addEventListener('submit', function(event) {
        const dateFrom = document.getElementById('date_from').value;
        const dateTo = document.getElementById('date_to').value;
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            event.preventDefault();
            alert('Date From must be before or equal to Date To');
        }
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>

</body>
</html>

<?php
// Close database connections
if (isset($stmt)) $stmt->close();
if (isset($labor_stmt)) $labor_stmt->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($delete_stmt)) $delete_stmt->close();
$connection->close();
?>
