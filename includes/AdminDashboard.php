<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: includes/adminLogin.php");
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Body Styling */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
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

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card i {
            font-size: 40px;
            color: #ff6600;
            margin-bottom: 15px;
        }

        .card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .card p {
            color: #666;
            margin-bottom: 20px;
        }

        .card-btn {
            background: linear-gradient(135deg, #ff6600, #ff8533);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .card-btn:hover {
            background: linear-gradient(135deg, #ff8533, #ff6600);
            transform: scale(1.05);
        }

        /* User Profile Dropdown */
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
            <a href="#" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="AdminManageAccount.php">
                <i class="fas fa-user-cog"></i>
                <span>Manage Account</span>
            </a>
        </li>
        <li>
            <a href="AdminManageProjects.php">
                <i class="fas fa-check-circle"></i>
                <span>Task Approval</span>
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
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            
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

        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-user-cog"></i>
                <h3>Manage Account</h3>
                <p>Add, edit, or remove user accounts from the system.</p>
                <button class="card-btn" onclick="window.location.href='AdminManageAccount.php'">Access</button>
            </div>
            
            <div class="card">
                <i class="fas fa-check-circle"></i>
                <h3>Projects Detail</h3>
                <p>Approve pending project task submissions and View Finished Projects.</p>
                <button class="card-btn" onclick="window.location.href='AdminManageProjects.php'">Access</button>
            </div>
            
            <div class="card">
                <i class="fas fa-file-alt"></i>
                <h3>Generate Report</h3>
                <p>Create and export system reports and analytics.</p>
                <button class="card-btn" onclick="window.location.href='AdminGenerateReport.php'">Access</button>
            </div>
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
