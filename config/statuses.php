<?php
// Статусы медицинских записей
function getStatuses() {
    return [
        'active' => [
            'name' => 'Активная',
            'color' => '#3b82f6',
            'icon' => '●'
        ],
        'in_treatment' => [
            'name' => 'На лечении',
            'color' => '#f59e0b',
            'icon' => '◉'
        ],
        'recovered' => [
            'name' => 'Выздоровел',
            'color' => '#10b981',
            'icon' => '✓'
        ],
        'chronic' => [
            'name' => 'Хроническая',
            'color' => '#8b5cf6',
            'icon' => '◐'
        ],
        'cancelled' => [
            'name' => 'Отменена',
            'color' => '#ef4444',
            'icon' => '✕'
        ]
    ];
}

function getStatusName($status) {
    $statuses = getStatuses();
    return isset($statuses[$status]) ? $statuses[$status]['name'] : $status;
}

function getStatusColor($status) {
    $statuses = getStatuses();
    return isset($statuses[$status]) ? $statuses[$status]['color'] : '#6b7280';
}

function getStatusIcon($status) {
    $statuses = getStatuses();
    return isset($statuses[$status]) ? $statuses[$status]['icon'] : '●';
}
?>



