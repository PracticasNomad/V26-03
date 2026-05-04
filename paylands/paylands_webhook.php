<?php

$raw = file_get_contents('php://input');

// Guardar log para ver qué llega
file_put_contents(
    __DIR__ . '/paylands_webhook.log',
    date('Y-m-d H:i:s') . " | " . $raw . PHP_EOL,
    FILE_APPEND
);

$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit('JSON inválido');
}

// IMPORTANTE: inspeccionar esto en pruebas
$status = $data['order']['status'] ?? null;

// Estados típicos correctos
$okStatuses = ['PAID', 'SUCCESS', 'AUTHORIZED'];

if (!in_array($status, $okStatuses)) {
    http_response_code(200);
    exit('Pago no válido');
}


file_put_contents(
    __DIR__ . '/paylands_paid.log',
    date('Y-m-d H:i:s') . " | PAGO OK | " . json_encode($data) . PHP_EOL,
    FILE_APPEND
);

http_response_code(200);
echo 'OK';