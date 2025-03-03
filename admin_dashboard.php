<?php
// admin_dashboard.php - Admin dashboard
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin access
if (!isAdmin()) {
    redirect('login.php');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug information
echo "<!-- Debug Info: -->";
echo "<!-- User Type: " . ($_SESSION['user_type'] ?? 'Not Set') . " -->";
echo "<!-- User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . " -->";

// Count active companies
$stmt = $pdo->query("SELECT COUNT(*) as count FROM companies WHERE is_active = 1");
$activeCompanies = $stmt->fetch()['count'];

// Count inactive companies
$stmt = $pdo->query("SELECT COUNT(*) as count FROM companies WHERE is_active = 0");
$inactiveCompanies = $stmt->fetch()['count'];

// Count active personnel
$stmt = $pdo->query("SELECT COUNT(*) as count FROM personnel p 
                    JOIN companies c ON p.company_id = c.id 
                    WHERE p.is_active = 1 AND c.is_active = 1");
$activePersonnel = $stmt->fetch()['count'];

// Count inactive personnel
$stmt = $pdo->query("SELECT COUNT(*) as count FROM personnel p 
                    LEFT JOIN companies c ON p.company_id = c.id 
                    WHERE p.is_active = 0 OR c.is_active = 0");
$inactivePersonnel = $stmt->fetch()['count'];

// Count total reports
$stmt = $pdo->query("SELECT COUNT(*) as count FROM reports");
$totalReports = $stmt->fetch()['count'];

// Count total report items
$stmt = $pdo->query("SELECT COUNT(*) as count FROM report_items");
$totalItems = $stmt->fetch()['count'];

// Count reports by month
$stmt = $pdo->query("SELECT DATE_FORMAT(report_date, '%Y-%m') as month, COUNT(*) as count 
                    FROM reports 
                    GROUP BY month 
                    ORDER BY month DESC 
                    LIMIT 6");
$reportsByMonth = $stmt->fetchAll();

// Get recent reports with more info
$stmt = $pdo->query("SELECT r.id, r.report_date, p.full_name, c.name as company_name,
                     (SELECT COUNT(*) FROM report_items WHERE report_id = r.id) as item_count
                     FROM reports r
                     JOIN personnel p ON r.personnel_id = p.id
                     JOIN companies c ON p.company_id = c.id
                     ORDER BY r.created_at DESC
                     LIMIT 10");
$recentReports = $stmt->fetchAll();

include 'header.php';
?>

<h1 class="mb-4">داشبورد مدیریت</h1>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">شرکت‌های فعال</h5>
                <p class="card-text display-4"><?php echo $activeCompanies; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">شرکت‌های غیرفعال</h5>
                <p class="card-text display-4"><?php echo $inactiveCompanies; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">پرسنل فعال</h5>
                <p class="card-text display-4"><?php echo $activePersonnel; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">پرسنل غیرفعال</h5>
                <p class="card-text display-4"><?php echo $inactivePersonnel; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">کل گزارش‌ها</h5>
                <p class="card-text display-4"><?php echo $totalReports; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h5 class="card-title">کل آیتم‌های گزارش</h5>
                <p class="card-text display-4"><?php echo $totalItems; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">گزارش‌های اخیر</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentReports) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>تاریخ</th>
                                    <th>نام پرسنل</th>
                                    <th>شرکت</th>
                                    <th>تعداد آیتم</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReports as $report): ?>
                                    <tr>
                                        <td><?php echo $report['report_date']; ?></td>
                                        <td><?php echo $report['full_name']; ?></td>
                                        <td><?php echo $report['company_name']; ?></td>
                                        <td><?php echo $report['item_count']; ?></td>
                                        <td>
                                            <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-info">مشاهده</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="view_reports.php" class="btn btn-primary">مشاهده همه گزارش‌ها</a>
                    </div>
                <?php else: ?>
                    <p class="text-center">هیچ گزارشی یافت نشد.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>