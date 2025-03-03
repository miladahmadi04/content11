<?php
// view_social_report.php - View a specific report with performance scores
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get report ID from query string
$reportId = isset($_GET['report']) && is_numeric($_GET['report']) ? clean($_GET['report']) : null;

// If no report ID, redirect to social pages
if (!$reportId) {
    redirect('social_pages.php');
}

// Get report details
$stmt = $pdo->prepare("SELECT r.*, p.id as page_id, p.page_name, p.page_url, p.start_date,
                     s.name as network_name, s.icon as network_icon, s.id as network_id,
                     c.name as company_name,
                     creator.username as creator_username,
                     personnel.full_name as creator_fullname
                     FROM monthly_reports r
                     JOIN social_pages p ON r.page_id = p.id
                     JOIN social_networks s ON p.social_network_id = s.id
                     JOIN companies c ON p.company_id = c.id
                     LEFT JOIN admin_users creator ON r.creator_id = creator.id
                     LEFT JOIN personnel ON r.creator_id = personnel.id
                     WHERE r.id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    redirect('social_pages.php');
}

// Check if user can access this report
if (!canAccessSocialPage($report['page_id'], $pdo)) {
    redirect('social_pages.php');
}

// Get report field values
$stmt = $pdo->prepare("SELECT v.*, f.field_label, f.field_type, f.is_kpi, f.field_name
                      FROM monthly_report_values v
                      JOIN social_network_fields f ON v.field_id = f.id
                      WHERE v.report_id = ?
                      ORDER BY f.sort_order, f.id");
$stmt->execute([$reportId]);
$fieldValues = $stmt->fetchAll();

// Get page KPIs
$pageKPIs = getPageKPIs($report['page_id'], $pdo);

// Calculate expected values for KPIs using the same method as expected_performance.php
$expectedValues = calculateExpectedValues($report['page_id'], $report['report_date'], $pdo);

// Create a structure to hold scores
$scores = [];
$overallScore = 0;
$scoreCount = 0;

// Calculate scores manually based on actual and expected values
foreach ($fieldValues as $value) {
    if ($value['is_kpi'] == 1 && isset($expectedValues[$value['field_id']])) {
        $expectedValue = $expectedValues[$value['field_id']]['expected_value'];
        $actualValue = floatval($value['field_value']);
        
        // Calculate performance score
        $score = calculatePerformanceScore($actualValue, $expectedValue);
        
        // Store score information
        $scores[] = [
            'field_id' => $value['field_id'],
            'field_label' => $value['field_label'],
            'actual_value' => $actualValue,
            'expected_value' => $expectedValue,
            'score' => $score
        ];
        
        $overallScore += $score;
        $scoreCount++;
    }
}

// Calculate overall score
if ($scoreCount > 0) {
    $overallScore = round($overallScore / $scoreCount, 1);
} else {
    $overallScore = 0;
}

// Get performance level
$performanceLevel = getPerformanceLevel($overallScore);

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>مشاهده گزارش عملکرد</h1>
        <p class="text-muted">
            <i class="<?php echo $report['network_icon']; ?>"></i>
            <?php echo $report['network_name']; ?> / <?php echo $report['page_name']; ?> / <?php echo $report['company_name']; ?>
        </p>
    </div>
    <div>
        <?php if (isAdmin() || (isset($report['creator_id']) && $report['creator_id'] == $_SESSION['user_id'])): ?>
            <a href="social_report.php?page=<?php echo $report['page_id']; ?>&edit=<?php echo $reportId; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> ویرایش گزارش
            </a>
        <?php endif; ?>
        <a href="expected_performance.php?page=<?php echo $report['page_id']; ?>" class="btn btn-info">
            <i class="fas fa-chart-line"></i> عملکرد مورد انتظار
        </a>
        <a href="social_report.php?page=<?php echo $report['page_id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> بازگشت به لیست گزارش‌ها
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between">
                    <h5 class="mb-0">اطلاعات گزارش</h5>
                    <span>تاریخ گزارش: <?php echo $report['report_date']; ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-3">اطلاعات صفحه</h6>
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th>نام صفحه</th>
                                <td><?php echo $report['page_name']; ?></td>
                            </tr>
                            <tr>
                                <th>آدرس صفحه</th>
                                <td>
                                    <a href="<?php echo $report['page_url']; ?>" target="_blank">
                                        <?php echo $report['page_url']; ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>شبکه اجتماعی</th>
                                <td>
                                    <i class="<?php echo $report['network_icon']; ?>"></i>
                                    <?php echo $report['network_name']; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>شرکت</th>
                                <td><?php echo $report['company_name']; ?></td>
                            </tr>
                            <tr>
                                <th>ثبت کننده</th>
                                <td>
                                    <?php 
                                        if ($report['creator_username']) {
                                            echo $report['creator_username'] . ' (مدیر)';
                                        } elseif ($report['creator_fullname']) {
                                            echo $report['creator_fullname'];
                                        } else {
                                            echo 'نامشخص';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>تاریخ ثبت</th>
                                <td><?php echo $report['created_at']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">امتیاز عملکرد</h6>
                        <div class="text-center mb-3">
                            <div class="display-1 fw-bold text-<?php echo $performanceLevel['class']; ?>">
                                <?php echo number_format($overallScore, 1); ?>
                            </div>
                            <div class="text-muted mb-2">از 7</div>
                            <div class="badge bg-<?php echo $performanceLevel['class']; ?> p-2 fs-6">
                                عملکرد <?php echo $performanceLevel['level']; ?>
                            </div>
                        </div>
                        <div class="progress" style="height: 30px;">
                            <?php
                                $percentage = ($overallScore / 7) * 100;
                                echo '<div class="progress-bar bg-' . $performanceLevel['class'] . '" role="progressbar" 
                                      style="width: ' . $percentage . '%" 
                                      aria-valuenow="' . $overallScore . '" aria-valuemin="0" aria-valuemax="7">';
                                echo $performanceLevel['level'] . ' - ' . number_format($percentage, 1) . '%';
                                echo '</div>';
                            ?>
                        </div>
                        <div id="achievement-chart" class="mt-3" style="height: 250px;"></div>
                    </div>
                </div>
                
                <h5 class="mb-3">مقادیر فیلدها و عملکرد</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>فیلد</th>
                                <th>مقدار واقعی</th>
                                <th>مقدار مورد انتظار</th>
                                <th>درصد تحقق</th>
                                <th>امتیاز (از 7)</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fieldValues as $value): ?>
                                <tr>
                                    <td>
                                        <?php echo $value['field_label']; ?>
                                        <?php if ($value['is_kpi']): ?>
                                            <span class="badge bg-primary">KPI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $value['field_value']; ?></td>
                                    <td>
                                        <?php 
                                            if ($value['is_kpi'] && isset($expectedValues[$value['field_id']])) {
                                                echo number_format($expectedValues[$value['field_id']]['expected_value'], 2);
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($value['is_kpi'] && isset($expectedValues[$value['field_id']])) {
                                                $expectedVal = $expectedValues[$value['field_id']]['expected_value'];
                                                $actualVal = floatval($value['field_value']);
                                                if ($expectedVal > 0) {
                                                    $achievementPercent = ($actualVal / $expectedVal) * 100;
                                                    $colorClass = getScoreColorClass(calculatePerformanceScore($actualVal, $expectedVal));
                                                    
                                                    echo '<div class="progress">';
                                                    echo '<div class="progress-bar bg-' . $colorClass . '" ';
                                                    echo 'role="progressbar" style="width: ' . min(100, $achievementPercent) . '%" ';
                                                    echo 'aria-valuenow="' . $achievementPercent . '" aria-valuemin="0" aria-valuemax="100">';
                                                    echo number_format($achievementPercent, 1) . '%';
                                                    echo '</div></div>';
                                                } else {
                                                    echo '-';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($value['is_kpi'] && isset($expectedValues[$value['field_id']])) {
                                                $expectedVal = $expectedValues[$value['field_id']]['expected_value'];
                                                $actualVal = floatval($value['field_value']);
                                                $score = calculatePerformanceScore($actualVal, $expectedVal);
                                                $colorClass = getScoreColorClass($score);
                                                
                                                echo '<span class="badge bg-' . $colorClass . '">';
                                                echo number_format($score, 1);
                                                echo '</span>';
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($value['is_kpi'] && isset($expectedValues[$value['field_id']])) {
                                                $expectedVal = $expectedValues[$value['field_id']]['expected_value'];
                                                $actualVal = floatval($value['field_value']);
                                                $score = calculatePerformanceScore($actualVal, $expectedVal);
                                                $level = getPerformanceLevel($score);
                                                
                                                echo '<span class="badge bg-' . $level['class'] . '">';
                                                echo $level['level'];
                                                echo '</span>';
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Performance Comparison Chart -->
                <h5 class="mt-4 mb-3">نمودار مقایسه مقادیر واقعی و مورد انتظار</h5>
                <div id="performance-chart" style="height: 350px;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">KPI های تعریف شده</h5>
            </div>
            <div class="card-body">
                <?php if (count($pageKPIs) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($pageKPIs as $kpi): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo $kpi['field_label']; ?></h6>
                                    <small><?php echo $kpi['model_name']; ?></small>
                                </div>
                                <p class="mb-1">
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
                                </p>
                                
                                <?php if (isset($expectedValues[$kpi['field_id']])): ?>
                                    <small class="text-muted">
                                        مقدار مورد انتظار: 
                                        <?php echo number_format($expectedValues[$kpi['field_id']]['expected_value'], 2); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        هیچ KPI برای این صفحه تعریف نشده است.
                        <a href="page_kpi.php?page=<?php echo $report['page_id']; ?>" class="alert-link">
                            تعریف KPI
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">راهنمای امتیازدهی</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    امتیازات بر اساس میزان تحقق اهداف KPI محاسبه می‌شوند:
                </p>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        عملکرد عالی
                        <span class="badge bg-success rounded-pill">6 تا 7</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        عملکرد خوب
                        <span class="badge bg-primary rounded-pill">5 تا 5.9</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        عملکرد متوسط
                        <span class="badge bg-warning rounded-pill">3.5 تا 4.9</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        عملکرد ضعیف
                        <span class="badge bg-danger rounded-pill">0 تا 3.4</span>
                    </li>
                </ul>
                <p class="small text-muted">
                    فرمول محاسبه: امتیاز = (مقدار واقعی / مقدار مورد انتظار) × 7
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">گزارش‌های دیگر</h5>
            </div>
            <div class="card-body">
                <?php
                    // Get other reports for this page
                    $stmt = $pdo->prepare("SELECT id, report_date, 
                                         (SELECT AVG(score) FROM report_scores WHERE report_id = r.id) as avg_score
                                         FROM monthly_reports r 
                                         WHERE r.page_id = ? AND r.id != ? 
                                         ORDER BY r.report_date DESC LIMIT 5");
                    $stmt->execute([$report['page_id'], $reportId]);
                    $otherReports = $stmt->fetchAll();
                    
                    if (count($otherReports) > 0):
                ?>
                    <div class="list-group">
                        <?php foreach ($otherReports as $otherReport): ?>
                            <a href="view_social_report.php?report=<?php echo $otherReport['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">گزارش <?php echo $otherReport['report_date']; ?></h6>
                                    <?php if ($otherReport['avg_score']): ?>
                                        <?php $otherLevel = getPerformanceLevel($otherReport['avg_score']); ?>
                                        <span class="badge bg-<?php echo $otherLevel['class']; ?>">
                                            <?php echo number_format($otherReport['avg_score'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($otherReports) >= 5): ?>
                        <div class="text-center mt-3">
                            <a href="social_report.php?page=<?php echo $report['page_id']; ?>" class="btn btn-sm btn-outline-primary">
                                مشاهده همه گزارش‌ها
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        گزارش دیگری برای این صفحه وجود ندارد.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add charts scripts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for performance comparison chart
    const categories = [];
    const actualValues = [];
    const expectedValues = [];
    
    <?php
    // Only include KPI fields that have expected values
    foreach ($fieldValues as $value):
        if ($value['is_kpi'] && isset($expectedValues[$value['field_id']])):
    ?>
        categories.push('<?php echo $value['field_label']; ?>');
        actualValues.push(<?php echo floatval($value['field_value']); ?>);
        expectedValues.push(<?php echo $expectedValues[$value['field_id']]['expected_value']; ?>);
    <?php 
        endif;
    endforeach; 
    ?>
    
    // Configure performance comparison chart
    const options = {
        series: [{
            name: 'مقدار واقعی',
            data: actualValues
        }, {
            name: 'مقدار مورد انتظار',
            data: expectedValues
        }],
        chart: {
            type: 'bar',
            height: 350,
            fontFamily: 'Tahoma, Arial, sans-serif',
            dir: 'rtl',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                }
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val.toFixed(1);
            },
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ['#304758']
            }
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'مقدار'
            }
        },
        fill: {
            opacity: 1
        },
        colors: ['#4e73df', '#1cc88a'],
        tooltip: {
            y: {
                formatter: function(val) {
                    return val.toFixed(2);
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center'
        }
    };

    // Create performance comparison chart
    if (categories.length > 0) {
        const chart = new ApexCharts(document.querySelector("#performance-chart"), options);
        chart.render();
    } else {
        document.querySelector("#performance-chart").innerHTML = 
            '<div class="alert alert-info text-center">هیچ داده‌ای برای نمایش در نمودار وجود ندارد.</div>';
    }
    
    // Create radial chart for overall score percentage
    const pieOptions = {
        series: [<?php echo number_format(($overallScore / 7) * 100, 1); ?>],
        chart: {
            height: 250,
            type: 'radialBar',
            fontFamily: 'Tahoma, Arial, sans-serif',
            dir: 'rtl'
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '70%',
                },
                dataLabels: {
                    name: {
                        show: true,
                        fontSize: '16px',
                        offsetY: -10
                    },
                    value: {
                        fontSize: '22px',
                        fontWeight: 'bold',
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                shadeIntensity: 0.5,
                gradientToColors: ['<?php echo $performanceLevel['class'] == 'success' ? '#1cc88a' : ($performanceLevel['class'] == 'primary' ? '#4e73df' : ($performanceLevel['class'] == 'warning' ? '#f6c23e' : '#e74a3b')); ?>'],
                inverseColors: true,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100]
            }
        },
        stroke: {
            lineCap: 'round'
        },
        labels: ['درصد تحقق اهداف'],
    };

    const achievementChart = new ApexCharts(document.querySelector("#achievement-chart"), pieOptions);
    achievementChart.render();
});
</script>

<?php include 'footer.php'; ?>