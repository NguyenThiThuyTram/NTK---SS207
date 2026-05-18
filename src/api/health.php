<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$status = ['status' => 'ok', 'timestamp' => date('c')];

try {
    $conn->query("SELECT 1");
    $status['database'] = 'connected';
} catch (Exception $e) {
    $status['status'] = 'error';
    $status['database'] = 'disconnected';
    http_response_code(503);
}

echo json_encode($status);
