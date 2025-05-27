<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$role = $_GET['role'] ?? '';
include '../db_connection.php';

$stmt = $connection->prepare("SELECT * FROM users WHERE Role = ?");
$stmt->bind_param("s", $role);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage <?= htmlspecialchars($role) ?>s</title>
    <style>
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .user-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .back-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background: #4e73df;
            color: white;
            text-align: center;
            width: fit-content;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;"><?= htmlspecialchars($role) ?>s</h1>
    
    <div class="user-grid">
        <?php while ($user = $users->fetch_assoc()): ?>
        <div class="user-card">
            <img src="../uploads/avatars/<?= $user['UserId'] ?>.jpg" 
                 onerror="this.src='../images/default-avatar.jpg'" 
                 class="user-avatar">
            <h4><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></h4>
            <p><?= htmlspecialchars($user['Email']) ?></p>
        </div>
        <?php endwhile; ?>
    </div>
    
    <a href="role_dashboard.php" class="back-btn">Back</a>
</body>
</html>