<?php
require_once(__DIR__ . "/../includes/config.php");
require_once(__DIR__ . "/../includes/auth.php");
require_once(__DIR__ . "/../includes/functions.php");

requireLogin();
if (!isAdmin() && !isOwner()) {
    header('Location: ../index.php');
    exit();
}

// Optional date filters (YYYY-MM-DD)
$start = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
$end   = !empty($_GET['end_date']) ? $_GET['end_date'] : '';

// Build WHERE clause for orders and feedback (by created_at)
$whereOrder = '';
$paramsOrder = [];
if ($start && $end) {
    $whereOrder = 'WHERE DATE(o.created_at) BETWEEN :start AND :end';
    $paramsOrder[':start'] = $start;
    $paramsOrder[':end'] = $end;
} elseif ($start) {
    $whereOrder = 'WHERE DATE(o.created_at) >= :start';
    $paramsOrder[':start'] = $start;
} elseif ($end) {
    $whereOrder = 'WHERE DATE(o.created_at) <= :end';
    $paramsOrder[':end'] = $end;
}

// Sales / Orders
$sqlOrders = "SELECT o.id, u.username AS customer, o.total_price, o.tracking_status AS status, o.created_at,
    GROUP_CONCAT(CONCAT(s.name, ' x', oi.quantity) SEPARATOR '; ') AS items
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN services s ON s.id = oi.service_id
    " . $whereOrder . "
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sqlOrders);
$stmt->execute($paramsOrder);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customers
$sqlCustomers = "SELECT u.id, u.username, u.email, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total_price),0) AS total_spent, u.created_at
    FROM users u
    LEFT JOIN orders o ON u.id = o.customer_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$customers = $pdo->query($sqlCustomers)->fetchAll(PDO::FETCH_ASSOC);

// Feedback
$whereFeedback = '';
$paramsFeedback = [];
if ($start && $end) {
    $whereFeedback = 'WHERE DATE(f.created_at) BETWEEN :start AND :end';
    $paramsFeedback[':start'] = $start;
    $paramsFeedback[':end'] = $end;
} elseif ($start) {
    $whereFeedback = 'WHERE DATE(f.created_at) >= :start';
    $paramsFeedback[':start'] = $start;
} elseif ($end) {
    $whereFeedback = 'WHERE DATE(f.created_at) <= :end';
    $paramsFeedback[':end'] = $end;
}

$sqlFeedback = "SELECT f.id, u.username AS customer, f.order_id, f.rating, f.comment, f.created_at
    FROM feedback f
    JOIN users u ON f.customer_id = u.id
    " . $whereFeedback . "
    ORDER BY f.created_at DESC
";
$stmtF = $pdo->prepare($sqlFeedback);
$stmtF->execute($paramsFeedback);
$feedbacks = $stmtF->fetchAll(PDO::FETCH_ASSOC);

// Build a single Excel-friendly HTML (works with Excel .xls)
$filename = 'export_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
// BOM for proper UTF-8 in Excel
echo "\xEF\xBB\xBF";

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<h2>Penjualan (Orders)</h2>
<table border="1">
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Items</th>
    <th>Total Price</th>
    <th>Status</th>
    <th>Created At</th>
</tr>
<?php foreach ($orders as $o): ?>
<tr>
    <td><?php echo $o['id']; ?></td>
    <td><?php echo htmlspecialchars($o['customer']); ?></td>
    <td><?php echo htmlspecialchars($o['items']); ?></td>
    <td><?php echo number_format($o['total_price'], 0, ',', '.'); ?></td>
    <td><?php echo htmlspecialchars($o['status']); ?></td>
    <td><?php echo $o['created_at']; ?></td>
</tr>
<?php endforeach; ?>
</table>

<br>
<h2>Pelanggan (Customers)</h2>
<table border="1">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Total Orders</th>
    <th>Total Spent</th>
    <th>Bergabung</th>
</tr>
<?php foreach ($customers as $c): ?>
<tr>
    <td><?php echo $c['id']; ?></td>
    <td><?php echo htmlspecialchars($c['username']); ?></td>
    <td><?php echo htmlspecialchars($c['email']); ?></td>
    <td><?php echo (int)$c['total_orders']; ?></td>
    <td><?php echo number_format($c['total_spent'], 0, ',', '.'); ?></td>
    <td><?php echo $c['created_at']; ?></td>
</tr>
<?php endforeach; ?>
</table>

<br>
<h2>Feedback</h2>
<table border="1">
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Order ID</th>
    <th>Rating</th>
    <th>Comment</th>
    <th>Created At</th>
</tr>
<?php foreach ($feedbacks as $f): ?>
<tr>
    <td><?php echo $f['id']; ?></td>
    <td><?php echo htmlspecialchars($f['customer']); ?></td>
    <td><?php echo $f['order_id']; ?></td>
    <td><?php echo (int)$f['rating']; ?></td>
    <td><?php echo htmlspecialchars($f['comment']); ?></td>
    <td><?php echo $f['created_at']; ?></td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>