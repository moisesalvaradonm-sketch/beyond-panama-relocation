<?php
// Newsletter subscription handler — routed here because /api/subscribe.php has a stale server cache
if (($_GET['t'] ?? '') === 'nl') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: https://beyondpanamarelocation.com');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
    $em = isset($body['email']) ? trim(strtolower($body['email'])) : '';
    if (!$em || !filter_var($em, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['error' => 'Invalid email']); exit; }
    $cfgFile = __DIR__ . '/bpr/config.php';
    $cfg = is_file($cfgFile) ? (require $cfgFile) : [];
    $KEY = $cfg['brevo']['api_key'] ?? '';
    if (!$KEY) { http_response_code(500); echo json_encode(['error' => 'Server not configured']); exit; }
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\nAccept: application/json\r\napi-key: {$KEY}", 'content' => json_encode(['email' => $em, 'listIds' => [3], 'updateEnabled' => true]), 'timeout' => 10, 'ignore_errors' => true]]);
    $resp = @file_get_contents('https://api.brevo.com/v3/contacts', false, $ctx);
    $code = 0;
    if (isset($http_response_header)) { preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m); $code = (int)($m[1] ?? 0); }
    if ($code === 201 || $code === 204) { echo json_encode(['ok' => true]); } else { http_response_code(502); $d = $resp ? json_decode($resp, true) : []; echo json_encode(['error' => $d['message'] ?? 'Subscription failed']); }
    exit;
}

/**
 * Property inquiry endpoint (backwards compatible).
 * Feeds the BPR Lead Engine: creates contact, tags interest=property, logs activity.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://beyondpanamarelocation.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

function clean($val) { return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8'); }

$name     = clean($_POST['name']      ?? '');
$email    = trim(strtolower($_POST['email']    ?? ''));
$whatsapp = clean($_POST['whatsapp']  ?? '');
$message  = clean($_POST['message']   ?? '');
$source   = clean($_POST['source']    ?? 'property-popup');

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// ------- Notification & confirmation emails (before engine so they always send) -------
$NOTIFY_EMAIL = 'contact@beyondpanamarelocation.com';
$subject = "Nueva consulta de propiedad — {$name}";
$body = "Nueva consulta de propiedad recibida desde beyondpanamarelocation.com\nFuente: {$source}\n\nNOMBRE:   {$name}\nWHATSAPP: {$whatsapp}\nEMAIL:    {$email}\n\nDETALLES:\n{$message}\n";
$headers  = "From: noreply@beyondpanamarelocation.com\r\nReply-To: {$email}\r\nX-Mailer: PHP/" . phpversion();
$sent = @mail($NOTIFY_EMAIL, $subject, $body, $headers);

$confirmSubject = "Recibimos tu consulta — Beyond Panama Relocation";
$confirmBody = "Hola {$name},\n\nHemos recibido tu consulta sobre propiedades en Panamá. Uno de nuestros asesores te contactará pronto al correo {$email}" . ($whatsapp ? " o por WhatsApp al {$whatsapp}" : "") . ".\n\n--- Tu mensaje ---\n{$message}\n---\n\nMientras tanto, puedes explorar más recursos en: https://beyondpanamarelocation.com/es/\n\nEl equipo de Beyond Panama Relocation\nWhatsApp: +507 6084-4256\ncontact@beyondpanamarelocation.com\n";
$confirmHeaders  = "From: contact@beyondpanamarelocation.com\r\nReply-To: contact@beyondpanamarelocation.com\r\nX-Mailer: PHP/" . phpversion();
@mail($email, $confirmSubject, $confirmBody, $confirmHeaders);

// ------- Feed the Lead Engine (only if DB is reachable) -------
$leadId = null;
$_dbOk = false;
try {
    $cfg = require __DIR__ . '/bpr/config.php';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $cfg['db']['host'], $cfg['db']['port'] ?? 3306, $cfg['db']['name']);
    $tmp = new \PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'],
        [\PDO::ATTR_TIMEOUT => 3, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    unset($tmp);
    $_dbOk = true;
} catch (\Throwable $_e) {
    error_log('[property-inquiry] DB unavailable, skipping engine: ' . $_e->getMessage());
}

if ($_dbOk) {
    try {
        require __DIR__ . '/bpr/bootstrap.php';
        $parts = preg_split('/\s+/', $name, 2);
        $contact = \BPR\Contact::upsert([
            'email'             => $email,
            'first_name'        => $parts[0] ?? '',
            'last_name'         => $parts[1] ?? '',
            'phone'             => $whatsapp,
            'lead_source'       => $source,
            'main_interest'     => 'property',
            'interests'         => ['property'],
            'marketing_consent' => 1,
            'consent_source'    => 'property_inquiry',
        ]);
        \BPR\DB::insert(
            'INSERT INTO bpr_form_submissions (contact_id, form_key, payload_json, ip, user_agent) VALUES (?,?,?,?,?)',
            [$contact['id'], 'property_inquiry',
             json_encode(['name' => $name, 'email' => $email, 'whatsapp' => $whatsapp, 'message' => $message, 'source' => $source], JSON_UNESCAPED_UNICODE),
             \BPR\Utils::ip(), \BPR\Utils::userAgent()]
        );
        \BPR\Activity::log((int)$contact['id'], 'form_submit', [
            'category' => 'property',
            'meta' => ['form' => 'property_inquiry', 'message' => mb_substr($message, 0, 500)],
        ]);
        if ($message) \BPR\IntentDetector::fromText((int)$contact['id'], $message);
        \BPR\Brevo::upsertContact($email, [
            'FIRSTNAME' => $contact['first_name'] ?? '',
            'LASTNAME'  => $contact['last_name'] ?? '',
        ]);
        \BPR\Automation::enrollByTrigger((int)$contact['id'], 'interest_added', ['interest' => 'property']);
        $leadId = (int)$contact['id'];
    } catch (\Throwable $e) { error_log('[property-inquiry] engine: ' . $e->getMessage()); }
}

echo json_encode(['ok' => (bool)$sent, 'contact_id' => $leadId]);
