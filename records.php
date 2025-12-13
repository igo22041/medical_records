<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';

requireLogin();

$recordModel = new MedicalRecord();
$search_term = $_GET['search'] ?? '';
$records = array();
$success_message = '';

if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success_message = 'Запись успешно удалена!';
}

if (!empty($search_term)) {
    $records = $recordModel->search($search_term, $_SESSION['user_id'], isAdmin());
} else {
    $records = $recordModel->getAll($_SESSION['user_id'], isAdmin());
}

$pageTitle = "Медицинские записи";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Медицинские записи</h1>
        <a href="add_record.php" class="btn btn-primary">Добавить запись</a>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <div class="search-section">
        <form method="GET" action="" class="search-form">
            <input type="text" name="search" placeholder="Поиск по имени пациента, диагнозу, врачу или лечению..." 
                   value="<?php echo htmlspecialchars($search_term); ?>" class="search-input">
            <button type="submit" class="btn btn-primary">Поиск</button>
            <?php if (!empty($search_term)): ?>
                <a href="records.php" class="btn btn-secondary">Очистить</a>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if (empty($records)): ?>
        <div class="empty-state">
            <p><?php echo !empty($search_term) ? 'Записи не найдены' : 'У вас пока нет медицинских записей'; ?></p>
            <a href="add_record.php" class="btn btn-primary">Добавить первую запись</a>
        </div>
    <?php else: ?>
        <div class="records-grid">
            <?php foreach ($records as $record): ?>
                <div class="record-card">
                    <div class="record-header">
                        <h3><?php echo htmlspecialchars($record['patient_name']); ?></h3>
                        <span class="record-age">Возраст:<?php echo $record['patient_age']; ?></span>
                    </div>
                    
                    <div class="record-info">
                        <p><strong>Врач:</strong> <?php echo htmlspecialchars($record['doctor_name']); ?></p>
                        <p><strong>Дата:</strong> <?php echo date('d.m.Y', strtotime($record['record_date'])); ?></p>
                        <?php if (isAdmin() && isset($record['creator_name'])): ?>
                            <p><strong>Создал:</strong> <?php echo htmlspecialchars($record['creator_name']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="record-content">
                        <p><strong>Диагноз:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                        
                        <p><strong>Лечение:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                    </div>
                    
                    <div class="record-actions">
                        <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">Просмотр</a>
                        <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-secondary">Редактировать</a>
                        <a href="delete_record.php?id=<?php echo $record['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="records-count">
            <p>Найдено записей: <?php echo count($records); ?></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

