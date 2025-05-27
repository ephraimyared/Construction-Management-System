<?php
session_start();
require '../db_connection.php';

// Initialize variables
$message = '';
$message_type = '';
$managers = [];
$show_inactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == 1;

// Verify admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    header("Location: ../unauthorized.php");
    exit();
}

// Handle all actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // ADD NEW MANAGER
        if (isset($_POST['add_manager'])) {
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
            
            // Now insert with the unique username
            $query = "INSERT INTO users (Username, FirstName, LastName, Email, Password, Role, Phone) 
                      VALUES (?, ?, ?, ?, ?, 'Project Manager', ?)";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("ssssss", $username, $firstName, $lastName, $email, $password, $phone);
            $stmt->execute();
            
            $message = "Manager added successfully! Username: $username (Default password: default123)";
            $message_type = "success";
        }
        
        // UPDATE MANAGER - REMOVED EMAIL EDITING
        if (isset($_POST['update_manager'])) {
            $id = (int)$_POST['manager_id'];
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
                      WHERE UserID = ? AND Role = 'Project Manager'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("sssi", $firstName, $lastName, $phone, $id);
            $stmt->execute();
            
            $message = "Manager updated successfully!";
            $message_type = "success";
        }
        
        // SOFT DELETE MANAGER (DEACTIVATE)
        if (isset($_POST['deactivate_manager'])) {
            $id = (int)$_POST['manager_id'];
            
            // First check if manager has any active projects
            $check_projects = $connection->prepare("SELECT COUNT(*) as project_count FROM projects WHERE manager_id = ? AND status != 'Completed' AND status != 'Cancelled'");
            $check_projects->bind_param("i", $id);
            $check_projects->execute();
            $result = $check_projects->get_result();
            $project_count = $result->fetch_assoc()['project_count'];
            
            if ($project_count > 0) {
                $message = "Cannot deactivate manager with active projects. Please reassign projects first.";
                $message_type = "danger";
            } else {
                // Add is_active column if it doesn't exist
                $check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
                if ($check_column->num_rows == 0) {
                    $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
                }
                
                $query = "UPDATE users SET is_active = 0 WHERE UserID = ? AND Role = 'Project Manager'";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $message = "Manager deactivated successfully!";
                $message_type = "success";
            }
        }
        
        // REACTIVATE MANAGER
        if (isset($_POST['reactivate_manager'])) {
            $id = (int)$_POST['manager_id'];
            
            $query = "UPDATE users SET is_active = 1 WHERE UserID = ? AND Role = 'Project Manager'";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = "Manager reactivated successfully!";
            $message_type = "success";
        }
        
        // DELETE MANAGER PERMANENTLY
        if (isset($_POST['delete_manager'])) {
            $id = (int)$_POST['manager_id'];
            
            // Check if manager has any assignments
            $check_projects = $connection->prepare("SELECT COUNT(*) as project_count FROM projects WHERE manager_id = ?");
            $check_projects->bind_param("i", $id);
            $check_projects->execute();
            $result = $check_projects->get_result();
            $project_count = $result->fetch_assoc()['project_count'];
            
            // Check for other assignments (add more checks as needed)
            $has_assignments = ($project_count > 0);
            
            if ($has_assignments) {
                // Store the manager ID in session for the reassignment page
                $_SESSION['manager_to_reassign'] = $id;
                header("Location: ReassignTask.php?type=manager&id=$id");
                exit();
            } else {
                // No assignments, proceed with deletion
                $query = "DELETE FROM users WHERE UserID = ? AND Role = 'Project Manager'";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $message = "Manager deleted successfully!";
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

// Get all Project Managers based on active status
$is_active = $show_inactive ? 0 : 1;
$query = "SELECT * FROM users WHERE Role = 'Project Manager' AND (is_active = ? OR is_active IS NULL) ORDER BY LastName, FirstName";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $is_active);
$stmt->execute();
$managers = $stmt->get_result();

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $show_inactive ? 'Inactive' : 'Active' ?> Project Managers</title>
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
        .validation-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <a href="AdminManageAccount.php?role=Project_Manager" class="btn btn-secondary back-btn">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <div class="container mt-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0"><?= $show_inactive ? 'Inactive' : 'Active' ?> Project Managers</h2>
                    <div>
                        <?php if (!$show_inactive): ?>
                        <?php endif; ?>
                    </div>
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
                                <i class="bi bi-person-check"></i> Show Active Managers
                            </a>
                        <?php else: ?>
                            <a href="?show_inactive=1" class="btn btn-outline-danger">
                                <i class="bi bi-person-x"></i> Show Inactive Managers
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-container">
                        <table id="managersTable" class="table table-striped table-hover">
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
                                <?php if ($managers->num_rows > 0): ?>
                                    <?php while ($manager = $managers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $manager['UserID'] ?></td>
                                        <td><?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?></td>
                                        <td class="email-field"><?= htmlspecialchars($manager['Email']) ?></td>
                                        <td><?= htmlspecialchars($manager['Phone'] ?? 'N/A') ?></td>
                                        <td><?= date('M d, Y', strtotime($manager['RegistrationDate'])) ?></td>
                                        <td>
                                            <?php if (!$show_inactive): ?>
                                                        <button class="btn btn-sm btn-primary btn-action edit-btn" 
                                                        data-id="<?= $manager['UserID'] ?>"
                                                        data-firstname="<?= htmlspecialchars($manager['FirstName']) ?>"
                                                        data-lastname="<?= htmlspecialchars($manager['LastName']) ?>"
                                                        data-phone="<?= htmlspecialchars($manager['Phone'] ?? '') ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action deactivate-btn" 
                                                        data-id="<?= $manager['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?>">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action delete-btn" 
                                                        data-id="<?= $manager['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success btn-action reactivate-btn" 
                                                        data-id="<?= $manager['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?>">
                                                    <i class="bi bi-person-check"></i> Reactivate
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action delete-btn" 
                                                        data-id="<?= $manager['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($manager['FirstName'] . ' ' . $manager['LastName']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No managers found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Manager Modal -->
    <div class="modal fade" id="addManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Project Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                            <div class="validation-info">Must start with a capital letter and contain only letters</div>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                            <div class="validation-info">Must start with a capital letter and contain only letters</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">+251</span>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="9XXXXXXXX or 7XXXXXXXX">
                            </div>
                            <div class="validation-info">Must be 9 digits starting with 7 or 9</div>
                        </div>
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle"></i> A username will be automatically generated from the first and last name.
                                <br>
                                <i class="bi bi-key"></i> Default password: <strong>default123</strong>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_manager" class="btn btn-primary">Add Manager</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Manager Modal -->
    <div class="modal fade" id="editManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Project Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_manager_id" name="manager_id">
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            <div class="validation-info">Must start with a capital letter and contain only letters</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            <div class="validation-info">Must start with a capital letter and contain only letters</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">+251</span>
                                <input type="text" class="form-control" id="edit_phone" name="phone" placeholder="9XXXXXXXX or 7XXXXXXXX">
                            </div>
                            <div class="validation-info">Must be 9 digits starting with 7 or 9</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_manager" class="btn btn-primary">Update Manager</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deactivate Manager Modal -->
    <div class="modal fade" id="deactivateManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deactivate Project Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="deactivate_manager_id" name="manager_id">
                        <p>Are you sure you want to deactivate <span id="deactivate_manager_name" class="fw-bold"></span>?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This will prevent the manager from logging in and accessing the system.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deactivate_manager" class="btn btn-danger">Deactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reactivate Manager Modal -->
    <div class="modal fade" id="reactivateManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reactivate Project Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="reactivate_manager_id" name="manager_id">
                        <p>Are you sure you want to reactivate <span id="reactivate_manager_name" class="fw-bold"></span>?</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This will allow the manager to log in and access the system again.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reactivate_manager" class="btn btn-success">Reactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Manager Modal -->
    <div class="modal fade" id="deleteManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Project Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="delete_manager_id" name="manager_id">
                        <p>Are you sure you want to permanently delete <span id="delete_manager_name" class="fw-bold"></span>?</p>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Warning:</strong> This action cannot be undone!
                            <hr>
                            <p class="mb-0">If this manager has any assigned projects or tasks, you will be redirected to a reassignment page.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_manager" class="btn btn-danger">Delete Permanently</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#managersTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[1, "asc"]], // Sort by name by default
                "columnDefs": [
                    { "orderable": false, "targets": 5 } // Disable sorting on actions column
                ]
            });
            
            // Handle Edit button clicks - using event delegation for DataTables compatibility
            $(document).on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                var firstName = $(this).data('firstname');
                var lastName = $(this).data('lastname');
                var phone = $(this).data('phone');
                
                // Remove +251 prefix if present for the edit form
                if (phone && phone.startsWith('+251')) {
                    phone = phone.substring(4);
                }
                
                $('#edit_manager_id').val(id);
                $('#edit_first_name').val(firstName);
                $('#edit_last_name').val(lastName);
                $('#edit_phone').val(phone);
                
                $('#editManagerModal').modal('show');
            });
            
            // Handle Deactivate button clicks
            $(document).on('click', '.deactivate-btn', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                $('#deactivate_manager_id').val(id);
                $('#deactivate_manager_name').text(name);
                
                $('#deactivateManagerModal').modal('show');
            });
            
            // Handle Reactivate button clicks
            $(document).on('click', '.reactivate-btn', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                $('#reactivate_manager_id').val(id);
                $('#reactivate_manager_name').text(name);
                
                $('#reactivateManagerModal').modal('show');
            });
            
            // Handle Delete button clicks
            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                $('#delete_manager_id').val(id);
                $('#delete_manager_name').text(name);
                
                $('#deleteManagerModal').modal('show');
            });
            
            // Phone number validation for add form
            $('#phone').on('input', function() {
                var input = $(this).val();
                // Remove any non-digit characters
                var digits = input.replace(/\D/g, '');
                // Limit to 9 digits
                digits = digits.substring(0, 9);
                         $(this).val(digits);
            });
            
            // Phone number validation for edit form
            $('#edit_phone').on('input', function() {
                var input = $(this).val();
                // Remove any non-digit characters
                var digits = input.replace(/\D/g, '');
                // Limit to 9 digits
                digits = digits.substring(0, 9);
                $(this).val(digits);
            });
            
            // Form validation for add form
            $('form[name="add_manager"]').on('submit', function(e) {
                var firstName = $('#first_name').val();
                var lastName = $('#last_name').val();
                var phone = $('#phone').val();
                
                var isValid = true;
                var errorMessage = '';
                
                // Validate first name
                if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                    errorMessage += 'First name must start with a capital letter and contain only letters.\n';
                    isValid = false;
                }
                
                // Validate last name
                if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                    errorMessage += 'Last name must start with a capital letter and contain only letters.\n';
                    isValid = false;
                }
                
                // Validate phone if provided
                if (phone && !/^[79]\d{8}$/.test(phone)) {
                    errorMessage += 'Phone number must be 9 digits starting with 7 or 9.\n';
                    isValid = false;
                }
                
                if (!isValid) {
                    alert(errorMessage);
                    e.preventDefault();
                }
            });
            
            // Form validation for edit form
            $('form[name="edit_manager"]').on('submit', function(e) {
                var firstName = $('#edit_first_name').val();
                var lastName = $('#edit_last_name').val();
                var phone = $('#edit_phone').val();
                
                var isValid = true;
                var errorMessage = '';
                
                // Validate first name
                if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                    errorMessage += 'First name must start with a capital letter and contain only letters.\n';
                    isValid = false;
                }
                
                // Validate last name
                if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                    errorMessage += 'Last name must start with a capital letter and contain only letters.\n';
                    isValid = false;
                }
                
                // Validate phone if provided
                if (phone && !/^[79]\d{8}$/.test(phone)) {
                    errorMessage += 'Phone number must be 9 digits starting with 7 or 9.\n';
                    isValid = false;
                }
                
                if (!isValid) {
                    alert(errorMessage);
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>

                                      