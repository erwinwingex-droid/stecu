<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireLogin();
// Allow both admin and owner to fetch customer orders for management views
if (!isAdmin() && !isOwner()) {
    http_response_code(403);
    echo json_encode(['error' => 'access_denied']);
    exit;
}
include '../includes/functions.php';

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'customer_id is required']);
    exit;
}

// Use helper to fetch orders and include items per order
$orders = getCustomerOrders($customer_id);

// limit results
if ($limit > 0) {
    $orders = array_slice($orders, 0, $limit);
}

// attach items to each order
foreach ($orders as &$o) {
    $o['items'] = getOrderItems($o['id']);
}

// return JSON
echo json_encode($orders);

?>
