<?php
// Database connection settings
$server = "localhost";     // Host name
$username = "root";        // MySQL username (typically "root" for XAMPP)
$password = "";            // MySQL password (leave empty for XAMPP default)
$dbname = "cms";           // The database name (CMS in your case)

// Create connection
$connection = new mysqli($server, $username, $password, $dbname);

// Check the connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error); // If there is an error, terminate
}
?>
