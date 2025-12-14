<?php
require_once 'config/session.php';
require_once 'models/MedicalRecord.php';
require_once 'config/statuses.php';

requireAdmin();

$patient_name = $_GET['patient'] ?? '';

if (empty($patient_name)) {
    header("Location: admin/dashboard.php");
    exit();
}

$recordModel = new MedicalRecord();
// Получаем все записи (для админа)
$all_records = $recordModel->getAll(null, true);
// Фильтруем только записи для конкретного пациента (точное совпадение)
$records = array_filter($all_records, function($record) use ($patient_name) {
    return strcasecmp(trim($record['patient_name']), trim($patient_name)) === 0;
});

if (empty($records)) {
    header("Location: admin/dashboard.php");
    exit();
}

// Сортируем записи по дате
usort($records, function($a, $b) {
    return strtotime($b['record_date']) - strtotime($a['record_date']);
});

// Проверяем, есть ли TCPDF
$use_tcpdf = class_exists('TCPDF');

if ($use_tcpdf) {
    // Используем TCPDF если доступен
    require_once __DIR__ . '/vendor/autoload.php';
    
    class PDF extends TCPDF {
        function Header() {
            $this->SetFont('dejavusans', 'B', 16);
            $this->Cell(0, 10, 'Медицинские записи', 0, 1, 'C');
            $this->SetFont('dejavusans', '', 10);
            $this->Cell(0, 5, 'Дата создания: ' . date('d.m.Y H:i'), 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('dejavusans', '', 8);
            $this->Cell(0, 10, 'Страница ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }
    
    $pdf = new PDF();
    $pdf->SetCreator('Медицинская система');
    $pdf->SetAuthor('Система управления записями');
    $pdf->SetTitle('Медицинские записи - ' . $patient_name);
    $pdf->SetSubject('Медицинские записи');
    $pdf->AddPage();
    
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(0, 10, 'Пациент: ' . $patient_name, 0, 1);
    $pdf->Ln(5);
    
    foreach ($records as $index => $record) {
        if ($index > 0) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, 'Запись #' . $record['id'], 0, 1);
        $pdf->Ln(3);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(50, 6, 'Дата записи:', 0, 0, 'L');
        $pdf->Cell(0, 6, date('d.m.Y', strtotime($record['record_date'])), 0, 1);
        
        $pdf->Cell(50, 6, 'Возраст:', 0, 0, 'L');
        $pdf->Cell(0, 6, $record['patient_age'] . ' лет', 0, 1);
        
        $pdf->Cell(50, 6, 'Врач:', 0, 0, 'L');
        $pdf->Cell(0, 6, $record['doctor_name'], 0, 1);
        
        $pdf->Cell(50, 6, 'Статус:', 0, 0, 'L');
        $pdf->Cell(0, 6, getStatusName($record['status'] ?? 'active'), 0, 1);
        
        if (isset($record['creator_name'])) {
            $pdf->Cell(50, 6, 'Создал:', 0, 0, 'L');
            $pdf->Cell(0, 6, $record['creator_name'], 0, 1);
        }
        
        $pdf->Ln(3);
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(0, 6, 'Диагноз:', 0, 1);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, $record['diagnosis'], 0, 'L');
        
        $pdf->Ln(3);
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(0, 6, 'Лечение:', 0, 1);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, $record['treatment'], 0, 'L');
    }
    
    $pdf->Output('medical_record_' . $patient_name . '_' . date('Y-m-d') . '.pdf', 'D');
} else {
    // Простой HTML-to-PDF fallback
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Медицинские записи - <?php echo htmlspecialchars($patient_name); ?></title>
        <style>
            @media print {
                @page { margin: 2cm; }
                body { margin: 0; }
                .no-print { display: none; }
            }
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
            }
            h1 {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 30px;
            }
            .patient-info {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 20px;
            }
            .record {
                page-break-inside: avoid;
                margin-bottom: 30px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .record-header {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #555;
            }
            .record-item {
                margin: 5px 0;
            }
            .record-label {
                font-weight: bold;
                display: inline-block;
                width: 120px;
            }
            .record-section {
                margin-top: 15px;
            }
            .record-section-title {
                font-weight: bold;
                font-size: 13px;
                margin-bottom: 5px;
            }
            .record-content {
                text-align: justify;
                line-height: 1.6;
            }
            .print-btn {
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                font-size: 16px;
                cursor: pointer;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .print-btn:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <button class="print-btn no-print" onclick="window.print()">Печать / Сохранить как PDF</button>
        
        <h1>Медицинские записи</h1>
        <div style="text-align: center; margin-bottom: 20px; color: #666;">
            Дата создания: <?php echo date('d.m.Y H:i'); ?>
        </div>
        
        <div class="patient-info">
            Пациент: <?php echo htmlspecialchars($patient_name); ?>
        </div>
        
        <?php foreach ($records as $record): ?>
            <div class="record">
                <div class="record-header">Запись #<?php echo $record['id']; ?></div>
                
                <div class="record-item">
                    <span class="record-label">Дата записи:</span>
                    <?php echo date('d.m.Y', strtotime($record['record_date'])); ?>
                </div>
                
                <div class="record-item">
                    <span class="record-label">Возраст:</span>
                    <?php echo $record['patient_age']; ?> лет
                </div>
                
                <div class="record-item">
                    <span class="record-label">Врач:</span>
                    <?php echo htmlspecialchars($record['doctor_name']); ?>
                </div>
                
                <div class="record-item">
                    <span class="record-label">Статус:</span>
                    <?php echo htmlspecialchars(getStatusName($record['status'] ?? 'active')); ?>
                </div>
                
                <?php if (isset($record['creator_name'])): ?>
                    <div class="record-item">
                        <span class="record-label">Создал:</span>
                        <?php echo htmlspecialchars($record['creator_name']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="record-section">
                    <div class="record-section-title">Диагноз:</div>
                    <div class="record-content"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></div>
                </div>
                
                <div class="record-section">
                    <div class="record-section-title">Лечение:</div>
                    <div class="record-content"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        

    </body>
    </html>
    <?php
}
?>

