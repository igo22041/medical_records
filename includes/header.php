<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Система управления медицинскими записями'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="/index.php">Медицинские записи</a>
            </div>
            <button class="burger-menu-toggle" id="burgerToggle" aria-label="Меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <?php if (isLoggedIn()): ?>
                    <li><a href="/index.php">Главная</a></li>
                    <li><a href="/records.php"><?php echo isAdmin() ? 'Все записи' : 'Мои записи'; ?></a></li>
                    <li><a href="/add_record.php">Добавить запись</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="/admin/dashboard.php">Панель администратора</a></li>
                    <?php endif; ?>
                    <li><a href="/logout.php">Выход (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Вход</a></li>
                    <li><a href="/register.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <script>
        (function() {
            const burgerToggle = document.getElementById('burgerToggle');
            const navMenu = document.getElementById('navMenu');
            
            if (burgerToggle && navMenu) {
                burgerToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    burgerToggle.classList.toggle('active');
                });
                
                // Закрываем меню при клике на ссылку
                navMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('active');
                        burgerToggle.classList.remove('active');
                    });
                });
                
                // Закрываем меню при клике вне его
                document.addEventListener('click', function(e) {
                    if (!navMenu.contains(e.target) && !burgerToggle.contains(e.target)) {
                        navMenu.classList.remove('active');
                        burgerToggle.classList.remove('active');
                    }
                });
            }
        })();
    </script>
    <main class="main-content">

