<?php
/**
 * Скрипт для проверки конфигурации окружения
 * Используйте этот файл для отладки проблем с переменными окружения на Railway
 * Удалите этот файл после проверки в production!
 */

// Загружаем autoload
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Загружаем конфигурацию
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка конфигурации</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Проверка конфигурации окружения</h1>
        
        <div class="section">
            <h2>Переменные окружения</h2>
            <table>
                <tr>
                    <th>Переменная</th>
                    <th>Значение</th>
                    <th>Статус</th>
                </tr>
                <tr>
                    <td>MYSQL_HOST / DB_HOST</td>
                    <td><?php echo htmlspecialchars(DB_HOST); ?></td>
                    <td class="<?php echo DB_HOST !== 'localhost' ? 'success' : 'warning'; ?>">
                        <?php echo DB_HOST !== 'localhost' ? '✓' : '⚠ Локальное значение'; ?>
                    </td>
                </tr>
                <tr>
                    <td>MYSQL_DATABASE / DB_NAME</td>
                    <td><?php echo htmlspecialchars(DB_NAME); ?></td>
                    <td class="success">✓</td>
                </tr>
                <tr>
                    <td>MYSQL_USER / DB_USER</td>
                    <td><?php echo htmlspecialchars(DB_USER); ?></td>
                    <td class="success">✓</td>
                </tr>
                <tr>
                    <td>MYSQL_PASSWORD / DB_PASSWORD</td>
                    <td><?php echo DB_PASS ? '***' . substr(DB_PASS, -3) : '(не установлен)'; ?></td>
                    <td class="<?php echo DB_PASS ? 'success' : 'error'; ?>">
                        <?php echo DB_PASS ? '✓' : '✗'; ?>
                    </td>
                </tr>
                <tr>
                    <td>MYSQL_PORT / DB_PORT</td>
                    <td><?php echo htmlspecialchars(DB_PORT); ?></td>
                    <td class="success">✓</td>
                </tr>
                <tr>
                    <td>APP_ENV</td>
                    <td><?php echo htmlspecialchars(APP_ENV); ?></td>
                    <td class="success">✓</td>
                </tr>
                <tr>
                    <td>APP_DEBUG</td>
                    <td><?php echo APP_DEBUG ? 'true' : 'false'; ?></td>
                    <td class="success">✓</td>
                </tr>
                <tr>
                    <td>PORT (Railway)</td>
                    <td><?php echo getenv('PORT') ?: '(не установлен)'; ?></td>
                    <td class="<?php echo getenv('PORT') ? 'success' : 'warning'; ?>">
                        <?php echo getenv('PORT') ? '✓' : '⚠'; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Подключение к базе данных</h2>
            <?php
            $db = new Database();
            $conn = $db->getConnection();
            if ($conn) {
                echo '<p class="success">✓ Подключение к базе данных успешно!</p>';
                try {
                    $stmt = $conn->query("SELECT VERSION() as version");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo '<p>Версия MySQL: ' . htmlspecialchars($result['version']) . '</p>';
                } catch (Exception $e) {
                    echo '<p class="error">Ошибка при запросе: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p class="error">✗ Не удалось подключиться к базе данных</p>';
            }
            ?>
        </div>

        <div class="section">
            <h2>PHP информация</h2>
            <p><strong>Версия PHP:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Расширения:</strong></p>
            <ul>
                <li>PDO: <?php echo extension_loaded('pdo') ? '✓' : '✗'; ?></li>
                <li>PDO MySQL: <?php echo extension_loaded('pdo_mysql') ? '✓' : '✗'; ?></li>
                <li>MySQLi: <?php echo extension_loaded('mysqli') ? '✓' : '✗'; ?></li>
                <li>GD: <?php echo extension_loaded('gd') ? '✓' : '✗'; ?></li>
                <li>Zip: <?php echo extension_loaded('zip') ? '✓' : '✗'; ?></li>
                <li>JSON: <?php echo extension_loaded('json') ? '✓' : '✗'; ?></li>
                <li>mbstring: <?php echo extension_loaded('mbstring') ? '✓' : '✗'; ?></li>
                <li>cURL: <?php echo extension_loaded('curl') ? '✓' : '✗'; ?></li>
                <li>OpenSSL: <?php echo extension_loaded('openssl') ? '✓' : '✗'; ?></li>
            </ul>
        </div>

        <div class="section">
            <h2>Файловая система</h2>
            <ul>
                <li>Директория uploads: <?php echo is_dir(__DIR__ . '/uploads') ? '✓ существует' : '✗ не существует'; ?></li>
                <li>Права на запись в uploads: <?php echo is_writable(__DIR__ . '/uploads') ? '✓' : '✗'; ?></li>
                <li>Директория tmp: <?php echo is_dir(__DIR__ . '/tmp') ? '✓ существует' : '✗ не существует'; ?></li>
            </ul>
        </div>

        <div class="section">
            <h2>Все переменные окружения (для отладки)</h2>
            <pre><?php
            $env_vars = [
                'MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_PORT',
                'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_PORT',
                'APP_ENV', 'APP_DEBUG', 'PORT'
            ];
            foreach ($env_vars as $var) {
                $value = getenv($var);
                if ($var === 'MYSQL_PASSWORD' || $var === 'DB_PASSWORD') {
                    echo $var . ' = ' . ($value ? '***' . substr($value, -3) : '(не установлен)') . "\n";
                } else {
                    echo $var . ' = ' . ($value ?: '(не установлен)') . "\n";
                }
            }
            ?></pre>
        </div>

        <div class="section">
            <p class="warning"><strong>⚠ Внимание:</strong> Удалите этот файл (check_env.php) после проверки конфигурации в production!</p>
        </div>
    </div>
</body>
</html>
