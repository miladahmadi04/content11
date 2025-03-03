<?php
// personnel.php - Manage personnel
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check admin access
requireAdmin();

$message = '';
$filterCompany = isset($_GET['company']) && is_numeric($_GET['company']) ? $_GET['company'] : null;

// Add new personnel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_personnel'])) {
    $company_id = clean($_POST['company_id']);
    $role_id = clean($_POST['role_id']);
    $full_name = clean($_POST['full_name']);
    $gender = clean($_POST['gender']);
    $email = clean($_POST['email']);
    $mobile = clean($_POST['mobile']);
    
    // Generate username and password
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $full_name)) . rand(100, 999);
    $password = generateRandomPassword();
    $hashed_password = generateHash($password);
    
    if (empty($company_id) || empty($role_id) || empty($full_name) || empty($gender) || empty($email) || empty($mobile)) {
        $message = showError('لطفا تمام فیلدها را پر کنید.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO personnel (company_id, role_id, full_name, gender, email, mobile, username, password) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company_id, $role_id, $full_name, $gender, $email, $mobile, $username, $hashed_password]);
            $message = showSuccess("پرسنل با موفقیت اضافه شد. نام کاربری: $username | رمز عبور: $password");
        } catch (PDOException $e) {
            $message = showError('خطا در ثبت پرسنل: ' . $e->getMessage());
        }
    }
}

// Toggle personnel status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $personnelId = $_GET['toggle'];
    
    // Get current status
    $stmt = $pdo->prepare("SELECT is_active FROM personnel WHERE id = ?");
    $stmt->execute([$personnelId]);
    $personnel = $stmt->fetch();
    
    if ($personnel) {
        $newStatus = $personnel['is_active'] ? 0 : 1;
        
        try {
            $stmt = $pdo->prepare("UPDATE personnel SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $personnelId]);
            $message = showSuccess('وضعیت پرسنل با موفقیت تغییر کرد.');
        } catch (PDOException $e) {
            $message = showError('خطا در تغییر وضعیت پرسنل: ' . $e->getMessage());
        }
    }
}

// Reset password
if (isset($_GET['reset']) && is_numeric($_GET['reset'])) {
    $personnelId = $_GET['reset'];
    
    // Generate new password
    $newPassword = generateRandomPassword();
    $hashedPassword = generateHash($newPassword);
    
    try {
        $stmt = $pdo->prepare("UPDATE personnel SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $personnelId]);
        $message = showSuccess("رمز عبور با موفقیت بازنشانی شد. رمز عبور جدید: $newPassword");
    } catch (PDOException $e) {
        $message = showError('خطا در بازنشانی رمز عبور: ' . $e->getMessage());
    }
}

// Get all companies for the form
$stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
$companies = $stmt->fetchAll();

// Get all roles for the form
$stmt = $pdo->query("SELECT * FROM roles ORDER BY name");
$roles = $stmt->fetchAll();

// Get personnel with company and role information
$query = "SELECT p.*, c.name as company_name, r.name as role_name 
         FROM personnel p 
         JOIN companies c ON p.company_id = c.id 
         JOIN roles r ON p.role_id = r.id";

if ($filterCompany) {
    $query .= " WHERE p.company_id = $filterCompany";
}

$query .= " ORDER BY p.full_name";
$stmt = $pdo->query($query);
$personnelList = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت پرسنل</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPersonnelModal">
        <i class="fas fa-plus"></i> افزودن پرسنل جدید
    </button>
</div>

<?php echo $message; ?>

<?php if ($filterCompany): ?>
    <?php 
        $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$filterCompany]);
        $companyName = $stmt->fetch()['name'];
    ?>
    <div class="alert alert-info">
        در حال نمایش پرسنل شرکت: <?php echo $companyName; ?>
        <a href="personnel.php" class="btn btn-sm btn-outline-primary ms-2">نمایش همه</a>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (count($personnelList) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام و نام خانوادگی</th>
                            <th>شرکت</th>
                            <th>نقش</th>
                            <th>جنسیت</th>
                            <th>ایمیل</th>
                            <th>موبایل</th>
                            <th>نام کاربری</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personnelList as $index => $person): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $person['full_name']; ?></td>
                                <td><?php echo $person['company_name']; ?></td>
                                <td><?php echo $person['role_name']; ?></td>
                                <td><?php echo $person['gender'] == 'male' ? 'مرد' : 'زن'; ?></td>
                                <td><?php echo $person['email']; ?></td>
                                <td><?php echo $person['mobile']; ?></td>
                                <td><?php echo $person['username']; ?></td>
                                <td>
                                    <?php if ($person['is_active']): ?>
                                        <span class="badge bg-success">فعال</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">غیرفعال</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?toggle=<?php echo $person['id']; ?>" class="btn btn-sm 
                                        <?php echo $person['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <?php echo $person['is_active'] ? 'غیرفعال کردن' : 'فعال کردن'; ?>
                                    </a>
                                    <a href="?reset=<?php echo $person['id']; ?>" class="btn btn-sm btn-info"
                                       onclick="return confirm('آیا از بازنشانی رمز عبور اطمینان دارید؟')">
                                        بازنشانی رمز
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center">هیچ پرسنلی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Personnel Modal -->
<div class="modal fade" id="addPersonnelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن پرسنل جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label">شرکت</label>
                        <div class="col-md-9">
                            <?php foreach ($companies as $company): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="company_id" id="company_<?php echo $company['id']; ?>" value="<?php echo $company['id']; ?>" required>
                                    <label class="form-check-label" for="company_<?php echo $company['id']; ?>">
                                        <?php echo $company['name']; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="role_id" class="col-md-3 col-form-label">نقش کاربری</label>
                        <div class="col-md-9">
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="full_name" class="col-md-3 col-form-label">نام و نام خانوادگی</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label">جنسیت</label>
                        <div class="col-md-9">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" required>
                                <label class="form-check-label" for="gender_male">مرد</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female">
                                <label class="form-check-label" for="gender_female">زن</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="email" class="col-md-3 col-form-label">ایمیل</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <label for="mobile" class="col-md-3 col-form-label">موبایل</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="mobile" name="mobile" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>نام کاربری و رمز عبور به صورت خودکار تولید و نمایش داده خواهد شد.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_personnel" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>