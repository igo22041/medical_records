<?php
require_once '../config/session.php';
require_once '../models/MedicalRecord.php';
require_once '../models/User.php';
require_once '../config/statuses.php';

requireAdmin();

$recordModel = new MedicalRecord();
$userModel = new User();

$totalRecords = $recordModel->getTotalCount(null, true);
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Статистика
$statsByStatus = $recordModel->getStatsByStatus();
$statsByUser = $recordModel->getStatsByUser();
$statsByDate = $recordModel->getStatsByDate(30);
$statsByAge = $recordModel->getStatsByAgeGroup();
$recentRecords = $recordModel->getRecentRecords(5);

// Получение записей с учетом фильтров
if (!empty($search_term)) {
    $allRecords = $recordModel->search($search_term, null, true);
    if (!empty($status_filter)) {
        $allRecords = array_filter($allRecords, function($record) use ($status_filter) {
            return ($record['status'] ?? 'active') === $status_filter;
        });
    }
} else {
    $allRecords = $recordModel->getAll(null, true, $status_filter);
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
    
    <!-- Статистика -->
    <div class="stats-section">
        <h2>Статистика</h2>
        
        <div class="stats-grid-admin">
            <!-- Статистика по статусам -->
            <div class="stat-card-admin">
                <h3>Распределение по статусам</h3>
                <div class="status-stats">
                    <?php 
                    $total = array_sum(array_column($statsByStatus, 'count'));
                    foreach ($statsByStatus as $stat): 
                        $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
                        $statusName = getStatusName($stat['status']);
                        $statusColor = getStatusColor($stat['status']);
                    ?>
                        <div class="status-stat-item">
                            <div class="status-stat-header">
                                <span class="status-badge-small" style="background-color: <?php echo $statusColor; ?>">
                                    <?php echo getStatusIcon($stat['status']); ?>
                                </span>
                                <span class="status-stat-name"><?php echo htmlspecialchars($statusName); ?></span>
                                <span class="status-stat-count"><?php echo $stat['count']; ?></span>
                            </div>
                            <div class="status-stat-bar">
                                <div class="status-stat-bar-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $statusColor; ?>"></div>
                            </div>
                            <div class="status-stat-percentage"><?php echo $percentage; ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Статистика по пользователям -->
            <div class="stat-card-admin">
                <h3>Активность пользователей</h3>
                <div class="user-stats">
                    <?php foreach ($statsByUser as $stat): ?>
                        <div class="user-stat-item">
                            <div class="user-stat-info">
                                <strong><?php echo htmlspecialchars($stat['username']); ?></strong>
                                <span class="user-stat-count"><?php echo $stat['record_count']; ?> записей</span>
                            </div>
                            <div class="user-stat-bar">
                                <?php 
                                $maxRecords = max(array_column($statsByUser, 'record_count'));
                                $width = $maxRecords > 0 ? ($stat['record_count'] / $maxRecords) * 100 : 0;
                                ?>
                                <div class="user-stat-bar-fill" style="width: <?php echo $width; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Статистика по возрастным группам -->
            <div class="stat-card-admin">
                <h3>Распределение по возрасту</h3>
                <div class="age-stats">
                    <?php foreach ($statsByAge as $stat): ?>
                        <div class="age-stat-item">
                            <span class="age-group"><?php echo htmlspecialchars($stat['age_group']); ?></span>
                            <span class="age-count"><?php echo $stat['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Последние записи -->
        <div class="recent-records-section">
            <h3>Последние записи</h3>
            <div class="recent-records-list">
                <?php foreach ($recentRecords as $record): ?>
                    <div class="recent-record-item">
                        <div class="recent-record-info">
                            <strong><?php echo htmlspecialchars($record['patient_name']); ?></strong>
                            <span class="recent-record-date"><?php echo date('d.m.Y H:i', strtotime($record['created_at'])); ?></span>
                        </div>
                        <div class="recent-record-meta">
                            <span class="status-badge-small" style="background-color: <?php echo getStatusColor($record['status'] ?? 'active'); ?>">
                                <?php echo getStatusIcon($record['status'] ?? 'active'); ?>
                                <?php echo getStatusName($record['status'] ?? 'active'); ?>
                            </span>
                            <span class="recent-record-creator"><?php echo htmlspecialchars($record['creator_name'] ?? 'Неизвестно'); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Управление медицинскими записями</h2>
        
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Поиск по всем записям..." 
                       value="<?php echo htmlspecialchars($search_term); ?>" class="search-input">
                <select name="status" class="status-filter">
                    <option value="">Все статусы</option>
                    <?php 
                    $statuses = getStatuses();
                    foreach ($statuses as $key => $status_info): 
                    ?>
                        <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status_info['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Поиск</button>
                <?php if (!empty($search_term) || !empty($status_filter)): ?>
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
                            <th>Статус</th>
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
                                <td>
                                    <span class="status-badge-small" style="background-color: <?php echo getStatusColor($record['status'] ?? 'active'); ?>">
                                        <?php echo getStatusIcon($record['status'] ?? 'active'); ?>
                                        <?php echo getStatusName($record['status'] ?? 'active'); ?>
                                    </span>
                                </td>
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

