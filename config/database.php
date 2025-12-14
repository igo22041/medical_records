<?php
// Загружаем autoload если еще не загружен
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Загружаем переменные окружения
require_once __DIR__ . '/env.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Используем переменные окружения (Railway или .env файл)
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->port = DB_PORT;
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8";
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // В production не показываем детали ошибки
            if (APP_DEBUG) {
                echo "Ошибка подключения: " . $exception->getMessage();
            } else {
                error_log("Database connection error: " . $exception->getMessage());
                echo "Ошибка подключения к базе данных. Пожалуйста, обратитесь к администратору.";
            }
        }

        return $this->conn;
    }
}
?>

