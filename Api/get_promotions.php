<?php
header('Content-Type: application/json');
include '../includes/config.php';
include '../includes/functions.php';

$promotions = getActivePromotions();
echo json_encode($promotions);
?>