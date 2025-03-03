<?php
// page_kpi.php - Manage KPIs for a specific social page
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get page ID from query string
$pageId = isset($_GET['page']) && is_numeric($_GET['page']) ? clean($_GET['page']) : null;

// If no page ID, redirect to social pages
if (!$pageId) {
    redirect('social_pages.php');
}

// Get page details
$page = getSocialPage($pageId, $pdo);
if (!$page) {
    redirect('social_pages.php');
}

// Check if user can access this page
if (!canAccessSocialPage($pageId, $pdo)) {
    redirect('social_pages.php');
}

$message = '';

// Add new KPI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kpi'])) {
    $fieldId = clean($_POST['field_id']);
    $modelId = clean($_POST['kpi_model_id']);
    $relatedFieldId = isset($_POST['related_field_id']) ? clean($_POST['related_field_id']) : null;
    $growthValue = isset($_POST['growth_value']) ? clean($_POST['growth_value']) : null;
    $growthPeriodDays = isset($_POST['growth_period_days']) ? clean($_POST['growth_period_days']) : null;
    $percentageValue = isset($_POST['percentage_value']) ? clean($_POST['percentage_value']) : null;
    
    if (empty($fieldId) || empty($modelId)) {
        $message = showError('لطفا فیلد و مدل KPI را انتخاب کنید.');
    } else {
        try {
            // Get model type
            $model = getKPIModel($modelId, $pdo);
            if (!$model) {
                throw new Exception('مدل KPI انتخاب شده معتبر نیست.');
            }
            
            // Validate based on model type
            if ($model['model_type'] == 'growth_over_time') {
                if (empty($growthValue) || empty($growthPeriodDays)) {
                    throw new Exception('لطفا مقدار رشد و دوره زمانی را وارد کنید.');
                }
                
                // Check if KPI already exists for this field
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_kpis 
                                      WHERE page_id = ? AND field_id = ? AND kpi_model_id = ?");
                $stmt->execute([$pageId, $fieldId, $modelId]);
                $exists = $stmt->fetch()['count'] > 0;
                
                if ($exists) {
                    // Update existing KPI
                    $stmt = $pdo->prepare("UPDATE page_kpis 
                                          SET growth_value = ?, growth_period_days = ? 
                                          WHERE page_id = ? AND field_id = ? AND kpi_model_id = ?");
                    $stmt->execute([$growthValue, $growthPeriodDays, $pageId, $fieldId, $modelId]);
                } else {
                    // Insert new KPI
                    $stmt = $pdo->prepare("INSERT INTO page_kpis 
                                          (page_id, field_id, kpi_model_id, growth_value, growth_period_days) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$pageId, $fieldId, $modelId, $growthValue, $growthPeriodDays]);
                }
                
                $message = showSuccess('KPI با موفقیت ذخیره شد.');
            } else if ($model['model_type'] == 'percentage_of_field') {
                if (empty($relatedFieldId) || empty($percentageValue)) {
                    throw new Exception('لطفا فیلد مرجع و درصد مورد نظر را وارد کنید.');
                }
                
                // Check if related field is different from main field
                if ($fieldId == $relatedFieldId) {
                    throw new Exception('فیلد مرجع باید با فیلد اصلی متفاوت باشد.');
                }
                
                // Check if KPI already exists for this field
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_kpis 
                                      WHERE page_id = ? AND field_id = ? AND kpi_model_id = ?");
                $stmt->execute([$pageId, $fieldId, $modelId]);
                $exists = $stmt->fetch()['count'] > 0;
                
                if ($exists) {
                    // Update existing KPI
                    $stmt = $pdo->prepare("UPDATE page_kpis 
                                          SET related_field_id = ?, percentage_value = ? 
                                          WHERE page_id = ? AND field_id = ? AND kpi_model_id = ?");
                    $stmt->execute([$relatedFieldId, $percentageValue, $pageId, $fieldId, $modelId]);
                } else {
                    // Insert new KPI
                    $stmt = $pdo->prepare("INSERT INTO page_kpis 
                                          (page_id, field_id, kpi_model_id, related_field_id, percentage_value) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$pageId, $fieldId, $modelId, $relatedFieldId, $percentageValue]);
                }
                
                $message = showSuccess('KPI با موفقیت ذخیره شد.');
            } else {
                throw new Exception('نوع مدل KPI نامعتبر است.');
            }
        } catch (Exception $e) {
            $message = showError('خطا در ثبت KPI: ' . $e->getMessage());
        }
    }
}

// Delete KPI
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $kpiId = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM page_kpis WHERE id = ? AND page_id = ?");
        $stmt->execute([$kpiId, $pageId]);
        $message = showSuccess('KPI با موفقیت حذف شد.');
    } catch (Exception $e) {
        $message = showError('خطا در حذف KPI: ' . $e->getMessage());
    }
}

