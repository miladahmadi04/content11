<?php
// social_report.php - Manage reports for social pages (replacing monthly_report.php)
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get page ID from query string
$pageId = isset($_GET['page']) && is_numeric($_GET['page']) ? clean($_GET['page']) : null;

// If no page ID, redirect to social pages
if (!$pageId) {
    redirect('social_pages.php');
}

// Get page details
$page = getSocialPage($pageId, $pdo);
if (!$page) {
    redirect('social_pages.php');
}

// Check if user can access this page
if (!canAccessSocialPage($pageId, $pdo)) {
    redirect('social_pages.php');
}

$message = '';

// Add new report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    $reportDate = clean($_POST['report_date']);
    $fieldValues = isset($_POST['field_values']) ? $_POST['field_values'] : [];
    
    if (empty($reportDate)) {
        $message = showError('لطفا تاریخ گزارش را وارد کنید.');
    } else {
        try {
            // Check if a report already exists for this date
            $stmt = $pdo->prepare("SELECT id FROM monthly_reports WHERE page_id = ? AND report_date = ?");
            $stmt->execute([$pageId, $reportDate]);
            $existingReport = $stmt->fetch();
            
            // Begin transaction
            $pdo->beginTransaction();
            
            if ($existingReport) {
                $reportId = $existingReport['id'];
                
                // Delete old values
                $stmt = $pdo->prepare("DELETE FROM monthly_report_values WHERE report_id = ?");
                $stmt->execute([$reportId]);
                
                // Delete old scores
                $stmt = $pdo->prepare("DELETE FROM report_scores WHERE report_id = ?");
                $stmt->execute([$reportId]);
            } else {
                // Insert new report with creator_id
                $stmt = $pdo->prepare("INSERT INTO monthly_reports (page_id, report_date, creator_id) VALUES (?, ?, ?)");
                $stmt->execute([$pageId, $reportDate, $_SESSION['user_id']]);
                $reportId = $pdo->lastInsertId();
            }
            
            // Get fields for this social network
            $fields = getSocialNetworkFields($page['social_network_id'], $pdo);
            
            // Insert field values
            foreach ($fields as $field) {
                if (isset($fieldValues[$field['id']])) {
                    $fieldValue = clean($fieldValues[$field['id']]);
                    
                    // If field is required and value is empty, show error
                    if ($field['is_required'] && $fieldValue === '') {
                        throw new Exception("فیلد {$field['field_label']} الزامی است.");
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO monthly_report_values (report_id, field_id, field_value) 
                                          VALUES (?, ?, ?)");
                    $stmt->execute([$reportId, $field['id'], $fieldValue]);
                }
            }
            
            // Calculate and store performance scores
            $performance = calculateReportPerformance($reportId, $pdo);
            
            $pdo->commit();
            
            $message = showSuccess('گزارش با موفقیت ذخیره شد.');
            
            // Redirect to view the report
            redirect('view_social_report.php?report=' . $reportId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = showError('خطا در ثبت گزارش: ' . $e->getMessage());
        }
    }
}

// Edit report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_report'])) {
    $reportId = clean($_POST['report_id']);
    $reportDate = clean($_POST['report_date']);
    $fieldValues = isset($_POST['field_values']) ? $_POST['field_values'] : [];
    
    // Verify if admin or report creator
    $canEdit = false;
    if (isAdmin()) {
        $canEdit = true;
    } else {
        $stmt = $pdo->prepare("SELECT creator_id FROM monthly_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        if ($report && $report['creator_id'] == $_SESSION['user_id']) {
            $canEdit = true;
        }
    }
    
    if (!$canEdit) {
        $message = showError('شما مجاز به ویرایش این گزارش نیستید.');
    } 
    else if (empty($reportDate)) {
        $message = showError('لطفا تاریخ گزارش را وارد کنید.');
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Update report date
            $stmt = $pdo->prepare("UPDATE monthly_reports SET report_date = ? WHERE id = ?");
            $stmt->execute([$reportDate, $reportId]);
            
            // Delete old values
            $stmt = $pdo->prepare("DELETE FROM monthly_report_values WHERE report_id = ?");
            $stmt->execute([$reportId]);
            
            // Delete old scores
            $stmt = $pdo->prepare("DELETE FROM report_scores WHERE report_id = ?");
            $stmt->execute([$reportId]);
            
            // Get fields for this social network
            $fields = getSocialNetworkFields($page['social_network_id'], $pdo);
            
            // Insert updated field values
            foreach ($fields as $field) {
                if (isset($fieldValues[$field['id']])) {
                    $fieldValue = clean($fieldValues[$field['id']]);
                    
                    // If field is required and value is empty, show error
                    if ($field['is_required'] && $fieldValue === '') {
                        throw new Exception("فیلد {$field['field_label']} الزامی است.");
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO monthly_report_values (report_id, field_id, field_value) 
                                          VALUES (?, ?, ?)");
                    $stmt->execute([$reportId, $field['id'], $fieldValue]);
                }
            }
            
            // Recalculate and store performance scores
            $performance = calculateReportPerformance($reportId, $pdo);
            
            $pdo->commit();
            
            $message = showSuccess('گزارش با موفقیت به‌روزرسانی شد.');
            
            // Redirect to view the report
            redirect('view_social_report.php?report=' . $reportId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = showError('خطا در به‌روزرسانی گزارش: ' . $e->getMessage());
        }
    }
}

// Delete report
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reportId = $_GET['delete'];
    
    // Check if admin or report creator
    $canDelete = false;
    if (isAdmin()) {
        $canDelete = true;
    } else {
        $stmt = $pdo->prepare("SELECT creator_id FROM monthly_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        if ($report && $report['creator_id'] == $_SESSION['user_id']) {
            $canDelete = true;
        }
    }
    
    if (!$canDelete) {
        $message = showError('شما مجاز به حذف این گزارش نیستید.');
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete report values
            $stmt = $pdo->prepare("DELETE FROM monthly_report_values WHERE report_id = ?");
            $stmt->execute([$reportId]);
            
            // Delete report scores
            $stmt = $pdo->prepare("DELETE FROM report_scores WHERE report_id = ?");
            $stmt->execute([$reportId]);
            
            // Delete the report
            $stmt = $pdo->prepare("DELETE FROM monthly_reports WHERE id = ?");
            $stmt->execute([$reportId]);
            
            $pdo->commit();
            $message = showSuccess('گزارش با موفقیت حذف شد.');
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = showError('خطا در حذف گزارش: ' . $e->getMessage());
        }
    }
}

