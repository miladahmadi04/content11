<?php
// content_types.php - مدیریت انواع محتوا
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// بررسی دسترسی ادمین یا مدیر محتوا
if (!isAdmin() && !hasPermission('manage_content')) {
    redirect('index.php');
}

$message = '';
$companyId = isAdmin() ? (isset($_GET['company']) ? clean($_GET['company']) : '') : $_SESSION['company_id'];

// افزودن نوع محتوا جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $name = clean($_POST['name']);
    $selectedCompany = clean($_POST['company_id']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام نوع محتوا را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_types (company_id, name, is_default) VALUES (?, ?, ?)");
            $stmt->execute([$selectedCompany, $name, $isDefault]);
            $message = showSuccess('نوع محتوا با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت نوع محتوا: ' . $e->getMessage());
        }
    }
}

// ویرایش نوع محتوا
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_type'])) {
    $typeId = clean($_POST['type_id']);
    $name = clean($_POST['name']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام نوع محتوا را وارد کنید.');
    } else {
        try {
            // بررسی اینکه آیا این نوع محتوا متعلق به شرکت کاربر است
            $canEdit = false;
            if (isAdmin()) {
                $canEdit = true;
            } else {
                $stmt = $pdo->prepare("SELECT company_id FROM content_types WHERE id = ?");
                $stmt->execute([$typeId]);
                $type = $stmt->fetch();
                if ($type && $type['company_id'] == $_SESSION['company_id']) {
                    $canEdit = true;
                }
            }
            
            if ($canEdit) {
                $stmt = $pdo->prepare("UPDATE content_types SET name = ?, is_default = ? WHERE id = ?");
                $stmt->execute([$name, $isDefault, $typeId]);
                $message = showSuccess('نوع محتوا با موفقیت ویرایش شد.');
            } else {
                $message = showError('شما اجازه ویرایش این نوع محتوا را ندارید.');
            }
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش نوع محتوا: ' . $e->getMessage());
        }
    }
}

// حذف نوع محتوا
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $typeId = $_GET['delete'];
    
    try {
        // بررسی اینکه آیا این نوع محتوا متعلق به شرکت کاربر است
        $canDelete = false;
        if (isAdmin()) {
            $canDelete = true;
        } else {
            $stmt = $pdo->prepare("SELECT company_id FROM content_types WHERE id = ?");
            $stmt->execute([$typeId]);
            $type = $stmt->fetch();
            if ($type && $type['company_id'] == $_SESSION['company_id']) {
                $canDelete = true;
            }
        }
        
        if ($canDelete) {
            // بررسی اینکه آیا این نوع محتوا در استفاده است
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_type_relations WHERE type_id = ?");
            $stmt->execute([$typeId]);
            $usageCount = $stmt->fetch()['count'];
            
            if ($usageCount > 0) {
                $message = showError('این نوع محتوا قابل حذف نیست زیرا در ' . $usageCount . ' محتوا استفاده شده است.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM content_types WHERE id = ?");
                $stmt->execute([$typeId]);
                $message = showSuccess('نوع محتوا با موفقیت حذف شد.');
            }
        } else {
            $message = showError('شما اجازه حذف این نوع محتوا را ندارید.');
        }
    } catch (PDOException $e) {
        $message = showError('خطا در حذف نوع محتوا: ' . $e->getMessage());
    }
}

// دریافت لیست شرکت‌ها برای ادمین
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
    $companies = $stmt->fetchAll();
}

// دریافت لیست انواع محتوا
$query = "SELECT t.*, c.name as company_name, 
         (SELECT COUNT(*) FROM content_type_relations WHERE type_id = t.id) as usage_count 
         FROM content_types t 
         JOIN companies c ON t.company_id = c.id ";

if (!isAdmin()) {
    $query .= " WHERE t.company_id = ? ";
    $params = [$_SESSION['company_id']];
} else if (!empty($companyId)) {
    $query .= " WHERE t.company_id = ? ";
    $params = [$companyId];
} else {
    $params = [];
}

$query .= " ORDER BY t.company_id, t.name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$types = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت انواع محتوا</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
            <i class="fas fa-plus"></i> افزودن نوع محتوا جدید
        </button>
        <a href="content_management.php<?php echo isAdmin() && !empty($companyId) ? '?company=' . $companyId : ''; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به مدیریت محتوا
        </a>
    </div>
</div>

<?php echo $message; ?>

<?php if (isAdmin()): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">فیلتر بر اساس شرکت</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-10">
                    <select class="form-select" name="company">
                        <option value="">همه شرکت‌ها</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" <?php echo $companyId == $company['id'] ? 'selected' : ''; ?>>
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
        <?php if (count($types) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام نوع محتوا</th>
                            <?php if (isAdmin()): ?>
                                <th>شرکت</th>
                            <?php endif; ?>
                            <th>پیش‌فرض</th>
                            <th>تعداد استفاده</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $index => $type): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $type['name']; ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo $type['company_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($type['is_default']): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $type['usage_count']; ?></td>
                                <td><?php echo $type['created_at']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTypeModal" 
                                            data-id="<?php echo $type['id']; ?>"
                                            data-name="<?php echo $type['name']; ?>"
                                            data-default="<?php echo $type['is_default']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <?php if ($type['usage_count'] == 0): ?>
                                        <a href="?<?php echo isAdmin() && !empty($companyId) ? 'company=' . $companyId . '&' : ''; ?>delete=<?php echo $type['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این نوع محتوا اطمینان دارید؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>