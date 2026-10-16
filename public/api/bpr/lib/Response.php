<?php
namespace BPR;

class Response {
    public static function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    public static function ok($data = []): void { self::json(['ok' => true] + (is_array($data) ? $data : ['data' => $data]), 200); }
    public static function error(string $msg, int $code = 400, array $extra = []): void {
        self::json(['ok' => false, 'error' => $msg] + $extra, $code);
    }
    public static function requireMethod(string $method): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) self::error('Method not allowed', 405);
    }
    public static function requirePost(): void { self::requireMethod('POST'); }
}