// Get report to edit if ID is provided
$editReport = null;
$editReportValues = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = clean($_GET['edit']);
    
    // Check if admin or report creator
    $canEdit = false;
    if (isAdmin()) {
        $canEdit = true;
    } else {
        $stmt = $pdo->prepare("SELECT creator_id FROM monthly_reports WHERE id = ?");
        $stmt->execute([$editId]);
        $report = $stmt->fetch();
        if ($report && $report['creator_id'] == $_SESSION['user_id']) {
            $canEdit = true;
        }
    }
    
    if ($canEdit) {
        // Get report details
        $stmt = $pdo->prepare("SELECT * FROM monthly_reports WHERE id = ? AND page_id = ?");
        $stmt->execute([$editId, $pageId]);
        $editReport = $stmt->fetch();
        
        if ($editReport) {
            // Get report values
            $stmt = $pdo->prepare("SELECT field_id, field_value FROM monthly_report_values WHERE report_id = ?");
            $stmt->execute([$editId]);
            $values = $stmt->fetchAll();
            
            foreach ($values as $value) {
                $editReportValues[$value['field_id']] = $value['field_value'];
            }
        }
    }
}

// Get fields for this network
$fields = getSocialNetworkFields($page['social_network_id'], $pdo);

// Get field values for this page (for initial values)
$fieldValues = getSocialPageFieldValues($pageId, $pdo);

