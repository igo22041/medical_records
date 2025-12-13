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

$recordModel = new MedicalRecord();
$record = $recordModel->getById($id, $_SESSION['user_id'], isAdmin());

if (!$record) {
    header("Location: records.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age = intval($_POST['patient_age'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $record_date = $_POST['record_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    
    if (empty($patient_name) || empty($diagnosis) || empty($treatment) || empty($doctor_name)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($patient_age <= 0 || $patient_age > 150) {
        $error = 'Некорректный возраст пациента';
    } else {
        if ($recordModel->update($id, $patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $status, $_SESSION['user_id'], isAdmin())) {
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
        <form method="POST" action="" class="record-form">
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
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="view_record.php?id=<?php echo $id; ?>" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

