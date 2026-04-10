<?php
// profile.php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Вземане на данните за потребителя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$success_message = '';
$error_message = '';

// Обработка на изтриване на акаунт
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirmation_input = trim($_POST['delete_confirmation'] ?? '');
    $expected_phrase = 'ИЗТРИЙ ' . $user['full_name'];
    
    // Защита: администратор не може да бъде изтрит
    if ($user['role'] === 'Администратор') {
        $error_message = "Администраторският акаунт не може да бъде изтрит през потребителския панел.";
    } elseif ($confirmation_input !== $expected_phrase) {
        $error_message = "Потвърдителната фраза не съвпада. Акаунтът не е изтрит.";
    } else {
        try {
            // Изтриване на потребителя (свързаните записи ще останат с NULL поради ON DELETE SET NULL)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Прекратяване на сесията и пренасочване
            session_destroy();
            header('Location: index.php?account_deleted=1');
            exit;
        } catch (PDOException $e) {
            $error_message = "Грешка при изтриване на акаунта: " . $e->getMessage();
        }
    }
}

// Обработка на формата за промяна на запазен час
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_appointment') {
    $appointment_id = $_POST['appointment_id'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];

    // Проверка дали часът принадлежи на потребителя и не е зает от друг
    $checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ? AND status = 'Планирано'");
    $checkStmt->execute([$appointment_id, $user_id]);
    
    if ($checkStmt->fetch()) {
        // Проверка дали новият час е свободен
        $checkSlotStmt = $pdo->prepare("
            SELECT id FROM appointments 
            WHERE appointment_date = ? AND appointment_time = ? 
            AND id != ? AND status = 'Планирано'
        ");
        $checkSlotStmt->execute([$new_date, $new_time, $appointment_id]);
        
        if ($checkSlotStmt->fetch()) {
            $error_message = "Избраният час вече е зает. Моля, изберете друг час.";
        } else {
            try {
                $updateStmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ?");
                $updateStmt->execute([$new_date, $new_time, $appointment_id]);
                $success_message = "Часът за посещение беше успешно променен!";
            } catch (Exception $e) {
                $error_message = "Грешка при промяна на часа: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Грешка: Не можете да промените този час (вероятно вече е завършен или отказан).";
    }
}

// Взимане на броя артикули в количката
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Получаване на история на поръчки
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Получаване на история на осиновявания със запазените часове
$stmt = $pdo->prepare("
    SELECT a.*, app.id as appointment_id, app.appointment_date, app.appointment_time, app.status as appointment_status 
    FROM adoptions a
    LEFT JOIN appointments app ON a.id = app.adoption_id
    WHERE a.user_id = ? 
    ORDER BY a.submitted_at DESC
");
$stmt->execute([$user_id]);
$adoptions = $stmt->fetchAll();

// Получаване на история на дарения
$stmt = $pdo->prepare("SELECT * FROM donations WHERE user_id = ? ORDER BY donation_date DESC");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();

// Изчисляване на статистика за профила
$total_adoptions = count($adoptions);
$total_orders = count($orders);
$total_donated = array_sum(array_column($donations, 'amount'));
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моят профил | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
    <style>
        /* Стилове за модален прозорец за изтриване на акаунт - центриран чрез flexbox */
        .modal-delete {
            display: none; /* Скрит по подразбиране */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            /* Центриране на съдържанието */
            justify-content: center;
            align-items: center;
        }

        .modal-delete .modal-content {
            background-color: #fefefe;
            margin: 0; /* Премахваме margin: auto, тъй като flexbox се грижи за центрирането */
            padding: 30px;
            border: none;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-delete .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-delete .close-modal:hover,
        .modal-delete .close-modal:focus {
            color: #000;
            text-decoration: none;
        }

        .modal-delete .modal-header {
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-delete .modal-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.modal .modal-content {
    background-color: #fefefe;
    margin: 0;
    padding: 30px;
    border: none;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    position: relative;
    animation: fadeIn 0.3s ease-out;
    max-height: 90vh;
    overflow-y: auto;
}
    </style>
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="index.php" class="logo"><img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img">Надежда</a>
        <div class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></div>
        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="index.php">Начало</a></li>
                <li><a href="about.php">За нас</a></li>
                <li><a href="animals.php">Животни</a></li>
                <li><a href="accessories.php">Аксесоари</a></li>
                <li><a href="stories.php">Истории</a></li>
                <li><a href="signal.php">Сигнал</a></li>
                <li><a href="contacts.php">Контакти</a></li>
            </ul>
            <div class="nav-actions">
                <div class="cart-icon" onclick="openCart()">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown">
                    <button class="login-btn active">
                        <i class="fas fa-user"></i> <?php echo escape($user['full_name']); ?>
                    </button>
                    <div class="dropdown-content">
                        <?php if ($user['role'] === 'Администратор'): ?>
                            <a href="admin-dashboard.php">Администраторски панел</a>
                        <?php else: ?>
                            <a href="profile.php">Моят профил</a>
                        <?php endif; ?>
                        <a href="logout.php">Изход</a>
                    </div>
                </div>
                <button class="donate-btn" onclick="location.href='donation.php'">Дари сега</button>
            </div>
        </div>
    </div>
</nav>

<main class="profile-page">
    <div class="container">
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo escape($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo escape($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-layout">
            
            <!-- Лява колона -->
            <aside>
                <div class="profile-card" style="width:400px;">
                    <div class="profile-header">
                        <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                        <h2><?php echo escape($user['full_name']); ?></h2>
                        <p class="role-badge">Роля: <strong><?php echo escape($user['role']); ?></strong></p>
                    </div>
                    
                    <div class="info-row">
                        <i class="fas fa-envelope" title="Имейл"></i> 
                        <span><?php echo escape($user['email']); ?></span>
                        
                        <?php if (isset($user['email_status']) && $user['email_status'] === 'Потвърден'): ?>
                            <i class="fas fa-check-circle" style="color: #28a745; width: auto; margin-left: 10px; font-size: 16px;" title="Имейлът е потвърден"></i>
                        <?php else: ?>
                            <a href="verify_email.php" style="font-size: 13px; margin-left: 8px; color: var(--primary-color, #ff6b6b); text-decoration: underline;">
                                <i class="fas fa-exclamation-circle" style="color: #ffc107; width: auto; margin-left: 10px; font-size: 16px;" title="Имейлът не е потвърден"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="info-row"><i class="fas fa-phone"></i> <?php echo escape($user['phone'] ?: 'Не е посочен телефон'); ?></div>
                    <div class="info-row"><i class="fas fa-calendar-alt"></i> Член от: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                </div>

                <div class="profile-card">
                    <h2 class="section-heading" style="font-size: 18px;"><i class="fas fa-cog"></i> Настройки</h2>
                    <ul class="settings-menu">
                        <li><a href="edit_profile.php"><i class="fas fa-user-edit"></i> Редактирай профила</a></li>
                        <li><a href="change_password.php"><i class="fas fa-key"></i> Смяна на парола</a></li>
                        <li><a href="verify_email.php"><i class="fas fa-envelope"></i> Активиране на имейл</a></li>
                        <li><a href="#" onclick="openDeleteModal(); return false;"><i class="fas fa-trash-alt"></i> Изтриване на акаунта</a></li>
                        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Изход от профила</a></li>
                    </ul>
                </div>
            </aside>
    
            <!-- Дясна колона -->
            <section class="profile-content">
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <i class="fas fa-paw"></i>
                        <div class="stat-value"><?php echo $total_adoptions; ?></div>
                        <div class="stat-label">Осиновявания</div>
                    </div>
                    <div class="stat-box">
                        <i class="fas fa-shopping-bag"></i>
                        <div class="stat-value"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Поръчки</div>
                    </div>
                    <div class="stat-box">
                        <i class="fas fa-hand-holding-heart"></i>
                        <div class="stat-value"><?php echo number_format($total_donated, 2); ?> €</div>
                        <div class="stat-label">Дарени средства</div>
                    </div>
                </div>

                <div class="profile-card">
                    <h2 class="section-heading"><i class="fas fa-paw"></i> Мои заявки за осиновяване</h2>
                    <?php if (!empty($adoptions)): ?>
                        <div class="history-grid">
                            <?php foreach ($adoptions as $adoption): ?>
                            <div class="history-item status-<?php echo explode(' ', $adoption['status'])[0]; ?>">
                                <div class="item-details">
                                    <h4>Животно: <?php echo escape($adoption['animal_name']); ?></h4>
                                    <p>Подадена на: <?php echo date('d.m.Y', strtotime($adoption['submitted_at'])); ?></p>
                                    
                                    <?php if ($adoption['appointment_id']): ?>
                                        <p style="margin-top: 8px; color: var(--primary-color);">
                                            <strong><i class="far fa-calendar-check"></i> Запазен час:</strong> 
                                            <?php echo date('d.m.Y', strtotime($adoption['appointment_date'])); ?> от <?php echo date('H:i', strtotime($adoption['appointment_time'])); ?> ч.
                                            <span class="badge <?php echo escape($adoption['appointment_status']); ?>" style="margin-left: 5px;"><?php echo escape($adoption['appointment_status']); ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="item-actions" style="text-align: right;">
                                    <span class="badge <?php echo escape($adoption['status']); ?>"><?php echo escape($adoption['status']); ?> (Осиновяване)</span><br>
                                    
                                    <?php if ($adoption['appointment_id'] && $adoption['appointment_status'] === 'Планирано'): ?>
                                        <button class="btn btn-outline" style="padding: 6px 12px; font-size: 13px; margin-top: 10px;" onclick="openEditForm(<?php echo $adoption['appointment_id']; ?>, '<?php echo $adoption['appointment_date']; ?>', '<?php echo $adoption['appointment_time']; ?>')">
                                            <i class="fas fa-edit"></i> Промени часа
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Форма за редактиране на час -->
                                <div class="edit-form" id="form-<?php echo $adoption['appointment_id']; ?>">
                                    <form method="POST" id="edit-form-<?php echo $adoption['appointment_id']; ?>" onsubmit="return validateAndSubmitForm(<?php echo $adoption['appointment_id']; ?>)">
                                        <input type="hidden" name="action" value="update_appointment">
                                        <input type="hidden" name="appointment_id" value="<?php echo $adoption['appointment_id']; ?>">
                                        <input type="hidden" name="new_date" id="new_date_<?php echo $adoption['appointment_id']; ?>" value="">
                                        <input type="hidden" name="new_time" id="new_time_<?php echo $adoption['appointment_id']; ?>" value="">
                                        
                                        <div class="form-group">
                                            <label><i class="far fa-calendar-alt"></i> Изберете нова дата:</label>
                                            <input type="date" id="appointment_date_<?php echo $adoption['appointment_id']; ?>" 
                                                   min="<?php echo date('Y-m-d'); ?>" 
                                                   value="<?php echo $adoption['appointment_date']; ?>"
                                                   onchange="fetchTimeSlotsForEdit(<?php echo $adoption['appointment_id']; ?>)">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label><i class="far fa-clock"></i> Изберете свободен час:</label>
                                            <div id="time_slots_<?php echo $adoption['appointment_id']; ?>" class="time-slots">
                                                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Зареждане на свободни часове...</div>
                                            </div>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                                            <button type="submit" class="btn btn-primary" style="flex: 1;">Запази промените</button>
                                            <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeEditForm(<?php echo $adoption['appointment_id']; ?>)">Отказ</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #777;">
                            <i class="fas fa-cat" style="font-size: 40px; margin-bottom: 15px; color: #ddd;"></i>
                            <p>Все още нямате подадени заявки за осиновяване.</p>
                            <a href="animals.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Разгледай животните</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-card">
                    <h2 class="section-heading"><i class="fas fa-shopping-bag"></i> Моите поръчки от магазина</h2>
                    <?php if (!empty($orders)): ?>
                        <div class="history-grid">
                            <?php foreach ($orders as $order): ?>
                            <div class="history-item status-<?php echo explode(' ', $order['status'])[0]; ?>">
                                <div class="item-details">
                                    <h4>Поръчка #<?php echo $order['id']; ?></h4>
                                    <p><i class="far fa-clock"></i> Дата: <?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                                    <p style="margin-top: 5px; font-size: 16px;"><strong>Сума: <?php echo $order['total_amount']; ?> €</strong></p>
                                </div>
                                <span class="badge <?php echo escape($order['status']); ?>"><?php echo escape($order['status']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #777;">
                            <p>Все още нямате направени поръчки.</p>
                            <a href="accessories.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Разгледай нашите продукти</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-card" style="margin-bottom: 0;">
                    <h2 class="section-heading"><i class="fas fa-hand-holding-heart"></i> Моите дарения</h2>
                    <?php if (!empty($donations)): ?>
                        <div class="history-grid">
                            <?php foreach ($donations as $donation): ?>
                            <div class="history-item status-<?php echo explode(' ', $donation['status'])[0]; ?>">
                                <div class="item-details">
                                    <h4>Дарение: <?php echo $donation['amount']; ?> €</h4>
                                    <p><i class="far fa-calendar-alt"></i> Дата: <?php echo date('d.m.Y', strtotime($donation['donation_date'])); ?></p>
                                    <p><i class="far fa-credit-card"></i> Метод: <?php echo escape($donation['payment_method'] ?: 'Не е посочен'); ?></p>
                                </div>
                                <span class="badge <?php echo escape($donation['status']); ?>"><?php echo escape($donation['status']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #777;">
                            <i class="fas fa-heart" style="font-size: 40px; margin-bottom: 15px; color: #ddd;"></i>
                            <p>Все още нямате направени дарения. Всяка помощ е безценна за нас!</p>
                            <a href="donation.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Направи дарение</a>
                        </div>
                    <?php endif; ?>
                </div>

            </section>
        </div>
    </div>
</main>

<footer>
    <div class="footer-content">
        <div class="footer-col">
            <h3>За Приют Надежда</h3>
            <p>Ние сме неправителствена организация, посветена на спасяването и лечението на бездомни животни.</p>
            <div class="social-icons">
                <a href="https://www.facebook.com/yusuf.kapanak/"><i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/y_kapanak/"><i class="fab fa-instagram"></i></a>
                <a href="https://discord.com/users/776490369084031076"><i class="fab fa-discord"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h3>Бързи връзки</h3>
            <ul>
                <li><a href="animals.php">Осинови</a></li>
                <li><a href="#" onclick="quickDonate(50)">Дари</a></li>
                <li><a href="about.php">За нас</a></li>
                <li><a href="accessories.php">Аксесоари</a></li>
                <li><a href="stories.php">Истории</a></li>
                <li><a href="signal.php">Сигнал</a></li>
            </ul>
        </div>
        <div class="footer-col">
    <h3>Политики</h3>
    <ul>
        <li><a href="terms.php">Общи условия</a></li>
        <li><a href="privacy.php">Политика за поверителност</a></li>
    </ul>
    <h3>Авторски права</h3>
    <ul>
    <li><a href="https://fontawesome.com/license/free">FontAwesome Лиценз</a></li>
    <li><a href="https://unsplash.com/license">Unsplash Лиценз</a></li>
    </ul>
</div>
        <div class="footer-col">
            <h3>Контакти</h3>
            <ul>
                <li><i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i> ул. "Синигер" 15, София</li>
                <li><i class="fas fa-phone" style="margin-right: 8px;"></i> 0888 123 456</li>
                <li><i class="fas fa-envelope" style="margin-right: 8px;"></i> info@priut-nadezhda.bg</li>
            </ul>
        </div>
    </div>
    <div class="copyright">
        <p>&copy; 2026 Приют "Надежда". Всички права запазени.</p>
    </div>
</footer>

<!-- Модален прозорец за изтриване на акаунт -->
<div id="deleteAccountModal" class="modal-delete">
    <div class="modal-content">
        <button class="close-modal" onclick="closeDeleteModal()">×</button>
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-exclamation-triangle" style="color: #c62828;"></i> Изтриване на профила</h3>
        </div>
        
        <div id="deleteError" style="display:none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>
        
        <p style="margin-bottom: 15px; color: #333;"><strong>Внимание:</strong> Това действие е необратимо! Всички ваши лични данни ще бъдат изтрити завинаги. Историята на поръчки, осиновявания и дарения ще бъде запазена анонимно за статистически цели.</p>
        <p style="margin-bottom: 20px; background: #fff3cd; padding: 10px; border-radius: 5px; color: #856404;">
            За да потвърдите изтриването, моля напишете фразата <strong>"ИЗТРИЙ <?php echo escape($user['full_name']); ?>"</strong> в полето по-долу.
        </p>
        
        <form id="deleteAccountForm" method="POST" action="profile.php" onsubmit="return validateDeleteForm()">
            <input type="hidden" name="delete_account" value="1">
            <div class="form-group">
                <label>Потвърждение *</label>
                <input type="text" id="deleteConfirmation" name="delete_confirmation" placeholder="ИЗТРИЙ <?php echo escape($user['full_name']); ?>" required>
                <small style="color: #666;">Въведете точно "ИЗТРИЙ <?php echo escape($user['full_name']); ?>"</small>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn" style="background: #c62828; color: white; flex: 1;"><i class="fas fa-trash-alt"></i> Потвърди изтриването</button>
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeDeleteModal()">Отказ</button>
            </div>
        </form>
    </div>
</div>

<!-- Модални прозорци за количка и дарение (остават с клас "modal") -->
<div id="cartModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" onclick="closeCart()">×</button>
        <div class="modal-header">
            <h3 class="modal-title">Количка</h3>
        </div>
        
        <div id="cartError" style="display:none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align:center;"></div>
        
        <div id="cartItems" style="max-height: 300px; overflow-y: auto;"></div>
        
        <div class="cart-total">
            Общо: <span id="cartTotal">0.00 €</span>
        </div>
        
        <form id="cartForm" method="POST" action="process_order.php" style="margin-top: 20px;">
            <div class="form-group">
                <label>Име и фамилия *</label>
                <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo escape($user['full_name']); ?>">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" placeholder="089 123 4567" pattern="[0-9]{10}" required value="<?php echo escape($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required value="<?php echo escape($user['email']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Адрес за доставка *</label>
                <textarea rows="3" name="address" placeholder="Адрес, град, пощенски код" required></textarea>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="terms" id="cartTerms" required>
                <label for="cartTerms">Съгласен/съгласна съм с общите условия</label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Завърши поръчката</button>
        </form>
    </div>
</div>

<div id="donationModal" class="modal">
    <div class="modal-content">
        <button class="close-modal">×</button>
        <div class="modal-header">
            <h3 class="modal-title">Направи дарение</h3>
            <p>Вашата помощ променя животи!</p>
        </div>
        
        <div id="donationError" style="display:none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align:center;"></div>
        
        <form id="donationForm" method="POST" action="process_donation.php">
            <div class="form-group">
                <label>Сума (EUR)</label>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                    <button type="button" class="btn btn-outline" onclick="setDonationAmount(10)">10 €</button>
                    <button type="button" class="btn btn-outline" onclick="setDonationAmount(20)">20 €</button>
                    <button type="button" class="btn btn-outline" onclick="setDonationAmount(50)">50 €</button>
                    <button type="button" class="btn btn-outline" onclick="setDonationAmount(100)">100 €</button>
                </div>
                <input type="number" id="donationAmount" name="amount" placeholder="Друга сума" required min="1">
            </div>
            
            <div class="form-group">
                <label>Име и фамилия *</label>
                <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo escape($user['full_name']); ?>">
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл" required value="<?php echo escape($user['email']); ?>">
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="terms" id="donationTerms" required>
                <label for="donationTerms">Съгласен/съгласна съм с <a href="terms.php" target="_blank" style="color: var(--primary-color);">условията за дарения</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Дари сега</button>
        </form>
    </div>
</div>

<script src="script.js"></script>
<script>
// Работни часове
const WORKING_HOURS = [
    '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
    '15:00', '15:30', '16:00', '16:30', '17:00'
];

// Глобални променливи за избраните часове
let selectedTimes = {};

function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('active');
}

// Отваряне на модала за изтриване
function openDeleteModal() {
    document.getElementById('deleteAccountModal').style.display = 'flex';
    document.getElementById('deleteConfirmation').value = '';
    document.getElementById('deleteError').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('deleteAccountModal').style.display = 'none';
}

// Валидация на формата за изтриване
function validateDeleteForm() {
    const input = document.getElementById('deleteConfirmation').value.trim();
    const expected = 'ИЗТРИЙ <?php echo escape(addslashes($user['full_name'])); ?>';
    const errorDiv = document.getElementById('deleteError');
    
    if (input !== expected) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Потвърдителната фраза не съвпада. Моля, въведете точния текст.';
        errorDiv.style.display = 'block';
        return false;
    }
    
    return confirm('Сигурни ли сте, че искате да изтриете акаунта си? Това действие е необратимо!');
}

// Отваряне на формата за редактиране
function openEditForm(appointmentId, currentDate, currentTime) {
    const form = document.getElementById('form-' + appointmentId);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        const dateInput = document.getElementById('appointment_date_' + appointmentId);
        if (dateInput) {
            dateInput.value = currentDate;
        }
        fetchTimeSlotsForEdit(appointmentId);
    }
}

// Затваряне на формата за редактиране
function closeEditForm(appointmentId) {
    const form = document.getElementById('form-' + appointmentId);
    form.style.display = 'none';
    delete selectedTimes[appointmentId];
}

// Функция за зареждане на свободните часове за редактиране
function fetchTimeSlotsForEdit(appointmentId) {
    const dateInput = document.getElementById('appointment_date_' + appointmentId);
    const date = dateInput.value;
    const slotsContainer = document.getElementById('time_slots_' + appointmentId);
    
    if (!date) {
        slotsContainer.innerHTML = '<div style="color: #999;">Моля, изберете дата първо</div>';
        return;
    }
    
    slotsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Зареждане на свободни часове...</div>';
    
    fetch(`get_timeslots.php?date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                slotsContainer.innerHTML = `<div style="color: #c62828;">${data.error}</div>`;
            } else {
                slotsContainer.innerHTML = '';
                
                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.className = `slot-btn ${slot.is_booked ? 'booked' : ''}`;
                    btn.textContent = slot.time;
                    btn.disabled = slot.is_booked;
                    
                    if (!slot.is_booked) {
                        btn.onclick = () => selectTimeForEdit(appointmentId, btn, slot.time);
                    } else {
                        btn.title = "Този час вече е зает";
                    }
                    
                    if (selectedTimes[appointmentId] === slot.time) {
                        btn.classList.add('selected');
                    }
                    
                    slotsContainer.appendChild(btn);
                });
            }
        })
        .catch(err => {
            console.error('Error fetching slots:', err);
            slotsContainer.innerHTML = '<div style="color: #c62828;">Грешка при зареждане на часовете. Моля, опитайте отново.</div>';
        });
}

// Избиране на час за редактиране
function selectTimeForEdit(appointmentId, btnElement, time) {
    const container = document.getElementById('time_slots_' + appointmentId);
    container.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btnElement.classList.add('selected');
    selectedTimes[appointmentId] = time;
}

// Валидиране и изпращане на формата
function validateAndSubmitForm(appointmentId) {
    const dateInput = document.getElementById('appointment_date_' + appointmentId);
    const selectedTime = selectedTimes[appointmentId];
    
    if (!dateInput.value) {
        alert('Моля, изберете дата за посещението.');
        return false;
    }
    
    if (!selectedTime) {
        alert('Моля, изберете час от списъка със свободни часове.');
        return false;
    }
    
    document.getElementById('new_date_' + appointmentId).value = dateInput.value;
    document.getElementById('new_time_' + appointmentId).value = selectedTime;
    
    return confirm('Сигурни ли сте, че искате да промените часа на ' + 
                   new Date(dateInput.value).toLocaleDateString('bg-BG') + 
                   ' от ' + selectedTime + ' ч.?');
}

// Скрол анимация
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

// Клиентска валидация за формите в модалите
document.getElementById('cartForm').addEventListener('submit', function(e) {
    let errorDiv = document.getElementById('cartError');
    errorDiv.style.display = 'none';
    
    let cartCount = <?php echo $cartCount; ?>;
    
    let badge = document.getElementById('cartBadgeCount');
    if(badge && badge.style.display !== 'none') {
        cartCount = parseInt(badge.innerText || '0');
    }
    
    if (cartCount === 0) {
        e.preventDefault(); 
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Количката е празна! Моля, добавете продукти преди поръчка.';
        errorDiv.style.display = 'block';
        return;
    }
});

document.getElementById('donationForm').addEventListener('submit', function(e) {
    let errorDiv = document.getElementById('donationError');
    errorDiv.style.display = 'none';
    
    let amount = document.getElementById('donationAmount').value;
    if (amount <= 0) {
        e.preventDefault();
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Моля въведете валидна сума за дарение!';
        errorDiv.style.display = 'block';
    }
});

// Функция за бързо дарение
function quickDonate(amount) {
    document.getElementById('donationAmount').value = amount;
    document.getElementById('donationModal').style.display = 'flex';
}

// Затваряне на модали при клик извън тях
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteAccountModal');
    const cartModal = document.getElementById('cartModal');
    const donationModal = document.getElementById('donationModal');
    
    if (event.target === deleteModal) {
        deleteModal.style.display = 'none';
    }
    if (event.target === cartModal) {
        cartModal.style.display = 'none';
    }
    if (event.target === donationModal) {
        donationModal.style.display = 'none';
    }
}
</script>
</body>
</html>
