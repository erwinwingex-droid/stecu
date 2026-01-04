<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

$customer_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$stmt = $pdo->prepare("
    SELECT o.*, s.name as service_name 
    FROM orders o 
    JOIN services s ON o.service_id = s.id 
    WHERE o.customer_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT ?
");
$stmt->execute([$customer_id, $limit]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($orders);
?>