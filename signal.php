<?php
require_once 'db_connect.php';

// Инициализация на съобщения
$successMessage = '';
$errorMessage = '';

// Обработка на подадения сигнал
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $animal_type = trim($_POST['animal_type'] ?? '');
    $location_address = trim($_POST['location_address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $reporter_phone = trim($_POST['reporter_phone'] ?? '');

    // Обработка на качената снимка
    $image_path = null;
    if (isset($_FILES['animal_image']) && $_FILES['animal_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/signals/';
        // Създаваме папката, ако не съществува
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_info = pathinfo($_FILES['animal_image']['name']);
        $file_ext = strtolower($file_info['extension']);
        // Обновени позволени формати според изискването
        $allowed_exts = ['jpg', 'jpeg', 'png', 'avif'];

        if (in_array($file_ext, $allowed_exts)) {
            try {
                // Взимаме най-голямото ID до момента от таблицата със сигнали
                $countStmt = $pdo->query("SELECT MAX(id) FROM stray_reports");
                $maxId = (int)$countStmt->fetchColumn();
                $nextNumber = $maxId + 1; // Следващият пореден номер

                // Генерираме новото поредно име на файла 
                $new_filename = 'signal_' . $nextNumber . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['animal_image']['tmp_name'], $destination)) {
                    $image_path = $destination;
                } else {
                    $errorMessage = 'Грешка при запазване на снимката.';
                }
            } catch (Exception $e) {
                $errorMessage = 'Грешка при генериране на име на файла: ' . $e->getMessage();
            }
        } else {
            $errorMessage = 'Невалиден файлов формат. Позволени са само PNG, AVIF и JPG/JPEG.';
        }
    } else {
        $errorMessage = 'Моля, качете снимка на животното.';
    }

    // Ако няма грешки до момента, записваме в базата
    if (empty($errorMessage)) {
        if (!empty($animal_type) && !empty($location_address) && !empty($latitude) && !empty($longitude)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO stray_reports (animal_type, location_address, latitude, longitude, image_path, description, reporter_name, reporter_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Ново')");
                $stmt->execute([$animal_type, $location_address, $latitude, $longitude, $image_path, $description, $reporter_name, $reporter_phone]);
                $successMessage = 'Сигналът е подаден успешно! Благодарим ви, че помагате на животните.';
            } catch (Exception $e) {
                $errorMessage = 'Грешка при запис в базата: ' . $e->getMessage();
            }
        } else {
            $errorMessage = 'Моля, попълнете всички задължителни полета, включително точните координати (Latitude и Longitude).';
        }
    }
}

$rescuedLastMonth = 0;
try {
    $stmtRescued = $pdo->query("
        SELECT COUNT(*) 
        FROM animals 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
          AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)
    ");
    $rescuedLastMonth = (int)$stmtRescued->fetchColumn();
} catch (Exception $e) {
    // В случай че няма такава колона или има друга грешка, запазваме 0
    $rescuedLastMonth = 0;
}

