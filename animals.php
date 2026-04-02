<?php
require_once 'db_connect.php';

// Позволени типове според ENUM
$allowed_types = ['куче', 'коте', 'папагал', 'заек', 'хамстер'];

// Взимане на параметрите от филтъра
$type_filter = $_GET['type'] ?? 'all';
$gender_filter = $_GET['gender'] ?? 'all';
$age_filter = trim($_GET['age'] ?? '');

// Странициране - настройки
$limit = 6; // Брой животни на страница
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Базови условия за заявката
$where_sql = "status = 'Налично'";
$params = [];

// Динамично добавяне на условия към заявката според избраните филтри
if ($type_filter !== 'all' && in_array($type_filter, $allowed_types)) {
    $where_sql .= " AND type = ?";
    $params[] = $type_filter;
}

if ($gender_filter !== 'all') {
    
    $where_sql .= " AND gender = ?";
    $params[] = $gender_filter;
}

if (!empty($age_filter)) {
    $where_sql .= " AND age LIKE ?";
    $params[] = "%" . $age_filter . "%";
}

// 1. Взимане на ОБЩИЯ брой животни, отговарящи на филтрите
$count_query = "SELECT COUNT(*) FROM animals WHERE $where_sql";
$stmtCountFilter = $pdo->prepare($count_query);
$stmtCountFilter->execute($params);
$totalRecords = $stmtCountFilter->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// 2. Взимане на самите животни за текущата страница
$data_query = "SELECT * FROM animals WHERE $where_sql LIMIT ? OFFSET ?";
// Добавяме limit и offset към параметрите
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($data_query);

foreach ($params as $index => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($index + 1, $value, $type);
}
$stmt->execute();
$animals = $stmt->fetchAll();

// Оптимизирано взимане на броячи за статистика
$counts = [
    'куче' => 0, 
    'коте' => 0, 
    'папагал' => 0, 
    'заек' => 0, 
    'хамстер' => 0
];
$totalAvailable = 0;

$stmtCounts = $pdo->query("SELECT type, COUNT(*) as total FROM animals WHERE status = 'Налично' GROUP BY type");
while ($row = $stmtCounts->fetch()) {
    $type = $row['type'];
    if (isset($counts[$type])) {
        $counts[$type] = $row['total'];
    }
    $totalAvailable += $row['total'];
}

// Функция за генериране на URL за страниците със запазване на филтрите
function buildPageUrl($pageNum) {
    $get_params = $_GET;
    $get_params['page'] = $pageNum;
    return '?' . http_build_query($get_params);
}

