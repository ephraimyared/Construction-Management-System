<?php
session_start();
include '../db_connection.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get admin info
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE UserID = ?";
$stmt = $connection->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get counts for dashboard
$pending_projects_query = "SELECT COUNT(*) as count FROM projects WHERE decision_status IS NULL";
$pending_result = $connection->query($pending_projects_query);
$pending_count = $pending_result->fetch_assoc()['count'];

$submitted_projects_query = "SELECT COUNT(*) as count FROM completed_projects WHERE status = 'Pending'";
$submitted_result = $connection->query($submitted_projects_query);
$submitted_count = $submitted_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects | Admin Dashboard</title>
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--dark);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            clip-path: polygon(100% 0, 0% 100%, 100% 100%);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            opacity: 0.8;
            margin-bottom: 0;
        }
        
        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .back-btn i {
            margin-right: 0.5rem;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-weight: 600;
            border: none;
            padding: 1.25rem;
        }
        
        .card-body {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .icon-circle {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--accent), #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 2.5rem;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .card-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .badge-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #2980b9);
            border: none;
            border-radius: 30px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, var(--accent));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-icon {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
            }
            
            .back-button {
                position: static;
                margin-bottom: 1rem;
                display: flex;
                justify-content: center;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .icon-circle {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="back-button">
                <a href="AdminDashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <h1><i class="fas fa-tasks"></i> Project and Task Management</h1>
            <p>Review, approve tasks, and manage projects submitted by project managers</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-clipboard-check"></i> Project Task Approval
                    </div>
                    <div class="card-body">
                        <div class="position-relative">
                            <div class="icon-circle">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <?php if ($pending_count > 0): ?>
                                <span class="badge-count"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title">Approve Project Tasks</h5>
                        <p class="card-text">Review and approve additional project Task proposals submitted by project managers.</p>
                        <a href="ApproveProjects.php" class="btn btn-primary">
                            <i class="fas fa-thumbs-up btn-icon"></i> Approve Projects
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-clipboard-list"></i> Completed Projects
                    </div>
                    <div class="card-body">
                        <div class="position-relative">
                            <div class="icon-circle">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <?php if ($submitted_count > 0): ?>
                                <span class="badge-count"><?php echo $submitted_count; ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title">View Submitted Projects</h5>
                        <p class="card-text">Review completed projects submitted by project managers for final approval.</p>
                        <a href="ViewSubmittedProjects.php" class="btn btn-primary">
                            <i class="fas fa-eye btn-icon"></i> View Submitted Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $connection->close(); ?>
