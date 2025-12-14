<?php
require_once 'config/session.php';
require_once 'config/database.php';

// Проверка авторизации ДО вывода HTML
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$pageTitle = "Главная страница";
require_once 'includes/header.php';

require_once 'models/MedicalRecord.php';
$recordModel = new MedicalRecord();
$totalRecords = $recordModel->getTotalCount($_SESSION['user_id'], isAdmin());
?>

<div class="container">
    <div class="welcome-section">
        <h1>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p class="subtitle">Система управления медицинскими записями</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $totalRecords; ?></h3>
                <p>Всего записей</p>
            </div>
            <div class="stat-card">
                <h3><?php echo isAdmin() ? 'Администратор' : 'Пользователь'; ?></h3>
                <p>Ваша роль</p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add_record.php" class="btn btn-primary">Добавить новую запись</a>
            <a href="records.php" class="btn btn-secondary">Просмотреть все записи</a>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="btn btn-admin">Панель администратора</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

