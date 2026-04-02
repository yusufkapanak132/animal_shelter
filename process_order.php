<?php
// process_order.php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'db_connect.php';

// Уверяваме се, че сесията е стартирана
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Таен ключ за генериране на сигурни линкове 
define('ORDER_SECRET_KEY', 'NadejdaPriutSecret2024!@');
// Фиксирана цена за доставка
define('SHIPPING_COST', 5.00);

// Помощна функция за сигурност при извеждане на данни
function safe_escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Помощна функция за изпращане на имейли
function sendStoreEmail($to, $subject, $htmlBody) {
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
        $mail->addAddress($to);
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Грешка при изпращане: {$mail->ErrorInfo}";
    }
}

// Помощна функция за генериране на HTML таблица с продуктите
function generateOrderTableHTML($items, $totalAmount) {
    $grandTotal = $totalAmount + SHIPPING_COST; // Крайната сума за плащане
    
    $html = '<table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-family: sans-serif;">';
    $html .= '<thead><tr style="background-color: #f2f2f2;">';
    $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Продукт</th>';
    $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: center;">Количество</th>';
    $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Ед. цена</th>';
    $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Общо</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($items as $item) {
        $productName = $item['product_name'] ?? $item['name'] ?? 'Неизвестен артикул';
        $lineTotal = floatval($item['price']) * intval($item['quantity']);
        
        $html .= '<tr>';
        $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . safe_escape($productName) . '</td>';
        $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . intval($item['quantity']) . '</td>';
        $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . number_format($item['price'], 2) . ' €</td>';
        $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . number_format($lineTotal, 2) . ' €</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody><tfoot>';
    
    // Сума само на продуктите
    $html .= '<tr><td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>Сума продукти:</strong></td>';
    $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . number_format($totalAmount, 2) . ' €</td></tr>';
    
    // Цена за доставка
    $html .= '<tr><td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>Доставка (Еконт):</strong></td>';
    $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . number_format(SHIPPING_COST, 2) . ' €</td></tr>';
    
    // Крайна сума (Продукти + Доставка)
    $html .= '<tr><td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>Крайна сума:</strong></td>';
    $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #2ecc71; font-size: 1.1em;"><strong>' . number_format($grandTotal, 2) . ' €</strong></td></tr>';
    
    $html .= '</tfoot></table>';
    
    return $html;
}

// ======================================================================
// СТЪПКА 3: ОБРАБОТКА НА ПОТВЪРЖДЕНИЕ ПРЕЗ ЛИНК 
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'confirm_link') {
    $orderId = $_GET['order_id'] ?? 0;
    $providedToken = $_GET['token'] ?? '';

    // Взимаме поръчката
    $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmtOrder->execute([$orderId]);
    $orderData = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if ($orderData) {
        // Генерираме същия токен, за да го сравним
        $expectedToken = hash('sha256', $orderData['id'] . $orderData['email'] . ORDER_SECRET_KEY);

        if (hash_equals($expectedToken, $providedToken)) {
            
            if ($orderData['status'] === 'Непотвърдена') {
                try {
                    $pdo->beginTransaction();

                    // 1. Актуализиране на статуса
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'Чакаща' WHERE id = ?");
                    $stmt->execute([$orderId]);

                    // 2. Взимане на продуктите
                    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $stmtItems->execute([$orderId]);
                    $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

                    // 3. Намаляване на наличностите
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    foreach ($orderItems as $item) {
                        $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
                    }

                    $pdo->commit();
                    
                    // Изчистване на количката
                    unset($_SESSION['cart']);

                    // 4. Изпращане на имейл (Разписка)
                    $tableHtml = generateOrderTableHTML($orderItems, $orderData['total_amount']);
                    $subject = "Успешно приета поръчка #{$orderId} | Приют Надежда";
                    $body = "
                        <h2 style='color: #4CAF50;'>Здравейте, {$orderData['full_name']}!</h2>
                        <p>Вашата поръчка <strong>#{$orderId}</strong> беше успешно потвърдена и приета за обработка.</p>
                        <p><strong>Очаквайте поръчката на посочения адрес ({$orderData['address']}) до няколко работни дни чрез куриерска фирма Еконт.</strong></p>
                        <h3>Детайли на поръчката:</h3>
                        {$tableHtml}
                        <br>
                        <p>Благодарим Ви, че подкрепяте Приют 'Надежда'!</p>
                        <p>При въпроси, свържете се с нас на: 089 373 3552</p>
                    ";
                    sendStoreEmail($orderData['email'], $subject, $body);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    die('Грешка при потвърждаване: ' . $e->getMessage());
                }
            }
            
            // Показваме екрана за успех
            ?>
            <!DOCTYPE html>
            <html lang="bg">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Поръчката е приета | Приют "Надежда"</title>
                <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
                <link rel="stylesheet" href="style.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            </head>
            <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f5f5f5; padding: 20px;">
                <div class="form-box" style="max-width: 600px; width: 100%; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <div style="text-align: center;">
                        <i class="fas fa-check-circle" style="font-size: 4rem; color: #4CAF50; margin-bottom: 20px;"></i>
                        <h2 style="color: #4CAF50; margin-bottom: 15px;">Поръчката е успешно потвърдена!</h2>
                        <p style="font-size: 1.1rem;">Изпратихме Ви имейл с разписка за поръчка <strong>#<?php echo safe_escape($orderId); ?></strong>.</p>
                    </div>
                    
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 10px; margin: 20px 0; text-align: center;">
                        <p><strong>Очаквайте поръчката на посочения адрес до няколко работни дни.</strong></p>
                        <p style="font-size: 0.95rem; margin-top: 10px;">
                            <i class="fas fa-phone" style="color: #4CAF50;"></i> 089 373 3552 | 
                            <i class="fas fa-envelope" style="color: #4CAF50;"></i> yusuf.kapanak@pmggd.bg
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="index.php" class="btn btn-primary" style="padding: 10px 20px; text-decoration: none; border-radius: 5px; background: #4CAF50; color: white;">
                            <i class="fas fa-home"></i> Към началото
                        </a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            die("Невалиден или изтекъл линк за потвърждение.");
        }
    } else {
        die("Поръчката не е намерена.");
    }
}

