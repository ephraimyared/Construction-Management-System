<?php
function getDashboardUrl($role) {
    $role = strtolower(trim($role));
    $mapping = [
        'admin' => 'includes/AdminDashboard.php',
        'project manager' => 'includes/ProjectManagerDashboard.php',
        'contractor' => 'includes/ContractorDashboard.php',
        'consultant' => 'includes/ConsultantDashboard.php',
        'site engineer' => 'includes/SiteEngineerDashboard.php',
        'employee' => 'includes/EmployeeDashboard.php'
    ];
    return $mapping[$role] ?? 'role_selection.php?error=invalid_role';
}
?>