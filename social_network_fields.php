<?php
// social_network_fields.php - Manage fields for social networks
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check admin access
requireAdmin();

$message = '';

// Get network ID from query string
$networkId = isset($_GET['network']) && is_numeric($_GET['network']) ? clean($_GET['network']) : null;

// If no network ID, redirect to social networks page
if (!$networkId) {
    header('Location: social_networks.php');
    exit;
}

// Get network details
$network = getSocialNetwork($networkId, $pdo);
if (!$network) {
    header('Location: social_networks.php');
    exit;
}

// Add new field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_field'])) {
    $fieldName = clean($_POST['field_name']);
    $fieldLabel = clean($_POST['field_label']);
    $fieldType = clean($_POST['field_type']);
    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $isKpi = isset($_POST['is_kpi']) ? 1 : 0;
    $sortOrder = (int) clean($_POST['sort_order']);
    
    if (empty($fieldName) || empty($fieldLabel)) {
        $message = showError('لطفا نام و برچسب فیلد را وارد کنید.');
    } else {
        try {
            // Check if field name already exists for this network
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM social_network_fields 
                                  WHERE social_network_id = ? AND field_name = ?");
            $stmt->execute([$networkId, $fieldName]);
            $exists = $stmt->fetch()['count'] > 0;
            
            if ($exists) {
                $message = showError('این نام فیلد قبلاً برای این شبکه اجتماعی استفاده شده است.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO social_network_fields 
                                      (social_network_id, field_name, field_label, field_type, is_required, is_kpi, sort_order) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$networkId, $fieldName, $fieldLabel, $fieldType, $isRequired, $isKpi, $sortOrder]);
                $message = showSuccess('فیلد با موفقیت اضافه شد.');
            }
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت فیلد: ' . $e->getMessage());
        }
    }
}

// Edit field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_field'])) {
    $fieldId = clean($_POST['field_id']);
    $fieldName = clean($_POST['field_name']);
    $fieldLabel = clean($_POST['field_label']);
    $fieldType = clean($_POST['field_type']);
    $isRequired = isset($_POST['is_required']) ? 1 : 0;
    $isKpi = isset($_POST['is_kpi']) ? 1 : 0;
    $sortOrder = (int) clean($_POST['sort_order']);
    
    if (empty($fieldName) || empty($fieldLabel)) {
        $message = showError('لطفا نام و برچسب فیلد را وارد کنید.');
    } else {
        try {
            // Check if field name already exists for this network (excluding current field)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM social_network_fields 
                                  WHERE social_network_id = ? AND field_name = ? AND id != ?");
            $stmt->execute([$networkId, $fieldName, $fieldId]);
            $exists = $stmt->fetch()['count'] > 0;
            
            if ($exists) {
                $message = showError('این نام فیلد قبلاً برای این شبکه اجتماعی استفاده شده است.');
            } else {
                $stmt = $pdo->prepare("UPDATE social_network_fields 
                                      SET field_name = ?, field_label = ?, field_type = ?, 
                                      is_required = ?, is_kpi = ?, sort_order = ? 
                                      WHERE id = ? AND social_network_id = ?");
                $stmt->execute([$fieldName, $fieldLabel, $fieldType, $isRequired, $isKpi, $sortOrder, $fieldId, $networkId]);
                $message = showSuccess('فیلد با موفقیت ویرایش شد.');
            }
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش فیلد: ' . $e->getMessage());
        }
    }
}

// Delete field
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $fieldId = $_GET['delete'];
    
    // Check if field is being used in any social page
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM social_page_fields WHERE field_id = ?");
    $stmt->execute([$fieldId]);
    $usageCount = $stmt->fetch()['count'];
    
    if ($usageCount > 0) {
        $message = showError('این فیلد قابل حذف نیست زیرا در ' . $usageCount . ' صفحه استفاده شده است.');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM social_network_fields WHERE id = ? AND social_network_id = ?");
            $stmt->execute([$fieldId, $networkId]);
            $message = showSuccess('فیلد با موفقیت حذف شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در حذف فیلد: ' . $e->getMessage());
        }
    }
}

