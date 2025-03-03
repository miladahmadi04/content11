<?php
// content_tasks.php - مدیریت وظایف محتوایی
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// بررسی دسترسی ادمین یا مدیر محتوا
if (!isAdmin() && !hasPermission('manage_content')) {
    redirect('index.php');
}

$message = '';
$companyId = isAdmin() ? (isset($_GET['company']) ? clean($_GET['company']) : '') : $_SESSION['company_id'];

// افزودن وظیفه جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $name = clean($_POST['name']);
    $selectedCompany = clean($_POST['company_id']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام وظیفه را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_tasks (company_id, name, is_default, can_delete) VALUES (?, ?, ?, 1)");
            $stmt->execute([$selectedCompany, $name, $isDefault]);
            $message = showSuccess('وظیفه با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت وظیفه: ' . $e->getMessage());
        }
    }
}

// ویرایش وظیفه
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task'])) {
    $taskId = clean($_POST['task_id']);
    $name = clean($_POST['name']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام وظیفه را وارد کنید.');
    } else {
        try {
            // بررسی اینکه آیا این وظیفه متعلق به شرکت کاربر است
            $canEdit = false;
            if (isAdmin()) {
                $canEdit = true;
            } else {
                $stmt = $pdo->prepare("SELECT company_id, can_delete FROM content_tasks WHERE id = ?");
                $stmt->execute([$taskId]);
                $task = $stmt->fetch();
                if ($task && $task['company_id'] == $_SESSION['company_id']) {
                    $canEdit = true;
                }
            }
            
            if ($canEdit) {
                $stmt = $pdo->prepare("UPDATE content_tasks SET name = ?, is_default = ? WHERE id = ?");
                $stmt->execute([$name, $isDefault, $taskId]);
                $message = showSuccess('وظیفه با موفقیت ویرایش شد.');
            } else {
                $message = showError('شما اجازه ویرایش این وظیفه را ندارید.');
            }
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش وظیفه: ' . $e->getMessage());
        }
    }
}

