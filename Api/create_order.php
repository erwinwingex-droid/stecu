<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireLogin();
include '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $service_id = sanitize($input['service_id']);
    $quantity = sanitize($input['quantity']);
    $pickup_date = sanitize($input['pickup_date']);
    $pickup_time = sanitize($input['pickup_time']);
    $delivery_address = sanitize($input['delivery_address']);
    $notes = sanitize($input['notes']);
    
    if (createOrder($_SESSION['user_id'], $service_id, $quantity, $pickup_date, $pickup_time, $delivery_address, $notes)) {
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibuat']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat pesanan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>