// ======================================================================
// ОБРАБОТКА НА POST ЗАЯВКИ 
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // СТЪПКА 2: ОТКАЗ ОТ ПОРЪЧКАТА ПРЕДИ ПОТВЪРЖДЕНИЕ
    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        $orderId = $_POST['order_id'];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $pdo->commit();
            
            header('Location: accessories.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die('Грешка при изтриване на поръчката: ' . $e->getMessage());
        }
    }

    // СТЪПКА 1: ПЪРВОНАЧАЛНО СЪЗДАВАНЕ НА ПОРЪЧКАТА И ПРАЩАНЕ НА ЛИНК
    if (!isset($_POST['action'])) {
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (empty($fullName) || empty($email) || empty($phone) || empty($address)) {
            die('Моля, попълнете всички полета!');
        }
        
        $cartItems = getCartItems(); 
        if (empty($cartItems)) {
            die('Количката е празна!');
        }
        $totalAmount = getCartTotalPrice();
        
        try {
            $pdo->beginTransaction();
            
            
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, full_name, email, phone, address, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'Непотвърдена')");
            $userId = function_exists('isLoggedIn') && isLoggedIn() ? $_SESSION['user_id'] : null;
            $stmt->execute([$userId, $fullName, $email, $phone, $address, $totalAmount]);
            $orderId = $pdo->lastInsertId();
            
            // Записваме артикулите
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmt->execute([$orderId, $item['id'], $item['name'], $item['quantity'], $item['price']]);
            }
            
            $pdo->commit();
            
            // ГЕНЕРИРАНЕ И ИЗПРАЩАНЕ НА ЛИНК ЗА ПОТВЪРЖДЕНИЕ
            $token = hash('sha256', $orderId . $email . ORDER_SECRET_KEY);
            
            // Съставяме абсолютен URL адрес към този файл
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $currentUrl = explode('?', $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])[0];
            $confirmLink = $currentUrl . "?action=confirm_link&order_id=" . $orderId . "&token=" . $token;
            
            // Генерираме таблицата за имейла 
            $tableHtml = generateOrderTableHTML($cartItems, $totalAmount);
            $subject = "Потвърждение на поръчка #{$orderId} | Приют Надежда";
            $body = "
                <h2 style='color: #333;'>Здравейте, {$fullName}!</h2>
                <p>За да завършите Вашата поръчка, моля потвърдете я, като кликнете на бутона по-долу:</p>
                <p style='margin: 25px 0;'>
                    <a href='{$confirmLink}' style='background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>Потвърди Поръчката</a>
                </p>
                <p>Или копирайте следния линк във Вашия браузър:</p>
                <p><a href='{$confirmLink}'>{$confirmLink}</a></p>
                
                <hr style='border: 0; border-top: 1px solid #ddd; margin: 20px 0;'>
                
                <h3>Преглед на поръчката:</h3>
                {$tableHtml}
                <br>
                <p>Ако не сте правили тази поръчка, моля игнорирайте този имейл.</p>
            ";
            
            sendStoreEmail($email, $subject, $body);

        } catch (Exception $e) {
            $pdo->rollBack();
            die('Грешка при създаване на поръчката: ' . $e->getMessage());
        }

       
        ?>
        <!DOCTYPE html>
        <html lang="bg">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Очаква се потвърждение | Приют "Надежда"</title>
            <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
            <link rel="stylesheet" href="style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                .action-btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: bold; width: 100%; transition: 0.3s; margin-bottom: 10px; }
                .btn-cancel { background: #f44336; color: white; }
                .btn-cancel:hover { background: #da190b; }
            </style>
        </head>
        <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f5f5f5; padding: 20px;">
            <div class="form-box" style="max-width: 600px; width: 100%; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-envelope-open-text" style="font-size: 4rem; color: #2196F3; margin-bottom: 15px;"></i>
                    <h2 style="color: #333;">Проверете имейла си!</h2>
                </div>
                
                <div style="background: #e3f2fd; color: #0d47a1; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 1.1rem; line-height: 1.5;">
                    Изпратихме линк за потвърждение на: <strong><?php echo safe_escape($email); ?></strong>.<br>
                    Моля, отворете имейла си и кликнете на линка, за да финализирате поръчката.
                </div>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                <h3 style="margin-bottom: 15px;">Преглед на поръчката (#<?php echo safe_escape($orderId); ?>)</h3>
                <div style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 15px;">
                    <strong>Получател:</strong> <?php echo safe_escape($fullName); ?><br>
                    <strong>Телефон:</strong> <?php echo safe_escape($phone); ?><br>
                    <strong>Адрес за доставка:</strong> <?php echo safe_escape($address); ?>
                </div>

                <?php echo generateOrderTableHTML($cartItems, $totalAmount); ?>

                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin: 20px 0; font-size: 0.9rem;">
                    <i class="fas fa-truck" style="margin-right: 8px;"></i> 
                    Доставката ще се извърши чрез куриерска фирма <strong>Еконт</strong>. Към поръчката е добавена фиксирана такса за доставка от 5.00 €.
                </div>

                <form method="POST" action="process_order.php" onsubmit="return confirm('Сигурни ли сте, че искате да откажете поръчката?');">
                    <input type="hidden" name="order_id" value="<?php echo safe_escape($orderId); ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="action-btn btn-cancel">
                        <i class="fas fa-times"></i> Отказ и изтриване на поръчката
                    </button>
                </form>
                
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Ако достъпят файла без POST заявка или без GET
header('Location: index.php');
exit;
?>