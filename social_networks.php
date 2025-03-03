<?php
// social_networks.php - Manage social networks
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check admin access
requireAdmin();

$message = '';

// Add new social network
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_network'])) {
    $name = clean($_POST['name']);
    $icon = clean($_POST['icon']);
    
    if (empty($name) || empty($icon)) {
        $message = showError('لطفا نام و آیکون شبکه اجتماعی را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO social_networks (name, icon) VALUES (?, ?)");
            $stmt->execute([$name, $icon]);
            $message = showSuccess('شبکه اجتماعی با موفقیت اضافه شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت شبکه اجتماعی: ' . $e->getMessage());
        }
    }
}

// Delete social network
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $networkId = $_GET['delete'];
    
    // Check if network has any pages
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM social_pages WHERE social_network_id = ?");
    $stmt->execute([$networkId]);
    $pagesCount = $stmt->fetch()['count'];
    
    if ($pagesCount > 0) {
        $message = showError('این شبکه اجتماعی قابل حذف نیست زیرا دارای ' . $pagesCount . ' صفحه می‌باشد.');
    } else {
        try {
            // Delete fields first
            $stmt = $pdo->prepare("DELETE FROM social_network_fields WHERE social_network_id = ?");
            $stmt->execute([$networkId]);
            
            // Then delete the network
            $stmt = $pdo->prepare("DELETE FROM social_networks WHERE id = ?");
            $stmt->execute([$networkId]);
            
            $message = showSuccess('شبکه اجتماعی با موفقیت حذف شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در حذف شبکه اجتماعی: ' . $e->getMessage());
        }
    }
}

// Edit social network
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_network'])) {
    $networkId = clean($_POST['network_id']);
    $name = clean($_POST['name']);
    $icon = clean($_POST['icon']);
    
    if (empty($name) || empty($icon)) {
        $message = showError('لطفا نام و آیکون شبکه اجتماعی را وارد کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE social_networks SET name = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $networkId]);
            $message = showSuccess('شبکه اجتماعی با موفقیت ویرایش شد.');
        } catch (PDOException $e) {
            $message = showError('خطا در ویرایش شبکه اجتماعی: ' . $e->getMessage());
        }
    }
}

// Get all social networks with counts
$stmt = $pdo->query("SELECT n.*, 
                    (SELECT COUNT(*) FROM social_network_fields WHERE social_network_id = n.id) as field_count,
                    (SELECT COUNT(*) FROM social_pages WHERE social_network_id = n.id) as page_count 
                    FROM social_networks n ORDER BY n.name");
$networks = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت شبکه‌های اجتماعی</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNetworkModal">
        <i class="fas fa-plus"></i> افزودن شبکه اجتماعی جدید
    </button>
</div>

<?php echo $message; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($networks) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>آیکون</th>
                            <th>نام شبکه اجتماعی</th>
                            <th>تعداد فیلدها</th>
                            <th>تعداد صفحات</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($networks as $network): ?>
                            <tr>
                                <td><i class="<?php echo $network['icon']; ?>"></i></td>
                                <td><?php echo $network['name']; ?></td>
                                <td>
                                    <?php echo $network['field_count']; ?>
                                    <a href="social_network_fields.php?network=<?php echo $network['id']; ?>" class="btn btn-sm btn-info ms-2">
                                        <i class="fas fa-list-ul"></i> مدیریت فیلدها
                                    </a>
                                </td>
                                <td><?php echo $network['page_count']; ?></td>
                                <td><?php echo $network['created_at']; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editNetworkModal" 
                                            data-id="<?php echo $network['id']; ?>"
                                            data-name="<?php echo $network['name']; ?>"
                                            data-icon="<?php echo $network['icon']; ?>">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </button>
                                    
                                    <?php if ($network['page_count'] == 0): ?>
                                        <a href="?delete=<?php echo $network['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('آیا از حذف این شبکه اجتماعی اطمینان دارید؟')">
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
            <p class="text-center">هیچ شبکه اجتماعی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Network Modal -->
<div class="modal fade" id="addNetworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن شبکه اجتماعی جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">نام شبکه اجتماعی</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="icon" class="form-label">آیکون (Font Awesome)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fab fa-instagram" required>
                        </div>
                        <div class="form-text">
                            می‌توانید از <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">اینجا</a> آیکون‌های Font Awesome را انتخاب کنید.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_network" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Network Modal -->
<div class="modal fade" id="editNetworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ویرایش شبکه اجتماعی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_network_id" name="network_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">نام شبکه اجتماعی</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">آیکون (Font Awesome)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i id="icon_preview"></i></span>
                            <input type="text" class="form-control" id="edit_icon" name="icon" required>
                        </div>
                        <div class="form-text">
                            می‌توانید از <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank">اینجا</a> آیکون‌های Font Awesome را انتخاب کنید.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_network" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit network modal
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editNetworkModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const icon = button.getAttribute('data-icon');
            
            document.getElementById('edit_network_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_icon').value = icon;
            
            // Update icon preview
            const iconPreview = document.getElementById('icon_preview');
            iconPreview.className = icon;
        });
    }
    
    // Update icon preview on input change
    const editIconInput = document.getElementById('edit_icon');
    if (editIconInput) {
        editIconInput.addEventListener('input', function() {
            const iconPreview = document.getElementById('icon_preview');
            iconPreview.className = this.value;
        });
    }
    
    // Update icon preview on input change for add modal
    const addIconInput = document.getElementById('icon');
    if (addIconInput) {
        const addIconPreview = addIconInput.previousElementSibling.firstElementChild;
        addIconInput.addEventListener('input', function() {
            addIconPreview.className = this.value;
        });
    }
});
</script>

<?php include 'footer.php'; ?>