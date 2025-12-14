<?php
// Базовый путь приложения
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/');

// Загружаем переменные окружения первым делом
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/config/env.php';

// Автозагрузка конфигурации
require_once BASE_PATH . '/config/session.php';

