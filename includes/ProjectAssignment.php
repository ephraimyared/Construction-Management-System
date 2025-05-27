<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: ../unauthorized.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Assignment | Salale University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            font-weight: 600;
            padding: 20px;
            border-bottom: none;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .btn-assignment {
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 220px;
        }
        
        .btn-assignment:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-contractor {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: white;
        }
        
        .btn-contractor:hover {
            background: linear-gradient(135deg, #084298, #052c65);
            color: white;
        }
        
        .btn-consultant {
            background: linear-gradient(135deg, #198754, #0f5132);
            color: white;
        }
        
        .btn-consultant:hover {
            background: linear-gradient(135deg, #0f5132, #0a3622);
            color: white;
        }
        
        .btn-engineer {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #343a40;
        }
        
        .btn-engineer:hover {
            background: linear-gradient(135deg, #e0a800, #ba8b00);
            color: #343a40;
        }
        
        .btn-icon {
            font-size: 1.5rem;
            margin-right: 10px;
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
            <a href="ManagerDashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0"><i class="fas fa-tasks me-2"></i>Project Assignment Panel</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted mb-5">Select the appropriate role to assign project tasks and responsibilities</p>
                        
                        <div class="row justify-content-center g-4">
                            <div class="col-md-4">
                                <a href="ManagerAssignToContractor.php" class="btn btn-assignment btn-contractor w-100 h-100 py-4">
                                    <div class="d-flex flex-column align-items-center">
                                         <i class="fas fa-user-gear btn-icon mb-3"></i>
                                        <span>Assign to Contractor</span>
                                        <small class="mt-2 text-light">For construction execution</small>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4">
                                <a href="ManagerAssignToConsultant.php" class="btn btn-assignment btn-consultant w-100 h-100 py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-user-tie btn-icon mb-3"></i>
                                        <span>Assign to Consultant</span>
                                        <small class="mt-2 text-light">For expert guidance</small>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-4">
                                <a href="ManagerAssignToSiteEngineer.php" class="btn btn-assignment btn-engineer w-100 h-100 py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-user-gear btn-icon mb-3"></i>
                                        <span>Assign to Site Engineer</span>
                                        <small class="mt-2 text-dark">For on-site supervision</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                        
                        <div class="text-center mt-5">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Assign tasks to appropriate roles to ensure efficient project execution and management
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a2e0e6b2a5.js" crossorigin="anonymous"></script>
</body>
</html>
