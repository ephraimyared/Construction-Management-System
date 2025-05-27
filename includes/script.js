document.addEventListener("DOMContentLoaded", function () {
    // Open User Form Modal
    function openUserForm() {
        document.getElementById('userFormModal').style.display = 'block';
        document.getElementById('submitButton').innerText = "Add User"; // Set button text

        // Reset form values for new user
        document.getElementById('userId').value = '';
        document.getElementById('firstName').value = '';
        document.getElementById('lastName').value = '';
        document.getElementById('email').value = '';
        document.getElementById('role').value = 'Project Manager'; // Default role
    }

    // Close User Form Modal
    function closeUserForm() {
        document.getElementById('userFormModal').style.display = 'none';
    }

    // Edit User - Pre-fill form
    function editUser(userId, firstName, lastName, email, role) {
        document.getElementById('userId').value = userId;
        document.getElementById('firstName').value = firstName;
        document.getElementById('lastName').value = lastName;
        document.getElementById('email').value = email;
        document.getElementById('role').value = role;

        document.getElementById('submitButton').innerText = "Update User"; // Change button text

        openUserForm();
    }

    // Handle Form Submission
    document.getElementById("userForm").addEventListener("submit", function (event) {
        event.preventDefault(); // Prevent default form submission

        var formData = new FormData(this);

        fetch("add_user.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log("Server Response:", data);
            if (data.trim() === "success") {
                alert("User added successfully!");
                location.reload(); // Reload page to update user list
            } else {
                alert("Error: " + data); // Show error message
            }
        })
        .catch(error => console.error("Error:", error));
    });

    // Confirm Delete User
    function confirmDelete(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
            fetch('delete_user.php?userId=' + userId, { method: 'GET' })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "success") {
                    alert("User deleted successfully!");
                    location.reload();
                } else {
                    alert("Error: " + data);
                }
            })
            .catch(error => console.error("Error:", error));
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        let modal = document.getElementById("userFormModal");
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };

    // Attach functions to global scope
    window.openUserForm = openUserForm;
    window.closeUserForm = closeUserForm;
    window.editUser = editUser;
    window.confirmDelete = confirmDelete;
});
function approveProject(projectId) {
    if (confirm("Are you sure you want to approve this project?")) {
        window.location.href = 'includes/ApproveProjects.php?action=approve&projectId=' + projectId;
    }
}


function approveBudget(budgetId) {
    // Log the URL to the console for debugging
    console.log("Redirecting to: ../includes/ApproveBudget.php?action=approve&budgetId=" + budgetId);
    
    if (confirm("Are you sure you want to approve this budget?")) {
        window.location.href = '../includes/ApproveBudget.php?action=approve&budgetId=' + budgetId;
    }
}
function redirectToRole(role) {
    const rolePages = {
        'Project Manager': './manager_management.php',
        // ... other roles
    };

    if (rolePages[role]) {
        // Test if the file exists first
        fetch(rolePages[role])
            .then(response => {
                if (response.ok) {
                    window.location.href = rolePages[role];
                } else {
                    throw new Error('Page not found');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Could not load the management page. Please check the file exists.');
            });
    }
}

