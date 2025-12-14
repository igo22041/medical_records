<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';
require_once 'config/statuses.php';

requireLogin();

$recordModel = new MedicalRecord();
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 9;
$records = array();
$total_records = 0;
$total_pages = 1;
$success_message = '';

if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success_message = 'Запись успешно удалена!';
}

if (!empty($search_term)) {
    $all_records = $recordModel->search($search_term, $_SESSION['user_id'], isAdmin());
    // Фильтрация по статусу после поиска
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
    $total_records = $recordModel->getTotalCountForPagination($_SESSION['user_id'], isAdmin(), $status_filter);
    $total_pages = ceil($total_records / $per_page);
    $records = $recordModel->getAllPaginated($_SESSION['user_id'], isAdmin(), $status_filter, $page, $per_page);
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
        <form method="GET" action="" class="search-form" id="searchForm">
            <input type="text" name="search" id="searchInput" placeholder="Поиск по имени пациента, диагнозу, врачу или лечению..." 
                   value="<?php echo htmlspecialchars($search_term); ?>" class="search-input">
            <select name="status" id="statusFilter" class="status-filter">
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
            <?php if (!empty($search_term) || !empty($status_filter)): ?>
                <a href="records.php" class="btn btn-secondary">Очистить</a>
            <?php endif; ?>
        </form>
    </div>
    
    <script>
        (function() {
            let searchTimeout;
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const searchForm = document.getElementById('searchForm');
            
            function submitSearch() {
                searchForm.submit();
            }
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(submitSearch, 500);
                });
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', submitSearch);
            }
        })();
    </script>
    
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
                        <p><strong>Статус:</strong> 
                            <span class="status-badge" style="background-color: <?php echo getStatusColor($record['status'] ?? 'active'); ?>">
                                <?php echo getStatusIcon($record['status'] ?? 'active'); ?> 
                                <?php echo getStatusName($record['status'] ?? 'active'); ?>
                            </span>
                        </p>
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
            <p>Найдено записей: <?php echo $total_records; ?></p>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-btn">← Назад</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-btn">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-btn"><?php echo $total_pages; ?></a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" 
                       class="pagination-btn">Вперед →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

