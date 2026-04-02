    <?php
    require_once 'db_connect.php';

    // 1. AJAX Проверка за съществуващ имейл в реално време
   
    if (isset($_GET['check_email'])) {
        header('Content-Type: application/json');
        $emailToCheck = trim($_GET['check_email']);
        
        // Търсим имейла в базата
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$emailToCheck]);
        
        if ($stmt->fetch()) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }

    // Обработка на формите за вход и регистрация
    $loginError = '';
    $registerError = '';
    $success = '';
    $activeTab = 'login'; // По подразбиране показваме формата за вход

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['login_submit'])) {
            $activeTab = 'login'; 
            
            // Вход
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                if($user['role'] == "Потребител"){
                header('Location: index.php');
                }
                elseif($user['role'] == "Администратор")
                    {
                        header('Location: admin-dashboard.php');
                    }
                exit;
            } else {
                $loginError = 'Грешен имейл или парола!';
            }
            
        } elseif (isset($_POST['register_submit'])) {
            $activeTab = 'register'; 
            
            // Регистрация
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Валидация
            if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
                $registerError = 'Моля, попълнете всички задължителни полета!';
            } elseif ($password !== $confirmPassword) {
                $registerError = 'Паролите не съвпадат!';
            } elseif (strlen($password) < 8) {
                $registerError = 'Паролата трябва да е поне 8 символа!';
            } else {
                // Проверка дали имейлът съществува
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $registerError = 'Този имейл вече е регистриран!';
                } else {
                    // Хеширане на паролата
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Записване в базата
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$fullName, $email, $phone, $hashedPassword]);
                    
                    $success = 'Регистрацията е успешна! Моля, влезте в профила си.';
                    $activeTab = 'login'; // Прехвърляме към вход след успешна регистрация
                }
            }
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
        <title>Вход / Регистрация | Приют "Надежда"</title>
        <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Допълнителни стилове за валидацията */
            .input-error {
                border: 2px solid #dc3545 !important;
                background-color: #fff8f8;
            }
            .input-success {
                border: 2px solid #28a745 !important;
            }
            .validation-message {
                font-size: 0.85rem;
                margin-top: 5px;
                display: block;
            }
            .text-danger { color: #dc3545; }
            .text-success { color: #28a745; }
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
                        <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
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
        <main class="container" style="min-height: 70vh; display: flex; align-items: center; justify-content: center; margin-top: 100px; margin-bottom: 60px;">
            <div class="form-box" style="max-width: 500px; width: 100%;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;">
                        <img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img">
                    </div>
                    <h2 class="section-title" style="margin-bottom: 10px;">Добре дошли!</h2>
                    <p style="color: var(--text-light);">Влезте в профила си или се регистрирайте</p>
                </div>

                <?php if ($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo escape($success); ?>
                </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 30px;">
                    <button id="loginTab" class="btn <?php echo $activeTab === 'login' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 25px 0 0 25px;">Вход</button>
                    <button id="registerTab" class="btn <?php echo $activeTab === 'register' ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius: 0 25px 25px 0;">Регистрация</button>
                </div>

                <form id="loginForm" method="POST" style="display: <?php echo $activeTab === 'login' ? 'block' : 'none'; ?>;">
                    
                    <?php if ($loginError): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo escape($loginError); ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><i class="fas fa-envelope" style="margin-right: 8px;"></i> Имейл адрес</label>
                        <input type="email" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" required value="<?php echo function_exists('isLoggedIn') && isLoggedIn() ? escape(getCurrentUser()['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock" style="margin-right: 8px;"></i> Парола</label>
                        <input type="password" name="password" class="form-control" placeholder="Въведете парола" required>
                        <div style="text-align: right; margin-top: 8px;">
                            <a href="forgot_password.php" style="font-size: 0.9rem; color: var(--primary-color);">Забравена парола?</a>
                        </div>
                    </div>
                    
                    <button type="submit" name="login_submit" class="btn btn-primary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Вход
                    </button>
                </form>

                <form id="registerForm" method="POST" style="display: <?php echo $activeTab === 'register' ? 'block' : 'none'; ?>;">
                    
                    <?php if ($registerError): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo escape($registerError); ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label><i class="fas fa-user" style="margin-right: 8px;"></i> Име и фамилия *</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Иван Иванов" required value="<?php echo isset($_POST['full_name']) ? escape($_POST['full_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope" style="margin-right: 8px;"></i> Имейл адрес *</label>
                        <input type="email" id="regEmail" name="email" placeholder="вашият@email.com" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" required value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                        <span id="emailStatus" class="validation-message"></span>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone" style="margin-right: 8px;"></i> Телефон</label>
                        <input type="tel" name="phone" placeholder="089 123 4567" pattern="[0-9]{10}" maxlength="10" title="Точно 10 цифри" required oninput="this.value=this.value.replace(/[^0-9]/g,'');" value="<?php echo isset($_POST['phone']) ? escape($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock" style="margin-right: 8px;"></i> Парола *</label>
                        <input type="password" name="password" class="form-control" placeholder="Минимум 8 символа" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock" style="margin-right: 8px;"></i> Повтори парола *</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Повторете паролата" required>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="register_terms" id="registerTerms" required>
                        <label for="registerTerms">Съгласен/съгласна съм с <a href="#" style="color: var(--primary-color);">Общите условия</a> и <a href="#" style="color: var(--primary-color);">Политиката за поверителност</a></label>
                    </div>
                
                    <button type="submit" id="regSubmitBtn" name="register_submit" class="btn btn-primary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-user-plus" style="margin-right: 8px;"></i> Създай профил
                    </button>
                </form>
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

        <script>
            // Превключване на табовете
            const loginTabBtn = document.getElementById('loginTab');
            const registerTabBtn = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            loginTabBtn.addEventListener('click', function() {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                this.classList.add('btn-primary');
                this.classList.remove('btn-outline');
                registerTabBtn.classList.remove('btn-primary');
                registerTabBtn.classList.add('btn-outline');
            });
            
            registerTabBtn.addEventListener('click', function() {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                this.classList.add('btn-primary');
                this.classList.remove('btn-outline');
                loginTabBtn.classList.remove('btn-primary');
                loginTabBtn.classList.add('btn-outline');
            });

            // Навигационно меню
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
            // ПРОВЕРКА НА ИМЕЙЛ В РЕАЛНО ВРЕМЕ (AJAX)
            // ==========================================
            const regEmailInput = document.getElementById('regEmail');
            const emailStatus = document.getElementById('emailStatus');
            const regSubmitBtn = document.getElementById('regSubmitBtn');

            // Създаваме таймер за да не пускаме заявка при всяко натискане на клавиш мигновено
            let typingTimer;
            const doneTypingInterval = 500; // Изчаква половин секунда 

            regEmailInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                const email = this.value.trim();
                
                if (email.length === 0 || !email.includes('@')) {
                    regEmailInput.classList.remove('input-error', 'input-success');
                    emailStatus.innerHTML = '';
                    regSubmitBtn.disabled = false;
                    return;
                }

                // Изчакваме потребителят да спре да пише
                typingTimer = setTimeout(() => {
                    fetch(`?check_email=${encodeURIComponent(email)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                // Имейлът е зает
                                regEmailInput.classList.remove('input-success');
                                regEmailInput.classList.add('input-error');
                                emailStatus.innerHTML = '<i class="fas fa-times-circle"></i> Този имейл вече е регистриран!';
                                emailStatus.className = 'validation-message text-danger';
                                regSubmitBtn.disabled = true; // Блокираме бутона
                            } else {
                                // Имейлът е свободен
                                regEmailInput.classList.remove('input-error');
                                regEmailInput.classList.add('input-success');
                                emailStatus.innerHTML = '<i class="fas fa-check-circle"></i> Имейлът е свободен!';
                                emailStatus.className = 'validation-message text-success';
                                regSubmitBtn.disabled = false; // Отключваме бутона
                            }
                        })
                        .catch(error => console.error('Грешка при проверката:', error));
                }, doneTypingInterval);
            });

        </script>
        <script src="script.js"></script>
    </body>
    </html>