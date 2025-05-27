<?php
session_start();
require '../db_connection.php';

// Initialize variables
$message = '';
$message_type = '';
$admins = [];
$show_inactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == 1;

// Verify admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Handle all actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // ADD NEW ADMIN
        if (isset($_POST['add_admin'])) {
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
            
            // Generate a secure random password
            $random_password = bin2hex(random_bytes(4)); // 8 characters
            $password = password_hash($random_password, PASSWORD_BCRYPT);
            
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
            
            // Now insert with the unique username
            $query = "INSERT INTO users (Username, FirstName, LastName, Email, Password, Role, Phone) 
                      VALUES (?, ?, ?, ?, ?, 'Admin', ?)";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("ssssss", $username, $firstName, $lastName, $email, $password, $phone);
            $stmt->execute();
            
            $message = "Admin added successfully! Username: $username (Password: $random_password)";
            $message_type = "success";
        }
        
        // UPDATE ADMIN
        if (isset($_POST['update_admin'])) {
            $id = (int)$_POST['admin_id'];
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
                      WHERE UserID = ? AND Role = 'Admin'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("sssi", $firstName, $lastName, $phone, $id);
            $stmt->execute();
            
            $message = "Admin updated successfully!";
            $message_type = "success";
        }
        
        // SOFT DELETE ADMIN (DEACTIVATE)
        if (isset($_POST['deactivate_admin'])) {
            $id = (int)$_POST['admin_id'];
            
            // Check if this is the last active admin
            $check_admins = $connection->query("SELECT COUNT(*) as admin_count FROM users WHERE Role = 'Admin' AND is_active = 1 AND UserID != $id");
            $admin_count = $check_admins->fetch_assoc()['admin_count'];
            
            if ($admin_count == 0) {
                throw new Exception("Cannot deactivate the last active admin account. At least one admin must remain active.");
            }
            
            // Add is_active column if it doesn't exist
            $check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            if ($check_column->num_rows == 0) {
                $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
            }
            
            $query = "UPDATE users SET is_active = 0 WHERE UserID = ? AND Role = 'Admin'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Admin deactivated successfully!";
            $message_type = "success";
        }
        
        // REACTIVATE ADMIN
        if (isset($_POST['reactivate_admin'])) {
            $id = (int)$_POST['admin_id'];
            
            $query = "UPDATE users SET is_active = 1 WHERE UserID = ? AND Role = 'Admin'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Admin reactivated successfully!";
            $message_type = "success";
        }
        
        // DELETE ADMIN PERMANENTLY
        if (isset($_POST['delete_admin'])) {
            $id = (int)$_POST['admin_id'];
            
            // Check if this is the current logged-in admin
            if ($id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            
            // Check if this is the last admin (active or inactive)
            $check_admins = $connection->query("SELECT COUNT(*) as admin_count FROM users WHERE Role = 'Admin' AND UserID != $id");
            $admin_count = $check_admins->fetch_assoc()['admin_count'];
            
            if ($admin_count == 0) {
                throw new Exception("Cannot delete the last admin account. At least one admin must exist in the system.");
            }
            
            // Delete the admin
            $query = "DELETE FROM users WHERE UserID = ? AND Role = 'Admin'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Admin deleted permanently!";
            $message_type = "success";
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

// Get all Admins based on active status
$is_active = $show_inactive ? 0 : 1;
$query = "SELECT * FROM users WHERE Role = 'Admin' AND (is_active = ? OR is_active IS NULL) ORDER BY LastName, FirstName";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $is_active);
$stmt->execute();
$admins = $stmt->get_result();

// Get current admin ID to prevent self-deactivation
$current_admin_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $show_inactive ? 'Inactive' : 'Active' ?> Administrators</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
        .btn-action {
            margin-right: 5px;
        }
        .back-btn {
            margin: 20px;
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

        
.admin {
    background: linear-gradient(135deg, #6f42c1, #563d7c);
}

.admin.active::after {
    border-top-color: #563d7c;
}

.all-roles {
    background: linear-gradient(135deg, #20c997, #0ca678);
}

.all-roles.active::after {
    border-top-color: #0ca678;
}

        .validation-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .password-strength {
            margin-top: 10px;
        }
        .password-strength-meter {
            height: 5px;
            width: 100%;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
        }
        .password-strength-meter div {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .weak { width: 25%; background-color: #dc3545; }
        .medium { width: 50%; background-color: #ffc107; }
        .strong { width: 75%; background-color: #28a745; }
        .very-strong { width: 100%; background-color: #20c997; }
        .admin {
            background: linear-gradient(135deg, #6f42c1, #563d7c);
        }
        .admin.active::after {
            border-top-color: #563d7c;
        }
        .current-user {
            background-color: rgba(255, 243, 205, 0.5);
        }
        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <a href="AdminManageAccount.php?role=Admin" class="btn btn-secondary back-btn">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <div class="container mt-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><?= $show_inactive ? 'Inactive' : 'Active' ?> Administrators</h2>
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
                                <i class="bi bi-person-check"></i> Show Active Admins
                            </a>
                                                <?php else: ?>
                            <a href="?show_inactive=1" class="btn btn-outline-danger">
                                <i class="bi bi-person-x"></i> Show Inactive Admins
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-container">
                        <table id="adminsTable" class="table table-striped table-hover">
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
                                <?php if ($admins->num_rows > 0): ?>
                                    <?php while ($admin = $admins->fetch_assoc()): 
                                        $is_current_user = ($admin['UserID'] == $current_admin_id);
                                    ?>
                                    <tr class="<?= $is_current_user ? 'current-user' : '' ?>">
                                        <td><?= $admin['UserID'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?>
                                            <?php if ($is_current_user): ?>
                                                <span class="badge bg-info">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="email-field"><?= htmlspecialchars($admin['Email']) ?></td>
                                        <td><?= htmlspecialchars($admin['Phone'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($admin['RegistrationDate'])) ?></td>
                                        <td>
                                            <?php if (!$show_inactive): ?>
                                                <button class="btn btn-sm btn-primary btn-action edit-btn" 
                                                        data-id="<?= $admin['UserID'] ?>"
                                                        data-firstname="<?= htmlspecialchars($admin['FirstName']) ?>"
                                                        data-lastname="<?= htmlspecialchars($admin['LastName']) ?>"
                                                        data-phone="<?= htmlspecialchars($admin['Phone'] ?? '') ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <?php if (!$is_current_user): ?>
                                                    <button class="btn btn-sm btn-danger btn-action deactivate-btn" 
                                                            data-id="<?= $admin['UserID'] ?>"
                                                            data-name="<?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?>">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-delete btn-action delete-btn" 
                                                            data-id="<?= $admin['UserID'] ?>"
                                                            data-name="<?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="You cannot deactivate your own account">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-secondary" disabled title="You cannot delete your own account">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success btn-action reactivate-btn" 
                                                        data-id="<?= $admin['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?>">
                                                    <i class="bi bi-person-check"></i> Reactivate
                                                </button>
                                                
                                                <button class="btn btn-sm btn-delete btn-action delete-btn" 
                                                        data-id="<?= $admin['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No <?= $show_inactive ? 'inactive' : 'active' ?> administrators found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="addAdminForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Administrator</h5>
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
                            <small>A random secure password will be generated for the new admin. The password will be displayed once after creation.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_admin" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editAdminForm">
                    <input type="hidden" name="admin_id" id="editAdminId">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Administrator</h5>
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
                            <i class="bi bi-info-circle"></i> Email addresses cannot be changed. If a new email is needed, please deactivate this account and create a new one.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_admin" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deactivate Admin Modal -->
    <div class="modal fade" id="deactivateAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="admin_id" id="deactivateAdminId">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Deactivation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to deactivate <span id="deactivateAdminName" class="fw-bold"></span>?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This will prevent the administrator from logging in, but will preserve all their data and activity history.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deactivate_admin" class="btn btn-danger">Deactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reactivate Admin Modal -->
    <div class="modal fade" id="reactivateAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="admin_id" id="reactivateAdminId">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Reactivation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reactivate <span id="reactivateAdminName" class="fw-bold"></span>?</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This will allow the administrator to log in again with their existing credentials.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reactivate_admin" class="btn btn-success">Reactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Admin Modal -->
    <div class="modal fade" id="deleteAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="admin_id" id="deleteAdminId">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Permanent Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to permanently delete <span id="deleteAdminName" class="fw-bold"></span>?</p>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Warning:</strong> This action cannot be undone. All data associated with this administrator will be permanently removed from the system.
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label" for="confirmDelete">
                                I understand that this action is permanent and cannot be undone.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_admin" class="btn btn-delete" id="deleteAdminButton" disabled>
                            <i class="bi bi-trash"></i> Delete Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#adminsTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [10, 25, 50, 100],
            "order": [[1, 'asc']], // Sort by name by default
            "responsive": true,
            "language": {
                "search": "Search administrators:",
                "emptyTable": "No <?= $show_inactive ? 'inactive' : 'active' ?> administrators found"
            }
        });
        
        // Edit button handler
        $('.edit-btn').click(function() {
            $('#editAdminId').val($(this).data('id'));
            $('#editFirstName').val($(this).data('firstname'));
            $('#editLastName').val($(this).data('lastname'));
            
            // Extract digits after +251 for the phone field
            let phone = $(this).data('phone');
            if (phone && phone.startsWith('+251')) {
                phone = phone.substring(4); // Remove +251 prefix
            }
            $('#editPhone').val(phone);
            
            $('#editAdminModal').modal('show');
        });
        
        // Deactivate button handler
        $('.deactivate-btn').click(function() {
            $('#deactivateAdminId').val($(this).data('id'));
            $('#deactivateAdminName').text($(this).data('name'));
            $('#deactivateAdminModal').modal('show');
        });
        
        // Reactivate button handler
        $('.reactivate-btn').click(function() {
            $('#reactivateAdminId').val($(this).data('id'));
            $('#reactivateAdminName').text($(this).data('name'));
            $('#reactivateAdminModal').modal('show');
        });
        
        // Delete button handler
        $('.delete-btn').click(function() {
            $('#deleteAdminId').val($(this).data('id'));
            $('#deleteAdminName').text($(this).data('name'));
            $('#confirmDelete').prop('checked', false);
            $('#deleteAdminButton').prop('disabled', true);
            $('#deleteAdminModal').modal('show');
        });
        
        // Enable delete button when checkbox is checked
        $('#confirmDelete').change(function() {
            $('#deleteAdminButton').prop('disabled', !this.checked);
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
        $('#addAdminForm').on('submit', function(e) {
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
        $('#editAdminForm').on('submit', function(e) {
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
        
        // Confirm delete with additional warning
        $('form').has('button[name="delete_admin"]').on('submit', function(e) {
            if (!$('#confirmDelete').prop('checked')) {
                e.preventDefault();
                alert('You must confirm that you understand this action is permanent.');
                return false;
            }
            
            if (!confirm('WARNING: You are about to permanently delete this administrator. This action CANNOT be undone. Are you absolutely sure?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);
    });
    </script>
</body>
</html>