// Get KPI models
$kpiModels = getAllKPIModels($pdo);

// Get fields for the network
$fields = getSocialNetworkFields($page['social_network_id'], $pdo);

// Filter only KPI fields
$kpiFields = array_filter($fields, function($field) {
    return $field['is_kpi'] == 1;
});

// Get existing KPIs for this page
$pageKPIs = getPageKPIs($pageId, $pdo);

// Get field values for this page
$fieldValues = getSocialPageFieldValues($pageId, $pdo);

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>مدیریت KPI های صفحه</h1>
        <p class="text-muted">
            <i class="<?php echo $page['network_icon']; ?>"></i>
            <?php echo $page['network_name']; ?> / <?php echo $page['page_name']; ?> / <?php echo $page['company_name']; ?>
        </p>
    </div>
    <div>
        <a href="social_pages.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست صفحات
        </a>
    </div>
</div>

<?php echo $message; ?>

<?php if (empty($kpiFields)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        هیچ فیلد KPI برای این شبکه اجتماعی تعریف نشده است.
        <?php if (isAdmin()): ?>
            <a href="social_network_fields.php?network=<?php echo $page['social_network_id']; ?>" class="alert-link">
                مدیریت فیلدها
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">افزودن KPI جدید</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="addKpiForm">
                        <div class="mb-3">
                            <label for="field_id" class="form-label">فیلد مورد نظر</label>
                            <select class="form-select" id="field_id" name="field_id" required>
                                <option value="">انتخاب فیلد...</option>
                                <?php foreach ($kpiFields as $field): ?>
                                    <option value="<?php echo $field['id']; ?>" data-field-name="<?php echo $field['field_name']; ?>">
                                        <?php echo $field['field_label']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kpi_model_id" class="form-label">مدل KPI</label>
                            <select class="form-select" id="kpi_model_id" name="kpi_model_id" required>
                                <option value="">انتخاب مدل...</option>
                                <?php foreach ($kpiModels as $model): ?>
                                    <option value="<?php echo $model['id']; ?>" data-model-type="<?php echo $model['model_type']; ?>">
                                        <?php echo $model['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="model_description"></div>
                        </div>
                        
                        <!-- Dynamic form fields based on selected model -->
                        <div id="model_fields">
                            <div class="alert alert-info">
                                لطفاً ابتدا فیلد و مدل KPI را انتخاب کنید.
                            </div>
                        </div>
                        
                        <button type="submit" name="add_kpi" class="btn btn-primary mt-3">
                            ذخیره KPI
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Current Field Values -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">مقادیر فعلی فیلدها</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>فیلد</th>
                                    <th>مقدار فعلی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fieldValues as $value): ?>
                                    <tr>
                                        <td><?php echo $value['field_label']; ?></td>
                                        <td><?php echo $value['field_value']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">KPI های تعریف شده</h5>
                </div>
                <div class="card-body">
                    <?php if (count($pageKPIs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>فیلد</th>
                                        <th>مدل KPI</th>
                                        <th>جزئیات</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pageKPIs as $kpi): ?>
                                        <tr>
                                            <td><?php echo $kpi['field_label']; ?></td>
                                            <td><?php echo $kpi['model_name']; ?></td>
                                            <td>
                                                <?php if ($kpi['model_type'] == 'growth_over_time'): ?>
                                                    انتظار رشد 
                                                    <?php 
                                                        if ($kpi['growth_value'] < 100) {
                                                            echo $kpi['growth_value'] . '% '; 
                                                        } else {
                                                            echo $kpi['growth_value'] . ' واحد ';
                                                        }
                                                    ?>
                                                    هر <?php echo $kpi['growth_period_days']; ?> روز
                                                <?php elseif ($kpi['model_type'] == 'percentage_of_field'): ?>
                                                    <?php echo $kpi['percentage_value']; ?>% از فیلد "<?php echo $kpi['related_field_label']; ?>"
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?page=<?php echo $pageId; ?>&delete=<?php echo $kpi['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('آیا از حذف این KPI اطمینان دارید؟')">
                                                    <i class="fas fa-trash"></i> حذف
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            هیچ KPI برای این صفحه تعریف نشده است.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- KPI Explanation -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">راهنمای مدل‌های KPI</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="kpiAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    مدل رشد زمانی (Growth Over Time)
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#kpiAccordion">
                                <div class="accordion-body">
                                    <p>در این مدل، انتظار داریم یک فیلد (مثلاً تعداد فالوورها) در طول زمان با نرخ مشخصی رشد کند.</p>
                                    <ul>
                                        <li>
                                            <strong>مقدار رشد:</strong> می‌تواند به دو صورت تعریف شود:
                                            <ul>
                                                <li><strong>عدد کمتر از 100:</strong> به عنوان درصد رشد تفسیر می‌شود. مثلاً 10% رشد.</li>
                                                <li><strong>عدد بزرگتر از 100:</strong> به عنوان مقدار مطلق رشد تفسیر می‌شود. مثلاً 500 فالوور جدید.</li>
                                            </ul>
                                        </li>
                                        <li><strong>دوره زمانی:</strong> تعداد روزهایی که انتظار داریم این رشد در آن اتفاق بیفتد. مثلاً هر 30 روز.</li>
                                    </ul>
                                    <p><strong>مثال:</strong> انتظار داریم تعداد فالوورهای اینستاگرام هر 30 روز 10% رشد کند یا هر 30 روز 500 فالوور جدید اضافه شود.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    مدل درصدی از فیلد دیگر (Percentage of Field)
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#kpiAccordion">
                                <div class="accordion-body">
                                    <p>در این مدل، انتظار داریم مقدار یک فیلد، درصد مشخصی از مقدار فیلد دیگری باشد.</p>
                                    <ul>
                                        <li><strong>فیلد مرجع:</strong> فیلدی که می‌خواهیم درصدی از آن را محاسبه کنیم.</li>
                                        <li><strong>درصد:</strong> درصد مورد نظر از فیلد مرجع.</li>
                                    </ul>
                                    <p><strong>مثال:</strong> انتظار داریم تعداد لایک‌ها، 5% از تعداد فالوورها باشد یا تعداد مشتریان، 2% از تعداد بازدیدکنندگان باشد.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldSelect = document.getElementById('field_id');
    const modelSelect = document.getElementById('kpi_model_id');
    const modelFieldsDiv = document.getElementById('model_fields');
    const modelDescriptionDiv = document.getElementById('model_description');
    
    // Get model descriptions
    const modelDescriptions = {
        <?php foreach ($kpiModels as $model): ?>
            <?php echo $model['id']; ?>: '<?php echo addslashes($model['description']); ?>',
        <?php endforeach; ?>
    };
    
    // Function to update model fields based on selected model
    function updateModelFields() {
        const fieldId = fieldSelect.value;
        const modelId = modelSelect.value;
        const fieldName = fieldSelect.options[fieldSelect.selectedIndex]?.dataset.fieldName || '';
        
        // Show description for selected model
        modelDescriptionDiv.textContent = modelId ? modelDescriptions[modelId] : '';
        
        if (!fieldId || !modelId) {
            modelFieldsDiv.innerHTML = `
                <div class="alert alert-info">
                    لطفاً ابتدا فیلد و مدل KPI را انتخاب کنید.
                </div>
            `;
            return;
        }
        
        const modelType = modelSelect.options[modelSelect.selectedIndex].dataset.modelType;
        
        if (modelType === 'growth_over_time') {
            modelFieldsDiv.innerHTML = `
                <div class="mb-3">
                    <label for="growth_value" class="form-label">مقدار رشد</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control" id="growth_value" name="growth_value" required>
                        <span class="input-group-text">عدد یا درصد</span>
                    </div>
                    <div class="form-text">
                        اگر کمتر از 100 باشد، به عنوان درصد رشد در نظر گرفته می‌شود. در غیر این صورت، مقدار مطلق رشد محسوب می‌شود.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="growth_period_days" class="form-label">دوره زمانی (روز)</label>
                    <input type="number" min="1" class="form-control" id="growth_period_days" name="growth_period_days" required>
                    <div class="form-text">
                        تعداد روزهایی که انتظار دارید این رشد در آن اتفاق بیفتد.
                    </div>
                </div>
            `;
        } else if (modelType === 'percentage_of_field') {
            // Create options for related field select (excluding current field)
            let relatedFieldOptions = '<option value="">انتخاب فیلد مرجع...</option>';
            
            <?php foreach ($kpiFields as $field): ?>
                relatedFieldOptions += `
                    <option value="<?php echo $field['id']; ?>" ${<?php echo $field['id']; ?> == fieldId ? 'disabled' : ''}>
                        <?php echo $field['field_label']; ?>
                    </option>
                `;
            <?php endforeach; ?>
            
            modelFieldsDiv.innerHTML = `
                <div class="mb-3">
                    <label for="related_field_id" class="form-label">فیلد مرجع</label>
                    <select class="form-select" id="related_field_id" name="related_field_id" required>
                        ${relatedFieldOptions}
                    </select>
                    <div class="form-text">
                        فیلدی که می‌خواهید درصدی از آن را محاسبه کنید.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="percentage_value" class="form-label">درصد مورد نظر</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="percentage_value" name="percentage_value" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            `;
        }
    }
    
    // Update fields when model or field changes
    fieldSelect.addEventListener('change', updateModelFields);
    modelSelect.addEventListener('change', updateModelFields);
});
</script>

<?php include 'footer.php'; ?>