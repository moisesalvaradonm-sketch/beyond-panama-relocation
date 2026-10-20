<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://beyondpanamarelocation.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$email = isset($body['email']) ? trim(strtolower($body['email'])) : '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

$cfgFile = __DIR__ . '/bpr/config.php';
$cfg = is_file($cfgFile) ? (require $cfgFile) : [];
$BREVO_KEY = $cfg['brevo']['api_key'] ?? '';
$LIST_ID   = 3;

$payload = json_encode([
    'email'         => $email,
    'listIds'       => [$LIST_ID],
    'updateEnabled' => true,
]);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . $BREVO_KEY,
        ]),
        'content' => $payload,
        'timeout' => 10,
        'ignore_errors' => true,
    ]
]);

$resp = @file_get_contents('https://api.brevo.com/v3/contacts', false, $ctx);
$code = 0;
if (isset($http_response_header)) {
    preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m);
    $code = (int)($m[1] ?? 0);
}

// 201 = created, 204 = already exists — both are success
if ($code === 201 || $code === 204) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(502);
    $detail = $resp ? json_decode($resp, true) : [];
    echo json_encode(['error' => $detail['message'] ?? 'Subscription failed', 'code' => $code]);
}
