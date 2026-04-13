<?php
// adoption_success.php
require_once 'db_connect.php';

// Обработка на AJAX заявката за промяна на статуса на животното по ИМЕ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && !empty($_POST['animal_name'])) {
    header('Content-Type: application/json');
    try {
        // Обновяваме статуса на животното по име
        $stmt = $pdo->prepare("UPDATE animals SET status = 'Запазено' WHERE name = ? AND status = 'Налично'");
        $stmt->execute([$_POST['animal_name']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Проверяваме дали процесът по осиновяване е запазил данни в сесията:
if (isset($_SESSION['last_adoption']) && !empty($_SESSION['last_adoption']['id'])) {
    $adoptionData = $_SESSION['last_adoption'];
    // Изчистваме данните от сесията едва след като сме ги взели
    unset($_SESSION['last_adoption']);
} else {
    // Ако няма данни в сесията
    $adoptionData = [
        'id' => null, 
        'animal_name' => 'животно',
        'full_name' => isLoggedIn() ? getCurrentUser()['full_name'] : 'Потребител',
        'email' => isLoggedIn() ? getCurrentUser()['email'] : 'Не е посочен',
        'phone' => isLoggedIn() ? (getCurrentUser()['phone'] ?? 'Не е посочен') : 'Не е посочен',
        'date' => date('d.m.Y H:i:s')
    ];
}

// Взимане на броя артикули в количката за ползване в JavaScript
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявката за осиновяване е изпратена | Приют "Надежда"</title>
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
                    <li><a href="contacts.php">Контакти</a></li>
                </ul>
                <div class="nav-actions">
                    <div class="cart-icon" onclick="openCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
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
        <div class="form-box" style="max-width: 700px; margin: 6rem auto; text-align: center;">
            <div style="font-size: 4rem; color: #4CAF50; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h2 style="color: var(--primary-color); margin-bottom: 15px;">Заявката за осиновяване е изпратена успешно!</h2>
            
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: left;">
                <h4 style="color: var(--secondary-color); margin-bottom: 15px; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">
                    <i class="fas fa-info-circle"></i> Информация за вашата заявка
                </h4>
                
                <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <p><strong>🐾 Животно за осиновяване:</strong> <?php echo escape($adoptionData['animal_name']); ?></p>
                    <p><strong>👤 Вашето име:</strong> <?php echo escape($adoptionData['full_name']); ?></p>
                    <p><strong>📧 Email за обратна връзка:</strong> <?php echo escape($adoptionData['email']); ?></p>
                    <p><strong>📱 Телефон за връзка:</strong> <?php echo escape($adoptionData['phone']); ?></p>
                    <p><strong>📅 Дата и час на заявката:</strong> <?php echo escape($adoptionData['date']); ?></p>
                </div>
                
                <div style="padding: 10px; background: #e8f5e9; border-radius: 8px; margin-top: 10px;">
                    <p style="margin: 0; font-size: 0.9rem;">
                        <i class="fas fa-check" style="color: #4CAF50;"></i>
                        <strong>Статус:</strong> Заявката е получена и се обработва.
                    </p>
                </div>
            </div>
            
            <div class="calendar-container" id="appointmentSection">
                <h3 style="color: var(--secondary-color); margin-bottom: 15px;">
                    <i class="far fa-calendar-alt" style="color: var(--primary-color);"></i> Важна стъпка: Запишете час за посещение
                </h3>
                <p style="margin-bottom: 20px; color: #555; line-height: 1.5;">За да продължим с процеса, е необходимо да се запознаете с животното на място, след което ще проведем кратко интервю и при одобрение - подписваме договор.</p>
                
                <div class="form-group" style="text-align: left;">
                    <label style="display: block; margin-bottom: 8px; color: var(--secondary-color);"><strong>1. Изберете дата:</strong></label>
                    <input type="date" id="appointmentDate" min="<?php echo date('Y-m-d'); ?>" onchange="fetchTimeSlots()">
                </div>

                <div id="slotsContainer" style="display: none; text-align: left; margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--secondary-color);"><strong>2. Изберете свободен час:</strong></label>
                    <div id="timeSlots" class="time-slots"></div>
                </div>

                <div id="errorMessage" style="color: #c62828; background: #ffebee; padding: 10px; border-radius: 5px; margin-top: 15px; display: none; text-align: left; border-left: 4px solid #c62828;"></div>

                <button id="confirmAppointmentBtn" class="btn btn-primary" style="width: 100%; margin-top: 25px; display: none; padding: 15px; font-size: 1.1rem;" onclick="bookAppointment()">
                    <i class="fas fa-calendar-check"></i> Потвърди час за посещение
                </button>
            </div>

            <div id="successMessage" style="display: none; background: #d4edda; color: #155724; padding: 25px; border-radius: 8px; margin-top: 20px; border: 1px solid #c3e6cb; text-align: left;">
                <h4 style="margin-bottom: 15px; font-size: 1.2rem;"><i class="fas fa-check-circle" style="font-size: 1.5rem; vertical-align: middle; margin-right: 10px;"></i> Часът е запазен успешно!</h4>
                <p style="margin: 0; line-height: 1.5;">Очакваме ви в приюта в избраното от вас време. Подгответе си много любов за даване! Животинчето е маркирано като "Запазено" за вас. Ако се наложи да отмените посещението, моля свържете се с нас предварително.</p>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 40px;">
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Към началото
                </a>
                <a href="animals.php" class="btn btn-outline">
                    <img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img"> Виж още животни
                </a>
                <?php if (isLoggedIn()): ?>
                <a href="profile.php" class="btn btn-outline">
                    <i class="fas fa-user"></i> Моят профил
                </a>
                <?php endif; ?>
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
        // Функция за мобилното меню
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

        // Логика за запазване на час
        let selectedTime = null;
        const adoptionId = <?php echo !empty($adoptionData['id']) ? (int)$adoptionData['id'] : 'null'; ?>;
        const animalName = <?php echo json_encode($adoptionData['animal_name']); ?>;

        function fetchTimeSlots() {
            const date = document.getElementById('appointmentDate').value;
            const slotsContainer = document.getElementById('slotsContainer');
            const timeSlotsDiv = document.getElementById('timeSlots');
            const errorMsg = document.getElementById('errorMessage');
            const confirmBtn = document.getElementById('confirmAppointmentBtn');
            
            selectedTime = null;
            confirmBtn.style.display = 'none';
            timeSlotsDiv.innerHTML = '';
            
            if (!date) return;

            fetch(`get_timeslots.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        slotsContainer.style.display = 'none';
                        errorMsg.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.error}`;
                        errorMsg.style.display = 'block';
                    } else {
                        errorMsg.style.display = 'none';
                        slotsContainer.style.display = 'block';
                        
                        data.slots.forEach(slot => {
                            const btn = document.createElement('button');
                            btn.className = `slot-btn ${slot.is_booked ? 'booked' : ''}`;
                            btn.textContent = slot.time;
                            btn.disabled = slot.is_booked;
                            
                            if (!slot.is_booked) {
                                btn.onclick = () => selectTime(btn, slot.time);
                            } else {
                                btn.title = "Този час вече е зает";
                            }
                            
                            timeSlotsDiv.appendChild(btn);
                        });
                    }
                })
                .catch(err => {
                    console.error('Error fetching slots:', err);
                    errorMsg.textContent = "Възникна грешка при зареждане на часовете. Моля, опитайте отново.";
                    errorMsg.style.display = 'block';
                });
        }

        function selectTime(btnElement, time) {
            document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
            btnElement.classList.add('selected');
            selectedTime = time;
            document.getElementById('confirmAppointmentBtn').style.display = 'block';
        }

        function bookAppointment() {
            const date = document.getElementById('appointmentDate').value;
            
            if (!date || !selectedTime) {
                alert("Моля, изберете дата и маркирайте час от показаните бутони.");
                return;
            }

            if (!adoptionId) {
                alert("Системна грешка: Липсва номер на заявката. За да запазите час, трябва да преминете през реално подаване на формата за осиновяване.");
                return;
            }

            const btn = document.getElementById('confirmAppointmentBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('adoption_id', adoptionId);
            formData.append('appointment_date', date);
            formData.append('appointment_time', selectedTime);

            fetch('book_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Заявка за промяна на статуса на животното по ИМЕ след успешен час
                    if (animalName && animalName !== 'животно') {
                        const statusData = new FormData();
                        statusData.append('update_status', 'true');
                        statusData.append('animal_name', animalName);
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            body: statusData
                        }).catch(err => console.error('Грешка при обновяване на статуса:', err));
                    }

                    document.getElementById('appointmentSection').style.display = 'none';
                    document.getElementById('successMessage').style.display = 'block';
                } else {
                    alert(data.message || "Възникна грешка. Моля, опитайте отново.");
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    fetchTimeSlots(); 
                }
            })
            .catch(err => {
                alert("Проблем с връзката към сървъра.");
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // КЛИЕНТСКА ВАЛИДАЦИЯ ЗА ФОРМИТЕ В МОДАЛИТЕ
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
