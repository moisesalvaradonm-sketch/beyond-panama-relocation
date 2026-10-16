<?php
namespace BPR;

class Utils {
    public static function s(?string $v, int $max = 255): string {
        $v = trim((string)($v ?? ''));
        if ($v === '') return '';
        // strip control chars, cap length
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
        return mb_substr($v, 0, $max);
    }

    public static function email(?string $v): ?string {
        $v = strtolower(trim((string)$v));
        return $v && filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    public static function ip(): ?string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
            $v = $_SERVER[$k] ?? '';
            if ($v) return explode(',', $v)[0];
        }
        return null;
    }

    public static function userAgent(): ?string {
        return isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    }

    /** HMAC signed token (used in unsubscribe / tracking links). */
    public static function sign(array $payload): string {
        $secret = bpr_config()['security']['link_secret'] ?? 'insecure';
        $body   = base64_encode(json_encode($payload));
        $sig    = hash_hmac('sha256', $body, $secret);
        return $body . '.' . substr($sig, 0, 32);
    }
    public static function unsign(string $token): ?array {
        if (!str_contains($token, '.')) return null;
        [$body, $sig] = explode('.', $token, 2);
        $secret = bpr_config()['security']['link_secret'] ?? 'insecure';
        $expected = substr(hash_hmac('sha256', $body, $secret), 0, 32);
        if (!hash_equals($expected, $sig)) return null;
        $raw = base64_decode($body, true);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function unsubscribeUrl(int $contactId): string {
        $base = rtrim(bpr_config()['site']['base_url'], '/');
        $t = self::sign(['c' => $contactId, 't' => 'unsub', 'ts' => time()]);
        return $base . '/api/bpr/routes/unsubscribe.php?t=' . urlencode($t);
    }

    public static function trackClickUrl(int $queueId, string $target): string {
        $base = rtrim(bpr_config()['site']['base_url'], '/');
        $t = self::sign(['q' => $queueId, 'u' => $target]);
        return $base . '/api/bpr/routes/click.php?t=' . urlencode($t);
    }

    public static function trackOpenUrl(int $queueId): string {
        $base = rtrim(bpr_config()['site']['base_url'], '/');
        $t = self::sign(['q' => $queueId, 't' => 'o']);
        return $base . '/api/bpr/routes/open.php?t=' . urlencode($t);
    }

    public static function nowUtc(): string { return gmdate('Y-m-d H:i:s'); }
    public static function inFuture(int $seconds): string { return gmdate('Y-m-d H:i:s', time() + $seconds); }

    /** Minimal rate limit backed by file locks. */
    public static function rateLimit(string $key, int $max, int $windowSec): bool {
        $dir = __DIR__ . '/../logs/rl';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $file = $dir . '/' . hash('sha256', $key);
        $now = time();
        $data = @file_get_contents($file);
        $arr  = $data ? json_decode($data, true) : ['start' => $now, 'count' => 0];
        if (!is_array($arr)) $arr = ['start' => $now, 'count' => 0];
        if ($now - ($arr['start'] ?? $now) > $windowSec) $arr = ['start' => $now, 'count' => 0];
        $arr['count'] = ($arr['count'] ?? 0) + 1;
        @file_put_contents($file, json_encode($arr), LOCK_EX);
        return $arr['count'] <= $max;
    }

    public static function isBotUA(?string $ua): bool {
        if (!$ua) return false;
        return (bool) preg_match('/bot|crawler|spider|slurp|preview|monitor|curl|wget|python-requests/i', $ua);
    }
}
