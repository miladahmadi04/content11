<?php
// social_pages.php - Manage social media pages
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';

// Get company ID based on user type
if (isAdmin()) {
    $companyFilter = isset($_GET['company']) && is_numeric($_GET['company']) ? clean($_GET['company']) : '';
} else {
    // For personnel and CEO, use their own company
    $companyFilter = $_SESSION['company_id'];
}

// Add new social page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_page'])) {
    $company_id = clean($_POST['company_id']);
    $social_network_id = clean($_POST['social_network_id']);
    $page_name = clean($_POST['page_name']);
    $page_url = clean($_POST['page_url']);
    $start_date = clean($_POST['start_date']);
    $field_values = isset($_POST['field_values']) ? $_POST['field_values'] : [];
    
    // Check if user can add page for this company
    if (!isAdmin() && $company_id != $_SESSION['company_id']) {
        $message = showError('شما مجاز به ثبت صفحه برای این شرکت نیستید.');
    } else if (empty($company_id) || empty($social_network_id) || empty($page_name) || empty($page_url) || empty($start_date)) {
        $message = showError('لطفا تمام فیلدهای الزامی را پر کنید.');
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert social page
            $stmt = $pdo->prepare("INSERT INTO social_pages (company_id, social_network_id, page_name, page_url, start_date) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$company_id, $social_network_id, $page_name, $page_url, $start_date]);
            $pageId = $pdo->lastInsertId();
            
            // Get fields for this social network
            $fields = getSocialNetworkFields($social_network_id, $pdo);
            
            // Insert field values
            foreach ($fields as $field) {
                if (isset($field_values[$field['id']])) {
                    $fieldValue = clean($field_values[$field['id']]);
                    
                    // If field is required and value is empty, show error
                    if ($field['is_required'] && empty($fieldValue)) {
                        throw new Exception("فیلد {$field['field_label']} الزامی است.");
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO social_page_fields (page_id, field_id, field_value) 
                                          VALUES (?, ?, ?)");
                    $stmt->execute([$pageId, $field['id'], $fieldValue]);
                }
            }
            
            $pdo->commit();
            $message = showSuccess('صفحه اجتماعی با موفقیت اضافه شد.');
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = showError('خطا در ثبت صفحه: ' . $e->getMessage());
        }
    }
}

// Delete social page
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pageId = $_GET['delete'];
    
    // Check if user can delete this page
    $canDelete = false;
    
    if (isAdmin()) {
        $canDelete = true;
    } else {
        $stmt = $pdo->prepare("SELECT company_id FROM social_pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $page = $stmt->fetch();
        
        if ($page && $page['company_id'] == $_SESSION['company_id']) {
            $canDelete = true;
        }
    }
    
    if (!$canDelete) {
        $message = showError('شما مجاز به حذف این صفحه نیستید.');
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete related field values
            $stmt = $pdo->prepare("DELETE FROM social_page_fields WHERE page_id = ?");
            $stmt->execute([$pageId]);
            
            // Delete related KPIs
            $stmt = $pdo->prepare("DELETE FROM page_kpis WHERE page_id = ?");
            $stmt->execute([$pageId]);
            
            // Delete related reports
            $reportIdsStmt = $pdo->prepare("SELECT id FROM monthly_reports WHERE page_id = ?");
            $reportIdsStmt->execute([$pageId]);
            $reportIds = $reportIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($reportIds)) {
                $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
                
                // Delete report values
                $stmt = $pdo->prepare("DELETE FROM monthly_report_values WHERE report_id IN ($placeholders)");
                $stmt->execute($reportIds);
                
                // Delete report scores
                $stmt = $pdo->prepare("DELETE FROM report_scores WHERE report_id IN ($placeholders)");
                $stmt->execute($reportIds);
                
                // Delete reports
                $stmt = $pdo->prepare("DELETE FROM monthly_reports WHERE page_id = ?");
                $stmt->execute([$pageId]);
            }
            
            // Delete the page
            $stmt = $pdo->prepare("DELETE FROM social_pages WHERE id = ?");
            $stmt->execute([$pageId]);
            
            $pdo->commit();
            $message = showSuccess('صفحه اجتماعی با موفقیت حذف شد.');
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = showError('خطا در حذف صفحه: ' . $e->getMessage());
        }
    }
}

