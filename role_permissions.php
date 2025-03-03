<?php
// role_permissions.php - Manage permissions for each role
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check admin access
requireAdmin();

$message = '';
$roleId = isset($_GET['role']) && is_numeric($_GET['role']) ? clean($_GET['role']) : null;

// If no role ID is provided, redirect to roles page
if (!$roleId) {
    redirect('roles.php');
}

// Get role details
$stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    redirect('roles.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $selectedPermissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Remove all current permissions for this role
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$roleId]);
        
        // Add new permissions
        if (!empty($selectedPermissions)) {
            $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            
            foreach ($selectedPermissions as $permissionId) {
                $insertStmt->execute([$roleId, $permissionId]);
            }
        }
        
        $pdo->commit();
        $message = showSuccess('دسترسی‌های نقش با موفقیت به‌روزرسانی شدند.');
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = showError('خطا در به‌روزرسانی دسترسی‌ها: ' . $e->getMessage());
    }
}

// Get all permissions grouped by category
$stmt = $pdo->query("SELECT * FROM permissions ORDER BY code");
$allPermissions = $stmt->fetchAll();

// Group permissions by category (based on prefix)
$permissionGroups = [];
foreach ($allPermissions as $permission) {
    $prefix = explode('_', $permission['code'])[0];
    if ($prefix === 'view' || $prefix === 'add' || $prefix === 'edit' || $prefix === 'delete' || $prefix === 'toggle' || $prefix === 'reset' || $prefix === 'manage') {
        $prefix = explode('_', $permission['code'])[1];
    }
    
    if (!isset($permissionGroups[$prefix])) {
        $permissionGroups[$prefix] = [];
    }
    
    $permissionGroups[$prefix][] = $permission;
}

// Get current permissions for this role
$stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$stmt->execute([$roleId]);
$currentPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>مدیریت دسترسی‌های نقش</h1>
        <p class="text-muted">تعیین دسترسی‌های مجاز برای نقش "<?php echo $role['name']; ?>"</p>
    </div>
    <div>
        <a href="roles.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست نقش‌ها
        </a>
    </div>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">تنظیم دسترسی‌ها</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="selectAll">انتخاب همه</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">لغو همه</button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php foreach ($permissionGroups as $groupName => $permissions): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo getGroupTitle($groupName); ?></h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary select-group" data-group="<?php echo $groupName; ?>">انتخاب گروه</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php foreach ($permissions as $permission): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input permission-checkbox group-<?php echo $groupName; ?>" 
                                               type="checkbox" 
                                               id="permission_<?php echo $permission['id']; ?>" 
                                               name="permissions[]" 
                                               value="<?php echo $permission['id']; ?>"
                                               <?php echo in_array($permission['id'], $currentPermissions) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="permission_<?php echo $permission['id']; ?>">
                                            <?php echo $permission['name']; ?>
                                            <small class="text-muted d-block"><?php echo $permission['description']; ?></small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4 text-center">
                <button type="submit" name="save_permissions" class="btn btn-primary px-5">
                    <i class="fas fa-save"></i> ذخیره دسترسی‌ها
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all permissions
    document.getElementById('selectAll').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
            checkbox.checked = true;
        });
    });
    
    // Deselect all permissions
    document.getElementById('deselectAll').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
            checkbox.checked = false;
        });
    });
    
    // Select group permissions
    document.querySelectorAll('.select-group').forEach(function(button) {
        button.addEventListener('click', function() {
            const groupName = this.getAttribute('data-group');
            const isAnyUnchecked = Array.from(document.querySelectorAll(`.group-${groupName}`))
                .some(checkbox => !checkbox.checked);
            
            document.querySelectorAll(`.group-${groupName}`).forEach(function(checkbox) {
                checkbox.checked = isAnyUnchecked; // Check all if any are unchecked, otherwise uncheck all
            });
        });
    });
});
</script>

<?php
// Helper function to get friendly group titles
function getGroupTitle($group) {
    $titles = [
        'dashboard' => 'داشبورد',
        'companies' => 'شرکت‌ها',
        'personnel' => 'پرسنل',
        'roles' => 'نقش‌های کاربری',
        'categories' => 'دسته‌بندی‌ها',
        'daily' => 'گزارش‌های روزانه',
        'report' => 'گزارش‌ها',
        'social' => 'شبکه‌های اجتماعی',
        'networks' => 'شبکه‌های اجتماعی',
        'fields' => 'فیلدهای شبکه‌ها',
        'kpi' => 'مدل‌های KPI',
        'model' => 'مدل‌های KPI',
        'models' => 'مدل‌های KPI',
        'pages' => 'صفحات اجتماعی',
        'page' => 'صفحات اجتماعی',
        'reports' => 'گزارش‌های شبکه اجتماعی',
        'performance' => 'عملکرد مورد انتظار',
        'expected' => 'عملکرد مورد انتظار',
        'permissions' => 'دسترسی‌ها',
        'password' => 'رمز عبور',
        'view_reports' => 'مشاهده گزارش‌ها',
        'view_coach_reports' => 'مشاهده گزارش کوچ',
        'add_coach_report' => 'ایجاد گزارش کوچ',
        'coach' => 'گزارش کوچ'
    ];
    
    return isset($titles[$group]) ? $titles[$group] : ucfirst($group);
}

include 'footer.php';
?>