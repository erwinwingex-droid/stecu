<?php
header('Content-Type: application/json');
include '../includes/config.php';
include '../includes/functions.php';

$services = getServices();
echo json_encode($services);
?>