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
    header("Location: records.php");
    exit();
}

$upload_dir = __DIR__ . '/uploads/';

// Извлекаем оригинальное и системное имя файла
$file_info = explode('|', $record['attachment_file']);
$original_name = count($file_info) > 1 ? $file_info[0] : $record['attachment_file'];
$system_name = count($file_info) > 1 ? $file_info[1] : $record['attachment_file'];
$file_path = $upload_dir . $system_name;

if (!file_exists($file_path)) {
    header("Location: records.php");
    exit();
}

// Определяем MIME-тип файла по расширению оригинального имени
$file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
$mime_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'txt' => 'text/plain'
];

$mime_type = $mime_types[$file_ext] ?? 'application/octet-stream';

// Отправляем файл с оригинальным именем
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . htmlspecialchars($original_name, ENT_QUOTES, 'UTF-8') . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
readfile($file_path);
exit();
