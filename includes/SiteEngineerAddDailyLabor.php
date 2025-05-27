<?php
session_start();
include '../db_connection.php';

// Check if user is logged in and is a Site Engineer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Site Engineer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch tasks assigned to this Site Engineer (for project dropdown)
$projects_query = "SELECT DISTINCT p.project_id, p.project_name 
                  FROM projects p 
                  JOIN project_assignments pa ON p.project_id = pa.project_id 
                  WHERE pa.contractor_id = ? AND pa.role_in_project = 'Assigned Site Engineer'
                  ORDER BY p.project_name";
$stmt = $connection->prepare($projects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects_result = $stmt->get_result();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $project_id = $_POST['project_id'] ?? '';
    $laborer_name = $_POST['laborer_name'] ?? '';
    $tasks_performed = $_POST['tasks_performed'] ?? '';
    $hours_worked = $_POST['hours_worked'] ?? '';
    
    // Set date to today if not provided
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    
    // Validate required fields
    $errors = [];
    if (empty($project_id)) $errors[] = "Project is required";
    if (empty($laborer_name)) $errors[] = "Laborer name is required";
    if (empty($tasks_performed)) $errors[] = "Tasks performed is required";
    if (empty($hours_worked)) $errors[] = "Hours worked is required";
    
    // Validate laborer name - only characters and max 20 chars
    if (!empty($laborer_name)) {
        if (strlen($laborer_name) > 20) {
            $errors[] = "Laborer name must be 20 characters or less";
        }
        if (!preg_match("/^[a-zA-Z\s]+$/", $laborer_name)) {
            $errors[] = "Laborer name must contain only letters and spaces";
        }
    }
    
    // If there are errors, display them
    if (!empty($errors)) {
        $error_message = "Please correct the following errors: " . implode(", ", $errors);
    } else {
        // Insert into daily_labor table
        try {
            $site_engineer_id = $user_id; // Current logged-in site engineer
            
            $insert_query = "INSERT INTO daily_labor (project_id, user_id, date, hours_worked, tasks_performed, site_engineer_id, laborer_name) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($insert_query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $connection->error);
            }
            
            $stmt->bind_param("iisdsis", $project_id, $user_id, $date, $hours_worked, $tasks_performed, $site_engineer_id, $laborer_name);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $success_message = "Daily labor record added successfully!";
            
            // Clear form data after successful submission
            $project_id = '';
            $laborer_name = '';
            $tasks_performed = '';
            $hours_worked = '';
            
        } catch (Exception $e) {
            $error_message = "Error adding labor record: " . $e->getMessage();
            // Log the error for debugging
            error_log("Database error in SiteEngineerAddDailyLabor.php: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Daily Labor | Site Engineer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Your CSS styles here */
        .task-examples {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .id-preview-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .task-example-item {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        .task-example {
            color: #0d6efd;
            text-decoration: none;
            cursor: pointer;
        }
        
        .task-example:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Add Daily Labor</h2>
        
        <a href="SiteEngineerDashboard.php" class="btn btn-secondary mb-4">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Labor Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="laborForm" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="project_id" class="form-label required-field">Project</label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">-- Select Project --</option>
                                <?php while ($project = $projects_result->fetch_assoc()): ?>
                                    <option value="<?php echo $project['project_id']; ?>" <?php echo (isset($project_id) && $project_id == $project['project_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a project</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="date" class="form-label required-field">Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo date('Y-m-d'); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   required>
                            <div class="invalid-feedback">Please select a date (cannot be before today)</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="laborer_name" class="form-label required-field">Laborer Name</label>
                            <input type="text" class="form-control" id="laborer_name" name="laborer_name" 


                                   value="<?php echo isset($laborer_name) ? htmlspecialchars($laborer_name) : ''; ?>" 
                                   maxlength="20" pattern="[A-Za-z\s]+" required>
                            <small class="form-text text-muted">Only letters and spaces allowed. Maximum 20 characters.</small>
                            <div class="invalid-feedback">Please enter a valid laborer name (letters only, max 20 characters)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="hours_worked" class="form-label required-field">Work Hours</label>
                            <input type="number" class="form-control" id="hours_worked" name="hours_worked" 
                                   step="0.5" min="0" max="24"
                                   value="<?php echo isset($hours_worked) ? htmlspecialchars($hours_worked) : ''; ?>" required>
                            <small class="form-text text-muted">Enter hours in 0.5 increments (e.g., 8, 8.5, 9)</small>
                            <div class="invalid-feedback">Please enter valid hours worked (0-24)</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tasks_performed" class="form-label required-field">Tasks Performed</label>
                        <textarea class="form-control" id="tasks_performed" name="tasks_performed" rows="4" required><?php echo isset($tasks_performed) ? htmlspecialchars($tasks_performed) : ''; ?></textarea>
                        <div class="invalid-feedback">Please describe the tasks performed</div>
                        
                        <div class="task-examples">
                            <div class="id-preview-title">Common Tasks (click to add):</div>
                            <div class="task-example-item"><a href="#" class="task-example">Excavation and earthwork</a></div>
                            <div class="task-example-item"><a href="#" class="task-example">Concrete mixing and pouring</a></div>
                            <div class="task-example-item"><a href="#" class="task-example">Shoveling and moving soil, sand, and gravel.</a></div>
                            <div class="task-example-item"><a href="#" class="task-example">Material transportation</a></div>
                            <div class="task-example-item"><a href="#" class="task-example">Site cleaning</a></div>
                            <div class="task-example-item"><a href="#" class="task-example">breaking down of structures (walls, floors).</a></div>
                            <div class="task-example-item"><a href="#" class="task-example">Others</a></div>

                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Labor Record
                        </button>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-left">
                     <a href="SiteEngineerViewDailyLabor.php" class="btn btn-primary"> <i class="fas fa-save me-2"></i> View Available Labors </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Form validation
        (function() {
            'use strict';
            
            // Fetch all forms we want to apply validation to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Wait for DOM to be fully loaded before attaching event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Task examples click handler
            const taskExamples = document.querySelectorAll('.task-example');
            const tasksPerformed = document.getElementById('tasks_performed');
            
            if (taskExamples.length > 0 && tasksPerformed) {
                taskExamples.forEach(example => {
                    example.addEventListener('click', function(e) {
                        e.preventDefault();
                        const currentText = tasksPerformed.value;
                        const exampleText = this.textContent;
                        
                        if (currentText === '') {
                            tasksPerformed.value = exampleText;
                        } else if (!currentText.includes(exampleText)) {
                            tasksPerformed.value = currentText + (currentText.endsWith('.') || currentText.endsWith('\n') ? ' ' : '. ') + exampleText;
                        }
                        
                        tasksPerformed.focus();
                    });
                });
            }
            
            // Ensure date is set before form submission
            const laborForm = document.getElementById('laborForm');
            const dateInput = document.getElementById('date');
            
            if (laborForm && dateInput) {
                // Set today's date if not already set
                if (!dateInput.value) {
                    dateInput.value = new Date().toISOString().split('T')[0];
                }
                
                // Ensure date is set before form submission
                laborForm.addEventListener('submit', function(event) {
                    if (!dateInput.value) {
                        // Prevent the default form submission
                        event.preventDefault();
                        
                        // Set the date to today
                        dateInput.value = new Date().toISOString().split('T')[0];
                        
                        // Continue with the form submission
                        setTimeout(() => {
                            this.submit();
                        }, 100);
                    }
                });
            }
            
            // Additional validation for laborer name
            const laborerNameInput = document.getElementById('laborer_name');
            if (laborerNameInput) {
                laborerNameInput.addEventListener('input', function(e) {
                    // Remove any non-letter characters except spaces
                    this.value = this.value.replace(/[^A-Za-z\s]/g, '');
                    
                    // Enforce maximum length
                    if (this.value.length > 20) {
                        this.value = this.value.substring(0, 20);
                    }
                });
                
                // Additional validation before form submission
                laborForm.addEventListener('submit', function(event) {
                    const laborerName = laborerNameInput.value.trim();
                    
                    // Check if name contains only letters and spaces
                    if (!/^[A-Za-z\s]+$/.test(laborerName)) {
                        event.preventDefault();
                        laborerNameInput.setCustomValidity("Laborer name must contain only letters and spaces");
                        laborerNameInput.reportValidity();
                        return false;
                    }
                    
                    // Check length
                    if (laborerName.length > 20) {
                        event.preventDefault();
                        laborerNameInput.setCustomValidity("Laborer name must be 20 characters or less");
                        laborerNameInput.reportValidity();
                        return false;
                    }
                    
                    // Clear any previous validation messages if valid
                    laborerNameInput.setCustomValidity("");
                });
            }
        });
    </script>
</body>
</html>
