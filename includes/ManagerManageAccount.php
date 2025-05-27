<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Account | Salale University CMS</title>
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f0f8 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #343a40;
        }
        
        .header {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .logo-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .main-content {
            flex: 1;
            padding: 40px 0;
        }
        
        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header-section h2 {
            font-weight: 600;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header-section p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .action-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 30px;
        }
        
        .action-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .action-card-header {
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .action-card-header.contractor {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .action-card-header.consultant {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .action-card-header.engineer {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .action-card-header.users {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .action-icon {
            font-size: 48px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
        }
        
        .action-card-body {
            padding: 20px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .action-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .action-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .action-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn:hover {
            background: #2980b9;
            color: white;
        }
        
        .action-btn.contractor {
            background: #3498db;
        }
        
        .action-btn.contractor:hover {
            background: #2980b9;
        }
        
        .action-btn.consultant {
            background: #2ecc71;
        }
        
        .action-btn.consultant:hover {
            background: #27ae60;
        }
        
        .action-btn.engineer {
            background: #f39c12;
        }
        
        .action-btn.engineer:hover {
            background: #e67e22;
        }
        
        .action-btn.users {
            background: #9b59b6;
        }
        
        .action-btn.users:hover {
            background: #8e44ad;
        }
        
        .back-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="logo-container">
                <img src="../images/LOGO.png" alt="Salale University Logo" height="50" class="me-3">
            </div>
            <div>
                <a href="ManagerDashboard.php" class="back-btn me-2">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <div class="dashboard-container">
            <div class="header-section">
                <h2><i class="fas fa-users-cog me-2"></i>Account Management</h2>
                <p>Create and manage user accounts for your construction projects</p>
            </div>
            
            <div class="action-dashboard">
                <!-- Create Contractor -->
                <div class="action-card">
                    <div class="action-card-header contractor">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="action-card-body">
                        <div>
                            <div class="action-name">Create Contractor</div>
                            <div class="action-description">
                                Add new contractors to handle construction projects. Contractors are responsible for executing construction work according to specifications.
                            </div>
                        </div>
                        <a href="ManagerCreateContractor.php" class="action-btn contractor">
                            <i class="fas fa-plus-circle me-1"></i> Add Contractor
                        </a>
                    </div>
                </div>

                <!-- Create Consultant -->
                <div class="action-card">
                    <div class="action-card-header consultant">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="action-card-body">
                        <div>
                            <div class="action-name">Create Consultant</div>
                            <div class="action-description">
                                Add new consultants to provide expert advice on construction projects. Consultants offer specialized knowledge and oversight.
                            </div>
                        </div>
                        <a href="ManagerCreateConsultant.php" class="action-btn consultant">
                            <i class="fas fa-plus-circle me-1"></i> Add Consultant
                        </a>
                    </div>
                </div>
                
                <!-- Create Site Engineer -->
                <div class="action-card">
                    <div class="action-card-header engineer">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="action-card-body">
                        <div>
                            <div class="action-name">Create Site Engineer</div>
                            <div class="action-description">
                                Add new site engineers to oversee day-to-day construction activities. Engineers ensure quality control and technical compliance.
                            </div>
                        </div>
                        <a href="ManagerCreateSiteEng.php" class="action-btn engineer">
                            <i class="fas fa-plus-circle me-1"></i> Add Engineer
                        </a>
                    </div>
                </div>

                <!-- Manage Users -->
                <div class="action-card">
                    <div class="action-card-header users">
                        <div class="action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="action-card-body">
                        <div>
                            <div class="action-name">Manage Users</div>
                            <div class="action-description">
                                View, edit, and manage all user accounts including contractors, consultants, and site engineers. Update roles and permissions as needed.
                            </div>
                        </div>
                        <a href="ManagerManageUsers.php" class="action-btn users">
                            <i class="fas fa-cog me-1"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; 2025 Salale University Construction Management System</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">Designed for efficient user management</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $connection->close(); ?>
