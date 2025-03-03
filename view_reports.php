<?php
// view_reports.php - View personnel reports with enhanced filtering
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if logged in (allowing both admin and personnel)
if (!isLoggedIn()) {
    redirect('login.php');
}

$personnelId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Filter parameters
$startDate = isset($_GET['start_date']) ? clean($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? clean($_GET['end_date']) : '';
$categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? clean($_GET['category']) : '';
$companyFilter = isset($_GET['company']) && is_numeric($_GET['company']) ? clean($_GET['company']) : '';
$personnelFilter = isset($_GET['personnel']) && is_numeric($_GET['personnel']) ? clean($_GET['personnel']) : '';

// Get all categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// For admin, get companies for filter
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM companies ORDER BY name");
    $companies = $stmt->fetchAll();
    
    // Get personnel for the selected company (if any)
    $personnel = [];
    if (!empty($companyFilter)) {
        $stmt = $pdo->prepare("SELECT id, full_name FROM personnel WHERE company_id = ? ORDER BY full_name");
        $stmt->execute([$companyFilter]);
        $personnel = $stmt->fetchAll();
    }
}

// Build query based on user type
if (isAdmin()) {
    // Admin can see all reports
    $query = "SELECT r.*, p.full_name, c.name as company_name,
              (SELECT COUNT(*) FROM report_items WHERE report_id = r.id) as item_count
              FROM reports r
              JOIN personnel p ON r.personnel_id = p.id
              JOIN companies c ON p.company_id = c.id
              WHERE 1=1";
    $params = [];
    
    // Filter by company if selected
    if (!empty($companyFilter)) {
        $query .= " AND c.id = ?";
        $params[] = $companyFilter;
    }
    
    // Filter by personnel if selected
    if (!empty($personnelFilter)) {
        $query .= " AND p.id = ?";
        $params[] = $personnelFilter;
    }
} elseif (isCEO()) {
    // CEO can see reports from their company
    $query = "SELECT r.*, p.full_name, c.name as company_name,
              (SELECT COUNT(*) FROM report_items WHERE report_id = r.id) as item_count
              FROM reports r
              JOIN personnel p ON r.personnel_id = p.id
              JOIN companies c ON p.company_id = c.id
              WHERE c.id = ?";
    $params = [$_SESSION['company_id']];
    
    // Filter by personnel if CEO selects a personnel
    if (!empty($personnelFilter)) {
        $query .= " AND p.id = ?";
        $params[] = $personnelFilter;
    }
} else {
    // Regular personnel can only see their own reports
    $query = "SELECT r.*, 
              (SELECT COUNT(*) FROM report_items WHERE report_id = r.id) as item_count,
              (SELECT full_name FROM personnel WHERE id = r.personnel_id) as full_name,
              (SELECT c.name FROM personnel p JOIN companies c ON p.company_id = c.id WHERE p.id = r.personnel_id) as company_name
              FROM reports r 
              WHERE r.personnel_id = ?";
    $params = [$personnelId];
}

// Apply filters
if (!empty($startDate)) {
    $query .= " AND r.report_date >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $query .= " AND r.report_date <= ?";
    $params[] = $endDate;
}

if (!empty($categoryId)) {
    $query .= " AND EXISTS (
                SELECT 1 FROM report_items ri 
                JOIN report_item_categories ric ON ri.id = ric.item_id 
                WHERE ri.report_id = r.id AND ric.category_id = ?)";
    $params[] = $categoryId;
}

