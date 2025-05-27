<?php
/**
 * Employee Report Preparation System
 * 
 * This file allows employees to prepare reports for contractors.
 * The prepared reports will be displayed on ContractorGenerateReport.php.
 */

// Start session to maintain user state
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Employee') {
    // Redirect to login page if not logged in as employee
    header("Location: ../login.php");
    exit;
}

// Database connection configuration
$dbConfig = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'cms'
];

/**
 * Connect to the database
 * 
 * @param array $config Database configuration
 * @return mysqli Database connection
 */
function connectToDatabase($config) {
    $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

/**
 * Get list of contractors from the database
 * 
 * @param mysqli $conn Database connection
 * @return array List of contractors
 */
function getContractors($conn) {
    $contractors = [];
    $sql = "SELECT UserID, FirstName, LastName, Email FROM users WHERE Role = 'Contractor' ORDER BY FirstName, LastName";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $contractors[] = $row;
        }
    }
    
    return $contractors;
}

/**
 * Get list of tasks assigned to the employee from the database
 * 
 * @param mysqli $conn Database connection
 * @param int $employeeId The ID of the current employee
 * @return array List of tasks
 */
function getAssignedTasks($conn, $employeeId) {
    $tasks = [];
    
    // Query to get tasks assigned to this employee by contractors
    $sql = "SELECT t.task_id, t.task_name, t.description, p.project_id, p.project_name, 
                   u.FirstName as contractor_first_name, u.LastName as contractor_last_name 
            FROM tasks t 
            JOIN projects p ON t.project_id = p.project_id 
            JOIN users u ON t.assigned_by = u.UserID 
            WHERE t.assigned_to = ? AND u.Role = 'Contractor' 
            ORDER BY t.task_id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
    
    return $tasks;
}

/**
 * Submit a task report to the database
 * 
 * @param array $taskData Task information
 * @param mysqli $conn Database connection
 * @return bool|string Task ID on success, error message on failure
 */
function submitTaskReport($taskData, $conn) {
    // Sanitize inputs
    $taskId = (int)$taskData['task_id'];
    $taskDescription = $conn->real_escape_string($taskData['task_description']);
    $hoursSpent = (float)$taskData['hours_spent'];
    $dateCompleted = $conn->real_escape_string($taskData['date_completed']);
    $employeeId = (int)$taskData['employee_id'];
    
    // Get task details
    $stmt = $conn->prepare("SELECT project_id, task_name, assigned_by FROM tasks WHERE task_id = ?");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return "Error: Task not found";
    }
    
    $taskDetails = $result->fetch_assoc();
    $projectId = $taskDetails['project_id'];
    $taskName = $taskDetails['task_name'];
    $contractorId = $taskDetails['assigned_by'];
    
    // Update task status to completed
    $updateSql = "UPDATE tasks SET status = 'Completed', description = ? WHERE task_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $taskDescription, $taskId);
    
    if ($stmt->execute()) {
        // Insert task completion details
        $sql2 = "INSERT INTO task_assignments (task_id, user_id, contractor_id, project_id, task_name, task_description, status, employee_id, start_date, end_date, hours_spent) 
                VALUES (?, ?, ?, ?, ?, ?, 'Completed', ?, NOW(), ?, ?)";
        
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("iiissisd", $taskId, $employeeId, $contractorId, $projectId, $taskName, $taskDescription, $employeeId, $dateCompleted, $hoursSpent);
        
        if ($stmt2->execute()) {
            return $taskId;
        } else {
            return "Error creating task assignment: " . $conn->error;
        }
    } else {
        return "Error updating task: " . $conn->error;
    }
}

// Process form submission
$message = '';
$messageType = '';

// Get database connection
$conn = connectToDatabase($dbConfig);

// Get employee ID from session
$employeeId = $_SESSION['user_id'];

