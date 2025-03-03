<?php
// database.php - Combined database connection and installation script

// Database configuration
$host = 'localhost';
$dbname = 'company_management';
$username = 'root';
$password = '';

// Function to check if a table exists
function tableExists($tableName, $pdo) {
    try {
        $result = $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Connect to MySQL
try {
    // First, connect without specifying a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check if database exists, create it if not
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Connect to the specific database
    $pdo->exec("USE $dbname");
    
    // Check if admin_users table exists
    if (!tableExists('admin_users', $pdo)) {
        // Database tables don't exist, create them
        
        // Create admin_users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create companies table
        $pdo->exec("CREATE TABLE IF NOT EXISTS companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create roles table
        $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            is_ceo TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create permissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create role_permissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        )");
        
        // Insert default permissions
        $defaultPermissions = [
            ['name' => 'مشاهده داشبورد', 'code' => 'view_dashboard', 'description' => 'دسترسی به مشاهده داشبورد'],
            ['name' => 'مدیریت شرکت‌ها', 'code' => 'manage_companies', 'description' => 'دسترسی به مدیریت شرکت‌ها'],
            ['name' => 'مدیریت پرسنل', 'code' => 'manage_personnel', 'description' => 'دسترسی به مدیریت پرسنل'],
            ['name' => 'مدیریت نقش‌ها', 'code' => 'manage_roles', 'description' => 'دسترسی به مدیریت نقش‌ها'],
            ['name' => 'مدیریت دسته‌بندی‌ها', 'code' => 'manage_categories', 'description' => 'دسترسی به مدیریت دسته‌بندی‌ها'],
            ['name' => 'مشاهده گزارش‌ها', 'code' => 'view_reports', 'description' => 'دسترسی به مشاهده گزارش‌ها'],
            ['name' => 'ثبت گزارش', 'code' => 'add_report', 'description' => 'دسترسی به ثبت گزارش جدید'],
            ['name' => 'مشاهده گزارش کوچ', 'code' => 'view_coach_reports', 'description' => 'دسترسی به مشاهده گزارش کوچ'],
            ['name' => 'ایجاد گزارش کوچ', 'code' => 'add_coach_report', 'description' => 'دسترسی به ایجاد گزارش کوچ جدید']
        ];
        
        $insertPermissionStmt = $pdo->prepare("INSERT IGNORE INTO permissions (name, code, description) VALUES (?, ?, ?)");
        foreach ($defaultPermissions as $permission) {
            $insertPermissionStmt->execute([$permission['name'], $permission['code'], $permission['description']]);
        }
        
        // Insert default CEO role
        $pdo->exec("INSERT INTO roles (name, is_ceo) VALUES ('مدیر عامل', 1)");
        
        // Create personnel table
        $pdo->exec("CREATE TABLE IF NOT EXISTS personnel (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            role_id INT NOT NULL,
            prefix VARCHAR(50) NULL,
            full_name VARCHAR(100) NOT NULL,
            gender ENUM('male', 'female') NOT NULL,
            email VARCHAR(100) NOT NULL,
            mobile VARCHAR(20) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            can_receive_reports TINYINT(1) DEFAULT 0,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        )");
        
        // Create categories table
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create reports table (main report header)
        $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            personnel_id INT NOT NULL,
            report_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
        )");
        
        // Create report_items table (individual items in a report)
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
        )");
        
        // Create report_item_categories table (categories for each item)
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_item_categories (
            item_id INT NOT NULL,
            category_id INT NOT NULL,
            PRIMARY KEY (item_id, category_id),
            FOREIGN KEY (item_id) REFERENCES report_items(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
        
        // Insert default admin user
        $adminUsername = 'miladahmadi04';
        $adminPassword = password_hash('963741', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $stmt->execute([$adminUsername, $adminPassword]);
        
        // --- NEW TABLES FOR SOCIAL MEDIA MANAGEMENT ---
        
        // Social networks table
        $pdo->exec("CREATE TABLE IF NOT EXISTS social_networks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default social networks
        $pdo->exec("INSERT INTO social_networks (name, icon) VALUES 
            ('Instagram', 'fab fa-instagram'),
            ('Twitter', 'fab fa-twitter'),
            ('Facebook', 'fab fa-facebook'),
            ('LinkedIn', 'fab fa-linkedin'),
            ('YouTube', 'fab fa-youtube'),
            ('TikTok', 'fab fa-tiktok'),
            ('Pinterest', 'fab fa-pinterest')
        ");
        
        // Dynamic fields for social networks
        $pdo->exec("CREATE TABLE IF NOT EXISTS social_network_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            social_network_id INT NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            field_label VARCHAR(100) NOT NULL,
            field_type ENUM('text', 'number', 'date', 'url') NOT NULL,
            is_required TINYINT(1) DEFAULT 0,
            is_kpi TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (social_network_id) REFERENCES social_networks(id) ON DELETE CASCADE
        )");
        
        // Default fields for Instagram
        $pdo->exec("INSERT INTO social_network_fields 
            (social_network_id, field_name, field_label, field_type, is_required, is_kpi, sort_order) VALUES 
            (1, 'instagram_url', 'آدرس اینستاگرام', 'text', 1, 0, 1),
            (1, 'followers', 'تعداد فالوور', 'number', 1, 1, 2),
            (1, 'engagement', 'تعداد تعامل', 'number', 1, 1, 3),
            (1, 'views', 'تعداد بازدید', 'number', 1, 1, 4),
            (1, 'leads', 'تعداد لید', 'number', 0, 1, 5),
            (1, 'customers', 'تعداد مشتری', 'number', 0, 1, 6)
        ");
        
        // Social pages (for any social network)
        $pdo->exec("CREATE TABLE IF NOT EXISTS social_pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            social_network_id INT NOT NULL,
            page_name VARCHAR(100) NOT NULL,
            page_url VARCHAR(255) NOT NULL,
            start_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (social_network_id) REFERENCES social_networks(id) ON DELETE CASCADE
        )");
        
        // Dynamic field values for pages
        $pdo->exec("CREATE TABLE IF NOT EXISTS social_page_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL,
            field_id INT NOT NULL,
            field_value TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES social_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES social_network_fields(id) ON DELETE CASCADE
        )");
        
        // KPI calculation models
        $pdo->exec("CREATE TABLE IF NOT EXISTS kpi_models (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            model_type ENUM('growth_over_time', 'percentage_of_field') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default KPI models
        $pdo->exec("INSERT INTO kpi_models (name, description, model_type) VALUES
            ('رشد زمانی', 'انتظار دارم فیلد X هر Y روز به مقدار N رشد کند', 'growth_over_time'),
            ('درصد از فیلد دیگر', 'انتظار دارم فیلد X به مقدار N درصد از فیلد دیگر باشد', 'percentage_of_field')
        ");
        
        // Actual KPIs for specific pages and fields
        $pdo->exec("CREATE TABLE IF NOT EXISTS page_kpis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL,
            field_id INT NOT NULL,
            kpi_model_id INT NOT NULL,
            related_field_id INT NULL,         -- For percentage_of_field model
            growth_value DECIMAL(10,2) NULL,   -- For growth_over_time model (amount or percentage)
            growth_period_days INT NULL,       -- For growth_over_time model
            percentage_value DECIMAL(10,2) NULL, -- For percentage_of_field model
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES social_pages(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES social_network_fields(id) ON DELETE CASCADE,
            FOREIGN KEY (kpi_model_id) REFERENCES kpi_models(id) ON DELETE CASCADE,
            FOREIGN KEY (related_field_id) REFERENCES social_network_fields(id) ON DELETE SET NULL
        )");
        
        // Monthly performance reports
        $pdo->exec("CREATE TABLE IF NOT EXISTS monthly_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL,
            report_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES social_pages(id) ON DELETE CASCADE
        )");
        
        // Report field values
        $pdo->exec("CREATE TABLE IF NOT EXISTS monthly_report_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            field_id INT NOT NULL,
            field_value TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES monthly_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES social_network_fields(id) ON DELETE CASCADE
        )");
        
        // Report performance scores
        $pdo->exec("CREATE TABLE IF NOT EXISTS report_scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            field_id INT NOT NULL,
            expected_value DECIMAL(10,2) NOT NULL,
            actual_value DECIMAL(10,2) NOT NULL,
            score DECIMAL(3,1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES monthly_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES social_network_fields(id) ON DELETE CASCADE
        )");

        // Create coach report access table
        $pdo->exec("CREATE TABLE IF NOT EXISTS coach_report_access (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            personnel_id INT NOT NULL,
            can_view TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
        )");

        // Create coach reports table
        $pdo->exec("CREATE TABLE IF NOT EXISTS coach_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coach_id INT NOT NULL,
            personnel_id INT NOT NULL,
            receiver_id INT NOT NULL,
            company_id INT NOT NULL,
            report_date DATE NOT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            team_name VARCHAR(100) NULL,
            coach_comment TEXT NULL,
            coach_score DECIMAL(3,1) NULL,
            statistics_json JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coach_id) REFERENCES personnel(id) ON DELETE CASCADE,
            FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES personnel(id) ON DELETE CASCADE,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )");

        // Create coach report social reports table (for linking to social reports)
        $pdo->exec("CREATE TABLE IF NOT EXISTS coach_report_social_reports (
            coach_report_id INT NOT NULL,
            social_report_id INT NOT NULL,
            PRIMARY KEY (coach_report_id, social_report_id),
            FOREIGN KEY (coach_report_id) REFERENCES coach_reports(id) ON DELETE CASCADE,
            FOREIGN KEY (social_report_id) REFERENCES monthly_reports(id) ON DELETE CASCADE
        )");
        
        // If this is being accessed directly (not through require/include), show success message
        if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
            echo '<div style="font-family: Tahoma, Arial; direction: rtl; text-align: center; margin-top: 100px;">';
            echo '<h2>نصب با موفقیت انجام شد!</h2>';
            echo '<p>پایگاه داده و جداول مورد نیاز با موفقیت ایجاد شدند.</p>';
            echo '<p>نام کاربری: miladahmadi04<br>رمز عبور: 963741</p>';
            echo '<a href="index.php" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;">رفتن به صفحه ورود</a>';
            echo '</div>';
            exit;
        }
    }
    
} catch (PDOException $e) {
    // If this is being accessed directly (not through require/include), show error message
    if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
        echo '<div style="font-family: Tahoma, Arial; direction: rtl; text-align: center; margin-top: 100px;">';
        echo '<h2>خطا در اتصال به پایگاه داده</h2>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
        exit;
    } else {
        die("خطا در اتصال به پایگاه داده: " . $e->getMessage());
    }
}