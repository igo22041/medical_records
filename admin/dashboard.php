<?php
require_once '../config/session.php';
require_once '../models/MedicalRecord.php';
require_once '../models/User.php';

requireAdmin();

$recordModel = new MedicalRecord();
$userModel = new User();

$totalRecords = $recordModel->getTotalCount(null, true);
$allRecords = $recordModel->getAll(null, true);
$search_term = $_GET['search'] ?? '';

if (!empty($search_term)) {
    $allRecords = $recordModel->search($search_term, null, true);
}

$pageTitle = "Панель администратора";
require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Панель администратора</h1>
        <div class="admin-stats">
            <div class="stat-badge">
                <span class="stat-number"><?php echo $totalRecords; ?></span>
                <span class="stat-label">Всего записей</span>
            </div>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Управление медицинскими записями</h2>
        
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Поиск по всем записям..." 
                       value="<?php echo htmlspecialchars($search_term); ?>" class="search-input">
                <button type="submit" class="btn btn-primary">Поиск</button>
                <?php if (!empty($search_term)): ?>
                    <a href="dashboard.php" class="btn btn-secondary">Очистить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($allRecords)): ?>
            <div class="empty-state">
                <p>Записи не найдены</p>
            </div>
        <?php else: ?>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пациент</th>
                            <th>Возраст</th>
                            <th>Врач</th>
                            <th>Дата записи</th>
                            <th>Создал</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRecords as $record): ?>
                            <tr>
                                <td><?php echo $record['id']; ?></td>
                                <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                                <td><?php echo $record['patient_age']; ?></td>
                                <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($record['record_date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['creator_name'] ?? 'Неизвестно'); ?></td>
                                <td class="action-buttons">
                                    <a href="../view_record.php?id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-primary">Просмотр</a>
                                    <a href="../edit_record.php?id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-secondary">Редактировать</a>
                                    <a href="../delete_record.php?id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="records-count">
                <p>Найдено записей: <?php echo count($allRecords); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

