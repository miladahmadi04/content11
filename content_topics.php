<?php
// content_topics.php - مدیریت موضوعات کلی
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// بررسی دسترسی ادمین یا مدیر محتوا
if (!isAdmin() && !hasPermission('manage_content')) {
    redirect('index.php');
}

$message = '';
$companyId = isAdmin() ? (isset($_GET['company']) ? clean($_GET['company']) : '') : $_SESSION['company_id'];

// افزودن موضوع جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_topic'])) {
    $name = clean($_POST['name']);
    $selectedCompany = clean($_POST['company_id']);
    
    if (empty($name)) {
        $message = showError('لطفا نام موضوع را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO content_topics (company_id, name) VALUES (?, ?)");
            $stmt->execute([$selectedCompany, $name]);
            $message = showSuccess('موضوع کلی با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت موضوع: ' . $e->getMessage());
        }
    }
}

// ویرایش موضوع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_topic'])) {
    $topicId = clean($_POST['topic_id']);
    $name = clean($_POST['name']);
    
    if (empty($name)) {
        $message = showError('لطفا نام موضوع را وارد کنید.');
    } else {
        try {
            // بررسی اینکه آیا این موضوع متعلق به شرکت کاربر است
            $canEdit = false;
            if (isAdmin()) {
                $canEdit = true;
            } else {
                $stmt = $pdo->prepare("SELECT company_id FROM content_topics WHERE id = ?");
                $stmt->execute([$topicId]);
                $topic = $stmt->fetch();
                if ($topic && $topic['company_id'] == $_SESSION['company_id']) {
                    $canEdit = true;
                }
            }
            
            if ($canEdit) {
                $stmt = $pdo->prepare("UPDATE content_topics SET name = ? WHERE id = ?");
                $stmt->execute([$name, $topicId]);
                $message = showSuccess('موضوع کلی با موفقیت ویرایش شد.');
            } else {
                $message = showError('شما اجازه ویرایش این موضوع را ندارید.');
            }
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش موضوع: ' . $e->getMessage());
        }
    }
}

// حذف موضوع
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $topicId = $_GET['delete'];
    
    try {
        // بررسی اینکه آیا این موضوع متعلق به شرکت کاربر است
        $canDelete = false;
        if (isAdmin()) {
            $canDelete = true;
        } else {
            $stmt = $pdo->prepare("SELECT company_id FROM content_topics WHERE id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch();
            if ($topic && $topic['company_id'] == $_SESSION['company_id']) {
                $canDelete = true;
            }
        }
        
        if ($canDelete) {
            // بررسی اینکه آیا این موضوع در استفاده است
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM content_topic_relations WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            $usageCount = $stmt->fetch()['count'];
            
            if ($usageCount > 0) {
                $message = showError('این موضوع قابل حذف نیست زیرا در ' . $usageCount . ' محتوا استفاده شده است.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM content_topics WHERE id = ?");
                $stmt->execute([$topicId]);
                $message = showSuccess('موضوع کلی با موفقیت حذف شد.');
            }
        } else {
            $message = showError('شما اجازه حذف این موضوع را ندارید.');
        }
    } catch (PDOException $e) {
        $message = showError('خطا در حذف موضوع: ' . $e->getMessage());
    }
}

// دریافت لیست شرکت‌ها برای ادمین
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
    $companies = $stmt->fetchAll();
}

// دریافت لیست موضوعات
$query = "SELECT t.*, c.name as company_name, 
         (SELECT COUNT(*) FROM content_topic_relations WHERE topic_id = t.id) as usage_count 
         FROM content_topics t 
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
$topics = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت موضوعات کلی</h1>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
            <i class="fas fa-plus"></i> افزودن موضوع جدید
        </button>
        <a href="content_management.php" class="btn btn-secondary">
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
        <?php if (count($topics) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام موضوع</th>
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
                        <?php foreach ($topics as $index => $topic): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $topic['name']; ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo $topic['company_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($topic['is_default']): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $topic['usage_count']; ?></td>
                                <td><?php echo $topic['created_at']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTopicModal" 
                                            data-id="<?php echo $topic['id']; ?>"
                                            data-name="<?php echo $topic['name']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <?php if (!$topic['is_default'] && $topic['usage_count'] == 0): ?>
                                        <a href="?delete=<?php echo $topic['id']; ?><?php echo isAdmin() && !empty($companyId) ? '&company=' . $companyId : ''; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این موضوع اطمینان دارید؟')">
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
            <p class="text-center">هیچ موضوعی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- افزودن موضوع جدید -->
<div class="modal fade" id="addTopicModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن موضوع جدید</h5>
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
                        <label for="name" class="form-label">نام موضوع</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_topic" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ویرایش موضوع -->
<div class="modal fade" id="editTopicModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش موضوع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_topic_id" name="topic_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">نام موضوع</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_topic" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ویرایش موضوع
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editTopicModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            
            document.getElementById('edit_topic_id').value = id;
            document.getElementById('edit_name').value = name;
        });
    }
});
</script>

<?php include 'footer.php'; ?>