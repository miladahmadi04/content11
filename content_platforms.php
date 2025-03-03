<?php
// content_platforms.php - مدیریت پلتفرم‌های انتشار
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// بررسی دسترسی ادمین یا مدیر محتوا
if (!isAdmin() && !hasPermission('manage_content')) {
    redirect('index.php');
}

$message = '';
$companyId = isAdmin() ? (isset($_GET['company']) ? clean($_GET['company']) : '') : $_SESSION['company_id'];

// افزودن پلتفرم انتشار جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_platform'])) {
    $name = clean($_POST['name']);
    $selectedCompany = clean($_POST['company_id']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام پلتفرم انتشار را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_platforms (company_id, name, is_default) VALUES (?, ?, ?)");
            $stmt->execute([$selectedCompany, $name, $isDefault]);
            $message = showSuccess('پلتفرم انتشار با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت پلتفرم انتشار: ' . $e->getMessage());
        }
    }
}

// ویرایش پلتفرم انتشار
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_platform'])) {
    $platformId = clean($_POST['platform_id']);
    $name = clean($_POST['name']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام پلتفرم انتشار را وارد کنید.');
    } else {
        try {
            // بررسی اینکه آیا این پلتفرم انتشار متعلق به شرکت کاربر است
            $canEdit = false;
            if (isAdmin()) {
                $canEdit = true;
            } else {
                $stmt = $pdo->prepare("SELECT company_id FROM content_platforms WHERE id = ?");
                $stmt->execute([$platformId]);
                $platform = $stmt->fetch();
                if ($platform && $platform['company_id'] == $_SESSION['company_id']) {
                    $canEdit = true;
                }
            }
            
            if ($canEdit) {
                $stmt = $pdo->prepare("UPDATE content_platforms SET name = ?, is_default = ? WHERE id = ?");
                $stmt->execute([$name, $isDefault, $platformId]);
                $message = showSuccess('پلتفرم انتشار با موفقیت ویرایش شد.');
            } else {
                $message = showError('شما اجازه ویرایش این پلتفرم انتشار را ندارید.');
            }
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش پلتفرم انتشار: ' . $e->getMessage());
        }
    }
}

// حذف پلتفرم انتشار
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $platformId = $_GET['delete'];
    
    try {
        // بررسی اینکه آیا این پلتفرم انتشار متعلق به شرکت کاربر است
        $canDelete = false;
        if (isAdmin()) {
            $canDelete = true;
        } else {
            $stmt = $pdo->prepare("SELECT company_id FROM content_platforms WHERE id = ?");
            $stmt->execute([$platformId]);
            $platform = $stmt->fetch();
            if ($platform && $platform['company_id'] == $_SESSION['company_id']) {
                $canDelete = true;
            }
        }
        
        if ($canDelete) {
            // بررسی اینکه آیا این پلتفرم انتشار در استفاده است
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_platform_relations WHERE platform_id = ?");
            $stmt->execute([$platformId]);
            $usageCount = $stmt->fetch()['count'];
            
            if ($usageCount > 0) {
                $message = showError('این پلتفرم انتشار قابل حذف نیست زیرا در ' . $usageCount . ' محتوا استفاده شده است.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM content_platforms WHERE id = ?");
                $stmt->execute([$platformId]);
                $message = showSuccess('پلتفرم انتشار با موفقیت حذف شد.');
            }
        } else {
            $message = showError('شما اجازه حذف این پلتفرم انتشار را ندارید.');
        }
    } catch (PDOException $e) {
        $message = showError('خطا در حذف پلتفرم انتشار: ' . $e->getMessage());
    }
}

// دریافت لیست شرکت‌ها برای ادمین
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
    $companies = $stmt->fetchAll();
}

// دریافت لیست پلتفرم‌های انتشار
$query = "SELECT p.*, c.name as company_name, 
         (SELECT COUNT(*) FROM content_platform_relations WHERE platform_id = p.id) as usage_count 
         FROM content_platforms p 
         JOIN companies c ON p.company_id = c.id ";

if (!isAdmin()) {
    $query .= " WHERE p.company_id = ? ";
    $params = [$_SESSION['company_id']];
} else if (!empty($companyId)) {
    $query .= " WHERE p.company_id = ? ";
    $params = [$companyId];
} else {
    $params = [];
}

$query .= " ORDER BY p.company_id, p.name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$platforms = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت پلتفرم‌های انتشار</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlatformModal">
            <i class="fas fa-plus"></i> افزودن پلتفرم انتشار جدید
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
        <?php if (count($platforms) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام پلتفرم</th>
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
                        <?php foreach ($platforms as $index => $platform): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $platform['name']; ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo $platform['company_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($platform['is_default']): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $platform['usage_count']; ?></td>
                                <td><?php echo $platform['created_at']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editPlatformModal" 
                                            data-id="<?php echo $platform['id']; ?>"
                                            data-name="<?php echo $platform['name']; ?>"
                                            data-default="<?php echo $platform['is_default']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <?php if ($platform['usage_count'] == 0): ?>
                                        <a href="?<?php echo isAdmin() && !empty($companyId) ? 'company=' . $companyId . '&' : ''; ?>delete=<?php echo $platform['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این پلتفرم انتشار اطمینان دارید؟')">
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
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">هیچ پلتفرم انتشاری یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- افزودن پلتفرم انتشار جدید -->
<div class="modal fade" id="addPlatformModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن پلتفرم انتشار جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <?php if (isAdmin()): ?>
                        <div class="mb-3">
                            <label for="company_id" class="form-label">شرکت</label>
                            <select class="form-select" id="company_id" name="company_id" required>
                                <option value="">انتخاب شرکت...</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>" <?php echo $companyId == $company['id'] ? 'selected' : ''; ?>>
                                        <?php echo $company['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="company_id" value="<?php echo $_SESSION['company_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">نام پلتفرم</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">پیش‌فرض</label>
                        <div class="form-text">پلتفرم‌های پیش‌فرض به صورت خودکار در فرم‌های ثبت محتوا انتخاب می‌شوند.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_platform" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ویرایش پلتفرم انتشار -->
<div class="modal fade" id="editPlatformModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش پلتفرم انتشار</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_platform_id" name="platform_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">نام پلتفرم</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_default" name="is_default">
                        <label class="form-check-label" for="edit_is_default">پیش‌فرض</label>
                        <div class="form-text">پلتفرم‌های پیش‌فرض به صورت خودکار در فرم‌های ثبت محتوا انتخاب می‌شوند.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_platform" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ویرایش پلتفرم انتشار
    const editModal = document.getElementById('editPlatformModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const isDefault = button.getAttribute('data-default') === '1';
            
            document.getElementById('edit_platform_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_is_default').checked = isDefault;
        });
    }
});
</script>

<?php include 'footer.php'; ?>