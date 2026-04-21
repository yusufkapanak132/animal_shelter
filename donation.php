<?php
require_once 'db_connect.php';

// Валидация на сървъра
if (!empty($fullName) && !empty($email) && !empty($subject) && !empty($message)) {
    // Проверка за валиден имейл формат
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

$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Приют "Надежда" - Направи Дарение</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        
        html {
            scroll-behavior: smooth;
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
                    <li><a href="signal.php">Сигнал</a></li>
                    <li><a href="contacts.php">Контакти</a></li>
                </ul>
                
                <div class="nav-actions">
                    <div class="cart-icon" onclick="openCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
                        $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                        if ($cartCount > 0): ?>
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

    <main style="padding-top: 80px; background-color: #f9f9f9; min-height: 80vh;">
        <div class="donation-page-container fade-in">
            <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 10px;">Направи Дарение</h2>
            <p style="text-align: center; margin-bottom: 30px; color: #666;">Вашата помощ осигурява храна и лечение за нашите животни.</p>
            
            <div id="pageDonationError" style="display:none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align:center;"></div>
            
            <form id="pageDonationForm" method="POST" action="process_donation.php" style="scroll-margin-top: 100px;">
                <div class="form-group">
                    <label>Сума (EUR)</label>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                        <button type="button" class="btn btn-outline" onclick="setPageDonationAmount(10)">10 €</button>
                        <button type="button" class="btn btn-outline" onclick="setPageDonationAmount(20)">20 €</button>
                        <button type="button" class="btn btn-outline" onclick="setPageDonationAmount(50)">50 €</button>
                        <button type="button" class="btn btn-outline" onclick="setPageDonationAmount(100)">100 €</button>
                    </div>
                    <input type="number" id="pageDonationAmount" name="amount" placeholder="Друга сума" required min="1">
                </div>
                
                <div class="form-group">
                    <label>Име и фамилия </label>
                    <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email </label>
                    <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['email']) : ''; ?>">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="terms" id="pageDonationTerms" required>
                    <label for="pageDonationTerms">Съгласен/съгласна съм с <a href="terms.php" target="_blank" style="color: var(--primary-color);">условията за дарения</a></label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Дари сега</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-col">
                <h3>За приют "Надежда"</h3>
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
                    <li><a href="#pageDonationForm" onclick="setPageDonationAmount(50)">Дари</a></li>
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
                    <label>Име и фамилия </label>
                    <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['full_name']) : ''; ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Телефон </label>
                        <input type="tel" name="phone" placeholder="089 123 4567" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" required oninput="this.value=this.value.replace(/[^0-9]/g,'');" value="<?php echo isLoggedIn() ? escape(getCurrentUser()['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email </label>
                        <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Адрес за доставка </label>
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

    <script src="script.js"></script>
    <script>
        // Функция за задаване на сумата
        function setPageDonationAmount(amount) {
            document.getElementById('pageDonationAmount').value = amount;
        }
setPageDonationAmount(50)
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

        // Валидация за количката
        let cartForm = document.getElementById('cartForm');
        if (cartForm) {
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

        // Валидация за формата в СТРАНИЦАТА
        let pageDonationForm = document.getElementById('pageDonationForm');
        if (pageDonationForm) {
            pageDonationForm.addEventListener('submit', function(e) {
                let errorDiv = document.getElementById('pageDonationError');
                errorDiv.style.display = 'none';
                let amount = document.getElementById('pageDonationAmount').value;
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