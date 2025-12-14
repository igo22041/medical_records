<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';
require_once 'config/statuses.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($id <= 0) {
    header("Location: records.php");
    exit();
}

// Создаем папку для загрузки файлов если её нет
$upload_dir = __DIR__ . '/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$recordModel = new MedicalRecord();
$record = $recordModel->getById($id, $_SESSION['user_id'], isAdmin());

if (!$record) {
    header("Location: records.php");
    exit();
}

// Сообщение об успешном удалении файла
if (isset($_GET['file_deleted'])) {
    $success = 'Файл успешно удален!';
    // Обновляем данные записи после удаления файла
    $record = $recordModel->getById($id, $_SESSION['user_id'], isAdmin());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age = intval($_POST['patient_age'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $record_date = $_POST['record_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    $attachment_file = $record['attachment_file'] ?? null;
    
    // Обработка загрузки нового файла
    if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
        // Удаляем старый файл если есть
        if ($attachment_file) {
            // Извлекаем системное имя файла (после |)
            $old_file_info = explode('|', $attachment_file);
            $old_system_name = count($old_file_info) > 1 ? $old_file_info[1] : $attachment_file;
            $old_file_path = $upload_dir . $old_system_name;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
        
        $file = $_FILES['attachment_file'];
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types)) {
            // Проверка размера файла (10MB)
            if ($file['size'] > 10 * 1024 * 1024) {
                $error = 'Файл слишком большой. Максимальный размер: 10MB';
            } else {
                // Сохраняем оригинальное имя файла
                $original_name = basename($file['name']);
                $system_name = uniqid() . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $system_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Сохраняем в формате: оригинальное_имя|системное_имя
                    $attachment_file = $original_name . '|' . $system_name;
                } else {
                    $error = 'Ошибка при загрузке файла';
                }
            }
        } else {
            $error = 'Недопустимый тип файла. Разрешенные типы: ' . implode(', ', $allowed_types);
        }
    }
    
    if (empty($patient_name) || empty($diagnosis) || empty($treatment) || empty($doctor_name)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($patient_age <= 0 || $patient_age > 150) {
        $error = 'Некорректный возраст пациента';
    } elseif (empty($error)) {
        if ($recordModel->update($id, $patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $status, $_SESSION['user_id'], isAdmin(), $attachment_file)) {
            $success = 'Медицинская запись успешно обновлена!';
            // Обновляем данные записи
            $record = $recordModel->getById($id, $_SESSION['user_id'], isAdmin());
        } else {
            $error = 'Ошибка при обновлении записи';
        }
    }
}

$pageTitle = "Редактировать запись";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Редактировать медицинскую запись</h1>
        <a href="view_record.php?id=<?php echo $id; ?>" class="btn btn-secondary">Назад</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="" class="record-form" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="patient_name">Имя пациента *</label>
                    <input type="text" id="patient_name" name="patient_name" 
                           value="<?php echo htmlspecialchars($record['patient_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="patient_age">Возраст пациента *</label>
                    <input type="number" id="patient_age" name="patient_age" 
                           value="<?php echo $record['patient_age']; ?>" 
                           min="1" max="150" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="doctor_name">ФИО врача *</label>
                <input type="text" id="doctor_name" name="doctor_name" 
                       value="<?php echo htmlspecialchars($record['doctor_name']); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="record_date">Дата записи *</label>
                    <input type="date" id="record_date" name="record_date" 
                           value="<?php echo date('Y-m-d', strtotime($record['record_date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Статус *</label>
                    <select id="status" name="status" required>
                        <?php 
                        $statuses = getStatuses();
                        $current_status = $record['status'] ?? 'active';
                        foreach ($statuses as $key => $status_info): 
                        ?>
                            <option value="<?php echo $key; ?>" <?php echo $current_status === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status_info['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Диагноз *</label>
                <textarea id="diagnosis" name="diagnosis" rows="4" required><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="treatment">Лечение *</label>
                <textarea id="treatment" name="treatment" rows="4" required><?php echo htmlspecialchars($record['treatment']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="attachment_file">Прикрепленный файл</label>
                <?php if (!empty($record['attachment_file'])): ?>
                    <div class="current-file-container">
                        <div class="current-file-info">
                            <a href="download_file.php?id=<?php echo $record['id']; ?>" target="_blank" class="file-link">
                                <?php 
                                // Извлекаем оригинальное имя файла
                                $file_info = explode('|', $record['attachment_file']);
                                $display_name = count($file_info) > 1 ? $file_info[0] : $record['attachment_file'];
                                $filename = htmlspecialchars($display_name);
                                echo strlen($filename) > 50 ? substr($filename, 0, 50) . '...' : $filename;
                                ?>
                            </a>
                            <a href="delete_file.php?id=<?php echo $record['id']; ?>" 
                               class="btn btn-sm btn-danger delete-file-btn"
                               onclick="return confirm('Вы уверены, что хотите удалить этот файл?');">
                                ✕ Удалить
                            </a>
                        </div>
                        <p class="file-note">Загрузка нового файла заменит текущий</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="attachment_file" name="attachment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt">
                <small>Разрешенные форматы: PDF, DOC, DOCX, JPG, PNG, GIF, TXT (макс. размер: 10MB)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="view_record.php?id=<?php echo $id; ?>" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

