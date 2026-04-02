<?php
// Зареждаме PHPMailer преди всичко останало
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db_connect.php';

// Уверяваме се, че сесията е стартирана
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Защита: Само логнати потребители могат да потвърждават имейла си
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['cancel']) || ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['step']))) {
    unset($_SESSION['verify_step'], $_SESSION['activation_code']);
    
    if (isset($_GET['cancel'])) {
        header("Location: verify_email.php");
        exit;
    }
}
// ==========================================

$error = '';
$success = '';

// Взимаме текущите данни за потребителя
$stmt = $pdo->prepare("SELECT email, IFNULL(email_status, 'Непотвърден') as email_status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$step = $_SESSION['verify_step'] ?? 1;

if ($user['email_status'] === 'Потвърден') {
    $step = 3;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // СТЪПКА 1 (или препращане на код): Генериране и изпращане на 6-цифрен код
    if (isset($_POST['send_code'])) {
        $code = rand(100000, 999999);
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';                     
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'yusuf.kapanak@pmggd.bg';                     
            $mail->Password   = 'oqpyqizclmicbylp';                     
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
            $mail->Port       = 587;                                    

            $mail->setFrom('yusuf.kapanak@pmggd.bg', 'Приют Надежда'); 
            $mail->addAddress($user['email']);                                  

            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);                                  
            $mail->Subject = 'Код за потвърждение на имейл | Приют Надежда';
            $mail->Body    = "Здравейте,<br><br>Вашият код за потвърждение е: <b style='font-size: 20px;'>{$code}</b><br><br>Ако не сте заявявали това, моля игнорирайте съобщението.";
            $mail->AltBody = "Здравейте,\n\nВашият код за потвърждение е: {$code}\n\nАко не сте заявявали това, моля игнорирайте съобщението.";

            $mail->send();
            
            // Записваме в сесията и преминаваме на Стъпка 2
            $_SESSION['activation_code'] = $code;
            $_SESSION['verify_step'] = 2;
            
            header("Location: verify_email.php?step=2");
            exit;
            
        } catch (Exception $e) {
            $error = "Имейлът не може да бъде изпратен. Грешка: {$mail->ErrorInfo}";
        }
    }
    
    // СТЪПКА 2: Проверка на въведения код
    elseif ($step === 2 && isset($_POST['verify_code'])) {
        $entered_code = trim($_POST['code'] ?? '');

        if (isset($_SESSION['activation_code']) && $entered_code == $_SESSION['activation_code']) {
        // Обновяваме статуса в базата.
            $updateStmt = $pdo->prepare("UPDATE users SET email_status = 'Потвърден' WHERE id = ?");
            $updateStmt->execute([$user_id]);
            
            // Изчистваме сесията за кода и стъпката
            unset($_SESSION['activation_code'], $_SESSION['verify_step']);
            
            // Отиваме на Стъпка 3 (Успех)
            header("Location: verify_email.php?step=3");
            exit;
        } else {
            $error = "Грешен код. Моля, проверете имейла си и опитайте отново.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Потвърди Имейл | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { max-width: 450px; width: 100%; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        
        /* Статуси */
        .status-badge { display: inline-block; padding: 8px 15px; border-radius: 20px; font-weight: bold; margin: 15px 0; font-size: 0.9rem;}
        .status-confirmed { background: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .status-unconfirmed { background: #fff3cd; color: #856404; border: 1px solid #ffeeba;}
        
        /* Полета за въвеждане */
        .form-control { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; text-align: center; font-size: 1.2rem; letter-spacing: 2px; box-sizing: border-box;}
        
        /* Бутони */
        .btn { padding: 12px 20px; border-radius: 5px; cursor: pointer; border: none; width: 100%; font-size: 1rem; font-weight: bold; transition: 0.3s; display: inline-block; text-decoration: none; box-sizing: border-box;}
        .btn-primary { background: var(--primary-color, #ff6b6b); color: white; margin-bottom: 10px; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-success { background: #28a745; color: white; margin-bottom: 10px; }
        .btn-success:hover { background: #218838; }
        .btn-outline { background: transparent; color: #555; border: 1px solid #ccc; margin-bottom: 10px; }
        .btn-outline:hover { background: #f8f9fa; color: #333; }
        .btn-link { background: none; color: #007bff; border: none; text-decoration: underline; font-size: 0.9rem; margin-top: 10px; padding: 5px; cursor: pointer;}
        .btn-link:hover { color: #0056b3; }
        
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <div class="box">
        <div style="margin-bottom: 20px;">
            <i class="fas fa-envelope-open-text" style="font-size: 3rem; color: var(--primary-color, #ff6b6b); margin-bottom: 10px;"></i>
            <h2 style="margin: 0; color: #333;">Потвърждение на Имейл</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <p style="margin: 0; color: #777; font-size: 0.9rem;">Регистриран имейл адрес:</p>
        <h3 style="margin: 5px 0 15px 0; color: #333; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></h3>


        <?php if ($step === 1): ?>
            <div class="status-badge status-unconfirmed">
                <i class="fas fa-exclamation-triangle"></i> Статус: Непотвърден
            </div>
            
            <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">За да имате пълен достъп до профила си, е необходимо да потвърдите имейл адреса си. Ще Ви изпратим 6-цифрен код.</p>
            
            <form method="POST">
                <button type="submit" name="send_code" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Изпрати код за потвърждение</button>
            </form>
            <a href="profile.php" class="btn btn-outline" style="margin-top: 5px;">По-късно</a>

        <?php elseif ($step === 2): ?>
            <div class="status-badge status-unconfirmed">
                <i class="fas fa-paper-plane"></i> Кодът е изпратен!
            </div>
            
            <p style="color: #666; margin-bottom: 20px;">Моля, проверете пощата си и въведете 6-цифрения код по-долу.</p>
            
            <form method="POST" style="background: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 15px;">
                <input type="text" name="code" class="form-control" placeholder="123456" required maxlength="6" pattern="[0-9]{6}">
                <button type="submit" name="verify_code" class="btn btn-success"><i class="fas fa-check"></i> Потвърди имейла</button>
            </form>
            
            <div style="margin-top: 15px;">
                <a href="?cancel=1" style="color: #FF6B8B; text-decoration: none; font-size: 0.9rem;"> Не сте получили код? Започни отначало</a>
            </div>


        <?php elseif ($step === 3): ?>
            <div class="status-badge status-confirmed" style="padding: 15px 20px; font-size: 1.1rem; width: 100%; box-sizing: border-box;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; display: block; margin-bottom: 10px;"></i>
                Статус: Успешно потвърден!
            </div>
            
            <p style="color: #666; margin: 20px 0;">Благодарим Ви! Вашият имейл адрес беше успешно верифициран.</p>
            
            <a href="profile.php" class="btn btn-primary"><i class="fas fa-user"></i> Към Профила</a>
        <?php endif; ?>

    </div>

</body>
</html>