<?php
session_start();
include '../db_connection.php';

// Only allow admins
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$message_type = 'success';
$show_inactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == 1;

// Handle all actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // ADD NEW SITE ENGINEER
        if (isset($_POST['add_engineer'])) {
            $firstName = $connection->real_escape_string($_POST['first_name']);
            $lastName = $connection->real_escape_string($_POST['last_name']);
            $email = $connection->real_escape_string($_POST['email']);
            $phone = $connection->real_escape_string($_POST['phone'] ?? '');
            
            // Validate first name and last name (must start with capital letter and contain only letters)
            if (!preg_match('/^[A-Z][a-zA-Z]*$/', $firstName)) {
                throw new Exception("First name must start with a capital letter and contain only letters.");
            }
            
            if (!preg_match('/^[A-Z][a-zA-Z]*$/', $lastName)) {
                throw new Exception("Last name must start with a capital letter and contain only letters.");
            }
            
            // Validate phone number - only validate the 9 digits after +251
            if (!empty($phone)) {
                // Add +251 prefix if not present
                if (substr($phone, 0, 4) !== '+251') {
                    $phone = '+251' . $phone;
                }
                
                // Check if the remaining part is 9 digits starting with 7 or 9
                $digits = substr($phone, 4);
                if (!preg_match('/^[79]\d{8}$/', $digits)) {
                    throw new Exception("Phone number must have 9 digits after +251, starting with 7 or 9.");
                }
            } else {
                $phone = ''; // Set to empty if not provided
            }
            
            // Generate a unique username (firstname.lastname)
            $username = strtolower($firstName . '.' . $lastName);
            $password = password_hash('default123', PASSWORD_BCRYPT);
            
            // First check if username exists
            $check = $connection->query("SELECT UserID FROM users WHERE Username = '$username'");
            if ($check->num_rows > 0) {
                // If exists, add a number to make it unique
                $counter = 1;
                while ($connection->query("SELECT UserID FROM users WHERE Username = '$username$counter'")->num_rows > 0) {
                    $counter++;
                }
                $username = $username . $counter;
            }
            
            // Get role_id for Site Engineer
            $role_query = $connection->query("SELECT role_id FROM roles WHERE role_name = 'Site Engineer'");
            $role_id = ($role_query->num_rows > 0) ? $role_query->fetch_assoc()['role_id'] : null;
            
            // Now insert with the unique username
            $query = "INSERT INTO users (Username, FirstName, LastName, Email, Password, Role, Phone, role_id, created_by) 
                      VALUES (?, ?, ?, ?, ?, 'Site Engineer', ?, ?, ?)";
            $stmt = $connection->prepare($query);
            $admin_id = $_SESSION['user_id'];
            $stmt->bind_param("ssssssis", $username, $firstName, $lastName, $email, $password, $phone, $role_id, $admin_id);
            $stmt->execute();
            
            $message = "Site Engineer added successfully! Username: $username (Default password: default123)";
            $message_type = "success";
        }
        
        // UPDATE SITE ENGINEER - REMOVED EMAIL EDITING
        if (isset($_POST['update_engineer'])) {
            $id = (int)$_POST['engineer_id'];
            $firstName = $connection->real_escape_string($_POST['first_name']);
            $lastName = $connection->real_escape_string($_POST['last_name']);
            $phone = $connection->real_escape_string($_POST['phone'] ?? '');
            
            // Validate first name and last name (must start with capital letter and contain only letters)
            if (!preg_match('/^[A-Z][a-zA-Z]*$/', $firstName)) {
                throw new Exception("First name must start with a capital letter and contain only letters.");
            }
            
            if (!preg_match('/^[A-Z][a-zA-Z]*$/', $lastName)) {
                throw new Exception("Last name must start with a capital letter and contain only letters.");
            }
            
            // Validate phone number - only validate the 9 digits after +251
            if (!empty($phone)) {
                // Add +251 prefix if not present
                if (substr($phone, 0, 4) !== '+251') {
                    $phone = '+251' . $phone;
                }
                
                // Check if the remaining part is 9 digits starting with 7 or 9
                $digits = substr($phone, 4);
                if (!preg_match('/^[79]\d{8}$/', $digits)) {
                    throw new Exception("Phone number must have 9 digits after +251, starting with 7 or 9.");
                }
            } else {
                $phone = ''; // Set to empty if not provided
            }
            
            // Updated query to not include email
            $query = "UPDATE users SET 
                      FirstName = ?,
                      LastName = ?,
                      Phone = ?
                      WHERE UserID = ? AND Role = 'Site Engineer'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("sssi", $firstName, $lastName, $phone, $id);
            $stmt->execute();
            
            $message = "Site Engineer updated successfully!";
            $message_type = "success";
        }
        
        // SOFT DELETE SITE ENGINEER (DEACTIVATE)
        if (isset($_POST['deactivate_engineer'])) {
            $id = (int)$_POST['engineer_id'];
            
            // First check if engineer has any active projects
            $check_projects = $connection->prepare("SELECT COUNT(*) as project_count FROM project_assignments WHERE user_id = ? AND role_in_project = 'Assigned Site Engineer' AND status != 'Completed'");
            $check_projects->bind_param("i", $id);
            $check_projects->execute();
            $result = $check_projects->get_result();
            $project_count = $result->fetch_assoc()['project_count'];
            
            if ($project_count > 0) {
                $message = "Cannot deactivate site engineer with active projects. Please reassign projects first.";
                $message_type = "danger";
            } else {
                // Add is_active column if it doesn't exist
                $check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
                if ($check_column->num_rows == 0) {
                    $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
                }
                
                $query = "UPDATE users SET is_active = 0 WHERE UserID = ? AND Role = 'Site Engineer'";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $message = "Site Engineer deactivated successfully!";
                $message_type = "success";
            }
        }
        
        // REACTIVATE SITE ENGINEER
        if (isset($_POST['reactivate_engineer'])) {
            $id = (int)$_POST['engineer_id'];
            
            $query = "UPDATE users SET is_active = 1 WHERE UserID = ? AND Role = 'Site Engineer'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Site Engineer reactivated successfully!";
            $message_type = "success";
        }
        
        // PERMANENTLY DELETE SITE ENGINEER
        if (isset($_POST['delete_engineer'])) {
            $id = (int)$_POST['engineer_id'];
            
            // First check if engineer has any projects (active or completed)
            $check_projects = $connection->prepare("SELECT COUNT(*) as project_count FROM project_assignments WHERE user_id = ? AND role_in_project = 'Assigned Site Engineer'");
            $check_projects->bind_param("i", $id);
            $check_projects->execute();
            $result = $check_projects->get_result();
            $project_count = $result->fetch_assoc()['project_count'];
            
            if ($project_count > 0) {
                $message = "Cannot delete site engineer with project history. Please deactivate instead.";
                $message_type = "danger";
            } else {
                // Delete the site engineer
                $query = "DELETE FROM users WHERE UserID = ? AND Role = 'Site Engineer'";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $message = "Site Engineer permanently deleted successfully!";
                $message_type = "success";
            }
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Check if is_active column exists, if not, add it
$check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($check_column->num_rows == 0) {
    $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
}

// Fetch site engineers based on active status
$is_active = $show_inactive ? 0 : 1;
$query = "SELECT * FROM users WHERE Role = 'Site Engineer' AND (is_active = ? OR is_active IS NULL) ORDER BY LastName, FirstName";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $is_active);
$stmt->execute();
$site_engineers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $show_inactive ? 'Inactive' : 'Active' ?> Site Engineers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f0f8 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            color: white;
            padding: 15px 25px;
            border-bottom: none;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #3a516b;
            color: white;
            font-weight: 500;
            padding: 15px;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(58, 81, 107, 0.05);
            transform: translateY(-1px);
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .btn-action {
            margin-right: 5px;
        }
        
        .back-btn {
            margin: 20px;
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .badge-active {
            background-color: #28a745;
        }
        
        .badge-inactive {
            background-color: #dc3545;
        }
        
        .table-container {
            padding: 20px;
        }
        
        .status-toggle {
            margin-bottom: 20px;
        }
        
        .email-field {
            color: #666;
            font-style: italic;
        }
        
        .validation-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .badge-engineer {
            background-color: #4ca1af;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            color: white;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1c2e40 0%, #3c919f 100%);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
                <div class="row">
            <div class="col-12">
                <a href="AdminManageAccount.php?role=Site_Engineer" class="btn back-btn">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <div class="container mt-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><i class="bi bi-hard-hat me-2"></i><?= $show_inactive ? 'Inactive' : 'Active' ?> Site Engineers</h2>
                </div>
                
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="status-toggle text-end">
                        <?php if ($show_inactive): ?>
                            <a href="?show_inactive=0" class="btn btn-outline-success">
                                <i class="bi bi-person-check"></i> Show Active Site Engineers
                            </a>
                        <?php else: ?>
                            <a href="?show_inactive=1" class="btn btn-outline-danger">
                                <i class="bi bi-person-x"></i> Show Inactive Site Engineers
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-container">
                        <table id="engineersTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($site_engineers->num_rows > 0): ?>
                                    <?php while ($engineer = $site_engineers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $engineer['UserID'] ?></td>
                                        <td><?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?></td>
                                        <td class="email-field"><?= htmlspecialchars($engineer['Email']) ?></td>
                                        <td><?= htmlspecialchars($engineer['Phone'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($engineer['RegistrationDate'])) ?></td>
                                        <td>
                                            <?php if (!$show_inactive): ?>
                                                <button class="btn btn-sm btn-primary btn-action edit-btn" 
                                                        data-id="<?= $engineer['UserID'] ?>"
                                                        data-firstname="<?= htmlspecialchars($engineer['FirstName']) ?>"
                                                        data-lastname="<?= htmlspecialchars($engineer['LastName']) ?>"
                                                        data-phone="<?= htmlspecialchars($engineer['Phone'] ?? '') ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action deactivate-btn" 
                                                        data-id="<?= $engineer['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?>">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action delete-btn" 
                                                        data-id="<?= $engineer['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success btn-action reactivate-btn" 
                                                        data-id="<?= $engineer['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?>">
                                                    <i class="bi bi-person-check"></i> Reactivate
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action delete-btn" 
                                                        data-id="<?= $engineer['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($engineer['FirstName'] . ' ' . $engineer['LastName']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No <?= $show_inactive ? 'inactive' : 'active' ?> site engineers found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Site Engineer Modal -->
    <div class="modal fade" id="addEngineerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="addEngineerForm">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Add New Site Engineer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required pattern="[A-Z][a-zA-Z]*">
                            <div class="validation-info">Must start with a capital letter and contain only letters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required pattern="[A-Z][a-zA-Z]*">
                            <div class="validation-info">Must start with a capital letter and contain only letters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text">+251</span>
                                <input type="text" name="phone" class="form-control" placeholder="9 digits" maxlength="9" pattern="[79]\d{8}" inputmode="numeric">
                            </div>
                            <div class="validation-info">Enter 9 digits starting with 7 or 9. The country code +251 is added automatically.</div>
                        </div>
                        <div class="alert alert-info">
                            <small><i class="bi bi-info-circle me-1"></i>A default password of "default123" will be set for the new site engineer.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_engineer" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Site Engineer Modal - REMOVED EMAIL FIELD -->
    <div class="modal fade" id="editEngineerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editEngineerForm">
                    <input type="hidden" name="engineer_id" id="editEngineerId">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Site Engineer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control" required pattern="[A-Z][a-zA-Z]*">
                            <div class="validation-info">Must start with a capital letter and contain only letters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control" required pattern="[A-Z][a-zA-Z]*">
                            <div class="validation-info">Must start with a capital letter and contain only letters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text">+251</span>
                                <input type="text" name="phone" id="editPhone" class="form-control" placeholder="9 digits" maxlength="9" pattern="[79]\d{8}" inputmode="numeric">
                            </div>
                            <div class="validation-info">Enter 9 digits starting with 7 or 9. The country code +251 is added automatically.</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i> Email addresses cannot be changed. If a new email is needed, please deactivate this account and create a new one.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_engineer" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deactivate Site Engineer Modal -->
    <div class="modal fade" id="deactivateEngineerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="engineer_id" id="deactivateEngineerId">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-x-fill me-2"></i>Confirm Deactivation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to deactivate <span id="deactivateEngineerName" class="fw-bold"></span>?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i> This will prevent the site engineer from logging in, but will preserve all their data and project history.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deactivate_engineer" class="btn btn-danger">
                            <i class="bi bi-person-x me-1"></i>Deactivate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reactivate Site Engineer Modal -->
    <div class="modal fade" id="reactivateEngineerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="engineer_id" id="reactivateEngineerId">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-check-fill me-2"></i>Confirm Reactivation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reactivate <span id="reactivateEngineerName" class="fw-bold"></span>?</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i> This will allow the site engineer to log in again with their existing credentials.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reactivate_engineer" class="btn btn-success">
                            <i class="bi bi-person-check me-1"></i>Reactivate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Site Engineer Modal -->
    <div class="modal fade" id="deleteEngineerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="engineer_id" id="deleteEngineerId">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-trash-fill me-2"></i>Confirm Permanent Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to permanently delete <span id="deleteEngineerName" class="fw-bold"></span>?</p>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Warning:</strong> This action cannot be undone. All data associated with this site engineer will be permanently removed.
                                                </div>
                        <p>If the site engineer has any project history, deletion will not be allowed. Consider deactivating instead.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_engineer" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Delete Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#engineersTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "order": [[1, 'asc']], // Sort by name by default
                "responsive": true,
                "language": {
                    "search": "Search site engineers:",
                    "emptyTable": "No <?= $show_inactive ? 'inactive' : 'active' ?> site engineers found"
                }
            });
            
            // Edit button handler - REMOVED EMAIL DATA
            $('.edit-btn').click(function() {
                $('#editEngineerId').val($(this).data('id'));
                $('#editFirstName').val($(this).data('firstname'));
                $('#editLastName').val($(this).data('lastname'));
                
                // Extract digits after +251 for the phone field
                let phone = $(this).data('phone');
                if (phone && phone.startsWith('+251')) {
                    phone = phone.substring(4); // Remove +251 prefix
                }
                $('#editPhone').val(phone);
                
                $('#editEngineerModal').modal('show');
            });
            
            // Deactivate button handler
            $('.deactivate-btn').click(function() {
                $('#deactivateEngineerId').val($(this).data('id'));
                $('#deactivateEngineerName').text($(this).data('name'));
                $('#deactivateEngineerModal').modal('show');
            });
            
            // Reactivate button handler
            $('.reactivate-btn').click(function() {
                $('#reactivateEngineerId').val($(this).data('id'));
                $('#reactivateEngineerName').text($(this).data('name'));
                $('#reactivateEngineerModal').modal('show');
            });
            
            // Delete button handler
            $('.delete-btn').click(function() {
                $('#deleteEngineerId').val($(this).data('id'));
                $('#deleteEngineerName').text($(this).data('name'));
                $('#deleteEngineerModal').modal('show');
            });
            
            // Force numeric input for phone fields
            $('input[name="phone"]').on('input', function() {
                // Remove any non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Ensure it starts with 7 or 9
                if (this.value.length > 0 && this.value[0] !== '7' && this.value[0] !== '9') {
                    this.value = '';
                    $(this).addClass('is-invalid');
                    $(this).next('.invalid-feedback').text('Phone number must start with 7 or 9');
                } else {
                    $(this).removeClass('is-invalid');
                }
                
                // Limit to 9 digits
                if (this.value.length > 9) {
                    this.value = this.value.substring(0, 9);
                }
            });
            
            // Validate first and last name fields on input
            $('input[name="first_name"], #editFirstName').on('input', function() {
                validateName(this, 'First name');
            });
            
            $('input[name="last_name"], #editLastName').on('input', function() {
                validateName(this, 'Last name');
            });
            
            // Name validation function
            function validateName(field, fieldName) {
                const value = $(field).val();
                
                // Check if starts with capital letter and contains only letters
                if (value && !/^[A-Z][a-zA-Z]*$/.test(value)) {
                    $(field).addClass('is-invalid');
                    if (!$(field).next('.invalid-feedback').length) {
                        $(field).after('<div class="invalid-feedback">' + fieldName + ' must start with a capital letter and contain only letters.</div>');
                    }
                } else {
                    $(field).removeClass('is-invalid');
                }
            }
            
            // Client-side validation for add form
            $('#addEngineerForm').on('submit', function(e) {
                const firstName = $('input[name="first_name"]').val();
                const lastName = $('input[name="last_name"]').val();
                const phone = $('input[name="phone"]').val();
                
                let isValid = true;
                let errorMessage = '';
                
                // Validate first name
                if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                    isValid = false;
                    errorMessage = 'First name must start with a capital letter and contain only letters.';
                    $('input[name="first_name"]').addClass('is-invalid');
                } else {
                    $('input[name="first_name"]').removeClass('is-invalid');
                }
                
                // Validate last name
                if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                    isValid = false;
                    errorMessage = errorMessage || 'Last name must start with a capital letter and contain only letters.';
                    $('input[name="last_name"]').addClass('is-invalid');
                } else {
                    $('input[name="last_name"]').removeClass('is-invalid');
                }
                
                // Validate phone if provided
                if (phone && !/^[79]\d{8}$/.test(phone)) {
                    isValid = false;
                    errorMessage = errorMessage || 'Phone number must be 9 digits starting with 7 or 9.';
                    $('input[name="phone"]').addClass('is-invalid');
                } else {
                    $('input[name="phone"]').removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });
            
            // Client-side validation for edit form
            $('#editEngineerForm').on('submit', function(e) {
                const firstName = $('#editFirstName').val();
                const lastName = $('#editLastName').val();
                const phone = $('#editPhone').val();
                
                let isValid = true;
                let errorMessage = '';
                
                // Validate first name
                if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                    isValid = false;
                    errorMessage = 'First name must start with a capital letter and contain only letters.';
                    $('#editFirstName').addClass('is-invalid');
                } else {
                    $('#editFirstName').removeClass('is-invalid');
                }
                
                // Validate last name
                if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                    isValid = false;
                    errorMessage = errorMessage || 'Last name must start with a capital letter and contain only letters.';
                    $('#editLastName').addClass('is-invalid');
                } else {
                    $('#editLastName').removeClass('is-invalid');
                }
                
                // Validate phone if provided
                if (phone && !/^[79]\d{8}$/.test(phone)) {
                    isValid = false;
                    errorMessage = errorMessage || 'Phone number must be 9 digits starting with 7 or 9.';
                    $('#editPhone').addClass('is-invalid');
                } else {
                    $('#editPhone').removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert-dismissible').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>