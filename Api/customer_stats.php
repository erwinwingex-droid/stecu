<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

$customer_id = $_SESSION['user_id'];

// Hitung statistik
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND status = 'completed'");
$stmt->execute([$customer_id]);
$completed_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND status = 'pending'");
$stmt->execute([$customer_id]);
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo json_encode([
    'total_orders' => $total_orders,
    'completed_orders' => $completed_orders,
    'pending_orders' => $pending_orders
]);
?>