$query .= " ORDER BY r.report_date DESC, r.created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مشاهده گزارش‌های روزانه</h1>
    <?php if (!isAdmin() && !isCEO()): ?>
    <a href="reports.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> ثبت گزارش جدید
    </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">فیلترها</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" id="filterForm">
            <div class="row">
                <?php if (isAdmin()): ?>
                <div class="col-md-3 mb-3">
                    <label for="company" class="form-label">شرکت</label>
                    <select class="form-select" id="company" name="company">
                        <option value="">همه شرکت‌ها</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" <?php echo $companyFilter == $company['id'] ? 'selected' : ''; ?>>
                                <?php echo $company['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="personnel" class="form-label">پرسنل</label>
                    <select class="form-select" id="personnel" name="personnel" <?php echo empty($companyFilter) ? 'disabled' : ''; ?>>
                        <option value="">همه پرسنل</option>
                        <?php if (!empty($companyFilter) && !empty($personnel)): ?>
                            <?php foreach ($personnel as $person): ?>
                                <option value="<?php echo $person['id']; ?>" <?php echo $personnelFilter == $person['id'] ? 'selected' : ''; ?>>
                                    <?php echo $person['full_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php elseif (isCEO()): ?>
                <!-- CEO can filter by personnel in their company -->
                <div class="col-md-3 mb-3">
                    <label for="personnel" class="form-label">پرسنل</label>
                    <select class="form-select" id="personnel" name="personnel">
                        <option value="">همه پرسنل</option>
                        <?php 
                            $stmt = $pdo->prepare("SELECT id, full_name FROM personnel WHERE company_id = ? ORDER BY full_name");
                            $stmt->execute([$_SESSION['company_id']]);
                            $companyPersonnel = $stmt->fetchAll();
                            
                            foreach ($companyPersonnel as $person): 
                        ?>
                            <option value="<?php echo $person['id']; ?>" <?php echo $personnelFilter == $person['id'] ? 'selected' : ''; ?>>
                                <?php echo $person['full_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-<?php echo (isAdmin() || isCEO()) ? '2' : '3'; ?> mb-3">
                    <label for="start_date" class="form-label">از تاریخ</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-<?php echo (isAdmin() || isCEO()) ? '2' : '3'; ?> mb-3">
                    <label for="end_date" class="form-label">تا تاریخ</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-<?php echo (isAdmin() || isCEO()) ? '2' : '4'; ?> mb-3">
                    <label for="category" class="form-label">دسته‌بندی</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">همه دسته‌بندی‌ها</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-<?php echo (isAdmin() || isCEO()) ? '12' : '2'; ?> mb-3 <?php echo (isAdmin() || isCEO()) ? 'text-center' : 'd-flex align-items-end'; ?>">
                    <button type="submit" class="btn btn-primary <?php echo (isAdmin() || isCEO()) ? 'px-5' : 'w-100'; ?>">اعمال فیلتر</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($reports) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>تاریخ گزارش</th>
                            <?php if (isAdmin() || isCEO()): ?>
                            <th>نام پرسنل</th>
                            <th>شرکت</th>
                            <?php endif; ?>
                            <th>تعداد آیتم</th>
                            <th>تاریخ ثبت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $index => $report): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $report['report_date']; ?></td>
                                <?php if (isAdmin() || isCEO()): ?>
                                <td><?php echo $report['full_name']; ?></td>
                                <td><?php echo $report['company_name']; ?></td>
                                <?php endif; ?>
                                <td><?php echo $report['item_count']; ?></td>
                                <td><?php echo $report['created_at']; ?></td>
                                <td>
                                    <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-info">
                                        مشاهده کامل
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">هیچ گزارشی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- AJAX script to get personnel when company changes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const companySelect = document.getElementById('company');
    const personnelSelect = document.getElementById('personnel');
    
    companySelect.addEventListener('change', function() {
        const companyId = this.value;
        
        // Clear personnel dropdown
        personnelSelect.innerHTML = '<option value="">همه پرسنل</option>';
        
        if (companyId) {
            // Enable personnel dropdown
            personnelSelect.disabled = false;
            
            // Fetch personnel for the selected company
            fetch('get_personnel.php?company_id=' + companyId)
                .then(response => response.json())
                .then(data => {
                    data.forEach(person => {
                        const option = document.createElement('option');
                        option.value = person.id;
                        option.textContent = person.full_name;
                        personnelSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching personnel:', error);
                });
        } else {
            // Disable personnel dropdown when no company is selected
            personnelSelect.disabled = true;
        }
    });
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>