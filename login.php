<?php
// login.php - Login page
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('personnel_dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = clean($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'لطفا نام کاربری و رمز عبور را وارد کنید.';
    } else {
        // Try admin login first
        if (adminLogin($username, $password, $pdo)) {
            redirect('admin_dashboard.php');
        } 
        // Then try personnel login
        else if (personnelLogin($username, $password, $pdo)) {
            redirect('personnel_dashboard.php');
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است.';
        }
    }
}

// Debug information
if (isset($_POST['username'])) {
    error_log("Login attempt for username: " . $_POST['username']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Tahoma, Arial, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">ورود به سیستم</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">نام کاربری</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">رمز عبور</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">ورود</button>
            </form>
            
            <!-- Debug Info -->
            <?php if (isset($_SESSION)): ?>
            <div class="mt-3 small text-muted">
                Session Info:
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>