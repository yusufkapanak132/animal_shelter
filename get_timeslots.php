<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'No date provided']);
    exit;
}

$date = $_GET['date'];
$dayOfWeek = date('w', strtotime($date)); // 0 (Неделя) до 6 (Събота)

// Определяне на работното време
$startHour = 0;
$endHour = 0;

if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Понеделник - Петък
    $startHour = 9;
    $endHour = 17; // Последният възможен час за записване е 17:00
} elseif ($dayOfWeek == 6) { // Събота
    $startHour = 10;
    $endHour = 15; // Последният час е 15:00
} else { // Неделя
    echo json_encode(['error' => 'В неделя посещенията са само след предварителна уговорка по телефона.']);
    exit;
}

// Генериране на всички възможни часове за деня
$allSlots = [];
for ($h = $startHour; $h <= $endHour; $h++) {
    $timeString = sprintf("%02d:00:00", $h);
    $allSlots[] = $timeString;
}

// Вземане на вече заетите часове от базата данни за тази дата
$stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status = 'Планирано'");
$stmt->execute([$date]);
$bookedRecords = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Форматиране на отговора
$response = [];
foreach ($allSlots as $slot) {
    $response[] = [
        'time' => substr($slot, 0, 5), 
        'is_booked' => in_array($slot, $bookedRecords)
    ];
}

echo json_encode(['slots' => $response]);
?>