// Get all social networks
$socialNetworks = getAllSocialNetworks($pdo);

// Get all active companies for admin
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
    $companies = $stmt->fetchAll();
}

// Build query for pages based on user type and filters
if (isAdmin()) {
    $query = "SELECT p.*, s.name as network_name, s.icon as network_icon, c.name as company_name 
              FROM social_pages p 
              JOIN social_networks s ON p.social_network_id = s.id 
              JOIN companies c ON p.company_id = c.id";
    
    if (!empty($companyFilter)) {
        $query .= " WHERE p.company_id = " . $companyFilter;
    }
} else {
    // For personnel and CEO, only show pages from their company
    $query = "SELECT p.*, s.name as network_name, s.icon as network_icon, c.name as company_name 
              FROM social_pages p 
              JOIN social_networks s ON p.social_network_id = s.id 
              JOIN companies c ON p.company_id = c.id 
              WHERE p.company_id = " . $_SESSION['company_id'];
}

$query .= " ORDER BY s.name, p.page_name";
$stmt = $pdo->query($query);
$pages = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت صفحات شبکه‌های اجتماعی</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPageModal">
            <i class="fas fa-plus"></i> افزودن صفحه جدید
        </button>
        <?php if (isAdmin() && empty($companyFilter)): ?>
            <a href="social_networks.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> مدیریت شبکه‌ها
            </a>
        <?php endif; ?>
    </div>
</div>

<?php echo $message; ?>

