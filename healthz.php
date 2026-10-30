<?php
// Health check endpoint untuk Docker HEALTHCHECK / load balancer.
// 200 OK + {"status":"ok"}  → web hidup
// 503 + {"status":"degraded","db":false} → web hidup tapi DB belum siap
header('Content-Type: application/json');
header('Cache-Control: no-store');

$out = ['status' => 'ok', 'time' => gmdate('c')];

try {
    require __DIR__ . '/api/config.php';
    $pdo->query('SELECT 1');
    $out['db'] = true;
} catch (Throwable $e) {
    http_response_code(503);
    $out['status'] = 'degraded';
    $out['db'] = false;
}

echo json_encode($out);
