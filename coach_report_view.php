<?php
// coach_report_view.php - View Coach/Digital Marketing Manager Report Details
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check access
if (!isAdmin()) {
    if (!hasPermission('view_coach_reports')) {
        redirect('index.php');
    }
    
    // For non-admin users, check coach report access
    $stmt = $pdo->prepare("SELECT can_view FROM coach_report_access WHERE company_id = ? AND personnel_id = ?");
    $stmt->execute([$_SESSION['company_id'], $_SESSION['user_id']]);
    $hasAccess = $stmt->fetch();

    if (!$hasAccess) {
        redirect('index.php');
    }
}

// Get user's company_id if not set
if (!isset($_SESSION['company_id'])) {
    if (isAdmin()) {
        // For admin, get the first company
        $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
        $company = $stmt->fetch();
        $_SESSION['company_id'] = $company['id'];
    } else {
        // For non-admin, get company_id from personnel table
        $stmt = $pdo->prepare("SELECT company_id FROM personnel WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['company_id'] = $user['company_id'];
    }
}

$message = '';
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reportId) {
    redirect('coach_report_list.php');
}

// Get report details
$stmt = $pdo->prepare("SELECT cr.*, 
                              r.full_name as receiver_name,
                              comp.name as company_name
                       FROM coach_reports cr 
                       JOIN personnel r ON cr.receiver_id = r.id
                       JOIN companies comp ON cr.company_id = comp.id
                       WHERE cr.id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    redirect('coach_report_list.php');
}

