<?php
header('Content-Type: application/json');
include '../includes/auth.php';
requireAdmin();
include '../includes/functions.php';

$stats = getAdminStats();
echo json_encode($stats);
?>