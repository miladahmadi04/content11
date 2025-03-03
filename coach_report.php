<?php
// coach_report.php - Coach/Digital Marketing Manager Report
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check access
if (!isAdmin()) {
    if (!hasPermission('add_coach_report')) {
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

$message = '';
$selectedPersonnel = [];
$dateFrom = isset($_POST['date_from']) ? clean($_POST['date_from']) : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_POST['date_to']) ? clean($_POST['date_to']) : date('Y-m-d');
$selectedCompany = isset($_POST['company_id']) ? clean($_POST['company_id']) : '';
$selectedReceiver = isset($_POST['receiver_id']) ? clean($_POST['receiver_id']) : '';
$generalComments = isset($_POST['general_comments']) ? clean($_POST['general_comments']) : '';

// Check if this is an edit operation
$isEdit = false;
$editReport = null;
$editId = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : null;

if ($editId) {
    // Get the report to edit
    $stmt = $pdo->prepare("SELECT * FROM coach_reports WHERE id = ?");
    $stmt->execute([$editId]);
    $editReport = $stmt->fetch();
    
    if ($editReport) {
        $isEdit = true;
        
        // Check if user has permission to edit
        $canEdit = false;
        if (isAdmin()) {
            $canEdit = true;
        } else if ($editReport['coach_id'] == $_SESSION['user_id']) {
            $canEdit = true;
        }
        
        if (!$canEdit) {
            redirect('coach_report_list.php');
        }
        
        // Set form values from the report
        $dateFrom = $editReport['date_from'];
        $dateTo = $editReport['date_to'];
        $generalComments = $editReport['general_comments'];
        
        // Get personnel related to this report
        $stmt = $pdo->prepare("SELECT personnel_id FROM coach_report_personnel WHERE coach_report_id = ?");
        $stmt->execute([$editId]);
        $selectedPersonnel = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If there are no entries in coach_report_personnel, use the main personnel_id
        if (empty($selectedPersonnel) && $editReport['personnel_id']) {
            $selectedPersonnel = [$editReport['personnel_id']];
        }
        
        // Get social reports linked to this coach report
        $stmt = $pdo->prepare("SELECT social_report_id FROM coach_report_social_reports WHERE coach_report_id = ?");
        $stmt->execute([$editId]);
        $linkedReports = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get personnel-specific data
        $personnelData = [];
        foreach ($selectedPersonnel as $personnelId) {
            $stmt = $pdo->prepare("SELECT coach_comment, coach_score FROM coach_report_personnel WHERE coach_report_id = ? AND personnel_id = ?");
            $stmt->execute([$editId, $personnelId]);
            $data = $stmt->fetch();
            
            if ($data) {
                $personnelData[$personnelId] = [
                    'coach_comment' => $data['coach_comment'],
                    'coach_score' => $data['coach_score']
                ];
            } else if ($personnelId == $editReport['personnel_id']) {
                // If this is the main personnel and no entry in coach_report_personnel
                $personnelData[$personnelId] = [
                    'coach_comment' => $editReport['coach_comment'],
                    'coach_score' => $editReport['coach_score']
                ];
            }
        }
    }
}

// Get user's company_id
if (isAdmin()) {
    // For admin, get the first company if we're not editing
    if (!$isEdit) {
        $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
        $company = $stmt->fetch();
        $_SESSION['company_id'] = $company['id'];
        
        if ($selectedCompany) {
            $_SESSION['company_id'] = $selectedCompany;
        }
    } else {
        // For edit mode, use the company from the report
        $_SESSION['company_id'] = $editReport['company_id'];
        $selectedCompany = $editReport['company_id'];
        $selectedReceiver = $editReport['receiver_id'];
    }
} else {
    // For non-admin, get company_id from personnel table
    $stmt = $pdo->prepare("SELECT company_id FROM personnel WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['company_id'] = $user['company_id'];
    $selectedCompany = $user['company_id'];
}

// Get companies for selection
$stmt = $pdo->query("SELECT id, name FROM companies WHERE is_active = 1 ORDER BY name");
$companies = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['show_fields'])) {
        $selectedPersonnel = isset($_POST['personnel']) ? $_POST['personnel'] : [];
        $selectedCompany = isset($_POST['company_id']) ? clean($_POST['company_id']) : '';
        $selectedReceiver = isset($_POST['receiver_id']) ? clean($_POST['receiver_id']) : '';
        $generalComments = isset($_POST['general_comments']) ? clean($_POST['general_comments']) : '';
        
        if (empty($selectedCompany)) {
            $message = showError('لطفاً شرکت را انتخاب کنید.');
        } elseif (empty($selectedReceiver)) {
            $message = showError('لطفاً دریافت کننده گزارش را انتخاب کنید.');
        } elseif (empty($selectedPersonnel)) {
            $message = showError('لطفاً حداقل یک نفر را انتخاب کنید.');
        }
    } elseif (isset($_POST['generate_report']) || isset($_POST['update_report'])) {
        $selectedPersonnel = isset($_POST['personnel']) ? $_POST['personnel'] : [];
        $selectedCompany = isset($_POST['company_id']) ? clean($_POST['company_id']) : '';
        $selectedReceiver = isset($_POST['receiver_id']) ? clean($_POST['receiver_id']) : '';
        $generalComments = isset($_POST['general_comments']) ? clean($_POST['general_comments']) : '';
        
        if (empty($selectedCompany)) {
            $message = showError('لطفاً شرکت را انتخاب کنید.');
        } elseif (empty($selectedReceiver)) {
            $message = showError('لطفاً دریافت کننده گزارش را انتخاب کنید.');
        } elseif (empty($selectedPersonnel)) {
            $message = showError('لطفاً حداقل یک نفر را انتخاب کنید.');
        } else {
            try {
                $pdo->beginTransaction();
                
                // If editing, update existing report
                if (isset($_POST['update_report']) && $isEdit) {
                    // Delete social report connections
                    $stmt = $pdo->prepare("DELETE FROM coach_report_social_reports WHERE coach_report_id = ?");
                    $stmt->execute([$editId]);
                    
                    // Delete personnel connections
                    $stmt = $pdo->prepare("DELETE FROM coach_report_personnel WHERE coach_report_id = ?");
                    $stmt->execute([$editId]);
                    
                    // Update main report
                    $stmt = $pdo->prepare("UPDATE coach_reports SET 
                                         coach_id = ?, personnel_id = ?, receiver_id = ?, company_id = ?,
                                         date_from = ?, date_to = ?, general_comments = ?
                                         WHERE id = ?");
                    $stmt->execute([
                        $_SESSION['user_id'], 
                        $selectedPersonnel[0], // Just use first personnel as primary
                        $selectedReceiver,
                        $selectedCompany,
                        $dateFrom, 
                        $dateTo, 
                        $generalComments,
                        $editId
                    ]);
                    
                    // Add personnel-specific data
                    foreach ($selectedPersonnel as $personnelId) {
                        // Get report statistics for this personnel
                        $stmt = $pdo->prepare("SELECT COUNT(*) as report_count FROM reports 
                                             WHERE personnel_id = ? AND report_date BETWEEN ? AND ?");
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
                        $reportCount = $stmt->fetch()['report_count'];
                        
                        // Get used categories
                        $stmt = $pdo->prepare("SELECT DISTINCT c.name 
                                             FROM categories c 
                                             JOIN report_item_categories ric ON c.id = ric.category_id 
                                             JOIN report_items ri ON ric.item_id = ri.id 
                                             JOIN reports r ON ri.report_id = r.id 
                                             WHERE r.personnel_id = ? AND r.report_date BETWEEN ? AND ?");
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
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
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
                        $topCategories = $stmt->fetchAll();
                        
                        // Prepare statistics JSON
                        $statistics = [
                            'report_count' => $reportCount,
                            'categories' => $categories,
                            'top_categories' => array_map(function($cat) {
                                return ['name' => $cat['name'], 'count' => $cat['count']];
                            }, $topCategories)
                        ];
                        
                        // Insert into coach_report_personnel
                        $stmt = $pdo->prepare("INSERT INTO coach_report_personnel 
                                             (coach_report_id, personnel_id, coach_comment, coach_score, statistics_json) 
                                             VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $editId,
                            $personnelId,
                            isset($_POST['coach_comment'][$personnelId]) ? clean($_POST['coach_comment'][$personnelId]) : null,
                            isset($_POST['coach_score'][$personnelId]) ? clean($_POST['coach_score'][$personnelId]) : null,
                            json_encode($statistics)
                        ]);
                    }
                    
                    // Link selected social reports for each personnel
                    foreach ($selectedPersonnel as $personnelId) {
                        if (isset($_POST['social_reports'][$personnelId]) && is_array($_POST['social_reports'][$personnelId])) {
                            $insertStmt = $pdo->prepare("INSERT INTO coach_report_social_reports 
                                                       (coach_report_id, social_report_id) VALUES (?, ?)");
                            foreach ($_POST['social_reports'][$personnelId] as $socialReportId) {
                                $insertStmt->execute([$editId, $socialReportId]);
                            }
                        }
                    }
                    
                    $pdo->commit();
                    redirect('coach_report_view.php?id=' . $editId);
                } else {
                    // Create new report
                    // Get social report count for company
                    $stmt = $pdo->prepare("SELECT COUNT(*) as social_report_count 
                                         FROM monthly_reports mr 
                                         JOIN social_pages sp ON mr.page_id = sp.id 
                                         WHERE sp.company_id = ? AND mr.report_date BETWEEN ? AND ?");
                    $stmt->execute([$selectedCompany, $dateFrom, $dateTo]);
                    $socialReportCount = $stmt->fetch()['social_report_count'];
                    
                    // Create main coach report
                    $stmt = $pdo->prepare("INSERT INTO coach_reports 
                                         (coach_id, personnel_id, receiver_id, company_id, report_date, date_from, date_to, 
                                         general_comments) 
                                         VALUES (?, ?, ?, ?, CURRENT_DATE, ?, ?, ?)");
                    $stmt->execute([
                        $_SESSION['user_id'], 
                        $selectedPersonnel[0], // Use first personnel as primary
                        $selectedReceiver,
                        $selectedCompany,
                        $dateFrom, 
                        $dateTo, 
                        $generalComments
                    ]);
                    $coachReportId = $pdo->lastInsertId();
                    
                    // Add entry for each personnel
                    foreach ($selectedPersonnel as $personnelId) {
                        // Get report statistics for this personnel
                        $stmt = $pdo->prepare("SELECT COUNT(*) as report_count FROM reports 
                                             WHERE personnel_id = ? AND report_date BETWEEN ? AND ?");
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
                        $reportCount = $stmt->fetch()['report_count'];
                        
                        // Get used categories
                        $stmt = $pdo->prepare("SELECT DISTINCT c.name 
                                             FROM categories c 
                                             JOIN report_item_categories ric ON c.id = ric.category_id 
                                             JOIN report_items ri ON ric.item_id = ri.id 
                                             JOIN reports r ON ri.report_id = r.id 
                                             WHERE r.personnel_id = ? AND r.report_date BETWEEN ? AND ?");
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
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
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
                        $topCategories = $stmt->fetchAll();
                        
                        // Prepare statistics JSON
                        $statistics = [
                            'report_count' => $reportCount,
                            'categories' => $categories,
                            'top_categories' => array_map(function($cat) {
                                return ['name' => $cat['name'], 'count' => $cat['count']];
                            }, $topCategories)
                        ];
                        
                        // Insert into coach_report_personnel
                        $stmt = $pdo->prepare("INSERT INTO coach_report_personnel 
                                             (coach_report_id, personnel_id, coach_comment, coach_score, statistics_json) 
                                             VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $coachReportId,
                            $personnelId,
                            isset($_POST['coach_comment'][$personnelId]) ? clean($_POST['coach_comment'][$personnelId]) : null,
                            isset($_POST['coach_score'][$personnelId]) ? clean($_POST['coach_score'][$personnelId]) : null,
                            json_encode($statistics)
                        ]);
                        
                        // Link selected social reports
                        if (isset($_POST['social_reports'][$personnelId]) && is_array($_POST['social_reports'][$personnelId])) {
                            $insertStmt = $pdo->prepare("INSERT IGNORE INTO coach_report_social_reports 
                                                      (coach_report_id, social_report_id) VALUES (?, ?)");
                            foreach ($_POST['social_reports'][$personnelId] as $socialReportId) {
                                $insertStmt->execute([$coachReportId, $socialReportId]);
                            }
                        }
                    }
                    
                    $pdo->commit();
                    redirect('coach_report_list.php');
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = showError('خطا در ' . ($isEdit ? 'ویرایش' : 'ایجاد') . ' گزارش: ' . $e->getMessage());
            }
        }
    }
}

// Get available personnel for the selected company
$stmt = $pdo->prepare("SELECT p.*, p.full_name as full_title 
                     FROM personnel p 
                     WHERE p.company_id = ? AND p.is_active = 1 
                     ORDER BY p.full_name");
$stmt->execute([$_SESSION['company_id']]);
$personnel = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $isEdit ? 'ویرایش گزارش کوچ | مدیر دیجیتال مارکتینگ' : 'گزارش کوچ | مدیر دیجیتال مارکتینگ'; ?></h1>
    <?php if ($isEdit): ?>
        <a href="coach_report_list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست
        </a>
    <?php endif; ?>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">تنظیمات گزارش</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="company_id" class="form-label">انتخاب شرکت</label>
                    <select class="form-select" id="company_id" name="company_id" required onchange="updateReceivers()" <?php echo $isEdit ? 'disabled' : ''; ?>>
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" <?php echo ($selectedCompany == $company['id']) ? 'selected' : ''; ?>>
                                <?php echo $company['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="company_id" value="<?php echo $selectedCompany; ?>">
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="receiver_id" class="form-label">دریافت کننده گزارش</label>
                    <select class="form-select" id="receiver_id" name="receiver_id" required>
                        <option value="">انتخاب کنید...</option>
                        <?php if ($isEdit): ?>
                            <?php
                                // Get receiver info
                                $stmt = $pdo->prepare("SELECT id, full_name, 
                                                    (CASE WHEN role_id IN (SELECT id FROM roles WHERE is_ceo = 1) THEN 1 ELSE 0 END) as is_ceo
                                                    FROM personnel 
                                                    WHERE id = ?");
                                $stmt->execute([$selectedReceiver]);
                                $receiver = $stmt->fetch();
                                if ($receiver):
                            ?>
                                <option value="<?php echo $receiver['id']; ?>" selected>
                                    <?php echo $receiver['full_name'] . ($receiver['is_ceo'] ? ' (مدیر عامل)' : ''); ?>
                                </option>
                            <?php endif; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date_from" class="form-label">از تاریخ</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="date_to" class="form-label">تا تاریخ</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="general_comments" class="form-label">توضیحات کلی</label>
                <textarea class="form-control" id="general_comments" name="general_comments" rows="3"><?php echo $generalComments; ?></textarea>
                <div class="form-text">این توضیحات مستقل از افراد انتخاب شده است و اختیاری می‌باشد.</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">انتخاب افراد</label>
                <div class="row">
                    <?php if ($isEdit): ?>
                        <?php foreach ($personnel as $person): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="personnel[]" 
                                           value="<?php echo $person['id']; ?>" 
                                           id="person_<?php echo $person['id']; ?>"
                                           <?php echo in_array($person['id'], $selectedPersonnel) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="person_<?php echo $person['id']; ?>">
                                        <?php echo $person['full_title']; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($personnel as $person): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="personnel[]" 
                                           value="<?php echo $person['id']; ?>" 
                                           id="person_<?php echo $person['id']; ?>"
                                           <?php echo in_array($person['id'], $selectedPersonnel) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="person_<?php echo $person['id']; ?>">
                                        <?php echo $person['full_title']; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" name="show_fields" class="btn btn-primary px-5">
                    <i class="fas fa-eye"></i> نمایش فیلدها
                </button>
            </div>

            <?php if (!empty($selectedPersonnel)): ?>
                <?php foreach ($selectedPersonnel as $personnelId): ?>
                    <?php
                        // Get personnel info
                        $stmt = $pdo->prepare("SELECT p.*, p.full_name as full_title 
                                             FROM personnel p WHERE p.id = ?");
                        $stmt->execute([$personnelId]);
                        $person = $stmt->fetch();
                        
                        // Get report counts
                        $stmt = $pdo->prepare("SELECT COUNT(*) as report_count FROM reports 
                                             WHERE personnel_id = ? AND report_date BETWEEN ? AND ?");
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
                        $reportCount = $stmt->fetch()['report_count'];
                        
                        // Get used categories
                        $stmt = $pdo->prepare("SELECT DISTINCT c.name 
                                             FROM categories c 
                                             JOIN report_item_categories ric ON c.id = ric.category_id 
                                             JOIN report_items ri ON ric.item_id = ri.id 
                                             JOIN reports r ON ri.report_id = r.id 
                                             WHERE r.personnel_id = ? AND r.report_date BETWEEN ? AND ?");
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
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
                        $stmt->execute([$personnelId, $dateFrom, $dateTo]);
                        $topCategories = $stmt->fetchAll();
                        
                        // Get social report count
                        $stmt = $pdo->prepare("SELECT COUNT(*) as social_report_count 
                                             FROM monthly_reports mr 
                                             JOIN social_pages sp ON mr.page_id = sp.id 
                                             WHERE sp.company_id = ? AND mr.report_date BETWEEN ? AND ?");
                        $stmt->execute([$selectedCompany, $dateFrom, $dateTo]);
                        $socialReportCount = $stmt->fetch()['social_report_count'];
                        
                        // Get social reports for selection
                        $stmt = $pdo->prepare("SELECT mr.id, mr.report_date, sp.page_name 
                                             FROM monthly_reports mr 
                                             JOIN social_pages sp ON mr.page_id = sp.id 
                                             WHERE sp.company_id = ? AND mr.report_date BETWEEN ? AND ?");
                        $stmt->execute([$selectedCompany, $dateFrom, $dateTo]);
                        $socialReports = $stmt->fetchAll();
                        
                        // For edit mode, get existing values
                        $coachComment = '';
                        $coachScore = '';
                        
                        if ($isEdit && isset($personnelData[$personnelId])) {
                            $coachComment = $personnelData[$personnelId]['coach_comment'];
                            $coachScore = $personnelData[$personnelId]['coach_score'];
                        }
                    ?>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">گزارش عملکرد <?php echo $person['full_title']; ?></h5>
                        </div>
                        <div class="card-body">
                            <!-- نمایش آمار -->
                            <div class="alert alert-info">
                                <p><strong>تعداد گزارش‌های ثبت شده در بازه زمانی:</strong> <?php echo $reportCount; ?></p>
                                
                                <p><strong>دسته‌بندی‌های استفاده شده:</strong> 
                                    <?php echo empty($categories) ? 'موردی یافت نشد' : implode('، ', $categories); ?>
                                </p>
                                
                                <?php if (!empty($topCategories)): ?>
                                    <p><strong>5 دسته‌بندی پر تکرار:</strong></p>
                                    <ul class="mb-0">
                                        <?php foreach ($topCategories as $category): ?>
                                            <li><?php echo $category['name']; ?> (<?php echo $category['count']; ?> بار)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <p class="mb-0"><strong>تعداد گزارشات ثبت شده برای شبکه‌های اجتماعی:</strong> <?php echo $socialReportCount; ?></p>
                            </div>

                            <!-- فیلدهای ورودی -->
                            <div class="mb-3">
                                <label class="form-label">گزارشات شبکه اجتماعی مورد استناد:</label>
                                <div class="row">
                                    <?php foreach ($socialReports as $report): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="social_reports[<?php echo $personnelId; ?>][]" 
                                                       value="<?php echo $report['id']; ?>" 
                                                       id="report_<?php echo $personnelId; ?>_<?php echo $report['id']; ?>"
                                                       <?php echo $isEdit && in_array($report['id'], $linkedReports) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="report_<?php echo $personnelId; ?>_<?php echo $report['id']; ?>">
                                                    <?php echo $report['page_name']; ?> - <?php echo $report['report_date']; ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="coach_comment_<?php echo $personnelId; ?>" class="form-label">نظر در مورد عملکرد:</label>
                                <textarea class="form-control" id="coach_comment_<?php echo $personnelId; ?>" 
                                          name="coach_comment[<?php echo $personnelId; ?>]" rows="3"><?php echo $coachComment; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="coach_score_<?php echo $personnelId; ?>" class="form-label">امتیاز عملکرد (از 10):</label>
                                <input type="number" class="form-control" id="coach_score_<?php echo $personnelId; ?>" 
                                       name="coach_score[<?php echo $personnelId; ?>]" min="0" max="10" step="0.1"
                                       value="<?php echo $coachScore; ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-4 mb-4">
                    <?php if ($isEdit): ?>
                        <button type="submit" name="update_report" class="btn btn-warning px-5">
                            <i class="fas fa-save"></i> به‌روزرسانی گزارش
                        </button>
                    <?php else: ?>
                        <button type="submit" name="generate_report" class="btn btn-success px-5">
                            <i class="fas fa-save"></i> ایجاد گزارش
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function updateReceivers() {
    var companyId = document.getElementById('company_id').value;
    var receiverSelect = document.getElementById('receiver_id');
    
    // پاک کردن گزینه‌های قبلی
    receiverSelect.innerHTML = '<option value="">انتخاب کنید...</option>';
    
    if (companyId) {
        // درخواست AJAX برای دریافت لیست پرسنل شرکت
        fetch('get_receivers.php?company_id=' + companyId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                
                data.forEach(receiver => {
                    var option = document.createElement('option');
                    option.value = receiver.id;
                    // نمایش علامت مدیر عامل در کنار نام
                    option.textContent = receiver.full_name + (receiver.is_ceo == 1 ? ' (مدیر عامل)' : '');
                    receiverSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching receivers:', error);
            });
    } else {
        // Disable personnel dropdown when no company is selected
        receiverSelect.disabled = true;
    }
}

// اجرای تابع در هنگام لود صفحه اگر شرکتی از قبل انتخاب شده باشد
document.addEventListener('DOMContentLoaded', function() {
    var selectedCompany = document.getElementById('company_id').value;
    if (selectedCompany) {
        updateReceivers();
    }
});
</script>

<?php include 'footer.php'; ?>