<?php if (isAdmin() && empty($companyFilter)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        برای مشاهده صفحات یک شرکت خاص، از فیلتر زیر استفاده کنید.
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">فیلتر بر اساس شرکت</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-10">
                    <select class="form-select" name="company">
                        <option value="">انتخاب شرکت...</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>">
                                <?php echo $company['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">فیلتر</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($pages) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>شبکه اجتماعی</th>
                            <th>نام صفحه</th>
                            <?php if (isAdmin()): ?>
                                <th>شرکت</th>
                            <?php endif; ?>
                            <th>آدرس صفحه</th>
                            <th>تاریخ شروع</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td>
                                    <i class="<?php echo $page['network_icon']; ?>"></i>
                                    <?php echo $page['network_name']; ?>
                                </td>
                                <td><?php echo $page['page_name']; ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo $page['company_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <a href="<?php echo $page['page_url']; ?>" target="_blank">
                                        <?php echo $page['page_url']; ?>
                                    </a>
                                </td>
                                <td><?php echo $page['start_date']; ?></td>
                                <td><?php echo $page['created_at']; ?></td>
                                <td>
                                    <a href="page_kpi.php?page=<?php echo $page['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-chart-line"></i> KPI ها
                                    </a>
                                    <a href="social_report.php?page=<?php echo $page['id']; ?>" class="btn btn-sm btn-success">
    <i class="fas fa-file-alt"></i> گزارش‌ها
</a>
                                    <a href="?delete=<?php echo $page['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('آیا از حذف این صفحه اطمینان دارید؟ تمام گزارش‌ها و KPI های مرتبط نیز حذف خواهند شد.')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                هیچ صفحه‌ای یافت نشد.
                <?php if (!empty($companyFilter) && isAdmin()): ?>
                    <a href="social_pages.php" class="alert-link">نمایش تمام صفحات</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Page Modal -->
<div class="modal fade" id="addPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن صفحه جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="addPageForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <?php if (isAdmin()): ?>
                            <div class="col-md-4">
                                <label for="company_id" class="form-label">شرکت</label>
                                <select class="form-select" id="company_id" name="company_id" required>
                                    <option value="">انتخاب شرکت...</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (!empty($companyFilter) && $companyFilter == $company['id']) ? 'selected' : ''; ?>>
                                            <?php echo $company['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="company_id" value="<?php echo $_SESSION['company_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-<?php echo isAdmin() ? '4' : '6'; ?>">
                            <label for="social_network_id" class="form-label">شبکه اجتماعی</label>
                            <select class="form-select" id="social_network_id" name="social_network_id" required>
                                <option value="">انتخاب شبکه اجتماعی...</option>
                                <?php foreach ($socialNetworks as $network): ?>
                                    <option value="<?php echo $network['id']; ?>">
                                        <?php echo $network['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-<?php echo isAdmin() ? '4' : '6'; ?>">
                            <label for="start_date" class="form-label">تاریخ شروع</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="page_name" class="form-label">نام صفحه</label>
                            <input type="text" class="form-control" id="page_name" name="page_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="page_url" class="form-label">آدرس صفحه</label>
                            <input type="url" class="form-control" id="page_url" name="page_url" required>
                        </div>
                    </div>
                    
                    <div id="dynamic_fields">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            لطفاً ابتدا یک شبکه اجتماعی انتخاب کنید تا فیلدهای مربوطه نمایش داده شوند.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_page" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const socialNetworkSelect = document.getElementById('social_network_id');
    const dynamicFieldsDiv = document.getElementById('dynamic_fields');
    
    // Load fields when social network changes
    socialNetworkSelect.addEventListener('change', function() {
        const networkId = this.value;
        
        if (!networkId) {
            dynamicFieldsDiv.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    لطفاً ابتدا یک شبکه اجتماعی انتخاب کنید تا فیلدهای مربوطه نمایش داده شوند.
                </div>
            `;
            return;
        }
        
        // Show loading message
        dynamicFieldsDiv.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin"></i>
                در حال بارگذاری فیلدها...
            </div>
        `;
        
        // Fetch fields for this network using AJAX
        fetch('get_network_fields.php?network_id=' + networkId)
            .then(response => response.json())
            .then(fields => {
                if (fields.error) {
                    dynamicFieldsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            خطا: ${fields.error}
                        </div>
                    `;
                    return;
                }
                
                if (fields.length === 0) {
                    dynamicFieldsDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            هیچ فیلدی برای این شبکه اجتماعی تعریف نشده است.
                            <a href="social_network_fields.php?network=${networkId}" class="alert-link">تعریف فیلد</a>
                        </div>
                    `;
                    return;
                }
                
                // Create HTML for fields
                let fieldsHtml = `
                    <h5 class="mb-3 mt-4">فیلدهای اطلاعاتی شبکه</h5>
                    <div class="row">
                `;
                
                fields.forEach(field => {
                    let required = field.is_required == 1 ? 'required' : '';
                    let fieldType = 'text';
                    
                    switch (field.field_type) {
                        case 'number':
                            fieldType = 'number';
                            break;
                        case 'date':
                            fieldType = 'date';
                            break;
                        case 'url':
                            fieldType = 'url';
                            break;
                    }
                    
                    fieldsHtml += `
                        <div class="col-md-6 mb-3">
                            <label for="field_${field.id}" class="form-label">
                                ${field.field_label}
                                ${field.is_required == 1 ? '<span class="text-danger">*</span>' : ''}
                                ${field.is_kpi == 1 ? '<span class="badge bg-primary">KPI</span>' : ''}
                            </label>
                            <input type="${fieldType}" class="form-control" id="field_${field.id}" 
                                   name="field_values[${field.id}]" ${required}>
                        </div>
                    `;
                });
                
                fieldsHtml += `</div>`;
                dynamicFieldsDiv.innerHTML = fieldsHtml;
            })
            .catch(error => {
                dynamicFieldsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        خطا در بارگذاری فیلدها: ${error.message}
                    </div>
                `;
            });
    });
});
</script>

<?php include 'footer.php'; ?>