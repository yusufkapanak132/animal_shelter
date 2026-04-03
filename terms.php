<?php
require_once 'db_connect.php';

// Взимане на броя артикули в количката
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Общи условия | Приют "Надежда"</title>
    <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .policy-container {
            background: var(--white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin: 40px auto;
            max-width: 900px;
            line-height: 1.6;
        }
        .policy-container h1 { color: var(--primary-color); margin-bottom: 30px; text-align: center; }
        .policy-container h2 { color: var(--secondary-color); margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid var(--bg-color); padding-bottom: 10px; }
        .policy-container h3 { color: #444; margin-top: 20px; margin-bottom: 10px; }
        .policy-container p { margin-bottom: 15px; color: #555; }
        .policy-container ul { margin-bottom: 20px; padding-left: 20px; color: #555; }
        .policy-container li { margin-bottom: 8px; }
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
                    <span class="cart-badge" id="cartBadgeCount"><?php echo $cartCount; ?></span>
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
    <div class="policy-container fade-in">
        <h1>Общи условия за ползване</h1>
        <p>Последна актуализация: 27 март 2026 г.</p>
        <p>Добре дошли в уебсайта на Приют "Надежда". Настоящите Общи условия уреждат правилата за използване на нашия сайт, процеса на осиновяване, извършването на дарения и покупката на артикули с благотворителна цел. Чрез използването на сайта, вие се съгласявате с тези условия.</p>

        <h2 id="general">1. Основни положения</h2>
        <p>Приют "Надежда" е неправителствена организация, чиято основна цел е спасяване, лечение и намиране на дом за бездомни животни. Съдържанието на сайта (текстове, снимки, лога) е обект на авторско право и не може да бъде копирано без изрично съгласие.</p>

        <h2 id="adoption">2. Условия за осиновяване и отговорност за животното</h2>
        <p>Осиновяването на животно е сериозен ангажимент, който изисква време, търпение и финансови ресурси. С подаването на заявка за осиновяване, вие декларирате, че сте съгласни със следното:</p>
        <ul>
            <li><strong>Отговорност:</strong> Осиновителят поема пълна отговорност за здравето, храненето, безопасността и добруването на животното до края на неговия живот.</li>
            <li><strong>Процедура:</strong> Подаването на онлайн заявка не гарантира автоматично осиновяване. Екипът на приюта ще се свърже с вас за интервю и евентуална среща на живо.</li>
            <li><strong>Право на отказ:</strong> Приют "Надежда" си запазва правото да откаже осиновяване, ако прецени, че условията, които кандидатът предлага, не са подходящи за конкретното животно.</li>
            <li><strong>Връщане:</strong> Ако по някаква причина осиновителят не може да продължи да се грижи за животното, той е <strong>длъжен</strong> да го върне в Приют "Надежда", а не да го изоставя или предава на трети лица без нашето съгласие.</li>
            <li><strong>Проследяване:</strong> Приютът си запазва правото да изисква снимки и информация за състоянието на животното през първите месеци след осиновяването.</li>
        </ul>

        <h2 id="donations">3. Условия за дарения</h2>
        <p>Всички дарения, направени през сайта (финансови или материални), се използват изключително за нуждите на животните – храна, лечение, консумативи и поддръжка на базата.</p>
        <ul>
            <li>Даренията са доброволни и безвъзмездни.</li>
            <li>След успешно извършено финансово дарение, сумите <strong>не подлежат на възстановяване</strong>, освен в случаи на доказана техническа грешка при транзакцията.</li>
            <li>Приютът се задължава да разходва средствата прозрачно и целесъобразно.</li>
        </ul>

        <h2 id="shop">4. Условия за онлайн магазина (Аксесоари)</h2>
        <p>Закупуването на аксесоари от нашия сайт е форма на подкрепа, като 100% от печалбата отива за приюта.</p>
        <ul>
            <li><strong>Доставка:</strong> Извършва се чрез куриерска фирма за сметка на купувача, освен ако не е упоменато друго.</li>
            <li><strong>Връщане:</strong> Потребителят има право да върне неизползван продукт в рамките на 14 дни от получаването му, съгласно ЗЗП. Разходите по връщането са за сметка на клиента.</li>
        </ul>

        <h2>5. Промени в условията</h2>
        <p>Приют "Надежда" си запазва правото да променя тези Общи условия по всяко време. Актуалната версия винаги ще бъде достъпна на тази страница.</p>
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
                    <li><a href="accessories.php">Аксесоари</a>
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