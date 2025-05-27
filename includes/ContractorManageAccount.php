<?php
session_start();
include '../db_connection.php';

// Only allow contractors
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Contractor') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$message_type = 'success';
$show_inactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == 1;
$contractor_id = $_SESSION['user_id']; // Get contractor ID from session

// Handle all actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // ADD NEW EMPLOYEE
        if (isset($_POST['add_employee'])) {
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
            
            // Get role_id for Employee
            $role_query = $connection->query("SELECT role_id FROM roles WHERE role_name = 'Employee'");
            $role_id = ($role_query->num_rows > 0) ? $role_query->fetch_assoc()['role_id'] : null;
            
            // Now insert with the unique username
            $query = "INSERT INTO users (Username, FirstName, LastName, Email, Password, Role, Phone, role_id, managed_by_contractor_id) 
                      VALUES (?, ?, ?, ?, ?, 'Employee', ?, ?, ?)";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("ssssssis", $username, $firstName, $lastName, $email, $password, $phone, $role_id, $contractor_id);
            $stmt->execute();
            
            $message = "Employee added successfully! Username: $username (Default password: default123)";
            $message_type = "success";
        }
        
        // UPDATE EMPLOYEE - REMOVED EMAIL EDITING
        if (isset($_POST['update_employee'])) {
            $id = (int)$_POST['employee_id'];
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
                      WHERE UserID = ? AND Role = 'Employee' AND managed_by_contractor_id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("sssii", $firstName, $lastName, $phone, $id, $contractor_id);
            $stmt->execute();
            
            $message = "Employee updated successfully!";
            $message_type = "success";
        }
        
        // SOFT DELETE EMPLOYEE (DEACTIVATE)
        if (isset($_POST['deactivate_employee'])) {
            $id = (int)$_POST['employee_id'];
            
            // First check if employee has any active tasks
            $check_tasks = $connection->prepare("SELECT COUNT(*) as task_count FROM tasks WHERE assigned_to = ? AND status != 'Completed'");
            $check_tasks->bind_param("i", $id);
            $check_tasks->execute();
            $result = $check_tasks->get_result();
            $task_count = $result->fetch_assoc()['task_count'];
            
            if ($task_count > 0) {
                $message = "Cannot deactivate employee with active tasks. Please reassign tasks first.";
                $message_type = "danger";
            } else {
                // Add is_active column if it doesn't exist
                $check_column = $connection->query("SHOW COLUMNS FROM users LIKE 'is_active'");
                if ($check_column->num_rows == 0) {
                    $connection->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
                }
                
                $query = "UPDATE users SET is_active = 0 WHERE UserID = ? AND Role = 'Employee' AND managed_by_contractor_id = ?";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("ii", $id, $contractor_id);
                $stmt->execute();
                
                $message = "Employee deactivated successfully!";
                $message_type = "success";
            }
        }
        
        // REACTIVATE EMPLOYEE
        if (isset($_POST['reactivate_employee'])) {
            $id = (int)$_POST['employee_id'];
            
            $query = "UPDATE users SET is_active = 1 WHERE UserID = ? AND Role = 'Employee' AND managed_by_contractor_id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("ii", $id, $contractor_id);
            $stmt->execute();
            
            $message = "Employee reactivated successfully!";
            $message_type = "success";
        }
        
        // PERMANENTLY DELETE EMPLOYEE
        if (isset($_POST['delete_employee'])) {
            $id = (int)$_POST['employee_id'];
            
            // First check if employee has any tasks (active or completed)
            $check_tasks = $connection->prepare("SELECT COUNT(*) as task_count FROM tasks WHERE assigned_to = ?");
            $check_tasks->bind_param("i", $id);
            $check_tasks->execute();
            $result = $check_tasks->get_result();
            $task_count = $result->fetch_assoc()['task_count'];
            
            if ($task_count > 0) {
                $message = "Cannot delete employee with task history. Please deactivate instead.";
                $message_type = "danger";
            } else {
                // Delete the employee
                $query = "DELETE FROM users WHERE UserID = ? AND Role = 'Employee' AND managed_by_contractor_id = ?";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("ii", $id, $contractor_id);
                $stmt->execute();
                
                $message = "Employee permanently deleted successfully!";
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

// Fetch employees based on active status
$is_active = $show_inactive ? 0 : 1;
$query = "SELECT * FROM users WHERE Role = 'Employee' AND managed_by_contractor_id = ? AND (is_active = ? OR is_active IS NULL) ORDER BY LastName, FirstName";
$stmt = $connection->prepare($query);
$stmt->bind_param("ii", $contractor_id, $is_active);
$stmt->execute();
$employees = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $show_inactive ? 'Inactive' : 'Active' ?> Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f8fa;
        }
        
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
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background-color: white;
            color: #4e73df;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background-color: #4e73df;
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <a href="ContractorManageEmployee.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>
        </div>
        
        <div class="container mt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0 text-center w-100"><?= $show_inactive ? 'Inactive' : 'Active' ?> Employees</h2>
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
                                <i class="bi bi-person-check"></i> Show Active Employees
                            </a>
                        <?php else: ?>
                            <a href="?show_inactive=1" class="btn btn-outline-danger">
                                <i class="bi bi-person-x"></i> Show Inactive Employees
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-container">
                        <table id="employeesTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($employees->num_rows > 0): ?>
                                    <?php while ($employee = $employees->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $employee['UserID'] ?></td>
                                            <td><?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?></td>
                                            <td class="email-field"><?= htmlspecialchars($employee['Email']) ?></td>
                                            <td><?= htmlspecialchars($employee['Phone'] ?? 'Not provided') ?></td>
                                            <td>
                                                <?php if (!$show_inactive): ?>
                                                    <button class="btn btn-sm btn-primary btn-action" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editEmployeeModal"
                                                            data-id="<?= $employee['UserID'] ?>"
                                                            data-firstname="<?= htmlspecialchars($employee['FirstName']) ?>"
                                                            data-lastname="<?= htmlspecialchars($employee['LastName']) ?>"
                                                            data-phone="<?= htmlspecialchars($employee['Phone'] ?? '') ?>">
                                                        <i class="bi bi-pencil-square"></i> 
                                                    </button>
                                                    <button class="btn btn-sm btn-warning btn-action" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deactivateEmployeeModal"
                                                            data-id="<?= $employee['UserID'] ?>"
                                                            data-name="<?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>">
                                                        <i class="bi bi-person-dash"></i> 
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success btn-action" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#reactivateEmployeeModal"
                                                            data-id="<?= $employee['UserID'] ?>"
                                                            data-name="<?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>">
                                                        <i class="bi bi-person-check"></i> 
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger btn-action" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteEmployeeModal"
                                                        data-id="<?= $employee['UserID'] ?>"
                                                        data-name="<?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>">
                                                    <i class="bi bi-trash"></i> 
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No <?= $show_inactive ? 'inactive' : 'active' ?> employees found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                       
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel"><i class="bi bi-person-plus"></i> Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addEmployeeForm">
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
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="9xxxxxxxx or 7xxxxxxxx">
                            </div>
                            <div class="validation-info">Must be 9 digits starting with 9 or 7</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> A default password will be generated for the employee.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                       
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel"><i class="bi bi-pencil-square"></i> Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editEmployeeForm">
                    <div class="modal-body">
                        <input type="hidden" name="employee_id" id="edit_employee_id">
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
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                            <div class="validation-info">Ethiopian format: +251 followed by 9 or 7 and then 8 more digits</div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Only name and phone number can be edited. Email cannot be changed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_employee" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deactivate Employee Modal -->
    <div class="modal fade" id="deactivateEmployeeModal" tabindex="-1" aria-labelledby="deactivateEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivateEmployeeModalLabel"><i class="bi bi-person-dash"></i> Deactivate Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="employee_id" id="deactivate_employee_id">
                        <p>Are you sure you want to deactivate <span id="deactivate_employee_name" class="fw-bold"></span>?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Deactivated employees will no longer be able to log in to the system.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deactivate_employee" class="btn btn-warning">Deactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reactivate Employee Modal -->
    <div class="modal fade" id="reactivateEmployeeModal" tabindex="-1" aria-labelledby="reactivateEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reactivateEmployeeModalLabel"><i class="bi bi-person-check"></i> Reactivate Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="employee_id" id="reactivate_employee_id">
                        <p>Are you sure you want to reactivate <span id="reactivate_employee_name" class="fw-bold"></span>?</p>
                        <div class="alert alert-success">
                            <i class="bi bi-info-circle"></i> Reactivated employees will be able to log in to the system again.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reactivate_employee" class="btn btn-success">Reactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Employee Modal -->
    <div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEmployeeModalLabel"><i class="bi bi-trash"></i> Delete Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="employee_id" id="delete_employee_id">
                        <p>Are you sure you want to permanently delete <span id="delete_employee_name" class="fw-bold"></span>?</p>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> This action cannot be undone. All data associated with this employee will be permanently deleted.
                        </div>
                        <p>Consider deactivating the employee instead if you may need to restore their account in the future.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_employee" class="btn btn-danger">Delete Permanently</button>
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
            $('#employeesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "language": {
                    "search": "Search employees:",
                    "emptyTable": "No <?= $show_inactive ? 'inactive' : 'active' ?> employees found"
                }
            });
            
            // Phone number validation and formatting for add form
            $('#phone').on('input', function() {
                let input = $(this).val().replace(/\D/g, '');
                // Ensure first digit is 9 or 7
                if (input.length > 0 && !['9', '7'].includes(input[0])) {
                    input = '9' + input.substring(1);
                }
                
                // Limit to 9 digits
                if (input.length > 9) {
                    input = input.substring(0, 9);
                }
                
                $(this).val(input);
            });
            
            // Phone number validation and formatting for edit form
            $('#edit_phone').on('input', function() {
                let input = $(this).val();
                
                // Remove any non-digit characters except the + sign at the beginning
                input = input.replace(/(?!^\+)\D/g, '');
                
                // If doesn't start with +251, add it
                if (!input.startsWith('+251') && input.length > 0) {
                    if (input.startsWith('+')) {
                        input = '+251';
                    } else if (input.startsWith('251')) {
                        input = '+' + input;
                    } else if (input.startsWith('0')) {
                        input = '+251' + input.substring(1);
                    } else {
                        input = '+251' + input;
                    }
                }
                
                // Ensure the digit after +251 is 9 or 7
                if (input.startsWith('+251') && input.length > 4) {
                    let afterCode = input.substring(4);
                    if (afterCode.length > 0 && !['9', '7'].includes(afterCode[0])) {
                        afterCode = '9' + (afterCode.length > 1 ? afterCode.substring(1) : '');
                    }
                    input = '+251' + afterCode;
                }
                
                // Limit to +251 plus 9 digits
                if (input.length > 13) {
                    input = input.substring(0, 13);
                }
                
                $(this).val(input);
            });
            
            // Validation for first and last name in add form
            $('#first_name, #last_name').on('input', function() {
                let input = $(this).val();
                
                // Capitalize first letter
                if (input.length > 0) {
                    input = input.charAt(0).toUpperCase() + input.slice(1);
                }
                
                // Remove any non-letter characters
                input = input.replace(/[^a-zA-Z]/g, '');
                
                $(this).val(input);
            });
            
            // Validation for first and last name in edit form
            $('#edit_first_name, #edit_last_name').on('input', function() {
                let input = $(this).val();
                
                // Capitalize first letter
                if (input.length > 0) {
                    input = input.charAt(0).toUpperCase() + input.slice(1);
                }
                
                // Remove any non-letter characters
                input = input.replace(/[^a-zA-Z]/g, '');
                
                $(this).val(input);
            });
            
            // Form validation before submission
            $('#addEmployeeForm').on('submit', function(e) {
                let firstName = $('#first_name').val();
                let lastName = $('#last_name').val();
                let phone = $('#phone').val();
                let isValid = true;
                
                // Validate first name
                if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                    alert('First name must start with a capital letter and contain only letters');
                    isValid = false;
                }
                
                // Validate last name
                if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                    alert('Last name must start with a capital letter and contain only letters');
                    isValid = false;
                }
                
                // Validate phone (if provided)
                if (phone && !/^[79]\d{8}$/.test(phone)) {
                    alert('Phone number must be 9 digits starting with 9 or 7');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Form validation before edit submission
            $('#editEmployeeForm').on('submit', function(e) {
                let firstName = $('#edit_first_name').val();
                let lastName = $('#edit_last_name').val();
                let phone = $('#edit_phone').val();
                let isValid = true;
                
                // Validate first name
                if (!/^[A-Z][a-zA-Z]*$/.test(firstName)) {
                    alert('First name must start with a capital letter and contain only letters');
                    isValid = false;
                }
                
                // Validate last name
                if (!/^[A-Z][a-zA-Z]*$/.test(lastName)) {
                    alert('Last name must start with a capital letter and contain only letters');
                    isValid = false;
                }
                
                // Validate phone (if provided)
                if (phone && !/^\+251[79]\d{8}$/.test(phone)) {
                    alert('Phone number must be in Ethiopian format: +251 followed by 9 or 7 and then 8 more digits');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Populate edit modal
            $('#editEmployeeModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const id = button.data('id');
                const firstName = button.data('firstname');
                const lastName = button.data('lastname');
                const phone = button.data('phone');
                
                $('#edit_employee_id').val(id);
                $('#edit_first_name').val(firstName);
                $('#edit_last_name').val(lastName);
                $('#edit_phone').val(phone);
            });
            
            // Populate deactivate modal
            $('#deactivateEmployeeModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const id = button.data('id');
                const name = button.data('name');
                
                $('#deactivate_employee_id').val(id);
                $('#deactivate_employee_name').text(name);
            });
            
            // Populate reactivate modal
            $('#reactivateEmployeeModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const id = button.data('id');
                const name = button.data('name');
                
                $('#reactivate_employee_id').val(id);
                $('#reactivate_employee_name').text(name);
            });
            
            // Populate delete modal
            $('#deleteEmployeeModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const id = button.data('id');
                const name = button.data('name');
                
                $('#delete_employee_id').val(id);
                $('#delete_employee_name').text(name);
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
if(isset($connection)) {
    $connection->close();
}
?>