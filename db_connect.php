<?php
// db_connect.php
session_start();

$host = 'localhost';
$dbname = 'animal_shelter';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Грешка при връзка с базата данни: " . $e->getMessage());
}

// Функция за безопасно извеждане на данни
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Проверка дали потребителят е логнат
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


// Проверка дали потребителят е администратор
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Администратор';
}

// Връща текущия потребител
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Функция за добавяне в количката
function addToCartAjax($productId) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += 1;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }
    
    return true;
}

// Функция за премахване от количката с AJAX
function removeFromCartAjax($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

// Функция за получаване на броя продукти в количката
function getCartCount() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

// Функция за получаване на общата цена на количката
function getCartTotalPrice() {
    global $pdo;
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    $total = 0;
    foreach ($products as $product) {
        $total += $product['price'] * $_SESSION['cart'][$product['id']];
    }
    
    return $total;

   
}
function getCartItems() {
    global $pdo;
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    // Добавяме количеството
    foreach ($products as &$product) {
        $product['quantity'] = $_SESSION['cart'][$product['id']];
        $product['item_total'] = $product['price'] * $product['quantity'];
    }
    
    return $products;
}
?>