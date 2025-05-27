<?php
session_start();
include '../db_connection.php';

// Only allow contractors
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Contractor') {
    header("Location: ../index.php");
    exit();
}

$contractor_id = $_SESSION['user_id']; // Assuming user ID is stored in session for Contractor
$task_id = isset($_GET['task_id']) ? $_GET['task_id'] : 0;
$message = '';
$message_type = 'success';
$task_description = '';

// Check for messages in session (from redirects)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    // Clear the message from session
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Task descriptions based on task selection
$task_descriptions = [
    'Material Handling' => 'Transporting and organizing materials on the construction site.',
    'Debris Cleanup' => 'Cleaning up construction debris, including waste disposal.',
    'Digging' => 'Excavation and digging for foundations or trenches.',
    'Other' => 'Any other tasks assigned by the contractor.'
];

// Fetch projects assigned to this contractor
$projects_query = $connection->query("SELECT p.project_id, p.project_name 
                                     FROM projects p 
                                     JOIN project_assignments pa ON p.project_id = pa.project_id 
                                     WHERE pa.contractor_id = $contractor_id");
$projects = [];
while ($row = $projects_query->fetch_assoc()) {
    $projects[$row['project_id']] = $row['project_name'];
}

// Assign Task to Employee
if (isset($_POST['assign_task'])) {
    $employee_id = $_POST['employee_id'];
    $task_type = $_POST['task_id']; // This is actually the task type, not an ID
    $task_description = $_POST['task_description']; // Get the task description from the form
    $project_id = $_POST['project_id']; // Get the project ID from the form
    
    // First, let's check the structure of the tasks table
    $table_info = $connection->query("DESCRIBE tasks");
    $columns = [];
    while ($row = $table_info->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Now insert the task based on the available columns
    if (in_array('description', $columns)) {
        $stmt = $connection->prepare("INSERT INTO tasks (project_id, task_name, description, assigned_by, assigned_to) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $project_id, $task_type, $task_description, $contractor_id, $employee_id);
    } else {
        // If description doesn't exist, just insert the task name and assigned_by
        $stmt = $connection->prepare("INSERT INTO tasks (project_id, task_name, assigned_by, assigned_to) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $project_id, $task_type, $contractor_id, $employee_id);
    }
    
    if ($stmt->execute()) {
        // Get the newly created task ID
        $task_id = $connection->insert_id;
        $stmt->close();
        
        // Now create the task assignment
        // Check if task_assignments table has task_description column
        $table_info = $connection->query("DESCRIBE task_assignments");
        $columns = [];
        while ($row = $table_info->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        if (in_array('task_description', $columns)) {
            // Use user_id instead of employee_id to match the foreign key constraint
            $stmt = $connection->prepare("INSERT INTO task_assignments (task_id, user_id, task_description, project_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $task_id, $employee_id, $task_description, $project_id);
        } else {
            // Use user_id instead of employee_id to match the foreign key constraint
            $stmt = $connection->prepare("INSERT INTO task_assignments (task_id, user_id, project_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $task_id, $employee_id, $project_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Task assigned successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to assign task: " . $connection->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Failed to create task: " . $connection->error;
        $_SESSION['message_type'] = 'danger';
        $stmt->close();
    }
    
    // Redirect to the same page to prevent form resubmission on refresh
    header("Location: ContractorAssignTasks.php");
    exit();
}

// Fetch Employees who are managed by the contractor
$employees = $connection->query("SELECT * FROM users WHERE Role = 'Employee' AND managed_by_contractor_id = $contractor_id");

// Fetch the number of tasks assigned to each employee
$task_counts = [];
// Use user_id instead of employee_id to match the database schema
$result = $connection->query("SELECT user_id, COUNT(*) as task_count FROM task_assignments GROUP BY user_id");
while ($row = $result->fetch_assoc()) {
    $task_counts[$row['user_id']] = $row['task_count'];
}

// Fetch the actual tasks assigned to each employee
$employee_tasks = [];
$tasks_query = $connection->query("
    SELECT ta.user_id, t.task_name, p.project_name 
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.task_id
    JOIN projects p ON ta.project_id = p.project_id
    ORDER BY ta.user_id, t.task_name
");

while ($row = $tasks_query->fetch_assoc()) {
    if (!isset($employee_tasks[$row['user_id']])) {
        $employee_tasks[$row['user_id']] = [];
    }
    $employee_tasks[$row['user_id']][] = [
        'task_name' => $row['task_name'],
        'project_name' => $row['project_name']
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Tasks to Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --card-border-radius: 0.75rem;
            --border-radius-sm: 0.375rem;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            border-radius: var(--card-border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }
        
        .page-header h1 {
            font-weight: 600;
            margin: 0;
            font-size: 1.75rem;
            position: relative;
            z-index: 1;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
            color: rgba(255, 255, 255, 0.9);
        }
        
        .back-button i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }
        
        .card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-container {
            border-radius: var(--card-border-radius);
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: var(--border-radius-sm);
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: var(--border-radius-sm);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--warning-color);
        }
        
        .task-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            margin: 0.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .task-badge:hover {
            background-color: rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        .task-icon {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }
        
        .task-count {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .task-count .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .task-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem
            .task-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .view-all-btn {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .view-all-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .no-tasks {
            color: var(--gray-color);
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .popover {
            border-radius: var(--border-radius-sm);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            max-width: 300px;
        }
        
        .popover-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border: none;
        }
        
        .popover-body {
            padding: 1rem;
        }
        
        .task-item-popover {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .task-item-popover i {
            color: var(--success-color);
            margin-right: 0.5rem;
        }
        
        .task-project-popover {
            font-size: 0.75rem;
            color: var(--gray-color);
            margin-top: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 1.25rem;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .card-header, .card-body {
                padding: 1.25rem;
            }
            
            .table thead th {
                padding: 0.75rem;
            }
            
            .table tbody td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <a href="Contractordashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1><i class="fas fa-tasks me-2"></i> Assign Tasks to Employees</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> d-flex align-items-center">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex align-items-center">
                <i class="fas fa-users me-2"></i>
                <span>Employee Overview</span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Assigned Tasks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($employees->num_rows > 0): ?>
                                <?php while ($employee = $employees->fetch_assoc()): ?>
                                    <?php
                                    // Get the number of tasks assigned to the employee
                                    $task_count = isset($task_counts[$employee['UserID']]) ? $task_counts[$employee['UserID']] : 0;
                                    $has_tasks = isset($employee_tasks[$employee['UserID']]) && !empty($employee_tasks[$employee['UserID']]);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($employee['UserID']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?= strtoupper(substr($employee['FirstName'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($employee['Email']) ?></td>
                                        <td>
                                            <?php if ($task_count > 0): ?>
                                                <div class="task-count">
                                                    <span class="badge bg-primary"><?= $task_count ?></span>
                                                    <span>Task<?= $task_count > 1 ? 's' : '' ?> Assigned</span>
                                                </div>
                                                <?php if ($has_tasks): ?>
                                                    <div class="task-list">
                                                        <?php foreach(array_slice($employee_tasks[$employee['UserID']], 0, 2) as $task): ?>
                                                            <div class="task-badge">
                                                                <i class="fas fa-check-circle task-icon"></i>
                                                                <?= htmlspecialchars($task['task_name']) ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        
                                                        <?php if (count($employee_tasks[$employee['UserID']]) > 2): ?>
                                                            <button type="button" class="view-all-btn" 
                                                                    data-bs-toggle="popover" 
                                                                    title="All Assigned Tasks" 
                                                                    data-bs-html="true"
                                                                    data-bs-content="<?php 
                                                                        $content = '<div class=\'task-popover-list\'>';
                                                                        foreach($employee_tasks[$employee['UserID']] as $task) {
                                                                            $content .= '<div class=\'task-item-popover\'>';
                                                                            $content .= '<i class=\'fas fa-check-circle\'></i>';
                                                                            $content .= '<div>';
                                                                            $content .= '<div><strong>' . htmlspecialchars($task['task_name']) . '</strong></div>';
                                                                            $content .= '<div class=\'task-project-popover\'>' . htmlspecialchars($task['project_name']) . '</div>';
                                                                            $content .= '</div></div>';
                                                                        }
                                                                        $content .= '</div>';
                                                                        echo $content;
                                                                    ?>">
                                                                <i class="fas fa-eye me-1"></i> View all <?= count($employee_tasks[$employee['UserID']]) ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="no-tasks">No tasks assigned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-user-slash fs-3 text-muted mb-3"></i>
                                            <p class="mb-0">No employees found under your management</p>
                                            <p class="text-muted small">Employees must be assigned to you first</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center">
                <i class="fas fa-plus-circle me-2"></i>
                <span>Assign New Task</span>
            </div>
            <div class="card-body">
                <form method="POST" action="ContractorAssignTasks.php">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Select Employee</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">-- Select Employee --</option>
                                <?php
                                // Fetch Employees again to populate the select options
                                $employees = $connection->query("SELECT * FROM users WHERE Role = 'Employee' AND managed_by_contractor_id = $contractor_id");
                                while ($employee = $employees->fetch_assoc()) {
                                    echo '<option value="' . $employee['UserID'] . '">' . htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="project_id" class="form-label">Select Project</label>
                            <select name="project_id" class="form-select" required>
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $id => $name): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="task_id" class="form-label">Select Task Type</label>
                        <select name="task_id" class="form-select" required id="task_type_select" onchange="updateTaskDescription()">
                            <option value="">-- Select Task Type --</option>
                            <option value="Material Handling">Material Handling</option>
                            <option value="Debris Cleanup">Debris Cleanup</option>
                            <option value="Digging">Digging</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="task_description" class="form-label" require>Task Description</label>
                        <textarea class="form-control" id="task_description" name="task_description" rows="3" placeholder="Enter detailed instructions for this task..." ></textarea>
                        <div class="form-text" id="description_help">Provide clear instructions about what needs to be done.</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="assign_task" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Assign Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize popovers
        document.addEventListener('DOMContentLoaded', function() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    trigger: 'focus',
                    placement: 'left'
                });
            });
            
            // Add avatar circle styling
            const avatarCircles = document.querySelectorAll('.avatar-circle');
            avatarCircles.forEach(circle => {
                circle.style.width = '32px';
                circle.style.height = '32px';
                circle.style.borderRadius = '50%';
                circle.style.display = 'flex';
                circle.style.alignItems = 'center';
                circle.style.justifyContent = 'center';
                circle.style.fontWeight = 'bold';
            });
        });
        
        // Update task description based on selection
        function updateTaskDescription() {
            const taskSelect = document.getElementById('task_type_select');
            const descriptionField = document.getElementById('task_description');
            const taskDescriptions = {
                'Material Handling': 'Transporting and organizing materials on the construction site.',
                'Debris Cleanup': 'Cleaning up construction debris, including waste disposal.',
                'Digging': 'Excavation and digging for foundations or trenches.',
                'Other': 'Please provide detailed description for this task.'
            };
            
            const selectedTask = taskSelect.value;
            if (selectedTask && taskDescriptions[selectedTask]) {
                descriptionField.value = taskDescriptions[selectedTask];
            }
        }
    </script>
</body>
</html>

<?php $connection->close(); ?>
