<?php
/**
 * SFXSMM Bot — Database moduli
 * 
 * Xavfsiz database ulanish va query funksiyalari.
 * Barcha SQL operatsiyalar prepared statements orqali amalga oshiriladi.
 */

class Database
{
    private static ?mysqli $connection = null;

    /**
     * Database ulanishini olish (Singleton pattern)
     */
    public static function connect(): mysqli
    {
        if (self::$connection === null) {
            self::$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if (self::$connection->connect_error) {
                error_log('Database ulanish xatosi: ' . self::$connection->connect_error);
                die('Database ulanish xatosi');
            }

            self::$connection->set_charset('utf8mb4');
        }

        return self::$connection;
    }

    /**
     * Xavfsiz SELECT query (bitta natija)
     */
    public static function fetchOne(string $query, array $params = []): ?array
    {
        $stmt = self::prepare($query, $params);
        if (!$stmt) return null;

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row;
    }

    /**
     * Xavfsiz SELECT query (barcha natijalar)
     */
    public static function fetchAll(string $query, array $params = []): array
    {
        $stmt = self::prepare($query, $params);
        if (!$stmt) return [];

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Xavfsiz INSERT/UPDATE/DELETE query
     */
    public static function execute(string $query, array $params = []): bool
    {
        $stmt = self::prepare($query, $params);
        if (!$stmt) return false;

        $result = $stmt->affected_rows >= 0;
        $stmt->close();

        return $result;
    }

    /**
     * COUNT query
     */
    public static function count(string $query, array $params = []): int
    {
        $row = self::fetchOne($query, $params);
        if (!$row) return 0;

        return intval(reset($row));
    }

    /**
     * SUM query
     */
    public static function sum(string $query, array $params = []): float
    {
        $row = self::fetchOne($query, $params);
        if (!$row) return 0;

        return floatval(reset($row));
    }

    /**
     * Oxirgi INSERT qilingan ID
     */
    public static function lastInsertId(): int
    {
        return self::connect()->insert_id;
    }

    /**
     * Prepared statement yaratish va execute qilish
     */
    private static function prepare(string $query, array $params): ?mysqli_stmt
    {
        $conn = self::connect();
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            error_log("SQL xato: {$conn->error} | Query: $query");
            return null;
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                else $types .= 's';
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Zarur jadvallarni yaratish (birinchi marta ishga tushganda)
     */
    public static function createTables(): void
    {
        $conn = self::connect();

        // soxta jadvali
        $conn->query("CREATE TABLE IF NOT EXISTS soxta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            come VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // ratings jadvali
        $conn->query("CREATE TABLE IF NOT EXISTS ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100) UNIQUE,
            rating TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // send jadvali (ommaviy xabar)
        $conn->query("CREATE TABLE IF NOT EXISTS send (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id VARCHAR(100),
            message_id VARCHAR(100),
            start_id INT DEFAULT 0,
            stop_id INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
