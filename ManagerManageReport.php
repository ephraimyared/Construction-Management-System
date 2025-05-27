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
    <title>Manage Reports | Salale University CMS</title>

    <!-- Latest Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (for icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
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
            background-color: white;
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
        
        .btn-report {
            padding: 18px 25px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .btn-report:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-prepare {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: white;
        }
        
        .btn-prepare:hover {
            background: linear-gradient(135deg, #084298, #052c65);
            color: white;
        }
        
        .btn-generate {
            background: linear-gradient(135deg, #198754, #0f5132);
            color: white;
        }
        
        .btn-generate:hover {
            background: linear-gradient(135deg, #0f5132, #0a3622);
            color: white;
        }
        
        .btn-icon {
            font-size: 1.8rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .btn-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        
        .btn-text small {
            font-size: 0.8rem;
            opacity: 0.8;
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
        
        .report-info {
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .report-info h5 {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .report-info ul {
            padding-left: 20px;
        }
        
        .report-info li {
            margin-bottom: 8px;
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
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0"><i class="fas fa-file-alt me-2"></i>Project Manager Report Panel</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted mb-4">Select an option to manage construction project reports</p>
                        
                        <div class="report-actions">
                            <a href="PMprepare_report.php" class="btn btn-report btn-prepare w-100">
                                <div class="btn-icon">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div class="btn-text">
                                    <span>Prepare Report</span>
                                    <small>Create new construction progress reports</small>
                                </div>
                            </a>
                            
                            <a href="PMViewReports.php" class="btn btn-report btn-generate w-100">
                                <div class="btn-icon">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <div class="btn-text">
                                    <span>Generate Report</span>
                                    <small>View and export existing project reports</small>
                                </div>
                            </a>
                        </div>
                        
                        <div class="report-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Report Management Guidelines</h5>
                            <ul>
                                <li><strong>Prepare Report:</strong> Create detailed construction progress reports, including milestones, challenges, and resource utilization.</li>
                                <li><strong>Generate Report:</strong> View, filter, and export reports for stakeholder meetings and administrative reviews.</li>
                                <!-- <li><strong>Frequency:</strong> Weekly progress reports are recommended for active construction projects.</li> -->
                                <!-- <li><strong>Documentation:</strong> Include relevant photos and supporting documents with your reports.</li> -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