// حذف وظیفه
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $taskId = $_GET['delete'];
    
    try {
        // بررسی اینکه آیا این وظیفه متعلق به شرکت کاربر است و قابل حذف است
        $canDelete = false;
        if (isAdmin()) {
            $stmt = $pdo->prepare("SELECT can_delete FROM content_tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            $canDelete = ($task && $task['can_delete'] == 1);
        } else {
            $stmt = $pdo->prepare("SELECT company_id, can_delete FROM content_tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            $canDelete = ($task && $task['company_id'] == $_SESSION['company_id'] && $task['can_delete'] == 1);
        }
        
        if ($canDelete) {
            // بررسی اینکه آیا این وظیفه در استفاده است
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_task_assignments WHERE task_id = ?");
            $stmt->execute([$taskId]);
            $usageCount = $stmt->fetch()['count'];
            
            if ($usageCount > 0) {
                $message = showError('این وظیفه قابل حذف نیست زیرا در ' . $usageCount . ' محتوا استفاده شده است.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM content_tasks WHERE id = ?");
                $stmt->execute([$taskId]);
                $message = showSuccess('وظیفه با موفقیت حذف شد.');
            }
        } else {
            $message = showError('این وظیفه قابل حذف نیست یا شما اجازه حذف آن را ندارید.');
        }
    } catch (PDOException $e) {
        $message = showError('خطا در حذف وظیفه: ' . $e->getMessage());
    }
}

// اضافه کردن وظایف پیش‌فرض برای شرکت انتخاب شده اگر وجود نداشته باشند
if (!empty($companyId)) {
    try {
        // بررسی وجود وظایف پیش‌فرض
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_tasks WHERE company_id = ? AND name IN ('وظیفه اصلی', 'فرآیند پس از انتشار')");
        $stmt->execute([$companyId]);
        $defaultTasksCount = $stmt->fetch()['count'];
        
        // اگر وظایف پیش‌فرض وجود ندارند، آنها را اضافه کن
        if ($defaultTasksCount < 2) {
            $defaultTasks = [
                ['وظیفه اصلی', 1, 0], // name, is_default, can_delete
                ['فرآیند پس از انتشار', 1, 0]
            ];
            
            $insertStmt = $pdo->prepare("INSERT INTO content_tasks (company_id, name, is_default, can_delete) VALUES (?, ?, ?, ?)");
            
            foreach ($defaultTasks as $task) {
                // بررسی وجود وظیفه
                $checkStmt = $pdo->prepare("SELECT id FROM content_tasks WHERE company_id = ? AND name = ?");
                $checkStmt->execute([$companyId, $task[0]]);
                $exists = $checkStmt->fetch();
                
                if (!$exists) {
                    $insertStmt->execute([$companyId, $task[0], $task[1], $task[2]]);
                }
            }
        }
    } catch (PDOException $e) {
        // خطا در اضافه کردن وظایف پیش‌فرض
    }
}

// دریافت لیست شرکت‌ها برای ادمین
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
    $companies = $stmt->fetchAll();
}

// دریافت لیست وظایف
$query = "SELECT t.*, c.name as company_name,
          (SELECT COUNT(*) FROM content_task_assignments WHERE task_id = t.id) as usage_count 
          FROM content_tasks t 
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
$tasks = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت وظایف محتوایی</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="fas fa-plus"></i> افزودن وظیفه جدید
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
        <?php if (count($tasks) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام وظیفه</th>
                            <?php if (isAdmin()): ?>
                                <th>شرکت</th>
                            <?php endif; ?>
                            <th>پیش‌فرض</th>
                            <th>قابل حذف</th>
                            <th>تعداد استفاده</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $index => $task): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $task['name']; ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo $task['company_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($task['is_default']): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['can_delete']): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $task['usage_count']; ?></td>
                                <td><?php echo $task['created_at']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTaskModal" 
                                            data-id="<?php echo $task['id']; ?>"
                                            data-name="<?php echo $task['name']; ?>"
                                            data-default="<?php echo $task['is_default']; ?>"
                                            data-delete="<?php echo $task['can_delete']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <?php if ($task['can_delete'] && $task['usage_count'] == 0): ?>
                                        <a href="?<?php echo isAdmin() && !empty($companyId) ? 'company=' . $companyId . '&' : ''; ?>delete=<?php echo $task['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این وظیفه اطمینان دارید؟')">
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
            <p class="text-center">هیچ وظیفه‌ای یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- افزودن وظیفه جدید -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن وظیفه جدید</h5>
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
                        <label for="name" class="form-label">نام وظیفه</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">پیش‌فرض</label>
                        <div class="form-text">وظایف پیش‌فرض به صورت خودکار در فرم‌های ثبت محتوا انتخاب می‌شوند.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_task" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ویرایش وظیفه -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش وظیفه</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_task_id" name="task_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">نام وظیفه</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_default" name="is_default">
                        <label class="form-check-label" for="edit_is_default">پیش‌فرض</label>
                        <div class="form-text">وظایف پیش‌فرض به صورت خودکار در فرم‌های ثبت محتوا انتخاب می‌شوند.</div>
                    </div>

                    <div class="mb-3" id="can_delete_info">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            این وظیفه پیش‌فرض سیستم است و قابل حذف نیست.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_task" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ویرایش وظیفه
    const editModal = document.getElementById('editTaskModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const isDefault = button.getAttribute('data-default') === '1';
            const canDelete = button.getAttribute('data-delete') === '1';
            
            document.getElementById('edit_task_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_is_default').checked = isDefault;
            
            // نمایش یا عدم نمایش هشدار برای وظایف غیرقابل حذف
            const canDeleteInfo = document.getElementById('can_delete_info');
            if (canDelete) {
                canDeleteInfo.style.display = 'none';
            } else {
                canDeleteInfo.style.display = 'block';
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>