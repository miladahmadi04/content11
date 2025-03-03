<?php
// coach_report_list.php - List of coach reports
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check access
if (!isAdmin()) {
    // For non-admin users, check coach report access
    $stmt = $pdo->prepare("SELECT can_view FROM coach_report_access WHERE company_id = ? AND personnel_id = ?");
    $stmt->execute([$_SESSION['company_id'], $_SESSION['user_id']]);
    $hasAccess = $stmt->fetch();

    if (!$hasAccess) {
        redirect('index.php');
    }
}

$message = '';

// Delete report
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reportId = $_GET['delete'];
    
    // Check if user has permission to delete
    $canDelete = false;
    if (isAdmin()) {
        $canDelete = true;
    } else {
        // Check if report was created by this user
        $stmt = $pdo->prepare("SELECT coach_id FROM coach_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        if ($report && $report['coach_id'] == $_SESSION['user_id']) {
            $canDelete = true;
        }
    }
    
    if ($canDelete) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete social report connections
            $stmt = $pdo->prepare("DELETE FROM coach_report_social_reports WHERE coach_report_id = ?");
            $stmt->execute([$reportId]);
            
            // Delete the report
            $stmt = $pdo->prepare("DELETE FROM coach_reports WHERE id = ?");
            $stmt->execute([$reportId]);
            
            $pdo->commit();
            $message = showSuccess('گزارش با موفقیت حذف شد.');
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = showError('خطا در حذف گزارش: ' . $e->getMessage());
        }
    } else {
        $message = showError('شما مجاز به حذف این گزارش نیستید.');
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total number of reports
$stmt = $pdo->query("SELECT COUNT(*) FROM coach_reports");
$totalReports = $stmt->fetchColumn();
$totalPages = ceil($totalReports / $perPage);

// Get reports for current page
$stmt = $pdo->prepare("SELECT cr.*, 
                       p1.full_name as personnel_name,
                       p3.full_name as receiver_name,
                       c.name as company_name
                       FROM coach_reports cr
                       JOIN personnel p1 ON cr.personnel_id = p1.id
                       JOIN personnel p3 ON cr.receiver_id = p3.id
                       JOIN companies c ON cr.company_id = c.id
                       ORDER BY cr.report_date DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>لیست گزارش‌های کوچ</h1>
    <?php if (hasPermission('add_coach_report')): ?>
        <a href="coach_report.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> گزارش جدید
        </a>
    <?php endif; ?>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($reports) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>تاریخ گزارش</th>
                            <th>نام پرسنل</th>
                            <th>دریافت‌کننده</th>
                            <th>نام کوچ</th>
                            <th>شرکت</th>
                            <th>بازه زمانی</th>
                            <th>امتیاز</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo $report['report_date']; ?></td>
                                <td><?php echo $report['personnel_name']; ?></td>
                                <td><?php echo $report['receiver_name']; ?></td>
                                <td>میلاد احمدی</td>
                                <td><?php echo $report['company_name']; ?></td>
                                <td>
                                    <?php echo $report['date_from']; ?> تا <?php echo $report['date_to']; ?>
                                </td>
                                <td>
                                    <?php if ($report['coach_score']): ?>
                                        <span class="badge bg-<?php echo getScoreColorClass($report['coach_score']); ?>">
                                            <?php echo $report['coach_score']; ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="coach_report_view.php?id=<?php echo $report['id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> مشاهده
                                    </a>
                                    
                                    <?php if (isAdmin() || $report['coach_id'] == $_SESSION['user_id']): ?>
                                        <a href="coach_report.php?edit=<?php echo $report['id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> ویرایش
                                        </a>
                                        
                                        <a href="?delete=<?php echo $report['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این گزارش اطمینان دارید؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-center">هیچ گزارشی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>