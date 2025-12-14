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

if (!$record || empty($record['attachment_file'])) {
    header("Location: view_record.php?id=" . $id);
    exit();
}

// Удаляем файл
$upload_dir = __DIR__ . '/uploads/';
// Извлекаем системное имя файла (после |)
$file_info = explode('|', $record['attachment_file']);
$system_name = count($file_info) > 1 ? $file_info[1] : $record['attachment_file'];
$file_path = $upload_dir . $system_name;
if (file_exists($file_path)) {
    unlink($file_path);
}

// Обновляем запись, удаляя имя файла
$recordModel->update(
    $id,
    $record['patient_name'],
    $record['patient_age'],
    $record['diagnosis'],
    $record['treatment'],
    $record['doctor_name'],
    $record['record_date'],
    $record['status'] ?? 'active',
    $_SESSION['user_id'],
    isAdmin(),
    null,
    true // clear_file = true для установки attachment_file в NULL
);

header("Location: edit_record.php?id=" . $id . "&file_deleted=1");
exit();


