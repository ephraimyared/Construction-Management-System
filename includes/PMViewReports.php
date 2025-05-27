<?php
session_start();
include '../db_connection.php';

// Ensure the user is a Project Manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../login.php");
    exit();
}

$project_manager_id = $_SESSION['user_id'];

// Determine which report type to display based on GET parameter
$view_type = $_GET['type'] ?? 'contractor'; // default to contractor

// Map view_type to the actual role name in the database
switch($view_type) {
    case 'consultant':
        $role_name = 'Consultant';
        break;
    case 'site_engineer':
        $role_name = 'Site Engineer';
        break;
    case 'contractor':
    default:
        $role_name = 'Contractor';
        break;
}

// Fetch reports based on selected role - UPDATED to include file_attachment
$stmt = $connection->prepare("
    SELECT r.report_id, r.title, r.report_type, r.content, r.created_at, r.file_attachment,
            u.FirstName, u.LastName,
            p.project_name, p.project_id
    FROM reports r
    JOIN projects p ON r.project_id = p.project_id
    JOIN users u ON r.created_by = u.UserID
    WHERE p.manager_id = ? AND u.Role = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("is", $project_manager_id, $role_name);
$stmt->execute();
$result = $stmt->get_result();

// Get count of reports by type for the selected role
$report_types_query = "
    SELECT r.report_type, COUNT(*) as count
    FROM reports r
    JOIN projects p ON r.project_id = p.project_id
    JOIN users u ON r.created_by = u.UserID
    WHERE p.manager_id = ? AND u.Role = ?
    GROUP BY r.report_type
";
$types_stmt = $connection->prepare($report_types_query);
$types_stmt->bind_param("is", $project_manager_id, $role_name);
$types_stmt->execute();
$types_result = $types_stmt->get_result();

$report_type_counts = [];
while ($type_row = $types_result->fetch_assoc()) {
    $report_type_counts[$type_row['report_type']] = $type_row['count'];
}

// Get projects managed by this manager for filtering
$projects_query = "SELECT project_id, project_name FROM projects WHERE manager_id = ? ORDER BY project_name";
$projects_stmt = $connection->prepare($projects_query);
$projects_stmt->bind_param("i", $project_manager_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();

$projects = [];
while ($project = $projects_result->fetch_assoc()) {
    $projects[$project['project_id']] = $project['project_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Reports | Project Manager Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --border-radius: 10px;
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: #333;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }
        
        .header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.75rem;
            position: relative;
        }
        
        .header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            position: relative;
        }
        
        .back-button {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .filters-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 0;
            margin-right: 0.5rem;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            border-bottom: 3px solid var(--secondary-color);
            font-weight: 600;
        }
        
        .report-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            flex: 1;
            min-width: 150px;
            text-align: center;
            border-left: 4px solid var(--secondary-color);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .reports-table {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            border-top: none;
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table tr {
            transition: var(--transition);
        }
        
        .table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .report-title {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .report-project {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .report-type {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .report-type-progress {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }
        
        .report-type-incident {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .report-type-inspection {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .report-type-material {
            background-color: rgba(52, 73, 94, 0.1);
            color: var(--dark-color);
        }
        
        .report-type-quality {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }
        
        /* Add styles for additional report types */
        .report-type-issue {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .report-type-financial {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .report-type-completion {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }
        
        .report-type-other {
            background-color: rgba(52, 73, 94, 0.1);
            color: var(--dark-color);
        }
        
        /* File attachment styling - added for new feature */
        .file-attachment {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 4px;
            color: var(--secondary-color);
            font-size: 0.85rem;
            text-decoration: none;
        }
        
        .file-attachment i {
            margin-right: 5px;
        }
        
        .file-attachment:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="ManagerDashboard.php" class="btn back-button mb-3">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <h2><i class="fas fa-folder-open me-2"></i>Project Reports</h2>
        <p>Review and manage reports submitted by your project team members</p>
    </div>
    
    <div class="filters-section">
        <!-- Role Tabs -->
        <ul class="nav nav-tabs" id="roleTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $view_type === 'contractor' ? 'active' : '' ?>" href="?type=contractor">
                    <i class="fas fa-hard-hat me-2"></i>Contractor Reports
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $view_type === 'consultant' ? 'active' : '' ?>" href="?type=consultant">
                    <i class="fas fa-user-tie me-2"></i>Consultant Reports
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $view_type === 'site_engineer' ? 'active' : '' ?>" href="?type=site_engineer">
                    <i class="fas fa-hard-hat me-2"></i>Site Engineer Reports
                </a>
            </li>
        </ul>
        <!-- Fix the Action button (around line 380) -->
<td data-label="Action">
    <a href="<?php 
        if ($view_type === 'consultant') {
            echo 'ConsultantViewReportById.php';
        } elseif ($view_type === 'site_engineer') {
            echo 'ViewSiteEngineerReport.php'; // Update this to the correct filename
        } else {
            echo 'ContractorViewReportById.php';
        }
    ?>?report_id=<?= $row['report_id'] ?>" class="btn btn-view">
        <i class="fas fa-eye me-1"></i> View Report
    </a>
</td>

        <!-- Report Statistics -->
        <div class="report-stats">
            <div class="stat-card">
                <div class="stat-value"><?= $result->num_rows ?></div>
                <div class="stat-label">Total Reports</div>
            </div>
            
            <?php foreach ($report_type_counts as $type => $count): ?>
                <div class="stat-card">
                    <div class="stat-value"><?= $count ?></div>
                    <div class="stat-label"><?= ucfirst($type) ?> Reports</div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Search and Filter -->
        <div class="row">
            <div class="col-md-6">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="reportSearch" class="form-control" placeholder="Search reports...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="filter-dropdown">
                    <select id="projectFilter" class="form-select">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $id => $name): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reports Table -->
    <div class="reports-table">
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-hover" id="reportsTable">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Report Title</th>
                        <th><?= ucfirst($role_name) ?></th>
                        <th>Submitted On</th>
                        <th>Attachment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-project="<?= $row['project_id'] ?>">
                            <td data-label="Project"><?= htmlspecialchars($row['project_name']) ?></td>
                            <td data-label="Report Title"><?= htmlspecialchars($row['title']) ?></td>
                            <td data-label="<?= ucfirst($role_name) ?>"><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                            <td data-label="Submitted On"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td data-label="Attachment">
                                <?php if (!empty($row['file_attachment'])): ?>
                                    <a href="../<?= htmlspecialchars($row['file_attachment']) ?>" class="file-attachment" target="_blank">
                                        <i class="fas fa-file-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No file</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Action">
                                <a href="<?= $view_type === 'consultant' ? 'ConsultantViewReportById.php' : ($view_type === 'site_engineer' ? 'SiteEngineerViewReportById.php' : 'ContractorViewReportById.php') ?>?report_id=<?= $row['report_id'] ?>" class="btn btn-view">
                                    <i class="fas fa-eye me-1"></i> View Report
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>No Reports Found</h3>
                <p>There are no <?= $role_name ?> reports submitted yet.</p>
                <a href="ManagerDashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Search functionality
    document.getElementById('reportSearch').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.getElementById('reportsTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length - 1; j++) { // Skip the action column
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(searchValue)) {
                    found = true;
                    break;
                }
            }
            
            rows[i].style.display = found ? '' : 'none';
        }
    });
    
    // Project filter
    document.getElementById('projectFilter').addEventListener('change', function() {
        const projectId = this.value;
        const table = document.getElementById('reportsTable');
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            if (!projectId) {
                rows[i].style.display = '';
                continue;
            }
            
            const rowProjectId = rows[i].getAttribute('data-project');
            
            if (rowProjectId === projectId) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    });
</script>

</body>
</html>

<?php
// Close database connections
if (isset($stmt)) $stmt->close();
if (isset($types_stmt)) $types_stmt->close();
if (isset($projects_stmt)) $projects_stmt->close();
$connection->close();
?>