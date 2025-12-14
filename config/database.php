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
        // Проверяем, что константы определены
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_PORT')) {
            throw new RuntimeException("Константы базы данных не определены. Убедитесь, что config/env.php загружен.");
        }
        
        // Используем переменные окружения (Railway или .env файл)
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->port = DB_PORT;
    }

    public function getConnection() {
        // Если подключение уже установлено, возвращаем его
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8";
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            // Логируем ошибку
            error_log("Database connection error: " . $exception->getMessage());
            error_log("Connection details: host=" . $this->host . ", port=" . $this->port . ", dbname=" . $this->db_name);
            
            // Выбрасываем исключение вместо возврата null
            throw new PDOException("Не удалось подключиться к базе данных. Пожалуйста, обратитесь к администратору.", 0, $exception);
        }

        return $this->conn;
    }
}

