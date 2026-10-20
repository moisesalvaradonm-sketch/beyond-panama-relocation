<?php
namespace BPR;

/** Simple session-token admin auth. */
class Auth {
    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function login(string $email, string $password): ?string {
        $email = Utils::email($email);
        if (!$email) return null;
        if (!Utils::rateLimit('login:' . Utils::ip(), 8, 300)) return null;
        $u = DB::row('SELECT * FROM bpr_users WHERE email = ? AND active = 1', [$email]);
        if (!$u || !password_verify($password, $u['password_hash'])) return null;
        $token = bin2hex(random_bytes(32));
        DB::exec(
            'INSERT INTO bpr_sessions (token, user_id, expires_at, ip, user_agent) VALUES (?,?,?,?,?)',
            [$token, $u['id'], gmdate('Y-m-d H:i:s', time() + 60*60*24*14), Utils::ip(), Utils::userAgent()]
        );
        DB::exec('UPDATE bpr_users SET last_login_at = ? WHERE id = ?', [Utils::nowUtc(), $u['id']]);
        return $token;
    }

    public static function logout(string $token): void {
        DB::exec('DELETE FROM bpr_sessions WHERE token = ?', [$token]);
    }

    public static function currentUser(): ?array {
        $token = self::readToken();
        if (!$token) return null;
        $u = DB::row(
            'SELECT u.* FROM bpr_users u JOIN bpr_sessions s ON s.user_id = u.id
             WHERE s.token = ? AND s.expires_at > UTC_TIMESTAMP() AND u.active = 1',
            [$token]
        );
        return $u ?: null;
    }

    public static function require(?array $allowedRoles = null): array {
        $u = self::currentUser();
        if (!$u) Response::error('Unauthorised', 401);
        if ($allowedRoles && !in_array($u['role'], $allowedRoles, true)) Response::error('Forbidden', 403);
        return $u;
    }

    private static function readToken(): ?string {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($h, 'Bearer ') === 0) return trim(substr($h, 7));
        $h = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
        if ($h) return trim($h);
        return $_COOKIE['bpr_admin'] ?? null;
    }

    public static function requireCronToken(): void {
        $t = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
        $expected = bpr_config()['security']['cron_token'] ?? '';
        if (!$expected || !hash_equals($expected, (string)$t)) {
            Response::error('Forbidden', 403);
        }
    }
}
