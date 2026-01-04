<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';
global $pdo;

$response = [];

// Totals
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('-6 days'));
$month_start = date('Y-m-01');

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$response['totals']['today']['orders'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$week_start, $today]);
$response['totals']['week']['orders'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$month_start, $today]);
$response['totals']['month']['orders'] = (int)$stmt->fetchColumn();

// Customers (total)
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer'");
$response['totals']['customers'] = (int)$stmt->fetchColumn();

// Revenue (completed)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE tracking_status='completed' AND DATE(created_at)=?");
$stmt->execute([$today]);
$response['totals']['today']['revenue'] = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE tracking_status='completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$week_start, $today]);
$response['totals']['week']['revenue'] = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE tracking_status='completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$month_start, $today]);
$response['totals']['month']['revenue'] = (float)$stmt->fetchColumn();

// Avg rating
$stmt = $pdo->query("SELECT AVG(rating) AS avg_rating FROM feedback WHERE rating IS NOT NULL");
$response['avg_rating'] = round((float)$stmt->fetchColumn(),1);

// Recent orders (limit 8)
$stmt = $pdo->prepare("SELECT o.id, o.total_price, o.status, o.created_at, u.username AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id ORDER BY o.created_at DESC LIMIT 8");
$stmt->execute();
$response['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Revenue series: weekly (last 7 days) and monthly (last 6 months)
$response['series'] = [];

// Weekly: last 7 days labels and sums
$labels = [];
$data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('d M', strtotime($d));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE tracking_status='completed' AND DATE(created_at)=?");
    $stmt->execute([$d]);
    $data[] = (float)$stmt->fetchColumn();
}
$response['series']['weekly'] = ['labels'=>$labels,'data'=>$data];

// Monthly: last 6 months
$labels = [];
$data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m-01', strtotime("-{$i} month"));
    $labels[] = date('M Y', strtotime($month));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE tracking_status='completed' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([date('Y-m', strtotime($month))]);
    $data[] = (float)$stmt->fetchColumn();
}
$response['series']['monthly'] = ['labels'=>$labels,'data'=>$data];

// Orders series (same buckets) for counts
$labels = [];
$data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('d M', strtotime($d));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=?");
    $stmt->execute([$d]);
    $data[] = (int)$stmt->fetchColumn();
}
$response['series']['orders_weekly'] = ['labels'=>$labels,'data'=>$data];

echo json_encode($response);
?>
