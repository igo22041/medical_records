<?php
require_once '../config/session.php';
require_once '../models/User.php';
require_once '../models/MedicalRecord.php';
require_once '../config/statuses.php';

requireAdmin();

$userModel = new User();
$recordModel = new MedicalRecord();

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: users.php");
    exit();
}

// Получаем информацию о пользователе
$user = $userModel->getUserById($user_id);

if (!$user) {
    header("Location: users.php");
    exit();
}

$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;

$records = array();
$total_records = 0;
$total_pages = 1;

if (!empty($search_term)) {
    // Поиск с фильтром по пользователю
    $all_records = $recordModel->search($search_term, $user_id, false);
    if (!empty($status_filter)) {
        $all_records = array_filter($all_records, function($record) use ($status_filter) {
            return ($record['status'] ?? 'active') === $status_filter;
        });
    }
    $total_records = count($all_records);
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;
    $records = array_slice($all_records, $offset, $per_page);
} else {
    // Получаем записи конкретного пользователя
    $total_records = $recordModel->getTotalCountForPagination($user_id, false, $status_filter);
    $total_pages = ceil($total_records / $per_page);
    $records = $recordModel->getAllPaginated($user_id, false, $status_filter, $page, $per_page);
}

$pageTitle = "Записи пользователя: " . htmlspecialchars($user['username']);
require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Записи пользователя: <?php echo htmlspecialchars($user['username']); ?></h1>
        <div>
            <a href="users.php" class="btn btn-secondary">← Назад к пользователям</a>
            <a href="dashboard.php" class="btn btn-secondary">Панель администратора</a>
        </div>
    </div>
    
    <div class="user-info-card">
        <h3>Информация о пользователе</h3>
        <div class="user-info-grid">
            <div>
                <strong>ID:</strong> <?php echo $user['id']; ?>
            </div>
            <div>
                <strong>Имя пользователя:</strong> <?php echo htmlspecialchars($user['username']); ?>
            </div>
            <div>
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </div>
            <div>
                <strong>Роль:</strong> 
                <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                    <?php echo $user['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                </span>
            </div>
            <div>
                <strong>Всего записей:</strong> <?php echo $total_records; ?>
            </div>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Медицинские записи</h2>
        
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="text" name="search" placeholder="Поиск по записям..." 
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
                    <a href="user_records.php?user_id=<?php echo $user_id; ?>" class="btn btn-secondary">Очистить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($records)): ?>
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
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
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
                <p>Найдено записей: <?php echo $total_records; ?></p>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn">← Назад</a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?user_id=<?php echo $user_id; ?>&page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="pagination-dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="pagination-dots">...</span>
                        <?php endif; ?>
                        <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?user_id=<?php echo $user_id; ?>&page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="pagination-btn">Вперед →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.user-info-card {
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 2px solid var(--border-color);
}

.user-info-card h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.user-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.user-info-grid > div {
    padding: 0.5rem;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
    display: inline-block;
}

.role-admin {
    background-color: var(--admin-color);
    color: white;
}

.role-user {
    background-color: var(--secondary-color);
    color: white;
}
</style>

<?php require_once '../includes/footer.php'; ?>
