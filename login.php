<?php
// login.php
session_start();
ob_start(); // Буферизация для предотвращения ошибок заголовков

// Подключаем базу данных
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            if (!$conn) {
                $error = 'Не удалось подключиться к базе данных';
            } else {
                // Сначала проверим структуру таблицы users
                $stmt = $conn->query("SHOW COLUMNS FROM users");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

                error_log("Columns in users table: " . implode(', ', $columns));

                // Определяем поле для логина
                if (in_array('username', $columns)) {
                    $loginField = 'username';
                } elseif (in_array('email', $columns)) {
                    $loginField = 'email';
                } elseif (in_array('login', $columns)) {
                    $loginField = 'login';
                } else {
                    $error = 'Не найдено поле для логина в таблице users';
                    error_log("No login field found in users table");
                }

                if (!$error) {
                    // Теперь выполняем запрос с правильным количеством параметров
                    // Вариант 1: с именованными параметрами
                    $sql = "SELECT id, username, email, password, role, full_name 
                            FROM users 
                            WHERE $loginField = :username 
                            LIMIT 1";

                    error_log("SQL query: " . $sql);
                    error_log("Login field: $loginField, Username: $username");

                    $stmt = $conn->prepare($sql);

                    // Связываем параметр
                    $stmt->bindParam(':username', $username, PDO::PARAM_STR);

                    // ИЛИ более простой способ:
                    // $stmt->execute([':username' => $username]);

                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        error_log("User found: " . json_encode($user));

                        // Проверяем пароль
                        if (password_verify($password, $user['password'])) {
                            // Успешный вход
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'] ?? $user['email'];
                            $_SESSION['email'] = $user['email'] ?? '';
                            $_SESSION['role'] = $user['role'] ?? 'user';
                            $_SESSION['full_name'] = $user['full_name'] ?? '';

                            // Редирект
                            ob_end_clean(); // Очищаем буфер перед редиректом
                            header('Location: index.php');
                            exit();
                        } else {
                            $error = 'Неверный пароль';
                            error_log("Password verification failed for user: $username");
                        }
                    } else {
                        $error = 'Пользователь не найден';
                        error_log("No user found with $loginField = $username");
                    }
                }
            }

        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
            error_log("Login PDO Error: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            error_log("SQL query: " . ($sql ?? 'not set'));
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
            error_log("Login Error: " . $e->getMessage());
        }
    }
}

// Если есть ошибка, логируем ее
if ($error) {
    error_log("Login error for user '$username': $error");
}
?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Вход в систему</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .login-container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                width: 100%;
                max-width: 400px;
            }
            h2 {
                text-align: center;
                color: #333;
                margin-bottom: 30px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 5px;
                color: #555;
                font-weight: bold;
            }
            input[type="text"],
            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
                box-sizing: border-box;
            }
            input[type="text"]:focus,
            input[type="password"]:focus {
                outline: none;
                border-color: #007bff;
                box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 5px;
                margin-bottom: 20px;
                border: 1px solid #f5c6cb;
            }
            .success {
                background: #d4edda;
                color: #155724;
                padding: 12px;
                border-radius: 5px;
                margin-bottom: 20px;
                border: 1px solid #c3e6cb;
            }
            .btn-login {
                width: 100%;
                padding: 12px;
                background: #007bff;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                transition: background 0.3s;
            }
            .btn-login:hover {
                background: #0056b3;
            }
            .links {
                margin-top: 20px;
                text-align: center;
            }
            .links a {
                color: #007bff;
                text-decoration: none;
            }
            .links a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
    <div class="login-container">
        <h2>Вход в систему медицинских записей</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя или Email:</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       placeholder="Введите имя пользователя или email">
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required
                       placeholder="Введите пароль">
            </div>

            <button type="submit" class="btn-login">Войти</button>
        </form>

        <div class="links">
            <p><a href="index.php">← На главную</a></p>
            <?php if (getenv('APP_DEBUG') === 'true'): ?>
                <p><small><a href="check_users.php">Проверить пользователей</a></small></p>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
<?php
ob_end_flush(); // Выводим буфер
?>