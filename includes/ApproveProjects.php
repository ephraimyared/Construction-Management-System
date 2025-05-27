<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$successMessage = '';
if (isset($_SESSION['success_action'])) {
    $successMessage = "Successfully " . $_SESSION['success_action'];
    unset($_SESSION['success_action']);
}

$query = "
SELECT p.project_id, p.project_name, p.description, p.start_date, p.end_date, p.attachment, 
       p.decision_status, u.FirstName, u.LastName
FROM projects p
JOIN users u ON p.manager_id = u.UserID
ORDER BY p.project_id DESC
";
$projects = $connection->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Projects</title>
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
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px 30px;
            border-radius: var(--border-radius);
            margin: 30px 0;
            box-shadow: var(--box-shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }
        
        .page-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .table {
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 30px;
            background-color: white;
            width: 100%;
            table-layout: fixed;
        }
        
        .table thead {
            background: linear-gradient(145deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        
        .table tbody tr {
            transition: var(--transition);
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #eee;
            word-wrap: break-word;
        }
        
        /* Column widths */
        .table th:nth-child(1), .table td:nth-child(1) { width: 20%; } /* Project Name */
        .table th:nth-child(2), .table td:nth-child(2) { width: 15%; } /* Manager */
        .table th:nth-child(3), .table td:nth-child(3) { width: 15%; } /* Description */
        .table th:nth-child(4), .table td:nth-child(4) { width: 20%; } /* Attachment */
        .table th:nth-child(5), .table td:nth-child(5) { width: 15%; } /* Status */
        .table th:nth-child(6), .table td:nth-child(6) { width: 15%; } /* Actions */
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
            border-radius: 30px;
            padding: 8px 16px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-success:hover {
            background-color: #219d54;
            border-color: #219d54;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.2);
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
            border-radius: 30px;
            padding: 8px 16px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2);
        }
        
        .btn-outline-info {
            color: var(--info);
            border-color: var(--info);
            border-radius: 30px;
            padding: 8px 16px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-outline-info:hover {
            background-color: var(--info);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.2);
        }
        
        .modal-header {
            background: linear-gradient(145deg, var(--accent), #2980b9);
            color: white;
            border: none;
            padding: 15px 20px;
        }
        
        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 15px 20px;
        }
        
        .badge {
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 30px;
            display: inline-block;
            width: auto;
        }
        
        .badge-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
            border: 1px solid rgba(243, 156, 18, 0.2);
        }
        
        .badge-approved {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .badge-rejected {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }
        
        .badge-seen {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--info);
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .badge-unseen {
            background-color: rgba(155, 89, 182, 0.1);
            color: #8e44ad;
            border: 1px solid rgba(155, 89, 182, 0.2);
        }
        
        .alert {
            border-radius: var(--border-radius);
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .detail-value {
            margin-bottom: 15px;
            color: #555;
        }
        
        textarea.form-control {
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 12px;
            transition: var(--transition);
            min-height: 150px; /* Increased height for comment box */
        }
        
        textarea.form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .attachment-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .attachment-link i {
            margin-right: 5px;
        }
        
        .no-attachment {
            color: #999;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            .container {
                padding-top: 60px;
            }
            
            .back-button {
                top: 10px;
                left: 10px;
            }
            
            .back-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .page-header {
                padding: 20px;
                margin: 20px 0;
            }
            
            .page-header h2 {
                font-size: 1.5rem;
            }
            
            .table thead th, 
            .table tbody td {
                padding: 10px;
            }
            
            .badge {
                padding: 5px 10px;
                font-size: 0.7rem;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="back-button">
        <a href="AdminDashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <h2 class="page-header">Project Tasks For Approval</h2>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= $successMessage ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Project Name</th>
                    <th>Manager</th>
                    <th>Description</th>
                    <th>Attachment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $projects->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['project_name']) ?></td>
                    <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailModal"
                            data-project='<?= json_encode($row) ?>'>View Detail</button>
                    </td>
                    <td>
                        <?php if (!empty($row['attachment'])): ?>
                            <a href="../<?= htmlspecialchars($row['attachment']) ?>" class="attachment-link" target="_blank">
                                <i class="fas fa-file-alt"></i> View Document
                            </a>
                        <?php else: ?>
                            <span class="no-attachment">No attachment</span>
                        <?php endif; ?>
                    </td>
                    <td>
                         <?php if ($row['decision_status'] === 'Approved'): ?>
                            <span class="badge badge-approved">Approved</span>
                        <?php elseif ($row['decision_status'] === 'Rejected'): ?>
                            <span class="badge badge-rejected">Rejected</span>
                        <?php elseif ($row['decision_status']): ?>
                            <span class="badge badge-seen">Seen</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['decision_status'] === 'Approved' || $row['decision_status'] === 'Rejected'): ?>
                            <span class="badge badge-seen">Seen</span>
                        <?php else: ?>
                            <span class="badge badge-unseen">Unseen</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Project Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="SaveProjectCommentAndDecision.php" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Project Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="project_id" id="modal-project-id">
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
          <div class="col-12">
            <div class="detail-label">Project Attachment</div>
            <div class="detail-value" id="modal-attachment"></div>
          </div>
        </div>
        <div class="row mb-4" id="comment-section">
          <div class="col-12">
            <label for="admin-comment" class="form-label">Admin Comment</label>
            <textarea class="form-control" id="admin-comment" name="admin_comment" rows="6" placeholder="Add your detailed comments here..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer" id="modal-footer-buttons">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" name="decision" value="approve" class="btn btn-success" id="modal-approve-btn">Approve</button>
        <button type="submit" name="decision" value="reject" class="btn btn-danger" id="modal-reject-btn">Reject</button>
      </div>
    </form>
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
    document.getElementById('modal-project-id').value = projectData.project_id;
    document.getElementById('modal-project-name').textContent = projectData.project_name;
    document.getElementById('modal-manager').textContent = projectData.FirstName + ' ' + projectData.LastName;
    document.getElementById('modal-start-date').textContent = new Date(projectData.start_date).toLocaleDateString();
    document.getElementById('modal-end-date').textContent = new Date(projectData.end_date).toLocaleDateString();
    document.getElementById('modal-description').textContent = projectData.description;
    
    // Handle attachment display
    const attachmentContainer = document.getElementById('modal-attachment');
    if (projectData.attachment) {
      attachmentContainer.innerHTML = `<a href="../${projectData.attachment}" class="attachment-link" target="_blank">
        <i class="fas fa-file-alt"></i> View Document
      </a>`;
    } else {
      attachmentContainer.innerHTML = '<span class="no-attachment">No attachment provided</span>';
    }
    
    // Show/hide approve/reject buttons based on current status
    const approveBtn = document.getElementById('modal-approve-btn');
    const rejectBtn = document.getElementById('modal-reject-btn');
    const commentSection = document.getElementById('comment-section');
    
    if (projectData.decision_status === 'Approved' || projectData.decision_status === 'Rejected') {
      approveBtn.style.display = 'none';
      rejectBtn.style.display = 'none';
      commentSection.style.display = 'none';
    } else {
      approveBtn.style.display = 'inline-block';
      rejectBtn.style.display = 'inline-block';
      commentSection.style.display = 'block';
    }
  });
</script>
</body>
</html>
<?php $connection->close(); ?>