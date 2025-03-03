<?php
// roles.php - Manage user roles with advanced permissions
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check admin access
requireAdmin();

$message = '';

// Add new role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $name = clean($_POST['name']);
    $is_ceo = isset($_POST['is_ceo']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام نقش را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO roles (name, is_ceo) VALUES (?, ?)");
            $stmt->execute([$name, $is_ceo]);
            $message = showSuccess('نقش کاربری با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت نقش: ' . $e->getMessage());
        }
    }
}

// Edit role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_role'])) {
    $roleId = clean($_POST['role_id']);
    $name = clean($_POST['name']);
    $is_ceo = isset($_POST['is_ceo']) ? 1 : 0;
    
    if (empty($name)) {
        $message = showError('لطفا نام نقش را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE roles SET name = ?, is_ceo = ? WHERE id = ?");
            $stmt->execute([$name, $is_ceo, $roleId]);
            $message = showSuccess('نقش کاربری با موفقیت ویرایش شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش نقش: ' . $e->getMessage());
        }
    }
}

// Delete role
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $roleId = $_GET['delete'];
    
    // Check if role is being used by any personnel
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM personnel WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $usageCount = $stmt->fetch()['count'];
    
    if ($usageCount > 0) {
        $message = showError('این نقش قابل حذف نیست زیرا توسط ' . $usageCount . ' پرسنل استفاده می‌شود.');
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete role permissions
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            
            // Delete role
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            
            $pdo->commit();
            $message = showSuccess('نقش کاربری با موفقیت حذف شد.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = showError('خطا در حذف نقش: ' . $e->getMessage());
        }
    }
}

// Get all roles with usage count and permission count
$stmt = $pdo->query("SELECT r.*, 
                     (SELECT COUNT(*) FROM personnel WHERE role_id = r.id) as usage_count,
                     (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permission_count 
                     FROM roles r ORDER BY name");
$roles = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت نقش‌های کاربری</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
        <i class="fas fa-plus"></i> افزودن نقش جدید
    </button>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($roles) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام نقش</th>
                            <th>دسترسی مدیر عامل</th>
                            <th>تعداد دسترسی‌ها</th>
                            <th>تعداد استفاده</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $index => $role): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $role['name']; ?></td>
                                <td>
                                    <?php if ($role['is_ceo']): ?>
                                        <span class="badge bg-success">دارد</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">ندارد</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $role['permission_count']; ?>
                                    <?php if ($role['permission_count'] > 0): ?>
                                        <span class="badge bg-primary">فعال</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">بدون دسترسی</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $role['usage_count']; ?></td>
                                <td><?php echo $role['created_at']; ?></td>
                                <td>
                                    <a href="role_permissions.php?role=<?php echo $role['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-key"></i> دسترسی‌ها
                                    </a>
                                    
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editRoleModal" 
                                            data-id="<?php echo $role['id']; ?>"
                                            data-name="<?php echo $role['name']; ?>"
                                            data-ceo="<?php echo $role['is_ceo']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <?php if ($role['usage_count'] == 0): ?>
                                        <a href="?delete=<?php echo $role['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این نقش اطمینان دارید؟ دسترسی‌های مربوط به این نقش نیز حذف خواهند شد.')">
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
            <p class="text-center">هیچ نقشی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن نقش جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">نام نقش</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_ceo" name="is_ceo">
                        <label class="form-check-label" for="is_ceo">دسترسی مدیر عامل (مشاهده گزارش‌های پرسنل شرکت)</label>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        پس از ایجاد نقش، می‌توانید دسترسی‌های آن را از بخش "دسترسی‌ها" تنظیم کنید.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_role" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش نقش</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_role_id" name="role_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">نام نقش</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_ceo" name="is_ceo">
                        <label class="form-check-label" for="edit_is_ceo">دسترسی مدیر عامل (مشاهده گزارش‌های پرسنل شرکت)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_role" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit role modal
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editRoleModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const isCeo = button.getAttribute('data-ceo') === '1';
            
            document.getElementById('edit_role_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_is_ceo').checked = isCeo;
        });
    }
});
</script>

<?php include 'footer.php'; ?>