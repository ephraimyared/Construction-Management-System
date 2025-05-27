<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports | SLU Construction Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 900px;
            margin: 50px auto;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: none;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-action {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            font-weight: 500;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .btn-prepare {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .btn-prepare:hover {
            background: linear-gradient(135deg, #2980b9, #2573a7);
        }
        
        .btn-view {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #27ae60, #219d54);
        }
        
        .icon-container {
            background-color: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .btn-text {
            display: flex;
            flex-direction: column;
        }
        
        .btn-text small {
            opacity: 0.8;
            font-size: 0.85rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #6c757d;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #343a40;
            transform: translateX(-5px);
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .page-title {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="ManagerDashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h2 class="page-title text-center">Manage Project Reports</h2>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt me-2"></i> Report Management</h3>
            </div>
            <div class="card-body">
                <p class="text-center text-muted mb-4">Select an option to manage your construction project reports</p>
                
                <a href="PMprepare_report.php" class="btn-action btn-prepare">
                    <div class="icon-container">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div class="btn-text">
                        <span>Prepare Report</span>
                        <small>Create new construction progress reports</small>
                    </div>
                </a>
                
                <a href="PMViewReports.php" class="btn-action btn-view">
                    <div class="icon-container">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="btn-text">
                        <span>View Reports</span>
                        <small>Access and manage your existing reports</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
