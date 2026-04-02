<?php
// admin-dashboard.php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'db_connect.php';

// 1. Проверка за сигурност - Само за администратори
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'Администратор') {
    header('Location: profile.php');
    exit;
}
// Функция за изпращане на имейл с PHPMailer
// Помощна функция за изпращане на имейли
if (!function_exists('sendStoreEmail')) {
    function sendStoreEmail($to, $subject, $htmlBody) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'yusuf.kapanak@pmggd.bg';
            $mail->Password   = 'oqpyqizclmicbylp'; 
            
           
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465; 
            
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
}


if (!function_exists('generateOrderTableHTML')) {
    function generateOrderTableHTML($items, $totalAmount) {
        
        $shipping = defined('SHIPPING_COST') ? SHIPPING_COST : 5.00; 
        $grandTotal = $totalAmount + $shipping; // Крайната сума за плащане
        
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
            
          
            $safeProductName = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
            
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $safeProductName . '</td>';
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
        $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . number_format($shipping, 2) . ' €</td></tr>';
        
        // Крайна сума (Продукти + Доставка)
        $html .= '<tr><td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>Крайна сума:</strong></td>';
        $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #2ecc71; font-size: 1.1em;"><strong>' . number_format($grandTotal, 2) . ' €</strong></td></tr>';
        
        $html .= '</tfoot></table>';
        
        return $html;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ЛОГИКА ЗА МАСОВО ИЗТРИВАНЕ
    if ($_POST['action'] === 'bulk_delete') {
        $section = $_POST['table'] ?? ''; 
        $selected_ids = array_values(array_filter($_POST['selected_ids'] ?? [], 'is_numeric'));

       
        $table_map = [
            'animals'      => 'animals',
            'users'        => 'users',
            'adoptions'    => 'adoptions',
            'appointments' => 'appointments',
            'products'     => 'products',
            'orders'       => 'orders',
            'donations'    => 'donations',
            'stories'      => 'success_stories',  
            'messages'     => 'contact_messages', 
            'reports'      => 'stray_reports'     
        ];

        
        if (array_key_exists($section, $table_map) && !empty($selected_ids)) {
            
            $db_table = $table_map[$section]; 
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            
            try {
                $sql = "DELETE FROM `$db_table` WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($selected_ids);
                
                
            } catch (PDOException $e) {
                 $_SESSION['error_message'] = "Грешка при изтриване: " . escape($e->getMessage());
            }
        }
        
        
        header("Location: admin-dashboard.php?section=" . urlencode($section));
        exit;
    }
}
// Помощна функция за защита на данните
if (!function_exists('escape')) {
    function escape($html) {
        return htmlspecialchars($html ?? '', ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}

// Валидационни функции
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function validateImageFile($file, $expectedPrefix = '') {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['valid' => false, 'message' => "Невалиден формат! Разрешени: " . implode(', ', $allowedExtensions)];
    }
    
    $fileMimeType = mime_content_type($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
    
    if (!in_array($fileMimeType, $allowedMimes)) {
        return ['valid' => false, 'message' => "Невалиден MIME тип на файла!"];
    }
    
    return ['valid' => true];
}

// 2. ОБРАБОТКА НА ВСИЧКИ POST ЗАЯВКИ
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $table = isset($_POST['table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['table']) : '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        // МАСОВО ИЗТРИВАНЕ НА МАРКИРАНИ ЗАПИСИ
        if ($action === 'bulk_delete' && $table && isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
            $selected_ids = array_values(array_filter($_POST['selected_ids'], 'is_numeric'));
            if (!empty($selected_ids)) {
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $success_message = "Успешно изтрити " . count($selected_ids) . " записа!";
            } else {
                $error_message = "Няма избрани записи за изтриване!";
            }
        }
        // УНИВЕРСАЛНО ИЗТРИВАНЕ
        elseif ($action === 'delete_record' && $table && $id) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
                $success_message = "Записът беше успешно изтрит!";
            } else {
                $error_message = "Невалидна таблица!";
            }
        }
        // УНИВЕРСАЛНА ПРОМЯНА НА СТАТУС
elseif ($action === 'update_status' && $table && $id) {
    $status = $_POST['status'];
    $tracking_number = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';

    // 1. Обновяваме статуса в базата данни
    $pdo->prepare("UPDATE `$table` SET status = ? WHERE id = ?")->execute([$status, $id]);
    $success_message = "Статусът беше обновен успешно!";

    // 2. Логика за изпращане на имейл, ако сме в поръчки и статусът е "Изпратена"
    if ($table === 'orders' && $status === 'Изпратена' && !empty($tracking_number)) {
        
        // Взимаме данните за самата поръчка
        $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmtOrder->execute([$id]);
        $orderData = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        // Взимаме продуктите от поръчката
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$id]);
        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        if ($orderData && !empty($orderData['email'])) {
            // Генерираме таблицата с продуктите за имейла
            $tableHtml = generateOrderTableHTML($orderItems, $orderData['total_amount']);
            
            $subject = "Изпратена поръчка #{$id} | Приют Надежда";
            $body = "
                <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
                    <h2 style='color: #4CAF50;'>Здравейте, {$orderData['full_name']}!</h2>
                    <p>С радост Ви съобщаваме, че Вашата поръчка <strong>#{$id}</strong> беше изпратена и пътува към Вас!</p>
                    
                    <div style='background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #c8e6c9;'>
                        <h3 style='margin-top: 0; color: #2e7d32;'>Проследяване на пратката</h3>
                        <p style='margin-bottom: 5px;'>Пратката е изпратена чрез куриерска фирма Еконт.</p>
                        <p style='margin: 0;'>Номер на товарителница: <strong style='font-size: 1.2em; background: #fff; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-top: 10px; border: 1px dashed #4CAF50;'>{$tracking_number}</strong></p>
                        <p style='margin-bottom: 5px;'>Линк за проследяване на пратката от Еконт чрез номера на товарителницата: <a href='https://www.econt.com/services/track-shipment'>Проследи пратката си </a></p>
                    </div>

                    <h3>Детайли на поръчката:</h3>
                    {$tableHtml}
                    
                    <p style='margin-top: 25px;'>Благодарим Ви отново, че подкрепяте Приют 'Надежда'!</p>
                </div>
            ";

            // Пращаме имейла
            $mailResult = sendStoreEmail($orderData['email'], $subject, $body);
            
            if ($mailResult === true) {
                $success_message = "Поръчката е маркирана като изпратена и клиентът е уведомен по имейл с номера на пратката!";
            } else {
                
                $error_message = "Статусът е променен, но възникна грешка при изпращането на имейл: " . $mailResult;
            }
        }
    }
}
        // ДОБАВЯНЕ НА ЖИВОТНО
        elseif ($action === 'add_animal') {
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $age = trim($_POST['age']);
            $gender = trim($_POST['gender']);
            $description = trim($_POST['description']);
            $image_url = 'assets/images/placeholder.jpg';
            
            if (empty($name) || empty($type) || empty($age) || empty($gender) || empty($description)) {
                $error_message = "Моля, попълнете всички полета!";
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $validation = validateImageFile($_FILES['image']);
                if (!$validation['valid']) {
                    $error_message = $validation['message'];
                } else {
                    $uploadDir = 'assets/images/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM animals");
                    $nextId = $countStmt->fetchColumn() + 1;
                    $fileName = 'animals_' . $nextId . '.' . $ext;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                        $image_url = $uploadDir . $fileName;
                        
                        $pdo->prepare("INSERT INTO animals (name, type, age, gender, description, image_url, status) VALUES (?, ?, ?, ?, ?, ?, 'Налично')")
                            ->execute([$name, $type, $age, $gender, $description, $image_url]);
                        $success_message = "Животното беше добавено успешно!";
                    } else {
                        $error_message = "Грешка при качване на снимката!";
                    }
                }
            } else {
                $error_message = "Моля, качете снимка на животното!";
            }
        }
        // РЕДАКТИРАНЕ НА ЖИВОТНО
        elseif ($action === 'edit_animal' && $id) {
            $name = trim($_POST['name']);
            $type = trim($_POST['type']);
            $age = trim($_POST['age']);
            $gender = trim($_POST['gender']);
            $description = trim($_POST['description']);
            $status = trim($_POST['status']);
            $image_url = $_POST['current_image'];
            
            if (empty($name) || empty($type) || empty($age) || empty($gender) || empty($description)) {
                $error_message = "Моля, попълнете всички полета!";
            } else {
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $validation = validateImageFile($_FILES['image']);
                    if (!$validation['valid']) {
                        $error_message = $validation['message'];
                    } else {
                        $uploadDir = 'assets/images/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $fileName = 'animals_' . $id . '.' . $ext;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                            $image_url = $uploadDir . $fileName;
                        } else {
                            $error_message = "Грешка при качване на снимката!";
                        }
                    }
                }
                
                if (empty($error_message)) {
                    $pdo->prepare("UPDATE animals SET name=?, type=?, age=?, gender=?, description=?, image_url=?, status=? WHERE id=?")
                        ->execute([$name, $type, $age, $gender, $description, $image_url, $status, $id]);
                    $success_message = "Животното беше обновено успешно!";
                }
            }
        }
      // ДОБАВЯНЕ НА ПРОДУКТ
        elseif ($action === 'add_product') {
            $name = trim($_POST['name']);
            $price = (float)$_POST['price'];
            $description = trim($_POST['description']);
            $stock = (int)$_POST['stock'];
            $image_url = 'assets/images/placeholder.jpg';
            
            if (empty($name) || $price <= 0) {
                $error_message = "Моля, попълнете всички полета с валидни данни!";
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $validation = validateImageFile($_FILES['image']);
                if (!$validation['valid']) {
                    $error_message = $validation['message'];
                } else {
                    $uploadDir = 'assets/images/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    
                    
                    $maxIdStmt = $pdo->query("SELECT MAX(id) FROM products");
                    $nextId = (int)$maxIdStmt->fetchColumn() + 1;
                    $fileName = 'accessories_' . $nextId . '.' . $ext;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                        $image_url = $uploadDir . $fileName;
                        
                        $pdo->prepare("INSERT INTO products (name, price, description, image_url, stock) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$name, $price, $description, $image_url, $stock]);
                        $success_message = "Продуктът беше добавен успешно!";
                    } else {
                        $error_message = "Грешка при качване на снимката!";
                    }
                }
            } else {
                $error_message = "Моля, качете снимка на продукта!";
            }
        }
        
        // РЕДАКТИРАНЕ НА ПРОДУКТ
        elseif ($action === 'edit_product' && $id) {
            $name = trim($_POST['name']);
            $price = (float)$_POST['price'];
            $description = trim($_POST['description']);
            $stock = (int)$_POST['stock'];
            $image_url = $_POST['current_image'];
            
            if (empty($name) || $price <= 0) {
                $error_message = "Моля, попълнете всички полета с валидни данни!";
            } else {
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $validation = validateImageFile($_FILES['image']);
                    if (!$validation['valid']) {
                        $error_message = $validation['message'];
                    } else {
                        $uploadDir = 'assets/images/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        
                        // Използваме текущото ID на продукта за името на снимката
                        $fileName = 'accessories_' . $id . '.' . $ext;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                            $image_url = $uploadDir . $fileName;
                        } else {
                            $error_message = "Грешка при качване на снимката!";
                        }
                    }
                }
                
                if (empty($error_message)) {
                    $pdo->prepare("UPDATE products SET name=?, price=?, description=?, image_url=?, stock=? WHERE id=?")
                        ->execute([$name, $price, $description, $image_url, $stock, $id]);
                    $success_message = "Продуктът беше обновен успешно!";
                }
            }
        } 
        // ДОБАВЯНЕ НА ИСТОРИЯ
        elseif ($action === 'add_story') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $animal_name = trim($_POST['animal_name']);
            $status = trim($_POST['status']);
            $image_url = 'assets/images/placeholder.jpg';
            
            if (empty($title) || empty($description) || empty($animal_name)) {
                $error_message = "Моля, попълнете всички текстови полета!";
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $validation = validateImageFile($_FILES['image']);
                if (!$validation['valid']) {
                    $error_message = $validation['message'];
                } else {
                    $uploadDir = 'assets/images/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM success_stories");
                    $nextId = $countStmt->fetchColumn() + 1;
                    $fileName = 'stories_' . $nextId . '.' . $ext;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                        $image_url = $uploadDir . $fileName;
                        
                        $pdo->prepare("INSERT INTO success_stories (title, description, animal_name, image_url, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
                            ->execute([$title, $description, $animal_name, $image_url, $status]);
                        $success_message = "Историята беше добавена успешно!";
                    } else {
                        $error_message = "Грешка при качване на снимката!";
                    }
                }
            } else {
                $error_message = "Моля, качете снимка за историята!";
            }
        }
        // РЕДАКТИРАНЕ НА ИСТОРИЯ
        elseif ($action === 'edit_story' && $id) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $animal_name = trim($_POST['animal_name']);
            $status = trim($_POST['status']);
            $image_url = $_POST['current_image'];
            
            if (empty($title) || empty($description) || empty($animal_name)) {
                $error_message = "Моля, попълнете всички текстови полета!";
            } else {
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $validation = validateImageFile($_FILES['image']);
                    if (!$validation['valid']) {
                        $error_message = $validation['message'];
                    } else {
                        $uploadDir = 'assets/images/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $fileName = 'stories_' . $id . '.' . $ext;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                            $image_url = $uploadDir . $fileName;
                        } else {
                            $error_message = "Грешка при качване на снимката!";
                        }
                    }
                }
                
                if (empty($error_message)) {
                    $pdo->prepare("UPDATE success_stories SET title=?, description=?, animal_name=?, image_url=?, status=? WHERE id=?")
                        ->execute([$title, $description, $animal_name, $image_url, $status, $id]);
                    $success_message = "Историята беше обновена успешно!";
                }
            }
        }
        // ДОБАВЯНЕ НА ПОТРЕБИТЕЛ
        elseif ($action === 'add_user') {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = trim($_POST['role']);
            $password = $_POST['password'];
            
            if (empty($full_name) || empty($email) || empty($password)) {
                $error_message = "Моля, попълнете всички задължителни полета!";
            } elseif (!validateEmail($email)) {
                $error_message = "Моля, въведете валиден имейл адрес!";
            } elseif (!empty($phone) && !validatePhone($phone)) {
                $error_message = "Телефонният номер трябва да съдържа точно 10 цифри!";
            } else {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                if ($check->rowCount() > 0) {
                    $error_message = "Този имейл вече е регистриран!";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, email_status, created_at) VALUES (?, ?, ?, ?, ?, 'Потвърден', NOW())")
                        ->execute([$full_name, $email, $phone, $hashedPassword, $role]);
                    $success_message = "Потребителят беше добавен успешно!";
                }
            }
        }
        // РЕДАКТИРАНЕ НА ПОТРЕБИТЕЛ
        elseif ($action === 'edit_user' && $id) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = trim($_POST['role']);
            
            if (empty($full_name) || empty($email)) {
                $error_message = "Моля, попълнете всички задължителни полета!";
            } elseif (!validateEmail($email)) {
                $error_message = "Моля, въведете валиден имейл адрес!";
            } elseif (!empty($phone) && !validatePhone($phone)) {
                $error_message = "Телефонният номер трябва да съдържа точно 10 цифри!";
            } else {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check->execute([$email, $id]);
                if ($check->rowCount() > 0) {
                    $error_message = "Този имейл вече се използва от друг потребител!";
                } else {
                    $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=? WHERE id=?")
                        ->execute([$full_name, $email, $phone, $role, $id]);
                    $success_message = "Потребителят беше обновен успешно!";
                }
            }
        }
        // РЕДАКТИРАНЕ НА ЗАЯВКА ЗА ОСИНОВЯВАНЕ
        elseif ($action === 'edit_adoption' && $id) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $animal_name = trim($_POST['animal_name']);
            $other_pets = trim($_POST['other_pets']);
            $message = trim($_POST['message']);
            $status = trim($_POST['status']);
            
            if (empty($full_name) || empty($email) || empty($phone) || empty($animal_name)) {
                $error_message = "Моля, попълнете всички задължителни полета!";
            } elseif (!validateEmail($email)) {
                $error_message = "Моля, въведете валиден имейл адрес!";
            } elseif (!validatePhone($phone)) {
                $error_message = "Телефонният номер трябва да съдържа точно 10 цифри!";
            } else {
                $pdo->prepare("UPDATE adoptions SET full_name=?, email=?, phone=?, animal_name=?, other_pets=?, message=?, status=? WHERE id=?")
                    ->execute([$full_name, $email, $phone, $animal_name, $other_pets, $message, $status, $id]);
                $success_message = "Заявката за осиновяване беше обновена успешно!";
            }
        }
        // РЕДАКТИРАНЕ НА ЧАС
        elseif ($action === 'edit_appointment' && $id) {
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $status = trim($_POST['status']);
            
            if (empty($appointment_date) || empty($appointment_time)) {
                $error_message = "Моля, попълнете дата и час!";
            } else {
                $pdo->prepare("UPDATE appointments SET appointment_date=?, appointment_time=?, status=? WHERE id=?")
                    ->execute([$appointment_date, $appointment_time, $status, $id]);
                $success_message = "Часът беше обновен успешно!";
            }
        }
        // РЕДАКТИРАНЕ НА ПОРЪЧКА
        elseif ($action === 'edit_order' && $id) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $total_amount = (float)$_POST['total_amount'];
            $status = trim($_POST['status']);
            
            if (empty($full_name) || empty($email) || empty($phone) || empty($address)) {
                $error_message = "Моля, попълнете всички задължителни полета!";
            } elseif (!validateEmail($email)) {
                $error_message = "Моля, въведете валиден имейл адрес!";
            } elseif (!validatePhone($phone)) {
                $error_message = "Телефонният номер трябва да съдържа точно 10 цифри!";
            } else {
                $pdo->prepare("UPDATE orders SET full_name=?, email=?, phone=?, address=?, total_amount=?, status=? WHERE id=?")
                    ->execute([$full_name, $email, $phone, $address, $total_amount, $status, $id]);
                $success_message = "Поръчката беше обновена успешно!";
            }
        }
        // РЕДАКТИРАНЕ НА ДАРЕНИЕ
        elseif ($action === 'edit_donation' && $id) {
            $amount = (float)$_POST['amount'];
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $payment_method = trim($_POST['payment_method']);
            $status = trim($_POST['status']);
            
            if (empty($full_name) || empty($email) || $amount <= 0) {
                $error_message = "Моля, попълнете всички задължителни полета с валидни данни!";
            } elseif (!validateEmail($email)) {
                $error_message = "Моля, въведете валиден имейл адрес!";
            } else {
                $pdo->prepare("UPDATE donations SET amount=?, full_name=?, email=?, payment_method=?, status=? WHERE id=?")
                    ->execute([$amount, $full_name, $email, $payment_method, $status, $id]);
                $success_message = "Дарението беше обновено успешно!";
            }
        }
        // РЕДАКТИРАНЕ НА СЪОБЩЕНИЕ
        elseif ($action === 'edit_message' && $id) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $subject = trim($_POST['subject']);
            $message = trim($_POST['message']);
            $status = trim($_POST['status']);
            
            if (empty($full_name) || empty($email) || empty($message)) {
                $error_message = "Моля, попълнете всички задължителни полета!";
            } elseif (!validateEmail($email)) {
                $error_message = "Моля, въведете валиден имейл адрес!";
            } elseif (!empty($phone) && !validatePhone($phone)) {
                $error_message = "Телефонният номер трябва да съдържа точно 10 цифри!";
            } else {
                $pdo->prepare("UPDATE contact_messages SET full_name=?, email=?, phone=?, subject=?, message=?, status=? WHERE id=?")
                    ->execute([$full_name, $email, $phone, $subject, $message, $status, $id]);
                $success_message = "Съобщението беше обновено успешно!";
            }
        }
        // РЕДАКТИРАНЕ НА СИГНАЛ
        elseif ($action === 'edit_stray_report' && $id) {
            $animal_type = trim($_POST['animal_type']);
            $location_address = trim($_POST['location_address']);
            $description = trim($_POST['description']);
            $reporter_name = trim($_POST['reporter_name']);
            $reporter_phone = trim($_POST['reporter_phone']);
            $status = trim($_POST['status']);
            
            if (empty($animal_type) || empty($location_address)) {
                $error_message = "Моля, попълнете всички задължителни полета!";
            } elseif (!empty($reporter_phone) && !validatePhone($reporter_phone)) {
                $error_message = "Телефонният номер трябва да съдържа точно 10 цифри!";
            } else {
                $pdo->prepare("UPDATE stray_reports SET animal_type=?, location_address=?, description=?, reporter_name=?, reporter_phone=?, status=? WHERE id=?")
                    ->execute([$animal_type, $location_address, $description, $reporter_name, $reporter_phone, $status, $id]);
                $success_message = "Сигналът беше обновен успешно!";
            }
        }
    } catch (Exception $e) {
        $error_message = "Възникна грешка: " . $e->getMessage();
    }
}

