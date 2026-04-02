<?php
// process_adoption.php
require_once 'db_connect.php';

// Проверяваме дали потребителят е логнат
if (!isLoggedIn()) {
    // Ако не е логнат, запазваме къде се е опитал да осинови
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Проверяваме дали има име на животно в POST заявката
    if (isset($_POST['animal_name'])) {
        $_SESSION['animal_to_adopt'] = $_POST['animal_name'];
    }
    
    // Пренасочваме към логин страница
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animalName = $_POST['animal_name'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $otherPets = $_POST['other_pets'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Валидация
    if (empty($animalName) || empty($fullName) || empty($email) || empty($phone) || empty($message)) {
        $_SESSION['adoption_error'] = 'Моля, попълнете всички задължителни полета!';
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'animals.php');
        exit;
    }
    
    // Валидация на имейл
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['adoption_error'] = 'Моля, въведете валиден имейл адрес!';
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'animals.php');
        exit;
    }
    
    // Валидация на телефон 
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strlen($phone) < 9) {
        $_SESSION['adoption_error'] = 'Моля, въведете валиден телефонен номер (поне 9 цифри)!';
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'animals.php');
        exit;
    }
    
    try {
        // Вземаме ID на потребителя
        $userId = $_SESSION['user_id'];
        
        // Вземаме информация за текущия потребител от базата данни
        $userStmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        // Ако потребителят не е попълнил имената/имейла/телефона във формата,
        // използваме данните от профила му
        if (empty($fullName) && !empty($user['full_name'])) {
            $fullName = $user['full_name'];
        }
        
        if (empty($email) && !empty($user['email'])) {
            $email = $user['email'];
        }
        
        if (empty($phone) && !empty($user['phone'])) {
            $phone = $user['phone'];
        }
        
        // Записваме заявката за осиновяване в базата данни
        $stmt = $pdo->prepare("INSERT INTO adoptions (user_id, animal_name, full_name, email, phone, other_pets, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Изчаква се')");
        $stmt->execute([$userId, $animalName, $fullName, $email, $phone, $otherPets, $message]);
        
        $adoptionId = $pdo->lastInsertId();
        
        $_SESSION['last_adoption'] = [
            'id' => $adoptionId,
            'animal_name' => $animalName,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'other_pets' => $otherPets,
            'message' => $message,
            'date' => date('d.m.Y H:i:s'),
            'user_id' => $userId
        ];
        
        // Ако има грешка в сесията от преди, я изчистваме
        if (isset($_SESSION['adoption_error'])) {
            unset($_SESSION['adoption_error']);
        }
        
        // Пренасочване към страница за успех
        header('Location: adoption_success.php');
        exit;
        
    } catch (Exception $e) {
        // Записваме грешката в сесията
        $_SESSION['adoption_error'] = 'Грешка при изпращане на заявката: ' . $e->getMessage();
        
        // Връщаме потребителя обратно към формата
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'animals.php');
        exit;
    }
} else {
    // Ако някой опита да достъпи страницата директно
    header('Location: animals.php');
    exit;
}
?>