<?php
// database_updates.php - Run this file to update database structure for new features
require_once 'database.php';
require_once 'functions.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("دسترسی غیرمجاز. فقط مدیر سیستم می‌تواند به این صفحه دسترسی داشته باشد.");
}

$message = '';

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // 1. Add creator_id field to monthly_reports table
    $pdo->exec("ALTER TABLE monthly_reports 
               ADD COLUMN creator_id INT NULL DEFAULT NULL AFTER page_id,
               ADD INDEX (creator_id)");
    
    // 2. Create permissions table for advanced role-based access control
    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 3. Create role_permissions table for mapping roles to permissions
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    )");
    
    // 4. Insert default permissions
    $defaultPermissions = [
        // Dashboard permissions
        ['name' => 'مشاهده داشبورد', 'code' => 'view_dashboard', 'description' => 'دسترسی به مشاهده داشبورد'],
        
        // Company permissions
        ['name' => 'مشاهده شرکت‌ها', 'code' => 'view_companies', 'description' => 'دسترسی به مشاهده لیست شرکت‌ها'],
        ['name' => 'افزودن شرکت', 'code' => 'add_company', 'description' => 'دسترسی به افزودن شرکت جدید'],
        ['name' => 'ویرایش شرکت', 'code' => 'edit_company', 'description' => 'دسترسی به ویرایش اطلاعات شرکت'],
        ['name' => 'غیرفعال کردن شرکت', 'code' => 'toggle_company', 'description' => 'دسترسی به فعال/غیرفعال کردن شرکت'],
        
        // Personnel permissions
        ['name' => 'مشاهده پرسنل', 'code' => 'view_personnel', 'description' => 'دسترسی به مشاهده لیست پرسنل'],
        ['name' => 'افزودن پرسنل', 'code' => 'add_personnel', 'description' => 'دسترسی به افزودن پرسنل جدید'],
        ['name' => 'ویرایش پرسنل', 'code' => 'edit_personnel', 'description' => 'دسترسی به ویرایش اطلاعات پرسنل'],
        ['name' => 'غیرفعال کردن پرسنل', 'code' => 'toggle_personnel', 'description' => 'دسترسی به فعال/غیرفعال کردن پرسنل'],
        ['name' => 'بازنشانی رمز عبور', 'code' => 'reset_password', 'description' => 'دسترسی به بازنشانی رمز عبور پرسنل'],
        
        // Role permissions
        ['name' => 'مشاهده نقش‌ها', 'code' => 'view_roles', 'description' => 'دسترسی به مشاهده لیست نقش‌ها'],
        ['name' => 'افزودن نقش', 'code' => 'add_role', 'description' => 'دسترسی به افزودن نقش جدید'],
        ['name' => 'ویرایش نقش', 'code' => 'edit_role', 'description' => 'دسترسی به ویرایش نقش'],
        ['name' => 'حذف نقش', 'code' => 'delete_role', 'description' => 'دسترسی به حذف نقش'],
        ['name' => 'مدیریت دسترسی‌ها', 'code' => 'manage_permissions', 'description' => 'دسترسی به مدیریت دسترسی‌های هر نقش'],
        
        // Category permissions
        ['name' => 'مشاهده دسته‌بندی‌ها', 'code' => 'view_categories', 'description' => 'دسترسی به مشاهده لیست دسته‌بندی‌ها'],
        ['name' => 'افزودن دسته‌بندی', 'code' => 'add_category', 'description' => 'دسترسی به افزودن دسته‌بندی جدید'],
        ['name' => 'ویرایش دسته‌بندی', 'code' => 'edit_category', 'description' => 'دسترسی به ویرایش دسته‌بندی'],
        ['name' => 'حذف دسته‌بندی', 'code' => 'delete_category', 'description' => 'دسترسی به حذف دسته‌بندی'],
        
        // Daily report permissions
        ['name' => 'ثبت گزارش روزانه', 'code' => 'add_daily_report', 'description' => 'دسترسی به ثبت گزارش روزانه'],
        ['name' => 'مشاهده گزارش‌های روزانه', 'code' => 'view_daily_reports', 'description' => 'دسترسی به مشاهده گزارش‌های روزانه'],
        ['name' => 'ویرایش گزارش روزانه', 'code' => 'edit_daily_report', 'description' => 'دسترسی به ویرایش گزارش روزانه'],
        ['name' => 'حذف گزارش روزانه', 'code' => 'delete_daily_report', 'description' => 'دسترسی به حذف گزارش روزانه'],
        
        // Social network permissions
        ['name' => 'مشاهده شبکه‌های اجتماعی', 'code' => 'view_social_networks', 'description' => 'دسترسی به مشاهده لیست شبکه‌های اجتماعی'],
        ['name' => 'افزودن شبکه اجتماعی', 'code' => 'add_social_network', 'description' => 'دسترسی به افزودن شبکه اجتماعی جدید'],
        ['name' => 'ویرایش شبکه اجتماعی', 'code' => 'edit_social_network', 'description' => 'دسترسی به ویرایش شبکه اجتماعی'],
        ['name' => 'حذف شبکه اجتماعی', 'code' => 'delete_social_network', 'description' => 'دسترسی به حذف شبکه اجتماعی'],
        
        // Social network fields permissions
        ['name' => 'مشاهده فیلدهای شبکه‌ها', 'code' => 'view_social_fields', 'description' => 'دسترسی به مشاهده فیلدهای شبکه‌های اجتماعی'],
        ['name' => 'افزودن فیلد شبکه', 'code' => 'add_social_field', 'description' => 'دسترسی به افزودن فیلد شبکه'],
        ['name' => 'ویرایش فیلد شبکه', 'code' => 'edit_social_field', 'description' => 'دسترسی به ویرایش فیلد شبکه'],
        ['name' => 'حذف فیلد شبکه', 'code' => 'delete_social_field', 'description' => 'دسترسی به حذف فیلد شبکه'],
        
        // KPI model permissions
        ['name' => 'مشاهده مدل‌های KPI', 'code' => 'view_kpi_models', 'description' => 'دسترسی به مشاهده مدل‌های KPI'],
        ['name' => 'افزودن مدل KPI', 'code' => 'add_kpi_model', 'description' => 'دسترسی به افزودن مدل KPI'],
        ['name' => 'ویرایش مدل KPI', 'code' => 'edit_kpi_model', 'description' => 'دسترسی به ویرایش مدل KPI'],
        ['name' => 'حذف مدل KPI', 'code' => 'delete_kpi_model', 'description' => 'دسترسی به حذف مدل KPI'],
        
        // Social page permissions
        ['name' => 'مشاهده صفحات اجتماعی', 'code' => 'view_social_pages', 'description' => 'دسترسی به مشاهده صفحات اجتماعی'],
        ['name' => 'افزودن صفحه اجتماعی', 'code' => 'add_social_page', 'description' => 'دسترسی به افزودن صفحه اجتماعی'],
        ['name' => 'ویرایش صفحه اجتماعی', 'code' => 'edit_social_page', 'description' => 'دسترسی به ویرایش صفحه اجتماعی'],
        ['name' => 'حذف صفحه اجتماعی', 'code' => 'delete_social_page', 'description' => 'دسترسی به حذف صفحه اجتماعی'],
        
        // KPI permissions
        ['name' => 'مشاهده KPI های صفحه', 'code' => 'view_page_kpis', 'description' => 'دسترسی به مشاهده KPI های صفحه'],
        ['name' => 'افزودن KPI صفحه', 'code' => 'add_page_kpi', 'description' => 'دسترسی به افزودن KPI به صفحه'],
        ['name' => 'ویرایش KPI صفحه', 'code' => 'edit_page_kpi', 'description' => 'دسترسی به ویرایش KPI صفحه'],
        ['name' => 'حذف KPI صفحه', 'code' => 'delete_page_kpi', 'description' => 'دسترسی به حذف KPI صفحه'],
        
        // Social report permissions
        ['name' => 'مشاهده گزارش‌های اجتماعی', 'code' => 'view_social_reports', 'description' => 'دسترسی به مشاهده گزارش‌های شبکه‌های اجتماعی'],
        ['name' => 'افزودن گزارش اجتماعی', 'code' => 'add_social_report', 'description' => 'دسترسی به افزودن گزارش شبکه‌های اجتماعی'],
        ['name' => 'ویرایش گزارش اجتماعی', 'code' => 'edit_social_report', 'description' => 'دسترسی به ویرایش گزارش شبکه‌های اجتماعی'],
        ['name' => 'حذف گزارش اجتماعی', 'code' => 'delete_social_report', 'description' => 'دسترسی به حذف گزارش شبکه‌های اجتماعی'],
        
        // Performance expectations permissions
        ['name' => 'مشاهده عملکرد مورد انتظار', 'code' => 'view_expected_performance', 'description' => 'دسترسی به مشاهده عملکرد مورد انتظار'],
    ];
    
    // Insert permissions
    $insertStmt = $pdo->prepare("INSERT INTO permissions (name, code, description) VALUES (?, ?, ?)");
    
    foreach ($defaultPermissions as $permission) {
        // Check if permission already exists
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = ?");
        $stmt->execute([$permission['code']]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $insertStmt->execute([$permission['name'], $permission['code'], $permission['description']]);
        }
    }
    
    // 5. Grant default permissions to CEO role
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE is_ceo = 1 LIMIT 1");
    $stmt->execute();
    $ceoRole = $stmt->fetch();
    
    if ($ceoRole) {
        $ceoRoleId = $ceoRole['id'];
        
        // Get all permissions for CEO
        $stmt = $pdo->query("SELECT id FROM permissions");
        $permissions = $stmt->fetchAll();
        
        // Grant all permissions to CEO
        $insertStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        
        foreach ($permissions as $permission) {
            $insertStmt->execute([$ceoRoleId, $permission['id']]);
        }
    }
    
    $pdo->commit();
    $message = '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> به‌روزرسانی پایگاه داده با موفقیت انجام شد.
                </div>';
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $message = '<div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> خطا در به‌روزرسانی پایگاه داده: ' . $e->getMessage() . '
                </div>';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>به‌روزرسانی پایگاه داده</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 30px;
        }
        .container {
            max-width: 800px;
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">به‌روزرسانی پایگاه داده سیستم</h1>
        
        <?php echo $message; ?>
        
        <div class="text-center mt-4">
            <a href="admin_dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i> بازگشت به داشبورد
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>