<?php
session_start();
include '../db_connection.php';

// Check if the user is logged in and has admin rights
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $project_id = $_POST['project_id'];
    $admin_comment = $_POST['admin_comment'];
    $decision = $_POST['decision'];
    
 // Set decision status based on button clicked
    $decision_status = ($decision === 'approve') ? 'Approved' : 'Rejected';
        
            // Update the project with decision status and admin comment
    $update_query = "UPDATE projects SET 
                    decision_status = ?,
                    admin_comment = ?
                    WHERE project_id = ?";
                    
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("ssi", $decision_status, $admin_comment, $project_id);
     if ($stmt->execute()) {
        $_SESSION['success_action'] = $decision_status . " project successfully";
        header("Location: ApproveProjects.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating project: " . $connection->error;
        header("Location: ApproveProjects.php");
        exit();
    }
} else {
    header("Location: ApproveProjects.php");
    exit();
}
?>