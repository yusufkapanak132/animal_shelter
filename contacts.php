<?php
require_once 'db_connect.php';

// Обработка на контактната форма
$messageSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
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
}

// Взимане на броя артикули в количката за ползване в JavaScript
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакти | Приют "Надежда"</title>
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
                    <li><a href="about.php">За нас</a></li>
                    <li><a href="animals.php">Животни</a></li>
                    <li><a href="accessories.php">Аксесоари</a></li>
                    <li><a href="stories.php">Истории</a></li>
                    <li><a href="signal.php">Сигнал</a></li>
                    <li><a href="contacts.php" class="active">Контакти</a></li>
                </ul>
                
                <div class="nav-actions">
                    <div class="cart-icon" onclick="openCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="cart-badge" id="cartBadgeCount"><?php echo $cartCount; ?></span>
                        <?php else: ?>
                        <span class="cart-badge" id="cartBadgeCount" style="display:none;">0</span>
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
        <h1 class="section-title">Свържи се с нас</h1>
        
        <?php if ($messageSent): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> Съобщението е изпратено успешно! Ще се свържем с вас в най-кратък срок.
        </div>
        <?php elseif (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo escape($error); ?>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; margin-bottom: 3rem;">
            
            <div class="fade-in">
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: var(--shadow); height: 100%;">
                    <h3 style="color: var(--primary-color); margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-marker-alt"></i> Къде сме?
                    </h3>
                    <p style="margin: 10px 0 20px; font-size: 1.1rem;">ул. „Скопие“ 4, Гоце Делчев, България</p>
                    
                    <h3 style="color: var(--primary-color); margin-bottom: 15px; margin-top: 30px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-phone"></i> Телефони
                    </h3>
                    <p style="margin: 10px 0;"><strong>Осиновяване:</strong> 089 373 3552</p>
                    <p style="margin: 10px 0 20px;"><strong>Дарения и партньорства:</strong> 089 373 3552</p>
                    
                    <h3 style="color: var(--primary-color); margin-bottom: 15px; margin-top: 30px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-envelope"></i> Email
                    </h3>
                    <p style="margin: 10px 0;"><strong>Общи въпроси:</strong> yusuf.kapanak@pmggd.bg</p>
                    <p style="margin: 10px 0 20px;"><strong>Осиновяване:</strong> yusuf.kapanak@pmggd.bg</p>
                    
                    <h3 style="color: var(--primary-color); margin-bottom: 15px; margin-top: 30px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-clock"></i> Работно време
                    </h3>
                    <p style="margin: 10px 0 20px; line-height: 1.6;">
                        <strong>Понеделник - Петък:</strong> 9:00 - 18:00<br>
                        <strong>Събота:</strong> 10:00 - 16:00<br>
                        <strong>Неделя:</strong> Само по предварителна уговорка
                    </p>
                    
                    <div style="margin-top: 30px;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2984.7383148929985!2d23.726641076310944!3d41.574907871276665!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14abbf88147dd4b9%3A0xfbbdd5a2d173911b!2z0J_QnNCTICLQr9C90LUg0KHQsNC90LTQsNC90YHQutC4Ig!5e0!3m2!1sen!2sbg!4v1773212911687!5m2!1sen!2sbg" width="100%" height="250" style="border:0; border-radius:10px;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>

            <div class="fade-in">
                <div class="form-box" style="height: 100%;">
                    <h3 style="text-align: center; margin-bottom: 25px; color: var(--primary-color); font-size: 1.5rem;">Изпрати запитване</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Вашите имена *</label>
                            <input type="text" name="full_name" placeholder="Име и фамилия" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['full_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Моля въведете валиден имейл, съдържащ @ и точка" required value="<?php echo isLoggedIn() ? escape(getCurrentUser()['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Телефон</label>
                            <input type="tel" name="phone" placeholder="088 123 4567" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'');" title="Телефонният номер трябва да съдържа точно 10 цифри" value="<?php echo isLoggedIn() ? escape(getCurrentUser()['phone']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Тема на запитването *</label>
                            <select name="subject" required>
                                <option value="">Изберете тема</option>
                                <option value="Осиновяване">Осиновяване</option>
                                <option value="Доброволчество">Доброволчество</option>
                                <option value="Партньорство">Партньорство</option>
                                <option value="Медии и интервюта">Медии и интервюта</option>
                                <option value="Дарения">Дарения</option>
                                <option value="Друго">Друго</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Съобщение *</label>
                            <textarea rows="5" name="message" placeholder="Какво искате да ни попитате или кажете?" required></textarea>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="contact_terms" id="contactTerms" required>
                            <label for="contactTerms">Съгласен/съгласна съм с <a href="privacy.php" target="_blank" style="color: var(--primary-color);">обработката на личните ми данни</a></label>
                        </div>
                        
                        <button type="submit" name="contact_submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Изпрати съобщение</button>
                    </form>
                </div>
            </div>
        </div>

        <div style="margin-top: 4rem;">
            <h3 style="text-align: center; color: var(--secondary-color); margin-bottom: 30px;">Често задавани въпроси</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div style="background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary-color); margin-bottom: 10px;">Мога ли да посетя приюта?</h4>
                    <p style="color: #666; font-size: 1rem;">Да, приютът е отворен за посетители всеки ден в работното време. Препоръчваме предварителна уговорка.</p>
                </div>
                <div style="background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary-color); margin-bottom: 10px;">Какво да донеса като дарение?</h4>
                    <p style="color: #666; font-size: 1rem;">Храна за животните, лекарства, одеяла, играчки и всякакви сухи храни са добре дошли.</p>
                </div>
                <div style="background: var(--white); padding: 1.5rem; border-radius: 10px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary-color); margin-bottom: 10px;">Как става процесът на осиновяване?</h4>
                    <p style="color: #666; font-size: 1rem;">Той включва попълване на форма, запознаване с животното, интервю и подписване на договор.</p>
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

    // 2. Валидация на даренията
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