// Get all fields for this network
$fields = getSocialNetworkFields($networkId, $pdo);

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>مدیریت فیلدهای <?php echo $network['name']; ?></h1>
        <p class="text-muted">
            <i class="<?php echo $network['icon']; ?>"></i>
            تعریف فیلدهای اطلاعاتی برای صفحات این شبکه اجتماعی
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFieldModal">
            <i class="fas fa-plus"></i> افزودن فیلد جدید
        </button>
        <a href="social_networks.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت
        </a>
    </div>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($fields) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>نام فیلد</th>
                            <th>برچسب فیلد</th>
                            <th>نوع فیلد</th>
                            <th>اجباری</th>
                            <th>شاخص KPI</th>
                            <th>ترتیب</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <td><?php echo $field['field_name']; ?></td>
                                <td><?php echo $field['field_label']; ?></td>
                                <td>
                                    <?php
                                    switch ($field['field_type']) {
                                        case 'text': echo 'متن'; break;
                                        case 'number': echo 'عدد'; break;
                                        case 'date': echo 'تاریخ'; break;
                                        case 'url': echo 'آدرس اینترنتی'; break;
                                        default: echo $field['field_type'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($field['is_required']): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($field['is_kpi']): ?>
                                        <span class="badge bg-primary">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $field['sort_order']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editFieldModal" 
                                            data-id="<?php echo $field['id']; ?>"
                                            data-name="<?php echo $field['field_name']; ?>"
                                            data-label="<?php echo $field['field_label']; ?>"
                                            data-type="<?php echo $field['field_type']; ?>"
                                            data-required="<?php echo $field['is_required']; ?>"
                                            data-kpi="<?php echo $field['is_kpi']; ?>"
                                            data-order="<?php echo $field['sort_order']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <a href="?network=<?php echo $networkId; ?>&delete=<?php echo $field['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                هیچ فیلدی برای این شبکه اجتماعی تعریف نشده است. لطفاً فیلدهای مورد نیاز را اضافه کنید.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Field Modal -->
<div class="modal fade" id="addFieldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن فیلد جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="field_name" class="form-label">نام فیلد (انگلیسی)</label>
                        <input type="text" class="form-control" id="field_name" name="field_name" 
                               pattern="[a-zA-Z0-9_]+" required
                               placeholder="مثال: followers">
                        <div class="form-text">
                            فقط از حروف انگلیسی، اعداد و زیرخط استفاده کنید. این نام برای استفاده در کد و ذخیره در پایگاه داده استفاده می‌شود.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="field_label" class="form-label">برچسب فیلد (فارسی)</label>
                        <input type="text" class="form-control" id="field_label" name="field_label" required
                               placeholder="مثال: تعداد فالوور">
                        <div class="form-text">
                            این برچسب در فرم‌ها و گزارش‌ها نمایش داده می‌شود.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="field_type" class="form-label">نوع فیلد</label>
                        <select class="form-select" id="field_type" name="field_type" required>
                            <option value="text">متن</option>
                            <option value="number">عدد</option>
                            <option value="date">تاریخ</option>
                            <option value="url">آدرس اینترنتی</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_required" name="is_required">
                        <label class="form-check-label" for="is_required">فیلد اجباری است</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_kpi" name="is_kpi">
                        <label class="form-check-label" for="is_kpi">شاخص KPI است</label>
                        <div class="form-text">
                            شاخص‌های KPI فیلدهایی هستند که برای اندازه‌گیری عملکرد استفاده می‌شوند و در گزارش‌های عملکرد و محاسبه امتیاز شامل می‌شوند.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">ترتیب نمایش</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_field" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Field Modal -->
<div class="modal fade" id="editFieldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش فیلد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_field_id" name="field_id">
                    <div class="mb-3">
                        <label for="edit_field_name" class="form-label">نام فیلد (انگلیسی)</label>
                        <input type="text" class="form-control" id="edit_field_name" name="field_name" 
                               pattern="[a-zA-Z0-9_]+" required>
                        <div class="form-text">
                            فقط از حروف انگلیسی، اعداد و زیرخط استفاده کنید. این نام برای استفاده در کد و ذخیره در پایگاه داده استفاده می‌شود.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_field_label" class="form-label">برچسب فیلد (فارسی)</label>
                        <input type="text" class="form-control" id="edit_field_label" name="field_label" required>
                        <div class="form-text">
                            این برچسب در فرم‌ها و گزارش‌ها نمایش داده می‌شود.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_field_type" class="form-label">نوع فیلد</label>
                        <select class="form-select" id="edit_field_type" name="field_type" required>
                            <option value="text">متن</option>
                            <option value="number">عدد</option>
                            <option value="date">تاریخ</option>
                            <option value="url">آدرس اینترنتی</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_required" name="is_required">
                        <label class="form-check-label" for="edit_is_required">فیلد اجباری است</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_kpi" name="is_kpi">
                        <label class="form-check-label" for="edit_is_kpi">شاخص KPI است</label>
                        <div class="form-text">
                            شاخص‌های KPI فیلدهایی هستند که برای اندازه‌گیری عملکرد استفاده می‌شوند و در گزارش‌های عملکرد و محاسبه امتیاز شامل می‌شوند.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">ترتیب نمایش</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_field" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit field modal
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editFieldModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const label = button.getAttribute('data-label');
            const type = button.getAttribute('data-type');
            const required = button.getAttribute('data-required') === '1';
            const kpi = button.getAttribute('data-kpi') === '1';
            const order = button.getAttribute('data-order');
            
            document.getElementById('edit_field_id').value = id;
            document.getElementById('edit_field_name').value = name;
            document.getElementById('edit_field_label').value = label;
            document.getElementById('edit_field_type').value = type;
            document.getElementById('edit_is_required').checked = required;
            document.getElementById('edit_is_kpi').checked = kpi;
            document.getElementById('edit_sort_order').value = order;
        });
    }
});
</script>

<?php include 'footer.php'; ?>