// 3. Текуща секция
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$edit_data = null;

// 4. Обработка на търсене и филтриране
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$type_filter = isset($_GET['type_filter']) ? trim($_GET['type_filter']) : '';

function buildSearchQuery($table, $search, $status_filter = '', $type_filter = '') {
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        switch($table) {
            case 'animals':
                $where[] = "(name LIKE ? OR type LIKE ? OR description LIKE ? OR age LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'users':
                $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'adoptions':
                $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR animal_name LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'products':
                $where[] = "(name LIKE ? OR description LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'orders':
                $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR address LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'donations':
                $where[] = "(full_name LIKE ? OR email LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'success_stories':
                $where[] = "(title LIKE ? OR description LIKE ? OR animal_name LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'contact_messages':
                $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'appointments':
                $where[] = "(appointment_date LIKE ? OR appointment_time LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                break;
            case 'stray_reports':
                $where[] = "(animal_type LIKE ? OR location_address LIKE ? OR description LIKE ? OR reporter_name LIKE ? OR reporter_phone LIKE ?)";
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                break;
        }
    }
    
    if (!empty($status_filter) && $status_filter !== 'all') {
        $where[] = "status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($type_filter) && $type_filter !== 'all' && $table === 'animals') {
        $where[] = "type = ?";
        $params[] = $type_filter;
    }
    
    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    return [$where_clause, $params];
}

