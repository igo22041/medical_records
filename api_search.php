<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';
require_once 'config/statuses.php';

// Только для авторизованных пользователей
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 9;

try {
    $recordModel = new MedicalRecord();
    $records = array();
    $total_records = 0;
    $total_pages = 1;

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

    // Форматируем записи для JSON
    $formatted_records = [];
    foreach ($records as $record) {
        $formatted_records[] = [
            'id' => $record['id'],
            'patient_name' => htmlspecialchars($record['patient_name']),
            'patient_age' => $record['patient_age'],
            'doctor_name' => htmlspecialchars($record['doctor_name']),
            'record_date' => date('d.m.Y', strtotime($record['record_date'])),
            'status' => $record['status'] ?? 'active',
            'status_name' => getStatusName($record['status'] ?? 'active'),
            'status_color' => getStatusColor($record['status'] ?? 'active'),
            'status_icon' => getStatusIcon($record['status'] ?? 'active'),
            'diagnosis' => htmlspecialchars($record['diagnosis']),
            'treatment' => htmlspecialchars($record['treatment']),
            'creator_name' => isset($record['creator_name']) ? htmlspecialchars($record['creator_name']) : null,
            'has_attachment' => !empty($record['attachment_file'])
        ];
    }

    echo json_encode([
        'success' => true,
        'records' => $formatted_records,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'has_records' => !empty($records)
    ]);

} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при выполнении поиска'
    ]);
}
