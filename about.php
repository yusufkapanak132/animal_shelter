<?php
require_once 'db_connect.php';

// Получаване на статистики за отбора
$stmt = $pdo->query("SELECT (
    (SELECT COUNT(*) FROM contact_messages WHERE subject = 'Доброволчество' AND status = 'Отговорено') + 
    (SELECT COUNT(*) FROM appointments WHERE status = 'Завършено')
) AS total");

$volunteers = $stmt->fetch()['total'] ?? 0;

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
// Взимане на броя артикули в количката за ползване в JavaScript
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>За нас | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
 <nav>
    <div class="nav-container">
        <a href="index.php" class="logo"><img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img"> Надежда</a>
        
        <div class="hamburger" onclick="toggleMenu()"><i class="fas fa-bars"></i></div>
        
        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="index.php">Начало</a></li>
                <li><a href="about.php" class="active">За нас</a></li>
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

    <main class="container">
        <h1 class="section-title">Нашата Мисия</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; align-items: center; margin-bottom: 3rem;">
            <div class="fade-in">
                <img src="assets/images/about_us.avif" alt="Екип" style="width: 100%; border-radius: 15px; box-shadow: var(--shadow);">
            </div>
            <div class="fade-in">
                <h2 style="color: var(--primary-color); margin-bottom: 20px; font-size: 1.8rem;">Защо правим това?</h2>
                <p style="margin-bottom: 20px; line-height: 1.8; font-size: 1.05rem;">
                    <strong>Приют "Надежда"</strong> е създаден през 2026 г. от група ентусиасти, които не можеха да подминат страдащите животни на улицата. 
                    Днес сме най-големият приют в региона, посветен на спасяването и намирането на нов дом за бездомни животни.
                </p>
                <p style="margin-bottom: 20px; line-height: 1.8; font-size: 1.05rem;">
                    Нашата мисия е да осигурим безопасно убежище, медицински грижи и любов на всеки животно в нужда, докато не намери свой постоянен дом.
                </p>
                <a href="contacts.php" class="btn btn-primary" style="margin-top: 10px;">Стани доброволец</a>
                <button class="btn btn-outline" style="margin-top: 10px; margin-left: 10px;" onclick="quickDonate(50)">Подкрепи ни</button>
            </div>
        </div>

        <div class="features" style="margin: 4rem 0;">
            <div class="feature-box fade-in">
                <i class="fas fa-heartbeat feature-icon"></i>
                <h3>Ветеринарни грижи</h3>
                <p>Всички животни получават пълни медицински прегледи, ваксинации и стерилизация.</p>
            </div>
            <div class="feature-box fade-in">
                <i class="fas fa-home feature-icon"></i>
                <h3>Безопасен дом</h3>
                <p>Модерни помещения с отопление, чиста вода и ежедневни грижи от екипа.</p>
            </div>
            <div class="feature-box fade-in">
                <i class="fas fa-handshake feature-icon"></i>
                <h3>След осиновяване</h3>
                <p>Поддържаме връзка и помагаме за успешното настаняване на животните.</p>
            </div>
        </div>

        <div class="fade-in" style="background: var(--white); padding: 2.5rem; border-radius: 15px; box-shadow: var(--shadow); margin-top: 2rem; margin-bottom: 3rem;">
            <h3 style="text-align: center; margin-bottom: 20px; color: var(--primary-color); font-size: 1.5rem;">Нашият Екип</h3>
            <p style="text-align: center; margin-bottom: 20px; font-size: 1.05rem;">
                Работим с над <strong><?php echo $volunteers; ?> доброволци</strong>, <strong>5 постоянни служители</strong> и <strong>3 ветеринарни лекари</strong>. 
                Всеки ден посвещаваме на грижата за животните в приюта ни.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: center; margin-top: 30px;">
                <div>
                    <img src="assets/images/MoharemTufanov.jpg" alt="Д-р Туфанов" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid var(--primary-color);">
                    <h4>Д-р Туфанов</h4>
                    <p style="color: #666; font-size: 0.9rem;">Главен ветеринарен лекар</p>
                </div>
                <div>
                    <img src="assets/images/KirilMedarev.png" alt="Кирил Медарев" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid var(--primary-color);">
                    <h4>Кирил Медарев</h4>
                    <p style="color: #666; font-size: 0.9rem;">Координатор на доброволците</p>
                </div>
                <div>
                    <img src="assets/images/YusufKapanak.jpg" alt="Юсуф Капанък" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid var(--primary-color);">
                    <h4>Юсуф Капанък</h4>
                    <p style="color: #666; font-size: 0.9rem;">Ръководител на приюта</p>
                </div>
            </div>
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
                    <label>Име и фамилия </label>
                    <input type="text" name="full_name" placeholder="Име Фамилия" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email </label>
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
        
        // Превключваме видимостта на менюто
        navMenu.classList.toggle('active');
        
        // Сменяме иконата (от три чертички на хикс)
        if(navMenu.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }

     // ==========================================
    // КЛИЕНТСКА ВАЛИДАЦИЯ ЗА ФОРМИТЕ В МОДАЛИТЕ
    // ==========================================

  
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

    // 2. Валидация на даренията (Допълнителна проверка за сума)
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