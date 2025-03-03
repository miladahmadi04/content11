<?php
// content_management.php - صفحه اصلی مدیریت محتوا
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// بررسی دسترسی کاربر
if (!isLoggedIn()) {
    redirect('login.php');
}

// دریافت اطلاعات شرکت کاربر
$companyId = isAdmin() ? 
    (isset($_GET['company']) && is_numeric($_GET['company']) ? clean($_GET['company']) : null) : 
    $_SESSION['company_id'];

// اگر ادمین است و شرکتی انتخاب نشده، لیست شرکت‌ها را نمایش می‌دهیم
if (isAdmin() && empty($companyId)) {
    // دریافت لیست شرکت‌ها
    $stmt = $pdo->query("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
    $companies = $stmt->fetchAll();
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>مدیریت محتوا</h1>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به صفحه اصلی
        </a>
    </div>
</div>

<?php if (isAdmin() && empty($companyId)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        لطفا ابتدا شرکت مورد نظر را انتخاب کنید.
    </div>
    
    <div class="row">
        <?php foreach ($companies as $company): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $company['name']; ?></h5>
                        <p class="card-text">مدیریت محتوای این شرکت</p>
                        <a href="?company=<?php echo $company['id']; ?>" class="btn btn-primary">انتخاب</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php 
        // دریافت نام شرکت
        $companyName = '';
        if ($companyId) {
            $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
            $stmt->execute([$companyId]);
            $company = $stmt->fetch();
            if ($company) {
                $companyName = $company['name'];
            }
        }
    ?>

    <div class="alert alert-info">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle"></i> 
                شما در حال مدیریت محتوای شرکت <strong><?php echo $companyName; ?></strong> هستید.
            </div>
            <?php if (isAdmin()): ?>
                <a href="content_management.php" class="btn btn-sm btn-outline-primary">تغییر شرکت</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <!-- بخش ثبت محتوا -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">محتوا</h5>
                </div>
                <div class="card-body">
                    <p>ثبت و مدیریت محتوای جدید</p>
                    <div class="d-grid gap-2">
                        <a href="content_add.php?company=<?php echo $companyId; ?>" class="btn btn-primary">ثبت محتوای جدید</a>
                        <a href="content_list.php?company=<?php echo $companyId; ?>" class="btn btn-outline-primary">لیست محتواها</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش تقویم محتوایی -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">تقویم محتوایی</h5>
                </div>
                <div class="card-body">
                    <p>مشاهده و مدیریت تقویم محتوایی</p>
                    <div class="d-grid gap-2">
                        <a href="content_calendar.php?company=<?php echo $companyId; ?>&view=month" class="btn btn-success">تقویم ماهانه</a>
                        <a href="content_calendar.php?company=<?php echo $companyId; ?>&view=week" class="btn btn-outline-success">تقویم هفتگی</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش قالب‌های محتوایی -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">قالب‌های محتوایی</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت قالب‌های محتوایی</p>
                    <div class="d-grid gap-2">
                        <a href="content_templates.php?company=<?php echo $companyId; ?>" class="btn btn-info">قالب‌های محتوایی</a>
                        <a href="content_template_add.php?company=<?php echo $companyId; ?>" class="btn btn-outline-info">ثبت قالب جدید</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-3">تنظیمات محتوا</h3>

    <div class="row mb-4">
        <!-- بخش موضوعات کلی -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">موضوعات کلی</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت موضوعات کلی محتوا</p>
                    <div class="d-grid">
                        <a href="content_topics.php?company=<?php echo $companyId; ?>" class="btn btn-outline-primary">مدیریت موضوعات</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش مخاطبین هدف -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">مخاطبین هدف</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت مخاطبین هدف محتوا</p>
                    <div class="d-grid">
                        <a href="content_audiences.php?company=<?php echo $companyId; ?>" class="btn btn-outline-primary">مدیریت مخاطبین</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش پلتفرم‌های انتشار -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">پلتفرم‌های انتشار</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت پلتفرم‌های انتشار محتوا</p>
                    <div class="d-grid">
                        <a href="content_platforms.php?company=<?php echo $companyId; ?>" class="btn btn-outline-primary">مدیریت پلتفرم‌ها</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش انواع محتوا -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">انواع محتوا</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت انواع محتوا</p>
                    <div class="d-grid">
                        <a href="content_types.php?company=<?php echo $companyId; ?>" class="btn btn-outline-primary">مدیریت انواع</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- بخش وظایف -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">وظایف</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت وظایف محتوایی</p>
                    <div class="d-grid">
                        <a href="content_tasks.php?company=<?php echo $companyId; ?>" class="btn btn-outline-warning">مدیریت وظایف</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش وضعیت‌های تولید -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">وضعیت‌های تولید</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت وضعیت‌های تولید محتوا</p>
                    <div class="d-grid">
                        <a href="content_production_statuses.php?company=<?php echo $companyId; ?>" class="btn btn-outline-warning">مدیریت وضعیت‌ها</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش وضعیت‌های انتشار -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">وضعیت‌های انتشار</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت وضعیت‌های انتشار محتوا</p>
                    <div class="d-grid">
                        <a href="content_publish_statuses.php?company=<?php echo $companyId; ?>" class="btn btn-outline-warning">مدیریت وضعیت‌ها</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- بخش فرمت‌های محتوایی -->
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">فرمت‌های محتوایی</h5>
                </div>
                <div class="card-body">
                    <p>مدیریت فرمت‌های محتوایی</p>
                    <div class="d-grid">
                        <a href="content_formats.php?company=<?php echo $companyId; ?>" class="btn btn-outline-warning">مدیریت فرمت‌ها</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش تنظیمات تقویم -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">تنظیمات تقویم محتوایی</h5>
                </div>
                <div class="card-body">
                    <p>تنظیم فیلدهای نمایش داده شده در تقویم محتوایی</p>
                    <a href="content_calendar_settings.php?company=<?php echo $companyId; ?>" class="btn btn-outline-secondary">تنظیمات تقویم</a>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include 'footer.php'; ?>