if ($edit_id > 0 && in_array($section, ['animals', 'users', 'adoptions', 'appointments', 'products', 'orders', 'donations', 'stories', 'messages', 'reports'])) {
    $table_map = [
        'animals' => 'animals',
        'users' => 'users',
        'adoptions' => 'adoptions',
        'appointments' => 'appointments',
        'products' => 'products',
        'orders' => 'orders',
        'donations' => 'donations',
        'stories' => 'success_stories',
        'messages' => 'contact_messages',
        'reports' => 'stray_reports'
    ];
    $table = $table_map[$section];
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Панел | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="index.php" class="logo">
            <img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img">
            Надежда
        </a>

        <button class="hamburger-menu" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-actions" id="nav-actions-menu">
            <div class="user-dropdown">
                <button class="login-btn active desktop-btn">
                    <i class="fas fa-user-shield"></i> Администраторски Панел
                </button>
                
                <div class="dropdown-content">
                    <span class="mobile-menu-title">
                        <i class="fas fa-user-shield"></i> Администраторски Панел
                    </span>
                    
                    <a href="index.php"><i class="fas fa-home"></i> Към сайта</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Профил</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Изход</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="profile-page">
    <div class="container" style="max-width: 100%; padding: 0 15px;">
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo escape($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo escape($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-layout">
            
            <aside>
                <div class="profile-card">
                    <div style="text-align:center; margin-bottom: 20px;">
                        <img src="assets/logo/paw-solid-full.png" style="width: 60px; margin-bottom: 10px;">
                        <h3>Администрация</h3>
                        <p style="color: #777; font-size: 12px;"><?php echo escape($user['full_name']); ?></p>
                    </div>
                    <ul class="settings-menu">
                        <li><a href="?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> Табло</a></li>
                        <li><a href="?section=animals" class="<?php echo $section === 'animals' ? 'active' : ''; ?>"><i class="fas fa-paw"></i> Животни</a></li>
                        <li><a href="?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Потребители</a></li>
                        <li><a href="?section=adoptions" class="<?php echo $section === 'adoptions' ? 'active' : ''; ?>"><i class="fas fa-file-signature"></i> Осиновявания</a></li>
                        <li><a href="?section=appointments" class="<?php echo $section === 'appointments' ? 'active' : ''; ?>"><i class="far fa-calendar-alt"></i> Часове</a></li>
                        <li><a href="?section=products" class="<?php echo $section === 'products' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag"></i> Продукти</a></li>
                        <li><a href="?section=orders" class="<?php echo $section === 'orders' ? 'active' : ''; ?>"><i class="fas fa-box-open"></i> Поръчки</a></li>
                        <li><a href="?section=donations" class="<?php echo $section === 'donations' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> Дарения</a></li>
                        <li><a href="?section=stories" class="<?php echo $section === 'stories' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Истории</a></li>
                        <li><a href="?section=messages" class="<?php echo $section === 'messages' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Съобщения</a></li>
                        <li><a href="?section=reports" class="<?php echo $section === 'reports' ? 'active' : ''; ?>"><i class="fas fa-exclamation-triangle"></i> Сигнали</a></li>
                    </ul>
                </div>
            </aside>
    
            <section class="profile-content">
                
                <?php if ($section === 'dashboard'): ?>
                    <?php
                        $counts = [
                            'animals' => $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn(),
                            'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                            'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
                            'adoptions' => $pdo->query("SELECT COUNT(*) FROM adoptions")->fetchColumn(),
                            'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                            'stories' => $pdo->query("SELECT COUNT(*) FROM success_stories")->fetchColumn(),
                            'donations' => $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn(),
                            'messages' => $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn(),
                            'reports' => $pdo->query("SELECT COUNT(*) FROM stray_reports")->fetchColumn()
                        ];
                        $donations_sum = $pdo->query("SELECT SUM(amount) FROM donations WHERE status='Завършено'")->fetchColumn() ?: 0;
                        $pending_adoptions = $pdo->query("SELECT COUNT(*) FROM adoptions WHERE status='Изчаква се'")->fetchColumn();
                        $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Чакаща'")->fetchColumn();
                        $pending_reports = $pdo->query("SELECT COUNT(*) FROM stray_reports WHERE status='Ново'")->fetchColumn();
                    ?>
                    <div class="stats-grid">
                        <div class="stat-box"><i class="fas fa-paw"></i><div class="stat-value"><?php echo $counts['animals']; ?></div><div class="stat-label">Животни</div></div>
                        <div class="stat-box"><i class="fas fa-users"></i><div class="stat-value"><?php echo $counts['users']; ?></div><div class="stat-label">Потребители</div></div>
                        <div class="stat-box"><i class="fas fa-file-signature"></i><div class="stat-value"><?php echo $counts['adoptions']; ?></div><div class="stat-label">Осиновявания</div></div>
                        <div class="stat-box"><i class="fas fa-shopping-bag"></i><div class="stat-value"><?php echo $counts['products']; ?></div><div class="stat-label">Продукти</div></div>
                        <div class="stat-box"><i class="fas fa-box-open"></i><div class="stat-value"><?php echo $counts['orders']; ?></div><div class="stat-label">Поръчки</div></div>
                        <div class="stat-box"><i class="fas fa-euro-sign"></i><div class="stat-value"><?php echo number_format($donations_sum, 2); ?> €</div><div class="stat-label">Дарения (общо)</div></div>
                        <div class="stat-box"><i class="fas fa-clock"></i><div class="stat-value"><?php echo $pending_adoptions; ?></div><div class="stat-label">Чакащи осиновявания</div></div>
                        <div class="stat-box"><i class="fas fa-truck"></i><div class="stat-value"><?php echo $pending_orders; ?></div><div class="stat-label">Чакащи поръчки</div></div>
                        <div class="stat-box"><i class="fas fa-exclamation-triangle"></i><div class="stat-value"><?php echo $counts['reports']; ?></div><div class="stat-label">Общо сигнали</div></div>
                        <div class="stat-box"><i class="fas fa-bell"></i><div class="stat-value"><?php echo $pending_reports; ?></div><div class="stat-label">Нови сигнали</div></div>
                    </div>
                    
                    <div class="profile-card">
                        <h3><i class="fas fa-chart-line"></i> Бързи действия</h3>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                            <button class="btn-add" onclick="openModal('modal-animal')"><i class="fas fa-plus"></i> Добави животно</button>
                            <button class="btn-add" onclick="openModal('modal-product')"><i class="fas fa-plus"></i> Добави продукт</button>
                            <button class="btn-add" onclick="openModal('modal-story')"><i class="fas fa-plus"></i> Добави история</button>
                            <button class="btn-add" onclick="openModal('modal-user')"><i class="fas fa-plus"></i> Добави потребител</button>
                        </div>
                    </div>

                <?php elseif ($section === 'animals'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-paw"></i> Управление на Животни</h2>
                            <button class="btn-add" onclick="openModal('modal-animal')"><i class="fas fa-plus"></i> Добави животно</button>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="animals">
                                <div class="search-group" style="flex: 1;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име, вид, описание..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Налично" <?php echo $status_filter === 'Налично' ? 'selected' : ''; ?>>Налично</option>
                                        <option value="Запазено" <?php echo $status_filter === 'Запазено' ? 'selected' : ''; ?>>Запазено</option>
                                        <option value="Осиновено" <?php echo $status_filter === 'Осиновено' ? 'selected' : ''; ?>>Осиновено</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-paw"></i> Филтър по вид:</label>
                                    <select name="type_filter">
                                        <option value="all" <?php echo $type_filter === 'all' || $type_filter === '' ? 'selected' : ''; ?>>Всички видове</option>
                                        <option value="куче" <?php echo $type_filter === 'куче' ? 'selected' : ''; ?>>Куче</option>
                                        <option value="коте" <?php echo $type_filter === 'коте' ? 'selected' : ''; ?>>Коте</option>
                                        <option value="папагал" <?php echo $type_filter === 'папагал' ? 'selected' : ''; ?>>Папагал</option>
                                        <option value="заек" <?php echo $type_filter === 'заек' ? 'selected' : ''; ?>>Заек</option>
                                        <option value="хамстер" <?php echo $type_filter === 'хамстер' ? 'selected' : ''; ?>>Хамстер</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=animals" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'animals'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на животно: <?php echo escape($edit_data['name']); ?></h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit_animal">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                <input type="hidden" name="current_image" value="<?php echo escape($edit_data['image_url']); ?>">
                                
                                <div class="form-group"><label>Име:</label><input type="text" name="name" value="<?php echo escape($edit_data['name']); ?>" required></div>
                                <div class="form-group"><label>Вид:</label><input type="text" name="type" value="<?php echo escape($edit_data['type']); ?>" required></div>
                                <div class="form-group"><label>Възраст:</label><input type="text" name="age" value="<?php echo escape($edit_data['age']); ?>" required></div>
                                <div class="form-group"><label>Пол:</label>
                                    <select name="gender" required>
                                        <option value="Мъжки" <?php if($edit_data['gender'] == 'Мъжки') echo 'selected'; ?>>Мъжки</option>
                                        <option value="Женски" <?php if($edit_data['gender'] == 'Женски') echo 'selected'; ?>>Женски</option>
                                    </select>
                                </div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Налично" <?php if($edit_data['status'] == 'Налично') echo 'selected'; ?>>Налично</option>
                                        <option value="Запазено" <?php if($edit_data['status'] == 'Запазено') echo 'selected'; ?>>Запазено</option>
                                        <option value="Осиновено" <?php if($edit_data['status'] == 'Осиновено') echo 'selected'; ?>>Осиновено</option>
                                    </select>
                                </div>
                                <div class="form-group"><label>Описание:</label><textarea name="description" rows="4" required><?php echo escape($edit_data['description']); ?></textarea></div>
                                <div class="form-group"><label>Текуща снимка:</label><br><img src="<?php echo escape($edit_data['image_url']); ?>" style="width:100px; margin-bottom:10px;"><br><label>Нова снимка:</label><input type="file" name="image" accept="image/*"></div>
                                
                                <div class="form-actions">
                                    <a href="?section=animals" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('animals', $search, $status_filter, $type_filter);
                        $stmt = $pdo->prepare("SELECT * FROM animals $where_clause ORDER BY id DESC");
                        $stmt->execute($params);
                        $animals = $stmt->fetchAll();
                        ?>
                        
                        <form method="POST" id="hidden-bulk-form-animals" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="animals">
</form>

<div id="bulk-form-animals"> 
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-animals">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('animals')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('animals')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table animals-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-animals" class="select-all-checkbox" onclick="toggleAllCheckboxes('animals', this)"></th>
                                        <th>ID</th><th>Снимка</th><th>Име</th><th>Вид</th><th>Пол</th><th>Възраст</th><th>Статус</th><th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($animals) > 0): ?>
                                        <?php foreach($animals as $a): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $a['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('animals')"></td>
                                              <td>#<?php echo $a['id']; ?></td>
                                              <td><img src="<?php echo escape($a['image_url']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px;"></td>
                                              <td><strong><?php echo escape($a['name']); ?></strong></td>
                                              <td><?php echo escape($a['type']); ?></td>
                                              <td><?php echo escape($a['gender']); ?></td>
                                              <td><?php echo escape($a['age']); ?></td>
                                              <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="table" value="animals">
                                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="Налично" <?php if($a['status']=='Налично') echo 'selected'; ?>>Налично</option>
                                                        <option value="Запазено" <?php if($a['status']=='Запазено') echo 'selected'; ?>>Запазено</option>
                                                        <option value="Осиновено" <?php if($a['status']=='Осиновено') echo 'selected'; ?>>Осиновено</option>
                                                    </select>
                                                </form>
                                              </td>
                                            <td class="action-buttons">
                                                <a href="?section=animals&edit_id=<?php echo $a['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?><?php echo $type_filter !== 'all' && !empty($type_filter) ? '&type_filter=' . urlencode($type_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете това животно?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="animals">
                                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                            </td>
                                          </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center;">Няма намерени животни, отговарящи на критериите за търсене.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        
                    </div>

                <?php elseif ($section === 'users'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-users"></i> Потребители</h2>
                            <button class="btn-add" onclick="openModal('modal-user')"><i class="fas fa-plus"></i> Добави потребител</button>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="users">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име, имейл или телефон..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по роля:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички роли</option>
                                        <option value="Потребител" <?php echo $status_filter === 'Потребител' ? 'selected' : ''; ?>>Потребител</option>
                                        <option value="Администратор" <?php echo $status_filter === 'Администратор' ? 'selected' : ''; ?>>Администратор</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=users" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'users'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на потребител: <?php echo escape($edit_data['full_name']); ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_user">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Пълно име:</label><input type="text" name="full_name" value="<?php echo escape($edit_data['full_name']); ?>" required></div>
                                <div class="form-group"><label>Имейл:</label><input type="email" name="email" value="<?php echo escape($edit_data['email']); ?>" required></div>
                                <div class="form-group"><label>Телефон:</label><input type="text" name="phone" value="<?php echo escape($edit_data['phone']); ?>" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри"></div>
                                <div class="form-group"><label>Роля:</label>
                                    <select name="role">
                                        <option value="Потребител" <?php if($edit_data['role'] == 'Потребител') echo 'selected'; ?>>Потребител</option>
                                        <option value="Администратор" <?php if($edit_data['role'] == 'Администратор') echo 'selected'; ?>>Администратор</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="?section=users" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                       <?php
// 1. Взимаме филтъра 
$current_role_filter = $_GET['status_filter'] ?? 'all'; 

$where_users = [];
$params_users = [];

// Търсене по текст 
if (!empty($search)) {
    $where_users[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params_users[] = $search_term;
    $params_users[] = $search_term;
    $params_users[] = $search_term;
}

// Филтър по роля
if ($current_role_filter !== 'all' && !empty($current_role_filter)) {
    $where_users[] = "role = ?";
    $params_users[] = $current_role_filter;
}

// Сглобяваме WHERE клаузата
$where_clause = !empty($where_users) ? "WHERE " . implode(" AND ", $where_users) : "";

// Изпълняваме заявката към базата
$stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC");
$stmt->execute($params_users);

$users_list = $stmt->fetchAll();
?>
                        
                        <form method="POST" id="hidden-bulk-form-users" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="users">
</form>

<div id="bulk-form-users">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-users">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('users')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('users')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            <table class="admin-table users-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-users" class="select-all-checkbox" onclick="toggleAllCheckboxes('users', this)"></th>
                                        <th>ID</th><th>Име</th><th>Имейл</th><th>Телефон</th><th>Роля</th><th>Регистриран</th><th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users_list) > 0): ?>
                                        <?php foreach($users_list as $u): ?>
                                          <tr>
                                              <td class="checkbox-column">
                                                  <?php if($u['id'] !== $user_id): ?>
                                                  <input type="checkbox" name="selected_ids[]" value="<?php echo $u['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('users')">
                                                  <?php else: ?>
                                                  <span style="color:#999;">—</span>
                                                  <?php endif; ?>
                                              </td>
                                              <td>#<?php echo $u['id']; ?></td>
                                              <td><?php echo escape($u['full_name']); ?></td>
                                              <td><?php echo escape($u['email']); ?></td>
                                              <td><?php echo escape($u['phone']); ?></td>
                                              <td><strong><?php echo escape($u['role']); ?></strong></td>
                                              <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                                            <td class="action-buttons">
                                                <?php if($u['id'] !== $user_id): ?>
                                                <a href="?section=users&edit_id=<?php echo $u['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете този потребител?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="users">
                                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                                <?php else: ?>
                                                <span style="color:#999;">(Текущ)</span>
                                                <?php endif; ?>
                                            </td>
                                          </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" style="text-align: center;">Няма намерени потребители, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        
                    </div>

                <?php elseif ($section === 'adoptions'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-file-signature"></i> Заявки за осиновяване</h2>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="adoptions">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име, имейл, телефон или животно..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Изчаква се" <?php echo $status_filter === 'Изчаква се' ? 'selected' : ''; ?>>Изчаква се</option>
                                        <option value="Потвърдена" <?php echo $status_filter === 'Потвърдена' ? 'selected' : ''; ?>>Потвърдена</option>
                                        <option value="Завършена" <?php echo $status_filter === 'Завършена' ? 'selected' : ''; ?>>Завършена</option>
                                        <option value="Отказана" <?php echo $status_filter === 'Отказана' ? 'selected' : ''; ?>>Отказана</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=adoptions" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'adoptions'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на заявка #<?php echo $edit_data['id']; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_adoption">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Пълно име:</label><input type="text" name="full_name" value="<?php echo escape($edit_data['full_name']); ?>" required></div>
                                <div class="form-group"><label>Имейл:</label><input type="email" name="email" value="<?php echo escape($edit_data['email']); ?>" required></div>
                                <div class="form-group"><label>Телефон:</label><input type="text" name="phone" value="<?php echo escape($edit_data['phone']); ?>" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" required></div>
                                <div class="form-group"><label>Животно:</label><input type="text" name="animal_name" value="<?php echo escape($edit_data['animal_name']); ?>" required></div>
                                <div class="form-group"><label>Други домашни любимци:</label><input type="text" name="other_pets" value="<?php echo escape($edit_data['other_pets']); ?>"></div>
                                <div class="form-group"><label>Съобщение:</label><textarea name="message" rows="4"><?php echo escape($edit_data['message']); ?></textarea></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Изчаква се" <?php if($edit_data['status'] == 'Изчаква се') echo 'selected'; ?>>Изчаква се</option>
                                        <option value="Потвърдена" <?php if($edit_data['status'] == 'Потвърдена') echo 'selected'; ?>>Потвърдена</option>
                                        <option value="Завършена" <?php if($edit_data['status'] == 'Завършена') echo 'selected'; ?>>Завършена</option>
                                        <option value="Отказана" <?php if($edit_data['status'] == 'Отказана') echo 'selected'; ?>>Отказана</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="?section=adoptions" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('adoptions', $search, $status_filter);
                        $stmt = $pdo->prepare("SELECT * FROM adoptions $where_clause ORDER BY submitted_at DESC");
                        $stmt->execute($params);
                        $adoptions = $stmt->fetchAll();
                        ?>
                        
                       <form method="POST" id="hidden-bulk-form-adoptions" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="adoptions"> </form>

<div id="bulk-form-adoptions">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-adoptions">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('adoptions')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('adoptions')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table adoptions-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-adoptions" class="select-all-checkbox" onclick="toggleAllCheckboxes('adoptions', this)"></th>
                                        <th>ID</th><th>Кандидат</th><th>Имейл</th><th>Телефон</th><th>Животно</th><th>Статус</th><th>Дата</th><th>Действия</th>
                                    </thead>
                                <tbody>
                                    <?php if (count($adoptions) > 0): ?>
                                        <?php foreach($adoptions as $ad): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $ad['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('adoptions')"></td>
                                              <td>#<?php echo $ad['id']; ?></td>
                                              <td><?php echo escape($ad['full_name']); ?></td>
                                              <td><?php echo escape($ad['email']); ?></td>
                                              <td><?php echo escape($ad['phone']); ?></td>
                                              <td><?php echo escape($ad['animal_name']); ?></td>
                                              <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="table" value="adoptions">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="Изчаква се" <?php if($ad['status']=='Изчаква се') echo 'selected'; ?>>Изчаква се</option>
                                                        <option value="Потвърдена" <?php if($ad['status']=='Потвърдена') echo 'selected'; ?>>Потвърдена</option>
                                                        <option value="Завършена" <?php if($ad['status']=='Завършена') echo 'selected'; ?>>Завършена</option>
                                                        <option value="Отказана" <?php if($ad['status']=='Отказана') echo 'selected'; ?>>Отказана</option>
                                                    </select>
                                                </form>
                                              </td>
                                              <td><?php echo date('d.m.Y', strtotime($ad['submitted_at'])); ?></td>
                                            <td class="action-buttons">
                                                <a href="?section=adoptions&edit_id=<?php echo $ad['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Изтриване?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="adoptions">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                             </td>
                                           </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" style="text-align: center;">Няма намерени заявки, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        
                    </div>

                <?php elseif ($section === 'appointments'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="far fa-calendar-alt"></i> Запазени часове</h2>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="appointments">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по дата или час..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Планирано" <?php echo $status_filter === 'Планирано' ? 'selected' : ''; ?>>Планирано</option>
                                        <option value="Завършено" <?php echo $status_filter === 'Завършено' ? 'selected' : ''; ?>>Завършено</option>
                                        <option value="Отказано" <?php echo $status_filter === 'Отказано' ? 'selected' : ''; ?>>Отказано</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=appointments" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'appointments'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на час #<?php echo $edit_data['id']; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_appointment">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Дата:</label><input type="date" name="appointment_date" value="<?php echo $edit_data['appointment_date']; ?>" required></div>
                                <div class="form-group"><label>Час:</label><input type="time" name="appointment_time" value="<?php echo $edit_data['appointment_time']; ?>" required></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Планирано" <?php if($edit_data['status'] == 'Планирано') echo 'selected'; ?>>Планирано</option>
                                        <option value="Завършено" <?php if($edit_data['status'] == 'Завършено') echo 'selected'; ?>>Завършено</option>
                                        <option value="Отказано" <?php if($edit_data['status'] == 'Отказано') echo 'selected'; ?>>Отказано</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="?section=appointments" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('appointments', $search, $status_filter);
                        $stmt = $pdo->prepare("SELECT * FROM appointments $where_clause ORDER BY appointment_date DESC, appointment_time DESC");
                        $stmt->execute($params);
                        $appointments = $stmt->fetchAll();
                        ?>
                        
                        <form method="POST" id="hidden-bulk-form-appointments" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="appointments">
</form>
<div id="bulk-form-appointments">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-appointments">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('appointments')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('appointments')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table appointments-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-appointments" class="select-all-checkbox" onclick="toggleAllCheckboxes('appointments', this)"></th>
                                        <th>ID</th><th>Дата/Час</th><th>Осиновяване ID</th><th>Потребител ID</th><th>Статус</th><th>Действия</th>
                                    </thead>
                                <tbody>
                                    <?php if (count($appointments) > 0): ?>
                                        <?php foreach($appointments as $app): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $app['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('appointments')"></td>
                                              <td>#<?php echo $app['id']; ?></td>
                                              <td><?php echo date('d.m.Y', strtotime($app['appointment_date'])) . ' ' . $app['appointment_time']; ?></td>
                                              <td>#<?php echo $app['adoption_id']; ?></td>
                                              <td>#<?php echo $app['user_id']; ?></td>
                                              <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="table" value="appointments">
                                                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="Планирано" <?php if($app['status']=='Планирано') echo 'selected'; ?>>Планирано</option>
                                                        <option value="Завършено" <?php if($app['status']=='Завършено') echo 'selected'; ?>>Завършено</option>
                                                        <option value="Отказано" <?php if($app['status']=='Отказано') echo 'selected'; ?>>Отказано</option>
                                                    </select>
                                                </form>
                                              </td>
                                            <td class="action-buttons">
                                                <a href="?section=appointments&edit_id=<?php echo $app['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Изтриване?');">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="appointments">
                                                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                              </td>
                                           </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" style="text-align: center;">Няма намерени часове, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        
                    </div>

                <?php elseif ($section === 'products'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-shopping-bag"></i> Продукти (Магазин)</h2>
                            <button class="btn-add" onclick="openModal('modal-product')"><i class="fas fa-plus"></i> Добави продукт</button>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="products">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име или описание..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=products" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'products'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на продукт: <?php echo escape($edit_data['name']); ?></h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit_product">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                <input type="hidden" name="current_image" value="<?php echo escape($edit_data['image_url']); ?>">
                                
                                <div class="form-group"><label>Име на продукта:</label><input type="text" name="name" value="<?php echo escape($edit_data['name']); ?>" required></div>
                                <div class="form-group"><label>Цена (€):</label><input type="number" step="0.01" name="price" value="<?php echo $edit_data['price']; ?>" required></div>
                                <div class="form-group"><label>Наличност (брой):</label><input type="number" name="stock" value="<?php echo $edit_data['stock']; ?>" required></div>
                                <div class="form-group"><label>Описание:</label><textarea name="description" rows="4"><?php echo escape($edit_data['description']); ?></textarea></div>
                                <div class="form-group"><label>Текуща снимка:</label><br><img src="<?php echo escape($edit_data['image_url']); ?>" style="width:100px; margin-bottom:10px;"><br><label>Нова снимка:</label><input type="file" name="image" accept="image/*"></div>
                                
                                <div class="form-actions">
                                    <a href="?section=products" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('products', $search);
                        $stmt = $pdo->prepare("SELECT * FROM products $where_clause ORDER BY id DESC");
                        $stmt->execute($params);
                        $products = $stmt->fetchAll();
                        ?>
                        
                        <form method="POST" id="hidden-bulk-form-products" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="products">
</form>
<div id="bulk-form-products">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-products">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('products')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('products')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table products-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-products" class="select-all-checkbox" onclick="toggleAllCheckboxes('products', this)"></th>
                                        <th>ID</th><th>Снимка</th><th>Име</th><th>Цена</th><th>Наличност</th><th>Действия</th>
                                     </thead>
                                <tbody>
                                    <?php if (count($products) > 0): ?>
                                        <?php foreach($products as $p): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $p['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('products')"></td>
                                              <td>#<?php echo $p['id']; ?></td>
                                              <td><img src="<?php echo escape($p['image_url']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px;"></td>
                                              <td><?php echo escape($p['name']); ?></td>
                                              <td><?php echo $p['price']; ?> €</td>
                                              <td><?php echo $p['stock']; ?> бр.</td>
                                            <td class="action-buttons">
                                                <a href="?section=products&edit_id=<?php echo $p['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Изтриване?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="products">
                                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                              </td>
                                           </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" style="text-align: center;">Няма намерени продукти, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        
                    </div>

                <?php elseif ($section === 'orders'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-box-open"></i> Поръчки</h2>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="orders">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име, имейл, телефон или адрес..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Непотвърдена" <?php echo $status_filter === 'Непотвърдена' ? 'selected' : ''; ?>>Непотвърдена</option>
                                        <option value="Чакаща" <?php echo $status_filter === 'Чакаща' ? 'selected' : ''; ?>>Чакаща</option>
                                        <option value="Обработва се" <?php echo $status_filter === 'Обработва се' ? 'selected' : ''; ?>>Обработва се</option>
                                        <option value="Изпратена" <?php echo $status_filter === 'Изпратена' ? 'selected' : ''; ?>>Изпратена</option>
                                        <option value="Завършена" <?php echo $status_filter === 'Завършена' ? 'selected' : ''; ?>>Завършена</option>
                                        <option value="Отказана" <?php echo $status_filter === 'Отказана' ? 'selected' : ''; ?>>Отказана</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=orders" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'orders'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на поръчка #<?php echo $edit_data['id']; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_order">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Пълно име:</label><input type="text" name="full_name" value="<?php echo escape($edit_data['full_name']); ?>" required></div>
                                <div class="form-group"><label>Имейл:</label><input type="email" name="email" value="<?php echo escape($edit_data['email']); ?>" required></div>
                                <div class="form-group"><label>Телефон:</label><input type="text" name="phone" value="<?php echo escape($edit_data['phone']); ?>" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" required></div>
                                <div class="form-group"><label>Адрес:</label><textarea name="address" rows="3" required><?php echo escape($edit_data['address']); ?></textarea></div>
                                <div class="form-group"><label>Обща сума (€):</label><input type="number" step="0.01" name="total_amount" value="<?php echo $edit_data['total_amount']; ?>" required></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Непотвърдена" <?php echo $status_filter === 'Непотвърдена' ? 'selected' : ''; ?>>Непотвърдена</option>
                                        <option value="Чакаща" <?php if($edit_data['status'] == 'Чакаща') echo 'selected'; ?>>Чакаща</option>
                                        <option value="Обработва се" <?php if($edit_data['status'] == 'Обработва се') echo 'selected'; ?>>Обработва се</option>
                                        <option value="Изпратена" <?php if($edit_data['status'] == 'Изпратена') echo 'selected'; ?>>Изпратена</option>
                                        <option value="Завършена" <?php if($edit_data['status'] == 'Завършена') echo 'selected'; ?>>Завършена</option>
                                        <option value="Отказана" <?php if($edit_data['status'] == 'Отказана') echo 'selected'; ?>>Отказана</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="?section=orders" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('orders', $search, $status_filter);
                        $stmt = $pdo->prepare("SELECT * FROM orders $where_clause ORDER BY order_date DESC");
                        $stmt->execute($params);
                        $orders = $stmt->fetchAll();
                        ?>
                        
                       <form method="POST" id="hidden-bulk-form-orders" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="orders">
</form>
<div id="bulk-form-orders">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-orders">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('orders')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('orders')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table orders-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-orders" class="select-all-checkbox" onclick="toggleAllCheckboxes('orders', this)"></th>
                                        <th>ID</th><th>Клиент</th><th>Сума</th><th>Статус</th><th>Дата</th><th>Действия</th>
                                     </thead>
                                <tbody>
                                    <?php if (count($orders) > 0): ?>
                                        <?php foreach($orders as $o): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $o['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('orders')"></td>
                                              <td>#<?php echo $o['id']; ?></td>
                                              <td><?php echo escape($o['full_name']); ?></td>
                                              <td><?php echo $o['total_amount']; ?> €</td>
                                              <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="table" value="orders">
                                                    <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                                    <select name="status" class="status-select" data-original="<?php echo $o['status']; ?>" onchange="handleStatusChange(this, <?php echo $o['id']; ?>)">
                                                        <option value="Непотвърдена" <?php echo $status_filter === 'Непотвърдена' ? 'selected' : ''; ?>>Непотвърдена</option>
                                                        <option value="Чакаща" <?php if($o['status']=='Чакаща') echo 'selected'; ?>>Чакаща</option>
                                                        <option value="Обработва се" <?php if($o['status']=='Обработва се') echo 'selected'; ?>>Обработва се</option>
                                                        <option value="Изпратена" <?php if($o['status']=='Изпратена') echo 'selected'; ?>>Изпратена</option>
                                                        <option value="Завършена" <?php if($o['status']=='Завършена') echo 'selected'; ?>>Завършена</option>
                                                        <option value="Отказана" <?php if($o['status']=='Отказана') echo 'selected'; ?>>Отказана</option>
                                                    </select>
                                                </form>
                                              </td>
                                              <td><?php echo date('d.m.Y H:i', strtotime($o['order_date'])); ?></td>
                                            <td class="action-buttons">
                                                <a href="?section=orders&edit_id=<?php echo $o['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Изтриване?');">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="orders">
                                                    <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                              </td>
                                           </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" style="text-align: center;">Няма намерени поръчки, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                             </table>
                        
                    </div>

                <?php elseif ($section === 'donations'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-hand-holding-usd"></i> Дарения</h2>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="donations">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име или имейл..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Изчаква се" <?php echo $status_filter === 'Изчаква се' ? 'selected' : ''; ?>>Изчаква се</option>
                                        <option value="Завършено" <?php echo $status_filter === 'Завършено' ? 'selected' : ''; ?>>Завършено</option>
                                        <option value="Отказано" <?php echo $status_filter === 'Отказано' ? 'selected' : ''; ?>>Отказано</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=donations" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'donations'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на дарение #<?php echo $edit_data['id']; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_donation">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Пълно име:</label><input type="text" name="full_name" value="<?php echo escape($edit_data['full_name']); ?>" required></div>
                                <div class="form-group"><label>Имейл:</label><input type="email" name="email" value="<?php echo escape($edit_data['email']); ?>" required></div>
                                <div class="form-group"><label>Сума (€):</label><input type="number" step="0.01" name="amount" value="<?php echo $edit_data['amount']; ?>" required></div>
                                <div class="form-group"><label>Метод на плащане:</label><input type="text" name="payment_method" value="<?php echo escape($edit_data['payment_method']); ?>"></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Изчаква се" <?php if($edit_data['status'] == 'Изчаква се') echo 'selected'; ?>>Изчаква се</option>
                                        <option value="Завършено" <?php if($edit_data['status'] == 'Завършено') echo 'selected'; ?>>Завършено</option>
                                        <option value="Отказано" <?php if($edit_data['status'] == 'Отказано') echo 'selected'; ?>>Отказано</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="?section=donations" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('donations', $search, $status_filter);
                        $stmt = $pdo->prepare("SELECT * FROM donations $where_clause ORDER BY donation_date DESC");
                        $stmt->execute($params);
                        $donations = $stmt->fetchAll();
                        ?>
                        
                       <form method="POST" id="hidden-bulk-form-donations" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="donations">
</form>
<div id="bulk-form-donations">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-donations">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('donations')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('donations')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table donations-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-donations" class="select-all-checkbox" onclick="toggleAllCheckboxes('donations', this)"></th>
                                        <th>ID</th><th>Дарител</th><th>Сума</th><th>Метод</th><th>Статус</th><th>Дата</th><th>Действия</th>
                                     </thead>
                                <tbody>
                                    <?php if (count($donations) > 0): ?>
                                        <?php foreach($donations as $don): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $don['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('donations')"></td>
                                              <td>#<?php echo $don['id']; ?>                                                 <td><?php echo escape($don['full_name']); ?>                                                <td><strong><?php echo $don['amount']; ?> €</strong>                                             
                                              <td><?php echo escape($don['payment_method'] ?? '-'); ?>                                                <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="table" value="donations">
                                                    <input type="hidden" name="id" value="<?php echo $don['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="Изчаква се" <?php if($don['status']=='Изчаква се') echo 'selected'; ?>>Изчаква се</option>
                                                        <option value="Завършено" <?php if($don['status']=='Завършено') echo 'selected'; ?>>Завършено</option>
                                                        <option value="Отказано" <?php if($don['status']=='Отказано') echo 'selected'; ?>>Отказано</option>
                                                    </select>
                                                </form>
                                               </td>
                                               <td><?php echo date('d.m.Y', strtotime($don['donation_date'])); ?>                                            <td class="action-buttons">
                                                <a href="?section=donations&edit_id=<?php echo $don['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете това дарение?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="donations">
                                                    <input type="hidden" name="id" value="<?php echo $don['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                               </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" style="text-align: center;">Няма намерени дарения, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        
                    </div>

                <?php elseif ($section === 'stories'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-book"></i> Истории с щастлив край</h2>
                            <button class="btn-add" onclick="openModal('modal-story')"><i class="fas fa-plus"></i> Добави история</button>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="stories">
                                <div class="search-group" style="flex: 2;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по заглавие, животно или описание..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Не одобрена" <?php echo $status_filter === 'Не одобрена' ? 'selected' : ''; ?>>Не одобрена</option>
                                        <option value="Одобрена" <?php echo $status_filter === 'Одобрена' ? 'selected' : ''; ?>>Одобрена</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=stories" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'stories'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на история: <?php echo escape($edit_data['title']); ?></h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="edit_story">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                <input type="hidden" name="current_image" value="<?php echo escape($edit_data['image_url']); ?>">
                                
                                <div class="form-group"><label>Заглавие:</label><input type="text" name="title" value="<?php echo escape($edit_data['title']); ?>" required></div>
                                <div class="form-group"><label>Име на животното:</label><input type="text" name="animal_name" value="<?php echo escape($edit_data['animal_name']); ?>" required></div>
                                <div class="form-group"><label>Съдържание:</label><textarea name="description" rows="6" required><?php echo escape($edit_data['description']); ?></textarea></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Не одобрена" <?php if($edit_data['status'] == 'Не одобрена') echo 'selected'; ?>>Не одобрена</option>
                                        <option value="Одобрена" <?php if($edit_data['status'] == 'Одобрена') echo 'selected'; ?>>Одобрена</option>
                                    </select>
                                </div>
                                <div class="form-group"><label>Текуща снимка:</label><br><img src="<?php echo escape($edit_data['image_url']); ?>" style="width:100px; margin-bottom:10px;"><br><label>Нова снимка:</label><input type="file" name="image" accept="image/*"></div>
                                
                                <div class="form-actions">
                                    <a href="?section=stories" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        list($where_clause, $params) = buildSearchQuery('success_stories', $search, $status_filter);
                        $stmt = $pdo->prepare("SELECT * FROM success_stories $where_clause ORDER BY created_at DESC");
                        $stmt->execute($params);
                        $stories = $stmt->fetchAll();
                        ?>
                        
                        <form method="POST" id="hidden-bulk-form-stories" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="stories">
</form>
<div id="bulk-form-stories">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-stories">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('stories')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('stories')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                            
                            <table class="admin-table stories-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column"><input type="checkbox" id="select-all-stories" class="select-all-checkbox" onclick="toggleAllCheckboxes('stories', this)"></th>
                                        <th>ID</th><th>Снимка</th><th>Заглавие</th><th>Животно</th><th>Статус</th><th>Дата</th><th>Действия</th>
                                     </thead>
                                <tbody>
                                    <?php if (count($stories) > 0): ?>
                                        <?php foreach($stories as $st): ?>
                                          <tr>
                                              <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $st['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('stories')">
                                                                                            </td>
                                              <td>#<?php echo $st['id']; ?></td>
                                              <td><img src="<?php echo escape($st['image_url']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px;"></td>
                                              <td><strong><?php echo escape($st['title']); ?></strong></td>
                                              <td><?php echo escape($st['animal_name']); ?></td>
                                              <td>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="table" value="success_stories">
                                                    <input type="hidden" name="id" value="<?php echo $st['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="Не одобрена" <?php if($st['status']=='Не одобрена') echo 'selected'; ?>>Не одобрена</option>
                                                        <option value="Одобрена" <?php if($st['status']=='Одобрена') echo 'selected'; ?>>Одобрена</option>
                                                    </select>
                                                </form>
                                              </td>
                                              <td><?php echo date('d.m.Y', strtotime($st['created_at'])); ?></td>
                                            <td class="action-buttons">
                                                <a href="?section=stories&edit_id=<?php echo $st['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                <form method="POST" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете тази история?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_record">
                                                    <input type="hidden" name="table" value="success_stories">
                                                    <input type="hidden" name="id" value="<?php echo $st['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                </form>
                                            </td>
                                          </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" style="text-align: center;">Няма намерени истории, отговарящи на критериите за търсене.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                              </table>
                        
                    </div>

                <?php elseif ($section === 'messages'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-envelope"></i> Съобщения от формата за контакти</h2>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="messages">
                                <div class="search-group" style="flex: 1;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по име, имейл, тема или съобщение..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Ново" <?php echo $status_filter === 'Ново' ? 'selected' : ''; ?>>Ново</option>
                                        <option value="Прочетено" <?php echo $status_filter === 'Прочетено' ? 'selected' : ''; ?>>Прочетено</option>
                                        <option value="Отговорено" <?php echo $status_filter === 'Отговорено' ? 'selected' : ''; ?>>Отговорено</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=messages" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'messages'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на съобщение #<?php echo $edit_data['id']; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_message">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Пълно име:</label><input type="text" name="full_name" value="<?php echo escape($edit_data['full_name']); ?>" required></div>
                                <div class="form-group"><label>Имейл:</label><input type="email" name="email" value="<?php echo escape($edit_data['email']); ?>" required></div>
                                <div class="form-group"><label>Телефон:</label><input type="text" name="phone" value="<?php echo escape($edit_data['phone']); ?>" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри"></div>
                                <div class="form-group"><label>Тема:</label><input type="text" name="subject" value="<?php echo escape($edit_data['subject']); ?>"></div>
                                <div class="form-group"><label>Съобщение:</label><textarea name="message" rows="5" required><?php echo escape($edit_data['message']); ?></textarea></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Ново" <?php if($edit_data['status'] == 'Ново') echo 'selected'; ?>>Ново</option>
                                        <option value="Прочетено" <?php if($edit_data['status'] == 'Прочетено') echo 'selected'; ?>>Прочетено</option>
                                        <option value="Отговорено" <?php if($edit_data['status'] == 'Отговорено') echo 'selected'; ?>>Отговорено</option>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="?section=messages" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        try {
                            $check_table = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
                            if ($check_table->rowCount() > 0) {
                                list($where_clause, $params) = buildSearchQuery('contact_messages', $search, $status_filter);
                                $stmt = $pdo->prepare("SELECT * FROM contact_messages $where_clause ORDER BY submitted_at DESC");
                                $stmt->execute($params);
                                $messages = $stmt->fetchAll();
                                
                                if (count($messages) > 0) { ?>
                                    <form method="POST" id="hidden-bulk-form-messages" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="messages">
</form>
<div id="bulk-form-messages">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-messages">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('messages')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('messages')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                                        
                                        <table class="admin-table messages-table">
                                            <thead>
                                                 <tr>
                                                    <th class="checkbox-column"><input type="checkbox" id="select-all-messages" class="select-all-checkbox" onclick="toggleAllCheckboxes('messages', this)"></th>
                                                    <th>ID</th><th>Име</th><th>Имейл</th><th>Телефон</th><th>Тема</th><th>Съобщение</th><th>Статус</th><th>Дата</th><th>Действия</th>
                                                 </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($messages as $m): ?>
                                                  <tr>
                                                      <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $m['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('messages')"></td>
                                                      <td>#<?php echo $m['id']; ?></td>
                                                      <td><?php echo escape($m['full_name']); ?></td>
                                                      <td><a href="mailto:<?php echo escape($m['email']); ?>"><?php echo escape($m['email']); ?></a></td>
                                                      <td><?php echo escape($m['phone'] ?? '-'); ?></td>
                                                      <td><?php echo escape($m['subject'] ?? '-'); ?></td>
                                                      <td><?php echo mb_strimwidth(escape($m['message']), 0, 60, "..."); ?></td>
                                                      <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="table" value="contact_messages">
                                                            <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                                <option value="Ново" <?php if($m['status']=='Ново') echo 'selected'; ?>>Ново</option>
                                                                <option value="Прочетено" <?php if($m['status']=='Прочетено') echo 'selected'; ?>>Прочетено</option>
                                                                <option value="Отговорено" <?php if($m['status']=='Отговорено') echo 'selected'; ?>>Отговорено</option>
                                                            </select>
                                                        </form>
                                                      </td>
                                                      <td><?php echo date('d.m.Y H:i', strtotime($m['submitted_at'])); ?></td>
                                                      <td class="action-buttons">
                                                          <a href="?section=messages&edit_id=<?php echo $m['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                          <form method="POST" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете това съобщение?');" style="display:inline;">
                                                              <input type="hidden" name="action" value="delete_record">
                                                              <input type="hidden" name="table" value="contact_messages">
                                                              <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                                              <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                          </form>
                                                      </td>
                                                  </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                          </table>
                                    
                                <?php } else { ?>
                                    <p style="text-align: center; padding: 40px;">Няма намерени съобщения, отговарящи на критериите за търсене.</p>
                                <?php }
                            } else { ?>
                                <p style="text-align: center; padding: 40px;">Таблицата "contact_messages" не съществува. Моля, създайте я.</p>
                            <?php }
                        } catch (Exception $e) { ?>
                            <p style="text-align: center; padding: 40px; color: red;">Грешка при зареждане на съобщенията: <?php echo escape($e->getMessage()); ?></p>
                        <?php } ?>
                    </div>

                <?php elseif ($section === 'reports'): ?>
                    <div class="profile-card">
                        <div class="header-flex">
                            <h2><i class="fas fa-exclamation-triangle"></i> Сигнали за изоставени животни</h2>
                        </div>
                        
                        <div class="search-filter-section">
                            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; width: 100%;">
                                <input type="hidden" name="section" value="reports">
                                <div class="search-group" style="flex: 1;">
                                    <label><i class="fas fa-search"></i> Търсене:</label>
                                    <input type="text" name="search" placeholder="Търси по вид животно, локация, описание или подател..." value="<?php echo escape($search); ?>">
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-filter"></i> Филтър по статус:</label>
                                    <select name="status_filter">
                                        <option value="all" <?php echo $status_filter === 'all' || $status_filter === '' ? 'selected' : ''; ?>>Всички статуси</option>
                                        <option value="Ново" <?php echo $status_filter === 'Ново' ? 'selected' : ''; ?>>Ново</option>
                                        <option value="В процес" <?php echo $status_filter === 'В процес' ? 'selected' : ''; ?>>В процес</option>
                                        <option value="Спасено" <?php echo $status_filter === 'Спасено' ? 'selected' : ''; ?>>Спасено</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label><i class="fas fa-paw"></i> Филтър по вид:</label>
                                    <select name="type_filter">
                                        <option value="all" <?php echo $type_filter === 'all' || $type_filter === '' ? 'selected' : ''; ?>>Всички видове</option>
                                        <option value="куче" <?php echo $type_filter === 'куче' ? 'selected' : ''; ?>>Куче</option>
                                        <option value="коте" <?php echo $type_filter === 'коте' ? 'selected' : ''; ?>>Коте</option>
                                        <option value="папагал" <?php echo $type_filter === 'папагал' ? 'selected' : ''; ?>>Папагал</option>
                                        <option value="заек" <?php echo $type_filter === 'заек' ? 'selected' : ''; ?>>Заек</option>
                                        <option value="друго" <?php echo $type_filter === 'друго' ? 'selected' : ''; ?>>Друго</option>
                                    </select>
                                </div>
                                <div class="search-group">
                                    <label>&nbsp;</label>
                                    <div class="search-buttons-group">
                                        <button type="submit"><i class="fas fa-search"></i> Търси</button>
                                        <a href="?section=reports" class="btn-cancel clear-search" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; margin: 0;"><i class="fas fa-times"></i> Изчисти</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if ($edit_id && $edit_data && $section === 'reports'): ?>
                        <div class="edit-section">
                            <h3><i class="fas fa-edit"></i> Редактиране на сигнал #<?php echo $edit_data['id']; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="edit_stray_report">
                                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                
                                <div class="form-group"><label>Вид животно:</label>
                                    <select name="animal_type" required>
                                        <option value="куче" <?php if($edit_data['animal_type'] == 'куче') echo 'selected'; ?>>Куче</option>
                                        <option value="коте" <?php if($edit_data['animal_type'] == 'коте') echo 'selected'; ?>>Коте</option>
                                        <option value="папагал" <?php if($edit_data['animal_type'] == 'папагал') echo 'selected'; ?>>Папагал</option>
                                        <option value="заек" <?php if($edit_data['animal_type'] == 'заек') echo 'selected'; ?>>Заек</option>
                                        <option value="друго" <?php if($edit_data['animal_type'] == 'друго') echo 'selected'; ?>>Друго</option>
                                    </select>
                                </div>
                                <div class="form-group"><label>Локация (адрес):</label><input type="text" name="location_address" value="<?php echo escape($edit_data['location_address']); ?>" required></div>
                                <div class="form-group"><label>Координати (ширина):</label><input type="text" name="latitude" value="<?php echo escape($edit_data['latitude']); ?>" placeholder="например: 42.6977082"></div>
                                <div class="form-group"><label>Координати (дължина):</label><input type="text" name="longitude" value="<?php echo escape($edit_data['longitude']); ?>" placeholder="например: 23.3218675"></div>
                                <div class="form-group"><label>Описание:</label><textarea name="description" rows="4"><?php echo escape($edit_data['description']); ?></textarea></div>
                                <div class="form-group"><label>Име на подателя:</label><input type="text" name="reporter_name" value="<?php echo escape($edit_data['reporter_name']); ?>"></div>
                                <div class="form-group"><label>Телефон на подателя:</label><input type="text" name="reporter_phone" value="<?php echo escape($edit_data['reporter_phone']); ?>" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри"></div>
                                <div class="form-group"><label>Статус:</label>
                                    <select name="status">
                                        <option value="Ново" <?php if($edit_data['status'] == 'Ново') echo 'selected'; ?>>Ново</option>
                                        <option value="В процес" <?php if($edit_data['status'] == 'В процес') echo 'selected'; ?>>В процес</option>
                                        <option value="Спасено" <?php if($edit_data['status'] == 'Спасено') echo 'selected'; ?>>Спасено</option>
                                    </select>
                                </div>
                                
                                <?php if($edit_data['image_path']): ?>
                                <div class="form-group"><label>Текуща снимка:</label><br><img src="<?php echo escape($edit_data['image_path']); ?>" style="width:150px; margin-bottom:10px;"></div>
                                <?php endif; ?>
                                
                                <div class="form-actions">
                                    <a href="?section=reports" class="btn-cancel"><i class="fas fa-times"></i> Назад</a>
                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Запази промените</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        $where_reports = [];
                        $params_reports = [];
                        
                        if (!empty($search)) {
                            $where_reports[] = "(animal_type LIKE ? OR location_address LIKE ? OR description LIKE ? OR reporter_name LIKE ? OR reporter_phone LIKE ?)";
                            $search_term = "%$search%";
                            $params_reports[] = $search_term;
                            $params_reports[] = $search_term;
                            $params_reports[] = $search_term;
                            $params_reports[] = $search_term;
                            $params_reports[] = $search_term;
                        }
                        
                        if (!empty($status_filter) && $status_filter !== 'all') {
                            $where_reports[] = "status = ?";
                            $params_reports[] = $status_filter;
                        }
                        
                        if (!empty($type_filter) && $type_filter !== 'all') {
                            $where_reports[] = "animal_type = ?";
                            $params_reports[] = $type_filter;
                        }
                        
                        $where_clause_reports = !empty($where_reports) ? "WHERE " . implode(" AND ", $where_reports) : "";
                        
                        try {
                            $check_table = $pdo->query("SHOW TABLES LIKE 'stray_reports'");
                            if ($check_table->rowCount() > 0) {
                                $stmt = $pdo->prepare("SELECT * FROM stray_reports $where_clause_reports ORDER BY created_at DESC");
                                $stmt->execute($params_reports);
                                $reports = $stmt->fetchAll();
                                
                                if (count($reports) > 0) { ?>
                                   <form method="POST" id="hidden-bulk-form-reports" style="display: none;">
    <input type="hidden" name="action" value="bulk_delete">
    <input type="hidden" name="table" value="reports">
</form>
<div id="bulk-form-reports">
    <div class="bulk-actions-bar">
        <span class="selected-count"><i class="fas fa-check-square"></i> <span id="selected-count-reports">0</span> избрани</span>
        <button type="button" class="btn-select-all" onclick="toggleSelectAll('reports')"><i class="fas fa-check-double"></i> Маркирай всички</button>
        <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('reports')"><i class="fas fa-trash"></i> Изтрий избраните</button>
    </div>
                                        
                                        <div style="overflow-x: auto;">
                                            <table class="admin-table reports-table">
                                                <thead>
                                                     <tr>
                                                        <th class="checkbox-column"><input type="checkbox" id="select-all-reports" class="select-all-checkbox" onclick="toggleAllCheckboxes('reports', this)"></th>
                                                        <th>ID</th>
                                                        <th>Снимка</th>
                                                        <th>Вид</th>
                                                        <th>Локация</th>
                                                        <th>Описание</th>
                                                        <th>Подател</th>
                                                        <th>Телефон</th>
                                                        <th>Статус</th>
                                                        <th>Дата</th>
                                                        <th>Действия</th>
                                                     </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($reports as $r): ?>
                                                      <tr>
                                                          <td class="checkbox-column"><input type="checkbox" name="selected_ids[]" value="<?php echo $r['id']; ?>" class="row-checkbox" onchange="updateSelectedCount('reports')"></td>
                                                          <td>#<?php echo $r['id']; ?></td>
                                                          <td>
                                                              <?php if($r['image_path']): ?>
                                                                  <img src="<?php echo escape($r['image_path']); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px;">
                                                              <?php else: ?>
                                                                  <span style="color:#999;">Няма снимка</span>
                                                              <?php endif; ?>
                                                          </td>
                                                          <td><strong><?php echo escape($r['animal_type']); ?></strong></td>
                                                          <td><?php echo escape($r['location_address']); ?></td>
                                                          <td><?php echo mb_strimwidth(escape($r['description']), 0, 50, "..."); ?></td>
                                                          <td><?php echo escape($r['reporter_name'] ?? 'Анонимен'); ?></td>
                                                          <td><?php echo escape($r['reporter_phone'] ?? '-'); ?></td>
                                                          <td>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="table" value="stray_reports">
                                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                                    <option value="Ново" <?php if($r['status'] == 'Ново') echo 'selected'; ?>>Ново</option>
                                                                    <option value="В процес" <?php if($r['status'] == 'В процес') echo 'selected'; ?>>В процес</option>
                                                                    <option value="Спасено" <?php if($r['status'] == 'Спасено') echo 'selected'; ?>>Спасено</option>
                                                                </select>
                                                            </form>
                                                          </td>
                                                          <td><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
                                                          <td class="action-buttons">
                                                              <a href="?section=reports&edit_id=<?php echo $r['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' && !empty($status_filter) ? '&status_filter=' . urlencode($status_filter) : ''; ?><?php echo $type_filter !== 'all' && !empty($type_filter) ? '&type_filter=' . urlencode($type_filter) : ''; ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Редактирай</a>
                                                              <form method="POST" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете този сигнал?');" style="display:inline;">
                                                                  <input type="hidden" name="action" value="delete_record">
                                                                  <input type="hidden" name="table" value="stray_reports">
                                                                  <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                                  <button type="submit" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Изтрий</button>
                                                              </form>
                                                          </td>
                                                      </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                              </table>
                                        </div>
                                    
                                <?php } else { ?>
                                    <p style="text-align: center; padding: 40px;">Няма намерени сигнали, отговарящи на критериите за търсене.</p>
                                <?php }
                            } else { ?>
                                <p style="text-align: center; padding: 40px;">Таблицата "stray_reports" не съществува.</p>
                               
                            <?php }
                        } catch (Exception $e) { ?>
                            <p style="text-align: center; padding: 40px; color: red;">Грешка при зареждане на сигналите: <?php echo escape($e->getMessage()); ?></p>
                        <?php } ?>
                    </div>
                <?php endif; ?>
                
            </section>
        </div>
    </div>
</main>

<!-- МОДАЛНИ ПРОЗОРЦИ ЗА ДОБАВЯНЕ -->
<div id="modal-animal" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3><i class="fas fa-paw"></i> Добави Ново Животно</h3>
            <button class="close-btn" onclick="closeModal('modal-animal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_animal">
            <div class="form-group">
                <label>Име:</label>
                <input type="text" name="name" required placeholder="Например: Арчи">
            </div>
            <div class="form-group">
                <label>Вид:</label>
                <select name="type" required>
                    <option value="куче">Куче</option>
                    <option value="коте">Коте</option>
                    <option value="папагал">Папагал</option>
                    <option value="заек">Заек</option>
                    <option value="хамстер">Хамстер</option>
                </select>
            </div>
            <div class="form-group">
                <label>Възраст:</label>
                <input type="text" name="age" required placeholder="Например: 2 години">
            </div>
            <div class="form-group">
                <label>Пол:</label>
                <select name="gender" required>
                    <option value="Мъжки">Мъжки</option>
                    <option value="Женски">Женски</option>
                </select>
            </div>
            <div class="form-group">
                <label>Описание:</label>
                <textarea name="description" rows="4" required placeholder="Опишете животното..."></textarea>
            </div>
            <div class="form-group">
                <label>Снимка:</label>
                <input type="file" name="image" accept="image/*" required>
                <small style="color:#777;">Разрешени формати: JPG, PNG, GIF, WEBP, AVIF</small>
            </div>
            <button type="submit" class="btn-add" style="width:100%;"><i class="fas fa-save"></i> Запази Животното</button>
        </form>
    </div>
</div>

<div id="modal-product" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3><i class="fas fa-shopping-bag"></i> Добави Нов Продукт</h3>
            <button class="close-btn" onclick="closeModal('modal-product')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            <div class="form-group">
                <label>Име на продукта:</label>
                <input type="text" name="name" required placeholder="Например: Удобно легло">
            </div>
            <div class="form-group">
                <label>Цена (€):</label>
                <input type="number" step="0.01" name="price" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Наличност (брой):</label>
                <input type="number" name="stock" value="10" required>
            </div>
            <div class="form-group">
                <label>Описание:</label>
                <textarea name="description" rows="4" placeholder="Опишете продукта..."></textarea>
            </div>
            <div class="form-group">
                <label>Снимка:</label>
                <input type="file" name="image" accept="image/*" required>
                <small style="color:#777;">Разрешени формати: JPG, PNG, GIF, WEBP, AVIF</small>
            </div>
            <button type="submit" class="btn-add" style="width:100%;"><i class="fas fa-save"></i> Запази Продукта</button>
        </form>
    </div>
</div>

<div id="modal-story" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3><i class="fas fa-book"></i> Добави Нова История</h3>
            <button class="close-btn" onclick="closeModal('modal-story')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_story">
            <div class="form-group">
                <label>Заглавие:</label>
                <input type="text" name="title" required placeholder="Например: Верен приятел">
            </div>
            <div class="form-group">
                <label>Име на животното:</label>
                <input type="text" name="animal_name" required placeholder="Например: Макс">
            </div>
            <div class="form-group">
                <label>Съдържание:</label>
                <textarea name="description" rows="6" required placeholder="Разкажете историята..."></textarea>
            </div>
            <div class="form-group">
                <label>Статус:</label>
                <select name="status">
                    <option value="Не одобрена">Не одобрена</option>
                    <option value="Одобрена">Одобрена</option>
                </select>
            </div>
            <div class="form-group">
                <label>Снимка:</label>
                <input type="file" name="image" accept="image/*" required>
                <small style="color:#777;">Разрешени формати: JPG, PNG, GIF, WEBP, AVIF</small>
            </div>
            <button type="submit" class="btn-add" style="width:100%;"><i class="fas fa-save"></i> Публикувай Историята</button>
        </form>
    </div>
</div>

<div id="modal-user" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3><i class="fas fa-user-plus"></i> Добави Нов Потребител</h3>
            <button class="close-btn" onclick="closeModal('modal-user')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group">
                <label>Пълно име:</label>
                <input type="text" name="full_name" required placeholder="Иван Иванов">
            </div>
            <div class="form-group">
                <label>Имейл:</label>
                <input type="email" name="email" required placeholder="email@example.com">
            </div>
            <div class="form-group">
                <label>Телефон:</label>
                <input type="text" name="phone" placeholder="0888123456" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри">
            </div>
            <div class="form-group">
                <label>Парола:</label>
                <input type="password" name="password" required placeholder="********">
            </div>
            <div class="form-group">
                <label>Роля:</label>
                <select name="role">
                    <option value="Потребител">Потребител</option>
                    <option value="Администратор">Администратор</option>
                </select>
            </div>
            <button type="submit" class="btn-add" style="width:100%;"><i class="fas fa-save"></i> Създай Потребител</button>
        </form>
    </div>
</div>
<div id="trackingModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:30px; border-radius:10px; width:400px; max-width:90%; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <h3 style="margin-top:0;"><i class="fas fa-truck"></i> Изпращане на поръчка #<span id="modalOrderIdDisplay"></span></h3>
        <p>Моля, въведете номер на товарителница за да уведомите клиента:</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="table" value="orders">
            <input type="hidden" name="id" id="modalOrderIdInput" value="">
            <input type="hidden" name="status" value="Изпратена">
            
            <div style="margin-bottom: 20px;">
                <input type="text" name="tracking_number" placeholder="Напр. 1023456789123" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            
            <div style="display:flex; justify-content:space-between; gap:10px;">
                <button type="button" onclick="closeTrackingModal()" style="padding:10px 15px; background:#f44336; color:#fff; border:none; border-radius:5px; cursor:pointer; width:50%;">Отказ</button>
                <button type="submit" style="padding:10px 15px; background:#4CAF50; color:#fff; border:none; border-radius:5px; cursor:pointer; width:50%;">Изпрати</button>
            </div>
        </form>
    </div>
</div>
<script>
    function handleStatusChange(selectElement, orderId) {
    if (selectElement.value === 'Изпратена') {
        // Визуално връщаме стария статус
        const originalStatus = selectElement.getAttribute('data-original');
        selectElement.value = originalStatus;
        
        document.getElementById('modalOrderIdDisplay').innerText = orderId;
        document.getElementById('modalOrderIdInput').value = orderId;
        document.getElementById('trackingModal').style.display = 'flex';
    } else {
        // За всички други статуси просто изпращаме формата
        selectElement.form.submit();
    }
}

function closeTrackingModal() {
    document.getElementById('trackingModal').style.display = 'none';
}
    // Функции за масово изтриване и чекбоксове
    function updateSelectedCount(section) {
        const checkboxes = document.querySelectorAll(`#bulk-form-${section} .row-checkbox:checked`);
        const countSpan = document.getElementById(`selected-count-${section}`);
        if (countSpan) {
            countSpan.textContent = checkboxes.length;
        }
    }
    
    function toggleAllCheckboxes(section, sourceCheckbox) {
        const checkboxes = document.querySelectorAll(`#bulk-form-${section} .row-checkbox`);
        checkboxes.forEach(cb => {
            cb.checked = sourceCheckbox.checked;
        });
        updateSelectedCount(section);
    }
    
    function toggleSelectAll(section) {
        const selectAllCheckbox = document.getElementById(`select-all-${section}`);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            toggleAllCheckboxes(section, selectAllCheckbox);
        } else {
            const checkboxes = document.querySelectorAll(`#bulk-form-${section} .row-checkbox`);
            const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
            checkboxes.forEach(cb => {
                cb.checked = anyUnchecked;
            });
            updateSelectedCount(section);
        }
    }
    
   function submitBulkDelete(section) {
    // Взимаме всички маркирани чекбоксове от таблицата
    const checkedBoxes = document.querySelectorAll(`#bulk-form-${section} .row-checkbox:checked`);
    
    if (checkedBoxes.length === 0) {
        alert('Моля, изберете поне един запис за изтриване!');
        return;
    }
    
    if (confirm(`Сигурни ли сте, че искате да изтриете ${checkedBoxes.length} записа? Това действие е необратимо!`)) {
        // Намираме скритата форма
        const form = document.getElementById(`hidden-bulk-form-${section}`);
        
        // Изчистваме стари данни, ако има такива
        form.querySelectorAll('.dynamic-id').forEach(el => el.remove());
        
        // Взимаме всяко избрано ID и го слагаме в скритата форма
        checkedBoxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = cb.value;
            input.className = 'dynamic-id';
            form.appendChild(input);
        });
        
        // Изпращаме формата към PHP
        form.submit();
    }
}
    
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    window.onclick = function(event) {
        if (event.target.classList && event.target.classList.contains('admin-modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
    
    document.addEventListener("DOMContentLoaded", function() {
        const hamburgerBtn = document.getElementById('mobile-menu-btn');
        const navActions = document.getElementById('nav-actions-menu');

        if(hamburgerBtn && navActions) {
            hamburgerBtn.addEventListener('click', function() {
                navActions.classList.toggle('active');
            });
        }
        
        const sections = ['animals', 'users', 'adoptions', 'appointments', 'products', 'orders', 'donations', 'stories', 'messages', 'reports'];
        sections.forEach(section => {
            updateSelectedCount(section);
        });
    });
</script>
</body>
</html>