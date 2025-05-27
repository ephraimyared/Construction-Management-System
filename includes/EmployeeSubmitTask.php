<?php
/**
 * Employee Report Preparation System
 * 
 * This file allows employees to prepare and submit reports related to contractor tasks.
 */

// Start session to maintain user state
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
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
    $sql = "SELECT UserID, FirstName, LastName FROM users WHERE Role = 'Contractor' ORDER BY FirstName, LastName";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $contractors[] = $row;
        }
    }
    
    return $contractors;
}

/**
 * Get list of projects from the database
 * 
 * @param mysqli $conn Database connection
 * @return array List of projects
 */
function getProjects($conn) {
    $projects = [];
    $sql = "SELECT project_id, project_name FROM projects ORDER BY project_name";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
    }
    
    return $projects;
}

/**
 * Get tasks assigned to a specific contractor
 * 
 * @param int $contractorId Contractor ID
 * @param mysqli $conn Database connection
 * @return array List of tasks
 */
function getContractorTasks($contractorId, $conn) {
    $tasks = [];
    $contractorId = (int)$contractorId;
    
    $sql = "SELECT t.task_id, t.task_name, t.status, p.project_name, t.start_date, t.end_date 
            FROM tasks t
            JOIN projects p ON t.project_id = p.project_id
            WHERE t.assigned_to = $contractorId
            ORDER BY t.end_date DESC";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
    
    return $tasks;
}

/**
 * Submit a report to the database
 * 
 * @param array $reportData Report information
 * @param mysqli $conn Database connection
 * @return bool|string True on success, error message on failure
 */
function submitReport($reportData, $conn) {
    // Sanitize inputs
    $title = $conn->real_escape_string($reportData['title']);
    $content = $conn->real_escape_string($reportData['content']);
    $reportType = $conn->real_escape_string($reportData['report_type']);
    $projectId = isset($reportData['project_id']) ? (int)$reportData['project_id'] : 'NULL';
    $createdBy = (int)$reportData['created_by'];
    
    // Handle file upload if present
    $attachmentPath = '';
    if (isset($reportData['attachment']) && $reportData['attachment']['error'] == 0) {
        $uploadDir = '../uploads/reports/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($reportData['attachment']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        // Move uploaded file to target directory
        if (move_uploaded_file($reportData['attachment']['tmp_name'], $targetFilePath)) {
            $attachmentPath = $targetFilePath;
        } else {
            return "Error uploading file.";
        }
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO reports (created_by, report_type, title, content, project_id, submitted_by, attachment) 
            VALUES ($createdBy, '$reportType', '$title', '$content', $projectId, $createdBy, '$attachmentPath')";
    
    // Execute query
    if ($conn->query($sql) === TRUE) {
        return $conn->insert_id;
    } else {
        return "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Process form submission
$message = '';
$messageType = '';
$selectedContractorId = '';
$contractorTasks = [];

// Get database connection
$conn = connectToDatabase($dbConfig);

// Get contractors and projects for dropdowns
$contractors = getContractors($conn);
$projects = getProjects($conn);

// Handle contractor selection to show their tasks
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['contractor_id'])) {
    $selectedContractorId = (int)$_GET['contractor_id'];
    $contractorTasks = getContractorTasks($selectedContractorId, $conn);
}

// Handle report submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_report'])) {
    // Prepare report data
    $reportData = [
        'title' => $_POST['title'],
        'content' => $_POST['content'],
        'report_type' => $_POST['report_type'],
        'project_id' => $_POST['project_id'],
        'created_by' => $_SESSION['user_id']
    ];
    
    // Handle file upload if present
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $reportData['attachment'] = $_FILES['attachment'];
    }
    
    // Submit report
    $result = submitReport($reportData, $conn);
    
    if (is_numeric($result)) {
        $message = "Report submitted successfully!";
        $messageType = "success";
    } else {
        $message = $result; // Error message
        $messageType = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Report Preparation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
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
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 150px;
        }
        button, .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover {
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f2f2f2;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background-color: #4CAF50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Employee Report Preparation</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('contractor-tasks')">Contractor Tasks</div>
            <div class="tab" onclick="showTab('prepare-report')">Prepare Report</div>
        </div>
        
        <div id="contractor-tasks" class="tab-content active">
            <h2>View Contractor Tasks</h2>
            
            <div class="form-group">
                <label for="contractor_id">Select Contractor:</label>
                <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <select id="contractor_id" name="contractor_id" onchange="this.form.submit()">
                        <option value="">Select a contractor</option>
                        <?php foreach ($contractors as $contractor): ?>
                            <option value="<?php echo $contractor['UserID']; ?>" <?php echo ($selectedContractorId == $contractor['UserID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($contractor['FirstName'] . ' ' . $contractor['LastName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if (!empty($contractorTasks)): ?>
                <h3>Tasks for Selected Contractor</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Task ID</th>
                            <th>Task Name</th>
                            <th>Project</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contractorTasks as $task): ?>
                            <tr>
                                <td><?php echo $task['task_id']; ?></td>
                                <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                                <td><?php echo htmlspecialchars($task['project_name']); ?></td>
                                <td><?php echo $task['start_date']; ?></td>
                                <td><?php echo $task['end_date']; ?></td>
                                <td><?php echo $task['status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($selectedContractorId): ?>
                <p>No tasks found for this contractor.</p>
            <?php endif; ?>
        </div>
        
        <div id="prepare-report" class="tab-content">
            <h2>Prepare New Report</h2>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="report_type">Report Type: <span class="required">*</span></label>
                    <select id="report_type" name="report_type" required>
                        <option value="">Select report type</option>
                        <option value="Progress">Progress Report</option>
                        <option value="Financial">Financial Report</option>
                        <option value="Issue">Issue Report</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="title">Report Title: <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Report Content: <span class="required">*</span></label>
                    <textarea id="content" name="content" required></textarea>
                    <div class="help-text">Provide detailed information about the report</div>
                </div>
                
                <div class="form-group">
                    <label for="project_id">Related Project: <span class="required">*</span></label>
                    <select id="project_id" name="project_id" required>
                        <option value="">Select a project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                                <div class="form-group">
                    <label for="attachment">Attachment (Optional):</label>
                    <input type="file" id="attachment" name="attachment">
                    <div class="help-text">Upload any supporting documents (PDF, images, etc.)</div>
                </div>
                
                <button type="submit" name="submit_report">Submit Report</button>
            </form>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="../employee_dashboard.php" class="btn" style="background-color: #6c757d;">Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to the clicked tab
            const clickedTab = Array.from(tabs).find(tab => tab.textContent.toLowerCase().includes(tabId.replace('-', ' ')));
            if (clickedTab) {
                clickedTab.classList.add('active');
            }
        }
        
        // Initialize date inputs with today's date
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any initialization code here if needed
        });
    </script>
</body>
</html>

               