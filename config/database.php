<?php
/**
 * Database Connection Handler
 * Supports both Local Development (XAMPP / Docker / .env) and Railway Cloud PaaS
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Load .env if exists
        $this->loadEnv();

        // 1. Check Railway/Cloud URL (e.g. MYSQL_URL or DATABASE_URL: mysql://user:pass@host:port/dbname)
        $dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
        if (!empty($dbUrl)) {
            $parsed = parse_url($dbUrl);
            $this->host = $parsed['host'] ?? 'localhost';
            $this->port = $parsed['port'] ?? 3306;
            $this->username = $parsed['user'] ?? 'root';
            $this->password = $parsed['pass'] ?? '';
            $this->db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'railway';
            return;
        }

        // 2. Check Railway Individual Environment Variables or Custom Env
        $this->host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
        $this->port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306');
        $this->db_name = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'dbnorthwind');
        $this->username = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
        $this->password = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    }

    private function loadEnv() {
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database Connection Failed: " . $e->getMessage(),
                "hint" => "Please ensure your MySQL database is active and credentials are correctly configured."
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        }
        return $this->conn;
    }
}
