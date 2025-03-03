<?php
// get_receivers.php - Get personnel who can receive reports for a specific company
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get company_id from query string
$companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;

if (!$companyId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid company ID']);
    exit;
}

try {
    // Get personnel who can receive reports for the selected company (preferring those with CEO role)
    $stmt = $pdo->prepare("SELECT p.id, p.full_name, 
                          (CASE WHEN r.is_ceo = 1 THEN 1 ELSE 0 END) as is_ceo
                          FROM personnel p 
                          JOIN roles r ON p.role_id = r.id
                          WHERE p.company_id = ? 
                          AND p.is_active = 1
                          ORDER BY is_ceo DESC, p.full_name");
    $stmt->execute([$companyId]);
    $receivers = $stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($receivers);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}