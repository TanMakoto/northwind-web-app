<?php
/**
 * Database Connection Handler
 * Supports Local Development, Railway PaaS, Render, and TiDB Cloud with SSL
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
        $this->loadEnv();

        // 1. Check Cloud URL
        $dbUrl = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: getenv('CLEARDB_DATABASE_URL'));
        if (!empty($dbUrl)) {
            $parsed = parse_url($dbUrl);
            $this->host = $parsed['host'] ?? 'localhost';
            $this->port = $parsed['port'] ?? 3306;
            $this->username = $parsed['user'] ?? 'root';
            $this->password = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
            $this->db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'test';
            return;
        }

        // 2. Individual Environment Variables
        $this->host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: '127.0.0.1');
        $this->port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
        $this->db_name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'test');
        $this->username = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');
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

            // SSL configuration for Cloud MySQL providers like TiDB Cloud & Aiven
            $caPaths = [
                '/etc/ssl/certs/ca-certificates.crt', // Debian/Ubuntu/Alpine/Docker
                '/etc/pki/tls/certs/ca-bundle.crt',   // CentOS/RHEL
                '/etc/ssl/ca-bundle.pem',
                'C:/xampp/apache/bin/curl-ca-bundle.crt'
            ];

            $caFound = false;
            foreach ($caPaths as $ca) {
                if (file_exists($ca)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                    $caFound = true;
                    break;
                }
            }

            // If hostname is tidbcloud or remote cloud, force SSL attribute
            if (!$caFound && (strpos($this->host, 'tidbcloud.com') !== false || strpos($this->host, 'aivencloud.com') !== false)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database Connection Failed: " . $e->getMessage(),
                "hint" => "Please check DB_HOST, DB_NAME, DB_USER, DB_PASS environment variables."
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit();
        }
        return $this->conn;
    }
}
