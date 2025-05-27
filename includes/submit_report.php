<?php
session_start();
include('../db_connection.php');  // Include your database connection

// Check if the form is submitted
if (isset($_POST['submit-report'])) {
    // Get form data
    $reportType = $_POST['report-type'];
    $title = $_POST['title'];
    $reportContent = $_POST['report-content'];
    $budgetStatus = $_POST['budget-status'];
    $submittedBy = $_SESSION['user_id']; // Assuming you have the user ID in session
    $createdBy = $_SESSION['user_id']; // Assuming you have the user ID in session
    $submittedAt = date("Y-m-d H:i:s");
    $createdAt = date("Y-m-d H:i:s"); // Set current timestamp for report creation

    // Prepare the SQL query to insert the report into the database
    $query = "INSERT INTO reports (created_by, report_type, title, content, report_content, budget_status, submitted_by, submitted_at, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare the statement and bind parameters
    if ($stmt = $connection->prepare($query)) {
        $stmt->bind_param('sssssssss', $createdBy, $reportType, $title, $reportContent, $reportContent, $budgetStatus, $submittedBy, $submittedAt, $createdAt);

        // Execute the query
        if ($stmt->execute()) {
            // Report submitted successfully
            $_SESSION['report_status'] = "Report submitted successfully.";
            header("Location: ../prepare_report.php");  // Redirect to the prepare_report page
            exit();  // Ensure no further code is executed
        } else {
            $_SESSION['report_status'] = "Error submitting the report. Please try again.";
            header("Location: ../prepare_report.php");  // Redirect back in case of failure
            exit();
        }
    } else {
        $_SESSION['report_status'] = "Database error. Please try again later.";
        header("Location: ../prepare_report.php");  // Redirect back if there's an error preparing the query
        exit();
    }
}
?>
