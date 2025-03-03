<?php
// auth.php - Authentication functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';
require_once 'functions.php';

// Admin login
function adminLogin($username, $password, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';
            return true;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
    }
    return false;
}

// Personnel login
function personnelLogin($username, $password, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.is_active as company_active, r.is_ceo 
                              FROM personnel p 
                              JOIN companies c ON p.company_id = c.id 
                              JOIN roles r ON p.role_id = r.id 
                              WHERE p.username = ?");
        $stmt->execute([$username]);
        $personnel = $stmt->fetch();
        
        if ($personnel && password_verify($password, $personnel['password'])) {
            // Check if both personnel and company are active
            if ($personnel['is_active'] == 1 && $personnel['company_active'] == 1) {
                $_SESSION['user_id'] = $personnel['id'];
                $_SESSION['username'] = $personnel['username'];
                $_SESSION['user_type'] = 'personnel';
                $_SESSION['company_id'] = $personnel['company_id'];
                $_SESSION['role_id'] = $personnel['role_id'];
                $_SESSION['is_ceo'] = $personnel['is_ceo'];
                return true;
            }
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
    }
    return false;
}

// Logout
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

// Check admin access
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        redirect('login.php');
    }
}

// Check personnel access
function requirePersonnel() {
    if (!isLoggedIn() || $_SESSION['user_type'] !== 'personnel') {
        redirect('login.php');
    }
}

// Check CEO access
function isCEO() {
    return isset($_SESSION['is_ceo']) && $_SESSION['is_ceo'] == 1;
}

// Function to check if user can access specific reports
function canAccessReports($reportPersonnelId, $pdo) {
    // Admins can access any report
    if (isAdmin()) {
        return true;
    }
    
    // Personnel can access their own reports
    if ($_SESSION['user_id'] == $reportPersonnelId) {
        return true;
    }
    
    // CEOs can access reports from personnel in their company
    if (isCEO()) {
        $stmt = $pdo->prepare("SELECT company_id FROM personnel WHERE id = ?");
        $stmt->execute([$reportPersonnelId]);
        $personnel = $stmt->fetch();
        
        if ($personnel && $personnel['company_id'] == $_SESSION['company_id']) {
            return true;
        }
    }
    
    return false;
}