// Валидация на сървъра
if (isset($_POST['full_name'], $_POST['email'], $_POST['subject'], $_POST['message'])) {
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    if (!empty($fullName) && !empty($email) && !empty($subject) && !empty($message)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Моля, въведете валиден имейл адрес (напр. ime@domain.com)!';
        } else {
            try {
                $stmtInsert = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([$fullName, $email, $phone, $subject, $message]);
                $messageSent = true;
            } catch (Exception $e) {
                $error = 'Грешка при изпращане на съобщението: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Моля, попълнете всички задължителни полета!';
    }
}

// Взимане на броя артикули в количката
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Нашите животни | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
                <li><a href="animals.php" class="active">Животни</a></li>
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
                <?php if (isLoggedIn()): ?>
                <div class="user-dropdown">
                    <button class="login-btn">
                        <i class="fas fa-user"></i> <?php echo escape(getCurrentUser()['full_name']); ?>
                    </button>
                    <div class="dropdown-content">
                            <?php $user = getCurrentUser(); ?>
<?php if ($user['role'] === 'Администратор'): ?>
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
        <h1 class="section-title">Те търсят дом</h1>
        
        <div id="filterToggleContainer">
            <button class="btn btn-outline" onclick="toggleFilters()" id="filterToggleBtn">
                <i class="fas fa-filter"></i> Филтри
            </button>
        </div>

        <div class="filter-panel" id="filterPanel">
            <form method="GET" action="animals.php" class="filter-form">
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Вид животно</label>
                    <select name="type">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Всички (<?php echo $totalAvailable; ?>)</option>
                        <option value="куче" <?php echo $type_filter === 'куче' ? 'selected' : ''; ?>>Кучета (<?php echo $counts['куче']; ?>)</option>
                        <option value="коте" <?php echo $type_filter === 'коте' ? 'selected' : ''; ?>>Котки (<?php echo $counts['коте']; ?>)</option>
                        <option value="папагал" <?php echo $type_filter === 'папагал' ? 'selected' : ''; ?>>Папагали (<?php echo $counts['папагал']; ?>)</option>
                        <option value="заек" <?php echo $type_filter === 'заек' ? 'selected' : ''; ?>>Зайци (<?php echo $counts['заек']; ?>)</option>
                        <option value="хамстер" <?php echo $type_filter === 'хамстер' ? 'selected' : ''; ?>>Хамстери (<?php echo $counts['хамстер']; ?>)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Пол</label>
                    <select name="gender">
                        <option value="all" <?php echo $gender_filter === 'all' ? 'selected' : ''; ?>>Всички</option>
                        <option value="Мъжки" <?php echo $gender_filter === 'Мъжки' ? 'selected' : ''; ?>>Мъжки</option>
                        <option value="Женски" <?php echo $gender_filter === 'Женски' ? 'selected' : ''; ?>>Женски</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Възраст (ключова дума)</label>
                    <input type="text" name="age" placeholder="напр. 'месеца', 'година', '2'..." value="<?php echo htmlspecialchars($age_filter); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px;">
                        <i class="fas fa-search"></i> Приложи
                    </button>
                </div>
                
                <?php if ($type_filter !== 'all' || $gender_filter !== 'all' || !empty($age_filter)): ?>
                <div class="form-group" style="margin-bottom: 0;">
                    <a href="animals.php" class="btn btn-outline" style="display: block; text-align: center; padding: 10px; width: 100%; box-sizing: border-box;">
                        Изчисти
                    </a>
                </div>
                <?php endif; ?>

            </form>
        </div>

        <?php if (empty($animals)): ?>
        <div style="text-align: center; padding: 50px;">
            <img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img-accessories">
            <h3 style="color: var(--secondary-color); margin-bottom: 10px;">Няма намерени животни по тези критерии</h3>
            <p>Моля, опитайте с други филтри или изчистете търсенето.</p>
            <a href="animals.php" class="btn btn-primary" style="margin-top: 20px;">Покажи всички животни</a>
        </div>
        <?php else: ?>
        <div id="animal-grid" class="grid-container">
            <?php foreach ($animals as $animal): ?>
            <div class="card fade-in">
                <div class="card-tag" style="text-transform: capitalize;"><?php echo escape($animal['type']); ?></div>
                <img src="<?php echo escape($animal['image_url']); ?>" alt="<?php echo escape($animal['name']); ?>" loading="lazy">
                <div class="card-content">
                    <h3><?php echo escape($animal['name']); ?></h3>
                    <p style="color:#666; font-size:0.9rem;">
                        <i class="fas <?php echo ($animal['gender'] == 'Мъжко' || $animal['gender'] == 'Мъжки') ? 'fa-mars' : 'fa-venus'; ?>"></i> 
                        <?php echo escape($animal['gender']); ?>, <?php echo escape($animal['age']); ?>
                    </p>
                    <p style="margin: 15px 0; flex-grow:1;"><?php echo escape($animal['description']); ?></p>
                    <button class="btn btn-primary" onclick="openAdopt('<?php echo escape($animal['name']); ?>')">Осинови</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo buildPageUrl($page - 1); ?>">&laquo; Предишна</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo buildPageUrl($i); ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo buildPageUrl($page + 1); ?>">Следваща &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>

        <div class="fade-in" style="background: var(--white); padding: 2.5rem; border-radius: 15px; box-shadow: var(--shadow);">
            <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 25px; font-size: 1.5rem;">Процес на осиновяване</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; text-align: center;">
                <div>
                    <div style="background: var(--accent-color); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-weight: bold; font-size: 1.3rem; color: var(--secondary-color);">1</div>
                    <h4 style="color: var(--secondary-color); margin-bottom: 10px;">Попълни форма</h4>
                    <p style="font-size: 0.95rem; color: #666;">Попълни заявка за осиновяване</p>
                </div>
                <div>
                    <div style="background: var(--accent-color); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-weight: bold; font-size: 1.3rem; color: var(--secondary-color);">2</div>
                    <h4 style="color: var(--secondary-color); margin-bottom: 10px;">Запознай се</h4>
                    <p style="font-size: 0.95rem; color: #666;">Посети приюта и се запознай с животното</p>
                </div>
                <div>
                    <div style="background: var(--accent-color); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-weight: bold; font-size: 1.3rem; color: var(--secondary-color);">3</div>
                    <h4 style="color: var(--secondary-color); margin-bottom: 10px;">Интервю</h4>
                    <p style="font-size: 0.95rem; color: #666;">Разговор с нашия екип</p>
                </div>
                <div>
                    <div style="background: var(--accent-color); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-weight: bold; font-size: 1.3rem; color: var(--secondary-color);">4</div>
                    <h4 style="color: var(--secondary-color); margin-bottom: 10px;">Нов дом</h4>
                    <p style="font-size: 0.95rem; color: #666;">Предаване на животното и последваща подкрепа</p>
                </div>
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
                    <label for="cartTerms">Съгласен/съгласна съм с общите условия и политиката за поверителност</label>
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
                    <label for="donationTerms">Съгласен/съгласна съм с условията за дарения</label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Дари сега</button>
            </form>
        </div>
    </div>

    <div id="adoptionModal" class="modal">
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <div class="modal-header">
                <h3 class="modal-title">Форма за осиновяване</h3>
                <p>Дайте дом на приятел</p>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <form method="POST" action="process_adoption.php">
                <div class="form-group">
                    <label>Избрано животно</label>
                    <input type="text" id="animalNameInput" name="animal_name" placeholder="Име на животното" required readonly style="background: #f5f5f5;">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Вашите имена *</label>
                        <input type="text" name="full_name" placeholder="Име и фамилия" required value="<?php echo escape(getCurrentUser()['full_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="phone" placeholder="089 123 4567" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" required oninput="this.value=this.value.replace(/[^0-9]/g,'');" value="<?php echo escape(getCurrentUser()['phone']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл" required value="<?php echo escape(getCurrentUser()['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Имате ли други домашни любимци?</label>
                    <select name="other_pets">
                        <option value="">Изберете</option>
                        <option value="Не">Не</option>
                        <option value="Да, куче">Да, куче</option>
                        <option value="Да, котка">Да, котка</option>
                        <option value="Да, друго животно">Да, друго животно</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Защо искате да осиновите? *</label>
                    <textarea rows="4" name="message" placeholder="Разкажете ни малко повече за вас и вашите условия..." required></textarea>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="terms" id="adoptionTerms" required>
                    <label for="adoptionTerms">Съгласен/съгласна съм с условията за осиновяване и приемам отговорността за животното</label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Изпрати заявка</button>
            </form>
            
            <?php else: ?>
            <input type="hidden" id="animalNameInput">
            
            <div style="text-align: center; padding: 30px 10px;">
                <i class="fas fa-lock" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="margin-bottom: 15px; font-size: 1.2rem;">Необходим е вход</h4>
                <p style="margin-bottom: 25px; color: #666;">За да подадете заявка за осиновяване, първо трябва да влезете в своя профил или да си създадете такъв.</p>
                <a href="login.php" class="btn btn-primary" style="display: inline-block; width: 100%;">Вход / Регистрация</a>
            </div>
            <?php endif; ?>
            
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    // Показване / скриване на панела с филтри при мобилни
    function toggleFilters() {
        const panel = document.getElementById('filterPanel');
        panel.classList.toggle('active');
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
    
    // Валидация за количката
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

    // Валидация на даренията
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
    </script>
</body>
</html>