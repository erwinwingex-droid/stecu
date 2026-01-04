<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");

// Cek autentikasi
requireLogin();
if (!isOwner()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Optional period filter via GET: start and end in YYYY-MM-DD
$start = isset($_GET['start']) ? trim($_GET['start']) : null;
$end = isset($_GET['end']) ? trim($_GET['end']) : null;

// Statistik Pendapatan Hari Ini (1 hari)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$today_revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Statistik Pendapatan 7 Hari Terakhir
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE(created_at) <= CURDATE()");
$stmt->execute();
$week_revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Statistik Pendapatan 30 Hari Terakhir
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE(created_at) <= CURDATE()");
$stmt->execute();
$month_revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Pendapatan Keseluruhan
$stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders");
$total_revenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// If a period is provided, compute period total and daily breakdown for that period
$period_total = null;
$period_labels = [];
$period_data = [];
if ($start && $end) {
    // Validate dates and limit range to avoid heavy queries (e.g., 1 year max)
    $start_dt = DateTime::createFromFormat('Y-m-d', $start);
    $end_dt = DateTime::createFromFormat('Y-m-d', $end);
    if ($start_dt && $end_dt && $start_dt <= $end_dt) {
        $diffDays = (int)$start_dt->diff($end_dt)->format('%a');
        if ($diffDays > 365) {
            // limit to 365 days
            $end_dt = (clone $start_dt)->modify('+365 days');
            $end = $end_dt->format('Y-m-d');
        }

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
        $stmt->execute([$start, $end]);
        $period_total = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Build daily breakdown between start and end
        $period_cursor = clone $start_dt;
        while ($period_cursor <= $end_dt) {
            $d = $period_cursor->format('Y-m-d');
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = ?");
            $stmt->execute([$d]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $period_labels[] = $period_cursor->format('d M Y');
            $period_data[] = (float)$row['total'];
            $period_cursor->modify('+1 day');
        }
    }
}

// Prepare arrays for charts
// Daily: hourly breakdown for today (0..23)
$daily_labels = [];
$daily_data = [];
for ($h = 0; $h < 24; $h++) {
    $label = sprintf('%02d:00', $h);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND HOUR(created_at) = ?");
    $stmt->execute([$h]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $daily_labels[] = $label;
    $daily_data[] = (float)$row['total'];
}

// Weekly: last 7 days (each day)
$weekly_labels = [];
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$d]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $weekly_labels[] = date('d M', strtotime($d));
    $weekly_data[] = (float)$row['total'];
}

// Monthly: last 30 days (each day)
$monthly_labels = [];
$monthly_data = [];
$today = new DateTime();
for ($i = 29; $i >= 0; $i--) {
    $d = $today->modify("-{$i} days")->format('Y-m-d');
    // reset today object for next iteration
    $today = new DateTime();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$d]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthly_labels[] = date('d M', strtotime($d));
    $monthly_data[] = (float)$row['total'];
}

// Return JSON
echo json_encode([
    'today_revenue' => $today_revenue,
    'week_revenue' => $week_revenue,
    'month_revenue' => $month_revenue,
    'total_revenue' => $total_revenue,
    'daily_labels' => $daily_labels,
    'daily_data' => $daily_data,
    'weekly_labels' => $weekly_labels,
    'weekly_data' => $weekly_data,
    'monthly_labels' => $monthly_labels,
    'monthly_data' => $monthly_data,
    'period_total' => $period_total,
    'period_labels' => $period_labels,
    'period_data' => $period_data,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
