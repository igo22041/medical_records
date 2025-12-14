<?php
/**
 * Загрузка переменных окружения
 * Поддерживает как .env файл (для локальной разработки), так и переменные окружения Railway
 */

// Загружаем .env файл если он существует (для локальной разработки)
if (file_exists(__DIR__ . '/../.env') && class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (Exception $e) {
        // Игнорируем ошибки загрузки .env, используем переменные окружения системы
        error_log('Warning: Could not load .env file: ' . $e->getMessage());
    }
}

/**
 * Получить переменную окружения с значением по умолчанию
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}


// Railway использует префикс MYSQL_, но мы также поддерживаем DB_ для гибкости
define('DB_HOST', env('MYSQL_HOST', env('DB_HOST', 'switchback.proxy.rlwy.net')));
define('DB_NAME', env('MYSQL_DATABASE', env('DB_NAME', 'medical_records')));
define('DB_USER', env('MYSQL_USER', env('DB_USER', 'root')));
define('DB_PASS', env('MYSQL_PASSWORD', env('DB_PASSWORD', 'sBxNvZMqCrmjjXmbuatiSsPBPwDEDuzW')));
define('DB_PORT', env('MYSQL_PORT', env('DB_PORT', '21778')));

// Другие переменные окружения
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');

