<?php
require_once 'db_connect.php';

// Проверка за активно търсене и текуща страница
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // Брой продукти на страница
$offset = ($page - 1) * $limit;

// 1. Взимане на общия брой продукти за изчисляване на страниците
if (!empty($searchQuery)) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name LIKE :name AND stock > 0");
    $countStmt->execute([':name' => '%' . $searchQuery . '%']);
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0");
}
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// 2. Получаване на продуктите за текущата страница
if (!empty($searchQuery)) {
   // Използваме само именувани параметри (:name, :limit, :offset)
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :name AND stock > 0 ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':name', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$products = $stmt->fetchAll();

// Валидация на сървъра за контактната форма

if (!empty($fullName) && !empty($email) && !empty($subject) && !empty($message)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Моля, въведете валиден имейл адрес (напр. ime@domain.com)!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $phone, $subject, $message]);
            $messageSent = true;
        } catch (Exception $e) {
            $error = 'Грешка при изпращане на съобщението: ' . $e->getMessage();
        }
    }
} else {
    $error = 'Моля, попълнете всички задължителни полета!';
}

// Взимане на броя артикули в количката за ползване в JavaScript
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Благотворителен Магазин | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       
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
                <li><a href="accessories.php" class="active">Аксесоари</a></li>
                <li><a href="stories.php">Истории</a></li>
                <li><a href="signal.php">Сигнал</a></li>
                <li><a href="contacts.php">Контакти</a></li>
            </ul>
            
            <div class="nav-actions">
            <div class="cart-icon" onclick="openCart()">
    <i class="fas fa-shopping-cart"></i>
    <?php $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
    <span class="cart-badge" id="cartBadgeCount" style="<?php echo $cartCount > 0 ? '' : 'display: none;'; ?>">
        <?php echo $cartCount; ?>
    </span>
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
        <h1 class="section-title">Магазин с кауза</h1>
      
        <div class="donation-info-card">
            <div class="donation-info-content">
                <div class="donation-info-logo">
                    <div class="logo-wrapper">
                        <img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img-accessories">
                    </div>
                </div>

                <div class="donation-info-text">
                    <h3 class="info-title">Какво означава покупка от нас?</h3>
                    <p class="info-description">Всеки продукт, който закупиш, директно подпомага животните в приюта. Средствата се използват за:</p>
                    
                    <ul class="info-list">
                        <li><span class="list-icon">🐾</span> Храна за всички животни</li>
                        <li><span class="list-icon">💊</span> Ветеринарни лечения и лекарства</li>
                        <li><span class="list-icon">🏠</span> Подобряване на условията в приюта</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="search-container fade-in">
            <form method="GET" action="accessories.php" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Търсене..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="search-btn" title="Търси">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <?php if (!empty($searchQuery)): ?>
                <div class="search-results-info">
                    За: <strong><?php echo htmlspecialchars($searchQuery); ?></strong>
                    <a href="accessories.php" class="clear-search"><i class="fas fa-times"></i></a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($products) > 0): ?>
        <div id="accessories-grid" class="grid-container">
            <?php foreach ($products as $product): ?>
            <div class="card fade-in">
                <img src="<?php echo escape($product['image_url']); ?>" alt="<?php echo escape($product['name']); ?>" loading="lazy">
                <div class="card-content">
                    <h3><?php echo escape($product['name']); ?></h3>
                    <div class="price"><?php echo number_format($product['price'], 2); ?> €.</div>
                    <p style="color:#666; margin:10px 0; font-size:0.9rem; flex-grow:1;"><?php echo escape($product['description'] ?: '100% от печалбата отива за животните'); ?></p>
                    <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo escape($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo escape($product['image_url']); ?>')">
                        <i class="fas fa-cart-plus"></i> Добави
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <ul class="pagination fade-in">
            <?php
            // Пазим стойността на търсенето в URL-а
            $searchParam = !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '';
            
            // Бутон Предишна
            if ($page > 1) {
                echo '<li><a href="?page=' . ($page - 1) . $searchParam . '">&laquo; Предишна</a></li>';
            }
            
            // Номера на страниците
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i == $page) {
                    echo '<li><span class="active">' . $i . '</span></li>';
                } else {
                    echo '<li><a href="?page=' . $i . $searchParam . '">' . $i . '</a></li>';
                }
            }
            
            // Бутон Следваща
            if ($page < $totalPages) {
                echo '<li><a href="?page=' . ($page + 1) . $searchParam . '">Следваща &raquo;</a></li>';
            }
            ?>
        </ul>
        <?php endif; ?>
        <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <i class="fas fa-search" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
            <h3 style="color: var(--secondary-color); margin-bottom: 10px;">Няма намерени продукти</h3>
            <?php if (!empty($searchQuery)): ?>
                <p>Опитайте с друга ключова дума или <a href="accessories.php" style="color: var(--primary-color);">вижте всички продукти</a>.</p>
            <?php else: ?>
                <p>Скоро ще добавим нови продукти в магазина.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <br>
    </main>
    <p style="text-align: center; max-width: 800px; margin: 0 auto 40px; font-size: 1.1rem;">
        <i class="fas fa-heart" style="color: var(--primary-color);"></i> 
        <strong>100% от печалбата</strong> отива за храна и ветеринарни грижи за животните в приюта. 
        Пазарувай и прави добро!
    </p>
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

    <script src="script.js"></script>
    <script>
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
    </script>
</body>
</html>