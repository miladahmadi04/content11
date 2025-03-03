<?php
// get_personnel.php - AJAX handler to fetch personnel for a company
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if user is authorized
if (!isLoggedIn() || (!isAdmin() && !isCEO())) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get company ID from request
$companyId = isset($_GET['company_id']) && is_numeric($_GET['company_id']) ? clean($_GET['company_id']) : null;

if (!$companyId) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid company ID']);
    exit;
}

try {
    // If admin, can access any company
    if (isAdmin()) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM personnel WHERE company_id = ? ORDER BY full_name");
        $stmt->execute([$companyId]);
    } 
    // If CEO, can only access own company
    else if (isCEO() && $_SESSION['company_id'] == $companyId) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM personnel WHERE company_id = ? ORDER BY full_name");
        $stmt->execute([$companyId]);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $personnel = $stmt->fetchAll();
    
    // Set headers for JSON response
    header('Content-Type: application/json');
    echo json_encode($personnel);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}