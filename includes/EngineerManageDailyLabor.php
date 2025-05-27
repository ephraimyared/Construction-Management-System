<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../unauthorized.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Labor Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .panel {
            background: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        h2 {
            margin-bottom: 30px;
            font-weight: bold;
            color: #198754;
        }
        .btn-custom {
            width: 80%;
            margin: 10px auto;
            padding: 15px;
            font-size: 18px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="panel">
    <h2>Daily Labor Management</h2>
    <a href="EngineerCreateDailyLabor.php" class="btn btn-success btn-custom">➕ Create Daily Labor</a>
    <a href="EngineerViewManageLabor.php" class="btn btn-outline-success btn-custom">📋 Manage Labor</a>
</div>

</body>
</html>
