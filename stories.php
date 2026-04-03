<?php
require_once 'db_connect.php';

// 1. ОБРАБОТКА НА ФОРМАТА 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_story'])) {
    $title = trim($_POST['story_title'] ?? '');
    $description = trim($_POST['story_description'] ?? '');
    $animal_name = trim($_POST['animal_name'] ?? ''); 
    
    if (empty($title) || empty($description) || empty($animal_name)) {
        $_SESSION['story_msg'] = ["type" => "error", "text" => "Моля, попълнете всички текстови полета!"];
    } 
    elseif (!isset($_FILES['story_image']) || $_FILES['story_image']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['story_msg'] = ["type" => "error", "text" => "Грешка при качването на файла. Моля, опитайте отново."];
    } 
    else {
        $fileTmpPath = $_FILES['story_image']['tmp_name'];
        $fileName = $_FILES['story_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = array('avif');

        $fileMimeType = mime_content_type($fileTmpPath);

        if ($fileExtension === 'avif' && $fileMimeType === 'image/avif') {
            $uploadFileDir = 'assets/images/';
            
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM success_stories");
                $totalInDb = $countStmt->fetchColumn();
                $nextNumber = $totalInDb + 1;
                
                $newFileName = 'stories_' . $nextNumber . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $stmt = $pdo->prepare("INSERT INTO success_stories (title, description, image_url, animal_name, status) VALUES (?, ?, ?, ?, 'Не одобрена')");
                    $stmt->execute([$title, $description, $dest_path, $animal_name]);
                    
                    $_SESSION['story_msg'] = ["type" => "success", "text" => "Успешно изпратихте вашата история! Тя ще бъде качена след одобрение от Администратора."];
                } else {
                    $_SESSION['story_msg'] = ["type" => "error", "text" => "Грешка при преместване на файла в папката."];
                }
            } catch (Exception $e) {
                $_SESSION['story_msg'] = ["type" => "error", "text" => "Грешка в базата данни: " . $e->getMessage()];
            }
        } else {
            $_SESSION['story_msg'] = ["type" => "error", "text" => "Невалиден формат! Позволени са само: " . implode(', ', $allowedExtensions)];
        }
    }

    header("Location: stories.php"); 
    exit();
}

// 2. ПОДГОТОВКА НА ДАННИТЕ ЗА ПОКАЗВАНЕ
$storyMessage = '';
if (isset($_SESSION['story_msg'])) {
    $msgType = $_SESSION['story_msg']['type'];
    $msgText = $_SESSION['story_msg']['text'];
    $bgColor = ($msgType === 'success') ? '#d4edda' : '#f8d7da';
    $textColor = ($msgType === 'success') ? '#155724' : '#721c24';
    
    $storyMessage = "<div style='background: $bgColor; color: $textColor; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid currentColor;'>$msgText</div>";
    
    unset($_SESSION['story_msg']);
}


$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // Брой истории на страница
$offset = ($page - 1) * $limit;

// Взимане на общия брой одобрени истории за изчисляване на страниците
$countStmt = $pdo->query("SELECT COUNT(*) FROM success_stories WHERE status = 'Одобрена'");
$totalStories = $countStmt->fetchColumn();
$totalPages = ceil($totalStories / $limit);

// Получаване на историите само за текущата страница
$stmt = $pdo->prepare("SELECT * FROM success_stories WHERE status = 'Одобрена' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$stories = $stmt->fetchAll();

// Статистики
$adoptedThisYear = $pdo->query("SELECT COUNT(*) FROM adoptions WHERE status = 'Завършена' AND YEAR(submitted_at) = YEAR(CURDATE())")->fetchColumn() ?? 0;
$savedLives = $pdo->query("SELECT COUNT(*) FROM adoptions WHERE status = 'Завършена'")->fetchColumn() ?? 1200;
$successRateQuery = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM adoptions WHERE status = 'Завършена') * 100.0 / 
    NULLIF((SELECT COUNT(*) FROM adoptions WHERE status IN ('Завършена', 'Отказана')), 0) as rate");
$successRate = round($successRateQuery->fetchColumn() ?? 0);

