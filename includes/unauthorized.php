<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Salale University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .unauthorized-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: white;
            text-align: center;
        }
        .icon-container {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .btn-home {
            background-color: #2c3e50;
            color: white;
            margin-top: 20px;
        }
        .btn-home:hover {
            background-color: #1a252f;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="unauthorized-container">
            <div class="icon-container">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h1 class="mb-3">Access Denied</h1>
            
            <?php if (isset($_SESSION['role'])): ?>
                <p class="lead">You are logged in as <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>, but you don't have permission to access this page.</p>
            <?php else: ?>
                <p class="lead">You need proper authorization to access this page.</p>
            <?php endif; ?>
            
            <p>Please contact the system administrator if you believe this is an error.</p>
            
            <div class="d-flex justify-content-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= ($_SESSION['role'] === 'Admin') ? 'admin_dashboard.php' : 'dashboard.php' ?>" 
                       class="btn btn-home">
                        <i class="bi bi-house-door"></i> Return to Dashboard
                    </a>
                <?php else: ?>
                    <a href="../login.php" class="btn btn-home">
                        <i class="bi bi-box-arrow-in-right"></i> Go to Login
                    </a>
                <?php endif; ?>
                
                <a href="mailto:admin@salaleuniversity.edu.et" class="btn btn-outline-secondary">
                    <i class="bi bi-envelope"></i> Contact Admin
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