// Get all personnel associated with this report
$stmt = $pdo->prepare("SELECT p.*, crp.coach_comment, crp.coach_score, crp.statistics_json 
                       FROM coach_report_personnel crp
                       JOIN personnel p ON crp.personnel_id = p.id
                       WHERE crp.coach_report_id = ?");
$stmt->execute([$reportId]);
$reportPersonnel = $stmt->fetchAll();

// If there are no entries in coach_report_personnel, use the main personnel
if (empty($reportPersonnel) && $report['personnel_id']) {
    $stmt = $pdo->prepare("SELECT p.*, 
                          cr.coach_comment, cr.coach_score, cr.statistics_json 
                          FROM personnel p
                          JOIN coach_reports cr ON p.id = cr.personnel_id
                          WHERE cr.id = ?");
    $stmt->execute([$reportId]);
    $reportPersonnel = $stmt->fetchAll();
}

// Get linked social reports
$stmt = $pdo->prepare("SELECT mr.*, sp.page_name 
                       FROM monthly_reports mr 
                       JOIN social_pages sp ON mr.page_id = sp.id 
                       JOIN coach_report_social_reports crsr ON mr.id = crsr.social_report_id 
                       WHERE crsr.coach_report_id = ?");
$stmt->execute([$reportId]);
$linkedReports = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>گزارش کوچ | مدیر دیجیتال مارکتینگ</h1>
    <div>
        <?php if (isAdmin() || $report['coach_id'] == $_SESSION['user_id']): ?>
            <a href="coach_report.php?edit=<?php echo $reportId; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> ویرایش گزارش
            </a>
        <?php endif; ?>
        <a href="coach_report_list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست
        </a>
    </div>
</div>

<?php echo $message; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">گزارش عملکرد</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-light border shadow-sm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>شرکت:</strong> <?php echo $report['company_name']; ?></p>
                    <p><strong>تاریخ گزارش:</strong> <?php echo $report['report_date']; ?></p>
                    <p><strong>بازه زمانی:</strong> <?php echo $report['date_from']; ?> تا <?php echo $report['date_to']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>تهیه کننده:</strong> میلاد احمدی</p>
                    <p><strong>دریافت کننده:</strong> <?php echo $report['receiver_name']; ?></p>
                    <p><strong>پرسنل مورد بررسی:</strong> 
                        <?php 
                            $personnelNames = array_map(function($p) {
                                return $p['full_name'];
                            }, $reportPersonnel);
                            echo implode(' ، ', $personnelNames);
                        ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($report['general_comments'])): ?>
            <div class="mt-3 p-3 bg-light rounded">
                <h6>توضیحات کلی:</h6>
                <p><?php echo nl2br($report['general_comments']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Display individual sections for each personnel -->
<?php foreach ($reportPersonnel as $index => $personnel): ?>
    <?php 
        // Get statistics from JSON
        $statistics = null;
        if (!empty($personnel['statistics_json'])) {
            $statistics = json_decode($personnel['statistics_json'], true);
        }
        
        // If no JSON data, try to get data manually
        if (!$statistics) {
            // Get report counts
            $stmt = $pdo->prepare("SELECT COUNT(*) as report_count FROM reports 
                                  WHERE personnel_id = ? AND report_date BETWEEN ? AND ?");
            $stmt->execute([$personnel['id'], $report['date_from'], $report['date_to']]);
            $reportCount = $stmt->fetch()['report_count'];
            
            // Get used categories
            $stmt = $pdo->prepare("SELECT DISTINCT c.name 
                                  FROM categories c 
                                  JOIN report_item_categories ric ON c.id = ric.category_id 
                                  JOIN report_items ri ON ric.item_id = ri.id 
                                  JOIN reports r ON ri.report_id = r.id 
                                  WHERE r.personnel_id = ? AND r.report_date BETWEEN ? AND ?");
            $stmt->execute([$personnel['id'], $report['date_from'], $report['date_to']]);
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get top 5 categories
            $stmt = $pdo->prepare("SELECT c.name, COUNT(*) as count 
                                  FROM categories c 
                                  JOIN report_item_categories ric ON c.id = ric.category_id 
                                  JOIN report_items ri ON ric.item_id = ri.id 
                                  JOIN reports r ON ri.report_id = r.id 
                                  WHERE r.personnel_id = ? AND r.report_date BETWEEN ? AND ? 
                                  GROUP BY c.id 
                                  ORDER BY count DESC 
                                  LIMIT 5");
            $stmt->execute([$personnel['id'], $report['date_from'], $report['date_to']]);
            $topCategories = $stmt->fetchAll();
        } else {
            $reportCount = $statistics['report_count'] ?? 0;
            $categories = $statistics['categories'] ?? [];
            $topCategories = $statistics['top_categories'] ?? [];
        }
        
        // Get social report count
        $stmt = $pdo->prepare("SELECT COUNT(*) as social_report_count 
                              FROM monthly_reports mr 
                              JOIN social_pages sp ON mr.page_id = sp.id 
                              WHERE sp.company_id = ? AND mr.report_date BETWEEN ? AND ?");
        $stmt->execute([$report['company_id'], $report['date_from'], $report['date_to']]);
        $socialReportCount = $stmt->fetch()['social_report_count'];
    ?>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">اطلاعات عملکرد <?php echo $personnel['full_name']; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card bg-primary bg-opacity-10 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">گزارش‌های ثبت شده در بازه زمانی</h6>
                                    <p class="display-4 text-center my-3"><?php echo $reportCount; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-success bg-opacity-10 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-success">گزارشات شبکه‌های اجتماعی</h6>
                                    <p class="display-4 text-center my-3"><?php echo $socialReportCount; ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($categories)): ?>
                        <div class="col-md-12">
                            <div class="card bg-info bg-opacity-10 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-info">دسته‌بندی‌های استفاده شده</h6>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <?php foreach ($categories as $category): ?>
                                            <span class="badge bg-info"><?php echo $category; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($topCategories)): ?>
                        <div class="col-md-12">
                            <div class="card bg-warning bg-opacity-10 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-warning">5 دسته‌بندی پر تکرار</h6>
                                    <div class="row mt-3">
                                        <?php foreach ($topCategories as $index => $category): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <span><?php echo ($index + 1) . '. ' . $category['name']; ?></span>
                                                    <span class="badge bg-warning text-dark"><?php echo $category['count']; ?> بار</span>
                                                </div>
                                                <div class="progress mt-1" style="height: 10px;">
                                                    <?php
                                                        $maxCount = $topCategories[0]['count'];
                                                        $percentage = ($category['count'] / $maxCount) * 100;
                                                    ?>
                                                    <div class="progress-bar bg-warning" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">نظر تهیه کننده</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($personnel['coach_comment'])): ?>
                        <div class="p-3 border rounded">
                            <i class="fas fa-quote-left text-muted fa-2x float-start me-3"></i>
                            <p class="lead"><?php echo nl2br($personnel['coach_comment']); ?></p>
                            <i class="fas fa-quote-right text-muted fa-2x float-end ms-3"></i>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">نظری ثبت نشده است.</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($personnel['coach_score'])): ?>
                        <div class="text-center mt-4">
                            <h5>امتیاز عملکرد</h5>
                            <div class="progress" style="height: 30px;">
                                <?php 
                                    $percentage = ($personnel['coach_score'] / 10) * 100;
                                    $colorClass = getScoreColorClass($personnel['coach_score']);
                                ?>
                                <div class="progress-bar bg-<?php echo $colorClass; ?>" 
                                     style="width: <?php echo $percentage; ?>%" 
                                     aria-valuenow="<?php echo $personnel['coach_score']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="10">
                                    <?php echo $personnel['coach_score']; ?> از 10
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-danger">0</span>
                                <span class="text-warning">5</span>
                                <span class="text-success">10</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if (!empty($linkedReports)): ?>
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">گزارشات شبکه اجتماعی مورد استناد</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>نام صفحه</th>
                        <th>تاریخ گزارش</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linkedReports as $index => $linkedReport): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $linkedReport['page_name']; ?></td>
                            <td><?php echo $linkedReport['report_date']; ?></td>
                            <td>
                                <a href="view_social_report.php?report=<?php echo $linkedReport['id']; ?>" 
                                   class="btn btn-sm btn-info" title="مشاهده" target="_blank">
                                    <i class="fas fa-eye"></i> مشاهده
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">اطلاعات تکمیلی</h5>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                تاریخ ایجاد گزارش
                <span class="badge bg-secondary rounded-pill"><?php echo $report['created_at']; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                شناسه گزارش
                <span class="badge bg-secondary rounded-pill"><?php echo $report['id']; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                مدت بازه گزارش
                <?php
                    $dateFrom = new DateTime($report['date_from']);
                    $dateTo = new DateTime($report['date_to']);
                    $interval = $dateFrom->diff($dateTo);
                    $days = $interval->days + 1; // Including end date
                ?>
                <span class="badge bg-info rounded-pill"><?php echo $days; ?> روز</span>
            </li>
        </ul>
        
        <div class="d-grid gap-2 mt-3">
            <a href="coach_report_list.php" class="btn btn-outline-primary">
                <i class="fas fa-list"></i> مشاهده سایر گزارش‌ها
            </a>
            <?php if (hasPermission('add_coach_report')): ?>
                <a href="coach_report.php" class="btn btn-outline-success">
                    <i class="fas fa-plus"></i> ایجاد گزارش جدید
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>