$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Щастливи истории | Приют "Надежда"</title>
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
                    <li><a href="accessories.php">Аксесоари</a></li>
                    <li><a href="stories.php" class="active">Истории</a></li>
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
        <h1 class="section-title">Щастливи истории</h1>
        
        <div style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            <p style="font-size: 1.1rem; margin-bottom: 20px;">
                <i class="fas fa-heart" style="color: var(--primary-color); margin-right: 10px;"></i>
                Тези истории са причината да продължаваме! Над <?php echo $savedLives; ?> животни вече са намерили свой дом благодарение на вас.
            </p>
        </div>
        
        <?php echo $storyMessage; ?>

        <?php if (!empty($stories)): ?>
        <div id="stories-grid" class="grid-container">
            <?php foreach ($stories as $story): ?>
            <div class="card fade-in">
                <img src="<?php echo escape($story['image_url']); ?>" alt="<?php echo escape($story['title']); ?>" loading="lazy">
                <div class="card-content">
                    <h3><?php echo escape($story['title']); ?></h3>
                    <p style="margin: 15px 0; flex-grow:1;"><?php echo escape($story['description']); ?></p>
                    <div style="color: var(--primary-color); font-weight: bold; margin-top: 10px;">
                        <i class="fas fa-heart"></i> <?php echo escape($story['animal_name']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <ul class="pagination fade-in">
            <?php
            // Пазим стойността на търсенето в URL-а, за да работи страницирането с филтър
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
        <div style="text-align:center;">
            <button class="btn btn-primary" onclick="openAddStoryModal()" style="font-size: 1.1rem; padding: 10px 20px;">
                <i class="fas fa-plus-circle"></i> Сподели твоята история
            </button>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <i class="fas fa-book-open" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;"></i>
            <h3 style="color: var(--secondary-color); margin-bottom: 10px;">Очаквайте скоро нови истории</h3>
            <p>В момента подготвяме нови щастливи истории за споделяне или очакваме одобрение на изпратените.</p>
        </div>
        <div style="text-align:center; margin-top: 20px;">
            <button class="btn btn-primary" onclick="openAddStoryModal()" style="font-size: 1.1rem; padding: 10px 20px;">
                <i class="fas fa-plus-circle"></i> Сподели твоята история
            </button>
        </div>
        <?php endif; ?>

        <div class="fade-in" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: var(--shadow); text-align: center;">
                <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"><?php echo $adoptedThisYear; ?>+</div>
                <h3 style="color: var(--secondary-color);">Осиновени тази година</h3>
                <p style="color: #666; margin-top: 10px;">И всеки ден стават повече</p>
            </div>
            <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: var(--shadow); text-align: center;">
                <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"><?php echo $savedLives; ?>+</div>
                <h3 style="color: var(--secondary-color);">Общо спасени животи</h3>
                <p style="color: #666; margin-top: 10px;">От създаването на приюта</p>
            </div>
            <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: var(--shadow); text-align: center;">
                <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"><?php echo $successRate; ?>%</div>
                <h3 style="color: var(--secondary-color);">Успешни осиновявания</h3>
                <p style="color: #666; margin-top: 10px;">Животните намират траен дом</p>
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

    <div id="addStoryModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeAddStoryModal()">×</button>
            <div class="modal-header">
                <h3 class="modal-title">Сподели твоята история</h3>
                <p>Разкажете ни как се промени животът ви след осиновяването!</p>
            </div>
            <div id="storyError" style="display:none; background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align:center;"></div>
            <form method="POST" action="stories.php" enctype="multipart/form-data" style="margin-top: 20px;">
                <div class="form-group">
                    <label>Име на животното *</label>
                    <input type="text" name="animal_name" placeholder="Напр. Макс, Луна и т.н." required>
                </div>

                <div class="form-group">
                    <label>Заглавие на историята *</label>
                    <input type="text" name="story_title" placeholder="Напр. Нашият нов член на семейството - Макс" required>
                </div>
                
                <div class="form-group">
                    <label>Качи снимка *</label>
                    <input type="file" name="story_image" accept="image/avif" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-top: 5px;">
                </div>
                
                <div class="form-group">
                    <label>Описание на историята *</label>
                    <textarea rows="5" name="story_description" placeholder="Разкажете ни вашата прекрасна история тук..." required></textarea>
                </div>
                
                <button type="submit" name="add_story" class="btn btn-primary" style="width: 100%;">Изпрати за преглед</button>
            </form>
        </div>
    </div>

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

    function openAddStoryModal() {
        document.getElementById('addStoryModal').style.display = 'flex';
        let errorDiv = document.getElementById('storyError');
        if (errorDiv) errorDiv.style.display = 'none';
    }

    function closeAddStoryModal() {
        document.getElementById('addStoryModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let storyModal = document.getElementById('addStoryModal');
        if (event.target == storyModal) {
            storyModal.style.display = 'none';
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
    
    document.querySelector('input[name="story_image"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const errorDiv = document.getElementById('storyError'); 
        
        if (file) {
            const isAvif = file.name.toLowerCase().endsWith('.avif') || file.type === 'image/avif';
            
            if (!isAvif) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Невалиден формат! Моля, качете само снимки във формат .avif';
                errorDiv.style.display = 'block';
                this.value = ''; 
            } else {
                errorDiv.style.display = 'none';
            }
        }
    });
    </script>
</body>
</html>