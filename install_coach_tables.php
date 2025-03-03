<?php
// install_coach_tables.php - Install coach-related tables
require_once 'database.php';

try {
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
        report_date DATE NOT NULL,
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        team_name VARCHAR(100) NULL,
        coach_comment TEXT NULL,
        coach_score DECIMAL(3,1) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (coach_id) REFERENCES personnel(id) ON DELETE CASCADE,
        FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
    )");

    // Create coach report social reports table (for linking to social reports)
    $pdo->exec("CREATE TABLE IF NOT EXISTS coach_report_social_reports (
        coach_report_id INT NOT NULL,
        social_report_id INT NOT NULL,
        PRIMARY KEY (coach_report_id, social_report_id),
        FOREIGN KEY (coach_report_id) REFERENCES coach_reports(id) ON DELETE CASCADE,
        FOREIGN KEY (social_report_id) REFERENCES monthly_reports(id) ON DELETE CASCADE
    )");

    // Grant access to admin user for all companies
    $stmt = $pdo->query("SELECT id FROM companies");
    $companies = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id FROM personnel WHERE username = 'miladahmadi04'");
    $admin = $stmt->fetch();

    if ($admin) {
        $insertAccess = $pdo->prepare("INSERT INTO coach_report_access (company_id, personnel_id, can_view) VALUES (?, ?, 1)");
        foreach ($companies as $company) {
            $insertAccess->execute([$company['id'], $admin['id']]);
        }
    }

    // ابتدا یک پرسنل از شرکت مورد نظر را به عنوان دریافت کننده گزارش تنظیم می‌کنیم
    $stmt = $pdo->prepare("UPDATE personnel SET can_receive_reports = 1 WHERE company_id = (SELECT id FROM companies WHERE is_active = 1 LIMIT 1) LIMIT 1");
    $stmt->execute();

    // برای اطمینان از اینکه تنظیم درست انجام شده، می‌توانید این کوئری را اجرا کنید
    $stmt = $pdo->prepare("SELECT p.id, p.full_name, c.name as company_name FROM personnel p JOIN companies c ON p.company_id = c.id WHERE p.can_receive_reports = 1");
    $stmt->execute();

    echo '<div style="font-family: Tahoma, Arial; direction: rtl; text-align: center; margin-top: 100px;">';
    echo '<h2>نصب با موفقیت انجام شد!</h2>';
    echo '<p>جداول مربوط به گزارش کوچ با موفقیت ایجاد شدند.</p>';
    echo '<a href="coach_report_list.php" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;">رفتن به لیست گزارش‌های کوچ</a>';
    echo '</div>';

} catch (PDOException $e) {
    echo '<div style="font-family: Tahoma, Arial; direction: rtl; text-align: center; margin-top: 100px;">';
    echo '<h2>خطا در نصب جداول</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '</div>';
} 