<?php
// categories.php - Manage report categories
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check admin access
requireAdmin();

$message = '';

// Add new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = clean($_POST['name']);
    
    if (empty($name)) {
        $message = showError('لطفا نام دسته‌بندی را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $message = showSuccess('دسته‌بندی با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت دسته‌بندی: ' . $e->getMessage());
        }
    }
}

// Delete category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $categoryId = $_GET['delete'];
    
    // Check if category is being used in any report items
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM report_item_categories WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $usageCount = $stmt->fetch()['count'];
    
    if ($usageCount > 0) {
        $message = showError('این دسته‌بندی قابل حذف نیست زیرا در ' . $usageCount . ' آیتم گزارش استفاده شده است.');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $message = showSuccess('دسته‌بندی با موفقیت حذف شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در حذف دسته‌بندی: ' . $e->getMessage());
        }
    }
}

// Get all categories with usage count
$stmt = $pdo->query("SELECT c.*, 
                     (SELECT COUNT(*) FROM report_item_categories WHERE category_id = c.id) as usage_count 
                     FROM categories c ORDER BY name");
$categories = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت دسته‌بندی گزارش‌ها</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus"></i> افزودن دسته‌بندی جدید
    </button>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($categories) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام دسته‌بندی</th>
                            <th>تعداد استفاده</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $index => $category): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $category['name']; ?></td>
                                <td><?php echo $category['usage_count']; ?></td>
                                <td><?php echo $category['created_at']; ?></td>
                                <td>
                                    <?php if ($category['usage_count'] == 0): ?>
                                        <a href="?delete=<?php echo $category['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟')">
                                            حذف
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>حذف</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">هیچ دسته‌بندی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن دسته‌بندی جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">نام دسته‌بندی</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_category" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>