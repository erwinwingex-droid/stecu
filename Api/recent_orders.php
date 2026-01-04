<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireLogin();
// Allow admin or owner to fetch recent orders
if (!isAdmin() && !isOwner()) {
	http_response_code(403);
	echo json_encode(['error' => 'Unauthorized']);
	exit();
}
include '../includes/functions.php';

// Optional date range filter: start and end in YYYY-MM-DD
$start = isset($_GET['start']) ? trim($_GET['start']) : null;
$end = isset($_GET['end']) ? trim($_GET['end']) : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

if ($start && $end) {
	$stmt = $pdo->prepare(
		"SELECT o.*, u.username AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.created_at DESC LIMIT ?"
	);
	$stmt->execute([$start, $end, $limit]);
	$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
	// default recent orders
	$orders = getRecentOrders($limit);
}

echo json_encode($orders);
?>