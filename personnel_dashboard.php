<?php
// personnel_dashboard.php - Personnel dashboard
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check personnel access
requirePersonnel();

$personnelId = $_SESSION['user_id'];

// Get personnel information
$stmt = $pdo->prepare("SELECT p.*, c.name as company_name, r.name as role_name 
                      FROM personnel p 
                      JOIN companies c ON p.company_id = c.id 
                      JOIN roles r ON p.role_id = r.id 
                      WHERE p.id = ?");
$stmt->execute([$personnelId]);
$personnel = $stmt->fetch();

// If this is a CEO, show different dashboard
if (isCEO()) {
    // Count personnel in company
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM personnel WHERE company_id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['company_id']]);
    $totalPersonnel = $stmt->fetch()['count'];
    
    // Count total reports in company
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports r 
                          JOIN personnel p ON r.personnel_id = p.id 
                          WHERE p.company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $totalReports = $stmt->fetch()['count'];
    
    // Get report count by date for company
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(r.report_date, '%Y-%m') as month, COUNT(*) as count 
                          FROM reports r
                          JOIN personnel p ON r.personnel_id = p.id
                          WHERE p.company_id = ?
                          GROUP BY month 
                          ORDER BY month DESC 
                          LIMIT 6");
    $stmt->execute([$_SESSION['company_id']]);
    $reportsByMonth = $stmt->fetchAll();
    
    // Get recent reports from company
    $stmt = $pdo->prepare("SELECT r.id, r.report_date, p.full_name,
                          (SELECT COUNT(*) FROM report_items WHERE report_id = r.id) as item_count
                          FROM reports r 
                          JOIN personnel p ON r.personnel_id = p.id 
                          WHERE p.company_id = ? 
                          ORDER BY r.report_date DESC, r.created_at DESC
                          LIMIT 10");
    $stmt->execute([$_SESSION['company_id']]);
    $recentReports = $stmt->fetchAll();
    
} else {
    // Regular personnel dashboard
    
    // Count total reports by personnel
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE personnel_id = ?");
    $stmt->execute([$personnelId]);
    $totalReports = $stmt->fetch()['count'];
    
    // Get report count by date
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(report_date, '%Y-%m') as month, COUNT(*) as count 
                          FROM reports 
                          WHERE personnel_id = ? 
                          GROUP BY month 
                          ORDER BY month DESC 
                          LIMIT 6");
    $stmt->execute([$personnelId]);
    $reportsByMonth = $stmt->fetchAll();
    
    // Get report count by category
    $stmt = $pdo->prepare("SELECT c.name, COUNT(DISTINCT ri.report_id) as count 
                          FROM report_item_categories ric 
                          JOIN categories c ON ric.category_id = c.id 
                          JOIN report_items ri ON ric.item_id = ri.id
                          JOIN reports r ON ri.report_id = r.id
                          WHERE r.personnel_id = ? 
                          GROUP BY c.id 
                          ORDER BY count DESC 
                          LIMIT 5");
    $stmt->execute([$personnelId]);
    $reportsByCategory = $stmt->fetchAll();
    
    // Get recent reports
    $stmt = $pdo->prepare("SELECT r.id, r.report_date, 
                          (SELECT COUNT(*) FROM report_items WHERE report_id = r.id) as item_count
                          FROM reports r 
                          WHERE r.personnel_id = ? 
                          ORDER BY r.report_date DESC, r.created_at DESC
                          LIMIT 5");
    $stmt->execute([$personnelId]);
    $recentReports = $stmt->fetchAll();
}

include 'header.php';
?>

<h1 class="mb-4">داشبورد <?php echo isCEO() ? 'مدیر عامل' : 'پرسنل'; ?></h1>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">اطلاعات <?php echo isCEO() ? 'مدیر عامل' : 'پرسنلی'; ?></h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>نام و نام خانوادگی:</strong> <?php echo $personnel['full_name']; ?></li>
                    <li class="list-group-item"><strong>شرکت:</strong> <?php echo $personnel['company_name']; ?></li>
                    <li class="list-group-item"><strong>نقش:</strong> <?php echo $personnel['role_name']; ?></li>
                    <li class="list-group-item"><strong>ایمیل:</strong> <?php echo $personnel['email']; ?></li>
                    <li class="list-group-item"><strong>موبایل:</strong> <?php echo $personnel['mobile']; ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">آمار گزارش‌ها</h5>
                <div class="row">
                    <?php if (isCEO()): ?>
                    <div class="col-md-6">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body text-center">
                                <h3 class="card-title">تعداد پرسنل</h3>
                                <p class="display-4"><?php echo $totalPersonnel; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="card-title">کل گزارش‌ها</h3>
                                <p class="display-4"><?php echo $totalReports; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body text-center">
                                <h3 class="card-title">کل گزارش‌ها</h3>
                                <p class="display-4"><?php echo $totalReports; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="card-title">ماه جاری</h3>
                                <?php 
                                    $currentMonth = date('Y-m');
                                    $currentMonthCount = 0;
                                    
                                    foreach ($reportsByMonth as $month) {
                                        if ($month['month'] == $currentMonth) {
                                            $currentMonthCount = $month['count'];
                                            break;
                                        }
                                    }
                                ?>
                                <p class="display-4"><?php echo $currentMonthCount; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-<?php echo isCEO() ? '12' : '6'; ?>">
        <div class="card mb-4">
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
                                    <?php if (isCEO()): ?>
                                    <th>نام پرسنل</th>
                                    <?php endif; ?>
                                    <th>تعداد آیتم</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReports as $report): ?>
                                    <tr>
                                        <td><?php echo $report['report_date']; ?></td>
                                        <?php if (isCEO()): ?>
                                        <td><?php echo $report['full_name']; ?></td>
                                        <?php endif; ?>
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
                    <?php if (!isCEO()): ?>
                    <div class="text-center">
                        <a href="reports.php" class="btn btn-primary">ثبت گزارش جدید</a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!isCEO()): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">گزارش بر اساس دسته‌بندی</h5>
            </div>
            <div class="card-body">
                <?php if (count($reportsByCategory) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>دسته‌بندی</th>
                                <th>تعداد گزارش</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportsByCategory as $category): ?>
                                <tr>
                                    <td><?php echo $category['name']; ?></td>
                                    <td><?php echo $category['count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">هیچ اطلاعاتی یافت نشد.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>