<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
    // Удаляем связанный файл если есть
    if (!empty($record['attachment_file'])) {
        $upload_dir = __DIR__ . '/uploads/';
        // Извлекаем системное имя файла (после |)
        $file_info = explode('|', $record['attachment_file']);
        $system_name = count($file_info) > 1 ? $file_info[1] : $record['attachment_file'];
        $file_path = $upload_dir . $system_name;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    if ($recordModel->delete($id, $_SESSION['user_id'], isAdmin())) {
        header("Location: records.php?deleted=1");
        exit();
    } else {
        $error = 'Ошибка при удалении записи';
    }
}

$pageTitle = "Удалить запись";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Удалить медицинскую запись</h1>
        <a href="view_record.php?id=<?php echo $id; ?>" class="btn btn-secondary">Назад</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="delete-confirmation">
        <div class="alert alert-warning">
            <h3>Вы уверены, что хотите удалить эту запись?</h3>
            <p>Это действие нельзя отменить.</p>
        </div>
        
        <div class="record-preview">
            <h4>Информация о записи:</h4>
            <p><strong>Пациент:</strong> <?php echo htmlspecialchars($record['patient_name']); ?></p>
            <p><strong>Возраст:</strong> <?php echo $record['patient_age']; ?> лет</p>
            <p><strong>Врач:</strong> <?php echo htmlspecialchars($record['doctor_name']); ?></p>
            <p><strong>Дата:</strong> <?php echo date('d.m.Y', strtotime($record['record_date'])); ?></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-actions">
                <button type="submit" name="confirm" value="1" class="btn btn-danger">Да, удалить</button>
                <a href="view_record.php?id=<?php echo $id; ?>" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

