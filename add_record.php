<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $patient_age = intval($_POST['patient_age'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $record_date = $_POST['record_date'] ?? date('Y-m-d');
    
    if (empty($patient_name) || empty($diagnosis) || empty($treatment) || empty($doctor_name)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($patient_age <= 0 || $patient_age > 150) {
        $error = 'Некорректный возраст пациента';
    } else {
        $recordModel = new MedicalRecord();
        if ($recordModel->create($patient_name, $patient_age, $diagnosis, $treatment, $doctor_name, $record_date, $_SESSION['user_id'])) {
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
        <form method="POST" action="" class="record-form">
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
            
            <div class="form-group">
                <label for="record_date">Дата записи *</label>
                <input type="date" id="record_date" name="record_date" 
                       value="<?php echo htmlspecialchars($_POST['record_date'] ?? date('Y-m-d')); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="diagnosis">Диагноз *</label>
                <textarea id="diagnosis" name="diagnosis" rows="4" required><?php echo htmlspecialchars($_POST['diagnosis'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="treatment">Лечение *</label>
                <textarea id="treatment" name="treatment" rows="4" required><?php echo htmlspecialchars($_POST['treatment'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Добавить запись</button>
                <a href="records.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

