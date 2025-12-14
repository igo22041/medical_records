<?php
// config/database.php
class Database {
    private $conn = null;

    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }

    private function connect() {
        try {

            $databaseUrl = getenv('DATABASE_URL');

            if ($databaseUrl) {

                $url = parse_url($databaseUrl);


                $host = "mysql.railway.internal";
                $port = '3306';
                $dbname = "railway";
                $username = "root";
                $password = "sBxNvZMqCrmjjXmbuatiSsPBPwDEDuzW";


                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

                $this->conn = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,

                    PDO::ATTR_PERSISTENT => false
                ]);

                error_log("Connected to Railway MySQL via TCP: $host:$port");

            } else {
                $host = getenv('DB_HOST') ?: 'localhost';
                $port = getenv('DB_PORT') ?: '3306';
                $dbname = getenv('DB_NAME') ?: 'railway';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASSWORD') ?: '';


                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

                $this->conn = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                error_log("Connected to MySQL: $host:$port");
            }

            // Тестовый запрос для проверки
            $this->conn->query("SELECT 1");

        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            error_log("DSN attempted: " . ($dsn ?? 'unknown'));

            // Дополнительная отладка
            $this->logEnvironment();

            // Показываем понятную ошибку
            if (getenv('APP_DEBUG') === 'true') {
                die("Database Connection Error: " . $e->getMessage() .
                    "<br>Check if MySQL service is running on Railway.");
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }

    private function logEnvironment() {
        $envVars = [
            'DATABASE_URL',
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'RAILWAY_ENVIRONMENT',
            'RAILWAY_PROJECT_NAME'
        ];

        foreach ($envVars as $var) {
            $value = getenv($var);
            error_log("ENV $var: " . ($value ? substr($value, 0, 50) . "..." : 'not set'));
        }
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT NOW() as time, VERSION() as version");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
