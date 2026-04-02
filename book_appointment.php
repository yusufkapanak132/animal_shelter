<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Неоторизиран достъп']);
    exit;
}

$adoption_id = $_POST['adoption_id'] ?? null;
$date = $_POST['appointment_date'] ?? null;
$time = $_POST['appointment_time'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$adoption_id || !$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'Моля, попълнете всички полета.']);
    exit;
}

// Проверка дали часът вече не е зает междувременно
$checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status = 'Планирано'");
$checkStmt->execute([$date, $time . ':00']);

if ($checkStmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Този час току-що беше зает от друг. Моля, изберете нов.']);
    exit;
}

// Записване на часа
try {
    $stmt = $pdo->prepare("INSERT INTO appointments (adoption_id, user_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([$adoption_id, $user_id, $date, $time . ':00']);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Грешка при запис: ' . $e->getMessage()]);
}
?>