<?php
// ajax_get_cart.php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Функция за получаване на продуктите в количката
function getCartWithDetails() {
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

$cartItems = getCartWithDetails();
$cartTotal = getCartTotalPrice();

echo json_encode([
    'items' => $cartItems,
    'total' => $cartTotal,
    'count' => getCartCount()
]);
?>