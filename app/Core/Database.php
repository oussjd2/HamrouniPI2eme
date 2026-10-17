<?php
namespace App\Core;
use PDO;

final class Database {
    private static array $config;
    private static ?PDO $pdo = null;
    public static function configure(array $config): void { self::$config = $config; self::$pdo = null; }
    public static function connection(): PDO {
        if (self::$pdo === null) {
            $c = self::$config;
            self::$pdo = new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        }
        return self::$pdo;
    }
    public static function query(string $sql, array $params = []): \PDOStatement {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
    public static function transaction(callable $work) {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try { $result = $work(); $pdo->commit(); return $result; }
        catch (\Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    }
}
