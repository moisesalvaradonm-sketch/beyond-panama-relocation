<?php
/**
 * Bootstrap — loaded by every route.
 *   require __DIR__ . '/../bootstrap.php';   // from routes/*.php
 */

declare(strict_types=1);

// ---- Errors: never leak to the browser ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-error.log');
if (!is_dir(__DIR__ . '/logs')) { @mkdir(__DIR__ . '/logs', 0750, true); }

date_default_timezone_set('UTC');

// ---- Load config ----
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server not configured. Copy config.example.php to config.php and fill in credentials.']);
    exit;
}
$CONFIG = require $configPath;

// ---- Simple autoload for lib/ ----
spl_autoload_register(function (string $class) {
    // BPR\DB → lib/DB.php
    if (strpos($class, 'BPR\\') === 0) {
        $file = __DIR__ . '/lib/' . substr($class, 4) . '.php';
        if (is_file($file)) require $file;
    }
});

// ---- Helpers ----
function bpr_config(): array {
    global $CONFIG;
    return $CONFIG;
}

function bpr_cors(): void {
    $cfg = bpr_config();
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array($origin, $cfg['site']['allowed_origins'], true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } else {
        // Fallback for direct requests (same-origin).
        header('Access-Control-Allow-Origin: ' . ($cfg['site']['base_url'] ?? '*'));
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Token');
    header('Access-Control-Max-Age: 600');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function bpr_json_input(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return $_POST ?? [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
