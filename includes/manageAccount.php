<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

include '../db_connection.php';
$result = $connection->query("SELECT UserId, FirstName, LastName, Email, Role FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .table-container { background: white; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 20px; }
        .btn-edit { background-color: #4e73df; color: white; }
        .btn-delete { background-color: #e74a3b; color: white; }
        .btn-add { background-color: #1cc88a; color: white; margin-bottom: 20px; }
        .modal-header { background-color: #4e73df; color: white; }
        .success-message { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">User Management</h1>
        <button class="btn btn-add" onclick="window.location.href='AdminCreateAccount.php'">
            <i class="fas fa-plus"></i> Add New User
        </button>
        
        <div class="table-container">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['UserId'] ?></td>
                        <td><?= $row['FirstName'] . ' ' . $row['LastName'] ?></td>
                        <td><?= $row['Email'] ?></td>
                        <td><?= $row['Role'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-edit" onclick="openEditForm(
                                <?= $row['UserId'] ?>,
                                '<?= $row['FirstName'] ?>',
                                '<?= $row['LastName'] ?>',
                                '<?= $row['Email'] ?>',
                                '<?= $row['Role'] ?>'
                            )">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-delete" onclick="confirmDelete(<?= $row['UserId'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" onclick="closeEditForm()"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editUserId" name="userId">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editFirstName" name="firstName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editLastName" name="lastName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="Admin">Admin</option>
                                <option value="Project Manager">Project Manager</option>
                                <option value="Contractor">Contractor</option>
                                <option value="Site Engineer">Site Engineer</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                        <div class="success-message" id="message"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeEditForm()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Open modal with user data
        function openEditForm(userId, firstName, lastName, email, role) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editFirstName').value = firstName;
            document.getElementById('editLastName').value = lastName;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            
            // Show modal using Bootstrap
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }

        // Close modal
        function closeEditForm() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            modal.hide();
        }

        // Handle form submission
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            fetch('edit_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    messageDiv.className = 'success-message';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> User updated successfully!';
                    setTimeout(() => {
                        closeEditForm();
                        location.reload();
                    }, 1500);
                } else {
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                }
            });
        });

        // Delete confirmation
        function confirmDelete(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('delete_user.php?userId=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>