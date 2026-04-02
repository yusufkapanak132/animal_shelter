<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'db_connect.php';

// Уверяваме се, че сесията е стартирана
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['cancel']) || ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['step']))) {
    unset($_SESSION['reset_step'], $_SESSION['reset_email']);
    
    if (isset($_GET['cancel'])) {
        header("Location: forgot_password.php");
        exit;
    }
}
// ==========================================

$error = '';
$success = '';
$testCodeMessage = ''; // Само за локално тестване

// Проверяваме на коя стъпка сме
$step = $_SESSION['reset_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // СТЪПКА 1: Изпращане на имейл и генериране на 6-цифрен код
    if ($step === 1 && isset($_POST['submit_email'])) {
        $email = trim($_POST['email'] ?? '');
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $code = sprintf("%06d", mt_rand(1, 999999));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes')); 
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?");
            $stmt->execute([$code, $email]);    
            
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_step'] = 2;
            
            // Настройки за реално пращане с PHPMailer
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
                $mail->addAddress($email);                                  

                $mail->CharSet = 'UTF-8';
                $mail->isHTML(true);                                  
                $mail->Subject = 'Код за възстановяване на парола';
                $mail->Body    = "Здравейте,<br><br>Вашият код за възстановяване е: <b>{$code}</b><br><br>Кодът е валиден 15 минути.";
                $mail->AltBody = "Вашият код за възстановяване е: {$code}. Кодът е валиден 15 минути.";

                $mail->send();
            } catch (Exception $e) {
                $error = "Имейлът не може да бъде изпратен. Грешка: {$mail->ErrorInfo}";
            }
            
            
            header("Location: forgot_password.php?step=2");
            exit;
        } else {
            $error = 'Ако този имейл съществува в нашата система, ще получите код за възстановяване.';
        }
    }
    
    // СТЪПКА 2: Проверка на 6-цифрения код
    elseif ($step === 2 && isset($_POST['submit_code'])) {
        $enteredCode = trim($_POST['code'] ?? '');
        $email = $_SESSION['reset_email'];
        
        $stmt = $pdo->prepare("SELECT reset_token FROM users WHERE email = ? AND reset_token_expires_at > NOW()");
        $stmt->execute([$email]);
        $dbRecord = $stmt->fetch();
        
        if ($dbRecord && $dbRecord['reset_token'] === $enteredCode) {
            $_SESSION['reset_step'] = 3;
         
            header("Location: forgot_password.php?step=3");
            exit;
        } else {
            $error = 'Грешен или изтекъл код! Моля, опитайте отново.';
        }
    }
    
    // СТЪПКА 3: Въвеждане на нова парола
    elseif ($step === 3 && isset($_POST['submit_password'])) {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'];

        if (strlen($password) < 8) {
            $error = 'Паролата трябва да е поне 8 символа!';
        } elseif ($password !== $confirmPassword) {
            $error = 'Паролите не съвпадат!';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            unset($_SESSION['reset_step'], $_SESSION['reset_email']);
            
            $success = 'Паролата е успешно променена! Вече можете да влезете.';
            $step = 4; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Възстановяване на парола</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f4;">

    <div class="form-box" style="max-width: 450px; width: 100%; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <i class="fas fa-key" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 10px;"></i>
            <h2 class="section-title">Забравена парола</h2>
        </div>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['test_code_msg'])): ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ffeeba;">
                <?php echo $_SESSION['test_code_msg']; unset($_SESSION['test_code_msg']); ?>
            </div>
        <?php elseif ($testCodeMessage): ?>
            <?php $_SESSION['test_code_msg'] = $testCodeMessage; ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #ffeeba;">
                <?php echo $testCodeMessage; ?>
            </div>
        <?php endif; ?>


        <?php if ($step === 1): ?>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">Въведете Вашия имейл, за да Ви изпратим 6-цифрен код за достъп.</p>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Имейл адрес</label>
                    <input type="email" name="email" class="form-control" required placeholder="вашият@email.com">
                </div>
                <button type="submit" name="submit_email" class="btn btn-primary" style="width: 100%; padding: 12px;">Изпрати код</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="login.php" style="color: var(--primary-color);"><i class="fas fa-arrow-left"></i> Назад към Вход</a>
            </div>

        <?php elseif ($step === 2): ?>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">
                Изпратихме 6-цифрен код на <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Въведете кода</label>
                    <input type="text" name="code" class="form-control" required placeholder="123456" maxlength="6" pattern="[0-9]{6}" style="text-align: center; font-size: 1.5rem; letter-spacing: 5px;">
                </div>
                <button type="submit" name="submit_code" class="btn btn-primary" style="width: 100%; padding: 12px;">Потвърди кода</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="?cancel=1" style="color: #FF6B8B;">Сгрешен имейл? Започни отначало</a>
            </div>

        <?php elseif ($step === 3): ?>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">Кодът е приет! Моля, въведете Вашата нова парола.</p>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Нова парола</label>
                    <input type="password" name="password" class="form-control" required placeholder="Минимум 8 символа">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Повторете паролата</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Повторете паролата">
                </div>
                <button type="submit" name="submit_password" class="btn btn-primary" style="width: 100%; padding: 12px;">Запази новата парола</button>
            </form>

        <?php elseif ($step === 4): ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb;">
                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                <?php echo $success; ?>
            </div>
            <a href="login.php" class="btn btn-primary" style="display: block; text-align: center; padding: 12px;">Към страницата за вход</a>
        <?php endif; ?>

    </div>
</body>
</html> 