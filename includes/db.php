<?php
/**
 * CivicTrack — includes/db.php
 * Singleton PDO database wrapper.
 * Usage: $db = DB::get();  then  $db->prepare(...)->execute(...)
 */
require_once __DIR__ . '/config.php';
class DB {
    private static ?PDO $instance = null;

    /** Returns the shared PDO connection (creates it on first call). */
    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Show a friendly error; never expose credentials
                die('<div style="font-family:sans-serif;padding:40px;color:#c0392b">
                    <h2>Database Connection Failed</h2>
                    <p>Please check your <code>includes/config.php</code> credentials and ensure MySQL is running.</p>'
                    . (DEBUG_MODE ? '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : '')
                    . '</div>');
            }
        }
        return self::$instance;
    }

    /** Prevents cloning */
    private function __clone() {}

    /** Execute a query with params and return all rows. */
    public static function rows(string $sql, array $params = []): array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Execute a query with params and return a single row. */
    public static function row(string $sql, array $params = []): ?array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Execute a query with params and return a single scalar value. */
    public static function value(string $sql, array $params = []): mixed {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** Execute a write query (INSERT/UPDATE/DELETE) and return affected rows. */
    public static function exec(string $sql, array $params = []): int {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Returns the last inserted auto-increment ID. */
   /** Returns the last inserted auto-increment ID. */
    public static function lastId(): string {
        return self::get()->lastInsertId();
    }

    // Add this specifically to fix the "undefined method connect" error
    public static function lastInsertId(): string {
        return self::get()->lastInsertId();
    }
}
