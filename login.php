<?php
require_once 'config/session.php';
require_once 'config/database.php';

$error = '';
$success = '';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

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
                // Вход по username или email
                // Определяем, что введено: email или username
                $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
                
                if ($isEmail) {
                    // Поиск по email
                    $sql = "SELECT id, username, email, password, role 
                            FROM users 
                            WHERE email = :login 
                            LIMIT 1";
                } else {
                    // Поиск по username
                    $sql = "SELECT id, username, email, password, role 
                            FROM users 
                            WHERE username = :login 
                            LIMIT 1";
                }

                error_log("SQL query: " . $sql);
                error_log("Login: $username (type: " . ($isEmail ? 'email' : 'username') . ")");

                $stmt = $conn->prepare($sql);

                // Связываем параметр
                $stmt->bindParam(':login', $username, PDO::PARAM_STR);

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

                            // Редирект
                            header('Location: index.php');
                            exit();
                        } else {
                            $error = 'Неверный пароль';
                            error_log("Password verification failed for user: $username");
                        }
                    } else {
                        $error = 'Пользователь не найден';
                        error_log("No user found with login = $username");
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

$pageTitle = "Вход в систему";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Вход в систему</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя или Email</label>
                    <input type="text" id="username" name="username" required autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           placeholder="Введите имя пользователя или email">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>
            
            <p class="auth-link">
                Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