// Взимане на броя артикули в количката
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
// Взимане на текущия потребител, ако е логнат
$currentUser = function_exists('isLoggedIn') && isLoggedIn() && function_exists('getCurrentUser') ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подай сигнал - Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Глобални правила за формите тук */
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: inherit;
        }

        /* Стилизиране на новата информационна секция */
        .signal-hero {
            text-align: center;
 
            background: linear-gradient(to bottom, #f8f9fa, #ffffff);
            border-radius: 15px;
            margin-bottom: 40px;
        }
        .signal-hero p {
            max-width: 700px;
            margin: 0 auto 30px;
            color: #555;
            line-height: 1.6;
            font-size: 1.1rem;
        }
        .signal-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            max-width: 900px;
            margin: 0 auto;
        }
        .step-card {
            background: #fff;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-bottom: 4px solid #17a2b8;
            transition: transform 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-5px);
        }
        .step-icon {
            font-size: 2.5rem;
            color: #17a2b8;
            margin-bottom: 15px;
        }
        .step-card h3 { margin-bottom: 10px; font-size: 1.2rem; color: #333; }
        .step-card p { font-size: 0.95rem; color: #666; margin: 0; }

        /* Оформление с три колони за формата и панелите */
        .signal-layout {
            display: grid;
            grid-template-columns: 280px 1fr 280px;
            gap: 30px;
            margin-bottom: 60px;
            align-items: start;
        }

        /* Странични панели */
        .side-panel {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }
        .side-panel h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #17a2b8;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-list { list-style: none; padding: 0; }
        .info-list li {
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.4;
            color: #555;
            display: flex;
            gap: 10px;
        }
        .info-list li i { color: #28a745; margin-top: 3px; flex-shrink: 0; }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-box span { display: block; font-size: 1.5rem; font-weight: bold; color: #333; }
        .stat-box label { font-size: 0.8rem; color: #777; text-transform: uppercase; }

        /* Предупреждение за спешни случаи */
        .emergency-alert {
            background: #fff3cd;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 5px solid #ffc107;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .emergency-alert i { font-size: 1.5rem; color: #ffc107; flex-shrink: 0; }

        /* Формата */
        .signal-container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            width: 100%;
            box-sizing: border-box;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        .location-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: 0.3s;
            width: auto;
        }
        .location-btn:hover { background: #138496; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .coord-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        /* Стилизиране за бутоните в модалите */
        .btn-outline {
            background: transparent;
            border: 2px solid #17a2b8;
            color: #17a2b8;
            padding: 8px 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-outline:hover, .btn-outline.active {
            background: #17a2b8;
            color: white;
        }

        /* =========================================
           АДАПТИВНОСТ 
           ========================================= */
           
        /* Таблети и по-малки десктоп екрани */
        @media (max-width: 1100px) {
            .signal-layout { 
                grid-template-columns: 1fr; 
            }
            .side-panel { 
                position: static; 
                order: 2; 
                margin-bottom: 20px;
            }
            .signal-container { 
                order: 1; 
                margin-bottom: 30px; 
            }
        }

        /* Мобилни телефони */
        @media (max-width: 768px) {
            .signal-hero {
                padding: 100px 15px;
            }
            .signal-hero p {
                font-size: 1rem;
            }
            .signal-container {
                padding: 25px 15px;
            }
            .form-grid {
                grid-template-columns: 1fr; 
            }
            .form-group.full-width {
                grid-column: 1 / -1;
            }
            .coord-fields {
                grid-template-columns: 1fr; 
            }
            .location-btn {
                width: 100%; 
            }
            .emergency-alert {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .signal-steps {
                grid-template-columns: 1fr; 
            }
        }
        
        /* Много малки телефони */
        @media (max-width: 480px) {
            .step-card {
                padding: 15px;
            }
            .side-panel {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo"><img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img"> Надежда</a>
            
            <div class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></div>
            
            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li><a href="index.php">Начало</a></li>
                    <li><a href="about.php">За нас</a></li>
                    <li><a href="animals.php">Животни</a></li>
                    <li><a href="accessories.php">Аксесоари</a></li>
                    <li><a href="stories.php">Истории</a></li>
                    <li><a href="signal.php" class="active">Сигнал</a></li>
                    <li><a href="contacts.php">Контакти</a></li>
                </ul>
                
                <div class="nav-actions">
                    <div class="cart-icon" onclick="openCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="cart-badge" id="cartBadgeCount"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <div class="user-dropdown">
                        <button class="login-btn">
                            <i class="fas fa-user"></i> <?php echo function_exists('escape') ? escape($currentUser['full_name']) : $currentUser['full_name']; ?>
                        </button>
                        <div class="dropdown-content">
                            <?php if ($currentUser['role'] === 'Администратор'): ?>
                                <a href="admin-dashboard.php">Администраторски панел</a>
                            <?php else: ?>
                                <a href="profile.php">Моят профил</a>
                            <?php endif; ?>
                            <a href="logout.php">Изход</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <button class="login-btn" onclick="location.href='login.php'">
                        <i class="fas fa-user"></i> Вход/Регистрация
                    </button>
                    <?php endif; ?>
                    <button class="donate-btn" onclick="location.href='donation.php'">Дари сега</button>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <section class="signal-hero">
            <h1 class="section-title">Подаване на сигнал за бездомно животно</h1>
            <p>Вашият сигнал е първата и най-важна стъпка към спасяването на един живот. Моля, предоставете възможно най-точна информация, за да могат нашите екипи да открият и помогнат на животното бързо.</p>
            
            <div class="signal-steps">
                <div class="step-card">
                    <div class="step-icon"><i class="fas fa-camera-retro"></i></div>
                    <h3>1. Снимайте</h3>
                    <p>Направете ясна снимка. Тя ни помага да преценим състоянието и нуждите на животното.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <h3>2. Локация</h3>
                    <p>Използвайте GPS бутона за максимална точност при откриването на мястото.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon"><i class="fas fa-phone-alt"></i></div>
                    <h3>3. Контакт</h3>
                    <p>Оставете актуален номер, в случай че имаме нужда от допълнителни насоки.</p>
                </div>
            </div>
        </section>
        
        <div class="signal-layout">
            
            <aside class="side-panel">
                <h3><i class="fas fa-lightbulb"></i> Какво да правите?</h3>
                <ul class="info-list">
                    <li><i class="fas fa-check"></i> Опитайте се да не плашите животното.</li>
                    <li><i class="fas fa-check"></i> Ако е безопасно, останете при него до пристигане на екипа.</li>
                    <li><i class="fas fa-check"></i> Осигурете му вода, ако времето е горещо.</li>
                    <li><i class="fas fa-check"></i> Наблюдавайте посоката му на движение, ако тръгне нанякъде.</li>
                </ul>
                <div style="margin-top: 20px; font-size: 0.85rem; color: #888;">
                    <p>Вашата бърза реакция е критична!</p>
                </div>
            </aside>

            <div class="signal-container">
                
                <div class="emergency-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Спешен случай?</strong> Ако животното е в критично състояние, блъснато на пътя или проявява силна агресия, след изпращане на формата се обадете директно на: <strong>089 373 3552</strong>.
                    </div>
                </div>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $successMessage; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <div id="clientError" class="alert alert-error" style="display:none;"></div>

                <form method="POST" action="signal.php" enctype="multipart/form-data" id="signalForm">
                    <div class="form-grid">
                        
                        <div class="form-group">
                            <label>Вид животно *</label>
                            <select name="animal_type" required>
                                <option value="" disabled selected>Изберете вид...</option>
                                <option value="куче">Куче</option>
                                <option value="коте">Коте</option>
                                <option value="папагал">Папагал</option>
                                <option value="заек">Заек</option>
                                <option value="хамстер">Хамстер</option>
                                <option value="друго">Друго</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Снимка на животното * (.png, .avif, .jpg)</label>
                            <input type="file" name="animal_image" id="animal_image" accept=".png, .avif, .jpg, .jpeg" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Адрес / Локация (описание) *</label>
                            <input type="text" name="location_address" placeholder="Напр. ул. Скопие 4, до парка..." required>
                        </div>

                        <div class="form-group full-width" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                            <label><i class="fas fa-crosshairs" style="color: #17a2b8;"></i> Точни координати (Задължително)</label>
                            <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Ако сте близо до животното в момента, натиснете синия бутон, за да вземем точните координати автоматично.</p>
                            
                            <button type="button" class="location-btn" onclick="getLocation()">
                                <i class="fas fa-location-arrow"></i> Вземи текуща локация
                            </button>
                            
                            <div class="coord-fields">
                                <input type="text" name="latitude" id="latitude" placeholder="Географска ширина (Latitude)" required readonly style="background: #fff;">
                                <input type="text" name="longitude" id="longitude" placeholder="Географска дължина (Longitude)" required readonly style="background: #fff;">
                            </div>
                            
                            <div id="locationError" style="color: #721c24; margin-top: 10px; font-size: 0.9rem; display: none;"></div>
                        </div>

                        <div class="form-group full-width">
                            <label>Описание на състоянието *</label>
                            <textarea name="description" rows="4" placeholder="Разкажете ни повече за състоянието на животното (напр. ранено, уплашено, изглежда гладно, има ли малки)..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Вашите Имена *</label>
                            <input type="text" name="reporter_name" placeholder="Име и Фамилия" required value="<?php echo $currentUser ? (function_exists('escape') ? escape($currentUser['full_name']) : $currentUser['full_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Телефон за връзка *</label>
                            <input type="tel" name="reporter_phone" placeholder="089 123 4567" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" oninput="this.value=this.value.replace(/[^0-9]/g,'');" required value="<?php echo $currentUser ? (function_exists('escape') ? escape($currentUser['phone']) : $currentUser['phone']) : ''; ?>">
                        </div>

                        <div class="form-group full-width" style="text-align: center; margin-top: 15px;">
                            <button type="submit" name="submit_report" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 30px; width: 100%; border-radius: 8px; background: #17a2b8; color: #fff; border: none; cursor: pointer;">
                                <i class="fas fa-paper-plane"></i> Изпрати сигнал към приюта
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <aside class="side-panel">
                <h3><i class="fas fa-chart-line"></i> Нашата дейност</h3>
                <div class="stat-box">
                    <span><?php echo $rescuedLastMonth; ?></span>
                    <label>Спасени миналият месец</label>
                </div>
                <div class="stat-box">
                    <span>15</span>
                    <label>Активни патрула</label>
                </div>
                
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                    <i class="fas fa-heart" style="color: #e91e63; font-size: 1.5rem; margin-bottom: 10px;"></i>
                    <p style="font-size: 0.85rem; margin-bottom: 10px;">Помогнете ни да помагаме!</p>
                    <button class="donate-btn" style="width: 100%; background: #e91e63; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;" onclick="location.href='donation.php'">Дари за гориво</button>
                </div>
            </aside>

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
                <h3 style="margin-top: 20px;">Авторски права</h3>
                <ul>
                    <li><a href="https://fontawesome.com/license/free">FontAwesome Лиценз</a></li>
                    <li><a href="https://unsplash.com/license">Unsplash Лиценз</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Контакти</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i> ул. „Скопие“ 4, Гоце Делчев</li>
                    <li><i class="fas fa-phone" style="margin-right: 8px;"></i> 089 373 3552</li>
                    <li><i class="fas fa-envelope" style="margin-right: 8px;"></i> yusuf.kapanak@pmggd.bg</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>© 2026 Приют "Надежда". Всички права запазени.</p>
        </div>
    </footer>

    <div id="cartModal" class="modal">
        <div class="modal-content">
            <button class="close-modal">×</button>
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
                    <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['full_name']) : ''; ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="phone" placeholder="089 123 4567" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" required oninput="this.value=this.value.replace(/[^0-9]/g,'');" value="<?php echo isLoggedIn() ? escape(getCurrentUser()['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Адрес за доставка *</label>
                    <textarea rows="3" name="address" placeholder="Адрес, град, пощенски код" required></textarea>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="terms" id="cartTerms" required>
                    <label for="cartTerms">Съгласен/съгласна съм с <a href="terms.php" target="_blank" style="color: var(--primary-color);">Общите условия</a> и <a href="privacy.php" target="_blank" style="color: var(--primary-color);">Политиката за поверителност</a></label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Направи поръчка</button>
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
                    <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['email']) : ''; ?>">
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
    // Мобилно меню
    function toggleMenu() {
        const navMenu = document.getElementById('navMenu');
        const icon = document.querySelector('.hamburger i');
        navMenu.classList.toggle('active');
        if(navMenu.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }

    // Незабавна валидация на снимката
    document.getElementById('animal_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const errorDiv = document.getElementById('clientError'); 
        
        if (file) {
            const fileName = file.name.toLowerCase();
            const isAllowed = fileName.endsWith('.png') || fileName.endsWith('.avif') || fileName.endsWith('.jpg') || fileName.endsWith('.jpeg');
            
            if (!isAllowed) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Невалиден формат! Моля, качете само снимки във формат .png, .avif или .jpg';
                errorDiv.style.display = 'block';
                this.value = ''; 
            } else {
                errorDiv.style.display = 'none';
            }
        }
    });

    // Взимане на локация
    function getLocation() {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const errorDiv = document.getElementById('locationError');
        const btn = document.querySelector('.location-btn');

        if (navigator.geolocation) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Зареждане...';
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    latInput.value = position.coords.latitude;
                    lngInput.value = position.coords.longitude;
                    errorDiv.style.display = 'none';
                    btn.innerHTML = '<i class="fas fa-check"></i> Локацията е фиксирана';
                    btn.style.background = '#28a745';
                }, 
                function(error) {
                    btn.innerHTML = '<i class="fas fa-location-arrow"></i> Опитай пак';
                    let errorMsg = "Възникна грешка при взимане на локацията.";
                    if (error.code === 1) errorMsg = "Моля, разрешете достъпа до локация във вашия браузър.";
                    else if (error.code === 2) errorMsg = "Локацията е недостъпна в момента.";
                    else if (error.code === 3) errorMsg = "Времето за изчакване изтече.";
                    
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + errorMsg + ' Може да въведете координатите и ръчно.';
                    errorDiv.style.display = 'block';
                },
                { enableHighAccuracy: true }
            );
        } else {
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Геолокацията не се поддържа от вашия браузър.';
            errorDiv.style.display = 'block';
        }
    }

    // ==========================================
    // КЛИЕНТСКА ВАЛИДАЦИЯ ЗА ФОРМИТЕ В МОДАЛИТЕ
    // ==========================================

    const cartForm = document.getElementById('cartForm');
    if(cartForm) {
        cartForm.addEventListener('submit', function(e) {
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
            }
        });
    }

    const donationForm = document.getElementById('donationForm');
    if(donationForm) {
        donationForm.addEventListener('submit', function(e) {
            let errorDiv = document.getElementById('donationError');
            errorDiv.style.display = 'none';
            
            let amount = document.getElementById('donationAmount').value;
            if (amount <= 0) {
                e.preventDefault();
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Моля въведете валидна сума за дарение!';
                errorDiv.style.display = 'block';
            }
        });
    }
    </script>
</body>
</html>