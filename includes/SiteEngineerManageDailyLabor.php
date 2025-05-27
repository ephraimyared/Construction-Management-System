<?php
session_start();
include '../db_connection.php';

// Ensure the user is logged in and is a Site Engineer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}

$site_engineer_id = $_SESSION['user_id'];

// Fetch user details for the sidebar
$user_query = "SELECT FirstName, LastName FROM users WHERE UserID = ?";
$user_stmt = $connection->prepare($user_query);
$user_stmt->bind_param("i", $site_engineer_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_name = $user['FirstName'] . ' ' . $user['LastName'];

// Fetch projects for quick access
$projects_query = "SELECT project_id, project_name FROM projects WHERE site_engineer_id = ? LIMIT 5";
$projects_stmt = $connection->prepare($projects_query);
$projects_stmt->bind_param("i", $site_engineer_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Daily Labor | Site Engineer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2ecc71;
            --secondary-dark: #27ae60;
            --accent-color: #f39c12;
            --danger-color: #e74c3c;
            --danger-dark: #c0392b;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
            
            --transition-fast: all 0.2s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--gray-800);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark-color) 0%, var(--gray-800) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            transition: var(--transition-normal);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo:hover {
            color: var(--light-color);
        }
        
        .logo i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .menu-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--gray-500);
            padding: 0.75rem 1.5rem;
            margin-top: 1rem;
        }
        
        .nav-item {
            padding: 0 1rem;
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: var(--border-radius-md);
            transition: var(--transition-fast);
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-link i {
            font-size: 1.25rem;
            min-width: 1.5rem;
            text-align: center;
        }
        
        .user-profile {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin: 0;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: none;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            font-weight: 500;
        }
        
        .logout-btn:hover {
            background-color: rgba(231, 76, 60, 0.2);
            color: white;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: var(--transition-normal);
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-separator {
            font-size: 0.75rem;
        }
        
        /* Card Styles */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition-normal);
            border: none;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .card-icon-primary {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
        }
        
        .card-icon-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--secondary-color);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
        }
        
        .card-text {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            flex: 1;
        }
        
        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-fast);
            margin-top: auto;
        }
        
        .card-link:hover {
            color: var(--primary-dark);
        }
        
        .card-link i {
            font-size: 0.875rem;
            transition: var(--transition-fast);
        }
        
        .card-link:hover i {
            transform: translateX(3px);
        }
        
        /* Quick Access Section */
        .quick-access {
            background-color: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .quick-access-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-access-title i {
            color: var(--primary-color);
        }
        
        .project-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .project-item {
            background-color: var(--gray-100);
            border-radius: var(--border-radius-md);
            padding: 0.75rem 1rem;
            color: var(--gray-700);
            text-decoration: none;
            transition: var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .project-item:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .project-item i {
            font-size: 0.875rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: visible;
            }
            
            .logo span, .menu-heading, .nav-link span, .user-details, .logout-btn span {
                display: none;
            }
            
            .nav-link {
                justify-content: center;
                padding: 1rem;
            }
            
            .nav-link i {
                font-size: 1.5rem;
                margin: 0;
            }
            
            .user-profile {
                display: flex;
                justify-content: center;
                padding: 1rem;
            }
            
            .user-info {
                margin-bottom: 0;
            }
            
            .logout-btn {
                justify-content: center;
                padding: 1rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
            
            .main-content {
                padding:
            @media (max-width: 768px) {
                .card-grid {
                    grid-template-columns: 1fr;
                }
                
                .main-content {
                    padding: 1.5rem;
                }
                
                .page-title {
                    font-size: 1.5rem;
                }
            }
            
            @media (max-width: 576px) {
                .sidebar {
                    width: 0;
                    transform: translateX(-100%);
                }
                
                .sidebar.show {
                    width: 280px;
                    transform: translateX(0);
                }
                
                .sidebar.show .logo span, 
                .sidebar.show .menu-heading, 
                .sidebar.show .nav-link span, 
                .sidebar.show .user-details, 
                .sidebar.show .logout-btn span {
                    display: block;
                }
                
                .main-content {
                    margin-left: 0;
                }
                
                .mobile-toggle {
                    display: block;
                    position: fixed;
                    top: 1rem;
                    left: 1rem;
                    z-index: 1100;
                    background-color: var(--primary-color);
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: var(--shadow-md);
                    cursor: pointer;
                }
            }
            }
            img{
    max-width: 80%; 
    height: auto; 
}
        </style>
    </head>
    <body>
        <div class="app-container">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <a href="SiteEngineerDashboard.php" class="logo">
                         <span class="logo-text"><img src="../images/LOGO.png" alt="SLU Logo"> </span>
                    </a>
                </div>
                
                <div class="nav-menu">     
                    <div class="nav-item">
                        <a href="SiteEngineerDashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="SiteEngineerViewTasks.php" class="nav-link">
                            <i class="fas fa-tasks"></i>
                            <span>View Tasks</span>
                        </a>
                    </div>
                 
                    
                    <div class="nav-item">
                        <a href="SiteEngineerManageDailyLabor.php" class="nav-link active">
                         <i class="fas fa-hard-hat"></i>
                            <span>Track Labor</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="SiteEngineerSubmitReport.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            <span>Submit Reports</span>
                        </a>
                    </div>
                       <div class="nav-item">
                       <a href="EngineerProfile.php" class="nav-link">
                          <i class="fas fa-user"></i>
                           <span>My Profile</span>
                        </a>
                    </div>
                  <br><br><br>
                
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <p class="user-name"><?= htmlspecialchars($user_name) ?></p>
                            <p class="user-role">Site Engineer</p>
                        </div>
                    </div>
                    
                    <a href="../logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </aside>
            
            <!-- Mobile Toggle Button -->
            <button class="mobile-toggle d-md-none" id="mobile-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Main Content -->
            <main class="main-content">
                <div class="page-header">
                    <h1 class="page-title">Manage Daily Labor</h1>
                    <div class="breadcrumb">
                        <a href="SiteEngineerDashboard.php">Dashboard</a>
                        <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                        <span>Manage Daily Labor</span>
                    </div>
                </div>
                
            
                
                <!-- Main Cards -->
                <div class="card-grid">
                    <!-- Add Labor Card -->
                    <div class="card">
                        <div class="card-body">
                            <div class="card-icon card-icon-success">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3 class="card-title">Add Daily Labor</h3>
                            <p class="card-text">
                                Record new labor activities for your projects. Track worker hours, tasks performed, and payments.
                            </p>
                            <a href="SiteEngineerAddDailyLabor.php" class="card-link">
                                Add New Record <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- View Labor Records Card -->
                    <div class="card">
                        <div class="card-body">
                            <div class="card-icon card-icon-primary">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3 class="card-title">View Labor Records</h3>
                            <p class="card-text">
                                Access and manage existing labor records. Filter by project, date, or worker to find specific entries.
                            </p>
                            <a href="SiteEngineerViewDailyLabor.php" class="card-link">
                                View Records <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Mobile sidebar toggle
            document.addEventListener('DOMContentLoaded', function() {
                const mobileToggle = document.getElementById('mobile-toggle');
                const sidebar = document.getElementById('sidebar');
                
                if (mobileToggle) {
                    mobileToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('show');
                    });
                }
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggle = mobileToggle.contains(event.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth < 576 && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                });
            });
        </script>
    </body>
</html>

<?php
// Close database connections
if (isset($user_stmt)) $user_stmt->close();
if (isset($projects_stmt)) $projects_stmt->close();
if (isset($recent_stmt)) $recent_stmt->close();
$connection->close();
?>
