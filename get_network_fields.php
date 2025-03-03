<?php
// get_network_fields.php - AJAX handler to fetch fields for a social network
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if user is authorized
if (!isLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'دسترسی غیرمجاز']);
    exit;
}

// Get network ID from request
$networkId = isset($_GET['network_id']) && is_numeric($_GET['network_id']) ? clean($_GET['network_id']) : null;

if (!$networkId) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'شناسه شبکه اجتماعی نامعتبر است']);
    exit;
}

try {
    // Get fields for this network
    $fields = getSocialNetworkFields($networkId, $pdo);
    
    // Set headers for JSON response
    header('Content-Type: application/json');
    echo json_encode($fields);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'خطای پایگاه داده: ' . $e->getMessage()]);
}