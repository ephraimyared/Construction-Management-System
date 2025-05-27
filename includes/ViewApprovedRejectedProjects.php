<?php
session_start();
include '../db_connection.php';

// Check if user is logged in with appropriate role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch projects with their approval status and comments, filtered by the current manager
$query = "
SELECT p.project_id, p.project_name, p.description, p.start_date, p.end_date, 
       p.decision_status, p.admin_comment,
       u.FirstName, u.LastName
FROM projects p
JOIN users u ON p.manager_id = u.UserID
WHERE p.decision_status IN ('Approved', 'Rejected')
AND p.manager_id = ?
ORDER BY p.project_id DESC
";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Approved/Rejected Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
            color: #333;
        }
        
        .container {
            position: relative;
            padding-top: 20px;
            max-width: 1200px;
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            padding: 10px 18px;
            background: linear-gradient(145deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            font-weight: 500;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background: linear-gradient(145deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
            color: white;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px 30px;
            border-radius: var(--border-radius);
            margin: 30px 0;
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        
        .table {
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 30px;
            background-color: white;
        }
        
        .table thead {
            background: linear-gradient(145deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .badge-approved {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border: 1px solid rgba(39, 174, 96, 0.2);
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 30px;
        }
        
        .badge-rejected {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.2);
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 30px;
        }
        
        .admin-comment {
            background-color: #f8f9fa;
            border-left: 3px solid var(--accent);
            padding: 10px 15px;
            border-radius: 5px;
            font-style: italic;
            color: #555;
        }
        
        .no-projects {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            margin: 20px 0;
            color: #6c757d;
        }
        
        .no-projects i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d1d1;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="back-button">
        <a href="ManageProjects.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <h2 class="page-header">My Approved/Rejected Projects</h2>

    <?php if ($projects->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Project Name</th>
                <th>Manager</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $projects->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['project_name']) ?></td>
                <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                <td>
                    <?php if ($row['decision_status'] === 'Approved'): ?>
                        <span class="badge badge-approved">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-rejected">Rejected</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailModal"
                        data-project='<?= json_encode($row) ?>'>View Detail</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-projects">
        <i class="fas fa-folder-open"></i>
        <h4>No approved or rejected projects found</h4>
        <p>You don't have any projects that have been approved or rejected yet.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Project Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Project Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="detail-label">Project Name</div>
            <div class="detail-value" id="modal-project-name"></div>
          </div>
          <div class="col-md-6">
            <div class="detail-label">Manager</div>
            <div class="detail-value" id="modal-manager"></div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="detail-label">Start Date</div>
            <div class="detail-value" id="modal-start-date"></div>
          </div>
          <div class="col-md-6">
            <div class="detail-label">End Date</div>
            <div class="detail-value" id="modal-end-date"></div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-12">
            <div class="detail-label">Description</div>
            <div class="detail-value" id="modal-description"></div>
          </div>
        </div>
        <div class="row mb-3">
         
        </div>
        <div class="row mb-3">
          <div class="col-12">
            <div class="detail-label">Admin Comment</div>
            <div class="detail-value" id="modal-admin-comment"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Handle project details modal
  const detailModal = document.getElementById('detailModal');
  detailModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const projectData = JSON.parse(button.getAttribute('data-project'));
    
    // Update modal content
    document.getElementById('modal-project-name').textContent = projectData.project_name;
    document.getElementById('modal-manager').textContent = projectData.FirstName + ' ' + projectData.LastName;
    document.getElementById('modal-start-date').textContent = new Date(projectData.start_date).toLocaleDateString();
    document.getElementById('modal-end-date').textContent = new Date(projectData.end_date).toLocaleDateString();
    document.getElementById('modal-description').textContent = projectData.description;
    
  
    // Display admin comment
    const adminCommentContainer = document.getElementById('modal-admin-comment');
    if (projectData.admin_comment) {
      adminCommentContainer.innerHTML = `<div class="admin-comment">${projectData.admin_comment}</div>`;
    } else {
      adminCommentContainer.innerHTML = '<em class="text-muted">No comment provided</em>';
    }
  });
</script>
</body>
</html>
<?php $connection->close(); ?>