// Get contractors and tasks for dropdowns
$contractors = getContractors($conn);
$assignedTasks = getAssignedTasks($conn, $employeeId);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_report'])) {
    // Validate date - ensure it's not before today
    $dateCompleted = $_POST['date_completed'];
    $today = date('Y-m-d');
    
    if ($dateCompleted < $today) {
        $message = "Error: Date Completed cannot be before today.";
        $messageType = "error";
    } else {
        // Prepare task data
        $taskData = [
            'task_id' => $_POST['task_id'],
            'task_description' => $_POST['task_description'],
            'hours_spent' => $_POST['hours_spent'],
            'date_completed' => $dateCompleted,
            'employee_id' => $_SESSION['user_id']
        ];
        
        // Submit task report
        $result = submitTaskReport($taskData, $conn);
        
        if (is_numeric($result)) {
            $message = "Task report submitted successfully!";
            $messageType = "success";
            
            // Redirect to contractor report generation page
            header("Location: ContractorGenerateReport.php?task_id=" . $result);
            exit;
        } else {
            $message = $result; // Error message
            $messageType = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prepare Task Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .required {
            color: red;
        }
        .help-text {
            font-size: 0.8em;
            color: #666;
            margin-top: 2px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .no-tasks {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .task-info {
            margin-top: 5px;
            font-size: 0.85em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="EmployeeDashboard.php" class="back-link">&larr; Back to Dashboard</a>
        
        <h1>Prepare Task Report for Contractor</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($assignedTasks)): ?>
            <div class="no-tasks">
                <p><strong>No assigned tasks found.</strong></p>
                <p>You don't have any tasks assigned by contractors yet. Please contact your contractor for task assignments.</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="reportForm">
                <div class="form-group">
                    <label for="task_id">Select Task: <span class="required">*</span></label>
                    <select id="task_id" name="task_id" required onchange="updateTaskDescription()">
                        <option value="">-- Select an assigned task --</option>
                        <?php foreach ($assignedTasks as $task): ?>
                            <option value="<?php echo $task['task_id']; ?>" 
                                    data-description="<?php echo htmlspecialchars($task['description']); ?>">
                                <?php echo htmlspecialchars($task['task_name']); ?> 
                                (Project: <?php echo htmlspecialchars($task['project_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="task-info" id="task-info"></div>
                    <div class="help-text">Only tasks assigned to you by contractors are shown</div>
                </div>
                
                <div class="form-group">
                    <label for="task_description">Task Completion Details: <span class="required">*</span></label>
                    <textarea id="task_description" name="task_description" required></textarea>
                    <div class="help-text">Provide details about how you completed the task, any challenges faced, and the results achieved</div>
                </div>
                
                <div class="form-group">
                    <label for="hours_spent">Hours Spent: <span class="required">*</span></label>
                    <input type="number" id="hours_spent" name="hours_spent" step="0.5" min="0.5" required>
                    <div class="help-text">Enter the number of hours spent on this task</div>
                </div>
                
                <div class="form-group">
                    <label for="date_completed">Date Completed: <span class="required">*</span></label>
                    <input type="date" id="date_completed" name="date_completed" required>
                    <div class="help-text">Date must be today or in the future</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="submit_report">Submit Report</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div style="margin-top: 20px; text-align: center;">
            <p>After submitting the report, it will be automatically generated and sent to the contractor.</p>
        </div>
    </div>
    
    <script>
        // Set default date to today and min attribute to prevent selecting past dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateCompletedInput = document.getElementById('date_completed');
            if (dateCompletedInput) {
                dateCompletedInput.value = today;
                dateCompletedInput.min = today; // This prevents selecting dates before today
            }
        });
        
        // Update task description when a task is selected
        function updateTaskDescription() {
            const taskSelect = document.getElementById('task_id');
            const taskDescription = document.getElementById('task_description');
            const taskInfo = document.getElementById('task-info');
            
            if (taskSelect.value) {
                const selectedOption = taskSelect.options[taskSelect.selectedIndex];
                const description = selectedOption.getAttribute('data-description');
                
                // Set initial description if available
                if (description && taskDescription.value === '') {
                    taskDescription.value = "I completed the task as assigned. " + description;
                }
                                // Show task info
                const projectInfo = selectedOption.textContent;
                taskInfo.innerHTML = `<strong>Selected Task:</strong> ${projectInfo}`;
                taskInfo.style.display = 'block';
            } else {
                taskInfo.style.display = 'none';
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const reportForm = document.getElementById('reportForm');
            
            if (reportForm) {
                reportForm.addEventListener('submit', function(event) {
                    const taskSelect = document.getElementById('task_id');
                    const taskDescription = document.getElementById('task_description');
                    const hoursSpent = document.getElementById('hours_spent');
                    
                    let isValid = true;
                    
                    // Validate task selection
                    if (!taskSelect.value) {
                        isValid = false;
                        alert('Please select a task');
                    }
                    
                    // Validate task description
                    if (!taskDescription.value.trim()) {
                        isValid = false;
                        alert('Please provide task completion details');
                    }
                    
                    // Validate hours spent
                    if (!hoursSpent.value || hoursSpent.value < 0.5) {
                        isValid = false;
                        alert('Please enter valid hours spent (minimum 0.5)');
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>

