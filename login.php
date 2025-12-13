<?php
require_once 'config/session.php';
require_once 'models/User.php';

$error = '';
$success = '';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $user = new User();
        if ($user->login($username, $password)) {
            header("Location: index.php");
            exit();
        } else {
            $error = 'Неверное имя пользователя или пароль';
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
                    <input type="text" id="username" name="username" required autofocus>
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
            
            <div class="demo-credentials">
                <p><strong>Тестовые учетные данные:</strong></p>
                <p>Администратор: admin / admin123</p>
                <p>Пользователь: user / user123</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

