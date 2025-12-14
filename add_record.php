<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';
require_once 'config/statuses.php';

requireLogin();

$error = '';
$success = '';

// Создаем папку для загрузки файлов если её нет
$upload_dir = __DIR__ . '/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age = intval($_POST['patient_age'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $record_date = $_POST['record_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    $attachment_file = null;
    
    // Обработка загрузки файла
    if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment_file'];
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_types)) {
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
        } else {
            $error = 'Недопустимый тип файла. Разрешенные типы: ' . implode(', ', $allowed_types);
        }
    }
    
    if (empty($patient_name) || empty($diagnosis) || empty($treatment) || empty($doctor_name)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($patient_age <= 0 || $patient_age > 150) {
        $error = 'Некорректный возраст пациента';
    } elseif (empty($error)) {
        $recordModel = new MedicalRecord();
        if ($recordModel->create($patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $status, $_SESSION['user_id'], $attachment_file)) {
            $success = 'Медицинская запись успешно добавлена!';
            // Очистка формы
            $_POST = array();
        } else {
            $error = 'Ошибка при добавлении записи';
        }
    }
}

$pageTitle = "Добавить медицинскую запись";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Добавить медицинскую запись</h1>
        <a href="records.php" class="btn btn-secondary">Назад к записям</a>
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
                           value="<?php echo htmlspecialchars($_POST['patient_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="patient_age">Возраст пациента *</label>
                    <input type="number" id="patient_age" name="patient_age" 
                           value="<?php echo htmlspecialchars($_POST['patient_age'] ?? ''); ?>" 
                           min="1" max="150" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="doctor_name">ФИО врача *</label>
                <input type="text" id="doctor_name" name="doctor_name" 
                       value="<?php echo htmlspecialchars($_POST['doctor_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="record_date">Дата записи *</label>
                    <input type="date" id="record_date" name="record_date" 
                           value="<?php echo htmlspecialchars($_POST['record_date'] ?? date('Y-m-d')); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Статус *</label>
                    <select id="status" name="status" required>
                        <?php 
                        $statuses = getStatuses();
                        $selected_status = $_POST['status'] ?? 'active';
                        foreach ($statuses as $key => $status_info): 
                        ?>
                            <option value="<?php echo $key; ?>" <?php echo $selected_status === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status_info['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Диагноз *</label>
                <textarea id="diagnosis" name="diagnosis" rows="4" required><?php echo htmlspecialchars($_POST['diagnosis'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="treatment">Лечение *</label>
                <textarea id="treatment" name="treatment" rows="4" required><?php echo htmlspecialchars($_POST['treatment'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="attachment_file">Прикрепить файл</label>
                <input type="file" id="attachment_file" name="attachment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt">
                <small>Разрешенные форматы: PDF, DOC, DOCX, JPG, PNG, GIF, TXT (макс. размер: 10MB)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Добавить запись</button>
                <a href="records.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

