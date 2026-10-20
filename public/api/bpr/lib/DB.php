<?php
namespace BPR;

use PDO;
use PDOException;

class DB {
    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;

        $cfg = bpr_config()['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'], $cfg['port'] ?? 3306, $cfg['name'], $cfg['charset'] ?? 'utf8mb4'
        );
        try {
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+00:00'",
            ]);
        } catch (PDOException $e) {
            error_log('[BPR][DB] ' . $e->getMessage());
            Response::error('Database unavailable', 503);
        }
        return self::$pdo;
    }

    /** @return array<int,array<string,mixed>> */
    public static function rows(string $sql, array $params = []): array {
        $st = self::get()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function row(string $sql, array $params = []): ?array {
        $st = self::get()->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function scalar(string $sql, array $params = []) {
        $r = self::row($sql, $params);
        return $r ? array_values($r)[0] : null;
    }

    public static function exec(string $sql, array $params = []): int {
        $st = self::get()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function insert(string $sql, array $params = []): int {
        self::exec($sql, $params);
        return (int) self::get()->lastInsertId();
    }
}
