<?php
session_start();

// Check if the user is logged in and is a contractor.
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Contractor') {
    // Redirect to login or another appropriate page.
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees | Construction Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            color: var(--dark-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .card-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.2);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #19b67d;
            border-color: #19b67d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 200, 138, 0.2);
        }
        
        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            background-color: #2fa6b9;
            border-color: #2fa6b9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(54, 185, 204, 0.2);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #d63a2f;
            border-color: #d63a2f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 74, 59, 0.2);
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background-color: white;
            color: var(--primary-color);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .feature-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .feature-text {
            color: #6c757d;
            flex-grow: 1;
        }
        
        .action-btn {
            margin-top: 1.5rem;
            width: 100%;
        }
        
        .footer {
            margin-top: auto;
            background-color: #2c3e50;
            color: white;
            padding: 2rem 0;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .social-icons {
            display: flex;
            gap: 1rem;
        }
        
        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s;
        }
        
        .social-icon:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="ContractorDashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Page Header -->
    <header class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6"><br><br>
                    <h1 class="mb-0">Employee Management</h1>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-inline-block">
                        <span class="text-white-50">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Contractor') ?></span>
                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <a href="../login.php" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                            <a href="ContractorProfile.php" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-user-circle me-1"></i> Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container py-4">
   
        <!-- Main Features -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus feature-icon"></i>
                        <h3 class="feature-title">Create Employee</h3>
                        <p class="feature-text">Add new employees to your team. Enter their details, assign roles, and set permissions.</p>
                        <a href="ContractorCreateAccount.php" class="btn btn-success action-btn">
                            <i class="fas fa-plus-circle me-2"></i>Create Employee
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users-cog feature-icon"></i>
                        <h3 class="feature-title">Manage Employees</h3>
                        <p class="feature-text">View, edit, and manage your existing employees. Update information and track performance.</p>
                        <a href="ContractorManageAccount.php" class="btn btn-primary action-btn">
                            <i class="fas fa-cogs me-2"></i>Manage Employees
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tasks feature-icon"></i>
                        <h3 class="feature-title">Assign Tasks</h3>
                        <p class="feature-text">Assign specific tasks to employees, set deadlines, and monitor progress efficiently.</p>
                        <a href="ContractorAssignTasks.php" class="btn btn-info action-btn">
                            <i class="fas fa-clipboard-check me-2"></i>Assign Tasks
                        </a>
                    </div>
                </div>
            </div>
        </div>        
    </main>

   

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Simulated data for demonstration purposes
        // In a real application, you would fetch this data from your database
        document.addEventListener('DOMContentLoaded', function() {
            // Simulate loading data with a slight delay to show animation
            setTimeout(function() {
                document.getElementById('totalEmployees').textContent = '24';
                document.getElementById('activeEmployees').textContent = '18';
                document.getElementById('assignedEmployees').textContent = '15';
                document.getElementById('newEmployees').textContent = '3';
            }, 500);
        });
    </script>
</body>
</html>