// Get all reports for this page
$stmt = $pdo->prepare("SELECT r.*, 
                     u.username as creator_name,
                     (SELECT COUNT(*) FROM monthly_report_values WHERE report_id = r.id) as value_count,
                     (SELECT AVG(score) FROM report_scores WHERE report_id = r.id) as avg_score
                     FROM monthly_reports r 
                     LEFT JOIN admin_users u ON r.creator_id = u.id
                     LEFT JOIN personnel p ON r.creator_id = p.id
                     WHERE r.page_id = ? 
                     ORDER BY r.report_date DESC");
$stmt->execute([$pageId]);
$reports = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>گزارش‌های صفحه</h1>
        <p class="text-muted">
            <i class="<?php echo $page['network_icon']; ?>"></i>
            <?php echo $page['network_name']; ?> / <?php echo $page['page_name']; ?> / <?php echo $page['company_name']; ?>
        </p>
    </div>
    <div>
        <?php if (!$editReport): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReportModal">
            <i class="fas fa-plus"></i> افزودن گزارش جدید
        </button>
        <?php endif; ?>
        <a href="social_pages.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست صفحات
        </a>
        <a href="expected_performance.php?page=<?php echo $pageId; ?>" class="btn btn-info">
            <i class="fas fa-chart-line"></i> عملکرد مورد انتظار
        </a>
    </div>
</div>

<?php echo $message; ?>

<?php if ($editReport): ?>
<div class="card mb-4">
    <div class="card-header bg-warning">
        <h5 class="mb-0">ویرایش گزارش مورخ <?php echo $editReport['report_date']; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="report_id" value="<?php echo $editReport['id']; ?>">
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="report_date" class="form-label">تاریخ گزارش</label>
                    <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $editReport['report_date']; ?>" required>
                </div>
            </div>
            
            <h5 class="mb-3">مقادیر فیلدها</h5>
            
            <?php if (count($fields) > 0): ?>
                <div class="row">
                    <?php foreach ($fields as $field): ?>
                        <?php
                            // Get value from edit report values or default to initial value
                            $fieldValue = isset($editReportValues[$field['id']]) ? $editReportValues[$field['id']] : '';
                            
                            $inputType = 'text';
                            switch ($field['field_type']) {
                                case 'number':
                                    $inputType = 'number';
                                    break;
                                case 'date':
                                    $inputType = 'date';
                                    break;
                                case 'url':
                                    $inputType = 'url';
                                    break;
                            }
                        ?>
                        <div class="col-md-6 mb-3">
                            <label for="field_<?php echo $field['id']; ?>" class="form-label">
                                <?php echo $field['field_label']; ?>
                                <?php if ($field['is_required']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                                <?php if ($field['is_kpi']): ?>
                                    <span class="badge bg-primary">KPI</span>
                                <?php endif; ?>
                            </label>
                            <input type="<?php echo $inputType; ?>" 
                                   class="form-control" 
                                   id="field_<?php echo $field['id']; ?>" 
                                   name="field_values[<?php echo $field['id']; ?>]" 
                                   value="<?php echo $fieldValue; ?>"
                                   <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="edit_report" class="btn btn-warning">
                        <i class="fas fa-save"></i> ذخیره تغییرات
                    </button>
                    <a href="?page=<?php echo $pageId; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> انصراف
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    هیچ فیلدی برای این شبکه اجتماعی تعریف نشده است.
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (empty($fields)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        هیچ فیلدی برای این شبکه اجتماعی تعریف نشده است.
        <?php if (isAdmin()): ?>
            <a href="social_network_fields.php?network=<?php echo $page['social_network_id']; ?>" class="alert-link">
                مدیریت فیلدها
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <?php if (count($reports) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>تاریخ گزارش</th>
                                <th>ثبت کننده</th>
                                <th>تعداد فیلدها</th>
                                <th>امتیاز عملکرد</th>
                                <th>وضعیت عملکرد</th>
                                <th>تاریخ ایجاد</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?php echo $report['report_date']; ?></td>
                                    <td>
                                        <?php echo $report['creator_name'] ?: 'نامشخص'; ?>
                                    </td>
                                    <td><?php echo $report['value_count']; ?></td>
                                    <td>
                                        <?php if ($report['avg_score']): ?>
                                            <?php echo number_format($report['avg_score'], 1); ?> از 7
                                        <?php else: ?>
                                            <span class="text-muted">محاسبه نشده</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            if ($report['avg_score']) {
                                                if ($report['avg_score'] >= 6) {
                                                    echo '<span class="badge bg-success">عالی</span>';
                                                } elseif ($report['avg_score'] >= 5) {
                                                    echo '<span class="badge bg-primary">خوب</span>';
                                                } elseif ($report['avg_score'] >= 3.5) {
                                                    echo '<span class="badge bg-warning">متوسط</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">ضعیف</span>';
                                                }
                                            } else {
                                                echo '<span class="badge bg-secondary">نامشخص</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo $report['created_at']; ?></td>
                                    <td>
                                        <a href="view_social_report.php?report=<?php echo $report['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> مشاهده
                                        </a>
                                        
                                        <?php if (isAdmin() || (isset($report['creator_id']) && $report['creator_id'] == $_SESSION['user_id'])): ?>
                                            <a href="?page=<?php echo $pageId; ?>&edit=<?php echo $report['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> ویرایش
                                            </a>
                                            <a href="?page=<?php echo $pageId; ?>&delete=<?php echo $report['id']; ?>" 
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
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    هیچ گزارشی برای این صفحه ثبت نشده است.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Add Report Modal -->
<div class="modal fade" id="addReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن گزارش جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="report_date" class="form-label">تاریخ گزارش</label>
                            <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">مقادیر فیلدها</h5>
                    
                    <?php if (count($fields) > 0): ?>
                        <div class="row">
                            <?php foreach ($fields as $field): ?>
                                <?php
                                    // Get initial value from page fields
                                    $initialValue = '';
                                    foreach ($fieldValues as $value) {
                                        if ($value['field_id'] == $field['id']) {
                                            $initialValue = $value['field_value'];
                                            break;
                                        }
                                    }
                                    
                                    $inputType = 'text';
                                    switch ($field['field_type']) {
                                        case 'number':
                                            $inputType = 'number';
                                            break;
                                        case 'date':
                                            $inputType = 'date';
                                            break;
                                        case 'url':
                                            $inputType = 'url';
                                            break;
                                    }
                                ?>
                                <div class="col-md-6 mb-3">
                                    <label for="field_<?php echo $field['id']; ?>" class="form-label">
                                        <?php echo $field['field_label']; ?>
                                        <?php if ($field['is_required']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                        <?php if ($field['is_kpi']): ?>
                                            <span class="badge bg-primary">KPI</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="<?php echo $inputType; ?>" 
                                           class="form-control" 
                                           id="field_<?php echo $field['id']; ?>" 
                                           name="field_values[<?php echo $field['id']; ?>]" 
                                           value="<?php echo $initialValue; ?>"
                                           <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            هیچ فیلدی برای این شبکه اجتماعی تعریف نشده است.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_report" class="btn btn-primary" <?php echo count($fields) == 0 ? 'disabled' : ''; ?>>
                        ذخیره گزارش
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>