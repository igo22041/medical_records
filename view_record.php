<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';
require_once 'config/statuses.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: records.php");
    exit();
}

$recordModel = new MedicalRecord();
$record = $recordModel->getById($id, $_SESSION['user_id'], isAdmin());

if (!$record) {
    header("Location: records.php");
    exit();
}

$pageTitle = "Просмотр записи";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Просмотр медицинской записи</h1>
        <a href="records.php" class="btn btn-secondary">Назад к записям</a>
    </div>
    
    <div class="record-detail">
        <div class="record-detail-header">
            <h2><?php echo htmlspecialchars($record['patient_name']); ?></h2>
            <span class="record-age"><?php echo $record['patient_age']; ?> лет</span>
        </div>
        
        <div class="record-detail-info">
            <div class="info-item">
                <strong>Врач:</strong>
                <span><?php echo htmlspecialchars($record['doctor_name']); ?></span>
            </div>
            
            <div class="info-item">
                <strong>Дата записи:</strong>
                <span><?php echo date('d.m.Y', strtotime($record['record_date'])); ?></span>
            </div>
            
            <div class="info-item">
                <strong>Статус:</strong>
                <span class="status-badge" style="background-color: <?php echo getStatusColor($record['status'] ?? 'active'); ?>">
                    <?php echo getStatusIcon($record['status'] ?? 'active'); ?> 
                    <?php echo getStatusName($record['status'] ?? 'active'); ?>
                </span>
            </div>
            
            <?php if (isAdmin() && isset($record['creator_name'])): ?>
                <div class="info-item">
                    <strong>Создал:</strong>
                    <span><?php echo htmlspecialchars($record['creator_name']); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-item">
                <strong>Дата создания:</strong>
                <span><?php echo date('d.m.Y H:i', strtotime($record['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="record-detail-content">
            <div class="content-section">
                <h3>Диагноз</h3>
                <p><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
            </div>
            
            <div class="content-section">
                <h3>Лечение</h3>
                <p><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
            </div>
            
            <?php if (!empty($record['attachment_file'])): ?>
                <div class="content-section">
                    <h3>Прикрепленный файл</h3>
                    <div class="attachment-file">
                        <a href="download_file.php?id=<?php echo $record['id']; ?>" class="file-download-link" target="_blank">
                            <span class="file-name">
                                <?php 
                                // Извлекаем оригинальное имя файла
                                $file_info = explode('|', $record['attachment_file']);
                                $display_name = count($file_info) > 1 ? $file_info[0] : $record['attachment_file'];
                                echo htmlspecialchars($display_name);
                                ?>
                            </span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="record-detail-actions">
            <?php if (isAdmin()): ?>
                <a href="export_pdf.php?patient=<?php echo urlencode($record['patient_name']); ?>" class="btn btn-primary">Экспорт в PDF</a>
            <?php endif; ?>
            <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-primary">Редактировать</a>
            <a href="delete_record.php?id=<?php echo $record['id']; ?>" 
               class="btn btn-danger" 
               onclick="return confirm('Вы уверены, что хотите удалить эту запись?');">Удалить</a>
            <a href="records.php" class="btn btn-secondary">Назад</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

