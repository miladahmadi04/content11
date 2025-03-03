<?php
// expected_performance.php - Show expected performance over time based on KPIs
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

// Get KPIs for this page
$pageKPIs = getPageKPIs($pageId, $pdo);

// Get field values for this page (initial values)
$fieldValues = getSocialPageFieldValues($pageId, $pdo);

// Calculate expected values for several time points (monthly for a year)
$timePoints = [];
$currentDate = new DateTime($page['start_date']);
$endDate = clone $currentDate;
$endDate->modify('+12 months');

$expectedDataPoints = [];
$expectedData = [];

// Initialize data structure for each KPI field
foreach ($pageKPIs as $kpi) {
    $expectedDataPoints[$kpi['field_id']] = [];
    $expectedData[$kpi['field_id']] = [
        'label' => $kpi['field_label'],
        'model' => $kpi['model_name'],
        'values' => []
    ];
}

// Generate time points (monthly for the first year)
while ($currentDate <= $endDate) {
    $timePoint = $currentDate->format('Y-m-d');
    $timePoints[] = $timePoint;
    
    // Calculate expected values for each KPI at this time point
    $expectedValues = calculateExpectedValues($pageId, $timePoint, $pdo);
    
    foreach ($pageKPIs as $kpi) {
        if (isset($expectedValues[$kpi['field_id']])) {
            $expectedValue = $expectedValues[$kpi['field_id']]['expected_value'];
            
            // Store data point
            $expectedDataPoints[$kpi['field_id']][] = [
                'date' => $timePoint,
                'value' => $expectedValue
            ];
            
            // Store for table display
            $expectedData[$kpi['field_id']]['values'][$timePoint] = $expectedValue;
        }
    }
    
    // Move to next month
    $currentDate->modify('+1 month');
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>عملکرد مورد انتظار صفحه</h1>
        <p class="text-muted">
            <i class="<?php echo $page['network_icon']; ?>"></i>
            <?php echo $page['network_name']; ?> / <?php echo $page['page_name']; ?> / <?php echo $page['company_name']; ?>
        </p>
    </div>
    <div>
        <a href="page_kpi.php?page=<?php echo $pageId; ?>" class="btn btn-primary">
            <i class="fas fa-cog"></i> مدیریت KPI ها
        </a>
        <a href="social_report.php?page=<?php echo $pageId; ?>" class="btn btn-success">
            <i class="fas fa-file-alt"></i> گزارش‌ها
        </a>
        <a href="social_pages.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست صفحات
        </a>
    </div>
</div>

<?php if (empty($pageKPIs)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        هیچ KPI برای این صفحه تعریف نشده است. برای مشاهده عملکرد مورد انتظار، ابتدا باید KPI ها را تعریف کنید.
        <a href="page_kpi.php?page=<?php echo $pageId; ?>" class="alert-link">
            تعریف KPI
        </a>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">اطلاعات اولیه صفحه</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>مشخصات صفحه</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>نام صفحه:</th>
                                    <td><?php echo $page['page_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>آدرس صفحه:</th>
                                    <td>
                                        <a href="<?php echo $page['page_url']; ?>" target="_blank">
                                            <?php echo $page['page_url']; ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>تاریخ شروع:</th>
                                    <td><?php echo $page['start_date']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>مقادیر اولیه</h6>
                            <table class="table table-sm">
                                <?php foreach ($fieldValues as $value): ?>
                                    <tr>
                                        <th><?php echo $value['field_label']; ?>:</th>
                                        <td>
                                            <?php echo $value['field_value']; ?>
                                            <?php if ($value['is_kpi']): ?>
                                                <span class="badge bg-primary">KPI</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">KPI های تعریف شده</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>فیلد</th>
                                    <th>مدل KPI</th>
                                    <th>جزئیات</th>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">پیش‌بینی عملکرد (۱۲ ماه آینده)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div id="chart"></div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>فیلد</th>
                                    <?php foreach ($timePoints as $timePoint): ?>
                                        <th><?php echo date('Y/m', strtotime($timePoint)); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expectedData as $fieldId => $data): ?>
                                    <tr>
                                        <td><?php echo $data['label']; ?></td>
                                        <?php foreach ($timePoints as $timePoint): ?>
                                            <td>
                                                <?php 
                                                    echo isset($data['values'][$timePoint]) ? 
                                                        number_format($data['values'][$timePoint], 0) : 
                                                        '-'; 
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for ApexCharts
        const series = [
            <?php foreach ($expectedDataPoints as $fieldId => $dataPoints): ?>
            {
                name: '<?php 
                    $fieldLabel = '';
                    foreach ($pageKPIs as $kpi) {
                        if ($kpi['field_id'] == $fieldId) {
                            $fieldLabel = $kpi['field_label'];
                            break;
                        }
                    }
                    echo $fieldLabel;
                ?>',
                data: [
                    <?php foreach ($dataPoints as $point): ?>
                    {
                        x: new Date('<?php echo $point['date']; ?>').getTime(),
                        y: <?php echo $point['value']; ?>
                    },
                    <?php endforeach; ?>
                ]
            },
            <?php endforeach; ?>
        ];
        
        // Chart options
        const options = {
            series: series,
            chart: {
                height: 400,
                type: 'line',
                zoom: {
                    enabled: true
                },
                fontFamily: 'Tahoma, Arial, sans-serif',
                dir: 'rtl'
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'پیش‌بینی عملکرد صفحه در ۱۲ ماه آینده',
                align: 'center',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    format: 'yyyy/MM',
                    style: {
                        direction: 'ltr'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'مقدار'
                }
            },
            tooltip: {
                x: {
                    format: 'yyyy/MM/dd'
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            },
            markers: {
                size: 5
            }
        };
        
        // Initialize chart
        const chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();
    });
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>