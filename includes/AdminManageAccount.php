<?php
session_start();
include '../db_connection.php';

// Check if the user is logged in and has admin rights
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch admin user info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get selected role from URL parameter if present
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';

// Define role-specific management pages
$role_management_pages = [
    'Admin' => 'admin_management.php',
    'Project_Manager' => 'manager_management.php',
    'Contractor' => 'contractor_management.php',
    'Consultant' => 'consultant_management.php',
    'Site_Engineer' => 'engineer_management.php',
    'Employee' => 'employee_management.php'
];

// Define role-specific creation pages
$role_creation_pages = [
    'Admin' => 'create_admin.php',
    'Project_Manager' => 'create_manager.php',
    'Contractor' => 'create_contractor.php',
    'Consultant' => 'create_consultant.php',
    'Site_Engineer' => 'create_engineer.php',
    'Employee' => 'create_employee.php'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Manage Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Body Styling */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
            height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(180deg, #ff6600, #ff8533);
            padding-top: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .sidebar-brand i {
            font-size: 28px;
            margin-right: 10px;
            color: white;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
            padding: 0 15px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar-menu i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                        url('../images/Admin.jpg') no-repeat center center fixed;
            background-size: cover;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h2 {
            color: #ff6600;
            margin: 0;
            font-size: 24px;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #333;
            background: transparent;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
        }

        .user-dropdown .dropdown-toggle:hover,
        .user-dropdown .dropdown-toggle:focus {
            background-color: rgba(255, 102, 0, 0.1);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ff6600, #ff8533);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 15px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px 20px;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 102, 0, 0.1);
            color: #ff6600;
        }

        .dropdown-item i {
            margin-right: 10px;
            color: #ff6600;
        }

        .dropdown-divider {
            margin: 10px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .dropdown-item.logout {
            color: #dc3545;
        }

        .dropdown-item.logout i {
            color: #dc3545;
        }

        /* Account Management Section */
        .account-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .role-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .role-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .role-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .role-button.active {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .role-button.active::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 15px solid;
        }

        .role-button i {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .role-button h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .role-button p {
            margin: 10px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .project-manager {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .project-manager.active::after {
            border-top-color: #2980b9;
        }

        .contractor {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .contractor.active::after {
            border-top-color: #27ae60;
        }

        .consultant {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .consultant.active::after {
            border-top-color: #8e44ad;
        }

        .site-engineer {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .site-engineer.active::after {
            border-top-color: #c0392b;
        }
        
        .employee {
            background: linear-gradient(135deg, #f39c12, #d35400);
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
        
        .all-roles {
            background: linear-gradient(135deg, #34495e, #2c3e50);
        }
        
        .all-roles.active::after {
            border-top-color: #2c3e50;
        }
        .employee.active::after {
            border-top-color: #d35400;
        }

        .role-specific-actions {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .action-button i {
            margin-right: 10px;
            font-size: 20px;
        }

        .create-account {
            background: linear-gradient(135deg, #ff6600, #ff8533);
        }

        .manage-users {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: #f1f1f1;
            color: #333;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #e0e0e0;
            transform: translateX(-5px);
        }

        .back-button i {
            margin-right: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                text-align: center;
            }

            .sidebar-header h3, .sidebar-menu span {
                display: none;
            }

            .sidebar-brand i {
                margin-right: 0;
            }

            .sidebar-menu a {
                justify-content: center;
            }

            .sidebar-menu i {
                margin-right: 0;
                font-size: 20px;
            }

            .main-content {
                margin-left: 70px;
            }
            
            .user-dropdown .dropdown-toggle span {
                display: none;
            }
            
            .role-buttons, .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-tachometer-alt"></i>
                <h3>Admin Panel</h3>
            </div>
        </div>
            <ul class="sidebar-menu">
            <li>
                <a href="AdminDashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="AdminManageAccount.php" class="active">
                    <i class="fas fa-user-cog"></i>
                    <span>Manage Account</span>
                </a>
            </li>
            <li>
                <a href="ApproveProjects.php">
                    <i class="fas fa-check-circle"></i>
                    <span>Task Approval </span>
                </a>
            </li>
            <li>
                <a href="AdminGenerateReport.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Generate Report</span>
                </a>
            </li>
            <li>
             <a href="AdminViewProfile.php" class="active">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
            </a>
        </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2><i class="fas fa-user-cog"></i> Manage Accounts</h2>
            
            <!-- User Profile Dropdown -->
            <div class="user-dropdown dropdown">
                <button class="dropdown-toggle" type="button" id="userMenu" onclick="toggleDropdown()">
                    <div class="user-avatar">
                        <?php echo isset($user['FirstName']) ? substr($user['FirstName'], 0, 1) : 'A'; ?>
                    </div>
                    <span><?php echo isset($user['FirstName']) ? htmlspecialchars($user['FirstName']) : 'Admin'; ?></span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a class="dropdown-item" href="AdminViewProfile.php">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item logout" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <?php if ($selected_role): ?>
            <!-- Back button when a role is selected -->
            <a href="AdminManageAccount.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to All Roles
            </a>
        <?php endif; ?>

        <!-- Create User by Role Section -->
        <div class="account-section">
            <h3 class="section-title">
                <?php echo $selected_role ? 'Manage ' . str_replace('_', ' ', $selected_role) . ' Accounts' : 'Create User by Role'; ?>
            </h3>
            
            <?php if (!$selected_role): ?>
                <!-- Show role buttons when no role is selected -->
                <div class="role-buttons">
                    <div onclick="window.location.href='AdminManageAccount.php?role=Admin'" class="role-button admin">
                        <i class="fas fa-user-shield"></i>
                        <h4>Admin</h4>
                        <p>Create accounts for system administrators</p>
                    </div>
                    <div onclick="window.location.href='AdminManageAccount.php?role=Project_Manager'" class="role-button project-manager">
                        <i class="fas fa-user-tie"></i>
                        <h4>Project Manager</h4>
                        <p>Create accounts for project management staff</p>
                    </div>
                    <div onclick="window.location.href='AdminManageAccount.php?role=Contractor'" class="role-button contractor">
                        <i class="fas fa-hard-hat"></i>
                        <h4>Contractor</h4>
                        <p>Create accounts for construction contractors</p>
                    </div>
                    <div onclick="window.location.href='AdminManageAccount.php?role=Consultant'" class="role-button consultant">
                        <i class="fas fa-briefcase"></i>
                        <h4>Consultant</h4>
                        <p>Create accounts for project consultants</p>
                    </div>
                    <div onclick="window.location.href='AdminManageAccount.php?role=Site_Engineer'" class="role-button site-engineer">
                        <i class="fas fa-drafting-compass"></i>
                        <h4>Site Engineer</h4>
                        <p>Create accounts for on-site engineers</p>
                    </div>
                    <div onclick="window.location.href='AdminManageAccount.php?role=Employee'" class="role-button employee">
                        <i class="fas fa-user-hard-hat"></i>
                        <h4>Employee</h4>
                        <p>Create accounts for general employees</p>
                    </div>
                     <div onclick="window.location.href='actorsInOne.php'" class="role-button project-manager">
                        <i class="fas fa-user-hard-hat"></i>
                        <h4>All Users</h4>
                        <p>See all existing users</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show role-specific actions when a role is selected -->
                <div class="role-specific-actions" style="display: block;">
                    <div class="action-buttons">
                        <?php if($selected_role !== 'Employee'): ?>
                            <?php
                            // Determine the correct creation page based on the selected role
                            $creation_page = isset($role_creation_pages[$selected_role]) ? 
                                            $role_creation_pages[$selected_role] : 
                                            'AdminCreateAccount.php?role=' . $selected_role;
                            ?>
                            <a href="<?php echo $creation_page; ?>" class="action-button create-account">
                                <i class="fas fa-user-plus"></i> Create New <?php echo str_replace('_', ' ', $selected_role); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        // Determine the correct management page based on the selected role
                        $management_page = isset($role_management_pages[$selected_role]) ? 
                                          $role_management_pages[$selected_role] : 
                                          'AdminManageExistingUsers.php?role=' . $selected_role;
                        ?>
                        <a href="<?php echo $management_page; ?>" class="action-button manage-users">
                            <i class="fas fa-users-cog"></i> Manage Existing <?php echo str_replace('_', ' ', $selected_role); ?>s
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for dropdown functionality -->
    <script>
        function toggleDropdown() {
            document.getElementById("userDropdownMenu").classList.toggle("show");
        }

        // Close the dropdown if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-toggle')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php if(isset($connection)) $connection->close(); ?>
