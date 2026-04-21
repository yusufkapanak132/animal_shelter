<?php
require_once 'db_connect.php';

// Уверяваме се, че сесията е стартирана
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Защита: Само логнати потребители могат да достъпят тази страница
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ==========================================
// 1. AJAX Проверка за съществуващ имейл в реално време
// ==========================================
if (isset($_GET['check_email'])) {
    header('Content-Type: application/json');
    $emailToCheck = trim($_GET['check_email']);
    
    // Търсим имейла в базата, НО изключваме текущия потребител
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$emailToCheck, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit; 
}

$success_message = '';
$error_message = '';

// Обработка на заявката за запазване на промените
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Валидация
    if (empty($full_name) || empty($email)) {
        $error_message = 'Моля, попълнете задължителните полета (Име и Имейл).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Моля, въведете валиден имейл адрес.';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = 'Телефонният номер трябва да съдържа точно 10 цифри.';
    } else {
        // Проверка дали имейлът вече е зает от друг потребител
        $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmailStmt->execute([$email, $user_id]);
        
        if ($checkEmailStmt->fetch()) {
            $error_message = 'Този имейл адрес вече се използва от друг профил!';
        } else {
            // Обновяване на данните
            try {
                // Взимаме стария имейл, за да видим дали е променен
                $oldDataStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $oldDataStmt->execute([$user_id]);
                $oldData = $oldDataStmt->fetch();

                // Ако имейлът е променен, връщаме статуса на Непотвърден
                $statusUpdate = "";
                if ($oldData['email'] !== $email) {
                    $statusUpdate = ", email_status = 'Непотвърден'";
                }

                $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? $statusUpdate WHERE id = ?");
                $updateStmt->execute([$full_name, $phone, $email, $user_id]);
                
                $success_message = 'Профилът Ви беше успешно обновен!';
                
                // Обновяваме сесията
                $_SESSION['user_name'] = $full_name; 
                
            } catch (Exception $e) {
                $error_message = "Грешка при запис в базата данни: " . $e->getMessage();
            }
        }
    }
}

// Взимаме актуалните данни на потребителя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирай профила | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; }
        .input-error { border: 2px solid #dc3545 !important; background-color: #fff8f8; }
        .input-success { border: 2px solid #28a745 !important; }
        .validation-message { font-size: 0.85rem; margin-top: 5px; display: block; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <main class="container" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; margin-top: 50px;">
        <div class="form-box" style="max-width: 500px; width: 100%; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="fas fa-user-edit" style="font-size: 3rem; color: var(--primary-color, #ff6b6b); margin-bottom: 10px;"></i>
                <h2>Редакция на профил</h2>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label><i class="fas fa-user" style="margin-right: 8px;"></i> Име и фамилия <span style="color:red;"></span></label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label><i class="fas fa-envelope" style="margin-right: 8px;"></i> Имейл адрес <span style="color:red;"></span></label>
                    <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                    <span id="emailStatus" class="validation-message"></span>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label><i class="fas fa-phone" style="margin-right: 8px;"></i> Телефон</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="089 123 4567" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" oninput="this.value=this.value.replace(/[^0-9]/g,'');" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 10px;">
                    <button type="submit" id="submitBtn" name="update_profile" class="btn btn-primary" style="flex: 1; padding: 12px; border-radius: 5px;">Запази</button>
                    <a href="profile.php" class="btn btn-outline" style="flex: 1; padding: 12px; border-radius: 5px; text-align: center; text-decoration: none; border: 1px solid #ddd; color: #555;">Назад</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        const editEmailInput = document.getElementById('editEmail');
        const emailStatus = document.getElementById('emailStatus');
        const submitBtn = document.getElementById('submitBtn');
        const originalEmail = "<?php echo htmlspecialchars($user['email']); ?>";

        let typingTimer;
        const doneTypingInterval = 500;

        editEmailInput.addEventListener('input', function() {
            clearTimeout(typingTimer);
            const email = this.value.trim();
            
            if (email.length === 0 || !email.includes('@')) {
                editEmailInput.classList.remove('input-error', 'input-success');
                emailStatus.innerHTML = '';
                submitBtn.disabled = false;
                return;
            }

            // Ако имейлът не е променен, няма нужда от проверка
            if (email === originalEmail) {
                editEmailInput.classList.remove('input-error');
                editEmailInput.classList.add('input-success');
                emailStatus.innerHTML = '<i class="fas fa-check-circle"></i> Това е текущият Ви имейл.';
                emailStatus.className = 'validation-message text-success';
                submitBtn.disabled = false;
                return;
            }

            typingTimer = setTimeout(() => {
                fetch(`?check_email=${encodeURIComponent(email)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            editEmailInput.classList.remove('input-success');
                            editEmailInput.classList.add('input-error');
                            emailStatus.innerHTML = '<i class="fas fa-times-circle"></i> Този имейл вече се използва от друг!';
                            emailStatus.className = 'validation-message text-danger';
                            submitBtn.disabled = true; 
                        } else {
                            editEmailInput.classList.remove('input-error');
                            editEmailInput.classList.add('input-success');
                            emailStatus.innerHTML = '<i class="fas fa-check-circle"></i> Имейлът е свободен!';
                            emailStatus.className = 'validation-message text-success';
                            submitBtn.disabled = false; 
                        }
                    })
                    .catch(error => console.error('Грешка при проверката:', error));
            }, doneTypingInterval);
        });
    </script>
</body>
</html>