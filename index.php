<?php
// index.php - Entry point
require_once 'auth.php';

// Redirect to appropriate dashboard or login page
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('personnel_dashboard.php');
    }
} else {
    redirect('login.php');
}